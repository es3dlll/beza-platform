# 10 - المصادقة والصلاحيات (Auth & Middleware) — تسجيل الدخول (Login)

## Guest Middleware

```php
<?php
// routes/api.php

use App\Http\Controllers\Api\AuthController;

Route::post('/auth/login', [AuthController::class, 'login'])
    ->middleware('guest:api')
    ->middleware('throttle:10,1');
```

## Guest Middleware (مخصص)

```php
<?php
// app/Http/Middleware/RedirectIfAuthenticated.php

public function handle(Request $request, Closure $next, string ...$guards): mixed
{
    $guards = empty($guards) ? [null] : $guards;

    foreach ($guards as $guard) {
        if (Auth::guard($guard)->check()) {
            return response()->json([
                'message' => 'أنت مسجل الدخول بالفعل',
            ], 400);
        }
    }

    return $next($request);
}
```

## التحقق من 2FA بعد تسجيل الدخول

إذا كان 2FA مفعّلاً، يتم إنشاء توكن مؤقت ويطلب رمز 2FA:

```php
// في AuthService::login()
$needsTwoFactor = $user->hasTwoFactorEnabled();

if ($needsTwoFactor) {
    // إنشاء JWT مؤقت مع claims (صلاحية محدودة — 5 دقائق)
    $token = JWTAuth::customClaims(['2fa' => 'pending'])->fromUser($user);

    return [
        'user'            => $user,
        'access_token'    => $token,
        'token_type'      => 'bearer',
        'expires_in'      => 300,
        'requires_2fa'    => true,
    ];
}
```

## JWT Claims للتوكنات

| Claim | الاستخدام |
|-------|-----------|
| `role: user` | المستخدم العادي — بعد تسجيل الدخول الكامل |
| `2fa: pending` | توكن مؤقت — ينتظر إدخال رمز 2FA |
| `2fa: verified` | بعد التحقق من 2FA — صلاحية قصيرة |

## التحقق من Claims في Middleware

```php
<?php
// app/Http/Middleware/CheckTwoFactor.php

public function handle(Request $request, Closure $next): mixed
{
    $payload = Auth::payload()->toArray();

    if (($payload['2fa'] ?? '') === 'pending') {
        return response()->json([
            'message' => 'يرجى إكمال المصادقة الثنائية',
            'requires_2fa' => true,
        ], 403);
    }

    return $next($request);
}
```
