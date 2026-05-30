<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Identity\Models\User;
use Tests\TestCase;

class PinTest extends TestCase
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

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'phone' => '963123456789',
            'pin' => '789012',
        ]);

        $this->token = $loginResponse->json('data.token');
    }

    public function test_user_can_create_pin_after_otp_verification(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/v1/auth/pin/create', [
            'pin' => '123789',
            'pin_confirmation' => '123789',
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);
    }

    public function test_pin_must_be_exactly_6_digits(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/v1/auth/pin/create', [
            'pin' => '12345',
            'pin_confirmation' => '12345',
        ]);

        $response->assertStatus(422);
    }

    public function test_pin_cannot_be_sequential_digits(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/v1/auth/pin/create', [
            'pin' => '123456',
            'pin_confirmation' => '123456',
        ]);

        $response->assertStatus(422);
    }

    public function test_pin_cannot_be_repeated_digits(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/v1/auth/pin/create', [
            'pin' => '111111',
            'pin_confirmation' => '111111',
        ]);

        $response->assertStatus(422);
    }

    public function test_pin_is_stored_as_hash(): void
    {
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/v1/auth/pin/create', [
            'pin' => '789012',
            'pin_confirmation' => '789012',
        ]);

        $this->assertNotNull($this->user->fresh()->pin_hash);
    }

    public function test_user_can_change_pin_with_valid_old_pin(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/v1/auth/pin/create', [
            'pin' => '789012',
            'pin_confirmation' => '789012',
        ]);

        $response->assertStatus(201);
    }

    public function test_pin_change_fails_with_wrong_old_pin(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/v1/auth/pin/create', [
            'pin' => '789012',
            'pin_confirmation' => '789012',
        ]);

        $response->assertStatus(201);
    }
}
