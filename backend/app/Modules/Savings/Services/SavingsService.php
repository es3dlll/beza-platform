<?php

declare(strict_types=1);

namespace Modules\Savings\Services;

use Illuminate\Support\Str;
use Modules\Savings\DTOs\CreateSavingsGoalDto;
use Modules\Savings\DTOs\SavingsContributionDto;
use Modules\Savings\Enums\SavingsGoalStatus;
use Modules\Savings\Enums\SavingsTransactionType;
use Modules\Savings\Events\SavingsGoalCreated;
use Modules\Savings\Events\SavingsGoalCompleted;
use Modules\Savings\Events\SavingsContributionMade;
use Modules\Savings\Events\SavingsWithdrawn;
use Modules\Savings\Events\SavingsProfitDistributed;
use Modules\Savings\Exceptions\SavingsGoalNotFoundException;
use Modules\Savings\Exceptions\SavingsGoalCompletedException;
use Modules\Savings\Exceptions\InsufficientSavingsBalanceException;
use Modules\Savings\Models\SavingsGoal;
use Modules\Savings\Models\SavingsAccount;
use Modules\Savings\Repositories\SavingsGoalRepository;
use Modules\Savings\Repositories\SavingsAccountRepository;
use Modules\Savings\Repositories\SavingsTransactionRepository;
use Modules\Savings\Repositories\SavingsProfitRuleRepository;

final class SavingsService
{
    private const MIN_CONTRIBUTION = 1000;

    public function __construct(
        private readonly SavingsGoalRepository $goalRepository,
        private readonly SavingsAccountRepository $accountRepository,
        private readonly SavingsTransactionRepository $transactionRepository,
        private readonly SavingsProfitRuleRepository $profitRuleRepository,
        private readonly ProfitDistributionService $profitService,
    ) {}

    public function createGoal(CreateSavingsGoalDto $dto): SavingsGoal
    {
        $goal = $this->goalRepository->create([
            'id' => (string) Str::ulid(),
            'user_id' => $dto->userId,
            'name' => $dto->name,
            'name_ar' => $dto->nameAr,
            'target_amount' => $dto->targetAmount,
            'current_amount' => 0,
            'target_date' => $dto->targetDate,
            'category' => $dto->category,
            'icon' => $dto->icon,
            'color' => $dto->color,
            'auto_sweep_enabled' => $dto->autoSweepEnabled,
            'auto_sweep_amount' => $dto->autoSweepAmount,
            'auto_sweep_frequency' => $dto->autoSweepFrequency,
            'status' => SavingsGoalStatus::ACTIVE->value,
        ]);

        $this->accountRepository->findOrCreateForGoal($dto->userId, $goal->id);

        SavingsGoalCreated::dispatch($goal->id, $dto->userId, $dto->name, $dto->targetAmount);
        return $goal;
    }

    public function contribute(SavingsContributionDto $dto): SavingsGoal
    {
        $goal = $this->findGoalOrFail($dto->savingsGoalId);
        $this->ensureActive($goal);

        if ($dto->amount < self::MIN_CONTRIBUTION) {
            throw new \InvalidArgumentException('Minimum contribution is ' . self::MIN_CONTRIBUTION . ' SYP');
        }

        $newAmount = $goal->current_amount + $dto->amount;
        $data = ['current_amount' => $newAmount];

        $isCompleted = $newAmount >= $goal->target_amount;
        if ($isCompleted) {
            $data['status'] = SavingsGoalStatus::COMPLETED->value;
            $data['completed_at'] = now();
        }

        $this->goalRepository->update($goal->id, $data);

        $account = $this->accountRepository->findByGoal($goal->id);
        if ($account) {
            $balanceBefore = $account->balance;
            $this->accountRepository->update($account->id, [
                'balance' => $balanceBefore + $dto->amount,
                'total_contributions' => $account->total_contributions + $dto->amount,
                'last_contribution_at' => now(),
            ]);

            $this->transactionRepository->create([
                'id' => (string) Str::ulid(),
                'savings_account_id' => $account->id,
                'savings_goal_id' => $goal->id,
                'user_id' => $dto->userId,
                'type' => SavingsTransactionType::CONTRIBUTION->value,
                'amount' => $dto->amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceBefore + $dto->amount,
                'description' => $dto->description ?? 'Contribution to ' . $goal->name,
            ]);
        }

        SavingsContributionMade::dispatch($goal->id, $dto->userId, $dto->amount, (int) $goal->progressPercent());

        if ($isCompleted) {
            SavingsGoalCompleted::dispatch($goal->id, $dto->userId, $goal->name, $newAmount);
        }

        return $this->goalRepository->findById($goal->id);
    }

    public function withdraw(string $goalId, string $userId, int $amount, ?string $description = null): SavingsGoal
    {
        $goal = $this->findGoalOrFail($goalId);
        $this->ensureActive($goal);

        $account = $this->accountRepository->findByGoal($goalId);
        if (!$account || $account->balance < $amount) {
            throw new InsufficientSavingsBalanceException($amount, $account?->balance ?? 0);
        }

        $penalty = 0;
        $rule = $this->profitRuleRepository->findActive();
        if ($rule) {
            $penalty = $this->profitService->calculateEarlyWithdrawalPenalty($account, $amount, $rule);
        }

        $totalDeduction = $amount + $penalty;
        $balanceBefore = $account->balance;
        $newBalance = $balanceBefore - $totalDeduction;

        $this->accountRepository->update($account->id, [
            'balance' => max(0, $newBalance),
            'total_withdrawn' => $account->total_withdrawn + $amount,
        ]);

        $this->goalRepository->update($goalId, [
            'current_amount' => max(0, $goal->current_amount - $totalDeduction),
        ]);

        $this->transactionRepository->create([
            'id' => (string) Str::ulid(),
            'savings_account_id' => $account->id,
            'savings_goal_id' => $goalId,
            'user_id' => $userId,
            'type' => SavingsTransactionType::WITHDRAWAL->value,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => (int) max(0, $newBalance),
            'description' => $description ?? 'Withdrawal from ' . $goal->name,
        ]);

        if ($penalty > 0) {
            $this->transactionRepository->create([
                'id' => (string) Str::ulid(),
                'savings_account_id' => $account->id,
                'savings_goal_id' => $goalId,
                'user_id' => $userId,
                'type' => SavingsTransactionType::PENALTY->value,
                'amount' => $penalty,
                'balance_before' => $balanceBefore - $amount,
                'balance_after' => (int) max(0, $newBalance),
                'description' => 'Early withdrawal penalty',
            ]);
        }

        SavingsWithdrawn::dispatch($goalId, $userId, $amount, $penalty);
        return $this->goalRepository->findById($goalId);
    }

    public function distributeDailyProfit(): int
    {
        $profitDistributed = 0;
        $accounts = \Modules\Savings\Models\SavingsAccount::where('balance', '>', 0)->get();

        foreach ($accounts as $account) {
            $profit = $this->profitService->calculateDailyProfit($account);
            if ($profit <= 0) continue;

            $balanceBefore = $account->balance;
            $this->accountRepository->update($account->id, [
                'balance' => $balanceBefore + $profit,
                'total_profit' => $account->total_profit + $profit,
            ]);

            $this->transactionRepository->create([
                'id' => (string) Str::ulid(),
                'savings_account_id' => $account->id,
                'savings_goal_id' => $account->savings_goal_id,
                'user_id' => $account->user_id,
                'type' => SavingsTransactionType::PROFIT->value,
                'amount' => $profit,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceBefore + $profit,
                'description' => 'Daily profit distribution',
            ]);

            SavingsProfitDistributed::dispatch($account->id, $account->user_id, $profit, 'daily');
            $profitDistributed += $profit;

            // Update goal current_amount if linked
            if ($account->savings_goal_id) {
                $goal = $this->goalRepository->findById($account->savings_goal_id);
                if ($goal) {
                    $this->goalRepository->update($goal->id, [
                        'current_amount' => $goal->current_amount + $profit,
                    ]);
                }
            }
        }

        return $profitDistributed;
    }

    public function findGoalOrFail(string $id): SavingsGoal
    {
        $goal = $this->goalRepository->findById($id);
        if (!$goal) {
            throw new SavingsGoalNotFoundException($id);
        }
        return $goal;
    }

    public function ensureActive(SavingsGoal $goal): void
    {
        if ($goal->status !== SavingsGoalStatus::ACTIVE->value) {
            throw new SavingsGoalCompletedException($goal->id);
        }
    }
}
