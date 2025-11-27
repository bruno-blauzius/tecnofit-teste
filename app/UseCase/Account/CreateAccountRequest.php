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

class CreateAccountRequest
{
    public function __construct(
        public readonly string $name,
        public readonly float $balance,
        public readonly ?string $id = null,
    ) {
    }
}
