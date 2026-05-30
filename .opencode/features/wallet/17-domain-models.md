# Wallet Domain Models

## Core Domain Objects

### Wallet
```php
class Wallet
{
    public function __construct(
        public readonly int $id,
        public readonly int $userId,
        public readonly int $tenantId,
        public readonly string $cfeAccountId,
        public readonly Currency $currency,
        public readonly WalletType $type,
        public WalletStatus $status,
        public int $kycLevel,
        public Money $dailySent,
        public ?Carbon $dailySentAt,
        public ?array $metadata,
    ) {}

    public function isActive(): bool
    {
        return $this->status === WalletStatus::ACTIVE;
    }

    public function canSend(Money $amount, LimitService $limits): bool
    {
        if (!$this->isActive()) return false;
        $dailyLimit = $limits->getDailyLimit($this->kycLevel, $this->currency, 'send');
        return $this->dailySent->plus($amount)->lte($dailyLimit);
    }

    public function recordDailySent(Money $amount): void
    {
        $today = Carbon::today();
        if ($this->dailySentAt?->isToday()) {
            $this->dailySent = $this->dailySent->plus($amount);
        } else {
            $this->dailySent = $amount;
            $this->dailySentAt = $today;
        }
    }
}

enum WalletType: string
{
    case MAIN = 'main';
    case SAVINGS = 'savings';
    case CARD = 'card';
    case MERCHANT = 'merchant';
}

enum WalletStatus: string
{
    case ACTIVE = 'active';
    case FROZEN = 'frozen';
    case CLOSED = 'closed';
    case DORMANT = 'dormant';
}
```

### Transaction
```php
class WalletTransaction
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $uuid,
        public readonly TransactionType $type,
        public TransactionStatus $status,
        public readonly Money $amount,
        public readonly Money $fee,
        public readonly Money $total,
        public readonly Currency $currency,
        public readonly ?int $senderWalletId,
        public readonly ?int $recipientWalletId,
        public readonly ?string $note,
        public readonly ?string $reference,
        public readonly ?string $cfeReference,
        public readonly ?string $cfeHoldId,
        public readonly ?string $idempotencyKey,
        public readonly ?int $agentId,
        public readonly ?int $merchantId,
        public readonly Carbon $createdAt,
    ) {}

    public function isDebit(): bool
    {
        return in_array($this->type, [
            TransactionType::SEND,
            TransactionType::CASH_OUT,
            TransactionType::BILL_PAYMENT,
            TransactionType::FEE,
        ]);
    }

    public function canReverse(): bool
    {
        return $this->status === TransactionStatus::COMPLETED
            && $this->createdAt->diffInHours(now()) < 24;
    }
}

enum TransactionType: string
{
    case SEND = 'send';
    case RECEIVE = 'receive';
    case CASH_IN = 'cash_in';
    case CASH_OUT = 'cash_out';
    case BILL_PAYMENT = 'bill_payment';
    case AIRTIME = 'airtime';
    case CARD_PAYMENT = 'card_payment';
    case LOAN_DISBURSEMENT = 'loan_disbursement';
    case LOAN_REPAYMENT = 'loan_repayment';
    case SAVINGS_DEPOSIT = 'savings_deposit';
    case SAVINGS_WITHDRAWAL = 'savings_withdrawal';
    case FEE = 'fee';
    case REFUND = 'refund';
    case REVERSAL = 'reversal';
}

enum TransactionStatus: string
{
    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case REVERSED = 'reversed';
    case DISPUTED = 'disputed';
    case EXPIRED = 'expired';
}
```

### Value Objects
```php
class Money
{
    public function __construct(
        public readonly int $amount,    // Smallest unit (piasters/cents)
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
class SendMoneyRequest
{
    public function __construct(
        public readonly User $sender,
        public readonly string $recipientPhone,
        public readonly Money $amount,
        public readonly Currency $currency,
        public readonly ?string $note,
        public readonly string $pinHash,
        public readonly string $idempotencyKey,
    ) {}
}

class TransactionResult
{
    public function __construct(
        public readonly string $transactionId,
        public readonly TransactionStatus $status,
        public readonly Receipt $receipt,
    ) {}
}

class Receipt
{
    public function __construct(
        public readonly string $reference,
        public readonly string $url,
        public readonly Carbon $generatedAt,
    ) {}
}

class BalanceDTO
{
    public function __construct(
        public readonly Money $available,
        public readonly Money $held,
        public readonly Money $total,
        public readonly Carbon $lastUpdated,
    ) {}
}
```
