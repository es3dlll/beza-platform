# 18 - الاختبارات (Testing)

```php
<?php
namespace Tests\Feature\Merchant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class MerchantRegistrationTest extends TestCase
{
    use RefreshDatabase;
    private User $user;
    private string $token;

    protected function setUp(): void {
        parent::setUp();
        $this->user = User::factory()->create(['status' => 'active']);
        $this->token = JWTAuth::fromUser($this->user);
    }

    /** @test */
    public function it_registers_a_merchant_successfully() {
        $response = $this->withToken($this->token)->post('/api/v1/merchant/register', [
            'business_name' => 'متجر اختبار',
            'business_type' => 'electronics',
            'commercial_registration' => 'CR123456',
            'tax_id' => 'TX123456',
            'owner_phone' => '963944123456',
            'owner_name' => 'أحمد',
            'bank_account_info' => ['bank_name' => 'اختبار', 'account_number' => '123', 'iban' => 'SY123'],
        ]);
        $response->assertStatus(201)->assertJson(['success' => true]);
        $this->assertDatabaseHas('merchants', ['business_name' => 'متجر اختبار']);
    }

    /** @test */
    public function it_requires_authentication() {
        $response = $this->postJson('/api/v1/merchant/register', []);
        $response->assertStatus(401);
    }
}
```
