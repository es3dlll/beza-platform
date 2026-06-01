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

class IssueCardTest extends TestCase
{
    use RefreshDatabase;
    private User $user;
    private Wallet $wallet;
    private string $token;

    protected function setUp(): void {
        parent::setUp();
        $this->user = User::factory()->create(['status' => 'active']);
        $this->wallet = Wallet::factory()->create(['user_id' => $this->user->id, 'balance' => 50000]);
        $this->token = JWTAuth::fromUser($this->user);
    }

    /** @test */
    public function it_issues_a_virtual_card_successfully() {
        $response = $this->withToken($this->token)->postJson('/api/v1/cards/issue', [
            'type' => 'virtual', 'currency' => 'SYP', 'daily_limit' => 50000,
        ]);
        $response->assertStatus(201)->assertJson(['success' => true]);
        $this->assertDatabaseHas('cards', ['user_id' => $this->user->id, 'type' => 'virtual', 'status' => 'active']);
    }

    /** @test */
    public function it_requires_authentication() {
        $response = $this->postJson('/api/v1/cards/issue', []);
        $response->assertStatus(401);
    }

    /** @test */
    public function it_validates_card_type() {
        $response = $this->withToken($this->token)->postJson('/api/v1/cards/issue', ['type' => 'invalid']);
        $response->assertStatus(422)->assertJsonValidationErrors(['type']);
    }

    /** @test */
    public function it_rejects_duplicate_virtual_card() {
        Card::factory()->create(['user_id' => $this->user->id, 'type' => 'virtual', 'status' => 'active']);
        $response = $this->withToken($this->token)->postJson('/api/v1/cards/issue', [
            'type' => 'virtual', 'currency' => 'SYP',
        ]);
        $response->assertStatus(409);
    }

    /** @test */
    public function it_creates_card_with_correct_hidden_pan() {
        $response = $this->withToken($this->token)->postJson('/api/v1/cards/issue', [
            'type' => 'virtual', 'currency' => 'SYP',
        ]);
        $response->assertStatus(201);
        $pan = $response->json('data.pan');
        $this->assertStringEndsWith('****', substr($pan, -4));
    }

    /** @test */
    public function it_orders_physical_card_with_shipping() {
        $response = $this->withToken($this->token)->postJson('/api/v1/cards/issue', [
            'type' => 'physical', 'currency' => 'SYP',
            'shipping_address' => 'دمشق, سوريا',
        ]);
        $response->assertStatus(201);
        $this->assertDatabaseHas('cards', ['type' => 'physical', 'status' => 'pending']);
    }

    /** @test */
    public function it_fails_when_wallet_insufficient() {
        $this->wallet->update(['balance' => 0]);
        $response = $this->withToken($this->token)->postJson('/api/v1/cards/issue', [
            'type' => 'physical', 'currency' => 'SYP',
        ]);
        $response->assertStatus(400);
    }

    /** @test */
    public function it_returns_card_details_with_masked_data() {
        Card::factory()->create(['user_id' => $this->user->id, 'type' => 'virtual', 'status' => 'active']);
        $response = $this->withToken($this->token)->getJson('/api/v1/cards');
        $response->assertStatus(200)->assertJsonStructure(['data' => [['id', 'masked_pan', 'type', 'status']]]);
    }
}
```

## تشغيل الاختبارات
```bash
php artisan test --filter=IssueCardTest
```
