<?php

declare(strict_types=1);

namespace Tests\Feature\IAM;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\IAM\Models\Permission;
use Modules\IAM\Models\Role;
use Modules\Identity\Models\User;
use Tests\TestCase;

class PermissionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::create([
            'name' => 'super_admin',
            'guard_name' => 'api',
            'description' => 'Super Admin',
            'is_system' => true,
        ]);

        $adminPermission = Permission::create([
            'name' => 'admin.access',
            'module' => 'IAM',
            'description' => 'Access admin panel',
        ]);

        $managePermission = Permission::create([
            'name' => 'permission.manage',
            'module' => 'IAM',
            'description' => 'Manage permissions',
        ]);

        $adminRole->permissions()->attach([$adminPermission->id, $managePermission->id]);

        $this->admin = User::factory()->verified()->withPin('789012')->create([
            'phone' => '963000000002',
        ]);

        $this->admin->roles()->attach($adminRole->id);

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'phone' => '963000000002',
            'pin' => '789012',
        ]);

        $this->token = $loginResponse->json('data.token');
    }

    public function test_admin_can_create_permission(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/v1/admin/permissions', [
            'name' => 'test.permission',
            'module' => 'TestModule',
            'description' => 'Test permission description',
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data' => ['id', 'name', 'module']]);

        $this->assertDatabaseHas('permissions', [
            'name' => 'test.permission',
            'module' => 'TestModule',
        ]);
    }

    public function test_admin_can_list_permissions_filtered_by_module(): void
    {
        Permission::create(['name' => 'perm.one', 'module' => 'Alpha', 'description' => '']);
        Permission::create(['name' => 'perm.two', 'module' => 'Alpha', 'description' => '']);
        Permission::create(['name' => 'perm.three', 'module' => 'Beta', 'description' => '']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/admin/permissions?module=Alpha');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonCount(2, 'data');
    }

    public function test_permission_name_must_be_unique(): void
    {
        Permission::create([
            'name' => 'unique.perm',
            'module' => 'Test',
            'description' => 'Original',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/v1/admin/permissions', [
            'name' => 'unique.perm',
            'module' => 'Test',
            'description' => 'Duplicate',
        ]);

        $response->assertStatus(422);
    }

    public function test_permission_with_assigned_roles_cannot_be_deleted(): void
    {
        $role = Role::create([
            'name' => 'test_role_del',
            'guard_name' => 'api',
            'description' => 'Test',
        ]);

        $permission = Permission::create([
            'name' => 'protected.perm',
            'module' => 'Test',
            'description' => 'Protected',
        ]);

        $role->permissions()->attach($permission->id);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->deleteJson("/api/v1/admin/permissions/{$permission->id}");

        $response->assertStatus(422);

        $this->assertDatabaseHas('permissions', ['id' => $permission->id]);
    }

    public function test_unauthorized_user_cannot_manage_permissions(): void
    {
        $response = $this->getJson('/api/v1/admin/permissions');

        $response->assertStatus(401);
    }
}
