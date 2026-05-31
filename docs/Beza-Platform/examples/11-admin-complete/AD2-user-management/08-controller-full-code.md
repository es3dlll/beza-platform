# 08 - المتحكم الكامل (Controller Full Code)

## AdminUserController

```php
<?php
// app/Http/Controllers/Api/Admin/AdminUserController.php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserFilterRequest;
use App\Http\Requests\Admin\UserUpdateRequest;
use App\Http\Resources\Admin\UserResource;
use App\Http\Resources\Admin\UserDetailResource;
use App\Services\Admin\UserManagementService;
use Illuminate\Http\JsonResponse;

class AdminUserController extends Controller
{
    public function __construct(
        private readonly UserManagementService $userService
    ) {}

    public function index(UserFilterRequest $request): JsonResponse
    {
        $users = $this->userService->list($request->validated());

        return response()->json([
            'success' => true,
            'data'    => UserResource::collection($users),
            'meta'    => [
                'current_page' => $users->currentPage(),
                'last_page'    => $users->lastPage(),
                'per_page'     => $users->perPage(),
                'total'        => $users->total(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $user = $this->userService->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => new UserDetailResource($user),
        ]);
    }

    public function update(UserUpdateRequest $request, int $id): JsonResponse
    {
        $user = $this->userService->update($id, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث بيانات المستخدم',
            'data'    => new UserResource($user),
        ]);
    }

    public function suspend(int $id): JsonResponse
    {
        $user = $this->userService->suspend($id, request()->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'تم تعليق المستخدم',
        ]);
    }

    public function activate(int $id): JsonResponse
    {
        $user = $this->userService->activate($id);

        return response()->json([
            'success' => true,
            'message' => 'تم تفعيل المستخدم',
        ]);
    }

    public function block(int $id): JsonResponse
    {
        $user = $this->userService->block($id, request()->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'تم حظر المستخدم',
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->userService->delete($id, request()->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'تم حذف المستخدم',
        ]);
    }

    public function transactions(int $id): JsonResponse
    {
        $transactions = $this->userService->getTransactions($id);

        return response()->json([
            'success' => true,
            'data'    => $transactions,
        ]);
    }
}
```

## المسارات (Routes)

```php
<?php
// routes/api.php

Route::middleware(['auth:api', 'admin'])
    ->prefix('admin/users')
    ->group(function () {
        Route::get('/', [AdminUserController::class, 'index']);
        Route::get('/{id}', [AdminUserController::class, 'show']);
        Route::put('/{id}', [AdminUserController::class, 'update']);
        Route::put('/{id}/suspend', [AdminUserController::class, 'suspend']);
        Route::put('/{id}/activate', [AdminUserController::class, 'activate']);
        Route::put('/{id}/block', [AdminUserController::class, 'block']);
        Route::delete('/{id}', [AdminUserController::class, 'destroy']);
        Route::get('/{id}/transactions', [AdminUserController::class, 'transactions']);
    });
```

## مثال الاستجابة

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "uuid": "abc-123",
            "name": "أحمد محمد",
            "phone": "963944123456",
            "email": "ahmed@beza.example",
            "status": "active",
            "kyc_status": "verified",
            "is_merchant": false,
            "is_agent": false,
            "wallet_balances": {
                "SYP": 500000.00,
                "USD": 1500.00
            },
            "created_at": "2026-01-15T10:00:00+03:00",
            "last_login_at": "2026-05-27T08:30:00+03:00"
        }
    ],
    "meta": {
        "current_page": 1,
        "last_page": 5,
        "per_page": 20,
        "total": 100
    }
}
```
