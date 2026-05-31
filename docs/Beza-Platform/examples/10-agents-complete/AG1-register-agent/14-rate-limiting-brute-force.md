# 14 - منع الهجمات — تسجيل وكيل

## Rate Limiting

```php
<?php
// في Route

Route::post('/agent/register', [AgentRegisterController::class, 'register'])
    ->middleware('throttle:5,1');
    // 5 محاولات كحد أقصى في الدقيقة
```

## Throttle Middleware Config

```php
<?php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

RateLimiter::for('agent-register', function (Request $request) {
    return Limit::perMinute(5)
        ->by($request->user()?->id ?: $request->ip())
        ->response(fn() => response()->json([
            'success' => false,
            'message' => 'طلبات تسجيل وكيل كثيرة. حاول بعد دقيقة',
        ], 429));
});
```

## Brute Force Protection

```php
<?php
const MAX_AGENT_REGISTER_ATTEMPTS = 3;

function checkAgentRegisterBruteForce(string $userId): void
{
    $key = 'agent_register_attempts_' . $userId;
    $attempts = Cache::get($key, 0);
    if ($attempts >= MAX_AGENT_REGISTER_ATTEMPTS) {
        throw new \App\Exceptions\AgentRegisterLockedException();
    }
}
```

## Redis-Based Attempt Tracking

```php
<?php
Redis::incr("brute:agent:register:{$userId}");
Redis::expire("brute:agent:register:{$userId}", 3600);
Redis::get("brute:agent:register:{$userId}") >= 3;
```

## الملخص (Summary)

| الإجراء | الحد |
|---------|------|
| API Rate Limit | 5 req/min |
| Attempt Lockout | 3 fails → 60 min block |
| Redis Key TTL | 3600s |
