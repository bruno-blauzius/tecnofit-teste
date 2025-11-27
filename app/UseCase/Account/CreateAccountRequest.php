<?php

declare(strict_types=1);

namespace App\UseCase\Account;

class CreateAccountRequest
{
    public function __construct(
        public readonly string $name,
        public readonly float $balance,
        public readonly ?string $id = null,
    ) {}
}
