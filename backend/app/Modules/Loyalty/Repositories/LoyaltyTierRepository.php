<?php

declare(strict_types=1);

namespace Modules\Loyalty\Repositories;

use Modules\Loyalty\Models\LoyaltyTier;

final class LoyaltyTierRepository
{
    public function findByLevel(string $level): ?LoyaltyTier
    {
        return LoyaltyTier::where('level', $level)->where('is_active', true)->first();
    }

    public function findHighestReachable(int $points): ?LoyaltyTier
    {
        return LoyaltyTier::where('min_points', '<=', $points)
            ->where('is_active', true)
            ->orderByDesc('min_points')
            ->first();
    }

    public function all(): iterable
    {
        return LoyaltyTier::where('is_active', true)->orderBy('min_points')->get();
    }

    public function create(array $data): LoyaltyTier
    {
        return LoyaltyTier::create($data);
    }
}
