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

class UpdateBalanceRequest
{
    public function __construct(
        public readonly string $accountId,
        public readonly float $balance,
    ) {
    }
}
