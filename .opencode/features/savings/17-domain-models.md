# Savings Domain Models

## Entity Definitions

### SavingsGoal
```php
class SavingsGoal
{
    public function __construct(
        public readonly string $id,
        public readonly int $userId,
        public string $name,
        public string $icon,
        public int $targetAmount,          // Smallest unit (SYP piasters)
        public int $currentAmount,
        public string $currency,            // SYP | USD
        public string $type,                // individual | team
        public bool $autoSaveEnabled,
        public ?string $autoSaveFrequency,  // daily | weekly
        public ?int $autoSaveAmount,
        public ?string $autoSaveTime,
        public bool $roundUpEnabled,
        public bool $goalLocked,
        public ?string $lockReleaseDate,
        public ?int $lockReleaseAmount,
        public string $status,              // active | completed | cancelled
        public ?string $targetDate,
        public ?string $completedAt,
        public string $cfeSubAccountId,
        public ?array $metadata,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public function getProgressPercentage(): float
    {
        if ($this->targetAmount <= 0) return 0;
        return round(($this->currentAmount / $this->targetAmount) * 100, 1);
    }

    public function isComplete(): bool
    {
        return $this->currentAmount >= $this->targetAmount;
    }

    public function isLocked(): bool
    {
        if (!$this->goalLocked) return false;
        if ($this->lockReleaseDate && now()->lessThan($this->lockReleaseDate)) return true;
        if ($this->lockReleaseAmount && $this->currentAmount < $this->lockReleaseAmount) return true;
        return false;
    }

    public function getRemainingAmount(): int
    {
        return max(0, $this->targetAmount - $this->currentAmount);
    }

    public function getDaysRemaining(): ?int
    {
        if (!$this->targetDate) return null;
        return max(0, now()->diffInDays($this->targetDate, false));
    }
}
```

### GoalTransaction
```php
class GoalTransaction
{
    public function __construct(
        public readonly string $id,
        public readonly string $goalId,
        public readonly int $userId,
        public string $type,                // deposit | withdrawal | profit | roundup
        public ?string $subType,            // manual | auto_save | roundup | etc.
        public int $amount,
        public int $fee,
        public int $penalty,
        public int $netAmount,
        public string $currency,
        public int $balanceBefore,
        public int $balanceAfter,
        public ?string $reference,
        public ?string $sourceTransactionId,
        public ?string $note,
        public string $createdAt,
    ) {}
}
```

### AutoSaveConfig
```php
class AutoSaveConfig
{
    public function __construct(
        public readonly string $goalId,
        public bool $enabled,
        public string $frequency,           // daily | weekly
        public int $amount,
        public string $time,                // HH:MM format
        public ?string $nextExecution,
        public ?string $lastExecution,
        public int $successiveSkips,
    ) {}

    public function isDue(): bool
    {
        if (!$this->enabled) return false;
        if (!$this->nextExecution) return false;
        return now()->greaterThanOrEqualTo($this->nextExecution);
    }

    public function calculateNextExecution(): string
    {
        $next = now()->setTimeFromTimeString($this->time);
        if ($this->frequency === 'weekly') {
            $next->addWeek();
        } else {
            $next->addDay();
        }
        if ($next->isPast()) {
            $next->addDay();
        }
        return $next->toDateTimeString();
    }
}
```

### RoundUpConfig
```php
class RoundUpConfig
{
    public function __construct(
        public readonly int $userId,
        public bool $enabled,
        public ?string $primaryGoalId,
        public int $roundToNearest,          // 1000 (default)
        public int $minRoundAmount,          // 100
        public int $dailyMax,
        public int $monthlyMax,
    ) {}

    public function calculateRoundUp(int $originalAmount): int
    {
        $rounded = (int) (ceil($originalAmount / $this->roundToNearest) * $this->roundToNearest);
        return $rounded - $originalAmount;
    }
}
```

### ProfitDistribution
```php
class ProfitDistribution
{
    public function __construct(
        public readonly string $id,
        public readonly string $goalId,
        public readonly int $userId,
        public int $amount,
        public string $period,               // monthly | quarterly | yearly
        public string $periodStart,
        public string $periodEnd,
        public float $weight,
        public int $poolTotal,
        public int $poolReturn,
        public int $managementFee,
        public int $netProfit,
        public string $distributedAt,
    ) {}

    public function getReturnRate(): float
    {
        if ($this->poolTotal <= 0) return 0;
        return round(($this->poolReturn / $this->poolTotal) * 100, 4);
    }
}
```

### SavingsTeam
```php
class SavingsTeam
{
    public function __construct(
        public readonly string $id,
        public string $name,
        public readonly string $goalId,
        public readonly int $createdBy,
        public string $inviteCode,
        public ?string $inviteCodeExpiresAt,
        public int $maxMembers,
        public string $status,               // active | completed | disbanded
        public string $createdAt,
    ) {}

    public function isInviteCodeValid(): bool
    {
        if (!$this->inviteCodeExpiresAt) return true;
        return now()->lessThan($this->inviteCodeExpiresAt);
    }
}
```

### SavingsTeamMember
```php
class SavingsTeamMember
{
    public function __construct(
        public readonly string $id,
        public readonly string $teamId,
        public readonly int $userId,
        public int $contribution,
        public string $role,                 // owner | admin | member
        public string $joinedAt,
        public ?string $leftAt,
        public string $status,               // active | inactive | removed | left
    ) {}
}
```

### Value Objects

```php
// GoalProgress
class GoalProgress
{
    public function __construct(
        public float $percentage,
        public int $saved,
        public int $remaining,
        public int $dailyRequired,
        public int $dailyAutoSave,
        public int $dailyGap,
        public ?string $predictedCompletion,
        public bool $onTrack,
        public array $milestones,            // Milestone[]
    ) {}
}

// GoalMilestone
class GoalMilestone
{
    public function __construct(
        public int $percentage,
        public bool $reached,
        public ?string $reachedAt,
        public ?string $projected,
    ) {}
}

// ProfitCalculationResult
class ProfitCalculationResult
{
    public function __construct(
        public int $poolTotal,
        public int $profitPool,
        public array $distributions,         // [['goal_id'=>, 'user_id'=>, 'amount'=>, 'weight'=>]]
    ) {}
}
```
