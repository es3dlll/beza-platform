<?php

declare(strict_types=1);

namespace Modules\Loyalty\Services;

use Modules\Loyalty\Exceptions\RewardNotFoundException;
use Modules\Loyalty\Exceptions\InsufficientPointsException;
use Modules\Loyalty\Repositories\LoyaltyRewardRepository;

class RewardService
{
    public function __construct(
        private readonly LoyaltyRewardRepository $rewardRepository,
        private readonly PointsService $pointsService,
    ) {}

    public function claimReward(string $userId, string $rewardId): array
    {
        $reward = $this->rewardRepository->findById($rewardId);
        if (!$reward || !$reward->is_active) {
            throw new RewardNotFoundException($rewardId);
        }

        // Check stock
        if ($reward->stock > 0) {
            $this->rewardRepository->update($rewardId, ['stock' => $reward->stock - 1]);
        }

        // Redeem points
        $this->pointsService->redeem(new \Modules\Loyalty\DTOs\RedeemPointsDto(
            userId: $userId,
            points: $reward->points_cost,
            rewardId: $rewardId,
            description: 'Redeemed: ' . $reward->name,
        ));

        return [
            'reward' => $reward,
            'redeemed_points' => $reward->points_cost,
        ];
    }
}
