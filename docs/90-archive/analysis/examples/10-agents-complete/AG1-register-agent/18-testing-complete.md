# 18 - كل الاختبارات (Testing Complete)

```php
<?php
namespace Tests\Feature\Agent;
use App\Models\Agent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AgentRegistrationTest extends TestCase
{
    use RefreshDatabase;
    private User $user;
    private string $token;

    protected function setUp(): void {
        parent::setUp();
        $this->user = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $this->token = JWTAuth::fromUser($this->user);
    }

    /** @test */
    public function it_registers_an_agent_successfully() {
        $response = $this->withToken($this->token)->postJson('/api/v1/agents/register', [
            'full_name' => 'أحمد خالد', 'phone' => '963944123456',
            'id_number' => 'ID123456', 'id_photo' => 'base64...',
            'location_lat' => 33.5138, 'location_lng' => 36.2765,
            'address' => 'دمشق, سوريا',
        ]);
        $response->assertStatus(201)->assertJson(['success' => true]);
        $this->assertDatabaseHas('agents', ['user_id' => $this->user->id, 'status' => 'pending']);
    }

    /** @test */
    public function it_requires_authentication() {
        $response = $this->postJson('/api/v1/agents/register', []);
        $response->assertStatus(401);
    }

    /** @test */
    public function it_validates_required_fields() {
        $response = $this->withToken($this->token)->postJson('/api/v1/agents/register', []);
        $response->assertStatus(422)->assertJsonValidationErrors(['full_name', 'phone', 'id_number']);
    }

    /** @test */
    public function it_rejects_duplicate_phone_number() {
        Agent::factory()->create(['phone' => '963944123456']);
        $response = $this->withToken($this->token)->postJson('/api/v1/agents/register', [
            'full_name' => 'أحمد خالد', 'phone' => '963944123456', 'id_number' => 'ID999999',
        ]);
        $response->assertStatus(422)->assertJsonValidationErrors(['phone']);
    }

    /** @test */
    public function it_rejects_existing_agent_for_user() {
        Agent::factory()->create(['user_id' => $this->user->id]);
        $response = $this->withToken($this->token)->postJson('/api/v1/agents/register', [
            'full_name' => 'أحمد خالد', 'phone' => '963944999999', 'id_number' => 'ID999999',
        ]);
        $response->assertStatus(409);
    }

    /** @test */
    public function it_creates_agent_with_pending_status() {
        $response = $this->withToken($this->token)->postJson('/api/v1/agents/register', [
            'full_name' => 'أحمد خالد', 'phone' => '963944123456',
            'id_number' => 'ID123456', 'id_photo' => 'base64...',
        ]);
        $this->assertEquals('pending', $response->json('data.status'));
    }

    /** @test */
    public function it_verifies_agent_identity() {
        $agent = Agent::factory()->create(['user_id' => $this->user->id, 'status' => 'pending']);
        $admin = User::factory()->create(['role' => 'admin']);
        $adminToken = JWTAuth::fromUser($admin);
        $response = $this->withToken($adminToken)->postJson("/api/v1/admin/agents/{$agent->id}/verify", ['status' => 'approved']);
        $response->assertStatus(200);
        $this->assertDatabaseHas('agents', ['id' => $agent->id, 'status' => 'approved']);
    }

    /** @test */
    public function it_returns_agent_list_for_admin() {
        Agent::factory()->count(3)->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $adminToken = JWTAuth::fromUser($admin);
        $response = $this->withToken($adminToken)->getJson('/api/v1/admin/agents');
        $response->assertStatus(200)->assertJsonCount(3, 'data');
    }
}
```

## تشغيل الاختبارات
```bash
php artisan test --filter=AgentRegistrationTest
```
