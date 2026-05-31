# Merchant Domain Models

## Core Domain Objects

### Merchant
```php
class Merchant
{
    public function __construct(
        public readonly int $id,
        public readonly int $userId,
        public readonly int $tenantId,
        public string $businessName,
        public BusinessType $businessType,
        public ?string $licenseNumber,
        public bool $licenseVerified,
        public ?array $shopPhotos,
        public ?array $location,
        public ?string $customerPhone,
        public MerchantStatus $status,
        public MerchantTier $tier,
        public float $mdrQrRate,
        public float $mdrPosRate,
        public float $mdrLinkRate,
        public float $mdrWebRate,
        public SettlementPeriod $settlementPeriod,
        public ?string $webhookUrl,
        public ?string $webhookSecret,
        public ?array $webhookEvents,
        public Money $dailyTxnLimit,
        public Money $monthlyTxnLimit,
        public Money $perTxnMax,
        public Money $perTxnMin,
        public ?string $referralCode,
        public ?Carbon $verifiedAt,
        public ?Carbon $createdAt,
    ) {}

    public function isVerified(): bool
    {
        return $this->status === MerchantStatus::VERIFIED;
    }

    public function isActive(): bool
    {
        return in_array($this->status, [MerchantStatus::VERIFIED]);
    }

    public function canAcceptPayment(Money $amount): bool
    {
        if (!$this->isActive()) return false;
        if ($amount->lte($this->perTxnMin)) return false;
        if ($amount->gt($this->perTxnMax)) return false;
        return true;
    }

    public function getMdrRate(PaymentMethod $method): float
    {
        return match ($method) {
            PaymentMethod::QR => $this->mdrQrRate,
            PaymentMethod::POS => $this->mdrPosRate,
            PaymentMethod::PAYMENT_LINK => $this->mdrLinkRate,
            PaymentMethod::WEB_CHECKOUT => $this->mdrWebRate,
        };
    }

    public function getPublicProfile(): array
    {
        return [
            'id' => $this->id,
            'business_name' => $this->businessName,
            'business_type' => $this->businessType->value,
            'customer_phone' => $this->customerPhone,
            'tier' => $this->tier->value,
        ];
    }
}

enum BusinessType: string
{
    case GROCERY = 'grocery';
    case RESTAURANT = 'restaurant';
    case RETAIL = 'retail';
    case ELECTRONICS = 'electronics';
    case PHARMACY = 'pharmacy';
    case CLOTHING = 'clothing';
    case BAKERY = 'bakery';
    case BUTCHER = 'butcher';
    case FRUIT_VEGETABLES = 'fruit_vegetables';
    case STATIONERY = 'stationery';
    case HOME_BUSINESS = 'home_business';
    case E_COMMERCE = 'e_commerce';
    case OTHER = 'other';
}

enum MerchantStatus: string
{
    case PENDING = 'pending';
    case VERIFIED = 'verified';
    case REJECTED = 'rejected';
    case SUSPENDED = 'suspended';
    case CLOSED = 'closed';
}

enum MerchantTier: string
{
    case MICRO = 'micro';
    case SMALL = 'small';
    case MID = 'mid';
    case ENTERPRISE = 'enterprise';
}

enum SettlementPeriod: string
{
    case DAILY = 'daily';
    case WEEKLY = 'weekly';
    case MONTHLY = 'monthly';
}

enum PaymentMethod: string
{
    case QR = 'qr';
    case POS = 'pos';
    case PAYMENT_LINK = 'payment_link';
    case WEB_CHECKOUT = 'web_checkout';
}
```

### MerchantQrCode
```php
class MerchantQrCode
{
    public function __construct(
        public readonly int $id,
        public readonly int $merchantId,
        public readonly QrType $type,
        public readonly ?int $amount,
        public readonly string $qrData,
        public readonly string $imageUrl,
        public QrStatus $status,
        public int $scanCount,
        public ?Carbon $expiresAt,
        public readonly Carbon $createdAt,
    ) {}

    public function isValid(): bool
    {
        if ($this->status !== QrStatus::ACTIVE) return false;
        if ($this->expiresAt && $this->expiresAt->isPast()) return false;
        return true;
    }

    public function isDynamic(): bool
    {
        return $this->type === QrType::DYNAMIC;
    }
}

enum QrType: string
{
    case STATIC = 'static';
    case DYNAMIC = 'dynamic';
}

enum QrStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case EXPIRED = 'expired';
}
```

### MerchantPaymentLink
```php
class MerchantPaymentLink
{
    public function __construct(
        public readonly int $id,
        public readonly string $uuid,
        public readonly int $merchantId,
        public readonly Money $amount,
        public readonly ?string $description,
        public PaymentLinkStatus $status,
        public ?Carbon $paidAt,
        public readonly string $shortUrl,
        public readonly Carbon $expiresAt,
        public readonly Carbon $createdAt,
    ) {}

    public function isExpired(): bool
    {
        return $this->expiresAt->isPast();
    }

    public function isPayable(): bool
    {
        return $this->status === PaymentLinkStatus::PENDING && !$this->isExpired();
    }

    public function markPaid(): void
    {
        $this->status = PaymentLinkStatus::PAID;
        $this->paidAt = now();
    }
}

enum PaymentLinkStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case EXPIRED = 'expired';
    case CANCELLED = 'cancelled';
}
```

### MerchantPosTerminal
```php
class MerchantPosTerminal
{
    public function __construct(
        public readonly int $id,
        public readonly int $merchantId,
        public readonly string $terminalId,
        public readonly string $serialNumber,
        public readonly string $model,
        public readonly ?string $certificateSn,
        public PosTerminalStatus $status,
        public readonly Carbon $lastPairedAt,
        public ?Carbon $lastSeenAt,
        public ?string $firmwareVersion,
    ) {}

    public function isOnline(): bool
    {
        return $this->lastSeenAt && $this->lastSeenAt->diffInMinutes(now()) < 5;
    }
}

enum PosTerminalStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case LOST = 'lost';
    case DECOMMISSIONED = 'decommissioned';
}
```

### MerchantSettlement
```php
class MerchantSettlement
{
    public function __construct(
        public readonly int $id,
        public readonly int $merchantId,
        public readonly Carbon $periodStart,
        public readonly Carbon $periodEnd,
        public readonly Money $grossAmount,
        public readonly Money $mdrAmount,
        public readonly Money $netAmount,
        public int $transactionCount,
        public SettlementStatus $status,
        public ?Carbon $paidAt,
        public readonly Carbon $createdAt,
    ) {}

    public function isCompleted(): bool
    {
        return $this->status === SettlementStatus::COMPLETED;
    }
}

enum SettlementStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
}
```

### Service DTOs
```php
class RegisterMerchantRequest
{
    public function __construct(
        public readonly User $user,
        public readonly Tenant $tenant,
        public readonly string $businessName,
        public readonly BusinessType $businessType,
        public readonly ?string $licenseNumber,
        public readonly ?string $licenseImage,
        public readonly ?array $shopPhotos,
        public readonly ?array $location,
        public readonly ?string $customerPhone,
    ) {}
}

class QrPaymentRequest
{
    public function __construct(
        public readonly string $qrData,
        public readonly int $amount,
        public readonly User $customer,
        public readonly string $pinHash,
        public readonly string $idempotencyKey,
    ) {}
}

class PaymentLinkResult
{
    public function __construct(
        public readonly string $linkId,
        public readonly string $shortUrl,
        public readonly string $fullUrl,
        public readonly Money $amount,
        public readonly ?string $description,
        public readonly Carbon $expiresAt,
    ) {}
}

class SettlementReport
{
    public function __construct(
        public readonly MerchantSettlement $settlement,
        public readonly array $transactions,
        public readonly string $pdfUrl,
    ) {}
}
```
