<?php

declare(strict_types=1);

namespace Modules\Marketplace\Services;

use Modules\Marketplace\Enums\LoyaltyAction;
use Modules\Marketplace\Exceptions\InsufficientPointsException;
use Modules\Marketplace\Models\LoyaltyPoint;

final class LoyaltyService
{
    public function earnPoints(string $userId, int $amount, string $action): void
    {
        $points = (int) floor($amount / 1000);

        if ($points <= 0) {
            return;
        }

        LoyaltyPoint::create([
            'user_id' => $userId,
            'points' => $points,
            'action' => $action,
            'created_at' => now(),
        ]);
    }

    public function getBalance(string $userId): int
    {
        return (int) LoyaltyPoint::where('user_id', $userId)->sum('points');
    }

    public function redeemPoints(string $userId, int $points): int
    {
        $balance = $this->getBalance($userId);

        if ($balance < $points) {
            throw new InsufficientPointsException();
        }

        $sypValue = (int) ($points * 10);

        LoyaltyPoint::create([
            'user_id' => $userId,
            'points' => -$points,
            'action' => LoyaltyAction::Redemption->value,
            'reference_type' => 'loyalty_redemption',
            'created_at' => now(),
        ]);

        return $sypValue;
    }

    public function getHistory(string $userId): iterable
    {
        return LoyaltyPoint::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
