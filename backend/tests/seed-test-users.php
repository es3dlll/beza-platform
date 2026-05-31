<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Boot app via health check
$req = Illuminate\Http\Request::create('/v1/core/health', 'GET');
$kernel->handle($req);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

// Check if admin@beza.test already exists
$existing = DB::table('users')->where('email', 'admin@beza.test')->first();
if ($existing) {
    echo 'admin@beza.test exists: ID ' . $existing->id . PHP_EOL;
    echo 'Setting password to admin123...' . PHP_EOL;
    DB::table('users')->where('id', $existing->id)->update([
        'password' => Hash::make('admin123'),
    ]);
} else {
    echo 'Creating admin@beza.test...' . PHP_EOL;
    $adminId = (string) Str::ulid();
    DB::table('users')->insert([
        'id' => $adminId,
        'name' => 'مدير النظام',
        'email' => 'admin@beza.test',
        'password' => Hash::make('admin123'),
        'created_at' => now()->toIso8601String(),
        'updated_at' => now()->toIso8601String(),
    ]);

    // Check if wallet exists
    $wallet = DB::table('wallets')->where('user_id', $adminId)->first();
    if (!$wallet) {
        echo 'Creating admin wallet...' . PHP_EOL;
        DB::table('wallets')->insert([
            'id' => (string) Str::ulid(),
            'user_id' => $adminId,
            'balance_fils' => 100000000,
            'currency' => 'SYP',
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);
    }
}

// Check if user1@beza.test exists
$existing2 = DB::table('users')->where('email', 'user1@beza.test')->first();
if ($existing2) {
    echo 'user1@beza.test exists: ID ' . $existing2->id . PHP_EOL;
} else {
    echo 'Creating user1@beza.test...' . PHP_EOL;
    $userId = (string) Str::ulid();
    DB::table('users')->insert([
        'id' => $userId,
        'name' => 'مستخدم تجريبي',
        'email' => 'user1@beza.test',
        'password' => Hash::make('12345678'),
        'created_at' => now()->toIso8601String(),
        'updated_at' => now()->toIso8601String(),
    ]);

    $wallet = DB::table('wallets')->where('user_id', $userId)->first();
    if (!$wallet) {
        echo 'Creating user wallet...' . PHP_EOL;
        DB::table('wallets')->insert([
            'id' => (string) Str::ulid(),
            'user_id' => $userId,
            'balance_fils' => 50000000,
            'currency' => 'SYP',
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);
    }
}

echo PHP_EOL . 'Final users:' . PHP_EOL;
foreach (DB::table('users')->get() as $u) {
    $w = DB::table('wallets')->where('user_id', $u->id)->first();
    $bal = $w ? $w->balance_fils : 'N/A';
    echo "  {$u->email} | Balance: {$bal}" . PHP_EOL;
}
echo PHP_EOL . 'Done.' . PHP_EOL;
