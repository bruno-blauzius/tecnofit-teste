<?php

declare(strict_types=1);

namespace App\UseCase\Schedule;

use App\Model\Account;
use App\Model\AccountWithdraw;
use App\Helper\EmailHelper;
use DateTimeImmutable;
use Hyperf\Coroutine\Parallel;

class ScheduleUseCase
{
    private const MAX_CONCURRENT_JOBS = 10;

    public function __construct(
        private readonly AccountWithdraw $withdrawModel,
        private readonly Account $accountModel,
        private readonly ProcessScheduledWithdrawUseCase $processWithdrawUseCase,
    ) {}

    /**
     * Processa todos os saques agendados que estão prontos para serem executados
     * Utiliza processamento paralelo com Coroutines para melhor performance
     *
     * @return array Resultado do processamento
     */
    public function execute(): array
    {
        $now = new DateTimeImmutable('now');

        // Buscar saques agendados que:
        // - Estão agendados (scheduled = true)
        // - Não foram processados ainda (done = false)
        // - Não tiveram erro (error = false)
        // - A data de agendamento é menor ou igual à data atual
        $scheduledWithdraws = $this->withdrawModel
            ->where('scheduled', true)
            ->where('done', false)
            ->where('error', false)
            ->where('scheduled_for', '<=', $now->format('Y-m-d H:i:s'))
            ->get();

        if ($scheduledWithdraws->isEmpty()) {
            return [
                'total' => 0,
                'processed' => 0,
                'errors' => 0,
                'results' => [],
            ];
        }

        $parallel = new Parallel(self::MAX_CONCURRENT_JOBS);

        foreach ($scheduledWithdraws as $withdraw) {
            $parallel->add(function () use ($withdraw) {
                try {
                    return $this->processWithdrawUseCase->execute($withdraw);
                } catch (\Throwable $e) {
                    $this->handleWithdrawError($withdraw, $e->getMessage());
                    return [
                        'withdraw_id' => $withdraw->id,
                        'status' => 'error',
                        'message' => $e->getMessage(),
                    ];
                }
            });
        }

        $results = $parallel->wait();

        $processed = 0;
        $errors = 0;

        foreach ($results as $result) {
            if (isset($result['status']) && $result['status'] === 'success') {
                $processed++;
            } else {
                $errors++;
            }
        }

        return [
            'total' => $scheduledWithdraws->count(),
            'processed' => $processed,
            'errors' => $errors,
            'results' => $results,
        ];
    }

    /**
     * Trata erro no processamento de saque agendado
     */
    private function handleWithdrawError(AccountWithdraw $withdraw, string $errorMessage): void
    {
        $withdraw->error = true;
        $withdraw->error_reason = $errorMessage;
        $withdraw->save();

        $account = $this->accountModel->with('user')->find($withdraw->account_id);
        $userEmail = $account->user?->email ?? 'cliente@example.com';

        EmailHelper::sendScheduledWithdrawError(
            $withdraw->account_id,
            (float) $withdraw->amount,
            $errorMessage,
            $userEmail
        );
    }
}
