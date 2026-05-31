# 18 - كل الاختبارات (Testing Complete)

```php
<?php
namespace Tests\Feature\Agent;
use App\Models\Agent;
use App\Models\AgentSettlement;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AgentSettlementTest extends TestCase
{
    use RefreshDatabase;
    private User $user;
    private Agent $agent;
    private Wallet $wallet;
    private string $token;

    protected function setUp(): void {
        parent::setUp();
        $this->user = User::factory()->create(['status' => 'active', 'role' => 'agent']);
        $this->agent = Agent::factory()->create(['user_id' => $this->user->id, 'status' => 'approved']);
        $this->wallet = Wallet::factory()->create(['user_id' => $this->user->id, 'balance' => 500000]);
        $this->token = JWTAuth::fromUser($this->user);
    }

    /** @test */
    public function it_requests_settlement_successfully() {
        $response = $this->withToken($this->token)->postJson('/api/v1/agent/settlements', [
            'amount' => 100000, 'bank_account_id' => 1,
        ]);
        $response->assertStatus(201);
        $this->assertDatabaseHas('agent_settlements', ['agent_id' => $this->agent->id, 'status' => 'pending']);
    }

    /** @test */
    public function it_requires_authentication() {
        $response = $this->postJson('/api/v1/agent/settlements', []);
        $response->assertStatus(401);
    }

    /** @test */
    public function it_rejects_settlement_exceeding_balance() {
        $response = $this->withToken($this->token)->postJson('/api/v1/agent/settlements', [
            'amount' => 999999999, 'bank_account_id' => 1,
        ]);
        $response->assertStatus(400);
    }

    /** @test */
    public function it_returns_settlement_history() {
        AgentSettlement::factory()->count(5)->create(['agent_id' => $this->agent->id]);
        $response = $this->withToken($this->token)->getJson('/api/v1/agent/settlements');
        $response->assertStatus(200)->assertJsonCount(5, 'data');
    }

    /** @test */
    public function it_approves_settlement_as_admin() {
        $settlement = AgentSettlement::factory()->create(['agent_id' => $this->agent->id, 'status' => 'pending']);
        $admin = User::factory()->create(['role' => 'admin']);
        $adminToken = JWTAuth::fromUser($admin);
        $response = $this->withToken($adminToken)->postJson("/api/v1/admin/agent-settlements/{$settlement->id}/approve");
        $response->assertStatus(200);
        $this->assertDatabaseHas('agent_settlements', ['id' => $settlement->id, 'status' => 'approved']);
    }

    /** @test */
    public function it_rejects_settlement_below_minimum() {
        $response = $this->withToken($this->token)->postJson('/api/v1/agent/settlements', [
            'amount' => 500, 'bank_account_id' => 1,
        ]);
        $response->assertStatus(422);
    }

    /** @test */
    public function it_cancels_pending_settlement() {
        $settlement = AgentSettlement::factory()->create(['agent_id' => $this->agent->id, 'status' => 'pending']);
        $response = $this->withToken($this->token)->postJson("/api/v1/agent/settlements/{$settlement->id}/cancel");
        $response->assertStatus(200);
        $this->assertDatabaseHas('agent_settlements', ['id' => $settlement->id, 'status' => 'cancelled']);
    }

    /** @test */
    public function it_prevents_cancelling_approved_settlement() {
        $settlement = AgentSettlement::factory()->create(['agent_id' => $this->agent->id, 'status' => 'approved']);
        $response = $this->withToken($this->token)->postJson("/api/v1/agent/settlements/{$settlement->id}/cancel");
        $response->assertStatus(400);
    }
}
```

## تشغيل الاختبارات
```bash
php artisan test --filter=AgentSettlementTest
```
