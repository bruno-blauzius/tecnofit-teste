<?php

declare(strict_types=1);

namespace App\UseCase\Account;

use DateTimeImmutable;

final class WithdrawRequest
{
    public function __construct(
        public readonly string $accountId,
        public readonly float $amount,
        public readonly string $method,         // ex: 'PIX'
        public readonly string $pixType,       // ex: 'email'
        public readonly string $pixKey,        // ex: 'user@example.com'
        public readonly ?DateTimeImmutable $scheduleAt = null, // null = imediato
    ) {
    }

    public function isScheduled(): bool
    {
        return $this->scheduleAt !== null;
    }
}
