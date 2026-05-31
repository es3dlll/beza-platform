# 14 - منع الهجمات وهندسة المعدل (Rate Limiting & Brute Force)

## Rate Limiting

```php
<?php
// في Route

// طلب OTP — 3 محاولات كحد أقصى كل 60 ثانية
Route::post('/auth/request-otp', [AuthController::class, 'requestOtp'])
    ->middleware('throttle:3,60');

// التحقق من OTP — 10 محاولات كحد أقصى في الدقيقة
Route::post('/auth/verify-otp', [AuthController::class, 'verifyOtp'])
    ->middleware('throttle:10,1');
```

## التحديد لكل رقم هاتف

```php
<?php
// في AppServiceProvider

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

public function boot(): void
{
    // حد OTP لكل رقم هاتف — 3 في 10 دقائق
    RateLimiter::for('otp-per-phone', function (Request $request) {
        return Limit::perMinutes(10, 3)
            ->by('otp:' . $request->input('phone'))
            ->response(fn() => response()->json([
                'success' => false,
                'message' => 'لقد تجاوزت حد طلب رموز التحقق. حاول بعد 10 دقائق',
            ], 429));
    });
}
```

## منع Brute Force (تخمين OTP)

```php
<?php
// في OtpService

private const MAX_ATTEMPTS = 5;

public function verify(string $phone, string $code): void
{
    $cached = Cache::get('otp_' . $phone);

    if (!$cached) {
        throw new OtpExpiredException();
    }

    $otp = OtpCode::fromArray($phone, $cached);

    // التحقق من عدد المحاولات
    if ($otp->attempts >= self::MAX_ATTEMPTS) {
        Cache::forget('otp_' . $phone);
        throw new OtpAttemptsExceededException();
    }

    if ($otp->code !== $code) {
        $otp->incrementAttempts();
        Cache::put('otp_' . $phone, $otp->toArray(), now()->addMinutes(5));
        throw new InvalidOtpException();
    }
}
```

## منع OTP Bombing

| الإجراء | الوصف |
|---------|-------|
| 3 طلبات OTP لكل 60 ثانية | يمنع إغراق المستخدم برسائل SMS |
| حد لكل رقم هاتف | يمنع إرسال OTP لرقم معين بشكل متكرر |
| 5 محاولات تحقق كحد أقصى | يمنع تخمين OTP |
| طلب جديد يلغي القديم | يمنع استخدام OTPs قديمة متعددة |
| إرجاع OTP في التطوير فقط | يمنع تسريب الرمز في الإنتاج |

## هيكل الـ Rate Limiting

```
┌──────────────┐     ┌──────────────┐
│  request-otp  │     │  verify-otp  │
│  3/60 ثانية   │     │  10/دقيقة    │
│  لكل IP       │     │  لكل IP      │
└──────┬───────┘     └──────┬───────┘
       │                    │
       ▼                    ▼
  ┌──────────────┐     ┌──────────────┐
  │ Redis Cache  │     │  Cache + DB  │
  │ otp_{phone}  │     │  attempts    │
  │ TTL: 300s    │     │  max: 5      │
  └──────────────┘     └──────────────┘
```
