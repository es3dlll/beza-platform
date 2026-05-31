# Loyalty Backend Architecture

## Module Structure (Laravel)
```
app/Modules/Loyalty/
├── Controllers/
│   ├── PointsController.php              # Points balance, history
│   ├── TierController.php                # Tier info, progress, benefits
│   ├── RewardController.php              # Reward catalog
│   ├── RedemptionController.php          # Points redemption
│   ├── MerchantCampaignController.php    # Merchant campaign CRUD
│   └── CampaignRedemptionController.php  # Campaign redemptions
│
├── Actions/
│   ├── EarnPointsAction.php              # Points earning orchestration
│   ├── RedeemPointsAction.php            # Points redemption orchestration
│   ├── CheckTierUpgradeAction.php        # Tier qualification check
│   ├── ProcessTierDowngradeAction.php    # Tier downgrade handling
│   ├── ExpirePointsAction.php            # 12-month expiry processing
│   ├── CreateCampaignAction.php          # Merchant campaign creation
│   └── ProcessCampaignRedemptionAction.php
│
├── Services/
│   ├── PointsService.php                 # Points calculation, ledger, balance
│   ├── TierService.php                   # Tier assignment, upgrade/downgrade
│   ├── RewardCatalogService.php          # Catalog management, partner rewards
│   ├── RedemptionService.php            # Redemption processing, coupon creation
│   ├── MerchantLoyaltyService.php        # Merchant campaign management
│   ├── CampaignService.php              # Campaign activation, budget tracking
│   └── PointsExpiryService.php          # Rolling expiry calculation
│
├── Repositories/
│   ├── LoyaltyPointsRepository.php
│   ├── LoyaltyTierRepository.php
│   ├── LoyaltyRewardRepository.php
│   ├── LoyaltyRedemptionRepository.php
│   ├── LoyaltyMemberTierHistoryRepository.php
│   ├── LoyaltyMerchantCampaignRepository.php
│   └── LoyaltyCampaignRedemptionRepository.php
│
├── Models/
│   ├── LoyaltyPoints.php
│   ├── LoyaltyTier.php
│   ├── LoyaltyReward.php
│   ├── LoyaltyRedemption.php
│   ├── LoyaltyMemberTierHistory.php
│   ├── LoyaltyMerchantCampaign.php
│   └── LoyaltyCampaignRedemption.php
│
├── Policies/
│   ├── RewardPolicy.php
│   ├── RedemptionPolicy.php
│   └── MerchantCampaignPolicy.php
│
├── Events/
│   ├── PointsEarned.php
│   ├── PointsRedeemed.php
│   ├── PointsExpired.php
│   ├── TierUpgraded.php
│   ├── TierDowngraded.php
│   ├── PointsExpiringWarning.php
│   └── MerchantCampaignActivated.php
│
├── Jobs/
│   ├── ProcessTierUpgradeJob.php         # Batch tier upgrade processing
│   ├── ProcessPointsExpiryJob.php        # Daily expiry check
│   ├── NotifyPointsExpiryJob.php         # Expiry warning notifications
│   └── ProcessCampaignBudgetJob.php      # Campaign budget tracking
│
├── Listeners/
│   ├── ApplyTierBenefits.php             # Update fees/limits on upgrade
│   ├── SendTierUpgradeNotification.php
│   ├── LogPointsActivity.php
│   └── UpdateLoyaltyAnalytics.php
│
├── Rules/
│   ├── SufficientPoints.php
│   ├── ValidRedemptionAmount.php
│   └── ValidCampaignBudget.php
│
├── Enums/
│   ├── TierLevel.php                     # bronze, silver, gold, platinum
│   ├── RewardCategory.php                # fee_discount, airtime, gift_card, partner
│   ├── RedemptionStatus.php              # pending, completed, refunded, expired
│   ├── CampaignType.php                  # multiplier, fixed_points, cashback
│   └── CampaignStatus.php                # draft, active, paused, ended
│
├── Exceptions/
│   ├── InsufficientPointsException.php
│   ├── TierUpgradeNotAvailableException.php
│   ├── CampaignBudgetExhaustedException.php
│   └── InvalidRedemptionException.php
│
├── Providers/
│   └── LoyaltyServiceProvider.php
│
└── routes/
    └── api.php                           # Loyalty API routes
```

## Service Layer Detail

### PointsService
```php
class PointsService
{
    public function __construct(
        private LoyaltyPointsRepository $pointsRepo,
        private TierService $tierService,
        private PointsExpiryService $expiryService,
        private LoyaltyMemberTierHistoryRepository $tierHistoryRepo,
        private EventService $eventService,
    ) {}

    public function earn(EarnPointsRequest $request): PointsTransaction
    {
        $tier = $this->tierService->getCurrentTier($request->userId);
        $multiplier = $this->tierService->getMultiplier($tier);
        $points = (int) floor($request->transactionAmount / 1000) * $multiplier;

        if ($points <= 0) return new PointsTransaction(amount: 0);

        $entry = $this->pointsRepo->create([
            'user_id' => $request->userId,
            'amount' => $points,
            'type' => 'earned',
            'source' => $request->source,
            'source_transaction_id' => $request->transactionId,
            'tier_multiplier' => $multiplier,
            'expires_at' => $this->expiryService->calculateExpiry($points),
        ]);

        $this->pointsRepo->incrementBalance($request->userId, $points);
        $this->tierService->checkAndUpgrade($request->userId);
        $this->eventService->emitPointsEarned($entry);

        return $entry;
    }

    public function getBalance(int $userId): int
    {
        return $this->pointsRepo->getBalance($userId);
    }

    public function getHistory(int $userId, int $page = 1, array $filters = []): array
    {
        return $this->pointsRepo->getPaginatedHistory($userId, $page, $filters);
    }
}
```

### TierService
```php
class TierService
{
    private array $tierThresholds = [
        TierLevel::BRONZE => 0,
        TierLevel::SILVER => 10000,
        TierLevel::GOLD => 50000,
        TierLevel::PLATINUM => 200000,
    ];

    private array $tierMultipliers = [
        TierLevel::BRONZE => 1.0,
        TierLevel::SILVER => 1.2,
        TierLevel::GOLD => 1.5,
        TierLevel::PLATINUM => 2.0,
    ];

    public function __construct(
        private LoyaltyPointsRepository $pointsRepo,
        private LoyaltyMemberTierHistoryRepository $tierHistoryRepo,
        private LoyaltyTierRepository $tierRepo,
        private EventService $eventService,
    ) {}

    public function getCurrentTier(int $userId): TierLevel
    {
        $history = $this->tierHistoryRepo->getCurrent($userId);
        return $history?->tier_level ?? TierLevel::BRONZE;
    }

    public function getMultiplier(TierLevel $tier): float
    {
        return $this->tierMultipliers[$tier->value] ?? 1.0;
    }

    public function getTierProgress(int $userId): TierProgress
    {
        $rollingTotal = $this->pointsRepo->getRolling12MonthTotal($userId);
        $currentTier = $this->getCurrentTier($userId);
        $nextTier = $this->getNextTier($currentTier);

        if (!$nextTier) {
            return new TierProgress(
                currentTier: $currentTier,
                currentPoints: $rollingTotal,
                pointsRequired: $rollingTotal,
                progress: 1.0,
                nextTier: null,
            );
        }

        $currentThreshold = $this->tierThresholds[$currentTier->value];
        $nextThreshold = $this->tierThresholds[$nextTier->value];

        return new TierProgress(
            currentTier: $currentTier,
            currentPoints: $rollingTotal,
            pointsRequired: $nextThreshold,
            progress: ($rollingTotal - $currentThreshold) / ($nextThreshold - $currentThreshold),
            nextTier: $nextTier,
        );
    }

    public function checkAndUpgrade(int $userId): ?TierLevel
    {
        $rollingTotal = $this->pointsRepo->getRolling12MonthTotal($userId);
        $currentTier = $this->getCurrentTier($userId);

        foreach (array_reverse(TierLevel::cases()) as $tier) {
            if ($rollingTotal >= $this->tierThresholds[$tier->value]) {
                if ($tier !== $currentTier && $this->isHigherTier($tier, $currentTier)) {
                    $this->upgradeTier($userId, $tier, $rollingTotal);
                    return $tier;
                }
                break;
            }
        }
        return null;
    }

    private function upgradeTier(int $userId, TierLevel $newTier, int $rollingTotal): void
    {
        $this->tierHistoryRepo->create([
            'user_id' => $userId,
            'tier_level' => $newTier,
            'rolling_total_points' => $rollingTotal,
            'action' => 'upgrade',
        ]);
        $this->eventService->emitTierUpgraded($userId, $newTier);
    }

    private function isHigherTier(TierLevel $a, TierLevel $b): bool
    {
        $order = array_flip([TierLevel::BRONZE, TierLevel::SILVER, TierLevel::GOLD, TierLevel::PLATINUM]);
        return ($order[$a->value] ?? 0) > ($order[$b->value] ?? 0);
    }
}
```

### RewardCatalogService
```php
class RewardCatalogService
{
    public function __construct(
        private LoyaltyRewardRepository $rewardRepo,
    ) {}

    public function getAvailableRewards(?int $userId = null): array
    {
        return $this->rewardRepo->findActive();
    }

    public function getRewardById(int $rewardId): LoyaltyReward
    {
        return $this->rewardRepo->findOrFail($rewardId);
    }

    public function getRewardsByCategory(RewardCategory $category): array
    {
        return $this->rewardRepo->findByCategory($category);
    }

    public function getFeaturedRewards(int $limit = 4): array
    {
        return $this->rewardRepo->findFeatured($limit);
    }
}
```

### RedemptionService
```php
class RedemptionService
{
    public function __construct(
        private LoyaltyPointsRepository $pointsRepo,
        private LoyaltyRedemptionRepository $redemptionRepo,
        private RewardCatalogService $catalogService,
        private EventService $eventService,
    ) {}

    public function redeem(RedemptionRequest $request): RedemptionResult
    {
        $reward = $this->catalogService->getRewardById($request->rewardId);
        $balance = $this->pointsRepo->getBalance($request->userId);

        throw_if($balance < $reward->pointCost, new InsufficientPointsException());

        $redemption = $this->redemptionRepo->create([
            'user_id' => $request->userId,
            'reward_id' => $reward->id,
            'points_spent' => $reward->pointCost,
            'syp_value' => $reward->pointCost, // 1:1
            'status' => RedemptionStatus::COMPLETED,
        ]);

        $this->pointsRepo->decrementBalance($request->userId, $reward->pointCost);
        $this->pointsRepo->create([
            'user_id' => $request->userId,
            'amount' => -$reward->pointCost,
            'type' => 'redeemed',
            'source' => 'redemption',
            'source_transaction_id' => $redemption->id,
        ]);

        $this->eventService->emitPointsRedeemed($redemption);

        return new RedemptionResult(
            redemptionId: $redemption->id,
            rewardName: $reward->name,
            pointsSpent: $reward->pointCost,
            sypValue: $reward->pointCost,
            couponCode: $reward->type === 'fee_discount' ? $this->generateCoupon() : null,
        );
    }

    private function generateCoupon(): string
    {
        return 'CPN_' . strtoupper(Str::random(8));
    }
}
```

### MerchantLoyaltyService
```php
class MerchantLoyaltyService
{
    public function createCampaign(int $merchantId, CreateCampaignRequest $request): LoyaltyMerchantCampaign
    {
        return $this->campaignRepo->create([
            'merchant_id' => $merchantId,
            'name' => $request->name,
            'type' => $request->type,
            'multiplier' => $request->multiplier ?? 2.0,
            'min_transaction_amount' => $request->minAmount,
            'budget_syp' => $request->budget,
            'budget_remaining' => $request->budget,
            'start_date' => $request->startDate,
            'end_date' => $request->endDate,
            'status' => CampaignStatus::ACTIVE,
        ]);
    }

    public function processCampaignTransaction(Transaction $transaction): void
    {
        $campaign = $this->campaignRepo->findActiveForMerchant($transaction->merchant_id);
        if (!$campaign || $campaign->budget_remaining <= 0) return;

        if ($transaction->amount < $campaign->min_transaction_amount) return;

        $bonusPoints = (int) floor($transaction->amount / 1000) * ($campaign->multiplier - 1);
        // ... allocate bonus points from campaign budget
    }
}
```

### CampaignService
```php
class CampaignService
{
    public function trackRedemption(int $campaignId, int $userId, int $pointsAwarded): void
    {
        $campaign = $this->campaignRepo->findOrFail($campaignId);
        $newRemaining = $campaign->budget_remaining - $pointsAwarded;
        $this->campaignRepo->update($campaignId, [
            'budget_remaining' => max(0, $newRemaining),
        ]);

        $this->campaignRedemptionRepo->create([
            'campaign_id' => $campaignId,
            'user_id' => $userId,
            'points_awarded' => $pointsAwarded,
        ]);

        if ($newRemaining <= 0) {
            $this->campaignRepo->update($campaignId, ['status' => CampaignStatus::ENDED]);
            // Notify merchant: campaign budget exhausted
        }
    }
}
```

### PointsExpiryService
```php
class PointsExpiryService
{
    public function calculateExpiry(int $points): Carbon
    {
        return now()->addMonths(12);
    }

    public function processExpiry(): int
    {
        $expiredPoints = $this->pointsRepo->getExpiringPoints();
        $totalExpired = 0;

        foreach ($expiredPoints as $entry) {
            $this->pointsRepo->decrementBalance($entry->user_id, $entry->amount);
            $this->pointsRepo->update($entry->id, ['expired_at' => now()]);
            $totalExpired += $entry->amount;
        }

        return $totalExpired;
    }

    public function getPointsExpiringSoon(int $userId, int $daysThreshold = 30): int
    {
        return $this->pointsRepo->getTotalExpiringBefore(
            $userId, now()->addDays($daysThreshold)
        );
    }
}
```

## API Endpoints
```php
// Loyalty Module Routes (prefix: /api/v1/loyalty)

Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    // Points
    Route::get('/points', [PointsController::class, 'balance']);
    Route::get('/points/history', [PointsController::class, 'history']);
    Route::get('/points/today', [PointsController::class, 'todayEarnings']);

    // Tier
    Route::get('/tier', [TierController::class, 'current']);
    Route::get('/tier/progress', [TierController::class, 'progress']);
    Route::get('/tier/benefits', [TierController::class, 'benefits']);

    // Rewards
    Route::get('/rewards', [RewardController::class, 'index']);
    Route::get('/rewards/featured', [RewardController::class, 'featured']);
    Route::get('/rewards/{id}', [RewardController::class, 'show']);

    // Redemption
    Route::post('/redeem', [RedemptionController::class, 'redeem']);
    Route::get('/redemptions', [RedemptionController::class, 'history']);

    // Merchant Campaigns
    Route::get('/merchant/campaigns', [MerchantCampaignController::class, 'index']);
    Route::post('/merchant/campaigns', [MerchantCampaignController::class, 'create']);
    Route::get('/merchant/campaigns/{id}', [MerchantCampaignController::class, 'show']);
    Route::put('/merchant/campaigns/{id}', [MerchantCampaignController::class, 'update']);
    Route::post('/merchant/campaigns/{id}/pause', [MerchantCampaignController::class, 'pause']);
    Route::post('/merchant/campaigns/{id}/resume', [MerchantCampaignController::class, 'resume']);
    Route::post('/merchant/campaigns/{id}/end', [MerchantCampaignController::class, 'end']);
});
```
