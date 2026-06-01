# 14 - منع الهجمات — تحويل QR

## Rate Limiting

```php
<?php
// في Route

Route::post('/transfer/qr', [TransferQrController::class, 'transfer'])
    ->middleware('throttle:30,1');
    // 30 محاولة كحد أقصى في الدقيقة
```

## Throttle Middleware Config

```php
<?php
// في AppServiceProvider

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

public function boot(): void
{
    RateLimiter::for('transfer-qr', function (Request $request) {
        return Limit::perMinute(30)
            ->by($request->user()?->id ?: $request->ip())
            ->response(function () {
                return response()->json([
                    'success' => false,
                    'message' => 'طلبات كثيرة جداً. حاول بعد دقيقة',
                ], 429);
            });
    });

    RateLimiter::for('qr-scan-ip', function (Request $request) {
        return Limit::perMinute(10)
            ->by($request->ip())
            ->response(fn() => response()->json([
                'success' => false,
                'message' => 'تم تجاوز حد مسح QR من هذا الجهاز',
            ], 429));
    });
}
```

## Brute Force Protection

```php
<?php
use Illuminate\Support\Facades\Cache;

const MAX_QR_ATTEMPTS = 5;
const QR_LOCKOUT_MINUTES = 15;

function checkQrBruteForce(string $identifier): void
{
    $key = 'qr_attempts_' . $identifier;
    $attempts = Cache::get($key, 0);

    if ($attempts >= MAX_QR_ATTEMPTS) {
        throw new \App\Exceptions\QrTransferLockedException();
    }
}

function incrementQrAttempt(string $identifier): void
{
    $key = 'qr_attempts_' . $identifier;
    $attempts = Cache::get($key, 0);
    Cache::put($key, $attempts + 1, now()->addMinutes(QR_LOCKOUT_MINUTES));
}
```

## Redis-Based Attempt Tracking

```php
<?php
use Illuminate\Support\Facades\Redis;

function trackQrAttempt(string $userId): void
{
    $key = "brute:qr:user:{$userId}";
    Redis::incr($key);
    Redis::expire($key, 900); // 15 دقيقة
}

function isQrBlocked(string $userId): bool
{
    $key = "brute:qr:user:{$userId}";
    return Redis::get($key) >= 5;
}
```

## Validation Security

| الحقل | الحماية |
|-------|---------|
| amount | minimum:1, max حد المحفظة |
| currency | enum: SYP, USD |
| pin | digits:4, موجود في Cache |

## الملخص (Summary)

| الإجراء | الحد |
|---------|------|
| API Rate Limit | 30 req/min |
| QR Scan per IP | 10 req/min |
| QR Attempt Lockout | 5 fails → 15 min block |
| Redis Key TTL | 900s |
