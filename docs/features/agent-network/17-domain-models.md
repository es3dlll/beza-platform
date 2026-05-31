# Agent Network Domain Models

## Core Domain Objects

### Agent
```php
class Agent
{
    public function __construct(
        public readonly int $id,
        public readonly int $userId,
        public readonly int $tenantId,
        public readonly string $code,
        public readonly string $fullName,
        public readonly string $phone,
        public readonly string $shopName,
        public AgentStatus $status,
        public AgentTier $tier,
        public Money $floatBalance,
        public Money $pendingCommission,
        public Money $totalCommissionEarned,
        public int $totalTransactions,
        public AgentLocation $location,
        public ?AgentDevice $device,
        public Carbon $createdAt,
    ) {}

    public function isActive(): bool
    {
        return $this->status === AgentStatus::ACTIVE;
    }

    public function isAvailable(): bool
    {
        return $this->isActive() && $this->device?->isOnline() === true;
    }

    public function hasSufficientFloat(Money $amount): bool
    {
        return $this->floatBalance->amount >= $amount->amount;
    }

    public function canCashIn(Money $amount, LimitService $limits): bool
    {
        return $this->isActive()
            && $this->hasSufficientFloat($amount)
            && $amount->amount <= $this->tier->maxCashInPerTxn()
            && $limits->isWithinDailyCashIn($this->id, $amount);
    }

    public function canCashOut(Money $amount, LimitService $limits): bool
    {
        return $this->isActive()
            && $amount->amount <= $this->tier->maxCashOutPerTxn()
            && $limits->isWithinDailyCashOut($this->id, $amount);
    }

    public function debitFloat(Money $amount): void
    {
        Assert::true($this->hasSufficientFloat($amount));
        $this->floatBalance = $this->floatBalance->minus($amount);
    }

    public function creditFloat(Money $amount): void
    {
        $this->floatBalance = $this->floatBalance->plus($amount);
    }

    public function accrueCommission(Money $amount): void
    {
        $this->pendingCommission = $this->pendingCommission->plus($amount);
        $this->totalCommissionEarned = $this->totalCommissionEarned->plus($amount);
    }

    public function settleCommission(): Money
    {
        $amount = $this->pendingCommission;
        $this->pendingCommission = Money::zero(Currency::SYP);
        return $amount;
    }

    public function incrementTransactionCount(): void
    {
        $this->totalTransactions++;
    }

    public function distanceTo(float $lat, float $lng): float
    {
        // Calculate Haversine distance in km
        return $this->location->distanceTo($lat, $lng);
    }
}

enum AgentStatus: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
    case TERMINATED = 'terminated';

    // FSM transitions:
    // pending → active (KYC approved)
    // active → suspended (violation)
    // suspended → active (reinstated)
    // active → terminated (permanent)
    // suspended → terminated (permanent)
    // pending → terminated (rejected)

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::PENDING => $next === self::ACTIVE || $next === self::TERMINATED,
            self::ACTIVE => $next === self::SUSPENDED || $next === self::TERMINATED,
            self::SUSPENDED => $next === self::ACTIVE || $next === self::TERMINATED,
            self::TERMINATED => false,
        };
    }
}

enum AgentTier: string
{
    case BRONZE = 'bronze';
    case SILVER = 'silver';
    case GOLD = 'gold';
    case PLATINUM = 'platinum';

    public function maxCashInPerTxn(): int
    {
        return match ($this) {
            self::BRONZE => 5000000,
            self::SILVER => 10000000,
            self::GOLD => 30000000,
            self::PLATINUM => 50000000,
        };
    }

    public function maxCashOutPerTxn(): int
    {
        return match ($this) {
            self::BRONZE => 500000,
            self::SILVER => 2000000,
            self::GOLD => 5000000,
            self::PLATINUM => 10000000,
        };
    }

    public function maxCashInDaily(): int
    {
        return match ($this) {
            self::BRONZE => 5000000,
            self::SILVER => 10000000,
            self::GOLD => 30000000,
            self::PLATINUM => 50000000,
        };
    }

    public function maxCashOutDaily(): int
    {
        return match ($this) {
            self::BRONZE => 2000000,
            self::SILVER => 5000000,
            self::GOLD => 15000000,
            self::PLATINUM => 40000000,
        };
    }

    public function maxFloatBalance(): int
    {
        return match ($this) {
            self::BRONZE => 5000000,
            self::SILVER => 15000000,
            self::GOLD => 50000000,
            self::PLATINUM => 100000000,
        };
    }

    public function commissionRateCashIn(): float
    {
        return match ($this) {
            self::BRONZE => 0.003,
            self::SILVER => 0.004,
            self::GOLD => 0.005,
            self::PLATINUM => 0.006,
        };
    }

    public function commissionRateCashOut(): float
    {
        return match ($this) {
            self::BRONZE => 0.005,
            self::SILVER => 0.006,
            self::GOLD => 0.0075,
            self::PLATINUM => 0.01,
        };
    }
}
```

### AgentTransaction
```php
class AgentTransaction
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $uuid,
        public readonly int $agentId,
        public readonly AgentTransactionType $type,
        public AgentTransactionStatus $status,
        public readonly Money $amount,
        public readonly Money $fee,
        public readonly Money $commission,
        public readonly Money $balanceBefore,
        public readonly Money $balanceAfter,
        public readonly ?string $customerPhone,
        public readonly ?int $customerWalletId,
        public readonly ?int $counterpartyAgentId,
        public readonly ?string $idempotencyKey,
        public readonly ?string $deviceId,
        public readonly ?AgentLocation $location,
        public readonly bool $offlineQueued,
        public readonly ?Carbon $createdAt,
    ) {}

    public function isCashIn(): bool
    {
        return $this->type === AgentTransactionType::CASH_IN;
    }

    public function isCashOut(): bool
    {
        return $this->type === AgentTransactionType::CASH_OUT;
    }

    public function canReverse(): bool
    {
        return $this->status === AgentTransactionStatus::COMPLETED
            && $this->createdAt->diffInHours(now()) < 2; // Agents have 2h reversal window
    }

    public function reverse(string $reason): void
    {
        Assert::true($this->canReverse());
        $this->status = AgentTransactionStatus::REVERSED;
    }
}

enum AgentTransactionType: string
{
    case CASH_IN = 'cash_in';
    case CASH_OUT = 'cash_out';
    case FLOAT_FUNDING = 'float_funding';
    case FLOAT_TRANSFER_IN = 'float_transfer_in';
    case FLOAT_TRANSFER_OUT = 'float_transfer_out';
    case COMMISSION = 'commission';
    case ADJUSTMENT = 'adjustment';
}

enum AgentTransactionStatus: string
{
    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case REVERSED = 'reversed';
}
```

### AgentFloat
```php
class AgentFloat
{
    public function __construct(
        public readonly int $agentId,
        public Money $balance,
        public readonly Money $dailyCashInTotal,
        public readonly Money $dailyCashOutTotal,
        public readonly int $dailyCashInCount,
        public readonly int $dailyCashOutCount,
        public readonly Carbon $lastUpdated,
    ) {}

    public function isLow(): bool
    {
        return $this->balance->amount < 100000; // <100K SYP
    }

    public function isCritical(): bool
    {
        return $this->balance->amount < 50000; // <50K SYP
    }

    public function percentageUsed(): float
    {
        // 0.0 to 1.0 based on tier max float
        return 0.0;
    }
}
```

### AgentCommission
```php
class AgentCommission
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $agentId,
        public readonly ?int $transactionId,
        public readonly Money $amount,
        public readonly CommissionType $type,
        public readonly float $rateApplied,
        public CommissionStatus $status,
        public readonly ?int $settlementId,
        public readonly Carbon $createdAt,
    ) {}

    public function isSettled(): bool
    {
        return $this->status === CommissionStatus::SETTLED;
    }

    public function markSettled(int $settlementId): void
    {
        $this->status = CommissionStatus::SETTLED;
        $this->settlementId = $settlementId;
    }
}

enum CommissionType: string
{
    case CASH_IN = 'cash_in';
    case CASH_OUT = 'cash_out';
}

enum CommissionStatus: string
{
    case ACCRUED = 'accrued';
    case SETTLED = 'settled';
    case REVERSED = 'reversed';
}

class AgentCommissionSettlement
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $batchReference,
        public readonly Carbon $settledDate,
        public readonly int $totalAgents,
        public readonly Money $totalAmount,
        public SettlementStatus $status,
    ) {}
}

enum SettlementStatus: string
{
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
}
```

### Value Objects
```php
class Money
{
    public function __construct(
        public readonly int $amount,    // Smallest unit (piasters)
        public readonly Currency $currency,
    ) {}

    public function plus(Money $other): Money
    {
        assert($this->currency === $other->currency);
        return new Money($this->amount + $other->amount, $this->currency);
    }

    public function minus(Money $other): Money
    {
        assert($this->currency === $other->currency);
        return new Money($this->amount - $other->amount, $this->currency);
    }

    public function lte(Money $other): bool
    {
        return $this->amount <= $other->amount;
    }

    public function gte(Money $other): bool
    {
        return $this->amount >= $other->amount;
    }

    public function format(): string
    {
        return number_format($this->amount, 0) . ' ' . $this->currency->symbol();
    }

    public static function zero(Currency $currency): self
    {
        return new self(0, $currency);
    }
}

class AgentLocation
{
    public function __construct(
        public readonly float $lat,
        public readonly float $lng,
    ) {}

    public function distanceTo(float $otherLat, float $otherLng): float
    {
        // Haversine formula
        $earthRadius = 6371;
        $dLat = deg2rad($otherLat - $this->lat);
        $dLng = deg2rad($otherLng - $this->lng);
        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($this->lat)) * cos(deg2rad($otherLat))
            * sin($dLng / 2) * sin($dLng / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }

    public function toWkt(): string
    {
        return sprintf('POINT(%f %f)', $this->lng, $this->lat);
    }
}

enum Currency: string
{
    case SYP = 'SYP';
    case USD = 'USD';

    public function symbol(): string
    {
        return match ($this) {
            self::SYP => 'ل.س',
            self::USD => '$',
        };
    }

    public function decimals(): int
    {
        return match ($this) {
            self::SYP => 0,
            self::USD => 2,
        };
    }
}
```

### Service DTOs
```php
class CashInRequest
{
    public function __construct(
        public readonly Agent $agent,
        public readonly string $customerPhone,
        public readonly string $verificationId,
        public readonly string $verificationCode,
        public readonly Money $amount,
        public readonly ?AgentLocation $location,
        public readonly string $idempotencyKey,
    ) {}
}

class CashOutRequest
{
    public function __construct(
        public readonly Agent $agent,
        public readonly string $customerPhone,
        public readonly string $verificationId,
        public readonly string $verificationCode,
        public readonly Money $amount,
        public readonly string $customerPin,
        public readonly bool $biometricVerified,
        public readonly ?AgentLocation $location,
        public readonly string $idempotencyKey,
    ) {}
}

class TransactionResult
{
    public function __construct(
        public readonly string $transactionId,
        public readonly AgentTransactionStatus $status,
        public readonly Money $commission,
        public readonly Money $floatAfter,
        public readonly Money $customerBalanceAfter,
        public readonly ReceiptData $receipt,
    ) {}
}

class ReceiptData
{
    public function __construct(
        public readonly string $reference,
        public readonly string $url,
        public readonly Carbon $generatedAt,
    ) {}
}

class FloatSummary
{
    public function __construct(
        public readonly Money $currentBalance,
        public readonly Money $dailyCashInTotal,
        public readonly Money $dailyCashOutTotal,
        public readonly int $dailyCashInCount,
        public readonly int $dailyCashOutCount,
        public readonly Money $dailyCommissionEarned,
        public readonly FloatStatus $status,
        public readonly Carbon $lastUpdated,
    ) {}
}

enum FloatStatus: string
{
    case OK = 'ok';
    case LOW = 'low';
    case CRITICAL = 'critical';
}
```
