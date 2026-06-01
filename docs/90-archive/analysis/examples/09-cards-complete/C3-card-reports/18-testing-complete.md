# 18 - الاختبارات (Testing)

```php
<?php
namespace Tests\Feature\Card;
use App\Models\Card;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class CardReportTest extends TestCase
{
    use RefreshDatabase;
    private User $user;
    private Card $card;
    private string $token;

    protected function setUp(): void {
        parent::setUp();
        $this->user = User::factory()->create(['status' => 'active']);
        $this->card = Card::factory()->create(['user_id' => $this->user->id, 'status' => 'active']);
        Transaction::factory()->count(15)->create(['card_id' => $this->card->id, 'user_id' => $this->user->id]);
        $this->token = JWTAuth::fromUser($this->user);
    }

    /** @test */
    public function it_returns_card_spending_summary() {
        $response = $this->withToken($this->token)->getJson("/api/v1/cards/{$this->card->id}/reports/summary");
        $response->assertStatus(200)->assertJsonStructure(['total_spent', 'transaction_count', 'period']);
    }

    /** @test */
    public function it_filters_reports_by_date_range() {
        $response = $this->withToken($this->token)->getJson("/api/v1/cards/{$this->card->id}/reports/summary?from=2025-01-01&to=2025-12-31");
        $response->assertStatus(200);
    }

    /** @test */
    public function it_returns_monthly_breakdown() {
        $response = $this->withToken($this->token)->getJson("/api/v1/cards/{$this->card->id}/reports/monthly");
        $response->assertStatus(200)->assertJsonStructure(['data' => [['month', 'total', 'count']]]);
    }

    /** @test */
    public function it_requires_authentication() {
        $response = $this->getJson("/api/v1/cards/{$this->card->id}/reports/summary");
        $response->assertStatus(401);
    }

    /** @test */
    public function it_prevents_accessing_other_users_card_reports() {
        $otherUser = User::factory()->create();
        $otherCard = Card::factory()->create(['user_id' => $otherUser->id]);
        $response = $this->withToken($this->token)->getJson("/api/v1/cards/{$otherCard->id}/reports/summary");
        $response->assertStatus(403);
    }

    /** @test */
    public function it_exports_report_as_csv() {
        $response = $this->withToken($this->token)->getJson("/api/v1/cards/{$this->card->id}/reports/export?format=csv");
        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }

    /** @test */
    public function it_returns_spending_by_category() {
        $response = $this->withToken($this->token)->getJson("/api/v1/cards/{$this->card->id}/reports/by-category");
        $response->assertStatus(200)->assertJsonStructure(['data' => [['category', 'amount', 'percentage']]]);
    }

    /** @test */
    public function it_handles_empty_report_gracefully() {
        $newCard = Card::factory()->create(['user_id' => $this->user->id, 'status' => 'active']);
        $response = $this->withToken($this->token)->getJson("/api/v1/cards/{$newCard->id}/reports/summary");
        $response->assertStatus(200);
        $this->assertEquals(0, $response->json('total_spent'));
    }
}
```

## تشغيل الاختبارات
```bash
php artisan test --filter=CardReportTest
```
