<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\IAM\Exceptions\UnauthorizedException;
use Modules\IAM\Models\Permission;
use Modules\IAM\Models\Policy;
use Modules\IAM\Models\Role;
use Modules\IAM\Repositories\PermissionRepository;
use Modules\IAM\Repositories\RoleRepository;
use Modules\IAM\Services\AuthorizationService;
use Modules\Identity\Models\User;
use Tests\TestCase;

class AuthorizationServiceTest extends TestCase
{
    use RefreshDatabase;

    private AuthorizationService $authService;

    private User $user;

    private Role $role;

    private Permission $permission;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authService = new AuthorizationService(
            $this->app->make(RoleRepository::class),
            $this->app->make(PermissionRepository::class),
        );

        $this->permission = Permission::create([
            'name' => 'test.permission',
            'module' => 'Test',
            'description' => 'Test permission',
        ]);

        $this->role = Role::create([
            'name' => 'test_role',
            'guard_name' => 'api',
            'description' => 'Test role',
        ]);

        $this->role->permissions()->attach($this->permission->id);

        $this->user = User::factory()->verified()->withPin('123456')->create([
            'phone' => '963123456789',
        ]);

        $this->user->roles()->attach($this->role->id);
    }

    public function test_user_has_permission_returns_true_when_user_has_permission(): void
    {
        $result = $this->authService->userHasPermission($this->user, 'test.permission');

        $this->assertTrue($result);
    }

    public function test_user_has_permission_returns_false_when_user_lacks_permission(): void
    {
        $result = $this->authService->userHasPermission($this->user, 'nonexistent.permission');

        $this->assertFalse($result);
    }

    public function test_user_has_permission_returns_false_for_user_without_roles(): void
    {
        $userWithoutRoles = User::factory()->create(['phone' => '963999999999']);

        $result = $this->authService->userHasPermission($userWithoutRoles, 'test.permission');

        $this->assertFalse($result);
    }

    public function test_user_has_role_returns_true_when_user_has_role(): void
    {
        $result = $this->authService->userHasRole($this->user, 'test_role');

        $this->assertTrue($result);
    }

    public function test_user_has_role_returns_false_when_user_lacks_role(): void
    {
        $result = $this->authService->userHasRole($this->user, 'nonexistent_role');

        $this->assertFalse($result);
    }

    public function test_authorize_does_not_throw_when_user_has_permission(): void
    {
        $this->authService->authorize($this->user, 'test.permission');

        $this->expectNotToPerformAssertions();
    }

    public function test_authorize_throws_unauthorized_exception_when_user_lacks_permission(): void
    {
        $this->expectException(UnauthorizedException::class);

        $this->authService->authorize($this->user, 'nonexistent.permission');
    }

    public function test_get_user_permissions_returns_all_permissions(): void
    {
        $perm2 = Permission::create([
            'name' => 'second.permission',
            'module' => 'Test',
            'description' => 'Second permission',
        ]);

        $this->role->permissions()->attach($perm2->id);

        $permissions = $this->authService->getUserPermissions($this->user);

        $this->assertCount(2, $permissions);
        $this->assertTrue($permissions->contains('name', 'test.permission'));
        $this->assertTrue($permissions->contains('name', 'second.permission'));
    }

    public function test_get_user_permissions_returns_empty_collection_for_user_without_roles(): void
    {
        $userWithoutRoles = User::factory()->create(['phone' => '963999999998']);

        $permissions = $this->authService->getUserPermissions($userWithoutRoles);

        $this->assertCount(0, $permissions);
    }

    public function test_can_perform_action_returns_true_when_allow_policy_matches(): void
    {
        Policy::create([
            'name' => 'Allow view reports',
            'resource' => 'report',
            'action' => 'view',
            'effect' => 'allow',
            'conditions' => null,
        ]);

        $result = $this->authService->canPerformAction($this->user, 'report', 'view');

        $this->assertTrue($result);
    }

    public function test_can_perform_action_returns_false_when_deny_policy_matches(): void
    {
        Policy::create([
            'name' => 'Deny delete reports',
            'resource' => 'report',
            'action' => 'delete',
            'effect' => 'deny',
            'conditions' => null,
        ]);

        $result = $this->authService->canPerformAction($this->user, 'report', 'delete');

        $this->assertFalse($result);
    }

    public function test_can_perform_action_returns_false_when_no_policy_exists(): void
    {
        $result = $this->authService->canPerformAction($this->user, 'report', 'export');

        $this->assertFalse($result);
    }

    public function test_can_perform_action_evaluates_conditions_correctly(): void
    {
        Policy::create([
            'name' => 'Allow high-tier KYC',
            'resource' => 'kyc',
            'action' => 'approve',
            'effect' => 'allow',
            'conditions' => ['user.kyc_tier' => 'tier_2_advanced'],
        ]);

        $this->user->update(['kyc_tier' => 'tier_2_advanced']);

        $result = $this->authService->canPerformAction($this->user, 'kyc', 'approve');

        $this->assertTrue($result);
    }

    public function test_can_perform_action_denies_when_conditions_not_met(): void
    {
        Policy::create([
            'name' => 'Allow high-tier KYC',
            'resource' => 'kyc',
            'action' => 'approve',
            'effect' => 'allow',
            'conditions' => ['user.kyc_tier' => 'tier_3_premium'],
        ]);

        $this->user->update(['kyc_tier' => 'tier_1_basic']);

        $result = $this->authService->canPerformAction($this->user, 'kyc', 'approve');

        $this->assertFalse($result);
    }

    public function test_deny_policy_overrides_allow_policy(): void
    {
        Policy::create([
            'name' => 'Allow view transactions',
            'resource' => 'transaction',
            'action' => 'view',
            'effect' => 'allow',
            'conditions' => null,
        ]);

        Policy::create([
            'name' => 'Deny view transactions for low tier',
            'resource' => 'transaction',
            'action' => 'view',
            'effect' => 'deny',
            'conditions' => ['user.kyc_tier' => 'tier_1_basic'],
        ]);

        $this->user->update(['kyc_tier' => 'tier_1_basic']);

        $result = $this->authService->canPerformAction($this->user, 'transaction', 'view');

        $this->assertFalse($result);
    }
}
