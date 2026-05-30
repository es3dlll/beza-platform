<?php

declare(strict_types=1);

namespace Tests\Feature\IAM;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\IAM\Models\Permission;
use Modules\IAM\Models\Role;
use Modules\Identity\Models\User;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private User $userWithPermission;

    private User $userWithoutPermission;

    private User $userWithRole;

    private string $tokenWithPermission;

    private string $tokenWithoutPermission;

    private string $tokenWithRole;

    protected function setUp(): void
    {
        parent::setUp();

        $testPermission = Permission::create([
            'name' => 'admin.access',
            'module' => 'Test',
            'description' => 'Test access',
        ]);

        $otherPermission = Permission::create([
            'name' => 'other.access',
            'module' => 'Test',
            'description' => 'Other access',
        ]);

        $adminRole = Role::create([
            'name' => 'admin_role',
            'guard_name' => 'api',
            'description' => 'Admin role',
        ]);

        $adminRole->permissions()->attach([$testPermission->id, $otherPermission->id]);

        $viewerRole = Role::create([
            'name' => 'viewer_role',
            'guard_name' => 'api',
            'description' => 'Viewer role',
        ]);

        $this->userWithPermission = User::factory()->verified()->withPin('789012')->create([
            'phone' => '963100000001',
        ]);
        $this->userWithPermission->roles()->attach($adminRole->id);

        $this->userWithoutPermission = User::factory()->verified()->withPin('789012')->create([
            'phone' => '963100000002',
        ]);
        $this->userWithoutPermission->roles()->attach($viewerRole->id);

        $this->userWithRole = User::factory()->verified()->withPin('789012')->create([
            'phone' => '963100000003',
        ]);
        $this->userWithRole->roles()->attach($adminRole->id);

        $resp1 = $this->postJson('/api/v1/auth/login', ['phone' => '963100000001', 'pin' => '789012']);
        $this->tokenWithPermission = $resp1->json('data.token');

        $resp2 = $this->postJson('/api/v1/auth/login', ['phone' => '963100000002', 'pin' => '789012']);
        $this->tokenWithoutPermission = $resp2->json('data.token');

        $resp3 = $this->postJson('/api/v1/auth/login', ['phone' => '963100000003', 'pin' => '789012']);
        $this->tokenWithRole = $resp3->json('data.token');
    }

    public function test_user_with_permission_can_access_protected_route(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenWithPermission,
        ])->getJson('/api/v1/admin/roles');

        $response->assertStatus(200);
    }

    public function test_user_without_permission_cannot_access_protected_route(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenWithoutPermission,
        ])->getJson('/api/v1/admin/roles');

        $response->assertStatus(403);
    }

    public function test_user_with_role_inherits_role_permissions(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenWithRole,
        ])->getJson('/api/v1/admin/roles');

        $response->assertStatus(200);
    }

    public function test_multiple_permissions_checked_correctly(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenWithPermission,
        ])->getJson('/api/v1/admin/permissions');

        $response->assertStatus(200);
    }
}
