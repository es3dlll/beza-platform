# 18 - كل الاختبارات (Testing Complete)

```php
<?php
namespace Tests\Feature\Agent;
use App\Models\Agent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AgentMapTest extends TestCase
{
    use RefreshDatabase;
    private User $user;
    private Agent $agent;
    private string $token;

    protected function setUp(): void {
        parent::setUp();
        $this->user = User::factory()->create(['status' => 'active']);
        $this->agent = Agent::factory()->create([
            'user_id' => $this->user->id, 'status' => 'approved', 'available' => true,
            'location_lat' => 33.5138, 'location_lng' => 36.2765,
        ]);
        $this->token = JWTAuth::fromUser($this->user);
    }

    /** @test */
    public function it_returns_nearby_agents() {
        $response = $this->getJson('/api/v1/agents/nearby?lat=33.51&lng=36.28&radius=5');
        $response->assertStatus(200)->assertJsonStructure(['data' => [['id', 'full_name', 'distance', 'available']]]);
    }

    /** @test */
    public function it_updates_agent_location() {
        $response = $this->withToken($this->token)->putJson('/api/v1/agent/location', [
            'lat' => 33.5200, 'lng' => 36.2900,
        ]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('agents', ['id' => $this->agent->id, 'location_lat' => 33.5200, 'location_lng' => 36.2900]);
    }

    /** @test */
    public function it_requires_authentication_for_location_update() {
        $response = $this->putJson('/api/v1/agent/location', ['lat' => 33.52, 'lng' => 36.29]);
        $response->assertStatus(401);
    }

    /** @test */
    public function it_filters_nearby_agents_by_availability() {
        Agent::factory()->create(['available' => false, 'location_lat' => 33.51, 'location_lng' => 36.28]);
        $response = $this->getJson('/api/v1/agents/nearby?lat=33.51&lng=36.28&radius=10&available=true');
        $response->assertStatus(200);
        foreach ($response->json('data') as $agent) {
            $this->assertTrue($agent['available']);
        }
    }

    /** @test */
    public function it_returns_empty_when_no_agents_nearby() {
        $response = $this->getJson('/api/v1/agents/nearby?lat=10.00&lng=10.00&radius=1');
        $response->assertStatus(200);
        $this->assertEmpty($response->json('data'));
    }

    /** @test */
    public function it_validates_coordinates() {
        $response = $this->getJson('/api/v1/agents/nearby?lat=invalid&lng=36.28');
        $response->assertStatus(422);
    }

    /** @test */
    public function it_returns_agent_details_with_location() {
        $response = $this->getJson("/api/v1/agents/{$this->agent->id}");
        $response->assertStatus(200)->assertJsonStructure(['data' => ['id', 'full_name', 'location_lat', 'location_lng', 'available']]);
    }

    /** @test */
    public function it_toggles_agent_online_status() {
        $response = $this->withToken($this->token)->postJson('/api/v1/agent/toggle-online');
        $response->assertStatus(200);
        $this->assertDatabaseHas('agents', ['id' => $this->agent->id, 'available' => false]);
    }
}
```

## تشغيل الاختبارات
```bash
php artisan test --filter=AgentMapTest
```
