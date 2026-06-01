# 18 - كل الاختبارات (Testing Complete)

## Feature Test — InvestDealTest

```php
<?php
// tests/Feature/InvestDealTest.php

namespace Tests\Feature;

use App\Models\Deal;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class InvestDealTest extends TestCase
{
    use RefreshDatabase;

    private User $investor;
    private Deal $deal;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->investor = User::factory()->create(['status' => 'active']);
        Wallet::factory()->create([
            'user_id' => $this->investor->id,
            'currency' => 'USD',
            'balance' => 5000,
        ]);

        $this->deal = Deal::factory()->create([
            'status' => 'active',
            'target_amount' => 10000,
            'current_amount' => 0,
            'currency' => 'USD',
        ]);

        $this->token = JWTAuth::fromUser($this->investor);
    }

    /** @test */
    public function user_can_invest_in_deal()
    {
        $response = $this->withToken($this->token)->postJson(
            "/api/v1/deals/{$this->deal->id}/invest",
            ['amount' => 1000]
        );

        $response->assertStatus(201);
        $this->assertDatabaseHas('deal_investments', [
            'deal_id' => $this->deal->id,
            'investor_id' => $this->investor->id,
            'amount' => 1000,
        ]);
    }

    /** @test */
    public function fails_when_deal_not_active()
    {
        $this->deal->update(['status' => 'pending']);

        $response = $this->withToken($this->token)->postJson(
            "/api/v1/deals/{$this->deal->id}/invest",
            ['amount' => 1000]
        );

        $response->assertStatus(422);
    }

    /** @test */
    public function fails_with_insufficient_balance()
    {
        $response = $this->withToken($this->token)->postJson(
            "/api/v1/deals/{$this->deal->id}/invest",
            ['amount' => 999999]
        );

        $response->assertStatus(422);
    }

    /** @test */
    public function fails_when_amount_exceeds_remaining()
    {
        $this->deal->update(['current_amount' => 9500]);

        $response = $this->withToken($this->token)->postJson(
            "/api/v1/deals/{$this->deal->id}/invest",
            ['amount' => 1000] // 9500 + 1000 > 10000
        );

        $response->assertStatus(422);
    }

    /** @test */
    public function fills_deal_when_fully_funded()
    {
        $this->deal->update(['current_amount' => 9000]);

        $this->withToken($this->token)->postJson(
            "/api/v1/deals/{$this->deal->id}/invest",
            ['amount' => 1000]
        );

        $this->assertEquals('filled', $this->deal->fresh()->status);
    }
}
```
