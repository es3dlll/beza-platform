# 18 - كل الاختبارات (Testing Complete)

```php
<?php
namespace Tests\Feature\Agent;
use App\Models\Agent;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AgentDashboardTest extends TestCase
{
    use RefreshDatabase;
    private User $user;
    private Agent $agent;
    private Wallet $wallet;
    private string $token;

    protected function setUp(): void {
        parent::setUp();
        $this->user = User::factory()->create(['status' => 'active']);
        $this->agent = Agent::factory()->create(['user_id' => $this->user->id, 'status' => 'approved']);
        $this->wallet = Wallet::factory()->create(['user_id' => $this->user->id, 'balance' => 200000]);
        Transaction::factory()->count(10)->create(['agent_id' => $this->agent->id, 'user_id' => $this->user->id]);
        $this->token = JWTAuth::fromUser($this->user);
    }

    /** @test */
    public function it_returns_dashboard_stats() {
        $response = $this->withToken($this->token)->getJson('/api/v1/agent/dashboard');
        $response->assertStatus(200)->assertJsonStructure([
            'total_transactions', 'total_volume', 'commission_earned', 'today_count',
        ]);
    }

    /** @test */
    public function it_requires_agent_role() {
        $regularUser = User::factory()->create(['role' => 'user']);
        $regularToken = JWTAuth::fromUser($regularUser);
        $response = $this->withToken($regularToken)->getJson('/api/v1/agent/dashboard');
        $response->assertStatus(403);
    }

    /** @test */
    public function it_returns_recent_activities() {
        $response = $this->withToken($this->token)->getJson('/api/v1/agent/dashboard/activities');
        $response->assertStatus(200)->assertJsonStructure(['data' => [['id', 'type', 'amount', 'created_at']]]);
    }

    /** @test */
    public function it_returns_daily_performance_chart() {
        $response = $this->withToken($this->token)->getJson('/api/v1/agent/dashboard/chart/daily');
        $response->assertStatus(200)->assertJsonStructure(['labels', 'values']);
    }

    /** @test */
    public function it_requires_authentication() {
        $response = $this->getJson('/api/v1/agent/dashboard');
        $response->assertStatus(401);
    }

    /** @test */
    public function it_returns_agent_profile() {
        $response = $this->withToken($this->token)->getJson('/api/v1/agent/profile');
        $response->assertStatus(200)->assertJsonStructure(['data' => ['id', 'full_name', 'status', 'rating']]);
    }

    /** @test */
    public function it_updates_agent_availability() {
        $response = $this->withToken($this->token)->putJson('/api/v1/agent/availability', ['available' => false]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('agents', ['id' => $this->agent->id, 'available' => false]);
    }

    /** @test */
    public function it_returns_agent_commission_history() {
        $response = $this->withToken($this->token)->getJson('/api/v1/agent/commissions');
        $response->assertStatus(200)->assertJsonStructure(['data', 'total', 'pending']);
    }
}
```

## تشغيل الاختبارات
```bash
php artisan test --filter=AgentDashboardTest
```
