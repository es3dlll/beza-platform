# 14 - منع الهجمات وهندسة المعدل (Rate Limiting & Brute Force)

## Rate Limiting

```php
<?php
// في Route

Route::post('/merchant/payment-link', [PaymentGatewayController::class, 'createLink'])
    ->middleware('throttle:20,1');
    // 20 رابط دفع كحد أقصى في الدقيقة
```

## Throttle Middleware Config

```php
<?php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

RateLimiter::for('payment-link', function (Request $request) {
    return Limit::perMinute(20)
        ->by($request->user()?->id ?: $request->ip())
        ->response(fn() => response()->json([
            'success' => false,
            'message' => 'طلبات إنشاء روابط دفع كثيرة. حاول بعد دقيقة',
        ], 429));
});

RateLimiter::for('payment-ip', function (Request $request) {
    return Limit::perMinute(100)
        ->by($request->ip())
        ->response(fn() => response()->json([
            'success' => false,
            'message' => 'طلبات كثيرة من هذا العنوان',
        ], 429));
});
```

## Brute Force Protection

```php
<?php
const MAX_PAYMENT_ATTEMPTS = 10;

function checkPaymentBruteForce(string $merchantId): void
{
    $key = 'payment_attempts_' . $merchantId;
    $attempts = Cache::get($key, 0);
    if ($attempts >= MAX_PAYMENT_ATTEMPTS) {
        throw new \App\Exceptions\PaymentGatewayLockedException();
    }
}
```

## Redis-Based Attempt Tracking

```php
<?php
Redis::incr("brute:payment:merchant:{$merchantId}");
Redis::expire("brute:payment:merchant:{$merchantId}", 300);
Redis::get("brute:payment:merchant:{$merchantId}") >= 10;
```

## الملخص (Summary)

| الإجراء | الحد |
|---------|------|
| API Rate Limit (إنشاء رابط) | 20 req/min |
| IP Rate Limit | 100 req/min |
| Payment Attempt Lockout | 10 fails → 5 min block |
| Redis Key TTL | 300s |
