# 18 - كل الاختبارات (Testing Complete)

## Feature Test — DashboardTest

```php
<?php
// tests/Feature/Admin/DashboardTest.php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class DashboardTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $admin;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'is_admin' => true,
            'status'   => 'active',
        ]);

        $this->token = JWTAuth::fromUser($this->admin);

        // إنشاء بيانات اختبارية
        User::factory()->count(50)->create(['status' => 'active']);
        User::factory()->count(10)->create(['is_merchant' => true, 'status' => 'active']);
        User::factory()->count(5)->create(['is_agent' => true, 'status' => 'active']);
    }

    /** @test */
    public function admin_can_view_dashboard_stats()
    {
        $response = $this->withToken($this->token)
            ->getJson('/api/v1/admin/dashboard/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'summary' => [
                        'total_users', 'active_users',
                        'merchants_count', 'agents_count',
                    ],
                    'charts',
                    'top_merchants',
                ],
            ]);
    }

    /** @test */
    public function non_admin_cannot_view_dashboard()
    {
        $user = User::factory()->create(['is_admin' => false]);
        $userToken = JWTAuth::fromUser($user);

        $response = $this->withToken($userToken)
            ->getJson('/api/v1/admin/dashboard/stats');

        $response->assertStatus(403);
    }

    /** @test */
    public function unauthenticated_user_gets_401()
    {
        $response = $this->getJson('/api/v1/admin/dashboard/stats');
        $response->assertStatus(401);
    }

    /** @test */
    public function dashboard_uses_cache()
    {
        // الطلب الأول — يخزن في Cache
        $response1 = $this->withToken($this->token)
            ->getJson('/api/v1/admin/dashboard/stats');

        // الطلب الثاني — من Cache
        $response2 = $this->withToken($this->token)
            ->getJson('/api/v1/admin/dashboard/stats');

        $response1->assertStatus(200);
        $response2->assertStatus(200);

        // نفس البيانات (من Cache)
        $this->assertEquals(
            $response1['data']['summary']['total_users'],
            $response2['data']['summary']['total_users']
        );
    }

    /** @test */
    public function admin_can_refresh_dashboard()
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/v1/admin/dashboard/refresh');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'تم تحديث البيانات',
            ]);
    }

    /** @test */
    public function charts_return_last_30_days()
    {
        $response = $this->withToken($this->token)
            ->getJson('/api/v1/admin/dashboard/stats?period=30d');

        $response->assertStatus(200);
        $this->assertNotEmpty($response['data']['charts']['revenue']);
        $this->assertNotEmpty($response['data']['charts']['volume']);
    }
}
```

## تشغيل الاختبارات

```bash
# تشغيل اختبارات لوحة التحكم
php artisan test --filter=DashboardTest

# تشغيل مع تغطية
php artisan test --filter=DashboardTest --coverage
```
