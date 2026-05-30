# Loyalty Domain Models

## Core Domain Objects

### PointsTransaction
```php
class PointsTransaction
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $userId,
        public readonly int $amount,
        public readonly string $type,          // earned, redeemed, expired, adjusted
        public readonly string $source,
        public readonly ?int $sourceTransactionId,
        public readonly float $tierMultiplier,
        public readonly int $runningBalance,
        public readonly ?Carbon $expiresAt,
        public readonly ?Carbon $expiredAt,
        public readonly Carbon $createdAt,
    ) {}

    public function isCredit(): bool
    {
        return $this->amount > 0;
    }

    public function isDebit(): bool
    {
        return $this->amount < 0;
    }

    public function isExpired(): bool
    {
        return $this->expiredAt !== null;
    }

    public function getSypValue(): int
    {
        return abs($this->amount); // 1:1 conversion
    }
}
```

### TierProgress
```php
class TierProgress
{
    public function __construct(
        public readonly TierLevel $currentTier,
        public readonly int $currentPoints,
        public readonly int $pointsRequired,
        public readonly float $progress,
        public readonly ?TierLevel $nextTier,
    ) {}

    public function getPointsRemaining(): int
    {
        return $this->nextTier ? max(0, $this->pointsRequired - $this->currentPoints) : 0;
    }

    public function isMaxTier(): bool
    {
        return $this->nextTier === null;
    }
}

enum TierLevel: string
{
    case BRONZE = 'bronze';
    case SILVER = 'silver';
    case GOLD = 'gold';
    case PLATINUM = 'platinum';

    public function nameAr(): string
    {
        return match ($this) {
            self::BRONZE => 'برونز',
            self::SILVER => 'فضي',
            self::GOLD => 'ذهبي',
            self::PLATINUM => 'بلاتيني',
        };
    }
}
```

### Reward
```php
class Reward
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $category,
        public readonly string $description,
        public readonly int $pointCost,
        public readonly int $sypValue,
        public readonly ?string $imageUrl,
        public readonly ?string $provider,
        public readonly bool $featured,
        public readonly bool $popular,
        public readonly ?int $stock,
        public readonly string $status,
    ) {}

    public function isAvailable(): bool
    {
        return $this->status === 'active' && ($this->stock === null || $this->stock > 0);
    }

    public function canAfford(int $userPoints): bool
    {
        return $userPoints >= $this->pointCost;
    }
}

enum RewardCategory: string
{
    case FEE_DISCOUNT = 'fee_discount';
    case AIRTIME = 'airtime';
    case GIFT_CARD = 'gift_card';
    case PARTNER_OFFER = 'partner_offer';
}
```

### Redemption
```php
class Redemption
{
    public function __construct(
        public readonly int $id,
        public readonly int $userId,
        public readonly int $rewardId,
        public readonly int $pointsSpent,
        public readonly int $sypValue,
        public readonly string $status,
        public readonly ?string $couponCode,
        public readonly ?Carbon $couponExpiresAt,
        public readonly Carbon $createdAt,
    ) {}

    public function isFeeDiscount(): bool
    {
        return $this->couponCode !== null;
    }
}

enum RedemptionStatus: string
{
    case COMPLETED = 'completed';
    case REFUNDED = 'refunded';
    case EXPIRED = 'expired';
}
```

### MerchantCampaign
```php
class MerchantCampaign
{
    public function __construct(
        public readonly int $id,
        public readonly int $merchantId,
        public readonly string $name,
        public readonly string $type,
        public readonly ?float $multiplier,
        public readonly ?int $fixedPoints,
        public readonly ?int $minTransactionAmount,
        public readonly int $budgetSyp,
        public readonly int $budgetRemaining,
        public readonly Carbon $startDate,
        public readonly Carbon $endDate,
        public readonly string $status,
        public readonly int $redemptionCount,
        public readonly int $totalPointsAwarded,
    ) {}

    public function isActive(): bool
    {
        return $this->status === 'active'
            && $this->startDate->isPast()
            && $this->endDate->isFuture()
            && $this->budgetRemaining > 0;
    }

    public function hasBudgetFor(int $pointsCost): bool
    {
        return $this->budgetRemaining >= $pointsCost;
    }
}

enum CampaignType: string
{
    case MULTIPLIER = 'multiplier';
    case FIXED_POINTS = 'fixed_points';
    case CASHBACK = 'cashback';
}
```

### Service DTOs
```php
class EarnPointsRequest
{
    public function __construct(
        public readonly int $userId,
        public readonly int $transactionAmount,
        public readonly string $source,
        public readonly ?int $transactionId,
    ) {}
}

class RedemptionRequest
{
    public function __construct(
        public readonly int $userId,
        public readonly int $rewardId,
        public readonly string $pin,
    ) {}
}

class RedemptionResult
{
    public function __construct(
        public readonly string $redemptionId,
        public readonly string $rewardName,
        public readonly int $pointsSpent,
        public readonly int $sypValue,
        public readonly ?string $couponCode,
    ) {}
}

class CreateCampaignRequest
{
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly ?float $multiplier,
        public readonly ?int $minAmount,
        public readonly int $budget,
        public readonly string $startDate,
        public readonly string $endDate,
    ) {}
}
```
