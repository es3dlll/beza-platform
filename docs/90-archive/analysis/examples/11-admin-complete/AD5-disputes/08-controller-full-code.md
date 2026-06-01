# 08 - المتحكم الكامل (Controller)

## DisputeController (للمستخدم)

```php
<?php
// app/Http/Controllers/Api/DisputeController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DisputeRequest;
use App\Http\Resources\DisputeResource;
use App\Services\DisputeService;
use Illuminate\Http\JsonResponse;

class DisputeController extends Controller
{
    public function __construct(
        private readonly DisputeService $disputeService
    ) {}

    public function store(DisputeRequest $request): JsonResponse
    {
        $dispute = $this->disputeService->create(
            user: $request->user(),
            transactionId: $request->input('transaction_id'),
            reason: $request->input('reason'),
            description: $request->input('description'),
            evidenceFiles: $request->file('evidence_files', []),
        );

        return response()->json([
            'success' => true,
            'message' => 'تم تقديم النزاع بنجاح',
            'data'    => new DisputeResource($dispute),
        ], 201);
    }

    public function myDisputes(): JsonResponse
    {
        $disputes = $this->disputeService->getUserDisputes(request()->user()->id);

        return response()->json([
            'success' => true,
            'data'    => DisputeResource::collection($disputes),
        ]);
    }
}
```

## AdminDisputeController (للمشرف)

```php
<?php
// app/Http/Controllers/Api/Admin/AdminDisputeController.php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResolveDisputeRequest;
use App\Http\Resources\DisputeResource;
use App\Services\DisputeService;
use App\Services\DisputeResolutionService;
use Illuminate\Http\JsonResponse;

class AdminDisputeController extends Controller
{
    public function __construct(
        private readonly DisputeService           $disputeService,
        private readonly DisputeResolutionService $resolutionService,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => DisputeResource::collection(
                $this->disputeService->getOpenDisputes()
            ),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => new DisputeResource(
                $this->disputeService->getDetail($id)
            ),
        ]);
    }

    public function resolve(ResolveDisputeRequest $request, int $id): JsonResponse
    {
        $this->resolutionService->resolve(
            disputeId: $id,
            adminId: $request->user()->id,
            resolution: $request->input('resolution'),
            partialAmount: $request->input('partial_amount'),
            notes: $request->input('admin_notes'),
        );

        return response()->json([
            'success' => true,
            'message' => 'تم حل النزاع بنجاح',
        ]);
    }
}
```

## المسارات (Routes)

```php
// المستخدم
Route::middleware('auth:api')->group(function () {
    Route::post('/support/disputes', [DisputeController::class, 'store']);
    Route::get('/support/disputes', [DisputeController::class, 'myDisputes']);
});

// المشرف
Route::middleware(['auth:api', 'admin'])
    ->prefix('admin/disputes')
    ->group(function () {
        Route::get('/', [AdminDisputeController::class, 'index']);
        Route::get('/{id}', [AdminDisputeController::class, 'show']);
        Route::post('/{id}/resolve', [AdminDisputeController::class, 'resolve']);
    });
```
