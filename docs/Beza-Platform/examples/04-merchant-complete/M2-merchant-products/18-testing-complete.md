# 18 - الاختبارات (Testing)

```php
<?php
namespace Tests\Feature\Merchant;
use App\Models\Merchant;
use App\Models\MerchantProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class ProductTest extends TestCase
{
    use RefreshDatabase;
    private Merchant $merchant;
    private string $token;

    protected function setUp(): void {
        parent::setUp();
        $user = User::factory()->create();
        $this->merchant = Merchant::factory()->create(['user_id' => $user->id]);
        $this->token = JWTAuth::fromUser($user);
    }

    /** @test */
    public function it_creates_a_product() {
        $response = $this->withToken($this->token)->postJson('/api/v1/merchant/products', [
            'name' => 'منتج تجريبي', 'price_syp' => 100000, 'price_usd' => 10,
        ]);
        $response->assertStatus(201)->assertJson(['success' => true]);
        $this->assertDatabaseHas('merchant_products', ['name' => 'منتج تجريبي']);
    }

    /** @test */
    public function it_lists_products() {
        MerchantProduct::factory()->count(3)->create(['merchant_id' => $this->merchant->id]);
        $response = $this->withToken($this->token)->getJson('/api/v1/merchant/products');
        $response->assertStatus(200);
        $this->assertCount(3, $response['data']);
    }

    /** @test */
    public function it_requires_authentication() {
        $this->postJson('/api/v1/merchant/products', [])->assertStatus(401);
    }
}
```
