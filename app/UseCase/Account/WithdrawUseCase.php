<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace App\UseCase\Account;

use App\Helper\EmailHelper;
use App\Model\Account;
use App\Model\AccountTransactionHistory;
use App\Model\AccountWithdraw;
use App\Model\AccountWithdrawPix;
use App\Model\PixKey;
use App\UseCase\Account\Exception\AccountNotFoundException;
use App\UseCase\Account\Exception\InsufficientBalanceException;
use App\UseCase\Account\Exception\InvalidScheduleException;
use App\UseCase\Account\Exception\PixKeyNotFoundException;
use DateTimeImmutable;
use Hyperf\DbConnection\Db;
use Hyperf\Metric\Contract\CounterInterface;
use Hyperf\Metric\Contract\HistogramInterface;
use Hyperf\Metric\Contract\MetricFactoryInterface;
use LogicException;
use Psr\Container\ContainerInterface;
use Throwable;

final class WithdrawUseCase
{
    private CounterInterface $withdrawCounter;

    private HistogramInterface $withdrawDuration;

    private CounterInterface $withdrawAmount;

    public function __construct(
        private readonly Account $accountModel,
        private readonly AccountWithdraw $withdrawModel,
        private readonly AccountWithdrawPix $withdrawPixModel,
        private readonly AccountTransactionHistory $transactionHistoryModel,
        private readonly PixKey $pixKeyModel,
        ContainerInterface $container,
    ) {
        $factory = $container->get(MetricFactoryInterface::class);

        $this->withdrawCounter = $factory->makeCounter(
            'withdraw_total',
            ['status', 'type']
        );

        $this->withdrawDuration = $factory->makeHistogram(
            'withdraw_duration_seconds',
            ['type']
        );

        $this->withdrawAmount = $factory->makeCounter(
            'withdraw_amount_total',
            ['type']
        );
    }

    public function execute(WithdrawRequest $request): array
    {
        $type = $request->isScheduled() ? 'scheduled' : 'immediate';
        $startTime = microtime(true);

        try {
            $result = Db::transaction(function () use ($request) {
                $account = $this->findAccount($request->accountId);

                $this->validatePixKey($account->id, $request->pixKey);

                $this->validateSchedule($request);
                $this->validateAmount($account, $request->amount);

                if (! $request->isScheduled()) {
                    $this->deductBalance($account, $request->amount);
                }

                $withdraw = $this->createWithdraw($account, $request);
                $this->createWithdrawPix($withdraw, $request);

                // Registrar histórico e enviar email para saque agendado
                if ($request->isScheduled()) {
                    $this->recordAndNotifyScheduledWithdraw(
                        $account,
                        $request->amount,
                        $request->scheduleAt->format('Y-m-d H:i:s'),
                        $withdraw->id
                    );
                }

                return [
                    'withdraw_id' => $withdraw->id,
                    'account_id' => $account->id,
                    'amount' => $request->amount,
                    'scheduled' => $request->isScheduled(),
                    'schedule_at' => $request->scheduleAt?->format('Y-m-d H:i:s'),
                    'balance' => $account->balance,
                ];
            });

            // Métrica de sucesso
            $this->withdrawCounter
                ->with('success', $type)
                ->add(1);

            $this->withdrawAmount
                ->with($type)
                ->add((int) ($request->amount * 100)); // Converte para centavos (int)

            return $result;
        } catch (Throwable $e) {
            // Métrica de erro
            $this->withdrawCounter
                ->with('error', $type)
                ->add(1);

            throw $e;
        } finally {
            $duration = microtime(true) - $startTime;
            $this->withdrawDuration->with($type)->put($duration);
        }
    }

    private function findAccount(string $accountId): Account
    {
        /** @var null|Account $account */
        $account = $this->accountModel->with('user')->find($accountId);

        if ($account === null) {
            throw new AccountNotFoundException('Conta não encontrada.');
        }

        return $account;
    }

    private function validatePixKey(string $accountId, string $pixKey): void
    {
        /** @var null|PixKey $pixKey */
        $pixKey = $this->pixKeyModel
            ->where('account_id', $accountId)
            ->where('key_value', $pixKey)
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->first();

        if ($pixKey === null) {
            throw new PixKeyNotFoundException(
                'Nenhuma chave PIX ativa encontrada para esta conta. Cadastre uma chave PIX antes de realizar saques.'
            );
        }
    }

    private function validateSchedule(WithdrawRequest $request): void
    {
        if (! $request->isScheduled()) {
            return;
        }

        $now = new DateTimeImmutable('now');

        if ($request->scheduleAt < $now) {
            throw new InvalidScheduleException('Não é permitido agendar saque para o passado.');
        }
    }

    private function validateAmount(Account $account, float $amount): void
    {
        if ($amount <= 0) {
            throw new InsufficientBalanceException('O valor do saque deve ser maior que zero.');
        }

        /*
         * Para saque imediato, o saldo precisa estar disponível agora.
         * Para saque agendado, depende da regra de negócio:
         * aqui vou seguir as regras que você passou:
         * - Não é permitido sacar valor maior que o saldo disponível.
         * - O saldo não pode ficar negativo.
         */
        if ($amount > $account->balance) {
            throw new InsufficientBalanceException('Saldo insuficiente para o saque.');
        }
    }

    private function deductBalance(Account $account, float $amount): void
    {
        $balanceBefore = (float) $account->balance;
        $newBalance = $account->balance - $amount;

        if ($newBalance < 0) {
            throw new InsufficientBalanceException('O saldo da conta não pode ficar negativo.');
        }

        $account->balance = $newBalance;
        $account->save();

        // Buscar email do usuário da conta
        $userEmail = $this->getUserEmail($account);

        // Registrar no histórico de transações
        $this->recordTransaction(
            $account->id,
            'withdraw',
            $amount,
            $balanceBefore,
            (float) $newBalance,
            'Saque realizado',
            null,
            'account_withdraw'
        );

        // Enviar notificação por email
        EmailHelper::sendTransactionNotification(
            $account->id,
            'withdraw',
            $amount,
            $balanceBefore,
            (float) $newBalance,
            'Saque realizado',
            $userEmail
        );
    }

    private function createWithdraw(Account $account, WithdrawRequest $request): AccountWithdraw
    {
        /** @var AccountWithdraw $withdraw */
        $withdraw = $this->withdrawModel->newModelInstance();

        $withdraw->account_id = $account->id;
        $withdraw->method = $request->method;
        $withdraw->amount = $request->amount;
        $withdraw->scheduled = $request->isScheduled();
        $withdraw->scheduled_for = $request->scheduleAt?->format('Y-m-d H:i:s');
        $withdraw->done = ! $request->isScheduled(); // done = true se não for agendado
        $withdraw->error = false;
        $withdraw->error_reason = null;

        $withdraw->save();

        return $withdraw;
    }

    private function createWithdrawPix(AccountWithdraw $withdraw, WithdrawRequest $request): void
    {
        if ($request->pixType !== 'email') {
            throw new LogicException('Somente chaves PIX do tipo email são suportadas atualmente.');
        }

        /** @var AccountWithdrawPix $pix */
        $pix = $this->withdrawPixModel->newModelInstance();

        $pix->account_withdraw_id = $withdraw->id;
        $pix->type = $request->pixType;
        $pix->key_value = $request->pixKey;

        $pix->save();
    }

    private function recordAndNotifyScheduledWithdraw(
        Account $account,
        float $amount,
        string $scheduledFor,
        string $withdrawId
    ): void {
        $currentBalance = (float) $account->balance;
        $description = 'Saque agendado para ' . $scheduledFor;

        // Buscar email do usuário da conta
        $userEmail = $account->user?->email ?? 'cliente@example.com';

        // Registrar no histórico de transações
        $this->recordTransaction(
            $account->id,
            'withdraw',
            $amount,
            $currentBalance,
            $currentBalance,
            $description,
            $withdrawId,
            'account_withdraw'
        );

        // Enviar notificação por email
        EmailHelper::sendTransactionNotification(
            $account->id,
            'withdraw',
            $amount,
            $currentBalance,
            $currentBalance,
            $description,
            $userEmail
        );
    }

    private function recordTransaction(
        string $accountId,
        string $type,
        float $amount,
        float $balanceBefore,
        float $balanceAfter,
        ?string $description = null,
        ?string $referenceId = null,
        ?string $referenceType = null
    ): void {
        /** @var AccountTransactionHistory $transaction */
        $transaction = $this->transactionHistoryModel->newModelInstance();

        $transaction->account_id = $accountId;
        $transaction->type = $type;
        $transaction->amount = $amount;
        $transaction->balance_before = $balanceBefore;
        $transaction->balance_after = $balanceAfter;
        $transaction->description = $description;
        $transaction->reference_id = $referenceId;
        $transaction->reference_type = $referenceType;

        $transaction->save();
    }

    /**
     * Obtém o email do usuário associado à conta.
     */
    private function getUserEmail(Account $account): string
    {
        $user = $account->user;
        return $user?->email ?? 'cliente@example.com';
    }
}
