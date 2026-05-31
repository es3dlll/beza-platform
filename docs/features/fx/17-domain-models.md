# FX Engine Domain Models

## Core Domain Objects

### FxRate
```php
class FxRate
{
    public function __construct(
        public readonly int $id,
        public readonly CurrencyPair $pair,
        public readonly Money $bid,
        public readonly Money $ask,
        public readonly Money $mid,
        public readonly float $spreadPct,
        public readonly Money $bezaRate,
        public readonly string $source,
        public readonly int $providerId,
        public readonly ?int $responseTimeMs,
        public readonly bool $isStale,
        public readonly bool $isOverride,
        public readonly ?int $overrideBy,
        public readonly ?string $overrideReason,
        public readonly Carbon $recordedAt,
        public readonly ?Carbon $expiresAt,
    ) {}

    public function isFresh(int $maxAgeSeconds = 15): bool
    {
        return !$this->isStale && $this->recordedAt->diffInSeconds(now()) <= $maxAgeSeconds;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt && $this->expiresAt->isPast();
    }

    public function withStaleIndicator(): self
    {
        return new self(...[...get_object_vars($this), 'isStale' => true]);
    }
}

enum CurrencyPair: string
{
    case SYP_USD = 'SYP/USD';
    case SYP_EUR = 'SYP/EUR';
    case USD_EUR = 'USD/EUR';

    public function base(): Currency
    {
        return match ($this) {
            self::SYP_USD => Currency::SYP,
            self::SYP_EUR => Currency::SYP,
            self::USD_EUR => Currency::USD,
        };
    }

    public function quote(): Currency
    {
        return match ($this) {
            self::SYP_USD => Currency::USD,
            self::SYP_EUR => Currency::EUR,
            self::USD_EUR => Currency::EUR,
        };
    }
}
```

### FxRateProvider
```php
class FxRateProvider
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly RateProviderType $type,
        public readonly string $handlerClass,
        public readonly int $priority,
        public RateProviderStatus $status,
        public readonly array $supportedPairs,
        public readonly ?string $baseUrl,
        public readonly ?string $healthUrl,
        public readonly ?string $credentialsEncrypted,
        public readonly int $timeoutMs,
        public readonly int $retryCount,
        public readonly int $consecutiveFailures,
        public readonly int $maxConsecutiveFailures,
        public readonly ?Carbon $circuitBreakerUntil,
        public readonly ?array $metadata,
        public readonly ?Carbon $lastSuccessAt,
        public readonly ?Carbon $lastFailureAt,
        public readonly ?int $avgResponseTimeMs,
        public readonly ?float $uptime24h,
    ) {}

    public function isAvailable(): bool
    {
        if ($this->status === RateProviderStatus::INACTIVE) return false;
        if ($this->circuitBreakerUntil && $this->circuitBreakerUntil->isFuture()) return false;
        return true;
    }

    public function recordSuccess(int $responseTimeMs): void
    {
        $this->consecutiveFailures = 0;
        $this->lastSuccessAt = now();
        $this->status = RateProviderStatus::ACTIVE;
        $this->circuitBreakerUntil = null;
        // Update rolling average
        $this->avgResponseTimeMs = $this->avgResponseTimeMs
            ? (int)(($this->avgResponseTimeMs * 0.9) + ($responseTimeMs * 0.1))
            : $responseTimeMs;
    }

    public function recordFailure(string $reason): void
    {
        $this->consecutiveFailures++;
        $this->lastFailureAt = now();
        $this->lastFailureReason = $reason;

        if ($this->consecutiveFailures >= $this->maxConsecutiveFailures) {
            $this->status = RateProviderStatus::DEGRADED;
            $this->circuitBreakerUntil = now()->addMinutes(5);
        }
    }
}

enum RateProviderType: string
{
    case API = 'api';
    case SCRAPER = 'scraper';
    case MANUAL = 'manual';
}

enum RateProviderStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case DEGRADED = 'degraded';
}
```

### FxRateLock
```php
class FxRateLock
{
    public function __construct(
        public readonly int $id,
        public readonly string $lockId,
        public readonly int $userId,
        public readonly CurrencyPair $pair,
        public readonly Money $rate,
        public readonly Money $amount,
        public readonly Currency $sourceCurrency,
        public readonly Currency $targetCurrency,
        public RateLockStatus $status,
        public readonly ?string $transactionId,
        public readonly Carbon $expiresAt,
        public readonly ?Carbon $usedAt,
        public readonly Carbon $createdAt,
    ) {}

    public function isExpired(): bool
    {
        return $this->expiresAt->isPast();
    }

    public function remainingSeconds(): int
    {
        return max(0, $this->expiresAt->diffInSeconds(now()));
    }

    public function use(string $transactionId): void
    {
        $this->status = RateLockStatus::USED;
        $this->transactionId = $transactionId;
        $this->usedAt = now();
    }
}

enum RateLockStatus: string
{
    case ACTIVE = 'active';
    case USED = 'used';
    case EXPIRED = 'expired';
    case RELEASED = 'released';
}
```

### FxConversion
```php
class FxConversion
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $uuid,
        public readonly int $userId,
        public readonly ?string $lockId,
        public readonly ?int $rateId,
        public readonly int $sourceWalletId,
        public readonly int $targetWalletId,
        public readonly Currency $sourceCurrency,
        public readonly Currency $targetCurrency,
        public readonly Money $sourceAmount,
        public readonly Money $targetAmount,
        public readonly Money $rateUsed,
        public readonly Money $midRate,
        public readonly float $spreadPct,
        public readonly Money $spreadAmount,
        public readonly Money $fee,
        public readonly Money $total,
        public ConversionStatus $status,
        public readonly ?string $cfeReference,
        public readonly ?string $reference,
        public readonly Carbon $createdAt,
    ) {}

    public function isCompleted(): bool
    {
        return $this->status === ConversionStatus::COMPLETED;
    }
}

enum ConversionStatus: string
{
    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case REVERSED = 'reversed';
}
```

### Value Objects
```php
class BezaRate
{
    public function __construct(
        public readonly CurrencyPair $pair,
        public readonly Money $mid,
        public readonly Money $bid,
        public readonly Money $ask,
        public readonly Money $bezaRate,
        public readonly float $spread,
        public readonly array $sources,
        public readonly Carbon $lastUpdated,
    ) {}
}

class RateResult
{
    public function __construct(
        public readonly CurrencyPair $pair,
        public readonly Money $bid,
        public readonly Money $ask,
        public readonly Money $mid,
        public readonly string $source,
        public readonly int $providerId,
        public readonly int $responseTimeMs,
        public readonly Carbon $timestamp,
        public readonly array $metadata = [],
    ) {}
}

class LockRateRequest
{
    public function __construct(
        public readonly int $userId,
        public readonly CurrencyPair $pair,
        public readonly Money $rate,
        public readonly Money $amount,
    ) {}
}

class RateLockResult
{
    public function __construct(
        public readonly string $lockId,
        public readonly Money $rate,
        public readonly Carbon $expiresAt,
        public readonly int $remainingSeconds,
    ) {}
}

class ConvertRequest
{
    public function __construct(
        public readonly int $userId,
        public readonly string $lockId,
        public readonly int $sourceWalletId,
        public readonly int $targetWalletId,
        public readonly Money $amount,
        public readonly string $pinHash,
        public readonly string $idempotencyKey,
    ) {}
}

class ConversionResult
{
    public function __construct(
        public readonly string $conversionId,
        public readonly ConversionStatus $status,
        public readonly Money $sourceAmount,
        public readonly Money $targetAmount,
        public readonly Money $rateUsed,
        public readonly Money $fee,
        public readonly string $reference,
        public readonly ?string $cfeReference,
        public readonly Carbon $timestamp,
        public readonly ?Receipt $receipt = null,
    ) {}
}

class Anomaly
{
    public function __construct(
        public readonly string $type,       // SPREAD_WIDENING, PRICE_SPIKE, PROVIDER_DIVERGENCE
        public readonly CurrencyPair $pair,
        public readonly string $severity,   // info, warning, critical
        public readonly string $message,
        public readonly ?array $data = null,
    ) {}
}

// Extended Currency enum for EUR support
enum Currency: string
{
    case SYP = 'SYP';
    case USD = 'USD';
    case EUR = 'EUR';

    public function symbol(): string
    {
        return match ($this) {
            self::SYP => 'ل.س',
            self::USD => '$',
            self::EUR => '€',
        };
    }

    public function decimals(): int
    {
        return match ($this) {
            self::SYP => 0,
            self::USD => 2,
            self::EUR => 2,
        };
    }
}
```

### Service DTOs
```php
class RateRequest
{
    public function __construct(
        public readonly CurrencyPair $pair,
        public readonly ?User $user = null,
    ) {}
}

class ProviderConfig
{
    public function __construct(
        public readonly string $apiKey,
        public readonly string $apiSecret,
        public readonly string $endpoint,
        public readonly array $headers = [],
        public readonly array $params = [],
    ) {}
}
```
