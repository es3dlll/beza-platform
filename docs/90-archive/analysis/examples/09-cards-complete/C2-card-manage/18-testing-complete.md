# 18 - الاختبارات (Testing)

```php
<?php
namespace Tests\Feature\Card;
use App\Models\Card;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class CardManageTest extends TestCase
{
    use RefreshDatabase;
    private User $user;
    private Card $card;
    private string $token;

    protected function setUp(): void {
        parent::setUp();
        $this->user = User::factory()->create(['status' => 'active']);
        $this->card = Card::factory()->create(['user_id' => $this->user->id, 'status' => 'active', 'daily_limit' => 100000]);
        $this->token = JWTAuth::fromUser($this->user);
    }

    /** @test */
    public function it_updates_card_limit_successfully() {
        $response = $this->withToken($this->token)->putJson("/api/v1/cards/{$this->card->id}/limit", [
            'daily_limit' => 200000,
        ]);
        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertDatabaseHas('cards', ['id' => $this->card->id, 'daily_limit' => 200000]);
    }

    /** @test */
    public function it_blocks_card_successfully() {
        $response = $this->withToken($this->token)->postJson("/api/v1/cards/{$this->card->id}/block");
        $response->assertStatus(200);
        $this->assertDatabaseHas('cards', ['id' => $this->card->id, 'status' => 'blocked']);
    }

    /** @test */
    public function it_unblocks_card_successfully() {
        $this->card->update(['status' => 'blocked']);
        $response = $this->withToken($this->token)->postJson("/api/v1/cards/{$this->card->id}/unblock");
        $response->assertStatus(200);
        $this->assertDatabaseHas('cards', ['id' => $this->card->id, 'status' => 'active']);
    }

    /** @test */
    public function it_requires_authentication() {
        $response = $this->putJson("/api/v1/cards/{$this->card->id}/limit", []);
        $response->assertStatus(401);
    }

    /** @test */
    public function it_prevents_updating_another_users_card() {
        $otherUser = User::factory()->create();
        $otherCard = Card::factory()->create(['user_id' => $otherUser->id]);
        $response = $this->withToken($this->token)->putJson("/api/v1/cards/{$otherCard->id}/limit", ['daily_limit' => 50000]);
        $response->assertStatus(403);
    }

    /** @test */
    public function it_returns_card_transactions_history() {
        $response = $this->withToken($this->token)->getJson("/api/v1/cards/{$this->card->id}/transactions");
        $response->assertStatus(200)->assertJsonStructure(['data']);
    }

    /** @test */
    public function it_rejects_invalid_limit_value() {
        $response = $this->withToken($this->token)->putJson("/api/v1/cards/{$this->card->id}/limit", [
            'daily_limit' => -100,
        ]);
        $response->assertStatus(422);
    }

    /** @test */
    public function it_reports_lost_card_successfully() {
        $response = $this->withToken($this->token)->postJson("/api/v1/cards/{$this->card->id}/report-lost");
        $response->assertStatus(200);
        $this->assertDatabaseHas('cards', ['id' => $this->card->id, 'status' => 'lost']);
    }
}
```

## تشغيل الاختبارات
```bash
php artisan test --filter=CardManageTest
```
