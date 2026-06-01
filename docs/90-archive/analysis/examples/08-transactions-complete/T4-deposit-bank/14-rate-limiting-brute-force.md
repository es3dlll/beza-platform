# 14 - منع الهجمات — إيداع بنكي

## Rate Limiting

```php
<?php
// في Route

Route::post('/deposit/bank', [DepositBankController::class, 'deposit'])
    ->middleware('throttle:10,1');
    // 10 محاولات كحد أقصى في الدقيقة لمنع التكرار
```

## Throttle Middleware Config

```php
<?php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

public function boot(): void
{
    RateLimiter::for('deposit-bank', function (Request $request) {
        return Limit::perMinute(10)
            ->by($request->user()?->id ?: $request->ip())
            ->response(fn() => response()->json([
                'success' => false,
                'message' => 'طلبات إيداع كثيرة. حاول بعد دقيقة',
            ], 429));
    });
}
```

## Brute Force Protection

```php
<?php
const MAX_DEPOSIT_FAILURES = 3;

function checkDepositBruteForce(string $userId): void
{
    $key = 'deposit_bank_attempts_' . $userId;
    $attempts = Cache::get($key, 0);
    if ($attempts >= MAX_DEPOSIT_FAILURES) {
        throw new \App\Exceptions\DepositLockedException();
    }
}
```

## Redis-Based Attempt Tracking

```php
<?php
use Illuminate\Support\Facades\Redis;

function trackDepositAttempt(string $userId): void
{
    $key = "brute:deposit:bank:{$userId}";
    Redis::incr($key);
    Redis::expire($key, 1800); // 30 دقيقة
}

function isDepositBlocked(string $userId): bool
{
    return Redis::get("brute:deposit:bank:{$userId}") >= 3;
}
```

## الملخص (Summary)

| الإجراء | الحد |
|---------|------|
| API Rate Limit | 10 req/min |
| Deposit Failure Lockout | 3 fails → 30 min block |
| Redis Key TTL | 1800s |
