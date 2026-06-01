# 14 - منع الهجمات — سحب وكيل

## Rate Limiting

```php
<?php
// في Route

Route::post('/withdraw/agent', [AgentWithdrawController::class, 'withdraw'])
    ->middleware('throttle:10,1');
    // 10 محاولات كحد أقصى في الدقيقة
```

## Throttle Middleware Config

```php
<?php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

RateLimiter::for('withdraw-agent', function (Request $request) {
    return Limit::perMinute(10)
        ->by($request->user()?->id ?: $request->ip())
        ->response(fn() => response()->json([
            'success' => false,
            'message' => 'طلبات سحب وكيل كثيرة. حاول بعد دقيقة',
        ], 429));
});
```

## Brute Force Protection

```php
<?php
const MAX_AGENT_WITHDRAW_ATTEMPTS = 5;

function checkAgentWithdrawBruteForce(string $userId): void
{
    $key = 'agent_withdraw_attempts_' . $userId;
    $attempts = Cache::get($key, 0);
    if ($attempts >= MAX_AGENT_WITHDRAW_ATTEMPTS) {
        throw new \App\Exceptions\AgentWithdrawLockedException();
    }
}
```

## Redis-Based Attempt Tracking

```php
<?php
Redis::incr("brute:withdraw:agent:{$userId}");
Redis::expire("brute:withdraw:agent:{$userId}", 900);
Redis::get("brute:withdraw:agent:{$userId}") >= 5;
```

## الملخص (Summary)

| الإجراء | الحد |
|---------|------|
| API Rate Limit | 10 req/min |
| Attempt Lockout | 5 fails → 15 min block |
| Redis Key TTL | 900s |
