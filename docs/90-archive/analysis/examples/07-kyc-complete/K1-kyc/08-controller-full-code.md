# 08 - المتحكم الكامل مع كل سطر (Controller Full Code)

## KycController

```php
<?php
// app/Http/Controllers/Api/KycController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\KycSubmitRequest;
use App\Http\Resources\KycStatusResource;
use App\Services\KycService;
use Illuminate\Http\JsonResponse;

class KycController extends Controller
{
    public function __construct(
        private readonly KycService $kycService
    ) {}

    /**
     * POST /api/v1/kyc/submit — رفع وثائق KYC
     */
    public function submit(KycSubmitRequest $request): JsonResponse
    {
        $user = $request->user();

        $result = $this->kycService->submit(
            user:  $user,
            files: [
                'front_id'      => $request->file('front_id'),
                'back_id'       => $request->file('back_id'),
                'selfie'        => $request->file('selfie'),
                'address_proof' => $request->file('address_proof'),
            ],
            docType: $request->input('doc_type'),
        );

        return response()->json([
            'success' => true,
            'message' => $result['auto_rejected']
                ? 'لم تمر الوثائق الفحص التلقائي، الرجاء إعادة رفع صور ذات دقة أعلى'
                : 'تم رفع الوثائق بنجاح، في انتظار المراجعة',
            'data'    => [
                'status' => new KycStatusResource($user->fresh()),
            ],
        ], 201);
    }

    /**
     * GET /api/v1/kyc/status — حالة KYC
     */
    public function status(): JsonResponse
    {
        $user = request()->user();

        return response()->json([
            'success' => true,
            'data'    => [
                'status' => new KycStatusResource($user),
            ],
        ]);
    }
}

// app/Http/Resources/KycStatusResource.php

class KycStatusResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'kyc_status'      => $this->kyc_status,
            'is_verified'     => $this->kyc_status === 'verified',
            'kyc_verified_at' => $this->kyc_verified_at?->toIso8601String(),
            'can_submit'      => !in_array($this->kyc_status, ['pending', 'verified']),
        ];
    }
}
```

## المسار (Route)

```php
// routes/api.php
Route::middleware('auth:api')->prefix('kyc')->group(function () {
    Route::post('/submit', [KycController::class, 'submit']);
    Route::get('/status', [KycController::class, 'status']);
});

// Admin review routes
Route::middleware(['auth:api', 'is_admin'])->prefix('admin/kyc')->group(function () {
    Route::get('/pending', [\App\Http\Controllers\Api\Admin\KycReviewController::class, 'pending']);
    Route::post('/{user}/review', [\App\Http\Controllers\Api\Admin\KycReviewController::class, 'review']);
});
```
