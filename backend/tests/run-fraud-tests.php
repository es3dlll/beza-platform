<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\User;
use App\Modules\Agent\Models\Agent;
use App\Modules\AuditLog\Models\AuditLog;
use App\Modules\Core\Enums\Currency;
use App\Modules\Core\ValueObjects\Money;
use App\Modules\Fraud\Jobs\FraudDetectionEngine;
use App\Modules\Fraud\Models\ComplianceRule;
use App\Modules\Fraud\Models\RiskScore;
use App\Modules\Fraud\Services\ComplianceRuleManager;

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

echo "Fraud Detection Module Integration Tests\n";
echo str_repeat('=', 60) . "\n";

// 1. Normal transaction passes auto-approval with low risk score
test('normal transaction passes auto-approval', function () {
    $user = User::factory()->create();
    $agent = Agent::create([
        'user_id' => $user->id,
        'status' => 'active',
        'region' => 'damascus',
        'available_balance_fils' => 0,
        'daily_liquidity_limit_fils' => 10_000_000,
    ]);

    // Small amount in safe region should result in approval
    $engine = new FraudDetectionEngine(
        agent: $agent,
        amountFils: 500_000,
        currency: 'SYP',
        requestId: 'req-norm-' . bin2hex(random_bytes(4)),
        region: 'damascus',
    );
    $engine->handle(app(ComplianceRuleManager::class));

    $score = RiskScore::where('request_type', 'liquidity')
        ->where('user_id', $user->id)
        ->latest()
        ->first();
    assert($score !== null, 'risk score should exist');
    assert($score->status === 'approved', 'small safe transaction should be approved, got ' . $score->status);
    assert($score->score < 30, 'score should be low for small safe transaction, got ' . $score->score);
});

// 2. High-risk transaction gets suspended for manual review
test('high-risk transaction triggers manual review suspension', function () {
    $user = User::factory()->create();
    $agent = Agent::create([
        'user_id' => $user->id,
        'status' => 'active',
        'region' => 'border_area',
        'available_balance_fils' => 0,
        'daily_liquidity_limit_fils' => 50_000_000,
    ]);

    // High amount + border area = high risk
    $engine = new FraudDetectionEngine(
        agent: $agent,
        amountFils: 25_000_000,
        currency: 'SYP',
        requestId: 'req-high-' . bin2hex(random_bytes(4)),
        region: 'border_area',
    );
    $engine->handle(app(ComplianceRuleManager::class));

    $score = RiskScore::where('user_id', $user->id)->latest()->first();
    assert($score !== null, 'risk score should exist');
    assert($score->status === 'suspended', 'high risk should be suspended, got ' . $score->status);
    assert($score->score >= 30, 'score should be >= 30, got ' . $score->score);
    assert(count($score->reasons ?? []) > 0, 'should have at least one reason');
    assert(str_contains(implode(' ', $score->reasons ?? []), 'border_area') || str_contains(implode(' ', $score->reasons ?? []), '15,000,000') || str_contains(implode(' ', $score->reasons ?? []), '25,000,000'), 'reasons should mention region or amount');

    // Verify AuditLog entries exist for this flow
    // LogLiquidityRequested is triggered by the event, but we ran the engine directly
    // So just check the RiskScore metadata
    $meta = $score->metadata;
    assert(is_array($meta), 'metadata should be array');
    assert(isset($meta['applied_rules']), 'should have applied rules');
    assert(isset($meta['agent_id']), 'should have agent_id');
});

// 3. Dynamic rule can be toggled without system restart
test('compliance rule can be toggled dynamically', function () {
    // Create a custom rule
    $rule = ComplianceRule::create([
        'name' => 'معاملات اختبارية ليلية',
        'key' => 'test_night_' . bin2hex(random_bytes(3)),
        'description' => 'قاعدة اختبارية',
        'rule_type' => 'amount',
        'parameters' => ['min_amount_fils' => 1_000_000],
        'is_active' => true,
        'priority' => 50,
        'risk_score_impact' => 40,
        'decision' => 'suspend',
    ]);
    assert($rule->is_active === true, 'rule should start active');

    $manager = app(ComplianceRuleManager::class);

    // Disable it
    $updated = $manager->toggleRule($rule->key, false);
    assert($updated->is_active === false, 'rule should be inactive after toggle');

    // Re-enable it
    $reEnabled = $manager->toggleRule($rule->key, true);
    assert($reEnabled->is_active === true, 'rule should be active after re-enable');

    // Verify active rules query
    $active = ComplianceRule::where('is_active', true)->get();
    assert($active->contains('key', $rule->key), 're-enabled rule should appear in active list');
});

// 4. Rapid successive transactions are flagged by frequency rule
test('rapid successive transactions trigger frequency rule', function () {
    $user = User::factory()->create();
    $agent = Agent::create([
        'user_id' => $user->id,
        'status' => 'active',
        'region' => 'homs',
        'available_balance_fils' => 0,
        'daily_liquidity_limit_fils' => 10_000_000,
    ]);
    $baseRequestId = 'req-freq-' . bin2hex(random_bytes(4));

    $manager = app(ComplianceRuleManager::class);
    $frequencyRule = ComplianceRule::where('key', 'rapid_successive_transfers')->first();
    assert($frequencyRule !== null, 'frequency rule should be seeded');

    // Simulate 3 recent transactions
    for ($i = 0; $i < 3; $i++) {
        RiskScore::create([
            'score' => 5,
            'status' => 'approved',
            'request_type' => 'liquidity',
            'request_id' => $baseRequestId . "-prev-$i",
            'user_id' => $user->id,
            'amount_fils' => 100_000,
            'currency' => 'SYP',
        ]);
    }

    // Now evaluate - the 4th transaction should trigger the rule
    $amount = Money::fromFils(100_000);
    $result = $manager->evaluate($frequencyRule, $agent, $amount, [
        'request_id' => $baseRequestId . '-current',
        'region' => 'homs',
    ]);

    assert($result['triggered'] === true, 'frequency rule should trigger after 3 rapid transactions');
    assert(str_contains($result['reason'], '3'), 'reason should mention count: ' . $result['reason']);
});

// 5. Risk score metadata is complete and contains no sensitive data
test('risk score metadata is complete and safe', function () {
    $user = User::factory()->create();
    $agent = Agent::create([
        'user_id' => $user->id,
        'status' => 'active',
        'region' => 'aleppo',
        'available_balance_fils' => 0,
        'daily_liquidity_limit_fils' => 10_000_000,
    ]);
    $requestId = 'req-safe-' . bin2hex(random_bytes(4));

    // Run full detection
    $engine = new FraudDetectionEngine(
        agent: $agent,
        amountFils: 12_000_000,
        currency: 'SYP',
        requestId: $requestId,
        region: 'aleppo',
    );
    $engine->handle(app(ComplianceRuleManager::class));

    $score = RiskScore::where('request_id', $requestId)->first();
    assert($score !== null, 'risk score should exist');
    assert($score->score > 0, 'score should be > 0 for 12M transaction');
    assert(in_array($score->status, ['approved', 'suspended', 'rejected'], true), 'status must be valid');

    $meta = $score->metadata;
    assert(is_array($meta), 'metadata should be array');
    assert(isset($meta['applied_rules']), 'should have applied_rules');
    assert(is_array($meta['applied_rules']), 'applied_rules should be array');

    // No sensitive data
    assert(!isset($meta['password']), 'no password');
    assert(!isset($meta['token']), 'no token');
    assert(!isset($meta['secret']), 'no secret');

    // Score should be an integer, not float
    assert(is_int($score->score), 'score must be integer, got ' . gettype($score->score));
    assert($score->score >= 0 && $score->score <= 100, 'score must be 0-100');
});

echo str_repeat('=', 60) . "\n";
echo "Results: {$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
