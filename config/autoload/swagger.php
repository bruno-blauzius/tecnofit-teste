<?php

declare(strict_types=1);

use function Hyperf\Support\env;

return [
    'enable' => env('SWAGGER_ENABLE', true),
    'output_file' => BASE_PATH . '/storage/swagger/swagger.json',
    'swagger' => '3.0',
    'scan' => [
        'paths' => [
            BASE_PATH . '/app/Controller',
        ],
    ],
];
