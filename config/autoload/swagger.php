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
    'enable' => env('SWAGGER_ENABLE', true),
    'output_file' => BASE_PATH . '/storage/swagger/swagger.json',
    'swagger' => '3.0',
    'scan' => [
        'paths' => [
            BASE_PATH . '/app/Controller',
        ],
    ],
];
