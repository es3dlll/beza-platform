<?php

declare(strict_types=1);

namespace Modules\Loyalty\Services;

use Modules\Loyalty\Events\CashbackApplied;
use Modules\Loyalty\Repositories\CashbackRuleRepository;

final class CashbackService
{
    public function __construct(
        private readonly CashbackRuleRepository $ruleRepository,
        private readonly TierService $tierService,
    ) {}

    public function calculateCashback(string $userId, int $transactionAmount, ?string $merchantCategory = null): int
    {
        $totalCashback = 0;
        $rules = $this->ruleRepository->findAllActive();

        foreach ($rules as $rule) {
            if ($transactionAmount < $rule->min_amount) continue;

            $eligible = false;

            switch ($rule->trigger_type) {
                case 'transaction_amount':
                    $eligible = true;
                    break;
                case 'merchant_category':
                    $eligible = ($merchantCategory === $rule->trigger_value);
                    break;
                case 'tier_bonus':
                    $points = \Modules\Loyalty\Models\LoyaltyPoints::where('user_id', $userId)->first();
                    $tierLevel = $points?->tier_level ?? 'bronze';
                    $eligible = ($tierLevel === $rule->tier_requirement || $rule->tier_requirement === null);
                    break;
                default:
                    $eligible = false;
            }

            if (!$eligible) continue;

            $cashback = (int) round($transactionAmount * ($rule->rate / 100));

            if ($rule->max_cashback > 0) {
                $cashback = min($cashback, $rule->max_cashback);
            }

            $totalCashback += $cashback;
        }

        // Add tier-based cashback
        $points = \Modules\Loyalty\Models\LoyaltyPoints::where('user_id', $userId)->first();
        $tierLevel = $points?->tier_level ?? 'bronze';
        $tierRate = $this->tierService->getCashbackRate($tierLevel);
        if ($tierRate > 0) {
            $tierCashback = (int) round($transactionAmount * ($tierRate / 100));
            $totalCashback += $tierCashback;
        }

        return $totalCashback;
    }

    public function applyCashback(string $userId, int $transactionAmount, ?string $merchantCategory = null): int
    {
        $cashback = $this->calculateCashback($userId, $transactionAmount, $merchantCategory);

        if ($cashback > 0) {
            // In production: dispatch to WalletService::deposit() or CreditService
            CashbackApplied::dispatch($userId, $cashback, $transactionAmount, 'auto');
        }

        return $cashback;
    }
}
