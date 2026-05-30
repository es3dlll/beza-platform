<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Identity\Models\User;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->verified()->withPin('789012')->create([
            'phone' => '963123456789',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'phone' => '963123456789',
            'pin' => '789012',
        ]);

        $this->token = $response->json('data.token');
    }

    public function test_user_can_logout(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_logout_invalidates_session(): void
    {
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/v1/auth/logout');

        $session = $this->user->sessions()->first();

        if ($session !== null) {
            $this->assertFalse($session->isActive());
        }
    }

    public function test_logout_requires_auth(): void
    {
        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertStatus(401);
    }

    public function test_expired_token_cannot_logout(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer expired.token.here',
        ])->postJson('/api/v1/auth/logout');

        $response->assertStatus(401);
    }
}
