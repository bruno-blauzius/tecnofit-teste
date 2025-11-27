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

use App\Constants\ErrorCode;
use App\Exception\BusinessException;
use App\Helper\EmailHelper;
use App\Model\Account;
use App\Model\AccountTransactionHistory;

class UpdateBalanceUseCase
{
    public function __construct(
        private Account $account,
        private AccountTransactionHistory $transactionHistoryModel
    ) {
    }

    public function execute(UpdateBalanceRequest $request): array
    {
        $account = $this->account->with('user')->find($request->accountId);

        if (! $account) {
            throw new BusinessException(
                ErrorCode::ACCOUNT_NOT_FOUND,
                'Conta não encontrada.'
            );
        }

        $balanceBefore = (float) $account->balance;
        $balanceAfter = $balanceBefore + (float) $request->balance;

        if ($balanceAfter < 0) {
            throw new BusinessException(
                ErrorCode::INVALID_BALANCE,
                'O saldo não pode ser negativo.'
            );
        }

        $amount = abs($balanceAfter - $balanceBefore);
        $transactionType = $balanceAfter > $balanceBefore ? 'credit' : 'debit';

        $account->balance = $balanceAfter;
        $account->save();

        // Buscar email do usuário da conta
        $userEmail = $account->user?->email ?? 'cliente@example.com';

        // Registrar transação no histórico
        $this->recordTransaction(
            $account->id,
            $transactionType,
            $amount,
            $balanceBefore,
            $balanceAfter,
            'Atualização de saldo',
            null,
            'balance_update'
        );

        // Enviar notificação por email
        EmailHelper::sendTransactionNotification(
            $account->id,
            $transactionType,
            $amount,
            $balanceBefore,
            $balanceAfter,
            'Atualização de saldo',
            $userEmail
        );

        return $account->toArray();
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
}
