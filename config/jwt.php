<?php

declare(strict_types=1);

return [
    'secret' => getenv('JWT_SECRET') ?: 'a-string-secret-at-least-256-bits-long',
    'alg'    => getenv('JWT_ALG') ?: 'HS256',
];
