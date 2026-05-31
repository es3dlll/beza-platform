# 18 - كل الاختبارات (Testing)

## ReportTest

```php
<?php
// tests/Feature/Admin/ReportTest.php

namespace Tests\Feature\Admin;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->token = JWTAuth::fromUser($this->admin);
    }

    /** @test */
    public function admin_can_view_daily_report()
    {
        $response = $this->withToken($this->token)
            ->getJson('/api/v1/admin/reports/daily');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success', 'data' => [
                    'date', 'total_transactions', 'total_volume',
                    'total_fees', 'new_users', 'active_users',
                ],
            ]);
    }

    /** @test */
    public function admin_can_view_daily_report_with_date()
    {
        $response = $this->withToken($this->token)
            ->getJson('/api/v1/admin/reports/daily?date=2026-05-27');

        $response->assertStatus(200)
            ->assertJsonPath('data.date', '2026-05-27');
    }

    /** @test */
    public function daily_report_returns_zero_for_empty_days()
    {
        $response = $this->withToken($this->token)
            ->getJson('/api/v1/admin/reports/daily?date=2025-01-01');

        $response->assertStatus(200)
            ->assertJsonPath('data.total_transactions', 0)
            ->assertJsonPath('data.total_volume', 0);
    }

    /** @test */
    public function admin_can_view_monthly_report()
    {
        $response = $this->withToken($this->token)
            ->getJson('/api/v1/admin/reports/monthly?year=2026&month=5');

        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_view_financial_report()
    {
        $response = $this->withToken($this->token)
            ->getJson('/api/v1/admin/reports/financial?from=2026-01-01&to=2026-05-27');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success', 'data' => [
                    'period', 'revenue', 'costs', 'profit_loss',
                ],
            ]);
    }

    /** @test */
    public function non_admin_gets_403()
    {
        $user = User::factory()->create(['is_admin' => false]);
        $userToken = JWTAuth::fromUser($user);

        $response = $this->withToken($userToken)
            ->getJson('/api/v1/admin/reports/daily');

        $response->assertStatus(403);
    }
}
```

## تشغيل الاختبارات

```bash
php artisan test --filter=ReportTest
```
