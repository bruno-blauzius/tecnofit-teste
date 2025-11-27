<?php

declare(strict_types=1);

namespace App\UseCase\Account;

class UpdateBalanceRequest
{
    public function __construct(
        public readonly string $accountId,
        public readonly float $balance,
    ) {}
}
