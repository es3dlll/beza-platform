<?php

declare(strict_types=1);

namespace Modules\Savings\Services;

use Modules\Savings\Models\SavingsProfitRule;
use Modules\Savings\Models\SavingsAccount;
use Modules\Savings\Repositories\SavingsProfitRuleRepository;
use Modules\Savings\Repositories\SavingsTransactionRepository;

final class ProfitDistributionService
{
    public function __construct(
        private readonly SavingsProfitRuleRepository $ruleRepository,
        private readonly SavingsTransactionRepository $transactionRepository,
    ) {}

    public function calculateDailyProfit(SavingsAccount $account): int
    {
        $rule = $this->ruleRepository->findActive();
        if (!$rule || $account->balance <= 0) {
            return 0;
        }

        if ($account->balance < $rule->min_balance) {
            return 0;
        }

        $dailyRate = $rule->annual_rate / 100 / 365;
        return (int) floor($account->balance * $dailyRate);
    }

    public function calculateEarlyWithdrawalPenalty(SavingsAccount $account, int $withdrawAmount, SavingsProfitRule $rule): int
    {
        $ageInDays = $account->created_at?->diffInDays(now()) ?? 0;

        if ($ageInDays >= $rule->min_duration_days) {
            return 0;
        }

        return (int) floor($withdrawAmount * ($rule->early_withdrawal_penalty_rate / 100));
    }

    public function calculateProfitForPeriod(SavingsAccount $account, string $startDate, string $endDate): int
    {
        $days = now()->parse($startDate)->diffInDays(now()->parse($endDate));
        if ($days <= 0) return 0;

        $rule = $this->ruleRepository->findActive();
        if (!$rule || $account->balance <= 0 || $account->balance < $rule->min_balance) {
            return 0;
        }

        $dailyRate = $rule->annual_rate / 100 / 365;
        return (int) floor($account->balance * $dailyRate * $days);
    }
}
