<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Boot and get wallet IDs
$req = Illuminate\Http\Request::create('/v1/core/health', 'GET');
$resp = $kernel->handle($req);
$kernel->terminate($req, $resp);

use Illuminate\Support\Facades\DB;

$admin = DB::table('users')->where('email', 'admin@beza.test')->first();
$user1 = DB::table('users')->where('email', 'user1@beza.test')->first();
$adminWallet = DB::table('wallets')->where('user_id', $admin->id)->first();
$user1Wallet = DB::table('wallets')->where('user_id', $user1->id)->first();

echo "Admin wallet: {$adminWallet->id} (balance: {$adminWallet->balance_fils})\n";
echo "User1 wallet: {$user1Wallet->id} (balance: {$user1Wallet->balance_fils})\n";

// Login
$kernel2 = $app->make(Illuminate\Contracts\Http\Kernel::class);
$req2 = Illuminate\Http\Request::create('/v1/auth/login', 'POST', [], [], [], [
    'CONTENT_TYPE' => 'application/json',
    'HTTP_ACCEPT' => 'application/json',
], json_encode(['email' => 'admin@beza.test', 'password' => 'admin123', 'device_id' => 'test-device-001']));
$resp2 = $kernel2->handle($req2);
$data2 = json_decode($resp2->getContent(), true);
$token = $data2['data']['token'] ?? null;
echo 'Login: ' . $resp2->getStatusCode() . "\n";
echo "Token: " . substr($token ?? 'NONE', 0, 20) . "...\n\n";

if (!$token) exit(1);

// Now transfer — new kernel to avoid state issues
$kernel3 = $app->make(Illuminate\Contracts\Http\Kernel::class);
$req3 = Illuminate\Http\Request::create('/v1/wallet/transfer', 'POST', [], [], [], [
    'CONTENT_TYPE' => 'application/json',
    'HTTP_ACCEPT' => 'application/json',
    'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
], json_encode([
    'to_wallet_id' => $user1Wallet->id,
    'amount_fils' => 1000,
    'currency' => 'SYP',
]));
$resp3 = $kernel3->handle($req3);
echo 'Transfer status: ' . $resp3->getStatusCode() . "\n";

$data3 = json_decode($resp3->getContent(), true);
if ($data3) {
    echo json_encode($data3, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
} else {
    echo 'Raw: ' . substr($resp3->getContent(), 0, 300) . "\n";
}

// Check final balances
echo "\nFinal balances:\n";
$adminW2 = DB::table('wallets')->where('user_id', $admin->id)->first();
$user1W2 = DB::table('wallets')->where('user_id', $user1->id)->first();
echo "  Admin: {$adminW2->balance_fils}\n";
echo "  User1: {$user1W2->balance_fils}\n";
echo "  Diff: " . ($admin->balance_fils - $adminW2->balance_fils) . " / " . ($user1W2->balance_fils - $user1->balance_fils) . "\n";
