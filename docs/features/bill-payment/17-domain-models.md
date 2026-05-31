# Bill Payment Domain Models

## Core Domain Objects

### Biller
```php
class Biller
{
    public function __construct(
        public readonly int $id,
        public readonly string $type,               // peed, damascus_water, syriatel, etc.
        public readonly string $nameAr,              // الشركة العامة للكهرباء
        public readonly string $nameEn,              // PEED
        public readonly BillerCategory $category,    // electricity, water, telecom, etc.
        public readonly BillerInterfaceType $interfaceType,  // api, csv, manual
        public readonly array $config,
        public readonly string $customerIdFormat,   // regex
        public readonly int $customerIdLength,
        public readonly bool $supportsFetch,
        public readonly bool $supportsPay,
        public readonly bool $supportsStatusCheck,
        public readonly bool $supportsAutoPay,
        public readonly bool $supportsPartialPay,
        public readonly float $feePercentage,
        public readonly int $feeFixed,
        public BillerStatus $status,
    ) {}

    public function isActive(): bool
    {
        return $this->status === BillerStatus::ACTIVE;
    }

    public function getConfig(string $key, mixed $default = null): mixed
    {
        return data_get($this->config, $key, $default);
    }

    public function calculateFee(int $amount): int
    {
        $percentage = (int) ceil($amount * $this->feePercentage / 100);
        return $percentage + $this->feeFixed;
    }
}

enum BillerCategory: string
{
    case ELECTRICITY = 'electricity';
    case WATER = 'water';
    case TELECOM = 'telecom';
    case INTERNET = 'internet';
    case GOVERNMENT = 'government';
    case EDUCATION = 'education';

    public function labelAr(): string
    {
        return match ($this) {
            self::ELECTRICITY => 'كهرباء',
            self::WATER => 'مياه',
            self::TELECOM => 'اتصالات',
            self::INTERNET => 'إنترنت',
            self::GOVERNMENT => 'حكومة',
            self::EDUCATION => 'تعليم',
        };
    }
}

enum BillerInterfaceType: string
{
    case API = 'api';
    case CSV = 'csv';
    case MANUAL = 'manual';
}

enum BillerStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case MAINTENANCE = 'maintenance';
}
```

### Bill
```php
class Bill
{
    public function __construct(
        public readonly string $customerId,
        public readonly string $customerName,
        public readonly string $customerAddress,
        public readonly string $billerType,
        public readonly string $billerName,
        public readonly string $invoiceNumber,
        public readonly string $billingPeriod,
        public readonly Money $amount,
        public readonly Money $lateFee,
        public readonly Money $totalDue,
        public readonly ?Money $vat,
        public readonly Carbon $dueDate,
        public readonly array $breakdown,        // [['label' => '...', 'amount' => int], ...]
        public readonly string $billerReference,
        public readonly ?Carbon $paymentDate,
        public readonly bool $isPaid,
    ) {}

    public function hasLateFee(): bool
    {
        return $this->lateFee->amount > 0;
    }

    public function daysUntilDue(): int
    {
        return now()->diffInDays($this->dueDate, false);
    }

    public function isOverdue(): bool
    {
        return $this->dueDate->isPast() && !$this->isPaid;
    }
}
```

### BillTransaction
```php
class BillTransaction
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $userId,
        public readonly int $billerId,
        public readonly string $customerId,
        public readonly ?string $customerName,
        public readonly ?string $invoiceNumber,
        public readonly ?string $billingPeriod,
        public readonly Money $billAmount,
        public readonly Money $lateFee,
        public readonly Money $fee,
        public readonly Money $total,
        public readonly string $reference,
        public readonly ?string $billerReference,
        public BillTransactionStatus $status,
        public readonly ?string $failureReason,
        public readonly ?Carbon $paidAt,
        public readonly ?string $receiptUrl,
        public readonly ?string $cfeReference,
        public readonly Carbon $createdAt,
    ) {}

    public function canRefund(): bool
    {
        return $this->status === BillTransactionStatus::PAID
            && $this->paidAt !== null
            && $this->paidAt->diffInHours(now()) < 48;
    }

    public function markRefunded(string $reason): void
    {
        $this->status = BillTransactionStatus::REFUNDED;
        $this->refundReason = $reason;
        $this->refundedAt = now();
    }
}

enum BillTransactionStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';
    case DISPUTED = 'disputed';
}
```

### ScheduledBill
```php
class ScheduledBill
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $userId,
        public readonly int $billerId,
        public readonly string $customerId,
        public readonly ?int $amount,             // null for variable bills
        public readonly ScheduleType $scheduleType,
        public readonly int $reminderDays,
        public readonly string $reminderMethod,
        public Carbon $nextDue,
        public bool $autoPayEnabled,
        public ?string $autoPayStatus,
        public int $autoPayFailures,
        public ?string $lastError,
        public ?Carbon $lastRemindedAt,
        public ScheduleStatus $status,
        public readonly Carbon $createdAt,
    ) {}

    public function isDueForReminder(Carbon $now): bool
    {
        if ($this->status !== ScheduleStatus::ACTIVE) return false;
        $reminderDate = $this->nextDue->copy()->subDays($this->reminderDays);
        return $now->isSameDay($reminderDate)
            && ($this->lastRemindedAt === null || !$this->lastRemindedAt->isToday());
    }

    public function isDueForAutoPay(Carbon $now): bool
    {
        return $this->autoPayEnabled
            && $this->status === ScheduleStatus::ACTIVE
            && $now->isSameDay($this->nextDue);
    }
}

enum ScheduleType: string
{
    case ONCE = 'once';
    case MONTHLY = 'monthly';
    case BI_MONTHLY = 'bi_monthly';
    case QUARTERLY = 'quarterly';
}

enum ScheduleStatus: string
{
    case ACTIVE = 'active';
    case PAUSED = 'paused';
    case CANCELLED = 'cancelled';
    case COMPLETED = 'completed';
}
```

### Value Objects
```php
class BillBreakdownItem
{
    public function __construct(
        public readonly string $label,
        public readonly Money $amount,
    ) {}
}

class CustomerIdFormat
{
    public function __construct(
        public readonly string $regex,
        public readonly int $length,
        public readonly string $example,
        public readonly string $displayFormat,    // "XXXX-XXXX-XXXX-XXXX-XXXX"
        public readonly string $helpTextAr,       // "24 رقماً — 4 مجموعات كل منها 6 أرقام"
    ) {}
}
```

### Service DTOs
```php
class FetchBillRequest
{
    public function __construct(
        public readonly string $customerId,
        public readonly string $billerType,
        public readonly string $idempotencyKey,
    ) {}
}

class PayBillRequest
{
    public function __construct(
        public readonly User $user,
        public readonly string $billerType,
        public readonly string $customerId,
        public readonly string $invoiceNumber,
        public readonly int $totalDue,
        public readonly string $pinHash,
        public readonly string $idempotencyKey,
    ) {}
}

class PaymentResult
{
    public function __construct(
        public readonly string $transactionId,
        public readonly string $bezaReference,
        public readonly string $billerReference,
        public readonly BillTransactionStatus $status,
        public readonly Receipt $receipt,
    ) {}
}

class BillReceipt
{
    public function __construct(
        public readonly string $reference,
        public readonly string $url,
        public readonly string $billerReference,
        public readonly string $billerName,
        public readonly string $customerName,
        public readonly string $invoiceNumber,
        public readonly Money $amount,
        public readonly Money $fee,
        public readonly Money $total,
        public readonly Carbon $generatedAt,
    ) {}
}
```
