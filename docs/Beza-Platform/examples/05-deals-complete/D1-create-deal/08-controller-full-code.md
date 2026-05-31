# 08 - المتحكم الكامل مع كل سطر (Controller Full Code)

## AdminDealController

```php
<?php
// app/Http/Controllers/Api/Admin/AdminDealController.php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\DealStoreRequest;
use App\Http\Resources\DealResource;
use App\Services\AdminDealService;
use Illuminate\Http\JsonResponse;

class AdminDealController extends Controller
{
    public function __construct(
        private readonly AdminDealService $adminDealService
    ) {}

    /**
     * POST /api/v1/admin/deals — إنشاء صفقة جديدة
     */
    public function store(DealStoreRequest $request): JsonResponse
    {
        $admin = $request->user();

        $deal = $this->adminDealService->create(
            admin: $admin,
            data: $request->validated(),
        );

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الصفقة بنجاح',
            'data'    => [
                'deal' => new DealResource($deal),
            ],
        ], 201);
    }

    /**
     * GET /api/v1/admin/deals — قائمة الصفقات
     */
    public function index(): JsonResponse
    {
        $deals = \App\Models\Deal::latest()->paginate(20);
        return response()->json([
            'success' => true,
            'data'    => DealResource::collection($deals),
        ]);
    }
}
```

## DealResource

```php
<?php
// app/Http/Resources/DealResource.php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DealResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                          => $this->id,
            'title'                       => $this->title,
            'description'                 => $this->description,
            'target_amount'               => (float) $this->target_amount,
            'current_amount'              => (float) $this->current_amount,
            'remaining_amount'            => (float) $this->remaining_amount,
            'progress_percentage'         => $this->progress_percentage,
            'currency'                    => $this->currency,
            'expected_profit_percentage'  => (float) $this->expected_profit_percentage,
            'duration_days'               => $this->duration_days,
            'category'                    => $this->category,
            'risk_level'                  => $this->risk_level,
            'status'                      => $this->status,
            'created_by'                  => [
                'id'   => $this->creator?->id,
                'name' => $this->creator?->name,
            ],
            'investors_count'             => $this->whenCounted('investments'),
            'created_at'                  => $this->created_at->toIso8601String(),
        ];
    }
}
```

## المسار (Route)

```php
<?php
// routes/api.php

use App\Http\Controllers\Api\Admin\AdminDealController;

Route::middleware(['auth:api', 'is_admin'])->prefix('admin')->group(function () {
    Route::post('/deals', [AdminDealController::class, 'store']);
    Route::get('/deals', [AdminDealController::class, 'index']);
});
```
