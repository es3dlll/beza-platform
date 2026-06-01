# 08 - متحكم 2FA الكامل (Controller Code)

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EnableTwoFactorRequest;
use App\Http\Requests\VerifyTwoFactorRequest;
use App\Services\TwoFactorService;
use Illuminate\Http\JsonResponse;

class TwoFactorController extends Controller
{
    public function __construct(
        private readonly TwoFactorService $twoFactorService
    ) {}

    /**
     * POST /api/v1/2fa/enable
     */
    public function enable(EnableTwoFactorRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasTwoFactorEnabled()) {
            return response()->json([
                'success' => false,
                'message' => 'المصادقة الثنائية مفعلة بالفعل',
            ], 400);
        }

        $secret = $this->twoFactorService->generateSecret();
        $qrCodeUrl = $this->twoFactorService->getQrCodeUrl($user, $secret);

        // تخزين Secret مؤقتاً للتحقق
        session(['pending_2fa_secret' => encrypt($secret)]);

        return response()->json([
            'success' => true,
            'data' => [
                'secret' => $secret,
                'qr_code_url' => $qrCodeUrl,
                'qr_code_svg' => $this->twoFactorService->getQrCodeSvg($secret),
            ],
        ]);
    }

    /**
     * POST /api/v1/2fa/verify
     */
    public function verify(VerifyTwoFactorRequest $request): JsonResponse
    {
        $user = $request->user();
        $secret = decrypt(session('pending_2fa_secret'));

        if (!$this->twoFactorService->verifyCode($secret, $request->code)) {
            return response()->json([
                'success' => false,
                'message' => 'رمز التحقق غير صحيح',
            ], 422);
        }

        $user->setTwoFactorSecret($secret);
        $recoveryCodes = $this->twoFactorService->generateRecoveryCodes();
        $user->setRecoveryCodes($recoveryCodes);
        $user->confirmTwoFactor();

        session()->forget('pending_2fa_secret');

        return response()->json([
            'success' => true,
            'message' => 'تم تفعيل المصادقة الثنائية بنجاح',
            'data' => [
                'recovery_codes' => $recoveryCodes,
            ],
        ]);
    }

    /**
     * POST /api/v1/2fa/disable
     */
    public function disable(EnableTwoFactorRequest $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasTwoFactorEnabled()) {
            return response()->json([
                'success' => false,
                'message' => 'المصادقة الثنائية غير مفعلة',
            ], 400);
        }

        $user->disableTwoFactor();

        return response()->json([
            'success' => true,
            'message' => 'تم إلغاء المصادقة الثنائية',
        ]);
    }
}
```
