<?php

declare(strict_types=1);

namespace Modules\IAM\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Modules\IAM\Models\Role;

class RoleRepository
{
    public function findByName(string $name): ?Role
    {
        return Role::where('name', $name)->first();
    }

    public function findById(string $id): ?Role
    {
        return Role::find($id);
    }

    public function create(array $data): Role
    {
        $data['id'] ??= (string) Str::ulid();

        return Role::create($data);
    }

    public function getAll(): Collection
    {
        return Role::withCount('permissions')->get();
    }

    public function assignPermission(string $roleId, string $permissionId): void
    {
        $role = $this->findById($roleId);

        if ($role !== null) {
            $role->permissions()->syncWithoutDetaching([$permissionId]);
        }
    }

    public function getPermissionsForRole(string $roleId): Collection
    {
        $role = $this->findById($roleId);

        if ($role === null) {
            return new Collection();
        }

        return $role->permissions()->get();
    }

    public function delete(string $id): void
    {
        $role = $this->findById($id);

        if ($role === null) {
            return;
        }

        if ($role->is_system) {
            throw new \RuntimeException('System roles cannot be deleted.');
        }

        $role->delete();
    }
}
