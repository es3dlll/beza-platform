<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\User;
use App\Modules\AuditLog\Models\AuditLog;
use App\Modules\Core\ValueObjects\Money;
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

echo "Network & Error Handling Integration Tests\n";
echo str_repeat('=', 60) . "\n";

// 1. Reject invalid amount
test('rejects transfer with invalid amount', function () {
    $sender = User::factory()->create();
    Wallet::factory()->create(['user_id' => $sender->id, 'balance_fils' => 10000]);
    $receiver = User::factory()->create();
    Wallet::factory()->create(['user_id' => $receiver->id, 'balance_fils' => 0]);

    $engine = app(CoreFinancialEngine::class);

    $caught = false;
    try {
        $engine->transfer(Money::fromFils(-100), $sender->wallet, $receiver->wallet, 'مبلغ سالب');
    } catch (\InvalidArgumentException) {
        $caught = true;
    } catch (\Throwable) {
        // Money::fromFils may throw generic exception
        $caught = true;
    }

    assert($caught, 'should reject negative amount');
});

// 2. Rate limit exceeded simulation (verify throttle config)
test('rate limiter rejects excessive auth requests', function () {
    $limiter = \Illuminate\Support\Facades\RateLimiter::limiter('auth');
    assert($limiter !== null, 'auth rate limiter should exist');

    $request = \Illuminate\Http\Request::create('/v1/auth/login', 'POST', ['email' => 'test@test.com', 'password' => 'password']);
    $response = $limiter($request);
    $maxAttempts = $response->maxAttempts;
    assert($maxAttempts === 5, 'auth should allow 5 per minute');
});

// 3. Audit log filtering works correctly
test('audit log can be filtered by action and result', function () {
    AuditLog::create([
        'action' => 'transfer',
        'resource_type' => 'wallet',
        'result' => 'success',
        'metadata' => ['amount' => 1000],
    ]);

    AuditLog::create([
        'action' => 'transfer',
        'resource_type' => 'wallet',
        'result' => 'failed',
        'metadata' => ['reason' => 'insufficient_balance'],
    ]);

    AuditLog::create([
        'action' => 'login',
        'resource_type' => 'user',
        'result' => 'success',
    ]);

    $successTransfers = AuditLog::where('action', 'transfer')->where('result', 'success')->get();
    assert($successTransfers->count() === 1, 'should find 1 success transfer');

    $transfers = AuditLog::where('action', 'transfer')->get();
    assert($transfers->count() === 2, 'should find 2 transfers');

    $logins = AuditLog::where('action', 'login')->get();
    assert($logins->count() === 1, 'should find 1 login');
});

// 4. Expired/invalid token is rejected
test('invalid token returns 401-like response', function () {
    $user = User::factory()->create();
    $token = $user->createApiToken();

    // Verify correct token works
    $hashed = hash('sha256', $token);
    $found = User::where('remember_token', $hashed)->first();
    assert($found !== null, 'valid token should find user');

    // Verify wrong token does not
    $wrong = User::where('remember_token', hash('sha256', 'wrong-token'))->first();
    assert($wrong === null, 'invalid token should not find user');
});

// 5. Sensitive data masking in audit log metadata
test('sensitive data is masked in audit log responses', function () {
    $log = AuditLog::create([
        'action' => 'test',
        'resource_type' => 'user',
        'result' => 'success',
        'metadata' => [
            'email' => 'user@test.com',
            'password' => 'secret123',
            'token' => 'abc.def.ghi',
            'nested' => [
                'authorization' => 'Bearer xyz',
            ],
        ],
    ]);

    $controller = new \App\Modules\AuditLog\Controllers\AuditLogController();
    $request = \Illuminate\Http\Request::create('/v1/admin/audit-logs', 'GET');
    $response = $controller->index($request);

    $content = json_decode($response->getContent(), true);
    $logData = $content['data'][0]['metadata'];

    assert($logData['password'] === '***', 'password should be masked');
    assert($logData['token'] === '***', 'token should be masked');
    assert($logData['nested']['authorization'] === '***', 'nested auth should be masked');
    assert($logData['email'] === 'user@test.com', 'email should not be masked');
});

echo str_repeat('=', 60) . "\n";
echo "Results: {$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
