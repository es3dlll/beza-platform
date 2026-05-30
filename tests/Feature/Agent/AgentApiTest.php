<?php

declare(strict_types=1);

namespace Tests\Feature\Agent;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Agent\Models\Agent;
use Modules\Identity\Models\User;
use Tests\TestCase;

final class AgentApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'phone' => '963900000010',
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'phone' => '963900000010',
        ]);

        $this->token = $response->json('data.token') ?? 'test-token';
    }

    public function test_can_register_as_agent(): void
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/v1/agents/register', [
                'business_name' => 'Test Exchange',
                'governorate' => 'Damascus',
                'city' => 'Al-Midan',
                'phone' => '963900000011',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'pending');
    }

    public function test_rejects_agent_without_business_name(): void
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/v1/agents/register', [
                'governorate' => 'Damascus',
                'city' => 'Al-Midan',
                'phone' => '963900000012',
            ]);

        $response->assertStatus(422);
    }

    public function test_can_get_nearby_agents(): void
    {
        $response = $this->getJson('/api/v1/public/agents/nearby/Damascus');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_returns_404_for_nonexistent_agent(): void
    {
        $response = $this->withToken($this->token)
            ->getJson('/api/v1/agents/nonexistent');

        $response->assertStatus(404);
    }
}
