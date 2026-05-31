# 10 - المصادقة والصلاحيات (Auth & Middleware) — تسجيل (Register)

## لا يحتاج مصادقة مسبقة

المستخدم غير مصادق بعد — لا `auth:api` middleware.

## Middleware المستخدمة

```php
<?php
// routes/api.php

use App\Http\Controllers\Api\AuthController;

Route::post('/auth/register', [AuthController::class, 'register'])
    ->middleware('throttle:10,1');
    // throttle: 10 محاولات كحد أقصى في الدقيقة
    // منع إنشاء حسابات وهمية بشكل آلي
```

## JWT Configuration

تطبيق JWT مع `auth:api` guard:

```php
<?php
// config/auth.php

'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],
    'api' => [
        'driver' => 'jwt',
        'provider' => 'users',
    ],
],
```

## دوال المصادقة بعد التسجيل

بعد التسجيل الناجح، المستخدم يستلم توكن ويصبح مصادقاً تلقائياً للطلبات اللاحقة.

```php
// إنشاء JWT مع claims
$token = JWTAuth::fromUser($user);
// الرد:
// {"access_token": "eyJ...", "token_type": "bearer", "expires_in": 3600}
```

## صلاحيات JWT Claims

| Claim | الوصف |
|-------|-------|
| `role: user` | صلاحيات المستخدم العادي (تحويل، دفع، إلخ) |
| `role: admin` | صلاحيات المدير (نظام الإدارة) |
| `role: agent` | صلاحيات الوكيل (كاش إن/آوت) |
| `role: merchant` | صلاحيات التاجر (مدفوعات تجارية) |

عند التسجيل: `claims: {role: user}`
