<?php

declare(strict_types=1);

namespace Tests\Feature\IAM;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\IAM\Models\Permission;
use Modules\IAM\Models\Role;
use Modules\Identity\Models\User;
use Tests\TestCase;

class RoleTest extends TestCase
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
            'name' => 'role.manage',
            'module' => 'IAM',
            'description' => 'Manage roles',
        ]);

        $adminRole->permissions()->attach([$adminPermission->id, $managePermission->id]);

        $this->admin = User::factory()->verified()->withPin('789012')->create([
            'phone' => '963000000001',
        ]);

        $this->admin->roles()->attach($adminRole->id);

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'phone' => '963000000001',
            'pin' => '789012',
        ]);

        $this->token = $loginResponse->json('data.token');
    }

    public function test_admin_can_create_role(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/v1/admin/roles', [
            'name' => 'test_role',
            'description' => 'Test role description',
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data' => ['id', 'name', 'description']]);

        $this->assertDatabaseHas('roles', [
            'name' => 'test_role',
            'description' => 'Test role description',
        ]);
    }

    public function test_admin_can_list_roles(): void
    {
        Role::create([
            'name' => 'custom_role',
            'guard_name' => 'api',
            'description' => 'Custom role',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/admin/roles');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data' => [['id', 'name', 'permissions_count']]]);
    }

    public function test_admin_can_show_role_detail(): void
    {
        $role = Role::create([
            'name' => 'detail_role',
            'guard_name' => 'api',
            'description' => 'Detail check',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson("/api/v1/admin/roles/{$role->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonPath('data.name', 'detail_role');
    }

    public function test_admin_can_update_role(): void
    {
        $role = Role::create([
            'name' => 'updatable_role',
            'guard_name' => 'api',
            'description' => 'Original',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson("/api/v1/admin/roles/{$role->id}", [
            'description' => 'Updated description',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'description' => 'Updated description',
        ]);
    }

    public function test_admin_cannot_delete_system_role(): void
    {
        $systemRole = Role::create([
            'name' => 'system_role_test',
            'guard_name' => 'api',
            'description' => 'System role',
            'is_system' => true,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->deleteJson("/api/v1/admin/roles/{$systemRole->id}");

        $response->assertStatus(422);

        $this->assertDatabaseHas('roles', ['id' => $systemRole->id]);
    }

    public function test_admin_can_assign_permissions_to_role(): void
    {
        $role = Role::create([
            'name' => 'assign_test_role',
            'guard_name' => 'api',
            'description' => 'Assign test',
        ]);

        $perm = Permission::create([
            'name' => 'test.permission',
            'module' => 'Test',
            'description' => 'Test permission',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson("/api/v1/admin/roles/{$role->id}/permissions", [
            'permissions' => [$perm->id],
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('role_permissions', [
            'role_id' => $role->id,
            'permission_id' => $perm->id,
        ]);
    }

    public function test_role_name_must_be_unique(): void
    {
        Role::create([
            'name' => 'duplicate_role',
            'guard_name' => 'api',
            'description' => 'Original',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/v1/admin/roles', [
            'name' => 'duplicate_role',
            'description' => 'Duplicate',
        ]);

        $response->assertStatus(422);
    }

    public function test_unauthorized_user_cannot_manage_roles(): void
    {
        $response = $this->getJson('/api/v1/admin/roles');

        $response->assertStatus(401);
    }
}
