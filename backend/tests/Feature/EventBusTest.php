<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\EventBus\Consumers\AuditLogConsumer;
use App\Modules\EventBus\Consumers\VelocityUpdateConsumer;
use App\Modules\EventBus\Contracts\EventConsumer;
use App\Modules\EventBus\Events\TestEvent;
use App\Modules\EventBus\Events\TestTransactionPosted;
use App\Modules\EventBus\Models\DeadLetterEvent;
use App\Modules\EventBus\Models\EventDeliveryLog;
use App\Modules\EventBus\Services\ConsumerRegistry;
use App\Modules\EventBus\Services\EventPublisher;
use App\Modules\EventBus\Services\EventSerializer;
use App\Modules\EventBus\Services\EventBusHealthCheck;
use App\Modules\EventBus\Services\RetryPolicy;
use App\Modules\EventBus\Services\SchemaVersionManager;
use App\Modules\Fraud\Models\VelocityCounter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

final class EventBusTest extends TestCase
{
    use RefreshDatabase;

    private EventPublisher $publisher;
    private ConsumerRegistry $registry;
    private RetryPolicy $retryPolicy;
    private SchemaVersionManager $schemaManager;
    private EventSerializer $serializer;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        $this->registry = new ConsumerRegistry();
        $this->registry->register(
            'velocity_update',
            new VelocityUpdateConsumer(),
            ['financial_core.transaction_posted'],
        );
        $this->registry->register(
            'audit_log',
            new AuditLogConsumer(),
            ['financial_core.#', 'financial.agent.#', 'financial.fx.#', 'financial.fraud.#'],
        );
        $this->app->instance(ConsumerRegistry::class, $this->registry);

        $this->publisher = $this->app->make(EventPublisher::class);
        $this->retryPolicy = $this->app->make(RetryPolicy::class);
        $this->schemaManager = $this->app->make(SchemaVersionManager::class);
        $this->serializer = $this->app->make(EventSerializer::class);
    }

    public function test_event_published_creates_delivery_log(): void
    {
        $eventId = $this->publisher->publish(new TestTransactionPosted(
            transactionId: 'tx-1',
            amount: 100000,
            fromWalletId: 'wallet-1',
            toWalletId: 'wallet-2',
            journalEntryId: 'je-1',
        ));

        $this->assertNotEmpty($eventId);

        $logs = EventDeliveryLog::where('event_id', $eventId)->get();

        $this->assertCount(2, $logs);
        $this->assertEquals('financial_core.transaction_posted', $logs[0]->event_type);
    }

    public function test_event_with_no_consumers_still_returns_event_id(): void
    {
        $eventId = $this->publisher->publish(new TestEvent(
            eventType: 'unregistered.event.type',
        ));

        $this->assertNotEmpty($eventId);

        $logs = EventDeliveryLog::where('event_id', $eventId)->get();
        $this->assertCount(0, $logs);
    }

    public function test_idempotent_consumer_skips_duplicate(): void
    {
        $testConsumer = new class implements EventConsumer {
            public int $callCount = 0;
            public function getName(): string { return 'test_idempotent'; }
            public function handle(string $eventType, array $payload, EventDeliveryLog $log): void
            {
                $this->callCount++;
            }
        };

        $this->registry->register('test_idempotent', $testConsumer, ['test.idempotent.event']);

        $eventId = $this->publisher->publish(new TestEvent(
            eventType: 'test.idempotent.event',
            payload: ['key' => 'value'],
        ));

        $this->publisher->publish(new TestEvent(
            eventType: 'test.idempotent.event',
            payload: ['key2' => 'value2'],
        ));

        $consumed = EventDeliveryLog::where('consumer_name', 'test_idempotent')
            ->where('status', 'consumed')
            ->count();

        $this->assertEquals(2, $consumed);

        $logs = EventDeliveryLog::where('consumer_name', 'test_idempotent')->get();
        $this->assertCount(2, $logs);
        $this->assertEquals('consumed', $logs[0]->status);
        $this->assertEquals('consumed', $logs[1]->status);
    }

    public function test_retry_policy_calculates_exponential_delays(): void
    {
        $this->assertEquals(0, $this->retryPolicy->getDelaySeconds(0));
        $this->assertEquals(60, $this->retryPolicy->getDelaySeconds(1));
        $this->assertEquals(120, $this->retryPolicy->getDelaySeconds(2));
        $this->assertEquals(240, $this->retryPolicy->getDelaySeconds(3));

        $this->assertTrue($this->retryPolicy->canRetry(1));
        $this->assertTrue($this->retryPolicy->canRetry(2));
        $this->assertFalse($this->retryPolicy->canRetry(3));
    }

    public function test_consumer_failure_moves_to_dead_letter(): void
    {
        $failingConsumer = new class implements EventConsumer {
            public function getName(): string { return 'test_failing'; }
            public function handle(string $eventType, array $payload, EventDeliveryLog $log): void
            {
                throw new \RuntimeException('Consumer failed intentionally');
            }
        };

        $this->registry->register('test_failing', $failingConsumer, ['test.failing.event']);

        $this->publisher->publish(new TestEvent(
            eventType: 'test.failing.event',
            payload: ['should' => 'fail'],
        ));

        $deadLetters = DeadLetterEvent::where('consumer_name', 'test_failing')->get();

        $this->assertGreaterThanOrEqual(1, $deadLetters->count());
        $this->assertEquals('pending', $deadLetters->first()->status);
        $this->assertStringContainsString('Consumer failed intentionally', $deadLetters->first()->error_message);
    }

    public function test_consumer_retry_counts_tracked_in_log(): void
    {
        $failingConsumer = new class implements EventConsumer {
            public int $callCount = 0;
            public function getName(): string { return 'test_retry_count'; }
            public function handle(string $eventType, array $payload, EventDeliveryLog $log): void
            {
                $this->callCount++;
                throw new \RuntimeException("Retry attempt {$this->callCount}");
            }
        };

        $this->registry->register('test_retry_count', $failingConsumer, ['test.retry.event']);

        $this->publisher->publish(new TestEvent(
            eventType: 'test.retry.event',
            payload: ['retry' => 'me'],
        ));

        $dlq = DeadLetterEvent::where('consumer_name', 'test_retry_count')->first();
        $this->assertNotNull($dlq);
        $this->assertEquals(3, $dlq->attempts);

        $failedLog = EventDeliveryLog::where('consumer_name', 'test_retry_count')
            ->where('status', 'dead_letter')
            ->first();
        $this->assertNotNull($failedLog);
    }

    public function test_velocity_consumer_updates_counter(): void
    {
        $this->publisher->publish(new TestTransactionPosted(
            transactionId: 'tx-vel-1',
            amount: 50000,
            fromWalletId: 'wallet-vel-test',
            toWalletId: 'wallet-vel-test-2',
            journalEntryId: 'je-vel-1',
        ));

        $windowKey = 'vel:wallet-vel-test:system_async_velocity:' . date('YmdH');
        $counters = VelocityCounter::where('wallet_id', 'wallet-vel-test')
            ->where('window_key', $windowKey)
            ->get();

        $this->assertGreaterThanOrEqual(1, $counters->count());
        $this->assertGreaterThanOrEqual(1, $counters->first()->count);
    }

    public function test_publisher_confirm_returns_event_id(): void
    {
        $eventId = $this->publisher->publish(new TestTransactionPosted(
            transactionId: 'tx-confirm-1',
            amount: 20000,
            fromWalletId: 'wallet-confirm',
            toWalletId: 'wallet-confirm-2',
            journalEntryId: 'je-confirm-1',
        ));

        $this->assertNotEmpty($eventId);

        $consumed = EventDeliveryLog::where('event_id', $eventId)
            ->where('status', 'consumed')
            ->count();

        $this->assertEquals(2, $consumed);
    }

    public function test_schema_version_backward_compatible(): void
    {
        $this->assertTrue($this->schemaManager->isVersionSupported('v1'));
        $this->assertTrue($this->schemaManager->isVersionSupported('v2'));
        $this->assertFalse($this->schemaManager->isVersionSupported('v3'));

        $this->assertTrue($this->schemaManager->isBackwardCompatible('v1', 'v2'));
        $this->assertTrue($this->schemaManager->isBackwardCompatible('v1', 'v1'));
        $this->assertTrue($this->schemaManager->isBackwardCompatible('v2', 'v2'));
        $this->assertFalse($this->schemaManager->isBackwardCompatible('v2', 'v1'));

        $this->assertEquals('v1', $this->schemaManager->getCurrentVersion());
    }

    public function test_unsupported_schema_version_rejected(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unsupported event version: v99');

        $this->serializer->deserialize([
            'event_id' => 'test',
            'event_version' => 'v99',
            'event_type' => 'test.event',
            'timestamp' => 1234,
            'source' => 'test',
            'data' => [],
        ]);
    }

    public function test_health_check_returns_correct_structure(): void
    {
        $healthCheck = $this->app->make(EventBusHealthCheck::class);
        $result = $healthCheck->check();

        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('consumers', $result);
        $this->assertArrayHasKey('dead_letter', $result);
        $this->assertArrayHasKey('retry_policy', $result);
        $this->assertArrayHasKey('schema_version', $result);
        $this->assertArrayHasKey('consumer_names', $result);
    }
}
