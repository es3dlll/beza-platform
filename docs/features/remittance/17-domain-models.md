# Remittance Domain Models

## Core Domain Objects

### Remittance
```php
class Remittance
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $uuid,
        public readonly int $senderId,
        public readonly ?int $beneficiaryId,
        public readonly int $recipientId,
        public readonly int $corridorId,
        public readonly ?int $recurringId,
        public readonly Money $sourceAmount,
        public readonly Currency $sourceCurrency,
        public readonly FXRate $fxRate,
        public readonly Money $targetAmount,
        public readonly Currency $targetCurrency,
        public readonly Money $fee,
        public readonly Money $fxSpreadIncome,
        public readonly RemittanceType $type,
        public RemittanceStatus $status,
        public DeliveryMethod $deliveryMethod,
        public readonly ?string $note,
        public readonly ?string $reference,
        public readonly string $idempotencyKey,
        public ComplianceStatus $complianceStatus,
        public readonly ?string $sourceOfFunds,
        public readonly string $senderCountry,
        public readonly Carbon $createdAt,
    ) {}

    public function canCancel(): bool
    {
        return $this->status === RemittanceStatus::PENDING
            || $this->status === RemittanceStatus::FX_LOCKED
            || ($this->status === RemittanceStatus::PROCESSING
                && $this->createdAt->diffInMinutes(now()) < 30);
    }

    public function isCrossBorder(): bool
    {
        return $this->type === RemittanceType::DIASPORA;
    }

    public function requiresTravelRule(): bool
    {
        return $this->isCrossBorder()
            && $this->sourceAmount->amount > 1000; // > $1K equivalent
    }

    public function requiresSourceOfFunds(): bool
    {
        return $this->isCrossBorder()
            && $this->sourceAmount->amount > 500; // > $500 equivalent
    }
}

enum RemittanceType: string
{
    case LOCAL_P2P = 'local_p2p';
    case DIASPORA = 'diaspora';
    case RECURRING = 'recurring';
    case REQUEST = 'request';
}

enum RemittanceStatus: string
{
    case PENDING = 'pending';
    case FX_LOCKED = 'fx_locked';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
    case DISPUTED = 'disputed';
}

enum DeliveryMethod: string
{
    case WALLET = 'wallet';
    case AGENT_PICKUP = 'agent_pickup';
    case BANK_DEPOSIT = 'bank_deposit';
}

enum ComplianceStatus: string
{
    case PENDING = 'pending';
    case PASSED = 'passed';
    case FLAGGED = 'flagged';
    case BLOCKED = 'blocked';
}
```

### Beneficiary
```php
class Beneficiary
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $userId,
        public readonly ?int $recipientUserId,
        public readonly string $name,
        public readonly ?string $nameEn,
        public readonly BeneficiaryRelationship $relationship,
        public readonly ?string $relationshipCustom,
        public readonly string $phone,
        public readonly ?string $city,
        public readonly string $country,
        public Currency $currencyPreference,
        public DeliveryMethod $deliveryPreference,
        public int $totalTransfers,
        public Money $totalSentAmount,
        public ?Carbon $lastSentAt,
        public bool $isFavorite,
        public ComplianceStatus $sanctionsStatus,
        public BeneficiaryStatus $status,
        public readonly Carbon $createdAt,
    ) {}

    public function requiresSanctionsScreening(): bool
    {
        return $this->sanctionsStatus === ComplianceStatus::PENDING
            || ($this->lastSentAt && $this->lastSentAt->diffInMonths(now()) > 6);
    }
}

enum BeneficiaryRelationship: string
{
    case MOTHER = 'mother';
    case FATHER = 'father';
    case BROTHER = 'brother';
    case SISTER = 'sister';
    case SPOUSE = 'spouse';
    case SON = 'son';
    case DAUGHTER = 'daughter';
    case FRIEND = 'friend';
    case OTHER = 'other';
}

enum BeneficiaryStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
}
```

### RecurringTransfer
```php
class RecurringTransfer
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $senderId,
        public readonly int $beneficiaryId,
        public readonly int $corridorId,
        public readonly Money $amount,
        public readonly Currency $sourceCurrency,
        public readonly Currency $targetCurrency,
        public RecurringFrequency $frequency,
        public readonly ?int $dayOfMonth,
        public readonly ?int $dayOfWeek,
        public readonly string $executionTime,
        public RecurringDuration $durationType,
        public readonly ?int $maxExecutions,
        public readonly ?Carbon $endDate,
        public int $executionsCount,
        public int $failedCount,
        public RecurringStatus $status,
        public Carbon $nextExecutionAt,
        public ?Carbon $lastExecutedAt,
        public Money $totalSentAmount,
        public readonly Carbon $createdAt,
    ) {}

    public function isDue(): bool
    {
        return $this->status === RecurringStatus::ACTIVE
            && $this->nextExecutionAt->isPast();
    }

    public function calculateNextExecution(): Carbon
    {
        return match ($this->frequency) {
            RecurringFrequency::WEEKLY => $this->nextExecutionAt->addWeek(),
            RecurringFrequency::BIWEEKLY => $this->nextExecutionAt->addWeeks(2),
            RecurringFrequency::MONTHLY => $this->nextExecutionAt->addMonth(),
            RecurringFrequency::QUARTERLY => $this->nextExecutionAt->addMonths(3),
        };
    }

    public function hasReachedEnd(): bool
    {
        if ($this->durationType === RecurringDuration::FIXED_COUNT
            && $this->executionsCount >= $this->maxExecutions) return true;
        if ($this->durationType === RecurringDuration::END_DATE
            && $this->endDate && $this->endDate->isPast()) return true;
        return false;
    }
}

enum RecurringFrequency: string
{
    case WEEKLY = 'weekly';
    case BIWEEKLY = 'biweekly';
    case MONTHLY = 'monthly';
    case QUARTERLY = 'quarterly';
}

enum RecurringDuration: string
{
    case ONGOING = 'ongoing';
    case FIXED_COUNT = 'fixed_count';
    case END_DATE = 'end_date';
}

enum RecurringStatus: string
{
    case ACTIVE = 'active';
    case PAUSED = 'paused';
    case CANCELLED = 'cancelled';
    case COMPLETED = 'completed';
}
```

### Value Objects
```php
class FXRate
{
    public function __construct(
        public readonly float $rate,
        public readonly float $midMarketRate,
        public readonly float $spreadPercent,
        public readonly ?string $lockId,
        public readonly ?Carbon $lockedAt,
        public readonly ?Carbon $expiresAt,
    ) {}

    public function isLocked(): bool
    {
        return $this->lockId !== null && $this->expiresAt?->isFuture();
    }

    public function convert(float $amount, Currency $from, Currency $to): int
    {
        if ($from === $to) return (int) $amount;
        return (int) round($amount * $this->rate);
    }
}

class RemittanceCorridor
{
    public function __construct(
        public readonly int $id,
        public readonly Currency $sourceCurrency,
        public readonly Currency $targetCurrency,
        public readonly string $sourceCountry,
        public readonly string $corridorKey,
        public readonly Money $dailyMaxSender,
        public readonly Money $monthlyMaxSender,
        public readonly Money $perTxnMax,
        public readonly Money $perTxnMin,
        public readonly float $fxSpreadPercent,
        public readonly float $feePercent,
        public readonly int $requiredKycLevel,
        public readonly float $sourceOfFundsThreshold,
        public readonly float $travelRuleThreshold,
        public CorridorStatus $status,
    ) {}

    public function isActive(): bool
    {
        return $this->status === CorridorStatus::ACTIVE;
    }
}

enum CorridorStatus: string
{
    case ACTIVE = 'active';
    case MAINTENANCE = 'maintenance';
    case INACTIVE = 'inactive';
}
```
