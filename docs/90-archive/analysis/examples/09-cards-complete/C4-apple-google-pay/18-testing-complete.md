# 18 - الاختبارات (Testing)

```php
<?php
namespace Tests\Feature\Card;
use App\Models\Card;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class WalletPayTest extends TestCase
{
    use RefreshDatabase;
    private User $user;
    private Wallet $wallet;
    private Card $card;
    private string $token;

    protected function setUp(): void {
        parent::setUp();
        $this->user = User::factory()->create(['status' => 'active']);
        $this->wallet = Wallet::factory()->create(['user_id' => $this->user->id, 'balance' => 100000]);
        $this->card = Card::factory()->create(['user_id' => $this->user->id, 'status' => 'active', 'daily_limit' => 50000]);
        $this->token = JWTAuth::fromUser($this->user);
    }

    /** @test */
    public function it_provisions_apple_pay_credentials() {
        $response = $this->withToken($this->token)->postJson("/api/v1/cards/{$this->card->id}/wallet/apple-pay/provision");
        $response->assertStatus(201)->assertJsonStructure(['data' => ['dpan', 'token']]);
    }

    /** @test */
    public function it_provisions_google_pay_credentials() {
        $response = $this->withToken($this->token)->postJson("/api/v1/cards/{$this->card->id}/wallet/google-pay/provision");
        $response->assertStatus(201)->assertJsonStructure(['data' => ['dpan', 'token']]);
    }

    /** @test */
    public function it_passes_wallet_pay_transaction() {
        $provision = $this->withToken($this->token)->postJson("/api/v1/cards/{$this->card->id}/wallet/apple-pay/provision");
        $dpan = $provision->json('data.dpan');
        $response = $this->postJson('/api/v1/wallet-pay/charge', [
            'dpan' => $dpan, 'amount' => 10000, 'merchant_id' => 1,
        ]);
        $response->assertStatus(200)->assertJson(['success' => true]);
    }

    /** @test */
    public function it_requires_authentication_for_provisioning() {
        $response = $this->postJson("/api/v1/cards/{$this->card->id}/wallet/apple-pay/provision");
        $response->assertStatus(401);
    }

    /** @test */
    public function it_rejects_charge_with_invalid_dpan() {
        $response = $this->postJson('/api/v1/wallet-pay/charge', [
            'dpan' => 'invalid_dpan', 'amount' => 10000, 'merchant_id' => 1,
        ]);
        $response->assertStatus(422);
    }

    /** @test */
    public function it_rejects_charge_exceeding_daily_limit() {
        $provision = $this->withToken($this->token)->postJson("/api/v1/cards/{$this->card->id}/wallet/apple-pay/provision");
        $response = $this->postJson('/api/v1/wallet-pay/charge', [
            'dpan' => $provision->json('data.dpan'), 'amount' => 200000, 'merchant_id' => 1,
        ]);
        $response->assertStatus(400);
    }

    /** @test */
    public function it_removes_wallet_pay_credentials_on_card_block() {
        $response = $this->withToken($this->token)->postJson("/api/v1/cards/{$this->card->id}/block");
        $response->assertStatus(200);
        $this->assertDatabaseMissing('wallet_pay_tokens', ['card_id' => $this->card->id, 'status' => 'active']);
    }

    /** @test */
    public function it_returns_provisioned_devices() {
        $this->withToken($this->token)->postJson("/api/v1/cards/{$this->card->id}/wallet/apple-pay/provision");
        $response = $this->withToken($this->token)->getJson("/api/v1/cards/{$this->card->id}/wallet/devices");
        $response->assertStatus(200)->assertJsonStructure(['data' => [['device_id', 'wallet_type', 'provisioned_at']]]);
    }
}
```

## تشغيل الاختبارات
```bash
php artisan test --filter=WalletPayTest
```
