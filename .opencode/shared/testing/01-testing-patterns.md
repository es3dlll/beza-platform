# Testing Patterns

> Single source of truth for testing conventions across ALL Beza Platform features.

## Test Framework

| Component | Framework | Assertion | Notes |
|-----------|-----------|-----------|-------|
| PHP (backend) | PHPUnit 10 | Native assertions + custom | Laravel app tests |
| JavaScript/TypeScript | Vitest + Testing Library | expect + jest-dom | Vue/React components |
| Mobile (iOS) | XCTest | XCTAssert | Swift |
| Mobile (Android) | JUnit 5 + Espresso | AssertJ | Kotlin |
| E2E | Playwright | Playwright assertions | Browser tests |
| Performance | k6 | k6 checks | Load tests |

## Arrange-Act-Assert Structure

Every test follows the AAA pattern with clear section separation:

```php
public function test_user_can_transfer_funds_within_daily_limit(): void
{
    // Arrange
    $sender = User::factory()->kycLevel2()->withWallet(100000)->create();
    $recipient = User::factory()->withWallet()->create();
    $this->actingAs($sender);

    // Act
    $response = $this->postJson('/api/v1/transfers', [
        'recipient_phone' => $recipient->phone,
        'amount' => 50000,
        'pin' => '1234',
    ]);

    // Assert
    $response->assertStatus(200);
    $response->assertJsonPath('data.status', 'completed');
    $this->assertEquals(50000, $sender->wallet->fresh()->balance);
    $this->assertEquals(150000, $recipient->wallet->fresh()->balance);
}
```

### AAA Rules
- **Arrange**: Setup only what's necessary for the test. Use factories with states. Don't share state between tests.
- **Act**: One action per test. Never test two behaviors in one test method.
- **Assert**: Assert the result AND side effects (DB changes, events dispatched, cache cleared).

## Factory Definitions

### Shared Factories (see `02-test-data-factories.md`)
```php
// Shared factory traits used across all feature tests
class UserFactory extends Factory
{
    public function unverified(): static
    {
        return $this->state(['kyc_level' => 0]);
    }

    public function kycLevel1(): static
    {
        return $this->state(['kyc_level' => 1]);
    }

    public function kycLevel2(): static
    {
        return $this->state(['kyc_level' => 2]);
    }

    public function withWallet(int $balance = 0): static
    {
        return $this->has(Wallet::factory()->state(['balance' => $balance]));
    }

    public function withDevice(string $platform = 'android'): static
    {
        return $this->has(Device::factory()->state(['platform' => $platform]));
    }
}
```

### Feature-Specific Factories
- Feature-specific factories extend shared factories
- Never redefine shared defaults in feature factories
- Example: `TransferFactory` extends base, adds `Transaction`-specific state

## Mock Conventions

### External Service Mocks
```php
// Mock external services in integration tests
class TransferTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Mock FX service
        FXService::partialMock()
            ->shouldReceive('getRate')
            ->andReturn(new FXRate(15000, 'USD', 'SYP', now()->addMinutes(5)));

        // Mock notification service (events only)
        NotificationFacade::shouldReceive('send')
            ->once()
            ->with(Mockery::type(TransferReceiptNotification::class));
    }
}
```

### Mock Naming Convention
| Pattern | When to Use |
|---------|-------------|
| `XXX::partialMock()` | Testing class that uses XXX, want to mock one method |
| `Mockery::mock(XXX::class)` | Full isolation, no real implementation |
| `Spy::on(XXX::class)` | Verify interactions without stubbing return values |
| `fake(XXX::class)` | Laravel fake (Mail, Notification, Queue, Storage) |

### Mock Rules
1. Always mock external HTTP calls (Guzzle, Twilio, FCM)
2. Never mock the database (use in-memory SQLite or Test DB)
3. Mock repositories when testing services, but test repositories with real DB
4. Use `shouldReceive` rather than `expects` unless verifying call count is critical
5. Always call `Mockery::close()` in `tearDown()` (Laravel does this automatically)

## Event Testing

### Event Assertion Patterns
```php
// Assert event was dispatched
Event::assertDispatched(TransferCompleted::class);

// Assert event was dispatched with specific data
Event::assertDispatched(TransferCompleted::class, function ($event) {
    return $event->transfer->amount === 50000
        && $event->sender->id === $this->sender->id;
});

// Assert event was NOT dispatched
Event::assertNotDispatched(TransferFailed::class);

// Assert event was dispatched N times
Event::assertDispatchedTimes(NotificationRequested::class, 2);
```

### Event Listener Testing
```php
public function test_transfer_completed_sends_notifications(): void
{
    // Arrange
    Event::fake([
        TransferCompleted::class => TransferCompletedListener::class,
    ]);

    // Act
    $this->postJson('/api/v1/transfers', [...]);

    // Assert
    Event::assertDispatched(TransferCompleted::class);
}
```

### Queue Testing
```php
public function test_notification_is_queued(): void
{
    Queue::fake();

    $this->postJson('/api/v1/transfers', [...]);

    Queue::assertPushed(SendPushNotification::class);
    Queue::assertPushed(SendSmsNotification::class);
    Queue::assertNotPushed(SendEmailNotification::class); // Not for transfers
}
```

## Idempotency Testing

### Idempotency Key Pattern
```php
public function test_transfer_is_idempotent(): void
{
    // Arrange
    $idempotencyKey = Str::uuid()->toString();
    $sender = User::factory()->kycLevel2()->withWallet(100000)->create();
    $recipient = User::factory()->withWallet()->create();

    // Act - first request
    $response1 = $this->postJson('/api/v1/transfers', [
        'recipient_phone' => $recipient->phone,
        'amount' => 50000,
        'idempotency_key' => $idempotencyKey,
    ]);

    // Act - duplicate request (same idempotency key)
    $response2 = $this->postJson('/api/v1/transfers', [
        'recipient_phone' => $recipient->phone,
        'amount' => 50000,
        'idempotency_key' => $idempotencyKey,
    ]);

    // Assert
    $response1->assertStatus(200);
    $response2->assertStatus(200);
    $this->assertEquals($response1->json('data.transaction_id'), $response2->json('data.transaction_id'));
    $this->assertEquals(50000, $sender->wallet->fresh()->balance); // Deducted once
}
```

### Idempotency Scenarios to Test
| Scenario | Expected |
|----------|----------|
| Exact duplicate within TTL (24h) | Return same result, no side effects |
| Same key, different payload | Return 422 (idempotency key mismatch) |
| Expired key (>24h) | Process as new request |
| Key used by different user | Return 409 (conflict) |

## Offline Testing

### Offline Transaction Pattern
```php
public function test_offline_transfer_syncs_when_online(): void
{
    // Arrange
    $device = Device::factory()->create();
    $pendingTxn = OfflineTransaction::factory()->create([
        'device_id' => $device->id,
        'status' => 'pending',
        'created_at' => now()->subHours(2),
    ]);

    // Act
    $response = $this->postJson('/api/v1/transfers/sync', [
        'device_id' => $device->id,
        'transactions' => [
            [
                'offline_id' => $pendingTxn->offline_id,
                'recipient_phone' => $pendingTxn->recipient_phone,
                'amount' => $pendingTxn->amount,
                'local_timestamp' => $pendingTxn->created_at->toIso8601String(),
                'signature' => $pendingTxn->signature,
            ]
        ],
    ]);

    // Assert
    $response->assertStatus(200);
    $this->assertDatabaseHas('transactions', [
        'offline_id' => $pendingTxn->offline_id,
        'status' => 'completed',
    ]);
    $this->assertDatabaseHas('offline_transactions', [
        'id' => $pendingTxn->id,
        'status' => 'synced',
    ]);
}

public function test_offline_transaction_rejected_if_tampered(): void
{
    // Test signature verification, timestamp drift, double-spend prevention
}
```

## Arabic Locale Testing

### Locale Test Helper
```php
trait WithArabicLocale
{
    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('ar');
    }

    protected function assertArabicMessage($response, string $expectedMessage): void
    {
        $response->assertJsonPath('message', $expectedMessage);
    }

    protected function assertArabicError($response, string $errorCode, string $expectedArabicMessage): void
    {
        $response->assertJson([
            'error_code' => $errorCode,
            'message' => $expectedArabicMessage,
        ]);
    }
}
```

### Locale Test Cases
```php
public function test_error_messages_in_arabic(): void
{
    $response = $this->postJson('/api/v1/auth/login', [
        'phone' => 'not_a_phone',
        'pin' => '1234',
    ]);

    $response->assertStatus(422);
    $response->assertJsonPath('message', 'رقم الهاتف غير صالح');
}

public function test_amount_formatting_in_arabic(): void
{
    $response = $this->getJson('/api/v1/wallet/balance');
    $response->assertJsonPath('data.formatted_balance', '١٬٠٠٠٬٠٠٠ ل.س');
}
```

## Performance Testing (k6)

### Test Structure
```javascript
// tests/performance/transfer.spec.js
import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate, Trend } from 'k6/metrics';

const errorRate = new Rate('errors');
const transferDuration = new Trend('transfer_duration');

export const options = {
    stages: [
        { duration: '2m', target: 50 },  // Ramp up
        { duration: '5m', target: 50 },  // Steady
        { duration: '2m', target: 100 }, // Ramp up
        { duration: '5m', target: 100 }, // Steady
        { duration: '2m', target: 0 },   // Ramp down
    ],
    thresholds: {
        http_req_duration: ['p(95)<2000'], // 95% under 2s
        errors: ['rate<0.05'],             // <5% error rate
    },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';

export default function () {
    const payload = JSON.stringify({
        recipient_phone: `+963944${Math.floor(Math.random() * 1000000)}`,
        amount: Math.floor(Math.random() * 50000) + 1000,
        pin: '1234',
    });

    const params = {
        headers: { 'Content-Type': 'application/json' },
    };

    const res = http.post(`${BASE_URL}/api/v1/transfers`, payload, params);

    check(res, {
        'status is 200 or 429': (r) => r.status === 200 || r.status === 429,
        'response time < 3s': (r) => r.timings.duration < 3000,
    });

    errorRate.add(res.status !== 200 && res.status !== 429);
    transferDuration.add(res.timings.duration);

    sleep(1);
}
```

### Performance Test Scenarios
| Scenario | Description | Target |
|----------|-------------|--------|
| Transfer creation | Concurrent transfers | 100 req/s, p95 < 2s |
| Wallet balance read | Read-after-write consistency | 500 req/s, p95 < 200ms |
| Login + token | Auth flow | 50 req/s, p95 < 1s |
| KYC document upload | File upload | 10 req/s, p95 < 5s |
| Search transactions | Paginated search | 50 req/s, p95 < 1s |
| Push notifications | Mass notification send | 200 msg/s |

### CI Integration
```yaml
# .github/workflows/performance.yml
performance-test:
    runs-on: ubuntu-latest
    steps:
        - uses: actions/checkout@v4
        - name: Start app
          run: docker compose up -d
        - name: Run k6 tests
          run: |
              docker run --rm \
                  -v ${{ github.workspace }}/tests/performance:/tests \
                  -e BASE_URL=http://host.docker.internal:8000 \
                  grafana/k6 run /tests/transfer.spec.js
```
