<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\User;
use App\Modules\Core\ValueObjects\Money;
use App\Modules\Ledger\Models\LedgerEntry;
use App\Modules\Ledger\Services\CoreFinancialEngine;
use App\Modules\Wallet\Models\Wallet;
use Illuminate\Support\Facades\Hash;

$passed = 0;
$failed = 0;

function test(string $name, callable $assert): void {
    global $passed, $failed;
    try {
        $assert();
        echo "  PASS: {$name}\n";
        $passed++;
    } catch (Throwable $e) {
        echo "  FAIL: {$name} - {$e->getMessage()}\n  at {$e->getFile()}:{$e->getLine()}\n";
        $failed++;
    }
}

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$kernel->call('migrate:fresh', ['--force' => true]);

echo "Auth Flow Integration Tests\n";
echo str_repeat('=', 60) . "\n";

// 1. Register + wallet via DB
test('user creation creates wallet with zero balance', function () {
    $user = User::factory()->create([
        'name' => 'أحمد',
        'email' => 'ahmed_' . uniqid() . '@test.com',
    ]);

    Wallet::create([
        'user_id' => $user->id,
        'balance_fils' => 0,
        'currency' => 'SYP',
    ]);

    $user->load('wallet');
    assert($user->wallet !== null, 'user should have wallet');
    assert($user->wallet->balance_fils === 0, 'wallet should be 0');
    assert($user->wallet->currency === 'SYP', 'currency should be SYP');
});

// 2. Custom API token creation
test('user can create api token', function () {
    $user = User::factory()->create(['password' => Hash::make('password123')]);
    Wallet::factory()->create(['user_id' => $user->id]);

    $token = $user->createApiToken();

    assert($token !== null, 'should have token');
    assert(strlen($token) === 64, 'token should be 64 hex chars');

    // Verify token is stored hashed
    $user->refresh();
    assert($user->remember_token === hash('sha256', $token), 'token should be stored hashed');
});

// 3. Successful transfer
test('successful transfer between wallets', function () {
    $sender = User::factory()->create();
    $senderWallet = Wallet::factory()->create([
        'user_id' => $sender->id,
        'balance_fils' => 5000,
    ]);
    $receiver = User::factory()->create();
    $receiverWallet = Wallet::factory()->create([
        'user_id' => $receiver->id,
        'balance_fils' => 0,
    ]);

    $engine = app(CoreFinancialEngine::class);
    $money = Money::fromFils(2000);
    $entry = $engine->transfer($money, $senderWallet, $receiverWallet, 'تحويل تجريبي');

    assert($entry->amount_fils === 2000, 'entry should be 2000');

    $senderWallet->refresh();
    $receiverWallet->refresh();

    assert($senderWallet->balance_fils === 3000, 'sender should have 3000');
    assert($receiverWallet->balance_fils === 2000, 'receiver should have 2000');
});

// 4. Insufficient balance
test('transfer fails with insufficient balance', function () {
    $sender = User::factory()->create();
    $senderWallet = Wallet::factory()->create([
        'user_id' => $sender->id,
        'balance_fils' => 500,
    ]);
    $receiver = User::factory()->create();
    $receiverWallet = Wallet::factory()->create([
        'user_id' => $receiver->id,
        'balance_fils' => 0,
    ]);

    $engine = app(CoreFinancialEngine::class);
    $money = Money::fromFils(2000);

    try {
        $engine->transfer($money, $senderWallet, $receiverWallet, 'محاولة فاشلة');
        assert(false, 'should have thrown');
    } catch (\RuntimeException $e) {
        assert(str_contains($e->getMessage(), 'الرصيد غير كاف'), 'should say insufficient balance');
    }
});

// 5. Ledger double entry
test('ledger has correct double entry after transfer', function () {
    $sender = User::factory()->create();
    $senderWallet = Wallet::factory()->create([
        'user_id' => $sender->id,
        'balance_fils' => 10000,
    ]);
    $receiver = User::factory()->create();
    $receiverWallet = Wallet::factory()->create([
        'user_id' => $receiver->id,
        'balance_fils' => 0,
    ]);

    $engine = app(CoreFinancialEngine::class);
    $money = Money::fromFils(3000);
    $engine->transfer($money, $senderWallet, $receiverWallet, 'تحويل', 'test', 'ref-001');

    $entries = LedgerEntry::where('amount_fils', 3000)->get();
    assert($entries->count() === 1, 'should have 1 entry');

    $entry = $entries->first();
    assert($entry->debit_wallet_id === $senderWallet->id, 'debit should match sender');
    assert($entry->credit_wallet_id === $receiverWallet->id, 'credit should match receiver');
    assert($entry->reference_id === 'ref-001', 'reference should match');

    $meta = $entry->metadata;
    assert($meta['from_balance_before'] === 10000, 'sender before should be 10000');
    assert($meta['from_balance_after'] === 7000, 'sender after should be 7000');
    assert($meta['to_balance_before'] === 0, 'receiver before should be 0');
    assert($meta['to_balance_after'] === 3000, 'receiver after should be 3000');
});

echo str_repeat('=', 60) . "\n";
echo "Results: {$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
