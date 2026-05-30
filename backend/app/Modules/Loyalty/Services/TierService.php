<?php

declare(strict_types=1);

namespace Modules\Loyalty\Services;

use Modules\Loyalty\Events\TierUpgraded;
use Modules\Loyalty\Repositories\LoyaltyTierRepository;
use Modules\Loyalty\Repositories\LoyaltyPointsRepository;

class TierService
{
    public function __construct(
        private readonly LoyaltyTierRepository $tierRepository,
        private readonly LoyaltyPointsRepository $pointsRepository,
    ) {}

    public function determineTier(int $points): string
    {
        $tier = $this->tierRepository->findHighestReachable($points);
        return $tier?->level ?? 'bronze';
    }

    public function getMultiplier(string $tierLevel): float
    {
        $tier = $this->tierRepository->findByLevel($tierLevel);
        return $tier?->points_multiplier ?? 1.0;
    }

    public function getCashbackRate(string $tierLevel): float
    {
        $tier = $this->tierRepository->findByLevel($tierLevel);
        return $tier?->cashback_rate ?? 0;
    }

    public function syncAndUpgrade(string $userId): ?string
    {
        $points = $this->pointsRepository->findByUser($userId);
        if (!$points) return null;

        $currentTier = $points->tier_level;
        $newTier = $this->determineTier($points->lifetime_earned);

        if ($newTier !== $currentTier) {
            $this->pointsRepository->update($points->id, ['tier_level' => $newTier]);
            TierUpgraded::dispatch($userId, $currentTier, $newTier);
        }

        return $newTier;
    }
}
