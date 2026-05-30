# Settlement Event Architecture

## Event Flow Diagram

```
Transaction System          Settlement Engine             Bank Integration          Reconciliation
      │                          │                             │                        │
      │── Transaction Committed  │                             │                        │
      │─────────────────────────>│                             │                        │
      │                          │                             │                        │
      │                          │── BatchCreated              │                        │
      │                          │── BatchProcessing           │                        │
      │                          │                             │                        │
      │                          │── PaymentOrderGenerated     │                        │
      │                          │── PaymentOrderTransmitted ──>│                        │
      │                          │                             │── Confirm (API Poll)   │
      │                          │<── PaymentOrderConfirmed ───│                        │
      │                          │                             │                        │
      │                          │── ReconciliationCompleted ──────────────────────────>│
      │                          │                             │                        │
      │                          │── ExceptionCreated          │                        │
      │                          │                             │                        │
      │                          │── ExceptionResolved         │                        │
      │                          │                             │                        │
      │                          │── BatchSettled              │                        │
      │                          │                             │                        │
```

## Event Catalog

| Event | Emitter | Listeners | Description |
|-------|---------|-----------|-------------|
| `BatchCreated` | BatchService::create() | LogSettlementAuditTrail, UpdateSettlementDashboard | Batch created from collected transactions |
| `BatchProcessing` | BatchService::process() | NotifyOpsOnBatchProcessing | Netting done, payment orders generated |
| `BatchSettled` | BatchService::settle() | NotifyOpsOnBatchSettled, UpdateSettlementDashboard, LogSettlementAuditTrail | All items confirmed and reconciled |
| `BatchOnHold` | BatchService::hold() | NotifyOpsOnException, UpdateSettlementDashboard | Batch held due to exception |
| `BatchReleased` | BatchService::release() | UpdateSettlementDashboard | Hold released, batch continues |
| `BatchFailed` | BatchService::process() | NotifyOpsOnBatchFailed | Batch processing failure |
| `PaymentOrderGenerated` | PaymentOrderService::generate() | TransmitPendingPaymentOrders | Payment file created |
| `PaymentOrderTransmitted` | PaymentOrderService::transmit() | PollBankConfirmationJob | File sent to bank |
| `PaymentOrderConfirmed` | PaymentOrderService::confirm() | TriggerReconciliationOnConfirm, LogSettlementAuditTrail | Bank confirmed receipt |
| `PaymentOrderRejected` | PaymentOrderService::transmit() | RetryFailedPaymentJob, NotifyOpsOnException | Bank rejected payment |
| `ReconciliationCompleted` | ReconciliationService::runForBatch() | UpdateSettlementDashboard, LogSettlementAuditTrail | Match run finished, results available |
| `ExceptionCreated` | ExceptionService::createFromMismatch() | NotifyOpsOnException, UpdateSettlementDashboard | Settlement mismatch detected |
| `ExceptionResolved` | ExceptionService::resolve() | UpdateSettlementDashboard, LogSettlementAuditTrail | Exception resolved, batch may release |
| `DayClosed` | CloseDayJob | GenerateDailySettlementReport, ArchiveSettledBatches | EOD finalized |

## Event Payload Specifications

### BatchCreated
```php
class BatchCreated
{
    public function __construct(
        public readonly SettlementBatch $batch,
        public readonly int $transactionCount,
        public readonly int $totalAmount,
        public readonly array $entityTypes,   // ['bank', 'biller', 'merchant', 'agent']
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('settlement.ops'),
            new PrivateChannel('settlement.batch.' . $this->batch->id),
        ];
    }
}
```

### BatchSettled
```php
class BatchSettled
{
    public function __construct(
        public readonly SettlementBatch $batch,
        public readonly float $matchRate,
        public readonly int $settledItemCount,
        public readonly int $totalNetAmount,
        public readonly ?int $exceptionCount,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('settlement.ops')];
    }
}
```

### ExceptionCreated
```php
class ExceptionCreated
{
    public function __construct(
        public readonly SettlementException $exception,
        public readonly string $batchNumber,
        public readonly string $entityName,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('settlement.ops'),
            new PrivateChannel('settlement.exceptions'),
        ];
    }
}
```

## Channel Structure
```
settlement.ops           → All operations team members
settlement.batch.{id}    → Per-batch updates
settlement.exceptions    → Exception feed
settlement.reports       → Report generation notifications
```

## Queue Configuration
```php
// config/queue.php
'settlement' => [
    'connection' => 'redis',
    'queue' => 'settlement',
    'retry_after' => 300,
    'block_for' => 5,
],

// Job routing
'ProcessBatchJob' => 'settlement-high',
'TransmitPaymentOrderJob' => 'settlement-high',
'PollBankConfirmationJob' => 'settlement-medium',
'RunScheduledReconciliation' => 'settlement-low',
'RetryFailedPaymentJob' => 'settlement-medium',
```
