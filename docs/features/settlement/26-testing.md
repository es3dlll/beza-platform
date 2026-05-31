# Settlement Testing

## Test Pyramid

```
        ┌──────────┐
        │   E2E    │  ← 5 critical paths
        │  (5)     │
       ┌┴──────────┴┐
       │ Integration │  ← 30 test cases
       │   (30)      │
      ┌┴─────────────┴┐
      │   Unit Tests   │  ← 200+ test cases
      │   (200+)       │
      └────────────────┘
```

## Unit Tests

### SettlementServiceTest
```php
class SettlementServiceTest extends TestCase
{
    public function test_execute_end_of_day_creates_batch(): void
    {
        // Arrange
        $this->mockCutOffService->shouldReceive('validateCutOff')->once();
        $this->mockBatchService->shouldReceive('collectPendingTransactions')
            ->once()->andReturn($this->sampleTransactions());
        $this->mockBatchService->shouldReceive('create')
            ->once()->andReturn($this->sampleBatch());
        $this->mockBatchService->shouldReceive('process')->once();
        $this->mockPaymentOrderService->shouldReceive('transmitAll')->once();

        // Act
        $result = $this->service->executeEndOfDay('2026-05-29T23:00:00Z');

        // Assert
        $this->assertInstanceOf(SettlementBatch::class, $result);
        $this->assertEquals(BatchStatus::PROCESSING, $result->status);
    }

    public function test_execute_real_time_settlement(): void
    {
        $transaction = $this->sampleRealTimeTransaction();
        $this->mockBatchService->shouldReceive('create')->once()->andReturn($this->sampleBatch());
        $this->mockBatchService->shouldReceive('process')->once();
        $this->mockPaymentOrderService->shouldReceive('generateAndTransmit')->once();
        $this->mockAccountingService->shouldReceive('postSettlementEntries')->once();
        $this->mockBatchService->shouldReceive('settle')->once();

        $result = $this->service->executeRealTime($transaction);

        $this->assertEquals(BatchStatus::SETTLED, $result->status);
    }

    public function test_rejects_cut_off_after_time(): void
    {
        $this->mockCutOffService->shouldReceive('validateCutOff')
            ->andThrow(new CutOffTimePassedException());

        $this->expectException(CutOffTimePassedException::class);
        $this->service->executeEndOfDay('2026-05-29T23:01:00Z');
    }
}
```

### NettingServiceTest
```php
class NettingServiceTest extends TestCase
{
    public function test_calculates_net_position(): void
    {
        $batch = $this->createBatchWithItems([
            ['entity_type' => 'bank', 'total_debit' => 10000000, 'total_credit' => 50000000],
            ['entity_type' => 'biller', 'total_debit' => 20000000, 'total_credit' => 5000000],
        ]);

        $result = $this->service->calculate($batch);

        $this->assertCount(2, $result->positions);
        $this->assertEquals(40000000, $result->positions[0]['net_amount']); // 50M - 10M = 40M (pay)
        $this->assertEquals(-15000000, $result->positions[1]['net_amount']); // 5M - 20M = -15M (receive)
        $this->assertEquals(25000000, $result->netAmount); // 40M - 15M = 25M
    }

    public function test_zero_net_entity_returns_zero(): void
    {
        $batch = $this->createBatchWithItems([
            ['entity_type' => 'bank', 'total_debit' => 25000000, 'total_credit' => 25000000],
        ]);

        $result = $this->service->calculate($batch);

        $this->assertEquals(0, $result->positions[0]['net_amount']);
        $this->assertTrue($result->positions[0]['net_amount'] === 0);
    }
}
```

### ReconciliationServiceTest
```php
class ReconciliationServiceTest extends TestCase
{
    public function test_exact_match(): void
    {
        $batch = $this->createBatchWithExternalAmounts([
            ['internal' => 45000000, 'external' => 45000000],
        ]);

        $recon = $this->service->runForBatch($batch);

        $this->assertEquals('matched', $recon->status->value);
        $this->assertEquals(100.0, $recon->match_rate);
    }

    public function test_tolerance_match(): void
    {
        $batch = $this->createBatchWithExternalAmounts([
            ['internal' => 45000000, 'external' => 45000100],
        ]);

        $recon = $this->service->runForBatch($batch);

        $this->assertEquals('matched', $recon->status->value);
        $this->assertEquals(100.0, $recon->match_rate);
    }

    public function test_mismatch_creates_exception(): void
    {
        $batch = $this->createBatchWithExternalAmounts([
            ['internal' => 45000000, 'external' => 45005000],
        ]);

        $recon = $this->service->runForBatch($batch);

        $this->assertEquals('partially_matched', $recon->status->value);
        $this->assertEquals(0, $recon->match_rate);
        $this->assertDatabaseHas('settlement_exceptions', [
            'batch_id' => $batch->id,
            'type' => 'amount_mismatch',
        ]);
    }
}
```

## Integration Tests

### API Integration Tests
```php
class SettlementApiTest extends TestCase
{
    public function test_can_create_and_process_batch(): void
    {
        // Create batch
        $response = $this->postJson('/api/v1/settlement/batch/create', [
            'type' => 'eod',
            'cut_off_time' => '2026-05-29T23:00:00Z',
        ]);
        $response->assertStatus(201);
        $batchId = $response->json('data.batch_id');

        // Process batch
        $response = $this->postJson("/api/v1/settlement/batch/{$batchId}/process");
        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'awaiting_confirmation');

        // Get batch detail
        $response = $this->getJson("/api/v1/settlement/batch/{$batchId}");
        $response->assertStatus(200);
        $response->assertJsonPath('data.batch_id', $batchId);
    }

    public function test_full_reconciliation_cycle(): void
    {
        // Create and process batch
        $batch = $this->createSettledBatch();

        // Run reconciliation
        $response = $this->postJson('/api/v1/settlement/reconciliation/run', [
            'batch_id' => $batch->id,
        ]);
        $response->assertStatus(200);
        $reconId = $response->json('data.reconciliation_id');

        // Get reconciliation by date
        $date = now()->format('Y-m-d');
        $response = $this->getJson("/api/v1/settlement/reconciliation/{$date}");
        $response->assertStatus(200);
    }
}
```

## E2E Tests

### Critical Paths
| # | Path | Description |
|---|------|-------------|
| 1 | Happy EOD | Create → Process → Transmit → Confirm → Reconcile → Settle |
| 2 | Exception + Recovery | Mismatch → Exception → Batch Hold → Investigate → Resolve → Release → Settle |
| 3 | Real-Time Settlement | Single txn → Immediate batch → Net → Transmit → Confirm → Settle |
| 4 | Failed Transmission | Transmit → Reject → Retry → Confirm → Settle |
| 5 | Reconciliation Failure | Multiple mismatches → Exceptions → Hold → Escalate |

## Load Tests

### Scenarios
```php
// Load test using k6
export default function () {
    const BASE_URL = 'https://api.beza.sy/api/v1/settlement';

    // 1. Batch creation (10 concurrent)
    for (let i = 0; i < 10; i++) {
        http.post(`${BASE_URL}/batch/create`, {
            type: 'eod',
            cut_off_time: '2026-05-29T23:00:00Z',
        }, { tags: { name: 'create_batch' } });
    }

    // 2. Batch processing
    // 3. Payment order generation
    // 4. Reconciliation run
}

// Performance targets
// Batch creation: < 2s p95
// Batch processing: < 5s p95 for 10K items
// Reconciliation: < 10s p95 for 10K items
// Payment order generation: < 1s p95
```

## Testing Data Fixtures

```php
class SettlementFixtures
{
    public static function sampleTransactions(int $count = 100): array
    {
        return array_map(fn($i) => [
            'id' => "txn_{$i}",
            'amount' => rand(1000, 1000000),
            'direction' => rand(0, 1) ? 'debit' : 'credit',
            'settlement_entity_type' => ['bank', 'biller', 'merchant', 'agent'][rand(0, 3)],
            'settlement_entity_id' => "entity_{$i}",
            'currency' => 'SYP',
            'created_at' => now()->subHours(rand(1, 23)),
        ], range(1, $count));
    }
}
```
