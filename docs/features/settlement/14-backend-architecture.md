# Settlement Backend Architecture

## Module Structure (Laravel)
```
app/Modules/Settlement/
├── Controllers/
│   ├── BatchController.php             # Batch CRUD + process
│   ├── ReconciliationController.php    # Reconciliation run + results
│   ├── PaymentOrderController.php      # Payment order generation
│   ├── ExceptionController.php         # Exception CRUD + resolve
│   ├── NettingController.php           # Net position queries
│   └── ReportController.php            # Settlement reports
│
├── Actions/
│   ├── CreateBatchAction.php           # Aggregate transactions → batch
│   ├── ProcessBatchAction.php          # Run netting + generate orders
│   ├── SettleBatchAction.php           # Confirm settlement + post to CFE
│   ├── RejectBatchAction.php           # Reject and rollback
│   ├── RunReconciliationAction.php     # Match internal vs external
│   ├── GeneratePaymentOrdersAction.php # Create payment files
│   ├── ResolveExceptionAction.php      # Resolve mismatch
│   ├── HoldBatchAction.php             # Place batch on hold
│   ├── ReleaseBatchAction.php          # Release batch from hold
│   └── CloseDayAction.php              # End-of-day finalization
│
├── Services/
│   ├── SettlementService.php           # Core orchestration
│   ├── BatchService.php                # Create, process, settle batches
│   ├── NettingService.php              # Calculate net positions
│   ├── ReconciliationService.php       # Match internal vs external
│   ├── PaymentOrderService.php         # Generate payment files
│   ├── ExceptionService.php            # Detect + manage exceptions
│   ├── SettlementReportService.php     # Generate reports
│   ├── AccountingService.php           # Double-entry journal creation
│   └── CutOffService.php               # Cut-off time management
│
├── Repositories/
│   ├── BatchRepository.php
│   ├── BatchItemRepository.php
│   ├── PaymentOrderRepository.php
│   ├── ReconciliationRepository.php
│   ├── ExceptionRepository.php
│   └── SettlementAccountRepository.php
│
├── Models/
│   ├── SettlementBatch.php
│   ├── SettlementBatchItem.php
│   ├── SettlementPaymentOrder.php
│   ├── SettlementReconciliation.php
│   ├── SettlementReconciliationItem.php
│   ├── SettlementException.php
│   └── SettlementAccount.php
│
├── Enums/
│   ├── BatchStatus.php                 # draft, processing, awaiting_confirmation, on_hold, settled, failed
│   ├── BatchType.php                   # eod, realtime
│   ├── ItemStatus.php                  # pending, matched, unmatched
│   ├── EntityType.php                  # bank, biller, merchant, agent, internal
│   ├── PaymentOrderStatus.php          # generated, transmitted, confirmed, rejected
│   ├── ReconciliationStatus.php        # pending, matched, partially_matched, unmatched
│   ├── ExceptionType.php               # amount_mismatch, missing_confirmation, duplicate, rejected
│   ├── ExceptionSeverity.php           # low, medium, high, critical
│   └── ExceptionStatus.php             # open, investigating, resolved, closed
│
├── Jobs/
│   ├── ProcessBatchJob.php             # Async batch processing
│   ├── TransmitPaymentOrderJob.php     # Send to bank via API
│   ├── PollBankConfirmationJob.php     # Check bank for confirmations
│   ├── RunScheduledReconciliation.php  # Auto reconciliation after cut-off
│   ├── CloseDayJob.php                 # EOD close
│   └── RetryFailedPaymentJob.php       # Retry transmission
│
├── Events/
│   ├── BatchCreated.php
│   ├── BatchProcessing.php
│   ├── BatchSettled.php
│   ├── BatchOnHold.php
│   ├── BatchReleased.php
│   ├── BatchFailed.php
│   ├── PaymentOrderGenerated.php
│   ├── PaymentOrderTransmitted.php
│   ├── PaymentOrderConfirmed.php
│   ├── PaymentOrderRejected.php
│   ├── ReconciliationCompleted.php
│   ├── ExceptionCreated.php
│   ├── ExceptionResolved.php
│   └── DayClosed.php
│
├── Listeners/
│   ├── TriggerReconciliationOnConfirm.php
│   ├── NotifyOpsOnException.php
│   ├── NotifyOpsOnBatchFailed.php
│   ├── UpdateSettlementDashboard.php
│   └── LogSettlementAuditTrail.php
│
├── Rules/
│   ├── ValidBatchCutOff.php
│   ├── SufficientSettlementBalance.php
│   ├── ValidReconciliationWindow.php
│   └── ValidPaymentOrderAmount.php
│
├── Exceptions/
│   ├── BatchNotFoundException.php
│   ├── BatchAlreadyProcessedException.php
│   ├── BatchOnHoldException.php
│   ├── InsufficientSettlementBalanceException.php
│   ├── PaymentOrderTransmissionFailedException.php
│   ├── ReconciliationAlreadyRunException.php
│   ├── ExceptionAlreadyResolvedException.php
│   └── CutOffTimePassedException.php
│
├── Providers/
│   └── SettlementServiceProvider.php
│
├── Console/
│   └── Commands/
│       ├── ProcessPendingBatches.php           # Cron: every 5 min
│       ├── TransmitPendingPaymentOrders.php    # Cron: every minute
│       ├── PollBankConfirmations.php           # Cron: every 2 min
│       ├── RunEndOfDaySettlement.php           # Cron: 23:00 daily
│       └── GenerateDailySettlementReport.php   # Cron: 00:30 daily
│
└── routes/
    └── api.php
```

## Service Layer Detail

### SettlementService
```php
class SettlementService
{
    public function __construct(
        private BatchService $batchService,
        private NettingService $nettingService,
        private PaymentOrderService $orderService,
        private ReconciliationService $reconciliationService,
        private ExceptionService $exceptionService,
        private SettlementReportService $reportService,
        private AccountingService $accountingService,
        private CutOffService $cutOffService,
        private EventService $eventService,
    ) {}

    public function executeEndOfDay(string $cutOffTime): SettlementBatch
    {
        // 1. Validate cut-off
        $this->cutOffService->validateCutOff($cutOffTime);

        // 2. Collect all pending transactions since last cut-off
        $transactions = $this->batchService->collectPendingTransactions($cutOffTime);

        // 3. Create batch
        $batch = $this->batchService->create([
            'type' => BatchType::EOD,
            'cut_off_time' => $cutOffTime,
            'transaction_count' => count($transactions),
            'total_amount' => array_sum(array_column($transactions, 'amount')),
        ], $transactions);

        // 4. Process batch (net + generate orders)
        $this->batchService->process($batch);

        // 5. Transmit payment orders
        $this->orderService->transmitAll($batch);

        // 6. Wait for confirmations (async — return batch early)
        $this->eventService->emit(new BatchProcessing($batch));

        return $batch;
    }

    public function executeRealTime(Transaction $transaction): SettlementBatch
    {
        // 1. Create single-transaction batch
        $batch = $this->batchService->create([
            'type' => BatchType::REALTIME,
            'cut_off_time' => now(),
            'transaction_count' => 1,
            'total_amount' => $transaction->amount,
        ], [$transaction]);

        // 2. Immediate netting
        $this->batchService->process($batch);

        // 3. Generate and transmit payment order immediately
        $order = $this->orderService->generateAndTransmit($batch);

        // 4. Post to CFE instantly (two-phase)
        $this->accountingService->postSettlementEntries($batch);

        // 5. Mark batch as settled
        $this->batchService->settle($batch);

        $this->eventService->emit(new BatchSettled($batch));

        return $batch;
    }
}
```

### BatchService
```php
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

    public function create(array $data, array $transactions): SettlementBatch
    {
        DB::beginTransaction();
        try {
            // 1. Group transactions by entity
            $grouped = $this->groupByEntity($transactions);

            // 2. Create batch record
            $batch = $this->batchRepo->create([
                'batch_number' => $this->generateBatchNumber($data['type']),
                'type' => $data['type'],
                'status' => BatchStatus::DRAFT,
                'cut_off_time' => $data['cut_off_time'],
                'transaction_count' => $data['transaction_count'],
                'total_amount' => $data['total_amount'],
                'currency' => Currency::SYP,
            ]);

            // 3. Create batch items
            foreach ($grouped as $entityType => $entityTransactions) {
                foreach ($entityTransactions as $entityId => $txns) {
                    $this->itemRepo->create([
                        'batch_id' => $batch->id,
                        'entity_type' => $entityType,
                        'entity_id' => $entityId,
                        'transaction_count' => count($txns),
                        'total_debit' => $this->sumDebits($txns),
                        'total_credit' => $this->sumCredits($txns),
                        'status' => ItemStatus::PENDING,
                        'metadata' => json_encode(['transactions' => array_column($txns, 'id')]),
                    ]);
                }
            }

            DB::commit();
            $this->eventService->emit(new BatchCreated($batch));

            return $batch;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function process(SettlementBatch $batch): void
    {
        DB::beginTransaction();
        try {
            // 1. Update status
            $batch->status = BatchStatus::PROCESSING;
            $batch->save();

            // 2. Run netting
            $netResults = $this->nettingService->calculateForBatch($batch);

            // 3. Generate payment orders
            $this->orderService->generate($batch, $netResults);

            // 4. Create CFE journal entries
            $this->accountingService->createSettlementJournals($batch, $netResults);

            // 5. Update batch
            $batch->net_amount = $netResults['net_amount'];
            $batch->status = BatchStatus::AWAITING_CONFIRMATION;
            $batch->processed_at = now();
            $batch->save();

            DB::commit();
            $this->eventService->emit(new BatchProcessing($batch));
        } catch (\Throwable $e) {
            DB::rollBack();
            $batch->status = BatchStatus::FAILED;
            $batch->failure_reason = $e->getMessage();
            $batch->save();
            throw $e;
        }
    }

    public function settle(SettlementBatch $batch): void
    {
        $batch->status = BatchStatus::SETTLED;
        $batch->settled_at = now();
        $batch->save();

        foreach ($batch->items as $item) {
            $item->status = ItemStatus::MATCHED;
            $item->save();
        }

        $this->eventService->emit(new BatchSettled($batch));
    }

    public function hold(SettlementBatch $batch, string $reason): void
    {
        $batch->status = BatchStatus::ON_HOLD;
        $batch->hold_reason = $reason;
        $batch->held_at = now();
        $batch->save();

        $this->eventService->emit(new BatchOnHold($batch, $reason));
    }

    public function release(SettlementBatch $batch): void
    {
        $batch->status = BatchStatus::AWAITING_CONFIRMATION;
        $batch->hold_reason = null;
        $batch->released_at = now();
        $batch->save();

        $this->eventService->emit(new BatchReleased($batch));
    }

    private function generateBatchNumber(BatchType $type): string
    {
        $prefix = $type === BatchType::EOD ? 'STL' : 'RT';
        $date = now()->format('Ymd');
        $sequence = str_pad($this->batchRepo->getTodayCount() + 1, 4, '0', STR_PAD_LEFT);
        return "{$prefix}-{$date}-{$sequence}";
    }

    private function groupByEntity(array $transactions): array
    {
        $grouped = [];
        foreach ($transactions as $txn) {
            $entityType = $txn['settlement_entity_type'];
            $entityId = $txn['settlement_entity_id'];
            $grouped[$entityType][$entityId][] = $txn;
        }
        return $grouped;
    }

    private function sumDebits(array $txns): int
    {
        return array_sum(array_map(fn($t) => $t['direction'] === 'debit' ? $t['amount'] : 0, $txns));
    }

    private function sumCredits(array $txns): int
    {
        return array_sum(array_map(fn($t) => $t['direction'] === 'credit' ? $t['amount'] : 0, $txns));
    }
}
```

### NettingService
```php
class NettingService
{
    public function __construct(
        private BatchItemRepository $itemRepo,
        private SettlementAccountRepository $accountRepo,
    ) {}

    public function calculate(SettlementBatch $batch): NettingResult
    {
        $netPositions = [];

        foreach ($batch->items as $item) {
            $position = $this->calculateNetPosition($item);
            $netPositions[] = [
                'entity_type' => $item->entity_type,
                'entity_id' => $item->entity_id,
                'total_debit' => $item->total_debit,
                'total_credit' => $item->total_credit,
                'net_amount' => $position,
                'direction' => $position >= 0 ? 'receive' : 'pay',
                'settlement_account' => $this->resolveSettlementAccount($item->entity_type, $item->entity_id),
            ];
        }

        return new NettingResult($netPositions, $batch);
    }

    public function calculateForBatch(SettlementBatch $batch): array
    {
        return $this->calculate($batch)->toArray();
    }

    private function calculateNetPosition(SettlementBatchItem $item): int
    {
        // Net = Credits (owed to entity) - Debits (owed from entity)
        return $item->total_credit - $item->total_debit;
    }

    private function resolveSettlementAccount(string $entityType, string $entityId): string
    {
        $account = $this->accountRepo->findByEntity($entityType, $entityId);
        return $account?->cfe_account_id ?? $this->accountRepo->getDefaultAccount($entityType)->cfe_account_id;
    }
}
```

### ReconciliationService
```php
class ReconciliationService
{
    public function __construct(
        private BatchRepository $batchRepo,
        private BatchItemRepository $itemRepo,
        private ReconciliationRepository $reconRepo,
        private ExceptionService $exceptionService,
        private EventService $eventService,
    ) {}

    public function runForBatch(SettlementBatch $batch): SettlementReconciliation
    {
        $recon = $this->reconRepo->create([
            'batch_id' => $batch->id,
            'date' => today(),
            'status' => ReconciliationStatus::PENDING,
            'total_items' => $batch->items->count(),
            'total_internal_amount' => $batch->total_amount,
        ]);

        $matchedItems = 0;
        $unmatchedItems = 0;

        foreach ($batch->items as $item) {
            $externalAmount = $this->getExternalAmount($item);

            $matchResult = $this->matchItem($item, $externalAmount);

            $this->reconRepo->createItem([
                'reconciliation_id' => $recon->id,
                'batch_item_id' => $item->id,
                'internal_amount' => $this->getInternalAmount($item),
                'external_amount' => $externalAmount,
                'difference' => $this->getInternalAmount($item) - $externalAmount,
                'status' => $matchResult['status'],
                'match_type' => $matchResult['type'],
            ]);

            if ($matchResult['status'] === 'matched') {
                $matchedItems++;
                $item->status = ItemStatus::MATCHED;
            } else {
                $unmatchedItems++;
                $item->status = ItemStatus::UNMATCHED;
                $this->exceptionService->createFromMismatch($item, $matchResult);
            }
            $item->save();
        }

        $recon->matched_items = $matchedItems;
        $recon->unmatched_items = $unmatchedItems;
        $recon->match_rate = $batch->items->count() > 0
            ? round(($matchedItems / $batch->items->count()) * 100, 2)
            : 0;
        $recon->status = $unmatchedItems > 0 ? ReconciliationStatus::PARTIALLY_MATCHED : ReconciliationStatus::MATCHED;
        $recon->completed_at = now();
        $recon->save();

        // If any mismatches, hold the batch
        if ($unmatchedItems > 0) {
            $this->eventService->emit(new ReconciliationCompleted($recon));
        }

        return $recon;
    }

    private function matchItem(SettlementBatchItem $item, int $externalAmount): array
    {
        $internalAmount = $this->getInternalAmount($item);

        if ($internalAmount === $externalAmount) {
            return ['status' => 'matched', 'type' => 'exact'];
        }

        $difference = abs($internalAmount - $externalAmount);

        if ($difference <= 100) { // Tolerance: 100 SYP
            return ['status' => 'matched', 'type' => 'within_tolerance'];
        }

        return ['status' => 'unmatched', 'type' => 'amount_mismatch', 'difference' => $difference];
    }

    private function getInternalAmount(SettlementBatchItem $item): int
    {
        return abs($item->total_debit - $item->total_credit);
    }

    private function getExternalAmount(SettlementBatchItem $item): int
    {
        // Query bank confirmation or external statement
        // Fallback to internal if no external data yet
        return $item->external_confirmed_amount ?? $this->getInternalAmount($item);
    }
}
```

### PaymentOrderService
```php
class PaymentOrderService
{
    public function __construct(
        private PaymentOrderRepository $orderRepo,
        private BankIntegrationService $bankService,
        private EventService $eventService,
    ) {}

    public function generate(SettlementBatch $batch, array $netResults): array
    {
        $orders = [];

        foreach ($netResults as $netResult) {
            if ($netResult['net_amount'] === 0) {
                continue; // No movement needed
            }

            $order = $this->orderRepo->create([
                'batch_id' => $batch->id,
                'entity_type' => $netResult['entity_type'],
                'entity_id' => $netResult['entity_id'],
                'direction' => $netResult['direction'],
                'amount' => abs($netResult['net_amount']),
                'currency' => Currency::SYP,
                'settlement_account' => $netResult['settlement_account'],
                'status' => PaymentOrderStatus::GENERATED,
                'file_format' => 'ISO_20022_CAMT_053',
            ]);

            $orders[] = $order;
        }

        $this->eventService->emit(new PaymentOrderGenerated($batch, $orders));

        return $orders;
    }

    public function generateAndTransmit(SettlementBatch $batch): SettlementPaymentOrder
    {
        $netResults = app(NettingService::class)->calculateForBatch($batch);
        $orders = $this->generate($batch, $netResults);

        foreach ($orders as $order) {
            $this->transmit($order);
        }

        return $orders[0] ?? throw new \RuntimeException('No payment orders generated');
    }

    public function transmit(SettlementPaymentOrder $order): void
    {
        try {
            $fileContent = $this->buildPaymentFile($order);
            $response = $this->bankService->sendPaymentFile($fileContent, $order->entity_type, $order->entity_id);

            $order->status = PaymentOrderStatus::TRANSMITTED;
            $order->transmitted_at = now();
            $order->external_reference = $response['reference'] ?? null;
            $order->save();

            $this->eventService->emit(new PaymentOrderTransmitted($order));
        } catch (\Throwable $e) {
            $order->status = PaymentOrderStatus::REJECTED;
            $order->failure_reason = $e->getMessage();
            $order->save();

            $this->eventService->emit(new PaymentOrderRejected($order));
            throw new PaymentOrderTransmissionFailedException($order->id, $e->getMessage());
        }
    }

    public function transmitAll(SettlementBatch $batch): void
    {
        $orders = $this->orderRepo->findByBatch($batch->id);
        foreach ($orders as $order) {
            try {
                $this->transmit($order);
            } catch (PaymentOrderTransmissionFailedException $e) {
                Log::error("Payment order {$order->id} transmission failed: {$e->getMessage()}");
                // Continue with other orders — batch will be on hold
            }
        }
    }

    public function confirm(SettlementPaymentOrder $order, int $confirmedAmount, string $bankReference): void
    {
        $order->status = PaymentOrderStatus::CONFIRMED;
        $order->confirmed_amount = $confirmedAmount;
        $order->bank_reference = $bankReference;
        $order->confirmed_at = now();
        $order->save();

        $this->eventService->emit(new PaymentOrderConfirmed($order));
    }

    private function buildPaymentFile(SettlementPaymentOrder $order): string
    {
        // ISO 20022 camt.053 format or custom CSV
        // For MVP: CSV format
        $header = "Reference,Amount,Currency,SettlementAccount,ValueDate";
        $line = implode(',', [
            $order->id,
            abs($order->amount),
            $order->currency,
            $order->settlement_account,
            now()->format('Y-m-d'),
        ]);
        return $header . "\n" . $line;
    }
}
```

### ExceptionService
```php
class ExceptionService
{
    public function __construct(
        private ExceptionRepository $exceptionRepo,
        private BatchService $batchService,
        private EventService $eventService,
    ) {}

    public function createFromMismatch(SettlementBatchItem $item, array $matchResult): SettlementException
    {
        $exception = $this->exceptionRepo->create([
            'batch_id' => $item->batch_id,
            'batch_item_id' => $item->id,
            'type' => ExceptionType::AMOUNT_MISMATCH,
            'severity' => $this->determineSeverity($matchResult['difference'] ?? 0),
            'status' => ExceptionStatus::OPEN,
            'internal_amount' => $matchResult['internal_amount'] ?? 0,
            'external_amount' => $matchResult['external_amount'] ?? 0,
            'difference' => $matchResult['difference'] ?? 0,
            'description' => "Amount mismatch: internal={$matchResult['internal_amount']}, external={$matchResult['external_amount']}",
        ]);

        // Hold the batch
        $this->batchService->hold($item->batch, "Exception {$exception->id}: amount mismatch");

        $this->eventService->emit(new ExceptionCreated($exception));

        return $exception;
    }

    public function resolve(int $exceptionId, string $resolutionType, string $notes, ?int $userId): SettlementException
    {
        $exception = $this->exceptionRepo->findById($exceptionId);
        if (!$exception) {
            throw new ExceptionNotFoundException($exceptionId);
        }
        if ($exception->status === ExceptionStatus::RESOLVED || $exception->status === ExceptionStatus::CLOSED) {
            throw new ExceptionAlreadyResolvedException($exceptionId);
        }

        $exception->status = ExceptionStatus::RESOLVED;
        $exception->resolution_type = $resolutionType;
        $exception->resolution_notes = $notes;
        $exception->resolved_by = $userId;
        $exception->resolved_at = now();
        $exception->save();

        // Check if all exceptions in batch are resolved → release batch
        $remainingExceptions = $this->exceptionRepo->countOpenByBatch($exception->batch_id);
        if ($remainingExceptions === 0) {
            $batch = $exception->batch;
            $this->batchService->release($batch);
        }

        $this->eventService->emit(new ExceptionResolved($exception));

        return $exception;
    }

    public function getOpenExceptions(array $filters = []): Collection
    {
        return $this->exceptionRepo->findOpen($filters);
    }

    private function determineSeverity(int $difference): string
    {
        return match (true) {
            $difference >= 1000000 => ExceptionSeverity::CRITICAL,
            $difference >= 100000 => ExceptionSeverity::HIGH,
            $difference >= 10000 => ExceptionSeverity::MEDIUM,
            default => ExceptionSeverity::LOW,
        };
    }
}
```

### SettlementReportService
```php
class SettlementReportService
{
    public function __construct(
        private BatchRepository $batchRepo,
        private ReconciliationRepository $reconRepo,
        private ExceptionRepository $exceptionRepo,
        private PaymentOrderRepository $orderRepo,
    ) {}

    public function generateDailyReport(string $date): array
    {
        $batches = $this->batchRepo->findByDate($date);

        $totalBatches = $batches->count();
        $settledBatches = $batches->where('status', BatchStatus::SETTLED)->count();
        $failedBatches = $batches->where('status', BatchStatus::FAILED)->count();
        $onHoldBatches = $batches->where('status', BatchStatus::ON_HOLD)->count();

        $totalTransactions = $batches->sum('transaction_count');
        $totalAmount = $batches->sum('total_amount');
        $totalNetAmount = $batches->sum('net_amount');

        $recon = $this->reconRepo->findByDate($date);
        $matchRate = $recon?->avg('match_rate') ?? 0;

        $exceptions = $this->exceptionRepo->findByDate($date);
        $openExceptions = $exceptions->where('status', ExceptionStatus::OPEN)->count();
        $resolvedExceptions = $exceptions->whereIn('status', [ExceptionStatus::RESOLVED, ExceptionStatus::CLOSED])->count();

        $paymentOrders = $this->orderRepo->findByDate($date);
        $confirmedOrders = $paymentOrders->where('status', PaymentOrderStatus::CONFIRMED)->count();
        $pendingOrders = $paymentOrders->where('status', PaymentOrderStatus::TRANSMITTED)->count();
        $rejectedOrders = $paymentOrders->where('status', PaymentOrderStatus::REJECTED)->count();

        return [
            'date' => $date,
            'summary' => [
                'total_batches' => $totalBatches,
                'settled_batches' => $settledBatches,
                'failed_batches' => $failedBatches,
                'on_hold_batches' => $onHoldBatches,
                'settlement_rate' => $totalBatches > 0 ? round(($settledBatches / $totalBatches) * 100, 2) : 0,
            ],
            'volume' => [
                'total_transactions' => $totalTransactions,
                'total_amount' => $totalAmount,
                'total_net_amount' => $totalNetAmount,
            ],
            'reconciliation' => [
                'match_rate' => $matchRate,
                'open_exceptions' => $openExceptions,
                'resolved_exceptions' => $resolvedExceptions,
            ],
            'payment_orders' => [
                'total' => $paymentOrders->count(),
                'confirmed' => $confirmedOrders,
                'pending' => $pendingOrders,
                'rejected' => $rejectedOrders,
                'confirmation_rate' => $paymentOrders->count() > 0
                    ? round(($confirmedOrders / $paymentOrders->count()) * 100, 2) : 0,
            ],
            'generated_at' => now()->toIso8601String(),
        ];
    }

    public function generateMonthlyReport(string $yearMonth): array
    {
        $startDate = Carbon::createFromFormat('Y-m', $yearMonth)->startOfMonth()->format('Y-m-d');
        $endDate = Carbon::createFromFormat('Y-m', $yearMonth)->endOfMonth()->format('Y-m-d');

        $dailyReports = [];
        $current = Carbon::parse($startDate);
        while ($current->lte(Carbon::parse($endDate))) {
            $dateStr = $current->format('Y-m-d');
            $dailyReports[$dateStr] = $this->generateDailyReport($dateStr);
            $current->addDay();
        }

        $aggregate = [
            'year_month' => $yearMonth,
            'total_batches' => array_sum(array_column($dailyReports, 'summary.total_batches')),
            'total_transactions' => array_sum(array_column($dailyReports, 'volume.total_transactions')),
            'total_amount' => array_sum(array_column($dailyReports, 'volume.total_amount')),
            'avg_match_rate' => array_sum(array_column($dailyReports, 'reconciliation.match_rate')) / max(count($dailyReports), 1),
            'total_exceptions' => array_sum(array_column($dailyReports, 'reconciliation.open_exceptions'))
                + array_sum(array_column($dailyReports, 'reconciliation.resolved_exceptions')),
            'daily_breakdown' => $dailyReports,
            'generated_at' => now()->toIso8601String(),
        ];

        return $aggregate;
    }
}
```

### AccountingService
```php
class AccountingService
{
    public function __construct(
        private CfeService $cfe,
    ) {}

    public function createSettlementJournals(SettlementBatch $batch, array $netResults): void
    {
        foreach ($netResults as $result) {
            if ($result['net_amount'] === 0) {
                continue;
            }

            $settlementAccount = $this->cfe->getAccount($result['settlement_account']);
            $counterpartyAccount = $this->cfe->getCounterpartyAccount($result['entity_type'], $result['entity_id']);
            $amount = abs($result['net_amount']);

            if ($result['direction'] === 'pay') {
                // Beza needs to pay the entity
                $this->cfe->debit($settlementAccount->id, $amount, "settlement-pay-{$batch->id}-{$result['entity_id']}");
                $this->cfe->credit($counterpartyAccount->id, $amount, "settlement-pay-{$batch->id}-{$result['entity_id']}");
            } else {
                // Entity needs to pay Beza
                $this->cfe->debit($counterpartyAccount->id, $amount, "settlement-receive-{$batch->id}-{$result['entity_id']}");
                $this->cfe->credit($settlementAccount->id, $amount, "settlement-receive-{$batch->id}-{$result['entity_id']}");
            }
        }
    }

    public function postSettlementEntries(SettlementBatch $batch): void
    {
        // Two-phase commit for real-time settlement
        $this->cfe->beginTransaction();
        try {
            $netResults = app(NettingService::class)->calculateForBatch($batch);
            $this->createSettlementJournals($batch, $netResults);
            $this->cfe->commit();
        } catch (\Throwable $e) {
            $this->cfe->rollback();
            throw $e;
        }
    }
}
```
