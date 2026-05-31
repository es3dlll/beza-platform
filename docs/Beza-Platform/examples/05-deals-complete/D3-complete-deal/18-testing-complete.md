# 18 - كل الاختبارات (Testing Complete)

## Feature Test — CompleteDealTest

```php
<?php
// tests/Feature/CompleteDealTest.php

namespace Tests\Feature;

use App\Models\Deal;
use App\Models\DealInvestment;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class CompleteDealTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Deal $deal;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->token = JWTAuth::fromUser($this->admin);

        $this->deal = Deal::factory()->create([
            'status' => 'active',
            'target_amount' => 10000,
            'current_amount' => 10000,
            'currency' => 'USD',
        ]);

        // مستثمران
        $investor1 = User::factory()->create();
        Wallet::factory()->create(['user_id' => $investor1->id, 'currency' => 'USD', 'balance' => 0]);
        DealInvestment::factory()->create([
            'deal_id' => $this->deal->id,
            'investor_id' => $investor1->id,
            'amount' => 6000,
            'currency' => 'USD',
            'status' => 'active',
        ]);

        $investor2 = User::factory()->create();
        Wallet::factory()->create(['user_id' => $investor2->id, 'currency' => 'USD', 'balance' => 0]);
        DealInvestment::factory()->create([
            'deal_id' => $this->deal->id,
            'investor_id' => $investor2->id,
            'amount' => 4000,
            'currency' => 'USD',
            'status' => 'active',
        ]);
    }

    /** @test */
    public function admin_can_complete_deal()
    {
        $response = $this->withToken($this->token)->postJson(
            "/api/v1/admin/deals/{$this->deal->id}/complete",
            ['profit_actual' => 20]
        );

        $response->assertStatus(200);
        $this->assertEquals('completed', $this->deal->fresh()->status);
    }

    /** @test */
    public function profit_is_distributed_correctly()
    {
        $this->withToken($this->token)->postJson(
            "/api/v1/admin/deals/{$this->deal->id}/complete",
            ['profit_actual' => 10] // 10% = 1000 USD total profit
        );

        // investor1: 6000/10000 × 1000 = 600
        // investor2: 4000/10000 × 1000 = 400
        $investments = $this->deal->investments()->with('investor.wallets')->get();

        foreach ($investments as $inv) {
            $this->assertEquals('completed', $inv->fresh()->status);
            $this->assertNotNull($inv->fresh()->profit_earned);
        }
    }

    /** @test */
    public function non_admin_cannot_complete_deal()
    {
        $user = User::factory()->create(['is_admin' => false]);
        $token = JWTAuth::fromUser($user);

        $response = $this->withToken($token)->postJson(
            "/api/v1/admin/deals/{$this->deal->id}/complete",
            ['profit_actual' => 10]
        );

        $response->assertStatus(403);
    }
}
```
