<?php

declare(strict_types=1);

namespace Modules\Fraud\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Fraud\DTOs\FraudCheckDto;
use Modules\Fraud\Enums\FraudDecision;
use Modules\Fraud\Exceptions\FraudTransactionBlockedException;
use Modules\Fraud\Exceptions\FraudReviewRequiredException;
use Modules\Fraud\Exceptions\FraudDeviceBlockedException;
use Modules\Fraud\Exceptions\FraudIpBlockedException;
use Modules\Fraud\Exceptions\FraudRapidSuccessiveTxnsException;
use Modules\Fraud\Models\FraudBlacklistEntry;
use Modules\Fraud\Services\FraudEngine;
use Modules\Fraud\Models\FraudEvent;
use Modules\Fraud\Models\FraudCase;
use Modules\Fraud\Repositories\FraudBlacklistRepository;
use Tests\TestCase;

final class FraudEngineTest extends TestCase
{
    use RefreshDatabase;

    private FraudEngine $engine;
    private FraudBlacklistRepository $blacklist;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = $this->app->make(FraudEngine::class);
        $this->blacklist = $this->app->make(FraudBlacklistRepository::class);
    }

    public function test_allows_low_risk_event(): void
    {
        $event = $this->engine->evaluate(new FraudCheckDto(
            eventType: 'login',
            actorId: '01ARfraudActorAllow01',
            ipAddress: '192.168.1.1',
        ));

        $this->assertInstanceOf(FraudEvent::class, $event);
        $this->assertEquals(FraudDecision::ALLOW->value, $event->decision);
        $this->assertLessThan(500, $event->risk_score);
    }

    public function test_blocks_device_blacklisted(): void
    {
        $this->blacklist->add([
            'id' => (string) \Illuminate\Support\Str::ulid(),
            'type' => 'device',
            'value' => 'blacklisted-device-001',
            'reason' => 'Known fraud device',
        ]);

        $this->expectException(FraudDeviceBlockedException::class);

        $this->engine->checkOrFail(new FraudCheckDto(
            eventType: 'payment',
            actorId: '01ARfraudActorBlockDev',
            deviceId: 'blacklisted-device-001',
        ));
    }

    public function test_blocks_ip_blacklisted(): void
    {
        $this->blacklist->add([
            'id' => (string) \Illuminate\Support\Str::ulid(),
            'type' => 'ip',
            'value' => '10.0.0.99',
            'reason' => 'Known abuse IP',
        ]);

        $this->expectException(FraudIpBlockedException::class);

        $this->engine->checkOrFail(new FraudCheckDto(
            eventType: 'login',
            actorId: '01ARfraudActorBlockIP',
            ipAddress: '10.0.0.99',
        ));
    }

    public function test_blocks_high_velocity(): void
    {
        $actorId = '01ARfraudActorVelocity';

        for ($i = 0; $i < 11; $i++) {
            try {
                $this->engine->evaluate(new FraudCheckDto(
                    eventType: 'payment',
                    actorId: $actorId,
                    ipAddress: '192.168.1.1',
                ));
            } catch (FraudTransactionBlockedException|FraudReviewRequiredException) {
                break;
            }
        }

        $this->expectException(FraudRapidSuccessiveTxnsException::class);
        $this->engine->checkOrFail(new FraudCheckDto(
            eventType: 'payment',
            actorId: $actorId,
            ipAddress: '192.168.1.1',
        ));
    }

    public function test_triggers_review_on_medium_score(): void
    {
        $actorId = '01ARfraudActorReview';

        // Combine velocity + sanctions to exceed 500 review threshold
        for ($i = 0; $i < 8; $i++) {
            try {
                $this->engine->evaluate(new FraudCheckDto(
                    eventType: 'payment',
                    actorId: $actorId,
                    ipAddress: '192.168.1.' . $i,
                    fullName: 'Terrorist Watchlist Person',
                ));
            } catch (FraudReviewRequiredException | FraudTransactionBlockedException) {
                break;
            }
        }

        // The last one should have created a fraud case
        $cases = FraudCase::where('actor_id', $actorId)->get();
        $this->assertGreaterThanOrEqual(1, $cases->count());
    }

    public function test_creates_fraud_event_on_evaluate(): void
    {
        $this->engine->evaluate(new FraudCheckDto(
            eventType: 'registration',
            actorId: '01ARfraudActorEvent1',
        ));

        $this->assertEquals(1, FraudEvent::count());
    }

    public function test_sanctions_screening_adds_score(): void
    {
        $event = $this->engine->evaluate(new FraudCheckDto(
            eventType: 'registration',
            actorId: '01ARfraudActorSanction',
            fullName: 'John Terrorist Smith',
        ));

        $this->assertGreaterThanOrEqual(300, $event->risk_score);
    }

    public function test_large_amount_adds_risk(): void
    {
        $event = $this->engine->evaluate(new FraudCheckDto(
            eventType: 'payment',
            actorId: '01ARfraudActorLargeAmt',
            amount: 25000000,
        ));

        $this->assertGreaterThanOrEqual(150, $event->risk_score);
    }

    public function test_geolocation_anomaly_adds_score(): void
    {
        // First event in Damascus
        $this->engine->evaluate(new FraudCheckDto(
            eventType: 'login',
            actorId: '01ARfraudActorGeo',
            latitude: 33.5131,
            longitude: 36.2919,
        ));

        // Second event in Tokyo seconds later - impossible travel
        $event = $this->engine->evaluate(new FraudCheckDto(
            eventType: 'login',
            actorId: '01ARfraudActorGeo',
            latitude: 35.6762,
            longitude: 139.6503,
        ));

        $this->assertGreaterThanOrEqual(100, $event->risk_score);
    }

    public function test_blacklist_remove_works(): void
    {
        $entry = $this->blacklist->add([
            'id' => (string) \Illuminate\Support\Str::ulid(),
            'type' => 'email',
            'value' => 'spam@example.com',
        ]);

        $this->assertTrue($this->blacklist->isBlocked('email', 'spam@example.com'));

        $this->blacklist->remove($entry->id);

        $this->assertFalse($this->blacklist->isBlocked('email', 'spam@example.com'));
    }

    public function test_blacklist_expiry(): void
    {
        $this->blacklist->add([
            'id' => (string) \Illuminate\Support\Str::ulid(),
            'type' => 'ip',
            'value' => '10.0.0.50',
            'expires_at' => now()->subDay(),
        ]);

        $this->assertFalse($this->blacklist->isBlocked('ip', '10.0.0.50'));
    }

    public function test_check_or_fail_passes_on_low_risk(): void
    {
        $event = $this->engine->checkOrFail(new FraudCheckDto(
            eventType: 'login',
            actorId: '01ARfraudActorCheckOk',
            ipAddress: '192.168.1.1',
        ));

        $this->assertInstanceOf(FraudEvent::class, $event);
    }
}
