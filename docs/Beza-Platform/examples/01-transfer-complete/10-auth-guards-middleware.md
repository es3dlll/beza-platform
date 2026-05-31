# 10 - المصادقة والصلاحيات — تحويل بين المستخدمين

## Auth Middleware

```php
<?php
// routes/api.php

use App\Http\Controllers\Api\TransferController;

Route::middleware(['auth:api'])->group(function () {
    Route::post('/transfer', [TransferController::class, 'transfer']);
});
```

التحويل يتطلب مستخدم مصادق — JWT middleware يضمن أن `$request->user()` موجود.

## Admin Middleware

لا يوجد — العملية متاحة لجميع المستخدمين المصادقين.

## Throttle Middleware

```php
Route::post('/transfer', [TransferController::class, 'transfer'])
    ->middleware(['auth:api', 'throttle:30,1']);
```

## Permission Checks

```php
<?php
// في المتحكم
public function transfer(Request $request): JsonResponse
{
    $user = $request->user();

    // التأكد أن الحساب نشط
    if ($user->status !== 'active') {
        return response()->json([
            'success' => false,
            'message' => 'الحساب غير نشط',
        ], 403);
    }

    // التأكد من صلاحية التوكن عبر JWT claims
    $payload = Auth::payload()->toArray();
    if ($payload['role'] !== 'user') {
        return response()->json([
            'success' => false,
            'message' => 'ليس لديك صلاحية التحويل',
        ], 403);
    }
}
```

## JWT Token Claims

| Claim | الوصف |
|-------|-------|
| `role: user` | صلاحيات المستخدم العادي (تحويل، دفع) |
| `role: admin` | صلاحيات المدير |
| `role: agent` | صلاحيات الوكيل |
| `role: merchant` | صلاحيات التاجر |

عند إنشاء التوكن: `JWTAuth::fromUser($user)``

## Middleware Stack Summary

| Middleware | الغرض |
|------------|-------|
| `auth:api` | المصادقة والتأكد من صحة JWT |
| `throttle:30,1` | منع الإرسال المتكرر (30 req/min) |
| فحص `status` | التأكد من نشاط الحساب |
| `payload['role']` | التحقق من صلاحية التوكن عبر JWT claims |
