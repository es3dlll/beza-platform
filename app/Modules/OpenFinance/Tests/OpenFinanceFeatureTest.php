<?php

declare(strict_types=1);

namespace Modules\OpenFinance\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\OpenFinance\Services\OpenFinanceService;
use Modules\Identity\Models\User;
use Tests\TestCase;

final class OpenFinanceFeatureTest extends TestCase
{
    use RefreshDatabase;
    private OpenFinanceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(OpenFinanceService::class);
    }

    public function test_can_register_app(): void
    {
        $user = $this->createUser('01ARofiU01');
        $app = $this->service->registerApp($user->id, 'My App', 'https://example.com/callback', ['accounts:read','profile:read']);
        $this->assertNotNull($app->client_id);
        $this->assertNotNull($app->client_secret);
    }

    public function test_can_create_consent_and_token(): void
    {
        $user = $this->createUser('01ARofiU02');
        $app = $this->service->registerApp($user->id, 'Wallet App', 'https://wallet.app/cb', ['wallet:read']);
        $consent = $this->service->createConsent($user->id, $app->id, ['wallet:read']);
        $this->assertEquals('active', $consent->status);
        $token = $this->service->generateToken($consent->id);
        $this->assertNotNull($token->token);
    }

    public function test_can_revoke_consent(): void
    {
        $user = $this->createUser('01ARofiU03');
        $app = $this->service->registerApp($user->id, 'Test App', 'https://test.app/cb', ['transactions:read']);
        $consent = $this->service->createConsent($user->id, $app->id, ['transactions:read']);
        $this->service->revokeConsent($consent->id);
        $this->expectException(\Modules\OpenFinance\Exceptions\ConsentExpiredException::class);
        $this->service->generateToken($consent->id);
    }

    private function createUser(string $id): User
    {
        $user = new User(); $user->id = $id; $user->phone = $id; $user->status = 'active'; $user->save();
        return $user;
    }
}
