# 18 - الاختبارات

```php
<?php
namespace Tests\Feature\Merchant;
use App\Models\Merchant;
use App\Models\MerchantWallet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class SettlementTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_requests_settlement() {
        \$merchant = Merchant::factory()->create();
        MerchantWallet::factory()->create(['merchant_id' => \$merchant->id, 'currency' => 'USD', 'balance' => 1000]);
        \$token = JWTAuth::fromUser(\$merchant->user);

        \$response = \$this->withToken(\$token)->postJson('/api/v1/merchant/settlement', [
            'currency' => 'USD',
        ]);
        \$response->assertStatus(201)->assertJson(['success' => true]);
    }

    /** @test */
    public function it_rejects_below_minimum() {
        \$merchant = Merchant::factory()->create();
        MerchantWallet::factory()->create(['merchant_id' => \$merchant->id, 'currency' => 'USD', 'balance' => 10]);
        \$token = JWTAuth::fromUser(\$merchant->user);

        \$response = \$this->withToken(\$token)->postJson('/api/v1/merchant/settlement', [
            'currency' => 'USD',
        ]);
        \$response->assertStatus(422);
    }

    /** @test */
    public function it_requires_authentication() {
        \$this->postJson('/api/v1/merchant/settlement', [])->assertStatus(401);
    }
}
```
