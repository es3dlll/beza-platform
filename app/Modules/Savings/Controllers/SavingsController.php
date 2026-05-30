<?php

declare(strict_types=1);

namespace Modules\Savings\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Savings\DTOs\CreateSavingsGoalDto;
use Modules\Savings\DTOs\SavingsContributionDto;
use Modules\Savings\Exceptions\SavingsGoalNotFoundException;
use Modules\Savings\Exceptions\SavingsGoalCompletedException;
use Modules\Savings\Exceptions\InsufficientSavingsBalanceException;
use Modules\Savings\Http\Requests\CreateSavingsGoalRequest;
use Modules\Savings\Http\Requests\ContributeRequest;
use Modules\Savings\Http\Requests\WithdrawRequest;
use Modules\Savings\Services\SavingsService;
use Modules\Savings\Repositories\SavingsGoalRepository;
use Modules\Savings\Repositories\SavingsAccountRepository;
use Modules\Savings\Repositories\SavingsTransactionRepository;

class SavingsController extends Controller
{
    public function __construct(
        private readonly SavingsService $savingsService,
        private readonly SavingsGoalRepository $goalRepository,
        private readonly SavingsAccountRepository $accountRepository,
        private readonly SavingsTransactionRepository $transactionRepository,
    ) {}

    public function createGoal(CreateSavingsGoalRequest $request): JsonResponse
    {
        $dto = new CreateSavingsGoalDto(
            userId: $request->user()->id,
            name: $request->input('name'),
            nameAr: $request->input('name_ar'),
            targetAmount: (int) $request->input('target_amount'),
            targetDate: $request->input('target_date'),
            category: $request->input('category'),
            icon: $request->input('icon'),
            color: $request->input('color'),
            autoSweepEnabled: $request->boolean('auto_sweep_enabled', false),
            autoSweepAmount: $request->integer('auto_sweep_amount'),
            autoSweepFrequency: $request->input('auto_sweep_frequency'),
        );

        $goal = $this->savingsService->createGoal($dto);
        return response()->json(['data' => $goal], 201);
    }

    public function listGoals(Request $request): JsonResponse
    {
        $goals = $this->goalRepository->findByUser($request->user()->id);
        return response()->json(['data' => $goals]);
    }

    public function showGoal(string $id): JsonResponse
    {
        try {
            $goal = $this->savingsService->findGoalOrFail($id);
        } catch (SavingsGoalNotFoundException $e) {
            return response()->json(['error' => 'SAVINGS_GOAL_NOT_FOUND'], 404);
        }

        $account = $this->accountRepository->findByGoal($id);
        return response()->json(['data' => $goal, 'account' => $account]);
    }

    public function contribute(ContributeRequest $request, string $id): JsonResponse
    {
        $dto = new SavingsContributionDto(
            savingsGoalId: $id,
            userId: $request->user()->id,
            amount: (int) $request->input('amount'),
        );

        try {
            $goal = $this->savingsService->contribute($dto);
        } catch (SavingsGoalNotFoundException $e) {
            return response()->json(['error' => 'SAVINGS_GOAL_NOT_FOUND'], 404);
        } catch (SavingsGoalCompletedException $e) {
            return response()->json(['error' => 'SAVINGS_GOAL_COMPLETED'], 422);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => 'INVALID_AMOUNT', 'reason' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $goal]);
    }

    public function withdraw(WithdrawRequest $request, string $id): JsonResponse
    {
        try {
            $goal = $this->savingsService->withdraw(
                goalId: $id,
                userId: $request->user()->id,
                amount: (int) $request->input('amount'),
                description: $request->input('description'),
            );
        } catch (SavingsGoalNotFoundException $e) {
            return response()->json(['error' => 'SAVINGS_GOAL_NOT_FOUND'], 404);
        } catch (SavingsGoalCompletedException $e) {
            return response()->json(['error' => 'SAVINGS_GOAL_COMPLETED'], 422);
        } catch (InsufficientSavingsBalanceException $e) {
            return response()->json(['error' => 'INSUFFICIENT_SAVINGS_BALANCE', 'reason' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $goal]);
    }

    public function transactions(Request $request, string $id): JsonResponse
    {
        $account = $this->accountRepository->findByGoal($id);
        if (!$account) {
            return response()->json(['error' => 'SAVINGS_GOAL_NOT_FOUND'], 404);
        }

        $txns = $this->transactionRepository->findByAccount(
            $account->id,
            (int) $request->input('per_page', 15),
        );

        return response()->json(['data' => $txns]);
    }
}
