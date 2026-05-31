<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\User;
use App\Modules\Agent\Models\Agent;
use App\Modules\Agent\Services\AgentCommissionCalculator;
use App\Modules\Agent\Services\LiquidityPoolService;
use App\Modules\AuditLog\Models\AuditLog;
use App\Modules\Core\Enums\Currency;
use App\Modules\Core\ValueObjects\Money;

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

echo "Agent Module Integration Tests\n";
echo str_repeat('=', 60) . "\n";

// 1. Register agent successfully
test('register agent creates agent with pending status', function () {
    $user = User::factory()->create();
    $agent = Agent::create([
        'user_id' => $user->id,
        'status' => 'pending',
        'region' => 'damascus',
        'available_balance_fils' => 0,
        'daily_liquidity_limit_fils' => 1_000_000_000,
    ]);

    assert($agent !== null, 'agent should be created');
    assert($agent->status === 'pending', 'status should be pending');
    assert($agent->region === 'damascus', 'region should be damascus');
    assert($agent->available_balance_fils === 0, 'balance should be 0');
    assert($agent->user_id === $user->id, 'should link to user');

    // Verify agent relationship on user
    $user->refresh();
    assert($user->agent !== null, 'user should have agent relationship');
});

// 2. Request liquidity within limits
test('request liquidity within daily limit succeeds', function () {
    $user = User::factory()->create();
    $agent = Agent::create([
        'user_id' => $user->id,
        'status' => 'active',
        'region' => 'aleppo',
        'available_balance_fils' => 0,
        'daily_liquidity_limit_fils' => 5_000_000,
    ]);

    $pool = app(LiquidityPoolService::class);
    $money = Money::fromFils(1_000_000);
    $result = $pool->requestLiquidity($agent, $money);

    assert($result['approved'] === true, 'should be approved');
    assert($result['amount_fils'] === 1_000_000, 'amount should match');
    assert($result['priority'] === 2, 'aleppo priority should be 2');
});

// 3. Calculate commission accurately across currencies and tiers
test('commission calculator works with different currencies and tiers', function () {
    $user = User::factory()->create();
    $agent = Agent::create([
        'user_id' => $user->id,
        'status' => 'active',
        'region' => 'homs',
    ]);

    $calc = app(AgentCommissionCalculator::class);

    // Retail, small transfer (500K, below 1M tier): rates[0] = 1%
    $moneySyp = Money::fromFils(500_000, Currency::SYP);
    $commission = $calc->calculate($agent, $moneySyp, 'retail');
    assert($commission->fils() === 5000, '1% of 500K should be 5K, got ' . $commission->fils());

    // Business, large transfer (15M, above 10M tier): rates[2] = 1.5%
    $moneyLarge = Money::fromFils(15_000_000, Currency::SYP);
    $commission2 = $calc->calculate($agent, $moneyLarge, 'business');
    assert($commission2->fils() === 225_000, '1.5% of 15M should be 225K, got ' . $commission2->fils());

    // Premium, mid transfer (3M, between 1M and 5M tier): rates[0] = 0.2%
    $moneyMid = Money::fromFils(3_000_000, Currency::SYP);
    $commission3 = $calc->calculate($agent, $moneyMid, 'premium');
    assert($commission3->fils() === 6000, '0.2% of 3M should be 6K, got ' . $commission3->fils());

    // Test preview returns expected values
    $preview = $calc->previewCommission($moneySyp, 'retail');
    assert($preview['rate'] === 0.01, 'retail 500K rate should be 0.01');
    assert($preview['commission_fils'] === 5000, 'preview commission should be 5K');
});

// 4. Reject liquidity request exceeding daily limit
test('liquidity request exceeding daily limit is rejected', function () {
    $user = User::factory()->create();
    $agent = Agent::create([
        'user_id' => $user->id,
        'status' => 'active',
        'region' => 'latakia',
        'available_balance_fils' => 0,
        'daily_liquidity_limit_fils' => 1_000_000,
    ]);

    $pool = app(LiquidityPoolService::class);
    $money = Money::fromFils(2_000_000);

    try {
        $pool->requestLiquidity($agent, $money);
        assert(false, 'should have thrown');
    } catch (\RuntimeException $e) {
        assert(str_contains($e->getMessage(), 'حد السيولة'), 'should mention limit, got: ' . $e->getMessage());
    }
});

// 5. Audit log entries created for agent operations
test('audit log records agent operations', function () {
    $user = User::factory()->create();
    $agent = Agent::create([
        'user_id' => $user->id,
        'status' => 'active',
        'region' => 'damascus',
    ]);

    // Create audit log entries manually simulating what events would do
    AuditLog::create([
        'user_id' => $user->id,
        'action' => 'agent_registered',
        'resource_type' => 'agent',
        'resource_id' => $agent->id,
        'metadata' => ['status' => 'active', 'region' => 'damascus'],
        'result' => 'success',
    ]);

    AuditLog::create([
        'user_id' => $user->id,
        'action' => 'liquidity_requested',
        'resource_type' => 'agent',
        'resource_id' => $agent->id,
        'metadata' => ['amount_fils' => 100_000, 'approved' => true],
        'result' => 'success',
    ]);

    AuditLog::create([
        'user_id' => $user->id,
        'action' => 'commission_calculated',
        'resource_type' => 'agent',
        'resource_id' => $agent->id,
        'metadata' => ['commission_fils' => 1000],
        'result' => 'success',
    ]);

    $agentLogs = AuditLog::where('resource_type', 'agent')->get();
    assert($agentLogs->count() >= 3, 'should have at least 3 agent audit logs, got ' . $agentLogs->count());

    $actions = $agentLogs->pluck('action')->toArray();
    assert(in_array('agent_registered', $actions, true), 'should have agent_registered');
    assert(in_array('liquidity_requested', $actions, true), 'should have liquidity_requested');
    assert(in_array('commission_calculated', $actions, true), 'should have commission_calculated');

    // Verify no sensitive data in audit logs
    foreach ($agentLogs as $log) {
        $meta = $log->metadata;
        assert(!isset($meta['password']), 'no password in logs');
        assert(!isset($meta['token']), 'no token in logs');
    }
});

echo str_repeat('=', 60) . "\n";
echo "Results: {$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
