<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

try {
    $users = $app->make('db')->table('users')->get();
    echo 'Users table exists. Count: ' . count($users) . PHP_EOL;
    foreach ($users as $u) {
        echo '  ID: ' . $u->id . ' | Name: ' . $u->name . ' | Email: ' . $u->email . PHP_EOL;
    }
} catch (\Throwable $e) {
    echo 'ERROR: ' . $e->getMessage() . PHP_EOL;
}

// Check wallets table
try {
    $wallets = $app->make('db')->table('wallets')->get();
    echo PHP_EOL . 'Wallets table exists. Count: ' . count($wallets) . PHP_EOL;
    foreach ($wallets as $w) {
        echo '  ID: ' . $w->id . ' | User: ' . $w->user_id . ' | Balance: ' . $w->balance_fils . ' | Currency: ' . $w->currency . PHP_EOL;
    }
} catch (\Throwable $e) {
    echo 'Wallets ERROR: ' . $e->getMessage() . PHP_EOL;
}

// Check available tables
echo PHP_EOL . 'Tables: ';
try {
    $tables = $app->make('db')->connection()->getSchemaBuilder()->getTables();
    foreach ($tables as $t) {
        echo $t['name'] . ' ';
    }
    echo PHP_EOL;
} catch (\Throwable $e) {
    echo 'ERROR: ' . $e->getMessage() . PHP_EOL;
}
