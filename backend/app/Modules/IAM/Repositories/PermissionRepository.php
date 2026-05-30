<?php

declare(strict_types=1);

namespace Modules\IAM\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Modules\IAM\Models\Permission;

class PermissionRepository
{
    public function findByName(string $name): ?Permission
    {
        return Permission::where('name', $name)->first();
    }

    public function findByModule(string $module): Collection
    {
        return Permission::byModule($module)->get();
    }

    public function getAll(): Collection
    {
        return Permission::withCount('roles')->get();
    }

    public function create(array $data): Permission
    {
        $data['id'] ??= (string) Str::ulid();

        return Permission::create($data);
    }
}
