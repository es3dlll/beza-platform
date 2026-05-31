# 18 - كل الاختبارات (Testing Complete)

## Feature Test — CancelDealTest

```php
<?php
// tests/Feature/CancelDealTest.php

namespace Tests\Feature;

use App\Models\Deal;
use App\Models\DealInvestment;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class CancelDealTest extends TestCase
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
            'current_amount' => 6000,
            'currency' => 'USD',
        ]);

        $investor = User::factory()->create();
        Wallet::factory()->create([
            'user_id' => $investor->id,
            'currency' => 'USD',
            'balance' => 0,
        ]);
        DealInvestment::factory()->create([
            'deal_id' => $this->deal->id,
            'investor_id' => $investor->id,
            'amount' => 6000,
            'currency' => 'USD',
            'status' => 'active',
        ]);
    }

    /** @test */
    public function admin_can_cancel_deal()
    {
        $response = $this->withToken($this->token)->postJson(
            "/api/v1/admin/deals/{$this->deal->id}/cancel",
            ['reason' => 'مشاكل لوجستية تمنع إتمام الصفقة']
        );

        $response->assertStatus(200);
        $this->assertEquals('cancelled', $this->deal->fresh()->status);
    }

    /** @test */
    public function refunds_are_processed()
    {
        $this->withToken($this->token)->postJson(
            "/api/v1/admin/deals/{$this->deal->id}/cancel",
            ['reason' => 'مشاكل لوجستية']
        );

        // تحقق من وجود معاملات refund
        $this->assertDatabaseHas('transactions', [
            'type' => 'refund',
        ]);

        // تحقق من تحديث حالة الاستثمارات
        $this->assertDatabaseHas('deal_investments', [
            'deal_id' => $this->deal->id,
            'status' => 'refunded',
        ]);
    }

    /** @test */
    public function cannot_cancel_completed_deal()
    {
        $this->deal->update(['status' => 'completed']);

        $response = $this->withToken($this->token)->postJson(
            "/api/v1/admin/deals/{$this->deal->id}/cancel",
            ['reason' => 'سبب ما']
        );

        $response->assertStatus(422);
    }
}
```
