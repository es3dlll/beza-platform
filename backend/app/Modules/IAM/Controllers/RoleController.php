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

class RoleController extends Controller
{
    public function __construct(
        private IamService $iamService,
    ) {}

    public function index(): JsonResponse
    {
        $roles = Role::withCount('permissions')->get();

        return response()->json([
            'success' => true,
            'data' => $roles,
        ]);
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        $role = $this->iamService->createRole(
            $request->validated()['name'],
            $request->validated()['description'] ?? '',
            $request->validated()['guard_name'] ?? 'api',
        );

        return response()->json([
            'success' => true,
            'data' => $role->loadCount('permissions'),
            'message' => 'Role created successfully',
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $role = Role::with('permissions')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $role,
        ]);
    }

    public function update(UpdateRoleRequest $request, string $id): JsonResponse
    {
        $role = Role::findOrFail($id);

        if ($role->is_system && $request->has('name')) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'SYSTEM_ROLE_IMMUTABLE',
                    'message' => 'Cannot change the name of a system role',
                    'message_ar' => 'لا يمكن تغيير اسم دور النظام',
                ],
            ], 422);
        }

        $role->update($request->validated());

        return response()->json([
            'success' => true,
            'data' => $role->loadCount('permissions'),
            'message' => 'Role updated successfully',
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $role = Role::findOrFail($id);

        if ($role->is_system) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'SYSTEM_ROLE_PROTECTED',
                    'message' => 'System roles cannot be deleted',
                    'message_ar' => 'لا يمكن حذف أدوار النظام',
                ],
            ], 422);
        }

        $role->delete();

        return response()->json([
            'success' => true,
            'message' => 'Role deleted successfully',
        ]);
    }

    public function assignPermissions(string $roleId, AssignPermissionsRequest $request): JsonResponse
    {
        $role = Role::findOrFail($roleId);

        $role->permissions()->syncWithoutDetaching($request->validated()['permissions']);

        return response()->json([
            'success' => true,
            'data' => $role->load('permissions'),
            'message' => 'Permissions assigned successfully',
        ]);
    }
}
