# 10 - المصادقة والصلاحيات (Auth & Middleware) - تسجيل تاجر (Merchant Registration)

## Auth Middleware

```php
<?php
// routes/api.php

use App\Http\Controllers\Api\MerchantRegisterController;

Route::middleware(['auth:api'])->group(function () {
    Route::post('/merchant/register', [MerchantRegisterController::class, 'register']);
});
```

## Admin Middleware

لا يحتاج — أي مستخدم عادي يمكنه التسجيل كتاجر.

## Throttle Middleware

```php
Route::post('/merchant/register', [MerchantRegisterController::class, 'register'])
    ->middleware(['auth:api', 'throttle:5,1']);
```

## Permission Checks

```php
<?php
public function register(Request $request): JsonResponse
{
    $user = $request->user();

    // المستخدم يجب أن يكون نشطاً
    if ($user->status !== 'active') {
        return response()->json([
            'success' => false,
            'message' => 'الحساب غير نشط. يجب تفعيل الحساب أولاً',
        ], 403);
    }

    // المستخدم يجب ألا يكون تاجراً مسبقاً
    if ($user->merchant) {
        return response()->json([
            'success' => false,
            'message' => 'لديك حساب تاجر بالفعل',
        ], 422);
    }

    // التوكن يحتاج claim role: user
    $payload = Auth::payload()->toArray();
    if (($payload['role'] ?? '') !== 'user') {
        return response()->json([
            'success' => false,
            'message' => 'ليس لديك صلاحية التسجيل كتاجر',
        ], 403);
    }
}
```

## JWT Token Claims

| Claim | الوصف |
|-------|-------|
| `role: user` | صلاحية المستخدم العادي (كافية للتسجيل) |
| `role: merchant` | صلاحية التاجر (بعد الموافقة) |

## Middleware Stack Summary

| Middleware | الغرض |
|------------|-------|
| `auth:api` | المصادقة والتأكد من صحة JWT |
| `throttle:5,1` | منع الإرسال المتكرر (5 req/min) |
| فحص `status` | التأكد من نشاط الحساب |
| فحص `merchant` | التأكد من عدم وجود تاجر مسبق |
