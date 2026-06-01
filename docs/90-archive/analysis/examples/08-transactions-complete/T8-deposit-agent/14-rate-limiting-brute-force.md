# 14 - منع الهجمات — إيداع وكيل

## Rate Limiting

```php
<?php
// في Route

Route::post('/deposit/agent', [AgentDepositController::class, 'deposit'])
    ->middleware('throttle:10,1');
    // 10 محاولات كحد أقصى في الدقيقة
```

## Throttle Middleware Config

```php
<?php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

RateLimiter::for('deposit-agent', function (Request $request) {
    return Limit::perMinute(10)
        ->by($request->user()?->id ?: $request->ip())
        ->response(fn() => response()->json([
            'success' => false,
            'message' => 'طلبات إيداع وكيل كثيرة. حاول بعد دقيقة',
        ], 429));
});
```

## Brute Force Protection

```php
<?php
const MAX_AGENT_DEPOSIT_ATTEMPTS = 5;

function checkAgentDepositBruteForce(string $userId): void
{
    $key = 'agent_deposit_attempts_' . $userId;
    $attempts = Cache::get($key, 0);
    if ($attempts >= MAX_AGENT_DEPOSIT_ATTEMPTS) {
        throw new \App\Exceptions\AgentDepositLockedException();
    }
}
```

## Redis-Based Attempt Tracking

```php
<?php
Redis::incr("brute:deposit:agent:{$userId}");
Redis::expire("brute:deposit:agent:{$userId}", 900);
Redis::get("brute:deposit:agent:{$userId}") >= 5;
```

## الملخص (Summary)

| الإجراء | الحد |
|---------|------|
| API Rate Limit | 10 req/min |
| Attempt Lockout | 5 fails → 15 min block |
| Redis Key TTL | 900s |
