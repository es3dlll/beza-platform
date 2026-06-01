# 10 - المصادقة والصلاحيات — المصادقة الثنائية (2FA)

## Auth JWT Middleware

```php
<?php
// routes/api.php

use App\Http\Controllers\Api\TwoFactorController;

Route::middleware('auth:api')->group(function () {
    Route::post('/auth/2fa/enable', [TwoFactorController::class, 'enable']);
    Route::post('/auth/2fa/verify', [TwoFactorController::class, 'verify'])
        ->middleware('throttle:5,1');
    Route::post('/auth/2fa/disable', [TwoFactorController::class, 'disable']);
});
```

## RequireTwoFactor Middleware

للتحقق من أن 2FA مفعل قبل تنفيذ عمليات حساسة:

```php
<?php
// app/Http/Middleware/RequireTwoFactor.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireTwoFactor
{
    /**
     * التحقق من أن 2FA مفعل للمستخدم
     * يستخدم في العمليات الحساسة (تحويل > 1000 USD)
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if (!$user->hasTwoFactorEnabled()) {
            return response()->json([
                'success' => false,
                'message' => 'المصادقة الثنائية مطلوبة لهذه العملية',
                'requires_2fa' => true,
            ], 403);
        }

        return $next($request);
    }
}
```

## تسجيل الـ Middleware

```php
<?php
// app/Http/Kernel.php

protected $routeMiddleware = [
    // ...
    '2fa' => \App\Http\Middleware\RequireTwoFactor::class,
];
```

## استخدامه في المسارات

```php
Route::middleware(['auth:api', '2fa'])->group(function () {
    Route::post('/transfer', [TransferController::class, 'transfer']);
    // أي عملية حساسة أخرى
});
```

## التحقق من 2FA أثناء تسجيل الدخول

```php
// بعد تسجيل الدخول — إذا 2FA مفعل:
// 1. التوكن يحصل على claim '2fa: pending'
// 2. كل الطلبات ما عدا /2fa/verify ترفض
// 3. بعد /2fa/verify → claim يتحول لـ 'verified'

public function handle(Request $request, Closure $next): mixed
{
    $payload = Auth::payload()->toArray();
    $status = $payload['2fa'] ?? '';

    if ($status === 'pending') {
        // السماح فقط بمسارات 2FA
        if (!$request->routeIs('api.v1.auth.2fa.*')) {
            return response()->json([
                'success' => false,
                'message' => 'يرجى إكمال المصادقة الثنائية أولاً',
                'requires_2fa' => true,
            ], 403);
        }
    }

    return $next($request);
}
```
