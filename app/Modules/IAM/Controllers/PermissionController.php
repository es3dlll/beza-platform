<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\IAM\Models\Permission;
use Modules\IAM\Requests\StorePermissionRequest;

class PermissionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Permission::query();

        if ($request->filled('module')) {
            $query->byModule($request->input('module'));
        }

        $permissions = $query->withCount('roles')->get();

        return response()->json([
            'success' => true,
            'data' => $permissions,
        ]);
    }

    public function store(StorePermissionRequest $request): JsonResponse
    {
        $permission = Permission::create($request->validated());

        return response()->json([
            'success' => true,
            'data' => $permission,
            'message' => 'Permission created successfully',
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $permission = Permission::with('roles')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $permission,
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $permission = Permission::withCount('roles')->findOrFail($id);

        if ($permission->roles_count > 0) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'PERMISSION_IN_USE',
                    'message' => 'Cannot delete permission that is assigned to roles',
                    'message_ar' => 'لا يمكن حذف صلاحية مرتبطة بأدوار',
                ],
            ], 422);
        }

        $permission->delete();

        return response()->json([
            'success' => true,
            'message' => 'Permission deleted successfully',
        ]);
    }
}
