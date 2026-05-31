<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// First request to boot the app
$req = Illuminate\Http\Request::create('/v1/core/health', 'GET');
$resp = $kernel->handle($req);

// Now check DB
use Illuminate\Support\Facades\DB;

echo 'Users:' . PHP_EOL;
try {
    $users = DB::table('users')->get();
    echo '  Count: ' . count($users) . PHP_EOL;
    foreach ($users as $u) {
        echo '  ID: ' . $u->id . ' | Name: ' . $u->name . ' | Email: ' . $u->email . PHP_EOL;
    }
} catch (\Throwable $e) {
    echo '  ERROR: ' . $e->getMessage() . PHP_EOL;
}

echo 'Wallets:' . PHP_EOL;
try {
    $wallets = DB::table('wallets')->get();
    echo '  Count: ' . count($wallets) . PHP_EOL;
    foreach ($wallets as $w) {
        echo '  ID: ' . $w->id . ' | User: ' . $w->user_id . ' | Balance: ' . $w->balance_fils . ' | Currency: ' . $w->currency . PHP_EOL;
    }
} catch (\Throwable $e) {
    echo '  ERROR: ' . $e->getMessage() . PHP_EOL;
}
