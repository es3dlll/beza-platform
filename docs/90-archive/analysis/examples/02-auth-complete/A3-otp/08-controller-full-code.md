# 08 - كود المتحكم الكامل (Controller Full Code)

## AuthController (جزء OTP)

```php
<?php
// app/Http/Controllers/Api/AuthController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RequestOtpRequest;
use App\Http\Requests\VerifyOtpRequest;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function __construct(
        private readonly OtpService $otpService
    ) {}

    /**
     * POST /api/v1/auth/request-otp
     *
     * طلب إرسال رمز تحقق OTP إلى رقم الهاتف
     */
    public function requestOtp(RequestOtpRequest $request): JsonResponse
    {
        $otp = $this->otpService->generate(
            phone: $request->input('phone'),
        );

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال رمز التحقق',
            'data'    => [
                'expires_in' => 300,
                // في بيئة التطوير فقط — نرجع OTP
                $this->shouldReturnOtp() ? 'otp' : 'hint' => $this->shouldReturnOtp()
                    ? $otp->code
                    : 'تم الإرسال',
            ],
        ]);
    }

    /**
     * POST /api/v1/auth/verify-otp
     *
     * التحقق من رمز OTP
     */
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $this->otpService->verify(
            phone: $request->input('phone'),
            code:  $request->input('otp'),
        );

        return response()->json([
            'success' => true,
            'message' => 'تم التحقق بنجاح',
        ]);
    }

    private function shouldReturnOtp(): bool
    {
        return app()->environment('local', 'development', 'testing');
    }
}
```

## المسار (Route)

```php
<?php
// routes/api.php

use App\Http\Controllers\Api\AuthController;

Route::post('/auth/request-otp', [AuthController::class, 'requestOtp'])
    ->middleware('throttle:3,60');

Route::post('/auth/verify-otp', [AuthController::class, 'verifyOtp'])
    ->middleware('throttle:10,1');
```

## مثال الاستجابة

### طلب OTP (200)
```json
{
    "success": true,
    "message": "تم إرسال رمز التحقق",
    "data": {
        "expires_in": 300
    }
}
```

### طلب OTP — في بيئة التطوير (200)
```json
{
    "success": true,
    "message": "تم إرسال رمز التحقق",
    "data": {
        "expires_in": 300,
        "otp": "123456"
    }
}
```

### التحقق ناجح (200)
```json
{
    "success": true,
    "message": "تم التحقق بنجاح"
}
```

### خطأ — OTP خاطئ (422)
```json
{
    "success": false,
    "message": "رمز التحقق غير صحيح"
}
```

### خطأ — OTP منتهي (422)
```json
{
    "success": false,
    "message": "انتهت صلاحية رمز التحقق"
}
```
