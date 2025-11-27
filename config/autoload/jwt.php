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
use function Hyperf\Support\env;

return [
    'secret' => env('JWT_SECRET', 'your-secret-key-change-this-in-production'),
    'expiration' => (int) env('JWT_EXPIRATION', 3600), // 1 hora
];
