# 08 - المتحكم الكامل مع كل سطر (Controller Full Code)

## AdminDealController (cancel)

```php
<?php
// app/Http/Controllers/Api/Admin/AdminDealController.php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\DealCancelRequest;
use App\Http\Resources\DealResource;
use App\Models\Deal;
use App\Services\RefundService;
use Illuminate\Http\JsonResponse;

class AdminDealController extends Controller
{
    public function __construct(
        private readonly RefundService $refundService
    ) {}

    /**
     * POST /api/v1/admin/deals/{deal}/cancel
     */
    public function cancel(Deal $deal, DealCancelRequest $request): JsonResponse
    {
        $reason = $request->input('reason');

        $result = $this->refundService->refund(
            deal:   $deal,
            reason: $reason,
        );

        return response()->json([
            'success' => true,
            'message' => 'تم إلغاء الصفقة واسترجاع المبالغ بنجاح',
            'data'    => [
                'deal'              => new DealResource($result['deal']),
                'total_refunded'    => $result['total_refunded'],
                'investors_count'   => $result['investors_count'],
            ],
        ], 200);
    }
}
```

## المسار (Route)

```php
// routes/api.php
Route::middleware(['auth:api', 'is_admin'])->prefix('admin')->group(function () {
    Route::post('/deals/{deal}/cancel', [AdminDealController::class, 'cancel']);
});
```
