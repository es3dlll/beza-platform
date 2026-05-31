# 08 - كود المتحكم الكامل (Controller Full Code)

## AuthController (جزء logout)

```php
<?php
// app/Http/Controllers/Api/AuthController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService
    ) {}

    /**
     * POST /api/v1/auth/logout
     *
     * تسجيل الخروج — حذف التوكن الحالي فقط
     * يتطلب Bearer token في Header
     */
    public function logout(): JsonResponse
    {
        JWTAuth::invalidate(JWTAuth::getToken());

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الخروج بنجاح',
        ]);
    }

    /**
     * POST /api/v1/auth/logout-all
     *
     * تسجيل الخروج من كل الأجهزة — إبطال التوكن الحالي وإضافته للقائمة السوداء
     */
    public function logoutAll(): JsonResponse
    {
        $token = JWTAuth::getToken();
        JWTAuth::invalidate($token);

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الخروج من جميع الأجهزة',
            'data'    => [
                'devices_count' => 1,
            ],
        ]);
    }
}
```

## المسار (Route)

```php
<?php
// routes/api.php

use App\Http\Controllers\Api\AuthController;

Route::middleware('auth:api')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/logout-all', [AuthController::class, 'logoutAll']);
});
```

## مثال الاستجابة

### نجاح (200)
```json
{
    "success": true,
    "message": "تم تسجيل الخروج بنجاح"
}
```

### نجاح — كل الأجهزة (200)
```json
{
    "success": true,
    "message": "تم تسجيل الخروج من 3 أجهزة",
    "data": {
        "devices_count": 3
    }
}
```

### خطأ — بدون توكن (401)
```json
{
    "message": "Unauthenticated"
}
```
