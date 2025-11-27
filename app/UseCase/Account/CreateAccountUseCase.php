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

class CreateAccountUseCase
{
    public function __construct(
        private AccountTransactionHistory $transactionHistoryModel
    ) {
    }

    public function execute(CreateAccountRequest $request): array
    {
        $account = Account::create([
            'name' => $request->name,
            'balance' => $request->balance,
        ]);

        // Se a conta foi criada com saldo inicial, registrar e notificar
        if ($request->balance > 0) {
            // Buscar email do usuário da conta
            $userEmail = $this->getUserEmail($account);

            $this->recordTransaction(
                $account->id,
                'deposit',
                (float) $request->balance,
                0.0,
                (float) $request->balance,
                'Saldo inicial da conta',
                null,
                'account_creation'
            );

            EmailHelper::sendTransactionNotification(
                $account->id,
                'deposit',
                (float) $request->balance,
                0.0,
                (float) $request->balance,
                'Saldo inicial da conta',
                $userEmail
            );
        }

        return [
            'id' => $account->id,
            'balance' => (float) $account->balance,
            'created_at' => $account->created_at?->toIso8601String(),
            'updated_at' => $account->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Registra uma transação no histórico.
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

    /**
     * Obtém o email do usuário associado à conta.
     */
    private function getUserEmail(Account $account): string
    {
        $user = $account->user;
        return $user?->email ?? 'cliente@example.com';
    }
}
