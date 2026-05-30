<?php

declare(strict_types=1);

namespace Modules\Loyalty\Repositories;

use Modules\Loyalty\Models\LoyaltyPoints;

class LoyaltyPointsRepository
{
    public function findByUser(string $userId): ?LoyaltyPoints
    {
        return LoyaltyPoints::where('user_id', $userId)->first();
    }

    public function findOrCreate(string $userId): LoyaltyPoints
    {
        $points = $this->findByUser($userId);
        if ($points) {
            return $points;
        }
        return LoyaltyPoints::create([
            'id' => (string) \Illuminate\Support\Str::ulid(),
            'user_id' => $userId,
            'balance' => 0,
            'tier_level' => 'bronze',
        ]);
    }

    public function update(string $id, array $data): LoyaltyPoints
    {
        $lp = LoyaltyPoints::findOrFail($id);
        $lp->update($data);
        return $lp->fresh();
    }
}
