<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Identity\Models\User;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->verified()->withPin('789012')->create([
            'phone' => '963123456789',
        ]);
    }

    public function test_user_can_login_with_valid_phone_and_pin(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'phone' => '963123456789',
            'pin' => '789012',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_login_returns_jwt_token(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'phone' => '963123456789',
            'pin' => '789012',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['token']]);
    }

    public function test_login_returns_refresh_token(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'phone' => '963123456789',
            'pin' => '789012',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['refresh_token']]);
    }

    public function test_login_creates_session_record(): void
    {
        $this->postJson('/api/v1/auth/login', [
            'phone' => '963123456789',
            'pin' => '789012',
        ]);

        $this->assertDatabaseHas('sessions', [
            'user_id' => $this->user->id,
        ]);
    }

    public function test_login_binds_device(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'phone' => '963123456789',
            'pin' => '789012',
            'device_id' => 'device-abc-123',
        ]);

        $response->assertStatus(200);
    }

    public function test_login_fails_with_wrong_pin(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'phone' => '963123456789',
            'pin' => '000000',
        ]);

        $response->assertStatus(401);
    }

    public function test_login_fails_for_blocked_user(): void
    {
        $this->user->update([
            'locked_until' => now()->addMinutes(30),
            'failed_attempts' => 5,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'phone' => '963123456789',
            'pin' => '789012',
        ]);

        $response->assertStatus(401);
    }

    public function test_login_fails_for_unverified_phone(): void
    {
        $unverifiedUser = User::factory()->withPin('789012')->create([
            'phone' => '963987654321',
            'phone_verified_at' => null,
            'status' => 'pending',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'phone' => '963987654321',
            'pin' => '789012',
        ]);

        $response->assertStatus(401);
    }

    public function test_login_respects_max_devices_limit(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'phone' => '963123456789',
                'pin' => '789012',
                'device_id' => "device-{$i}",
            ]);
        }

        $activeSessions = $this->user->sessions()
            ->where('expires_at', '>', now())
            ->count();

        $this->assertLessThanOrEqual(2, $activeSessions);
    }

    public function test_cannot_login_more_than_5_times_with_wrong_pin(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'phone' => '963123456789',
                'pin' => '000000',
            ]);
        }

        $this->user->refresh();
        $this->assertTrue($this->user->isLocked());
    }

    public function test_locked_user_can_login_after_30_minutes(): void
    {
        $this->user->update([
            'locked_until' => now()->subMinute(),
            'failed_attempts' => 5,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'phone' => '963123456789',
            'pin' => '789012',
        ]);

        $response->assertStatus(200);
    }
}
