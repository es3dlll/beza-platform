<?php

declare(strict_types=1);

namespace Modules\Loyalty\Services;

use Illuminate\Support\Str;
use Modules\Loyalty\DTOs\AwardPointsDto;
use Modules\Loyalty\DTOs\RedeemPointsDto;
use Modules\Loyalty\Enums\PointsTransactionType;
use Modules\Loyalty\Events\PointsAwarded;
use Modules\Loyalty\Events\PointsRedeemed;
use Modules\Loyalty\Exceptions\InsufficientPointsException;
use Modules\Loyalty\Models\LoyaltyPoints;
use Modules\Loyalty\Repositories\LoyaltyPointsRepository;
use Modules\Loyalty\Repositories\LoyaltyPointsTransactionRepository;

class PointsService
{
    public function __construct(
        private readonly LoyaltyPointsRepository $pointsRepository,
        private readonly LoyaltyPointsTransactionRepository $transactionRepository,
        private readonly TierService $tierService,
    ) {}

    public function award(AwardPointsDto $dto): LoyaltyPoints
    {
        $pointsRecord = $this->pointsRepository->findOrCreate($dto->userId);

        $multiplier = $this->tierService->getMultiplier($pointsRecord->tier_level);
        $actualPoints = (int) round($dto->points * $multiplier);

        $balanceBefore = $pointsRecord->balance;

        $this->pointsRepository->update($pointsRecord->id, [
            'balance' => $balanceBefore + $actualPoints,
            'lifetime_earned' => $pointsRecord->lifetime_earned + $actualPoints,
        ]);

        $this->transactionRepository->create([
            'id' => (string) Str::ulid(),
            'user_id' => $dto->userId,
            'loyalty_points_id' => $pointsRecord->id,
            'type' => PointsTransactionType::EARNED->value,
            'points' => $actualPoints,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceBefore + $actualPoints,
            'reference_type' => $dto->referenceType,
            'reference_id' => $dto->referenceId,
            'description' => $dto->description ?? 'Points awarded',
            'expires_at' => now()->addYear(),
        ]);

        $this->tierService->syncAndUpgrade($dto->userId);

        PointsAwarded::dispatch($dto->userId, $actualPoints, $balanceBefore + $actualPoints, $dto->description ?? 'award');

        return $this->pointsRepository->findOrCreate($dto->userId);
    }

    public function redeem(RedeemPointsDto $dto): LoyaltyPoints
    {
        $pointsRecord = $this->pointsRepository->findOrCreate($dto->userId);

        if ($pointsRecord->balance < $dto->points) {
            throw new InsufficientPointsException($dto->points, $pointsRecord->balance);
        }

        $balanceBefore = $pointsRecord->balance;

        $this->pointsRepository->update($pointsRecord->id, [
            'balance' => $balanceBefore - $dto->points,
            'lifetime_redeemed' => $pointsRecord->lifetime_redeemed + $dto->points,
        ]);

        $this->transactionRepository->create([
            'id' => (string) Str::ulid(),
            'user_id' => $dto->userId,
            'loyalty_points_id' => $pointsRecord->id,
            'type' => PointsTransactionType::REDEEMED->value,
            'points' => $dto->points,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceBefore - $dto->points,
            'reference_type' => 'reward',
            'reference_id' => $dto->rewardId,
            'description' => $dto->description ?? 'Points redeemed',
        ]);

        PointsRedeemed::dispatch($dto->userId, $dto->points, $balanceBefore - $dto->points, $dto->rewardId);

        return $this->pointsRepository->findOrCreate($dto->userId);
    }

    public function getBalance(string $userId): LoyaltyPoints
    {
        return $this->pointsRepository->findOrCreate($userId);
    }
}
