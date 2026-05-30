<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\IAM\Models\Permission;
use Modules\IAM\Requests\StorePermissionRequest;
use App\Support\ApiResponse;

final class PermissionController extends Controller
{
    use ApiResponse;
    public function index(Request $request): JsonResponse
    {
        $query = Permission::query();

        if ($request->filled('module')) {
            $query->byModule($request->input('module'));
        }

        $permissions = $query->withCount('roles')->get();

        return $this->respond($permissions);
    }

    public function store(StorePermissionRequest $request): JsonResponse
    {
        $permission = Permission::create($request->validated());

        return $this->respondCreated($permission, 'Permission created successfully');
    }

    public function show(string $id): JsonResponse
    {
        $permission = Permission::with('roles')->findOrFail($id);

        return $this->respond($permission);
    }

    public function destroy(string $id): JsonResponse
    {
        $permission = Permission::withCount('roles')->findOrFail($id);

        if ($permission->roles_count > 0) {
            return $this->respondError('PERMISSION_IN_USE', 'Cannot delete permission that is assigned to roles', 'لا يمكن حذف صلاحية مرتبطة بأدوار');
        }

        $permission->delete();

        return $this->respondDeleted('Permission deleted successfully');
    }
}
