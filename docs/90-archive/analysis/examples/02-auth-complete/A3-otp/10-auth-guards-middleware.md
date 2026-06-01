# 10 - المصادقة والصلاحيات (Auth & Middleware) — رمز التحقق (OTP)

## Guest Middleware

```php
<?php
// routes/api.php

use App\Http\Controllers\Api\AuthController;

Route::post('/auth/request-otp', [AuthController::class, 'requestOtp'])
    ->middleware('throttle:3,60');

Route::post('/auth/verify-otp', [AuthController::class, 'verifyOtp'])
    ->middleware('throttle:10,1');
```

## Rate Limiting

```php
<?php
// app/Providers/AppServiceProvider.php

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

public function boot(): void
{
    // حد OTP — 3 طلبات في 60 ثانية
    RateLimiter::for('otp', function (Request $request) {
        return Limit::perMinutes(1, 3)
            ->by($request->input('phone', $request->ip()));
    });
}
```

## لماذا guest فقط؟

| السبب | التفصيل |
|-------|---------|
| OTP يطلب قبل المصادقة | لتأكيد رقم الهاتف عند التسجيل |
| لا حاجة لمستخدم مصادق | العملية متاحة للجميع |
| الحماية من السبام | throttle يمنع الطلبات المتكررة |
