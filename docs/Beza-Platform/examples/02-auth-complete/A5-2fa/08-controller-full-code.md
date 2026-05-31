# 08 - المتحكم الكامل مع كل سطر — المصادقة الثنائية (2FA)

## TwoFactorController

```php
<?php
// app/Http/Controllers/Api/TwoFactorController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\VerifyTwoFactorRequest;
use App\Http\Requests\DisableTwoFactorRequest;
use App\Services\TwoFactorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class TwoFactorController extends Controller
{
    public function __construct(
        private readonly TwoFactorService $twoFactorService
    ) {}

    /**
     * POST /api/v1/auth/2fa/enable
     *
     * تفعيل المصادقة الثنائية — توليد secret و QR code
     */
    public function enable(): JsonResponse
    {
        $result = $this->twoFactorService->enable(auth()->user());

        return response()->json([
            'success' => true,
            'message' => 'تم تفعيل 2FA، يرجى مسح QR code ضوئياً',
            'data'    => [
                'qr_code' => $result['qr_code'],
                'secret'  => $result['secret'],
            ],
        ]);
    }

    /**
     * POST /api/v1/auth/2fa/verify
     *
     * تأكيد تفعيل 2FA عبر إدخال رمز من Google Authenticator
     */
    public function verify(VerifyTwoFactorRequest $request): JsonResponse
    {
        $this->twoFactorService->verify(
            user: auth()->user(),
            code: $request->input('code'),
        );

        return response()->json([
            'success' => true,
            'message' => 'تم تأكيد تفعيل المصادقة الثنائية',
            'data'    => [
                'two_factor_confirmed' => true,
            ],
        ]);
    }

    /**
     * POST /api/v1/auth/2fa/disable
     *
     * تعطيل المصادقة الثنائية (يتطلب إعادة المصادقة)
     */
    public function disable(DisableTwoFactorRequest $request): JsonResponse
    {
        $this->twoFactorService->disable(
            user:     auth()->user(),
            password: $request->input('password'),
            code:     $request->input('code'),
        );

        return response()->json([
            'success' => true,
            'message' => 'تم تعطيل المصادقة الثنائية',
        ]);
    }
}
```

## المسار (Route)

```php
<?php
// routes/api.php

use App\Http\Controllers\Api\TwoFactorController;

Route::middleware('auth:api')->group(function () {
    Route::post('/auth/2fa/enable',  [TwoFactorController::class, 'enable']);
    Route::post('/auth/2fa/verify',  [TwoFactorController::class, 'verify'])
        ->middleware('throttle:5,1');
    Route::post('/auth/2fa/disable', [TwoFactorController::class, 'disable']);
});
```

## مثال الاستجابة

### تفعيل 2FA (200)
```json
{
    "success": true,
    "message": "تم تفعيل 2FA، يرجى مسح QR code ضوئياً",
    "data": {
        "qr_code": "data:image/png;base64,iVBORw0KGgo...",
        "secret": "JBSWY3DPEHPK3PXP"
    }
}
```

### تأكيد التفعيل (200)
```json
{
    "success": true,
    "message": "تم تأكيد تفعيل المصادقة الثنائية",
    "data": {
        "two_factor_confirmed": true
    }
}
```

### خطأ — رمز خاطئ (422)
```json
{
    "success": false,
    "message": "رمز التحقق غير صحيح"
}
```
