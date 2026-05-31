# 18 - الاختبارات (Testing)

```php
<?php
namespace Tests\Feature\Merchant;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class PaymentLinkTest extends TestCase
{
    use RefreshDatabase;
    private Merchant $merchant;
    private string $token;

    protected function setUp(): void {
        parent::setUp();
        $user = User::factory()->create();
        $this->merchant = Merchant::factory()->create(['user_id' => $user->id]);
        MerchantWallet::factory()->create(['merchant_id' => $this->merchant->id, 'currency' => 'USD', 'balance' => 1000]);
        $this->token = JWTAuth::fromUser($user);
    }

    /** @test */
    public function it_creates_payment_link() {
        $response = $this->withToken($this->token)->postJson('/api/v1/merchant/payment-link', [
            'amount' => 100, 'currency' => 'USD', 'expiry_hours' => 24,
        ]);
        $response->assertStatus(201)->assertJson(['success' => true]);
        $this->assertDatabaseHas('payment_links', ['amount' => 100, 'currency' => 'USD', 'status' => 'active']);
    }

    /** @test */
    public function it_requires_authentication() {
        $this->postJson('/api/v1/merchant/payment-link', [])->assertStatus(401);
    }
}
```
