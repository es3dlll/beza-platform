# Open Finance Domain Models

## Core Domain Objects

### ApiKey
```php
class ApiKey
{
    public function __construct(
        public readonly int $id,
        public readonly int $developerId,
        public readonly string $label,
        public readonly string $keyPrefix,
        public readonly string $keyHash,
        public readonly string $environment,
        public readonly array $scopes,
        public readonly string $status,
        public readonly ?Carbon $expiresAt,
        public readonly ?Carbon $revokedAt,
        public readonly Carbon $createdAt,
        private ?string $rawKey = null,        // Transient — never persisted
    ) {}

    public function isExpired(): bool
    {
        return $this->expiresAt && $this->expiresAt->isPast();
    }

    public function isRevoked(): bool
    {
        return $this->revokedAt !== null;
    }

    public function hasScope(string $scope): bool
    {
        return in_array($scope, $this->scopes);
    }

    public function setRawKey(string $key): static
    {
        $this->rawKey = $key;
        return $this;
    }

    public function getRawKey(): ?string
    {
        return $this->rawKey;
    }
}

enum ApiKeyScope: string
{
    case PAYMENTS_WRITE = 'payments.write';
    case PAYMENTS_READ = 'payments.read';
    case ACCOUNTS_READ = 'accounts.read';
    case WALLETS_WRITE = 'wallets.write';
    case WALLETS_READ = 'wallets.read';
    case TRANSACTIONS_READ = 'transactions.read';
    case WEBHOOKS_READ = 'webhooks.read';
    case WEBHOOKS_WRITE = 'webhooks.write';
    case AGENTS_READ = 'agents.read';
}
```

### DeveloperAccount
```php
class DeveloperAccount
{
    public function __construct(
        public readonly int $id,
        public readonly int $userId,
        public readonly string $email,
        public readonly string $companyName,
        public readonly string $tier,
        public readonly string $kycStatus,
        public readonly bool $isActive,
        public readonly Carbon $createdAt,
    ) {}

    public function isKycApproved(): bool
    {
        return $this->kycStatus === 'approved';
    }

    public function canCreateProductionKeys(): bool
    {
        return $this->isKycApproved() && $this->isActive;
    }
}

enum DeveloperTier: string
{
    case FREE = 'free';
    case STARTUP = 'startup';
    case BUSINESS = 'business';
    case ENTERPRISE = 'enterprise';
}
```

### WebhookEndpoint
```php
class WebhookEndpoint
{
    public function __construct(
        public readonly int $id,
        public readonly int $developerId,
        public readonly string $url,
        public readonly string $signingSecret,
        public readonly array $events,
        public readonly ?string $description,
        public readonly string $status,
    ) {}

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function subscribesTo(string $eventType): bool
    {
        return in_array($eventType, $this->events);
    }
}

enum WebhookDeliveryStatus: string
{
    case PENDING = 'pending';
    case DELIVERED = 'delivered';
    case FAILED = 'failed';
}

enum WebhookEvent: string
{
    case PAYMENT_COMPLETED = 'payment.completed';
    case PAYMENT_FAILED = 'payment.failed';
    case PAYMENT_EXPIRED = 'payment.expired';
    case PAYMENT_CANCELLED = 'payment.cancelled';
    case TRANSFER_COMPLETED = 'transfer.completed';
    case TRANSFER_FAILED = 'transfer.failed';
    case ACCOUNT_BALANCE_CHANGED = 'account.balance_changed';
    case WALLET_CREATED = 'wallet.created';
    case WALLET_FUNDED = 'wallet.funded';
}
```

### ApiUsageLog
```php
class ApiUsageLog
{
    public function __construct(
        public readonly int $id,
        public readonly int $developerId,
        public readonly string $method,
        public readonly string $endpoint,
        public readonly int $statusCode,
        public readonly int $latencyMs,
        public readonly ?string $requestId,
        public readonly ?string $idempotencyKey,
        public readonly ?string $errorCode,
        public readonly Carbon $createdAt,
    ) {}
}

class UsageStats
{
    public function __construct(
        public readonly int $dailyRequests,
        public readonly float $errorRate,
        public readonly int $p99Latency,
        public readonly int $activeApps,
        public readonly array $timeSeries,
        public readonly array $recentRequests,
    ) {}
}
```

### Service DTOs
```php
class CreateKeyRequest
{
    public function __construct(
        public readonly string $label,
        public readonly string $environment,
        public readonly array $scopes,
    ) {}
}

class PaymentRequest
{
    public function __construct(
        public readonly int $amount,
        public readonly string $currency,
        public readonly array $recipient,
        public readonly ?string $description,
        public readonly ?array $metadata,
        public readonly string $idempotencyKey,
    ) {}
}

class PaymentResult
{
    public function __construct(
        public readonly string $paymentId,
        public readonly string $status,
        public readonly int $amount,
        public readonly int $fee,
        public readonly string $reference,
    ) {}
}

class WebhookDelivery
{
    public function __construct(
        public readonly string $id,
        public readonly string $eventType,
        public readonly array $payload,
        public readonly string $status,
        public readonly int $attempts,
        public readonly ?int $responseCode,
        public readonly ?Carbon $deliveredAt,
    ) {}
}
```
