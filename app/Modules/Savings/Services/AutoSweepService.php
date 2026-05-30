<?php

declare(strict_types=1);

namespace Modules\Savings\Services;

use Modules\Savings\Repositories\SavingsGoalRepository;
use Modules\Savings\Repositories\SavingsAccountRepository;
use Modules\Savings\Repositories\SavingsTransactionRepository;
use Modules\Savings\Enums\SavingsTransactionType;

class AutoSweepService
{
    public function __construct(
        private readonly SavingsGoalRepository $goalRepository,
        private readonly SavingsAccountRepository $accountRepository,
        private readonly SavingsTransactionRepository $transactionRepository,
        private readonly SavingsService $savingsService,
    ) {}

    public function processAll(): int
    {
        $goals = $this->goalRepository->findActiveWithAutoSweep();
        $processed = 0;

        foreach ($goals as $goal) {
            try {
                $this->processGoal($goal);
                $processed++;
            } catch (\Exception) {
                continue;
            }
        }

        return $processed;
    }

    private function processGoal($goal): void
    {
        if (!$goal->auto_sweep_enabled || !$goal->auto_sweep_amount) {
            return;
        }

        $account = $this->accountRepository->findByGoal($goal->id);
        if (!$account) {
            return;
        }

        $this->savingsService->contribute(new \Modules\Savings\DTOs\SavingsContributionDto(
            savingsGoalId: $goal->id,
            userId: $goal->user_id,
            amount: $goal->auto_sweep_amount,
            description: 'Auto-sweep contribution',
        ));
    }
}
