# 10 - المصادقة والصلاحيات — إنشاء صفقة

## Auth Middleware

```php
<?php
// routes/api.php

use App\Http\Controllers\Api\Admin\DealController;

Route::middleware(['auth:api'])->group(function () {
    Route::post('/admin/deals', [DealController::class, 'create']);
});
```

## Admin Middleware

```php
<?php
// في app/Http/Middleware/AdminMiddleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user() || !$request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح. هذه العملية تتطلب صلاحيات مشرف',
            ], 403);
        }

        return $next($request);
    }
}
```

```php
// تسجيل middleware في Kernel.php
'admin' => \App\Http\Middleware\AdminMiddleware::class,

// في Route
Route::post('/admin/deals', [DealController::class, 'create'])
    ->middleware(['auth:api', 'admin', 'throttle:10,1']);
```

## Throttle Middleware

```php
Route::post('/admin/deals', [DealController::class, 'create'])
    ->middleware(['auth:api', 'admin', 'throttle:10,1']);
```

## Permission Checks

```php
<?php
public function create(Request $request): JsonResponse
{
    $user = $request->user();

    // صلاحية admin مطلوبة عبر JWT claim
    $payload = Auth::payload()->toArray();
    if (($payload['role'] ?? '') !== 'admin') {
        return response()->json([
            'success' => false,
            'message' => 'ليس لديك صلاحية إنشاء صفقات',
        ], 403);
    }

    // المشرف يجب أن يكون نشطاً
    if ($user->status !== 'active') {
        return response()->json([
            'success' => false,
            'message' => 'حساب المشرف غير نشط',
        ], 403);
    }
}
```

## JWT Token Claims

| Claim | الوصف |
|-------|-------|
| `role: admin` | صلاحية المدير (مطلوبة لإنشاء الصفقات) |

## Middleware Stack Summary

| Middleware | الغرض |
|------------|-------|
| `auth:api` | المصادقة والتأكد من صحة JWT |
| `admin` | التحقق من صلاحية المشرف |
| `throttle:10,1` | منع الإرسال المتكرر (10 req/min) |
| `payload['role']` | التحقق من صلاحية التوكن عبر JWT claims |
