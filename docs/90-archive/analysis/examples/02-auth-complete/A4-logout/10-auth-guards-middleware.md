# 10 - المصادقة والصلاحيات (Auth & Middleware) — تسجيل الخروج (Logout)

## Auth JWT Middleware

```php
<?php
// routes/api.php

use App\Http\Controllers\Api\AuthController;

Route::middleware('auth:api')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/logout-all', [AuthController::class, 'logoutAll']);
});
```

## كيف يعمل auth:api (JWT)

1. يقرأ `Authorization: Bearer {token}` من Header
2. يفك تشفير JWT ويتحقق من التوقيع (signature)
3. يتحقق من صلاحية التوكن (exp, nbf)
4. يتحقق من أن التوكن ليس في القائمة السوداء (blacklist)
5. يحمل المستخدم المرتبط بالتوكن
6. يرفض الطلب (401) إذا كان التوكن غير صالح

## التحقق من Claims

```php
// يمكن إضافة middleware للتحقق من JWT claims
Route::post('/auth/logout', [AuthController::class, 'logout'])
    ->middleware('auth:api')
    ->middleware('claim:role,user');
```

## دوال الحماية المخصصة

```php
<?php
// app/Http/Middleware/EnsureTokenIsValid.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTokenIsValid
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        try {
            // التحقق من صحة JWT والتوقيع
            $payload = Auth::payload()->toArray();
        } catch (\Exception $e) {
            return response()->json(['message' => 'Token invalid or expired'], 401);
        }

        return $next($request);
    }
}
```
