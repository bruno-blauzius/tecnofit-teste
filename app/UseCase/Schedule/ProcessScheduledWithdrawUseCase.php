<?php

declare(strict_types=1);

namespace App\UseCase\Schedule;

use App\Model\Account;
use App\Model\AccountWithdraw;
use App\Model\AccountTransactionHistory;
use App\Helper\EmailHelper;
use Hyperf\DbConnection\Db;

use function Hyperf\Support\with;

class ProcessScheduledWithdrawUseCase
{
    public function __construct(
        private readonly Account $accountModel,
        private readonly AccountTransactionHistory $transactionHistoryModel,
    ) {}

    /**
     * Processa um saque agendado individual
     *
     * @param AccountWithdraw $withdraw
     * @return array
     */
    public function execute(AccountWithdraw $withdraw): array
    {
        return Db::transaction(function () use ($withdraw) {
            // Buscar a conta
            $account = $this->accountModel->with('user')->find($withdraw->account_id);

            if (!$account) {
                throw new \Exception("Conta não encontrada: {$withdraw->account_id}");
            }

            // Validar saldo
            $amount = (float) $withdraw->amount;
            $currentBalance = (float) $account->balance;

            if ($amount > $currentBalance) {
                throw new \Exception("Saldo insuficiente. Saldo atual: {$currentBalance}, Valor do saque: {$amount}");
            }

            // Deduzir saldo
            $balanceBefore = $currentBalance;
            $newBalance = $currentBalance - $amount;

            if ($newBalance < 0) {
                throw new \Exception("O saldo não pode ficar negativo");
            }

            $account->balance = $newBalance;
            $account->save();

            // Registrar transação no histórico
            $this->recordTransaction(
                $account->id,
                'withdraw',
                $amount,
                $balanceBefore,
                $newBalance,
                'Saque agendado executado',
                $withdraw->id,
                'account_withdraw'
            );

            // Marcar saque como processado
            $withdraw->done = true;
            $withdraw->save();

            // Buscar email do usuário da conta
            $userEmail = $account->user?->email ?? 'cliente@example.com';

            // Enviar email de notificação
            EmailHelper::sendScheduledWithdrawNotification(
                $account->id,
                $amount,
                $withdraw->scheduled_for->format('Y-m-d H:i:s'),
                $userEmail
            );

            return [
                'withdraw_id' => $withdraw->id,
                'account_id' => $account->id,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $newBalance,
                'scheduled_for' => $withdraw->scheduled_for->format('Y-m-d H:i:s'),
                'status' => 'success',
            ];
        });
    }

    /**
     * Registra uma transação no histórico
     */
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
}
