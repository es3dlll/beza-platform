# 14 - منع الهجمات — إيداع ببطاقة

## Rate Limiting

```php
<?php
// في Route

Route::post('/deposit/card', [DepositCardController::class, 'deposit'])
    ->middleware('throttle:5,1');
    // 5 محاولات كحد أقصى في الدقيقة — منخفضة بسبب الحساسية المالية
```

## Throttle Middleware Config

```php
<?php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

RateLimiter::for('deposit-card', function (Request $request) {
    return Limit::perMinute(5)
        ->by($request->user()?->id ?: $request->ip())
        ->response(fn() => response()->json([
            'success' => false,
            'message' => 'طلبات إيداع كثيرة. حاول بعد دقيقة',
        ], 429));
});
```

## Brute Force Protection

```php
<?php
const MAX_CARD_ATTEMPTS = 3;

function checkCardBruteForce(string $userId): void
{
    $key = 'card_deposit_attempts_' . $userId;
    $attempts = Cache::get($key, 0);
    if ($attempts >= MAX_CARD_ATTEMPTS) {
        throw new \App\Exceptions\CardDepositLockedException();
    }
}
```

## Redis-Based Attempt Tracking

```php
<?php
Redis::incr("brute:deposit:card:{$userId}");
Redis::expire("brute:deposit:card:{$userId}", 3600); // ساعة
Redis::get("brute:deposit:card:{$userId}") >= 3; // محظور
```

## الملخص (Summary)

| الإجراء | الحد |
|---------|------|
| API Rate Limit | 5 req/min |
| Card Attempt Lockout | 3 fails → 60 min block |
| Redis Key TTL | 3600s |
