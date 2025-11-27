<?php

ini_set('display_errors', 'on');
ini_set('display_startup_errors', 'on');
error_reporting(E_ALL);

! defined('BASE_PATH') && define('BASE_PATH', __DIR__);

require BASE_PATH . '/vendor/autoload.php';
$container = require BASE_PATH . '/config/container.php';

use Hyperf\DbConnection\Db;

echo "Dropping tables...\n";
Db::statement('SET FOREIGN_KEY_CHECKS=0');
Db::statement('DROP TABLE IF EXISTS pix_keys');
Db::statement('DROP TABLE IF EXISTS account_withdraw_pix');
Db::statement('DROP TABLE IF EXISTS account_withdraw');
Db::statement('DROP TABLE IF EXISTS account_transaction_history');
Db::statement('DROP TABLE IF EXISTS users');
Db::statement('DROP TABLE IF EXISTS account');
Db::statement('SET FOREIGN_KEY_CHECKS=1');

echo "Running schema...\n";
$schema = file_get_contents(BASE_PATH . '/test/schema.sql');
$statements = array_filter(array_map('trim', explode(';', $schema)));

foreach ($statements as $i => $statement) {
    if (!empty($statement)) {
        echo "Executing statement " . ($i + 1) . "...\n";
        Db::statement($statement);
    }
}

echo "Database reset successfully!\n";
