<?php

declare(strict_types=1);

use function Hyperf\Support\env;

return [
    'secret' => env('JWT_SECRET', 'your-secret-key-change-this-in-production'),
    'expiration' => (int) env('JWT_EXPIRATION', 3600), // 1 hora
];
