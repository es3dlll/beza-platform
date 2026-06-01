# 08 - المتحكم الكامل مع كل سطر (Controller Full Code)

## ReferralController

```php
<?php
// app/Http/Controllers/Api/ReferralController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReferralClaimRequest;
use App\Http\Resources\ReferralCodeResource;
use App\Http\Resources\ReferralRewardResource;
use App\Services\ReferralService;
use App\Services\RewardService;
use Illuminate\Http\JsonResponse;

class ReferralController extends Controller
{
    public function __construct(
        private readonly ReferralService $referralService,
        private readonly RewardService   $rewardService,
    ) {}

    /**
     * POST /api/v1/referral/code — إنشاء كود إحالة
     */
    public function generateCode(): JsonResponse
    {
        $user = request()->user();
        $code = $this->referralService->generateCode($user);

        return response()->json([
            'success' => true,
            'data'    => [
                'code' => new ReferralCodeResource($code),
            ],
        ]);
    }

    /**
     * POST /api/v1/referral/claim — تسجيل بدعوة
     */
    public function claim(ReferralClaimRequest $request): JsonResponse
    {
        $user = $request->user();
        $codeStr = $request->input('code');

        $this->referralService->claim($user, $codeStr);

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الدعوة بنجاح',
        ]);
    }

    /**
     * GET /api/v1/referral/rewards — مكافآتي
     */
    public function myRewards(): JsonResponse
    {
        $user = request()->user();
        $rewards = $user->referralRewardsGiven()->with('referred')->get();

        return response()->json([
            'success' => true,
            'data'    => ReferralRewardResource::collection($rewards),
        ]);
    }
}
```

## المسارات (Routes)

```php
// routes/api.php
Route::middleware('auth:api')->prefix('referral')->group(function () {
    Route::post('/code', [ReferralController::class, 'generateCode']);
    Route::post('/claim', [ReferralController::class, 'claim']);
    Route::get('/rewards', [ReferralController::class, 'myRewards']);
});
```
