# Cards Domain Models

## Core Domain Objects

### Card
```php
<?php

namespace App\Modules\Cards\Models;

class Card
{
    public function __construct(
        public readonly int $id,
        public readonly int $userId,
        public readonly int $tenantId,
        public readonly string $bin,
        public readonly string $panHash,
        public readonly string $panSuffix,
        public readonly string $expiry,
        public readonly CardType $cardType,
        public readonly CardNetwork $cardNetwork,
        public CardStatus $status,
        public readonly string $issuerId,
        public readonly string $cardProgram,
        public readonly Currency $currency,
        public array $limits,              // ['online' => int, 'pos' => int, 'atm' => int, 'international' => int]
        public int $kycLevelAtIssue,
        public ?string $nickname,
        public ?array $metadata,
        public int $spentToday,
        public ?Carbon $spentTodayAt,
        public ?Carbon $lastUsedAt,
        public ?Carbon $issuedAt,
        public ?Carbon $activatedAt,
        public ?Carbon $closedAt,
        public ?Carbon $lostAt,
    ) {}

    public function isActive(): bool
    {
        return $this->status === CardStatus::ACTIVE;
    }

    public function isFrozen(): bool
    {
        return $this->status === CardStatus::FROZEN;
    }

    public function canTransact(): bool
    {
        return $this->status === CardStatus::ACTIVE;
    }

    public function canAuthorize(int $amount, string $category): bool
    {
        if (!$this->canTransact()) return false;
        $limit = $this->limits[$category] ?? 0;
        if ($limit === 0) return false; // Category disabled
        $this->resetDailyIfNeeded();
        $remaining = $limit - $this->spentToday;
        return $amount <= $remaining;
    }

    public function recordSpending(int $amount): void
    {
        $this->resetDailyIfNeeded();
        $this->spentToday += $amount;
        $this->lastUsedAt = Carbon::now();
    }

    private function resetDailyIfNeeded(): void
    {
        $today = Carbon::today();
        if ($this->spentTodayAt === null || !$this->spentTodayAt->isToday()) {
            $this->spentToday = 0;
            $this->spentTodayAt = $today;
        }
    }

    public function freeze(): void
    {
        $this->status = CardStatus::FROZEN;
    }

    public function unfreeze(): void
    {
        $this->status = CardStatus::ACTIVE;
    }

    public function close(): void
    {
        $this->status = CardStatus::CLOSED;
        $this->closedAt = Carbon::now();
    }

    public function reportLost(): void
    {
        $this->status = CardStatus::LOST;
        $this->lostAt = Carbon::now();
    }

    public function isExpired(): bool
    {
        return Carbon::createFromFormat('m/y', $this->expiry)->isPast();
    }
}

enum CardType: string
{
    case VIRTUAL = 'virtual';
    case PHYSICAL = 'physical';
}

enum CardNetwork: string
{
    case MASTERCARD = 'mastercard';
    case VISA = 'visa';
    case LOCAL_SCHEME = 'local_scheme';
}

enum CardStatus: string
{
    case ACTIVE = 'active';
    case FROZEN = 'frozen';
    case CLOSED = 'closed';
    case LOST = 'lost';
    case EXPIRED = 'expired';
}

enum Currency: string
{
    case SYP = 'SYP';
    case USD = 'USD';
}
```

### CardTransaction
```php
class CardTransaction
{
    public function __construct(
        public readonly int $id,
        public readonly int $cardId,
        public readonly int $tenantId,
        public readonly string $uuid,
        public readonly TransactionType $type,        // purchase, atm, refund, fee, reversal
        public readonly int $amount,
        public readonly int $fee,
        public readonly int $tip,
        public readonly Currency $currency,
        public readonly ?Currency $billingCurrency,
        public readonly ?float $fxRate,
        public readonly ?int $originalAmount,
        public readonly string $merchantName,
        public readonly string $merchantCategory,
        public readonly string $merchantCountry,
        public readonly ?string $merchantCity,
        public readonly ?string $merchantId,
        public TransactionStatus $status,             // authorized, settled, declined, refunded, reversed
        public readonly ?string $declineReason,
        public readonly ?string $authCode,
        public readonly ?string $rrn,
        public readonly ?string $stan,
        public readonly ?Carbon $localTxnTime,
        public readonly ?string $authResponse,
        public readonly bool $cardPresent,
        public readonly bool $chipTransaction,
        public readonly bool $contactless,
        public readonly bool $onlineAuth,
        public readonly bool $recurring,
        public readonly bool $tokenized,
        public readonly ?string $eci,
        public readonly ?float $fraudScore,
        public readonly ?Carbon $settledAt,
        public readonly ?Carbon $reversalAt,
    ) {}

    public function isPurchase(): bool
    {
        return $this->type === TransactionType::PURCHASE;
    }

    public function isAtm(): bool
    {
        return $this->type === TransactionType::ATM;
    }

    public function isRefund(): bool
    {
        return $this->type === TransactionType::REFUND;
    }

    public function isSettled(): bool
    {
        return $this->status === TransactionStatus::SETTLED;
    }

    public function canReverse(): bool
    {
        return $this->status === TransactionStatus::SETTLED
            && $this->settledAt !== null
            && $this->settledAt->diffInHours(Carbon::now()) < 24;
    }
}
```

### CardPin
```php
class CardPin
{
    public function __construct(
        public readonly int $id,
        public readonly int $cardId,
        public readonly string $pinHash,
        public int $pinAttempts,
        public ?Carbon $lastAttemptAt,
        public ?Carbon $blockedUntil,
        public readonly ?Carbon $pinChangedAt,
    ) {}

    public function isBlocked(): bool
    {
        return $this->blockedUntil !== null && $this->blockedUntil->isFuture();
    }

    public function recordFailedAttempt(): void
    {
        $this->pinAttempts++;
        $this->lastAttemptAt = Carbon::now();
        if ($this->pinAttempts >= 3) {
            $this->blockedUntil = Carbon::now()->addHours(24);
        }
    }

    public function resetAttempts(): void
    {
        $this->pinAttempts = 0;
        $this->blockedUntil = null;
        $this->lastAttemptAt = null;
    }
}
```

### CardToken
```php
class CardToken
{
    public function __construct(
        public readonly int $id,
        public readonly int $cardId,
        public readonly string $token,               // DPAN from TSP
        public readonly Carbon $tokenExpires,
        public readonly string $deviceId,
        public readonly ?string $deviceName,
        public readonly WalletType $walletType,      // apple_pay, google_pay
        public TokenStatus $status,                   // active, revoked, suspended
        public readonly ?string $tspReference,
        public ?Carbon $lastUsedAt,
    ) {}

    public function isActive(): bool
    {
        return $this->status === TokenStatus::ACTIVE && $this->tokenExpires->isFuture();
    }

    public function revoke(): void
    {
        $this->status = TokenStatus::REVOKED;
    }
}

enum WalletType: string
{
    case APPLE_PAY = 'apple_pay';
    case GOOGLE_PAY = 'google_pay';
}

enum TokenStatus: string
{
    case ACTIVE = 'active';
    case REVOKED = 'revoked';
    case SUSPENDED = 'suspended';
}
```
