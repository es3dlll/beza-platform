<?php

declare(strict_types=1);

namespace Modules\OpenFinance\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\OpenFinance\Models\OpenFinanceApp;
use Modules\OpenFinance\Models\OpenFinanceConsent;
use Modules\OpenFinance\Models\OpenFinanceAccessToken;
use Modules\OpenFinance\Models\OpenFinanceWebhook;
use Modules\OpenFinance\Models\OpenFinancePayment;
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
        $user = $this->createUser('01ARofUser01');
        $app = $this->service->registerApp($user->id, 'Test App', 'https://app.test/callback', ['accounts:read']);
        $this->assertNotNull($app->client_id);
        $this->assertNotNull($app->client_secret);
        $this->assertTrue($app->is_active);
    }

    public function test_can_create_consent_and_token(): void
    {
        $user = $this->createUser('01ARofUser02');
        $app = $this->service->registerApp($user->id, 'My App', 'https://app.test', ['accounts:read']);
        $consent = $this->service->createConsent($user->id, $app->id, ['accounts:read'], 30);
        $this->assertEquals('active', $consent->status);

        $token = $this->service->generateToken($consent->id);
        $this->assertNotNull($token->token);
    }

    public function test_can_revoke_consent(): void
    {
        $user = $this->createUser('01ARofUser03');
        $app = $this->service->registerApp($user->id, 'Revocable', 'https://app.test', ['accounts:read']);
        $consent = $this->service->createConsent($user->id, $app->id, ['accounts:read']);
        $this->service->revokeConsent($consent->id);
        $this->assertEquals('revoked', $consent->fresh()->status);
    }

    public function test_can_initiate_payment(): void
    {
        $user = $this->createUser('01ARofUser04');
        $app = $this->service->registerApp($user->id, 'Pay App', 'https://app.test', ['payments:initiate']);
        $consent = $this->service->createConsent($user->id, $app->id, ['payments:initiate']);

        $payment = $this->service->initiatePayment($consent->id, $user->id, 'p2p', 'user-002', 50000, 'Test payment');
        $this->assertEquals('completed', $payment->status);
        $this->assertEquals(50000, $payment->amount);
    }

    public function test_can_register_webhook(): void
    {
        $user = $this->createUser('01ARofUser05');
        $app = $this->service->registerApp($user->id, 'Webhook App', 'https://app.test', ['webhooks:manage']);

        $webhook = $this->service->registerWebhook($app->id, 'https://app.test/webhook', ['payment.completed', 'payment.failed']);
        $this->assertNotNull($webhook->secret);
        $this->assertTrue($webhook->is_active);
    }

    public function test_developer_tier(): void
    {
        $user = $this->createUser('01ARofUser06');
        $tier = $this->service->getDeveloperTier($user->id);
        $this->assertEquals('starter', $tier);
        $this->assertEquals(10, $this->service->getRateLimit($tier));
    }

    private function createUser(string $id): User
    {
        $user = new User(); $user->id = $id; $user->phone = $id; $user->status = 'active'; $user->save();
        return $user;
    }
}
