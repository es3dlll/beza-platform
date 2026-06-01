# 18 - كل الاختبارات (Testing Complete)

## Feature Test — ReferralTest

```php
<?php
// tests/Feature/ReferralTest.php

namespace Tests\Feature;

use App\Models\ReferralCode;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class ReferralTest extends TestCase
{
    use RefreshDatabase;

    private User $referrer;
    private User $referred;
    private string $referrerToken;
    private string $referredToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->referrer = User::factory()->create(['status' => 'active']);
        Wallet::factory()->create(['user_id' => $this->referrer->id, 'currency' => 'USD']);
        $this->referrerToken = JWTAuth::fromUser($this->referrer);

        $this->referred = User::factory()->create(['status' => 'active', 'referred_by' => null]);
        Wallet::factory()->create(['user_id' => $this->referred->id, 'currency' => 'USD']);
        $this->referredToken = JWTAuth::fromUser($this->referred);
    }

    /** @test */
    public function user_can_generate_referral_code()
    {
        $response = $this->withToken($this->referrerToken)
            ->postJson('/api/v1/referral/code');

        $response->assertStatus(200);
        $this->assertDatabaseHas('referral_codes', [
            'user_id' => $this->referrer->id,
        ]);
    }

    /** @test */
    public function returns_existing_code_if_already_generated()
    {
        $code = ReferralCode::factory()->create(['user_id' => $this->referrer->id]);

        $response = $this->withToken($this->referrerToken)
            ->postJson('/api/v1/referral/code');

        $response->assertJson([
            'data' => ['code' => ['code' => $code->code]],
        ]);
    }

    /** @test */
    public function user_can_claim_referral()
    {
        $refCode = ReferralCode::factory()->create(['user_id' => $this->referrer->id]);

        $response = $this->withToken($this->referredToken)
            ->postJson('/api/v1/referral/claim', [
                'code' => $refCode->code,
            ]);

        $response->assertStatus(200);
        $this->assertEquals($this->referrer->id, $this->referred->fresh()->referred_by);
    }

    /** @test */
    public function cannot_claim_self_referral()
    {
        $refCode = ReferralCode::factory()->create(['user_id' => $this->referrer->id]);

        $response = $this->withToken($this->referrerToken)
            ->postJson('/api/v1/referral/claim', [
                'code' => $refCode->code,
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function cannot_claim_twice()
    {
        $refCode = ReferralCode::factory()->create(['user_id' => $this->referrer->id]);
        $this->referred->update(['referred_by' => $this->referrer->id]);

        $response = $this->withToken($this->referredToken)
            ->postJson('/api/v1/referral/claim', [
                'code' => $refCode->code,
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function invalid_code_returns_error()
    {
        $response = $this->withToken($this->referredToken)
            ->postJson('/api/v1/referral/claim', [
                'code' => 'INVALID',
            ]);

        $response->assertStatus(422);
    }
}
```
