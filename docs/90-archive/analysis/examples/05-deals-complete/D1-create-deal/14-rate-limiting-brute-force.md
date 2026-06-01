# 14 - منع الهجمات — إنشاء صفقة

## Rate Limiting

```php
<?php
// في Route

Route::post('/admin/deals', [DealController::class, 'create'])
    ->middleware('throttle:10,1');
    // 10 محاولات كحد أقصى في الدقيقة
```

## Throttle Middleware Config

```php
<?php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

RateLimiter::for('create-deal', function (Request $request) {
    return Limit::perMinute(10)
        ->by($request->user()?->id)
        ->response(fn() => response()->json([
            'success' => false,
            'message' => 'طلبات إنشاء صفقات كثيرة. حاول بعد دقيقة',
        ], 429));
});
```

## Brute Force Protection

```php
<?php
const MAX_DEAL_CREATE_ATTEMPTS = 5;

function checkDealCreateBruteForce(string $adminId): void
{
    $key = 'deal_create_attempts_' . $adminId;
    $attempts = Cache::get($key, 0);
    if ($attempts >= MAX_DEAL_CREATE_ATTEMPTS) {
        throw new \App\Exceptions\DealCreateLockedException();
    }
}
```

## Redis-Based Attempt Tracking

```php
<?php
Redis::incr("brute:deal:create:{$adminId}");
Redis::expire("brute:deal:create:{$adminId}", 1800);
Redis::get("brute:deal:create:{$adminId}") >= 5;
```

## الملخص (Summary)

| الإجراء | الحد |
|---------|------|
| API Rate Limit | 10 req/min |
| Attempt Lockout | 5 fails → 30 min block |
| Redis Key TTL | 1800s |
