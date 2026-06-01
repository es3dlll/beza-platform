# 08 - كود المتحكم الكامل (Controller Full Code)

## AuthController (جزء register)

```php
<?php
// app/Http/Controllers/Api/AuthController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
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
     * POST /api/v1/auth/register
     *
     * تسجيل مستخدم جديد في المنصة
     * يتم إنشاء: مستخدم + محفظتين (SYP/USD) + توكن
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register(
            name:     $request->input('name'),
            phone:    $request->input('phone'),
            password: $request->input('password'),
            pinCode:  $request->input('pin_code'),
            deviceId: $request->input('device_id'),
            ip:       $request->ip(),
        );

        return response()->json([
            'success' => true,
            'message' => 'تم التسجيل بنجاح',
            'data'    => [
                'user'    => new UserResource($result['user']),
                'wallets' => $result['wallets'],
                'token'   => $result['token'],
            ],
        ], 201);
    }
}
```

## UserResource

```php
<?php
// app/Http/Resources/UserResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'uuid'       => $this->uuid,
            'name'       => $this->name,
            'phone'      => $this->phone,
            'status'     => $this->status,
            'kyc_status' => $this->kyc_status,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
```

## WalletResource

```php
<?php
// app/Http/Resources/WalletResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'       => $this->id,
            'currency' => $this->currency,
            'balance'  => (float) $this->balance,
            'number'   => $this->wallet_number,
        ];
    }
}
```

## المسار (Route)

```php
<?php
// routes/api.php

use App\Http\Controllers\Api\AuthController;

Route::post('/auth/register', [AuthController::class, 'register'])
    ->middleware('throttle:10,1');
```

## مثال الاستجابة

### نجاح (201)
```json
{
    "success": true,
    "message": "تم التسجيل بنجاح",
    "data": {
        "user": {
            "id": 1,
            "uuid": "550e8400-e29b-41d4-a716-446655440000",
            "name": "علي أحمد",
            "phone": "0999123456",
            "status": "pending",
            "kyc_status": "not_submitted",
            "created_at": "2026-05-27T14:30:00+03:00"
        },
        "wallets": [
            {
                "id": 1,
                "currency": "SYP",
                "balance": 0.00,
                "number": "621234567890"
            },
            {
                "id": 2,
                "currency": "USD",
                "balance": 5.00,
                "number": "631234567890"
            }
        ],
        "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "token_type": "bearer",
        "expires_in": 3600
    }
}
```

### خطأ — رقم موجود (422)
```json
{
    "success": false,
    "message": "بيانات غير صحيحة",
    "errors": {
        "phone": ["رقم الهاتف مسجل مسبقاً"]
    }
}
```
