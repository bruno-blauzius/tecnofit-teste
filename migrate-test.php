#!/usr/bin/env php
<?php

declare(strict_types=1);

// Set test environment variables BEFORE loading anything
putenv('DB_HOST=db-test');
putenv('DB_PORT=3306');
putenv('DB_DATABASE=hyperf_test');
putenv('DB_USERNAME=test');
putenv('DB_PASSWORD=test');

$_ENV['DB_HOST'] = 'db-test';
$_ENV['DB_PORT'] = '3306';
$_ENV['DB_DATABASE'] = 'hyperf_test';
$_ENV['DB_USERNAME'] = 'test';
$_ENV['DB_PASSWORD'] = 'test';

// Now run the migration command
require __DIR__ . '/bin/hyperf.php';
