<?php

declare(strict_types=1);

namespace Modules\Loyalty\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Loyalty\DTOs\AwardPointsDto;
use Modules\Loyalty\DTOs\RedeemPointsDto;
use Modules\Loyalty\Exceptions\InsufficientPointsException;
use Modules\Loyalty\Exceptions\RewardNotFoundException;
use Modules\Loyalty\Http\Requests\AwardPointsRequest;
use Modules\Loyalty\Http\Requests\RedeemPointsRequest;
use Modules\Loyalty\Services\PointsService;
use Modules\Loyalty\Services\CashbackService;
use Modules\Loyalty\Services\RewardService;
use Modules\Loyalty\Repositories\LoyaltyPointsTransactionRepository;
use Modules\Loyalty\Repositories\LoyaltyRewardRepository;
use Modules\Loyalty\Repositories\LoyaltyTierRepository;

class LoyaltyController extends Controller
{
    public function __construct(
        private readonly PointsService $pointsService,
        private readonly CashbackService $cashbackService,
        private readonly RewardService $rewardService,
        private readonly LoyaltyPointsTransactionRepository $transactionRepository,
        private readonly LoyaltyRewardRepository $rewardRepository,
        private readonly LoyaltyTierRepository $tierRepository,
    ) {}

    public function myPoints(Request $request): JsonResponse
    {
        $points = $this->pointsService->getBalance($request->user()->id);
        return response()->json(['data' => $points]);
    }

    public function awardPoints(AwardPointsRequest $request): JsonResponse
    {
        $dto = new AwardPointsDto(
            userId: $request->input('user_id'),
            points: (int) $request->input('points'),
            referenceType: $request->input('reference_type'),
            referenceId: $request->input('reference_id'),
            description: $request->input('description'),
        );

        $points = $this->pointsService->award($dto);
        return response()->json(['data' => $points], 201);
    }

    public function redeemPoints(RedeemPointsRequest $request): JsonResponse
    {
        try {
            $result = $this->rewardService->claimReward(
                $request->user()->id,
                $request->input('reward_id'),
            );
        } catch (RewardNotFoundException $e) {
            return response()->json(['error' => 'REWARD_NOT_FOUND'], 404);
        } catch (InsufficientPointsException $e) {
            return response()->json(['error' => 'INSUFFICIENT_POINTS', 'reason' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $result]);
    }

    public function pointsHistory(Request $request): JsonResponse
    {
        $txns = $this->transactionRepository->findByUser(
            $request->user()->id,
            (int) $request->input('per_page', 15),
        );
        return response()->json(['data' => $txns]);
    }

    public function calculateCashback(Request $request): JsonResponse
    {
        $request->validate([
            'transaction_amount' => 'required|integer|min:100',
            'merchant_category' => 'sometimes|nullable|string|max:50',
        ]);

        $cashback = $this->cashbackService->calculateCashback(
            $request->user()->id,
            (int) $request->input('transaction_amount'),
            $request->input('merchant_category'),
        );

        return response()->json(['data' => ['cashback_amount' => $cashback]]);
    }

    public function rewards(Request $request): JsonResponse
    {
        $points = $this->pointsService->getBalance($request->user()->id);
        $rewards = $this->rewardRepository->findAvailable($points->tier_level);
        return response()->json(['data' => $rewards]);
    }

    public function tiers(): JsonResponse
    {
        $tiers = $this->tierRepository->all();
        return response()->json(['data' => $tiers]);
    }
}
