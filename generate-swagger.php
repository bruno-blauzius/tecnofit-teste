#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use OpenApi\Generator;

$openapi = Generator::scan([__DIR__ . '/app/Controller']);

$jsonFile = __DIR__ . '/storage/swagger/swagger.json';
file_put_contents($jsonFile, $openapi->toJson());

echo "Swagger documentation generated successfully at: $jsonFile\n";
