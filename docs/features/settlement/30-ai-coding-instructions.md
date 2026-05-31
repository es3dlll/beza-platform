# Settlement AI Coding Instructions

## Implementation Priority

### Sprint 1 — Core Engine (Week 1-2)
```
Priority: P0
Files: 14, 16, 17, 18, 19, 20

1. Laravel Module Setup
   - Create app/Modules/Settlement/ structure
   - Register SettlementServiceProvider
   - Create all migrations (16-database-schema.md)
   - Seed default settlement accounts

2. Enums
   - BatchStatus, BatchType, ItemStatus
   - EntityType, PaymentOrderStatus
   - ReconciliationStatus, ExceptionType, ExceptionSeverity, ExceptionStatus

3. Models (17-domain-models.md)
   - SettlementBatch, SettlementBatchItem
   - SettlementPaymentOrder, SettlementReconciliation, SettlementReconciliationItem
   - SettlementException, SettlementAccount

4. Core Services (14-backend-architecture.md)
   - SettlementService (executeEndOfDay, executeRealTime)
   - BatchService (create, process, settle, hold, release)
   - NettingService (calculate, calculateForBatch)
   - AccountingService (createSettlementJournals, postSettlementEntries)

5. Database indexes and constraints
6. Migration triggers for batch number generation
```

### Sprint 2 — Batch Processing (Week 3-4)
```
Priority: P0
Files: 15, 19, 21

1. BatchController
   - create(), process(), show(), index()

2. PaymentOrderService
   - generate(), generateAndTransmit(), transmit(), confirm()
   - CSV file generation (MVP)

3. PaymentOrderController
   - generate only (transmit is cron-driven)

4. Console Commands
   - ProcessPendingBatches
   - TransmitPendingPaymentOrders
   - RunEndOfDaySettlement (cron: 23:00 daily)

5. Events and Listeners
   - BatchCreated, BatchProcessing, BatchSettled
   - PaymentOrderGenerated, PaymentOrderTransmitted

6. Queue configuration for settlement-high queue
```

### Sprint 3 — Reconciliation & Exceptions (Week 5-6)
```
Priority: P0
Files: 14, 15, 16, 19

1. ReconciliationService
   - runForBatch(), matchItem()

2. ReconciliationController
   - run(), showByDate()

3. ExceptionService
   - createFromMismatch(), resolve(), getOpenExceptions()

4. ExceptionController
   - index(), resolve()

5. ReconciliationRepository, ExceptionRepository

6. Exception detection logic in ReconciliationService::matchItem
7. Batch hold/release integration with ExceptionService
```

### Sprint 4 — Reports & Monitoring (Week 7-8)
```
Priority: P1
Files: 14, 22, 25

1. SettlementReportService
   - generateDailyReport(), generateMonthlyReport()

2. ReportController (or CLI commands)

3. Console Commands
   - GenerateDailySettlementReport (cron: 00:30)
   - PollBankConfirmations (cron: every 2 min)

4. Health check endpoint
5. Grafana dashboard configuration
6. Prometheus metrics integration
7. Logging channels (settlement, settlement-exceptions, settlement-audit)
```

### Sprint 5 — Real-Time Settlement (Week 9-10)
```
Priority: P1
Files: 14, 19, 20

1. Real-time flow in SettlementService::executeRealTime
2. Two-phase commit in AccountingService
3. Immediate payment order generation + transmission
4. Real-time confirmation polling
5. Real-time CFE posting
```

### Sprint 6 — AI Integration & Polish (Week 11-12)
```
Priority: P2
Files: 28

1. AI ExceptionClassifier service
2. PredictiveExceptionDetector
3. AIExceptionResolver with ML model
4. NLP query integration for settlement reports
5. Anomaly detection
```

## Code Generation Patterns

### Repository Pattern
```php
// Follow this pattern for all repositories
class BatchRepository
{
    public function __construct(protected SettlementBatch $model) {}

    public function create(array $data): SettlementBatch
    {
        return $this->model->create($data);
    }

    public function findById(int $id): ?SettlementBatch
    {
        return $this->model->with(['items', 'paymentOrders', 'exceptions'])->find($id);
    }

    public function findByStatus(BatchStatus $status): Collection
    {
        return $this->model->where('status', $status)->get();
    }

    public function findByDate(string $date): Collection
    {
        return $this->model->whereDate('created_at', $date)->get();
    }

    public function getTodayCount(): int
    {
        return $this->model->whereDate('created_at', today())->count();
    }
}
```

### Service Injection Pattern
```php
// Always inject services through constructor
class BatchService
{
    public function __construct(
        private BatchRepository $batchRepo,
        private BatchItemRepository $itemRepo,
        private NettingService $nettingService,
        private PaymentOrderService $orderService,
        private AccountingService $accountingService,
        private EventService $eventService,
    ) {}
}
```

### Transaction Boundaries
```php
// Batch mutations must be wrapped in DB::transaction
public function create(array $data, array $transactions): SettlementBatch
{
    DB::beginTransaction();
    try {
        // ... business logic ...
        DB::commit();
        $this->eventService->emit(new BatchCreated($batch));
        return $batch;
    } catch (\Throwable $e) {
        DB::rollBack();
        throw $e;
    }
}
```

### Event Emission Pattern
```php
// Events emitted AFTER transaction commit
// Events carry domain objects, not raw data
$this->eventService->emit(new BatchSettled(
    batch: $batch,
    matchRate: $recon->match_rate,
    settledItemCount: $batch->items->where('status', 'matched')->count(),
    totalNetAmount: $batch->net_amount,
    exceptionCount: $batch->exceptions->where('status', '!=', 'resolved')->count(),
));
```

### API Response Pattern
```php
// Consistent response structure
return response()->json([
    'status' => 'success',
    'data' => [
        // ... resource data ...
    ],
], 201);

// Error response
return response()->json([
    'status' => 'error',
    'error' => [
        'code' => 'VALIDATION_ERROR',
        'message' => 'بيانات الإدخال غير صحيحة',
        'details' => [
            'field' => ['error message in Arabic'],
        ],
    ],
], 400);
```

## Key Domain Rules (Must Enforce)

```php
// 1. Settlement pool must zero out after each EOD
assert($poolBalance === 0, 'Settlement pool must balance to 0 at EOD');

// 2. No batch can be processed twice
assert($batch->status === BatchStatus::DRAFT, 'Cannot process an already-processed batch');

// 3. Exceptions must hold the entire batch
assert($exception->batch->status === BatchStatus::ON_HOLD, 'Exception must trigger batch hold');

// 4. Real-time settlement must use two-phase commit
// Phase 1: prepare (reserve funds)
// Phase 2: commit (post entries)
// If either phase fails, full rollback

// 5. Netting must be idempotent
// Running netting twice on same batch produces same result

// 6. Payment orders must be unique per entity per batch
assert(!SettlementPaymentOrder::where('batch_id', $batch->id)
    ->where('entity_id', $entityId)->exists(), 'Duplicate payment order');
```

## Testing Requirements

### Minimum Test Coverage
```php
// Services: 90% coverage
// Controllers: 85% coverage
// Actions/Jobs: 80% coverage
// Models: 100% coverage (relationships, scopes)

// Every batch operation must have:
// 1. Happy path test
// 2. Failure path test
// 3. Idempotency test
// 4. Edge case (zero amounts, single item, many items)
```

## Deployment Checklist
```markdown
Before production deployment:
- [ ] Run all migrations
- [ ] Seed settlement_accounts
- [ ] Configure bank integrations in config/banks.php
- [ ] Set up Horizon workers for settlement queues
- [ ] Configure cron schedules
- [ ] Set up Grafana dashboards
- [ ] Configure log channels
- [ ] Set up monitoring alerts
- [ ] Run full E2E test suite
- [ ] Verify bank connectivity
- [ ] Train ops team on manual runbooks
- [ ] Schedule holiday settlements
```
