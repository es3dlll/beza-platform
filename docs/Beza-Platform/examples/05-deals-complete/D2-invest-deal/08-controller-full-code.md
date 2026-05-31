# 08 - المتحكم الكامل مع كل سطر (Controller Full Code)

## DealController (invest)

```php
<?php
// app/Http/Controllers/Api/DealController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\InvestRequest;
use App\Http\Resources\DealResource;
use App\Http\Resources\InvestmentResource;
use App\Models\Deal;
use App\Services\InvestService;
use Illuminate\Http\JsonResponse;

class DealController extends Controller
{
    public function __construct(
        private readonly InvestService $investService
    ) {}

    /**
     * POST /api/v1/deals/{deal}/invest
     */
    public function invest(Deal $deal, InvestRequest $request): JsonResponse
    {
        $user = $request->user();

        $result = $this->investService->invest(
            user:   $user,
            deal:   $deal,
            amount: (float) $request->input('amount'),
        );

        return response()->json([
            'success' => true,
            'message' => 'تم الاستثمار بنجاح',
            'data'    => [
                'investment' => new InvestmentResource($result['investment']),
                'deal'       => new DealResource($result['deal']),
                'new_balance' => $result['new_balance'],
            ],
        ], 201);
    }

    /**
     * GET /api/v1/deals — قائمة الصفقات النشطة
     */
    public function index(): JsonResponse
    {
        $deals = Deal::available()->latest()->paginate(20);
        return response()->json([
            'success' => true,
            'data'    => DealResource::collection($deals),
        ]);
    }

    /**
     * GET /api/v1/deals/{deal} — تفاصيل صفقة
     */
    public function show(Deal $deal): JsonResponse
    {
        $deal->load('investments.investor');
        return response()->json([
            'success' => true,
            'data'    => new DealResource($deal),
        ]);
    }
}

// app/Http/Resources/InvestmentResource.php

class InvestmentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'            => $this->id,
            'deal_id'       => $this->deal_id,
            'amount'        => (float) $this->amount,
            'currency'      => $this->currency,
            'status'        => $this->status,
            'created_at'    => $this->created_at->toIso8601String(),
        ];
    }
}
```

## المسار (Route)

```php
// routes/api.php
Route::middleware('auth:api')->group(function () {
    Route::post('/deals/{deal}/invest', [DealController::class, 'invest']);
    Route::get('/deals', [DealController::class, 'index']);
    Route::get('/deals/{deal}', [DealController::class, 'show']);
});
```
