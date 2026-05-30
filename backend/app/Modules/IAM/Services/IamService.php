<?php

declare(strict_types=1);

namespace Modules\IAM\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\IAM\Models\Role;
use Modules\IAM\Repositories\PermissionRepository;
use Modules\IAM\Repositories\RoleRepository;
use Modules\Identity\Models\User;

final class IamService
{
    public function __construct(
        private RoleRepository $roles,
        private PermissionRepository $permissions,
    ) {}

    public function createRole(string $name, string $description, string $guardName = 'api'): Role
    {
        return $this->roles->create([
            'name' => $name,
            'description' => $description,
            'guard_name' => $guardName,
        ]);
    }

    public function assignRoleToUser(string $userId, string $roleId): void
    {
        $user = User::findOrFail($userId);
        $user->roles()->syncWithoutDetaching([$roleId]);
    }

    public function revokeRoleFromUser(string $userId, string $roleId): void
    {
        $user = User::findOrFail($userId);
        $user->roles()->detach($roleId);
    }

    public function createPermission(string $name, string $module, string $description, string $guardName = 'api'): \Modules\IAM\Models\Permission
    {
        return $this->permissions->create([
            'name' => $name,
            'module' => $module,
            'description' => $description,
            'guard_name' => $guardName,
        ]);
    }

    public function assignPermissionToRole(string $roleId, string $permissionId): void
    {
        $this->roles->assignPermission($roleId, $permissionId);
    }

    public function getUsersByRole(string $roleId): Collection
    {
        $role = $this->roles->findById($roleId);

        if ($role === null) {
            return new Collection();
        }

        return $role->users()->get();
    }

    public function getUserRoles(string $userId): Collection
    {
        $user = User::findOrFail($userId);

        return $user->roles()->get();
    }
}
