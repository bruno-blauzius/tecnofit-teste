#!/usr/bin/env php
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
require_once __DIR__ . '/vendor/autoload.php';

use OpenApi\Generator;

$openapi = Generator::scan([__DIR__ . '/app/Controller']);

$jsonFile = __DIR__ . '/storage/swagger/swagger.json';
file_put_contents($jsonFile, $openapi->toJson());

echo "Swagger documentation generated successfully at: {$jsonFile}\n";
