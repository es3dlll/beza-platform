<?php

declare(strict_types=1);

namespace Modules\Loyalty\Repositories;

use Modules\Loyalty\Models\LoyaltyReward;

class LoyaltyRewardRepository
{
    public function findById(string $id): ?LoyaltyReward
    {
        return LoyaltyReward::find($id);
    }

    public function findAvailable(?string $tierLevel = null): iterable
    {
        $query = LoyaltyReward::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            });

        if ($tierLevel) {
            $tierPriorities = ['bronze' => 0, 'silver' => 1, 'gold' => 2, 'platinum' => 3];
            $userPriority = $tierPriorities[$tierLevel] ?? 0;
            $query->where(function ($q) use ($tierPriorities, $userPriority) {
                $q->whereNull('tier_requirement')
                  ->orWhereIn('tier_requirement', ['bronze', 'silver', 'gold', 'platinum']);
            });
        }

        return $query->where(function ($q) {
            $q->where('stock', '>', 0)->orWhere('stock', 0);
        })->get();
    }

    public function update(string $id, array $data): LoyaltyReward
    {
        $reward = LoyaltyReward::findOrFail($id);
        $reward->update($data);
        return $reward->fresh();
    }

    public function create(array $data): LoyaltyReward
    {
        return LoyaltyReward::create($data);
    }
}
