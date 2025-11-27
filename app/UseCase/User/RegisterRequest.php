<?php

declare(strict_types=1);

namespace App\UseCase\User;

class RegisterRequest
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
    ) {}
}
