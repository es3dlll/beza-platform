# 10 - المصادقة والصلاحيات — لوحة المشرف

## Auth Middleware

```php
<?php
// routes/api.php

use App\Http\Controllers\Api\Admin\DashboardController;

Route::middleware(['auth:api'])->group(function () {
    Route::get('/admin/dashboard', [DashboardController::class, 'index']);
});
```

## Admin Middleware

```php
<?php
// app/Http/Middleware/AdminMiddleware.php

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
// في Route
Route::get('/admin/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth:api', 'admin']);
```

## Throttle Middleware

```php
Route::get('/admin/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth:api', 'admin', 'throttle:30,1']);
    // 30 طلب كحد أقصى في الدقيقة
```

## Permission Checks

```php
<?php
public function index(Request $request): JsonResponse
{
    $user = $request->user();

    // صلاحية admin مطلوبة عبر JWT claim
    $payload = Auth::payload()->toArray();
    if (($payload['role'] ?? '') !== 'admin') {
        return response()->json([
            'success' => false,
            'message' => 'ليس لديك صلاحية الوصول للوحة المشرف',
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
| `role: admin` | صلاحية المدير (مطلوبة للوحة المشرف) |

## Middleware Stack Summary

| Middleware | الغرض |
|------------|-------|
| `auth:api` | المصادقة والتأكد من صحة JWT |
| `admin` | التحقق من صلاحية المشرف |
| `throttle:30,1` | منع الإرسال المتكرر (30 req/min) |
| `payload['role']` | التحقق من صلاحية التوكن عبر JWT claims |
