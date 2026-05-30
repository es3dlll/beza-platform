<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\IAM\Models\Role;
use Modules\IAM\Requests\AssignPermissionsRequest;
use Modules\IAM\Requests\StoreRoleRequest;
use Modules\IAM\Requests\UpdateRoleRequest;
use Modules\IAM\Services\IamService;
use App\Support\ApiResponse;

final class RoleController extends Controller
{
    use ApiResponse;
    public function __construct(
        private IamService $iamService,
    ) {}

    public function index(): JsonResponse
    {
        $roles = Role::withCount('permissions')->get();

        return $this->respond($roles);
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        $role = $this->iamService->createRole(
            $request->validated()['name'],
            $request->validated()['description'] ?? '',
            $request->validated()['guard_name'] ?? 'api',
        );

        return $this->respondCreated($role->loadCount('permissions'), 'Role created successfully');
    }

    public function show(string $id): JsonResponse
    {
        $role = Role::with('permissions')->findOrFail($id);

        return $this->respond($role);
    }

    public function update(UpdateRoleRequest $request, string $id): JsonResponse
    {
        $role = Role::findOrFail($id);

        if ($role->is_system && $request->has('name')) {
            return $this->respondError('SYSTEM_ROLE_IMMUTABLE', 'Cannot change the name of a system role', 'لا يمكن تغيير اسم دور النظام');
        }

        $role->update($request->validated());

        return $this->respond($role->loadCount('permissions'), 'Role updated successfully');
    }

    public function destroy(string $id): JsonResponse
    {
        $role = Role::findOrFail($id);

        if ($role->is_system) {
            return $this->respondError('SYSTEM_ROLE_PROTECTED', 'System roles cannot be deleted', 'لا يمكن حذف أدوار النظام');
        }

        $role->delete();

        return $this->respondDeleted('Role deleted successfully');
    }

    public function assignPermissions(string $roleId, AssignPermissionsRequest $request): JsonResponse
    {
        $role = Role::findOrFail($roleId);

        $role->permissions()->syncWithoutDetaching($request->validated()['permissions']);

        return $this->respond($role->load('permissions'), 'Permissions assigned successfully');
    }
}
