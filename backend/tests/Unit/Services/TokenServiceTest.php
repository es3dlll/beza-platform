<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Identity\Models\Session;
use Modules\Identity\Models\User;
use Modules\Identity\Services\TokenService;
use Tests\TestCase;

class TokenServiceTest extends TestCase
{
    use RefreshDatabase;

    private TokenService $tokenService;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tokenService = $this->app->make(TokenService::class);

        $this->user = User::factory()->verified()->withPin('123456')->create([
            'phone' => '963123456789',
        ]);
    }

    public function test_generates_jwt_token(): void
    {
        $token = $this->tokenService->generateToken($this->user);

        $this->assertIsString($token);
        $this->assertNotEmpty($token);
    }

    public function test_generates_refresh_token(): void
    {
        $refreshToken = $this->tokenService->generateRefreshToken($this->user);

        $this->assertIsString($refreshToken);
        $this->assertNotEmpty($refreshToken);
    }

    public function test_create_session_stores_session_record(): void
    {
        $session = $this->tokenService->createSession($this->user, [
            'token' => 'test-token-hash',
            'refresh_token' => 'test-refresh-hash',
            'device_id' => 'device-001',
        ]);

        $this->assertDatabaseHas('sessions', [
            'id' => $session->id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_session_has_expiry_time(): void
    {
        $session = $this->tokenService->createSession($this->user, [
            'token' => 'test-token',
            'refresh_token' => 'test-refresh',
        ]);

        $this->assertNotNull($session->expires_at);
        $this->assertTrue($session->expires_at->isFuture());
    }

    public function test_invalidate_session_deletes_record(): void
    {
        $session = $this->tokenService->createSession($this->user, [
            'token' => 'test-token-del',
            'refresh_token' => 'test-refresh-del',
        ]);

        $this->tokenService->invalidateSession(hash('sha256', 'test-token-del'));

        $this->assertDatabaseMissing('sessions', ['id' => $session->id]);
    }

    public function test_invalidate_all_user_sessions(): void
    {
        $this->tokenService->createSession($this->user, ['token' => 'tok1', 'refresh_token' => 'ref1']);
        $this->tokenService->createSession($this->user, ['token' => 'tok2', 'refresh_token' => 'ref2']);

        $this->tokenService->invalidateAllUserSessions($this->user->id);

        $this->assertEquals(0, Session::where('user_id', $this->user->id)->count());
    }

    public function test_validate_token_returns_null_for_invalid_token(): void
    {
        $user = $this->tokenService->validateToken('invalid.token.string');

        $this->assertNull($user);
    }

    public function test_generate_and_validate_token_roundtrip(): void
    {
        $token = $this->tokenService->generateToken($this->user);

        $this->assertNotEmpty($token);
    }
}
