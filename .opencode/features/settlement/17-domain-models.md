# Settlement Domain Models

## Core Entities

### SettlementBatch
```php
class SettlementBatch
{
    public string $id;
    public string $batchNumber;       // STL-20260529-0001
    public BatchType $type;           // eod | realtime
    public BatchStatus $status;       // draft | processing | awaiting_confirmation | on_hold | settled | failed
    public string $currency;          // SYP | USD
    public Carbon $cutOffTime;
    public string $cutOffTimezone;

    // Volume
    public int $transactionCount;
    public int $totalDebit;
    public int $totalCredit;
    public int $totalAmount;
    public ?int $netAmount;

    // Timestamps
    public ?Carbon $processedAt;
    public ?Carbon $settledAt;
    public ?Carbon $heldAt;
    public ?Carbon $releasedAt;
    public ?string $holdReason;
    public ?string $failureReason;

    // Relationships
    public Collection $items;         // SettlementBatchItem[]
    public Collection $paymentOrders; // SettlementPaymentOrder[]
    public Collection $exceptions;    // SettlementException[]

    // Domain methods
    public function isProcessable(): bool
    {
        return $this->status === BatchStatus::DRAFT;
    }

    public function isSettleable(): bool
    {
        return $this->status === BatchStatus::AWAITING_CONFIRMATION
            && $this->exceptions->where('status', '!=', 'resolved')->isEmpty();
    }

    public function canHold(): bool
    {
        return in_array($this->status, [
            BatchStatus::PROCESSING,
            BatchStatus::AWAITING_CONFIRMATION,
        ]);
    }

    public function hold(string $reason): void
    {
        if (!$this->canHold()) {
            throw new \DomainException("Cannot hold batch in status: {$this->status->value}");
        }
        $this->status = BatchStatus::ON_HOLD;
        $this->holdReason = $reason;
        $this->heldAt = now();
    }

    public function release(): void
    {
        if ($this->status !== BatchStatus::ON_HOLD) {
            throw new \DomainException('Only held batches can be released');
        }
        $this->status = BatchStatus::AWAITING_CONFIRMATION;
        $this->holdReason = null;
        $this->releasedAt = now();
    }
}
```

### SettlementBatchItem
```php
class SettlementBatchItem
{
    public string $id;
    public string $batchId;

    // Entity
    public EntityType $entityType;    // bank | biller | merchant | agent | internal | cfe
    public string $entityId;
    public ?string $entityName;

    // Financial
    public int $totalDebit;
    public int $totalCredit;
    public int $netAmount;            // total_credit - total_debit (generated column)
    public int $transactionCount;

    // Status
    public ItemStatus $status;        // pending | matched | unmatched | excluded

    // External confirmation
    public ?int $externalConfirmedAmount;
    public ?Carbon $externalConfirmedAt;
    public ?string $externalReference;

    // Settlement account
    public ?string $settlementAccountId;

    // Domain methods
    public function getAbsoluteAmount(): int
    {
        return abs($this->netAmount);
    }

    public function needsPayment(): bool
    {
        return $this->netAmount !== 0;
    }

    public function isPayable(): bool
    {
        return $this->netAmount > 0; // Beza owes entity
    }

    public function isReceivable(): bool
    {
        return $this->netAmount < 0; // Entity owes Beza
    }

    public function matchWithExternal(int $externalAmount, int $tolerance = 100): string
    {
        if ($this->getAbsoluteAmount() === $externalAmount) {
            return 'matched_exact';
        }
        if (abs($this->getAbsoluteAmount() - $externalAmount) <= $tolerance) {
            return 'matched_tolerance';
        }
        return 'unmatched_amount';
    }
}
```

### SettlementPaymentOrder
```php
class SettlementPaymentOrder
{
    public string $id;
    public string $batchId;
    public ?string $batchItemId;

    // Entity
    public EntityType $entityType;
    public string $entityId;

    // Order
    public string $direction;         // pay | receive
    public int $amount;
    public string $currency;
    public string $settlementAccount;
    public PaymentOrderStatus $status; // generated | transmitted | confirmed | rejected | cancelled

    // File
    public string $fileFormat;        // CSV | ISO_20022_CAMT_053 | MT103
    public ?string $fileContent;

    // Transmission
    public ?Carbon $transmittedAt;
    public ?string $externalReference;

    // Confirmation
    public ?int $confirmedAmount;
    public ?string $bankReference;
    public ?Carbon $confirmedAt;

    // Rejection
    public ?string $failureReason;
    public int $retryCount;
    public ?Carbon $lastRetryAt;

    // Domain methods
    public function generateFileContent(): string
    {
        // Build file content in configured format
        return match($this->fileFormat) {
            'CSV' => $this->buildCsv(),
            'ISO_20022_CAMT_053' => $this->buildIso20022(),
            default => $this->buildCsv(),
        };
    }

    public function confirm(int $amount, string $bankReference): void
    {
        $this->status = PaymentOrderStatus::CONFIRMED;
        $this->confirmedAmount = $amount;
        $this->bankReference = $bankReference;
        $this->confirmedAt = now();
    }

    public function reject(string $reason): void
    {
        $this->status = PaymentOrderStatus::REJECTED;
        $this->failureReason = $reason;
        $this->retryCount++;
        $this->lastRetryAt = now();
    }

    private function buildCsv(): string
    {
        $header = "Reference,Amount,Currency,SettlementAccount,ValueDate";
        $line = implode(',', [
            $this->id,
            $this->amount,
            $this->currency,
            $this->settlementAccount,
            now()->format('Y-m-d'),
        ]);
        return $header . "\n" . $line;
    }

    private function buildIso20022(): string
    {
        // ISO 20022 camt.053 XML structure
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Document xmlns="urn:iso:std:iso:20022:tech:xsd:camt.053.001.08">'
            . '<BkToCstmrStmt>'
            . '<GrpHdr><MsgId>' . $this->id . '</MsgId></GrpHdr>'
            . '<Stmt>'
            . '<Acct><Id><Othr><Id>' . $this->settlementAccount . '</Id></Othr></Id></Acct>'
            . '</Stmt>'
            . '</BkToCstmrStmt>'
            . '</Document>';
    }
}
```

### SettlementReconciliation
```php
class SettlementReconciliation
{
    public string $id;
    public string $batchId;
    public Carbon $date;
    public ReconciliationStatus $status;

    public int $totalItems;
    public int $matchedItems;
    public int $unmatchedItems;
    public ?float $matchRate;

    public int $totalInternalAmount;
    public ?int $totalExternalAmount;
    public ?int $totalDifference;

    public ?Carbon $startedAt;
    public ?Carbon $completedAt;

    public Collection $items;         // SettlementReconciliationItem[]
}
```

### SettlementReconciliationItem
```php
class SettlementReconciliationItem
{
    public string $id;
    public string $reconciliationId;
    public string $batchItemId;

    public int $internalAmount;
    public ?int $externalAmount;
    public int $difference;

    public string $status;            // matched_exact | matched_tolerance | unmatched_amount | unmatched_missing | unmatched_duplicate | unmatched_rejected
    public ?int $matchTolerance;

    public ?string $internalReference;
    public ?string $externalReference;
    public ?string $notes;
}
```

### SettlementException
```php
class SettlementException
{
    public string $id;
    public string $batchId;
    public ?string $batchItemId;
    public ?string $reconciliationItemId;

    public ExceptionType $type;       // amount_mismatch | missing_confirmation | duplicate | rejected | timing_mismatch | reference_mismatch | other
    public ExceptionSeverity $severity; // low | medium | high | critical
    public ExceptionStatus $status;    // open | investigating | resolved | closed

    public ?int $internalAmount;
    public ?int $externalAmount;
    public ?int $difference;
    public ?string $description;
    public ?string $entityType;
    public ?string $entityId;

    // Investigation
    public ?string $assignedTo;
    public ?string $investigationNotes;

    // Resolution
    public ?string $resolutionType;   // adjustment | manual_match | write_off | reprocess | accepted_tolerance | rejected | other
    public ?string $resolutionNotes;
    public ?string $attachmentReference;
    public ?string $resolvedBy;
    public ?Carbon $resolvedAt;
    public ?Carbon $escalatedAt;
    public ?string $escalatedTo;

    // Domain methods
    public function assign(string $userId): void
    {
        $this->assignedTo = $userId;
        $this->status = ExceptionStatus::INVESTIGATING;
    }

    public function resolve(string $type, string $notes, string $userId): void
    {
        $this->status = ExceptionStatus::RESOLVED;
        $this->resolutionType = $type;
        $this->resolutionNotes = $notes;
        $this->resolvedBy = $userId;
        $this->resolvedAt = now();
    }

    public function escalate(string $to): void
    {
        $this->escalatedAt = now();
        $this->escalatedTo = $to;
    }

    public function reopen(): void
    {
        $this->status = ExceptionStatus::OPEN;
        $this->resolutionType = null;
        $this->resolutionNotes = null;
        $this->resolvedBy = null;
        $this->resolvedAt = null;
    }
}
```

### SettlementAccount
```php
class SettlementAccount
{
    public string $id;
    public EntityType $entityType;
    public string $entityId;
    public string $accountName;
    public ?string $accountNumber;
    public ?string $iban;
    public ?string $bankName;
    public ?string $bankBranch;
    public ?string $bankCode;
    public string $cfeAccountId;
    public string $currency;
    public bool $isActive;
    public bool $isDefault;
    public ?string $contactName;
    public ?string $contactEmail;
    public ?string $contactPhone;
    public string $settlementType;    // batch | realtime | both
    public ?string $cutOffTime;
    public ?int $minimumSettlement;
}
```

## Value Objects

### NettingResult
```php
class NettingResult
{
    public function __construct(
        public readonly array $positions,   // NetPosition[]
        public readonly SettlementBatch $batch,
        public readonly int $netAmount,
        public readonly Carbon $calculatedAt,
    ) {}

    public function toArray(): array
    {
        return [
            'net_amount' => $this->netAmount,
            'positions' => array_map(fn($p) => $p->toArray(), $this->positions),
            'calculated_at' => $this->calculatedAt->toIso8601String(),
        ];
    }
}

class NetPosition
{
    public function __construct(
        public readonly EntityType $entityType,
        public readonly string $entityId,
        public readonly int $totalDebit,
        public readonly int $totalCredit,
        public readonly int $netAmount,
        public readonly string $direction,    // 'pay' | 'receive'
        public readonly string $settlementAccount,
    ) {}

    public function toArray(): array
    {
        return [
            'entity_type' => $this->entityType->value,
            'entity_id' => $this->entityId,
            'total_debit' => $this->totalDebit,
            'total_credit' => $this->totalCredit,
            'net_amount' => $this->netAmount,
            'direction' => $this->direction,
            'settlement_account' => $this->settlementAccount,
        ];
    }
}
```
