# 18 - كل الاختبارات (Testing)

## Feature Test — UserManagementTest

```php
<?php
// tests/Feature/Admin/UserManagementTest.php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $regularUser;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'is_admin' => true,
            'status'   => 'active',
        ]);

        $this->regularUser = User::factory()->create([
            'status' => 'active',
        ]);

        Wallet::factory()->create([
            'user_id' => $this->regularUser->id,
            'currency' => 'USD',
            'balance' => 500,
        ]);

        $this->token = JWTAuth::fromUser($this->admin);
    }

    /** @test */
    public function admin_can_list_users()
    {
        User::factory()->count(25)->create();

        $response = $this->withToken($this->token)
            ->getJson('/api/v1/admin/users?per_page=10');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success', 'data', 'meta' => ['current_page', 'last_page', 'total']
            ]);

        $this->assertEquals(10, count($response['data']));
    }

    /** @test */
    public function admin_can_search_users()
    {
        User::factory()->create(['name' => 'أحمد محمد', 'phone' => '963944123456']);

        $response = $this->withToken($this->token)
            ->getJson('/api/v1/admin/users?search=أحمد');

        $response->assertStatus(200);
        $this->assertGreaterThan(0, count($response['data']));
    }

    /** @test */
    public function admin_can_view_user_details()
    {
        $response = $this->withToken($this->token)
            ->getJson("/api/v1/admin/users/{$this->regularUser->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $this->regularUser->id)
            ->assertJsonPath('data.name', $this->regularUser->name);
    }

    /** @test */
    public function admin_can_suspend_user()
    {
        $response = $this->withToken($this->token)
            ->putJson("/api/v1/admin/users/{$this->regularUser->id}/suspend");

        $response->assertStatus(200)
            ->assertJson(['message' => 'تم تعليق المستخدم']);

        $this->assertEquals('suspended', $this->regularUser->fresh()->status);
    }

    /** @test */
    public function admin_cannot_suspend_self()
    {
        $response = $this->withToken($this->token)
            ->putJson("/api/v1/admin/users/{$this->admin->id}/block");

        $response->assertStatus(422);
    }

    /** @test */
    public function admin_cannot_suspend_another_admin()
    {
        $anotherAdmin = User::factory()->create(['is_admin' => true]);

        $response = $this->withToken($this->token)
            ->putJson("/api/v1/admin/users/{$anotherAdmin->id}/suspend");

        $response->assertStatus(422);
    }

    /** @test */
    public function admin_can_activate_suspended_user()
    {
        $this->regularUser->suspend();

        $response = $this->withToken($this->token)
            ->putJson("/api/v1/admin/users/{$this->regularUser->id}/activate");

        $response->assertStatus(200);
        $this->assertEquals('active', $this->regularUser->fresh()->status);
    }

    /** @test */
    public function non_admin_gets_403()
    {
        $user = User::factory()->create(['is_admin' => false]);
        $userToken = JWTAuth::fromUser($user);

        $response = $this->withToken($userToken)
            ->getJson('/api/v1/admin/users');

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_soft_delete_user()
    {
        $response = $this->withToken($this->token)
            ->deleteJson("/api/v1/admin/users/{$this->regularUser->id}/");

        $response->assertStatus(200);
        $this->assertNotNull($this->regularUser->fresh()->deleted_at);
    }

    /** @test */
    public function admin_cannot_delete_self()
    {
        $response = $this->withToken($this->token)
            ->deleteJson("/api/v1/admin/users/{$this->admin->id}");

        $response->assertStatus(422);
    }
}
```

## تشغيل الاختبارات

```bash
php artisan test --filter=UserManagementTest
```
