# 08 - كود المتحكم الكامل (Controller Full Code)

## AuthController (جزء login)

```php
<?php
// app/Http/Controllers/Api/AuthController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService
    ) {}

    /**
     * POST /api/v1/auth/login
     *
     * تسجيل دخول المستخدم
     * يتحقق من: وجود المستخدم، كلمة المرور، حالة الحساب، القفل
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(
            phone:    $request->input('phone'),
            password: $request->input('password'),
            deviceId: $request->input('device_id'),
            ip:       $request->ip(),
        );

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الدخول بنجاح',
            'data'    => [
                'user'  => new UserResource($result['user']),
                'token' => $result['token'],
            ],
        ]);
    }
}
```

## تجديد التوكن (Token Refresh)

```php
/**
 * POST /api/v1/auth/refresh
 *
 * تجديد التوكن الحالي — يتطلب توكن صالح (حتى لو منتهي ضمن فترة السماح)
 */
public function refresh(): JsonResponse
{
    try {
        $newToken = JWTAuth::parseToken()->refresh();

        return response()->json([
            'success'    => true,
            'message'    => 'تم تجديد التوكن',
            'data'       => [
                'token'      => $newToken,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60,
            ],
        ]);
    } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
        return response()->json([
            'success' => false,
            'message' => 'لا يمكن تجديد التوكن — الرجاء إعادة تسجيل الدخول',
        ], 401);
    }
}
```

## المسار (Route)

```php
<?php
// routes/api.php

use App\Http\Controllers\Api\AuthController;

Route::post('/auth/login', [AuthController::class, 'login'])
    ->middleware('throttle:10,1');

Route::post('/auth/refresh', [AuthController::class, 'refresh'])
    ->middleware('auth:api');
```

## مثال الاستجابة

### نجاح (200)
```json
{
    "success": true,
    "message": "تم تسجيل الدخول بنجاح",
    "data": {
        "user": {
            "id": 1,
            "name": "علي أحمد",
            "phone": "0999123456",
            "status": "active",
            "kyc_status": "verified"
        },
        "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "token_type": "bearer",
        "expires_in": 3600
    }
}
```

### خطأ — بيانات غير صحيحة (401)
```json
{
    "success": false,
    "message": "بيانات الدخول غير صحيحة"
}
```

### خطأ — حساب موقوف (403)
```json
{
    "success": false,
    "message": "حسابك موقوف، يرجى التواصل مع الدعم"
}
```

### خطأ — حساب مقفل (429)
```json
{
    "success": false,
    "message": "تم قفل الحساب مؤقتاً بسبب كثرة المحاولات الفاشلة. حاول بعد 15 دقيقة"
}
```
