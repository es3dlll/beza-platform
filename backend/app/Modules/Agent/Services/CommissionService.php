<?php

declare(strict_types=1);

namespace App\Modules\Agent\Services;

use App\Domain\Enums\Currency;
use App\Domain\ValueObjects\Money;
use App\Modules\Agent\Events\CommissionCalculated;
use App\Modules\Agent\Models\CommissionRule;

final class CommissionService
{
    public function calculateCommission(string $txnType, Money $amount, string $kycTier = 't0'): Money
    {
        $rule = CommissionRule::where('txn_type', $txnType)
            ->where('kyc_tier_min', '<=', $kycTier)
            ->where('is_active', true)
            ->orderBy('kyc_tier_min', 'desc')
            ->orderBy('created_at', 'desc')
            ->first();

        if ($rule === null) {
            return Money::zero($amount->currency());
        }

        $commission = $rule->calculate($amount);

        event(new CommissionCalculated(
            agentTransactionId: '',
            agentId: '',
            amount: $commission->amount(),
            rateBps: (int) $rule->value,
            currency: $amount->currency()->value,
        ));

        return $commission;
    }
}
