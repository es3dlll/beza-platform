# 18 - اختبارات التدقيق (Testing Complete)

## Feature Tests

```php
<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private string $adminToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->adminToken = JWTAuth::fromUser($this->admin);
    }

    /** @test */
    public function it_logs_login_event()
    {
        $user = User::factory()->create(['password' => Hash::make('password')]);

        $this->postJson('/api/v1/auth/login', [
            'phone' => $user->phone,
            'password' => 'password',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'login',
            'user_id' => $user->id,
        ]);
    }

    /** @test */
    public function it_logs_transfer_event()
    {
        $sender = User::factory()->create(['pin_code' => Hash::make('1234')]);
        Wallet::factory()->create(['user_id' => $sender->id, 'currency' => 'USD', 'balance' => 1000]);
        Wallet::factory()->create(['user_id' => $sender->id, 'currency' => 'SYP', 'balance' => 0]);

        $token = JWTAuth::fromUser($sender);

        $this->withToken($token)
            ->postJson('/api/v1/transfer', [
                'to_phone' => '963900000002',
                'amount' => 100,
                'currency' => 'USD',
                'pin' => '1234',
            ]);

        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'transfer_created',
            'user_id' => $sender->id,
        ]);
    }

    /** @test */
    public function it_lists_audit_logs_for_admin()
    {
        AuditLog::factory()->count(5)->create();

        $response = $this->withToken($this->adminToken)
            ->getJson('/api/v1/admin/audit-logs');

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data']);
    }

    /** @test */
    public function it_filters_audit_logs_by_event_type()
    {
        AuditLog::factory()->create(['event_type' => 'login']);
        AuditLog::factory()->create(['event_type' => 'transfer_created']);

        $response = $this->withToken($this->adminToken)
            ->getJson('/api/v1/admin/audit-logs?event_type=login');

        $response->assertStatus(200);
        $this->assertCount(1, $response['data']);
    }

    /** @test */
    public function it_prevents_non_admin_from_viewing_logs()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withToken($token)
            ->getJson('/api/v1/admin/audit-logs');

        $response->assertStatus(403);
    }

    /** @test */
    public function it_shows_audit_log_stats()
    {
        AuditLog::factory()->count(10)->create(['event_type' => 'login']);
        AuditLog::factory()->count(5)->create(['event_type' => 'transfer_created']);

        $response = $this->withToken($this->adminToken)
            ->getJson('/api/v1/admin/audit-logs/stats/summary');

        $response->assertStatus(200);
        $this->assertEquals(15, $response['data']['total']);
    }
}
```

## Factory

```php
class AuditLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'event_type' => $this->faker->randomElement([
                'login', 'transfer_created', 'pin_changed',
                'kyc_verified', 'admin_action',
            ]),
            'user_id' => User::factory(),
            'data' => ['key' => 'value'],
            'ip' => $this->faker->ipv4,
            'user_agent' => $this->faker->userAgent,
        ];
    }
}
```

## تشغيل الاختبارات

```bash
php artisan test --filter=AuditLogTest
```
