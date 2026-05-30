<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Modules\IAM\Models\Role;
use Modules\Identity\Models\User;

abstract class TestCase extends BaseTestCase
{
    protected string $authToken = '';

    protected function authenticateUser(): void
    {
        $user = User::factory()->verified()->withPin('123456')->create([
            'phone' => '963900000001',
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'phone' => '963900000001',
            'pin' => '123456',
        ]);

        $this->authToken = $response->json('data.token');
        $this->withToken($this->authToken);
    }

    protected function authenticateAdmin(): void
    {
        $this->authenticateUser();

        $user = User::where('phone', '963900000001')->first();

        $adminPermission = \Modules\IAM\Models\Permission::firstOrCreate(
            ['name' => 'admin.access'],
            ['id' => (new \Illuminate\Support\Str())->ulid()->toBase32(), 'guard_name' => 'api', 'module' => 'Admin', 'description' => 'Access admin dashboard']
        );

        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['id' => (new \Illuminate\Support\Str())->ulid()->toBase32(), 'guard_name' => 'api', 'description' => 'Admin role']
        );

        $adminRole->permissions()->syncWithoutDetaching([$adminPermission->id]);
        $user->roles()->syncWithoutDetaching([$adminRole->id]);
    }
}
