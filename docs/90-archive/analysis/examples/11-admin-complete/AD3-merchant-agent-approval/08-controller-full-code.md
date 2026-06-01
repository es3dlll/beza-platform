# 08 - المتحكم الكامل (Controller Full Code)

## MerchantApprovalController

```php
<?php
// app/Http/Controllers/Api/Admin/MerchantApprovalController.php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RejectRequest;
use App\Http\Resources\Admin\MerchantApplicationResource;
use App\Services\Admin\MerchantApprovalService;
use Illuminate\Http\JsonResponse;

class MerchantApprovalController extends Controller
{
    public function __construct(
        private readonly MerchantApprovalService $approvalService
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => MerchantApplicationResource::collection(
                $this->approvalService->getPendingApplications()
            ),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => new MerchantApplicationResource(
                $this->approvalService->getApplicationDetail($id)
            ),
        ]);
    }

    public function approve(int $id): JsonResponse
    {
        $this->approvalService->approve($id, request()->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'تم الموافقة على طلب التاجر',
        ]);
    }

    public function reject(RejectRequest $request, int $id): JsonResponse
    {
        $this->approvalService->reject(
            $id,
            $request->input('reason'),
            request()->user()->id
        );

        return response()->json([
            'success' => true,
            'message' => 'تم رفض طلب التاجر',
        ]);
    }

    public function requestDocuments(int $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'تم طلب مستندات إضافية',
        ]);
    }
}
```

## AgentApprovalController

```php
<?php
// app/Http/Controllers/Api/Admin/AgentApprovalController.php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RejectRequest;
use App\Http\Resources\Admin\AgentApplicationResource;
use App\Services\Admin\AgentApprovalService;
use Illuminate\Http\JsonResponse;

class AgentApprovalController extends Controller
{
    public function __construct(
        private readonly AgentApprovalService $approvalService
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => AgentApplicationResource::collection(
                $this->approvalService->getPendingApplications()
            ),
        ]);
    }

    public function approve(int $id): JsonResponse
    {
        $this->approvalService->approve($id, request()->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'تم الموافقة على طلب الوكيل',
        ]);
    }

    public function reject(RejectRequest $request, int $id): JsonResponse
    {
        $this->approvalService->reject(
            $id,
            $request->input('reason'),
            request()->user()->id
        );

        return response()->json([
            'success' => true,
            'message' => 'تم رفض طلب الوكيل',
        ]);
    }
}
```

## المسارات (Routes)

```php
Route::middleware(['auth:api', 'admin'])
    ->prefix('admin')
    ->group(function () {
        // التجار
        Route::get('/merchants/applications', [MerchantApprovalController::class, 'index']);
        Route::get('/merchants/applications/{id}', [MerchantApprovalController::class, 'show']);
        Route::post('/merchants/{id}/approve', [MerchantApprovalController::class, 'approve']);
        Route::post('/merchants/{id}/reject', [MerchantApprovalController::class, 'reject']);
        Route::post('/merchants/{id}/request-documents', [MerchantApprovalController::class, 'requestDocuments']);

        // الوكلاء
        Route::get('/agents/applications', [AgentApprovalController::class, 'index']);
        Route::post('/agents/{id}/approve', [AgentApprovalController::class, 'approve']);
        Route::post('/agents/{id}/reject', [AgentApprovalController::class, 'reject']);
    });
```
