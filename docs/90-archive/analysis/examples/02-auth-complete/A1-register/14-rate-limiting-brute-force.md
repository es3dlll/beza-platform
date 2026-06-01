# 14 - منع الهجمات وهندسة المعدل (Rate Limiting & Brute Force)

## Rate Limiting

```php
<?php
// في Route

Route::post('/auth/register', [AuthController::class, 'register'])
    ->middleware('throttle:10,1');
    // 10 محاولات كحد أقصى في الدقيقة
```

## منع إنشاء حسابات متعددة

```php
<?php
// في AppServiceProvider

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

public function boot(): void
{
    RateLimiter::for('register', function (Request $request) {
        return Limit::perMinute(10)
            ->by($request->ip())
            ->response(function () {
                return response()->json([
                    'success' => false,
                    'message' => 'طلبات كثيرة جداً. حاول بعد دقيقة',
                ], 429);
            });
    });

    // حد IP — منع إنشاء حسابات من نفس IP
    RateLimiter::for('register-ip', function (Request $request) {
        return Limit::perHour(5)
            ->by($request->ip())
            ->response(fn() => response()->json([
                'success' => false,
                'message' => 'تم تجاوز حد إنشاء الحسابات من هذا الجهاز',
            ], 429));
    });
}
```

## Validation لمنع Injection

```php
// regex للهاتف — يمنع SQL Injection
'regex:/^09[0-9]{8}$/'

// name — يمنع XSS (Laravel يفلتر تلقائياً)
'max:255'

// password — min:8 لمنع القيم القصيرة
'min:8'
```

## SQL Injection

Laravel Eloquent يستخدم Parameter Binding — آمن تلقائياً ضد SQL Injection.

## ملخص الحماية

| الهجوم | الحماية |
|--------|---------|
| Brute force (إنشاء حسابات) | throttle:10,1 |
| Spam من IP واحد | RateLimiter لكل ساعة |
| SQL Injection | Eloquent Parameter Binding |
| XSS | Blade escapes / HTML Purifier |
| أرقام هواتف وهمية | regex للتحقق من الصيغة |
