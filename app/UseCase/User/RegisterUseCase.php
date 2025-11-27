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

namespace App\UseCase\User;

use App\Model\Account;
use App\Model\User;
use Hyperf\DbConnection\Db;

class RegisterUseCase
{
    public function execute(RegisterRequest $request): array
    {
        return Db::transaction(function () use ($request) {
            // Cria a conta com balance 0
            $account = Account::create([
                'name' => $request->name,
                'balance' => 0.00,
            ]);

            // Cria o usuário vinculado à conta
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => $request->password,
                'account_id' => $account->id,
            ]);

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'account_id' => $user->account_id,
                'account' => [
                    'id' => $account->id,
                    'balance' => (float) $account->balance,
                ],
                'created_at' => $user->created_at->toDateTimeString(),
                'updated_at' => $user->updated_at->toDateTimeString(),
            ];
        });
    }
}
