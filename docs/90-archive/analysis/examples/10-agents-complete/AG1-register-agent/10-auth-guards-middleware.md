# 10 - المصادقة والصلاحيات — تسجيل وكيل

## Auth Middleware

```php
<?php
// routes/api.php

use App\Http\Controllers\Api\AgentRegisterController;

Route::middleware(['auth:api'])->group(function () {
    Route::post('/agent/register', [AgentRegisterController::class, 'register']);
});
```

## Admin Middleware

لا يحتاج — أي مستخدم عادي يمكنه التقديم ليكون وكيلاً.

## Throttle Middleware

```php
Route::post('/agent/register', [AgentRegisterController::class, 'register'])
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
            'message' => 'الحساب غير نشط',
        ], 403);
    }

    // المستخدم يجب ألا يكون وكيلاً مسبقاً
    if ($user->agent) {
        return response()->json([
            'success' => false,
            'message' => 'أنت مسجل كوكيل بالفعل',
        ], 422);
    }

    // يجب أن يكون KYC مكتملاً
    if ($user->kyc_status !== 'verified') {
        return response()->json([
            'success' => false,
            'message' => 'يجب إكمال التحقق من الهوية KYC أولاً',
        ], 403);
    }

    $payload = Auth::payload()->toArray();
    if (($payload['role'] ?? '') !== 'user') {
        return response()->json([
            'success' => false,
            'message' => 'ليس لديك صلاحية التسجيل كوكيل',
        ], 403);
    }
}
```

## JWT Token Claims

| Claim | الوصف |
|-------|-------|
| `role: user` | صلاحية المستخدم العادي |
| `role: agent` | صلاحية الوكيل (بعد الموافقة) |

## Middleware Stack Summary

| Middleware | الغرض |
|------------|-------|
| `auth:api` | المصادقة والتأكد من صحة JWT |
| `throttle:5,1` | منع الإرسال المتكرر (5 req/min) |
| فحص `status` | التأكد من نشاط الحساب |
| فحص `kyc_status` | التأكد من إكمال KYC |
| فحص `agent` | التأكد من عدم وجود وكيل مسبق |
