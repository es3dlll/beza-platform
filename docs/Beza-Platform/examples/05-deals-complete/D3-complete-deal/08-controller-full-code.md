# 08 - المتحكم الكامل مع كل سطر (Controller Full Code)

## AdminDealController (complete)

```php
<?php
// app/Http/Controllers/Api/Admin/AdminDealController.php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\DealCompleteRequest;
use App\Http\Resources\DealResource;
use App\Models\Deal;
use App\Services\ProfitDistributionService;
use Illuminate\Http\JsonResponse;

class AdminDealController extends Controller
{
    public function __construct(
        private readonly ProfitDistributionService $profitService
    ) {}

    /**
     * POST /api/v1/admin/deals/{deal}/complete
     */
    public function complete(Deal $deal, DealCompleteRequest $request): JsonResponse
    {
        $profitActual = (float) $request->input('profit_actual');

        $result = $this->profitService->distribute(
            deal:          $deal,
            profitActual:  $profitActual,
        );

        return response()->json([
            'success' => true,
            'message' => 'تم إتمام الصفقة وتوزيع الأرباح بنجاح',
            'data'    => [
                'deal'              => new DealResource($result['deal']),
                'total_profit'      => $result['total_profit'],
                'investors_count'   => $result['investors_count'],
                'distributions'     => $result['distributions'],
            ],
        ], 200);
    }
}
```

## المسار (Route)

```php
// routes/api.php
Route::middleware(['auth:api', 'is_admin'])->prefix('admin')->group(function () {
    Route::post('/deals/{deal}/complete', [AdminDealController::class, 'complete']);
});
```
