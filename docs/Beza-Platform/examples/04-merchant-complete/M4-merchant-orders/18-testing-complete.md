# 18 - الاختبارات

```php
<?php
namespace Tests\Feature\Merchant;
use App\Models\Merchant;
use App\Models\MerchantOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_lists_orders() {
        $merchant = Merchant::factory()->create();
        $user = User::factory()->create();
        MerchantOrder::factory()->count(3)->create(['merchant_id' => $merchant->id]);
        $token = JWTAuth::fromUser($user);

        $response = $this->withToken($token)->getJson('/api/v1/merchant/orders');
        $response->assertStatus(200);
    }

    /** @test */
    public function it_updates_order_status() {
        $order = MerchantOrder::factory()->create(['status' => 'pending']);
        \$this->actingAs($order->merchant->user);

        \$response = \$this->patchJson("/api/v1/merchant/orders/{$order->id}/status", ['status' => 'processing']);
        \$response->assertStatus(200);
        \$this->assertEquals('processing', \$order->fresh()->status);
    }
}
```
