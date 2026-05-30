<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Identity\Models\User;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    private array $validPayload = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
        'phone' => '963123456789',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    public function test_user_can_register_with_valid_data(): void
    {
        $response = $this->postJson('/api/v1/auth/register', $this->validPayload);

        $response->assertStatus(201)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'first_name', 'last_name', 'email', 'phone', 'status'],
                    'token',
                    'token_type',
                ],
            ]);
    }

    public function test_registration_fails_with_duplicate_phone(): void
    {
        User::factory()->create(['phone' => '963123456789']);

        $response = $this->postJson('/api/v1/auth/register', $this->validPayload);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);
    }

    public function test_registration_fails_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'john@example.com']);

        $response = $this->postJson('/api/v1/auth/register', $this->validPayload);

        $response->assertStatus(422);
    }

    public function test_registration_fails_with_invalid_phone_format(): void
    {
        $response = $this->postJson('/api/v1/auth/register', array_merge($this->validPayload, [
            'phone' => 'abc',
        ]));

        $response->assertStatus(422);
    }

    public function test_registration_fails_with_empty_phone(): void
    {
        $response = $this->postJson('/api/v1/auth/register', array_merge($this->validPayload, [
            'phone' => '',
        ]));

        $response->assertStatus(422);
    }

    public function test_registration_fails_without_password_confirmation(): void
    {
        $response = $this->postJson('/api/v1/auth/register', array_merge($this->validPayload, [
            'password_confirmation' => 'wrong',
        ]));

        $response->assertStatus(422);
    }

    public function test_registration_fails_with_short_password(): void
    {
        $response = $this->postJson('/api/v1/auth/register', array_merge($this->validPayload, [
            'password' => 'short',
            'password_confirmation' => 'short',
        ]));

        $response->assertStatus(422);
    }

    public function test_registration_creates_user_in_pending_status(): void
    {
        $this->postJson('/api/v1/auth/register', $this->validPayload);

        $this->assertDatabaseHas('users', [
            'phone' => '963123456789',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'status' => 'pending',
        ]);
    }

    public function test_registration_returns_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', $this->validPayload);

        $response->assertStatus(201);
        $this->assertNotEmpty($response->json('data.token'));
        $this->assertEquals('Bearer', $response->json('data.token_type'));
    }
}
