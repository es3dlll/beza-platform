<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Identity\Models\User;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_with_valid_phone(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'phone' => '963123456789',
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data' => ['user' => ['id', 'phone', 'status']]]);
    }

    public function test_registration_fails_with_duplicate_phone(): void
    {
        User::factory()->create(['phone' => '963123456789']);

        $response = $this->postJson('/api/v1/auth/register', [
            'phone' => '963123456789',
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);
    }

    public function test_registration_fails_with_invalid_phone_format(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'phone' => 'abc',
        ]);

        $response->assertStatus(422);
    }

    public function test_registration_fails_with_empty_phone(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'phone' => '',
        ]);

        $response->assertStatus(422);
    }

    public function test_registration_creates_user_in_pending_status(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'phone' => '963123456789',
        ]);

        $this->assertDatabaseHas('users', [
            'phone' => '963123456789',
            'status' => 'pending',
        ]);
    }
}
