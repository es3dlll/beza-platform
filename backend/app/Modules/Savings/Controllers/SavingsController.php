<?php

declare(strict_types=1);

namespace Modules\Savings\Controllers;

use App\Support\ApiResponse;
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

final class SavingsController extends Controller
{
    use ApiResponse;

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
        return $this->respondCreated($goal);
    }

    public function listGoals(Request $request): JsonResponse
    {
        $goals = $this->goalRepository->findByUser($request->user()->id);
        return $this->respond($goals);
    }

    public function showGoal(string $id): JsonResponse
    {
        try {
            $goal = $this->savingsService->findGoalOrFail($id);
        } catch (SavingsGoalNotFoundException $e) {
            return $this->respondError('SAVINGS_GOAL_NOT_FOUND', null, null, 404);
        }

        $account = $this->accountRepository->findByGoal($id);
        return $this->respond(['goal' => $goal, 'account' => $account]);
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
            return $this->respondError('SAVINGS_GOAL_NOT_FOUND', null, null, 404);
        } catch (SavingsGoalCompletedException $e) {
            return $this->respondError('SAVINGS_GOAL_COMPLETED', null, null, 422);
        } catch (\InvalidArgumentException $e) {
            return $this->respondError('INVALID_AMOUNT', $e->getMessage(), null, 422);
        }

        return $this->respond($goal);
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
            return $this->respondError('SAVINGS_GOAL_NOT_FOUND', null, null, 404);
        } catch (SavingsGoalCompletedException $e) {
            return $this->respondError('SAVINGS_GOAL_COMPLETED', null, null, 422);
        } catch (InsufficientSavingsBalanceException $e) {
            return $this->respondError('INSUFFICIENT_SAVINGS_BALANCE', $e->getMessage(), null, 422);
        }

        return $this->respond($goal);
    }

    public function transactions(Request $request, string $id): JsonResponse
    {
        $account = $this->accountRepository->findByGoal($id);
        if (!$account) {
            return $this->respondError('SAVINGS_GOAL_NOT_FOUND', null, null, 404);
        }

        $txns = $this->transactionRepository->findByAccount(
            $account->id,
            (int) $request->input('per_page', 15),
        );

        return $this->respond($txns);
    }

    public function poolSummary(): JsonResponse
    {
        $totalAum = \Modules\Savings\Models\SavingsAccount::sum('balance');
        $activeGoals = \Modules\Savings\Models\SavingsGoal::whereIn('status', ['active', 'pending'])->count();
        $completedGoals = \Modules\Savings\Models\SavingsGoal::where('status', 'completed')->count();
        $totalProfit = \Modules\Savings\Models\SavingsTransaction::where('type', 'profit')
            ->where('status', 'completed')
            ->sum('amount');
        $recentProfitTxns = \Modules\Savings\Models\SavingsTransaction::where('type', 'profit')
            ->where('status', 'completed')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return $this->respond([
            'total_aum' => $totalAum,
            'active_goals' => $activeGoals,
            'completed_goals' => $completedGoals,
            'total_profit_distributed' => $totalProfit,
            'recent_profit_transactions' => $recentProfitTxns,
        ]);
    }

    public function profitRules(Request $request): JsonResponse
    {
        if ($request->isMethod('put')) {
            $rule = \Modules\Savings\Models\SavingsProfitRule::first();
            if (!$rule) {
                return $this->respondError('NO_RULE_FOUND', null, null, 404);
            }
            $rule->update($request->validate([
                'annual_rate' => 'numeric|min:0|max:100',
                'min_balance' => 'integer|min:0',
                'min_duration_days' => 'integer|min:0',
                'early_withdrawal_penalty_rate' => 'numeric|min:0|max:100',
            ]));
            return $this->respond($rule->fresh());
        }

        $rule = \Modules\Savings\Models\SavingsProfitRule::first();
        return $this->respond($rule);
    }
}
