# 18 - الاختبارات

```php
<?php
namespace Tests\Feature\Merchant;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_subscription() {
        $merchant = Merchant::factory()->create();
        $customer = User::factory()->create(['status' => 'active']);
        $token = JWTAuth::fromUser($merchant->user);

        $response = $this->withToken($token)->postJson('/api/v1/merchant/subscriptions', [
            'customer_phone' => $customer->phone,
            'amount' => 100, 'currency' => 'USD',
            'interval' => 'monthly', 'max_cycles' => 12,
        ]);
        $response->assertStatus(201);
        $this->assertDatabaseHas('merchant_subscriptions', ['amount' => 100, 'status' => 'pending']);
    }

    /** @test */
    public function it_requires_customer_consent() {
        $response = $this->postJson('/api/v1/merchant/subscriptions', []);
        $response->assertStatus(401);
    }
}
```
