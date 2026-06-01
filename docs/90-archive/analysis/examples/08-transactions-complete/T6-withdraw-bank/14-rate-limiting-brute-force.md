# 14 - منع الهجمات — سحب بنكي

## Rate Limiting

```php
<?php
// في Route

Route::post('/withdraw/bank', [WithdrawBankController::class, 'withdraw'])
    ->middleware('throttle:5,1');
    // 5 محاولات كحد أقصى في الدقيقة
```

## Throttle Middleware Config

```php
<?php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

RateLimiter::for('withdraw-bank', function (Request $request) {
    return Limit::perMinute(5)
        ->by($request->user()?->id)
        ->response(fn() => response()->json([
            'success' => false,
            'message' => 'طلبات سحب كثيرة. حاول بعد دقيقة',
        ], 429));
});
```

## Brute Force Protection

```php
<?php
const MAX_WITHDRAW_ATTEMPTS = 3;

function checkWithdrawBruteForce(string $userId): void
{
    $key = 'withdraw_bank_attempts_' . $userId;
    $attempts = Cache::get($key, 0);
    if ($attempts >= MAX_WITHDRAW_ATTEMPTS) {
        throw new \App\Exceptions\WithdrawLockedException();
    }
}
```

## Redis-Based Attempt Tracking

```php
<?php
Redis::incr("brute:withdraw:bank:{$userId}");
Redis::expire("brute:withdraw:bank:{$userId}", 3600);
Redis::get("brute:withdraw:bank:{$userId}") >= 3; // محظور
```

## الملخص (Summary)

| الإجراء | الحد |
|---------|------|
| API Rate Limit | 5 req/min |
| Withdraw Failure Lockout | 3 fails → 60 min block |
| Redis Key TTL | 3600s |
