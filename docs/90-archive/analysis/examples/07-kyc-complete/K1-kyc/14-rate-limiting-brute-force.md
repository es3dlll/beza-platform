# 14 - منع الهجمات — التحقق من الهوية KYC

## Rate Limiting

```php
<?php
// في Route

Route::post('/kyc/submit', [KycController::class, 'submit'])
    ->middleware('throttle:3,1');
    // 3 محاولات كحد أقصى في الدقيقة — حساسية عالية
```

## Throttle Middleware Config

```php
<?php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

RateLimiter::for('kyc-submit', function (Request $request) {
    return Limit::perMinute(3)
        ->by($request->user()?->id ?: $request->ip())
        ->response(fn() => response()->json([
            'success' => false,
            'message' => 'طلبات KYC كثيرة. حاول بعد دقيقة',
        ], 429));
});

RateLimiter::for('kyc-daily', function (Request $request) {
    return Limit::perDay(5)
        ->by($request->user()?->id)
        ->response(fn() => response()->json([
            'success' => false,
            'message' => 'تم تجاوز الحد اليومي لتقديم طلبات KYC',
        ], 429));
});
```

## Brute Force Protection

```php
<?php
const MAX_KYC_ATTEMPTS = 3;

function checkKycBruteForce(string $userId): void
{
    $key = 'kyc_attempts_' . $userId;
    $attempts = Cache::get($key, 0);
    if ($attempts >= MAX_KYC_ATTEMPTS) {
        throw new \App\Exceptions\KycSubmissionLockedException();
    }
}
```

## Redis-Based Attempt Tracking

```php
<?php
Redis::incr("brute:kyc:user:{$userId}");
Redis::expire("brute:kyc:user:{$userId}", 86400); // 24 ساعة
Redis::get("brute:kyc:user:{$userId}") >= 3;
```

## الملخص (Summary)

| الإجراء | الحد |
|---------|------|
| API Rate Limit | 3 req/min |
| Daily Submission Limit | 5 req/day |
| Attempt Lockout | 3 fails → 24h block |
| Redis Key TTL | 86400s |
