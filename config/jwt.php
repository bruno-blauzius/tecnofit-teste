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
return [
    'secret' => getenv('JWT_SECRET') ?: 'a-string-secret-at-least-256-bits-long',
    'alg' => getenv('JWT_ALG') ?: 'HS256',
];
