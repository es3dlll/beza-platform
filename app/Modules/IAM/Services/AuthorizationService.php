<?php

declare(strict_types=1);

namespace Modules\IAM\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\IAM\Exceptions\UnauthorizedException;
use Modules\IAM\Models\Policy;
use Modules\IAM\Repositories\PermissionRepository;
use Modules\IAM\Repositories\RoleRepository;
use Modules\Identity\Models\User;

class AuthorizationService
{
    public function __construct(
        private RoleRepository $roles,
        private PermissionRepository $permissions,
    ) {}

    public function userHasPermission(User $user, string $permission): bool
    {
        return $user->roles()
            ->whereHas('permissions', fn($q) => $q->where('name', $permission))
            ->exists();
    }

    public function userHasRole(User $user, string $role): bool
    {
        return $user->roles()->where('name', $role)->exists();
    }

    public function authorize(User $user, string $permission): void
    {
        if (!$this->userHasPermission($user, $permission)) {
            throw new UnauthorizedException("User lacks permission: {$permission}");
        }
    }

    public function getUserPermissions(User $user): Collection
    {
        $permissionIds = DB::table('iam_user_roles')
            ->where('user_id', $user->id)
            ->join('role_permissions', 'iam_user_roles.role_id', '=', 'role_permissions.role_id')
            ->pluck('role_permissions.permission_id')
            ->unique()
            ->values();

        if ($permissionIds->isEmpty()) {
            return new Collection();
        }

        return \Modules\IAM\Models\Permission::whereIn('id', $permissionIds)->get();
    }

    public function canPerformAction(User $user, string $resource, string $action, array $context = []): bool
    {
        $policies = Policy::where('resource', $resource)
            ->where('action', $action)
            ->orderBy('effect', 'desc')
            ->get();

        foreach ($policies as $policy) {
            if ($policy->evaluate($user, $context)) {
                return $policy->effect === 'allow';
            }
        }

        return false;
    }
}
