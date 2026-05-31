# 10 - المصادقة والصلاحيات — عرض الرصيد

## Auth Middleware

```php
<?php
// routes/api.php

use App\Http\Controllers\Api\BalanceController;

Route::middleware(['auth:api'])->group(function () {
    Route::get('/wallet/balance', [BalanceController::class, 'index']);
});
```

المستخدم يجب أن يكون مصادقاً لرؤية رصيده — JWT middleware.

## Admin Middleware

لا يحتاج — كل مستخدم يرى رصيده فقط.

## Throttle Middleware

```php
Route::get('/wallet/balance', [BalanceController::class, 'index'])
    ->middleware(['auth:api', 'throttle:60,1']);
```

## Permission Checks

```php
<?php
public function index(Request $request): JsonResponse
{
    $user = $request->user();

    // أي مستخدم مصادق يمكنه رؤية رصيده
    if (!in_array($user->status, ['active', 'pending'])) {
        return response()->json([
            'success' => false,
            'message' => 'الحساب غير نشط',
        ], 403);
    }

    // التوكن العادي يكفي — فحص JWT claims
    $payload = Auth::payload()->toArray();
    $role = $payload['role'] ?? '';
    if (!in_array($role, ['user', 'admin'])) {
        return response()->json([
            'success' => false,
            'message' => 'ليس لديك صلاحية',
        ], 403);
    }
}
```

## JWT Configuration

```php
<?php
// config/auth.php
'guards' => [
    'api' => [
        'driver' => 'jwt',
        'provider' => 'users',
    ],
],
```

## Middleware Stack Summary

| Middleware | الغرض |
|------------|-------|
| `auth:api` | المصادقة والتأكد من صحة JWT |
| `throttle:60,1` | 60 طلب كحد أقصى في الدقيقة |
| فحص `status` | التأكد من نشاط الحساب |
