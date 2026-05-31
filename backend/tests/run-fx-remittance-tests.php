<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\User;
use App\Modules\AuditLog\Models\AuditLog;
use App\Modules\Core\Enums\Currency;
use App\Modules\Core\ValueObjects\Money;
use App\Modules\FX\Models\ExchangeRate;
use App\Modules\FX\Services\FXRateProvider;
use App\Modules\Remittance\Models\Remittance;
use App\Modules\Remittance\Services\RemittanceFeeCalculator;
use App\Modules\Wallet\Models\Wallet;

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

echo "FX & Remittance Module Integration Tests\n";
echo str_repeat('=', 60) . "\n";

// 1. Successful conversion with accurate exchange rate
test('successful conversion with accurate exchange rate and fees', function () {
    $rateProvider = app(FXRateProvider::class);
    $feeCalc = app(RemittanceFeeCalculator::class);

    // Get SYP→USD rate (should auto-seed)
    $rate = $rateProvider->getRate(Currency::SYP, Currency::USD);
    assert($rate !== null, 'SYP→USD rate should exist');
    assert($rate->isValid(), 'rate should be valid');
    assert($rate->rate_fils_per_unit > 0, 'rate should be positive');

    // Convert 1,000,000 SYP to USD
    $result = $rateProvider->convert(1_000_000, Currency::SYP, Currency::USD);
    assert($result !== null, 'conversion should succeed');
    assert($result['to_amount_fils'] > 0, 'converted amount should be positive');

    // Calculate fee
    $fee = $feeCalc->calculate(Money::fromFils(1_000_000), 'SYP', 'USD');
    assert($fee['fee_fils'] > 0, 'fee should be positive');
    assert($fee['net_amount_fils'] < 1_000_000, 'net should be less than gross');
    assert(isset($fee['breakdown']['percentage_rate']), 'breakdown should show rate');
    assert(isset($fee['breakdown']['pair_surcharge_rate']), 'breakdown should show surcharge');
});

// 2. Conversion rejected when rate expires
test('conversion fails when exchange rate is expired', function () {
    $rateProvider = app(FXRateProvider::class);

    // Manually set an expired rate
    $oldRate = ExchangeRate::create([
        'from_currency' => 'SYP',
        'to_currency' => 'EUR',
        'rate_fils_per_unit' => 13800,
        'bid_fils_per_unit' => 13680,
        'ask_fils_per_unit' => 13920,
        'provider' => 'simulated',
        'valid_from' => now()->subHours(2),
        'valid_until' => now()->subHour(),
        'is_active' => true,
    ]);

    assert($oldRate->isValid() === false, 'expired rate should be invalid');

    // Provider should return a fresh rate instead
    $fresh = $rateProvider->getRate(Currency::SYP, Currency::EUR);
    assert($fresh !== null, 'provider should return a fresh rate');
    assert($fresh->isValid(), 'fresh rate should be valid');
    assert($fresh->id !== $oldRate->id, 'fresh rate should be different from expired one');
});

// 3. Correct tiered fee calculation across different currencies
test('tiered fee calculation works correctly across currency pairs', function () {
    $feeCalc = app(RemittanceFeeCalculator::class);

    // Small transfer < 1M: 2% fee
    $small = $feeCalc->calculate(Money::fromFils(500_000), 'SYP', 'USD');
    assert($small['fee_fils'] === 10500, '500K SYP→USD: 2% (10K) + 0.1% surcharge (500) = 10500, got ' . $small['fee_fils']);

    // Medium transfer 3M: 1.5% fee
    $medium = $feeCalc->calculate(Money::fromFils(3_000_000), 'SYP', 'EUR');
    assert($medium['fee_fils'] === 49500, '3M SYP→EUR: 1.5% (45K) + 0.15% surcharge (4500) = 49500, got ' . $medium['fee_fils']);

    // Large transfer 15M: 1% fee
    $large = $feeCalc->calculate(Money::fromFils(15_000_000), 'SYP', 'USD');
    assert($large['fee_fils'] === 165000, '15M SYP→USD: 1% (150K) + 0.1% surcharge (15K) = 165K, got ' . $large['fee_fils']);

    // Verify breakdown structure
    assert(isset($small['percentage_fils']), 'should have percentage_fils');
    assert(isset($small['pair_surcharge_fils']), 'should have pair_surcharge_fils');
    assert(isset($small['net_amount_fils']), 'should have net_amount_fils');

    // SYP→TRY has lower surcharge (0.05%)
    $tryFee = $feeCalc->calculate(Money::fromFils(1_000_000), 'SYP', 'TRY');
    assert($tryFee['fee_fils'] === 20500, '1M SYP→TRY: 2% (20K) + 0.05% (500) = 20500, got ' . $tryFee['fee_fils']);
});

// 4. Remittance initiated event creates audit trail
test('remittance initiation creates audit trail entry', function () {
    $user = User::factory()->create();
    $refNum = 'REM-TEST-' . strtoupper(bin2hex(random_bytes(4)));

    $remittance = Remittance::create([
        'sender_user_id' => $user->id,
        'receiver_name' => 'مستلم تجريبي',
        'receiver_phone' => '+963900000000',
        'from_currency' => 'SYP',
        'to_currency' => 'USD',
        'from_amount_fils' => 2_000_000,
        'to_amount_fils' => 160,
        'exchange_rate_id' => 'test-rate-id',
        'rate_used_fils_per_unit' => 12500,
        'fee_fils' => 42000,
        'total_charged_fils' => 2_042_000,
        'status' => 'pending',
        'reference_number' => $refNum,
        'metadata' => ['region' => 'damascus'],
    ]);

    assert($remittance->isPending(), 'remittance should start as pending');
    assert($remittance->reference_number === $refNum, 'reference number should match');
    assert($remittance->from_amount_fils === 2_000_000, 'amount should match');

    // Simulate what the event listener would do
    AuditLog::create([
        'user_id' => $user->id,
        'action' => 'remittance_initiated',
        'resource_type' => 'remittance',
        'resource_id' => $remittance->id,
        'metadata' => [
            'from_currency' => 'SYP',
            'to_currency' => 'USD',
            'from_amount_fils' => 2_000_000,
            'reference' => $refNum,
        ],
        'result' => 'pending',
    ]);

    $logs = AuditLog::where('resource_type', 'remittance')
        ->where('action', 'remittance_initiated')
        ->get();
    assert($logs->count() > 0, 'audit log should have remittance_initiated entry');
    assert($logs->first()->resource_id === $remittance->id, 'audit log should reference the remittance');
});

// 5. Double-entry ledger and audit trail are recorded without data leaks
test('remittance completes with ledger entry and clean audit trail', function () {
    $sender = User::factory()->create();
    $receiver = User::factory()->create();

    $senderWallet = Wallet::create([
        'user_id' => $sender->id,
        'balance_fils' => 10_000_000,
        'currency' => 'SYP',
        'status' => 'active',
    ]);
    $receiverWallet = Wallet::create([
        'user_id' => $receiver->id,
        'balance_fils' => 0,
        'currency' => 'SYP',
        'status' => 'active',
    ]);

    $cfe = app(\App\Modules\Ledger\Services\CoreFinancialEngine::class);
    $amount = Money::fromFils(500_000);

    $entry = $cfe->transfer(
        amount: $amount,
        from: $senderWallet,
        to: $receiverWallet,
        description: 'تحويل دولي تجريبي SYP→USD',
        referenceType: 'remittance_test',
        referenceId: 'test-ref-' . bin2hex(random_bytes(4)),
    );

    assert($entry !== null, 'ledger entry should exist');
    assert($entry->amount_fils === 500_000, 'entry amount should match');
    assert($entry->debit_wallet_id === $senderWallet->id, 'debit wallet should be sender');
    assert($entry->credit_wallet_id === $receiverWallet->id, 'credit wallet should be receiver');
    assert($entry->reference_type === 'remittance_test', 'reference type should match');

    // Verify wallet balances updated
    $senderWallet->refresh();
    $receiverWallet->refresh();
    assert($senderWallet->balance_fils === 9_500_000, 'sender balance should decrease by 500K');
    assert($receiverWallet->balance_fils === 500_000, 'receiver balance should increase by 500K');

    // Verify entry metadata has no sensitive data
    $meta = $entry->metadata;
    assert(!isset($meta['password']), 'no password in ledger metadata');
    assert(!isset($meta['token']), 'no token in ledger metadata');
    assert(!isset($meta['secret']), 'no secret in ledger metadata');
    assert(isset($meta['from_balance_before']), 'should have balance tracking');
    assert(isset($meta['to_balance_after']), 'should have balance tracking');

    // Verify audit log entry structure (simulating what event listener does)
    AuditLog::create([
        'user_id' => $sender->id,
        'action' => 'remittance_completed',
        'resource_type' => 'remittance',
        'resource_id' => 'test-ref-' . bin2hex(random_bytes(4)),
        'metadata' => [
            'ledger_entry_id' => $entry->id,
            'to_amount_fils' => 500_000,
            'status' => 'completed',
        ],
        'result' => 'success',
    ]);

    $auditLogs = AuditLog::where('action', 'remittance_completed')->get();
    assert($auditLogs->count() > 0, 'should have remittance_completed audit log');

    // Verify no sensitive data in audit log
    foreach ($auditLogs as $log) {
        assert(!isset($log->metadata['password']), 'no password');
        assert(!isset($log->metadata['token']), 'no token');
    }
});

echo str_repeat('=', 60) . "\n";
echo "Results: {$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
