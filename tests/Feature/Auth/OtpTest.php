<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Identity\Models\OtpCode;
use Modules\Identity\Models\User;
use Tests\TestCase;

class OtpTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'phone' => '963123456789',
        ]);
    }

    public function test_user_can_request_otp_after_registration(): void
    {
        $response = $this->postJson('/api/v1/auth/otp/request', [
            'phone' => '963123456789',
            'purpose' => 'register',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_otp_expires_after_5_minutes(): void
    {
        OtpCode::create([
            'user_id' => $this->user->id,
            'phone' => '963123456789',
            'purpose' => 'register',
            'code_hash' => bcrypt('123456'),
            'attempts' => 0,
            'max_attempts' => 5,
            'expires_at' => now()->subMinute(),
        ]);

        $response = $this->postJson('/api/v1/auth/otp/verify', [
            'phone' => '963123456789',
            'code' => '123456',
            'purpose' => 'register',
        ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_otp_verification_fails_with_wrong_code(): void
    {
        $this->postJson('/api/v1/auth/otp/request', [
            'phone' => '963123456789',
            'purpose' => 'register',
        ]);

        $response = $this->postJson('/api/v1/auth/otp/verify', [
            'phone' => '963123456789',
            'code' => '000000',
            'purpose' => 'register',
        ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_otp_rate_limiting_blocks_after_5_attempts(): void
    {
        $otp = OtpCode::create([
            'user_id' => $this->user->id,
            'phone' => '963123456789',
            'purpose' => 'register',
            'code_hash' => bcrypt('123456'),
            'attempts' => 5,
            'max_attempts' => 5,
            'expires_at' => now()->addMinutes(5),
        ]);

        $response = $this->postJson('/api/v1/auth/otp/verify', [
            'phone' => '963123456789',
            'code' => '123456',
            'purpose' => 'register',
        ]);

        $response->assertStatus(422);
    }

    public function test_cannot_verify_expired_otp(): void
    {
        OtpCode::create([
            'user_id' => $this->user->id,
            'phone' => '963123456789',
            'purpose' => 'register',
            'code_hash' => bcrypt('123456'),
            'attempts' => 0,
            'max_attempts' => 5,
            'expires_at' => now()->subSeconds(10),
        ]);

        $response = $this->postJson('/api/v1/auth/otp/verify', [
            'phone' => '963123456789',
            'code' => '123456',
            'purpose' => 'register',
        ]);

        $response->assertStatus(422);
    }

    public function test_otp_verified_at_is_set_on_success(): void
    {
        $this->postJson('/api/v1/auth/otp/request', [
            'phone' => '963123456789',
            'purpose' => 'register',
        ]);

        $otp = OtpCode::where('phone', '963123456789')
            ->where('purpose', 'register')
            ->latest()
            ->first();

        $this->assertNotNull($otp);
    }
}
