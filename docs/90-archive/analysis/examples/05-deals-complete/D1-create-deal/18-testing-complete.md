# 18 - كل الاختبارات (Testing Complete)

## Feature Test — AdminDealTest

```php
<?php
// tests/Feature/AdminDealTest.php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Deal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AdminDealTest extends TestCase
{
    use RefreshDatabase;

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
    }

    /** @test */
    public function admin_can_create_deal()
    {
        $response = $this->withToken($this->token)->postJson('/api/v1/admin/deals', [
            'title'                       => 'تجارة شحنات',
            'description'                 => 'وصف',
            'target_amount'               => 50000,
            'currency'                    => 'USD',
            'expected_profit_percentage'  => 15,
            'duration_days'               => 90,
            'category'                    => 'trade',
            'risk_level'                  => 'medium',
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('deals', [
            'title' => 'تجارة شحنات',
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function non_admin_cannot_create_deal()
    {
        $user = User::factory()->create(['is_admin' => false]);
        $token = JWTAuth::fromUser($user);

        $response = $this->withToken($token)->postJson('/api/v1/admin/deals', [
            'title' => 'test',
            'target_amount' => 1000,
            'currency' => 'USD',
            'expected_profit_percentage' => 10,
            'duration_days' => 30,
            'category' => 'trade',
            'risk_level' => 'low',
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function validates_required_fields()
    {
        $response = $this->withToken($this->token)->postJson('/api/v1/admin/deals', []);
        $response->assertStatus(422);
    }
}
```
