<?php

declare(strict_types=1);

namespace Modules\Financing\Services;

use Modules\Financing\Models\FinancingCreditScore;
use Modules\Identity\Models\User;

final class CreditScoringService
{
    private const MIN_SCORE_THRESHOLD = 300;
    private const MAX_SCORE = 850;

    public function calculate(User $user): FinancingCreditScore
    {
        $txVolume = (int) \Modules\Wallet\Models\WalletTransaction::where('user_id', $user->id)
            ->where('status', 'completed')
            ->sum('amount');

        $accountAge = (int) ($user->created_at?->diffInDays(now()) ?? 0);
        $kycTier = $user->kyc_tier ?? 'none';
        $txCount = \Modules\Wallet\Models\WalletTransaction::where('user_id', $user->id)
            ->where('status', 'completed')
            ->count();

        $fraudFlag = \Modules\Fraud\Models\FraudCase::where('user_id', $user->id)
            ->where('severity', 'high')
            ->exists();

        $score = $this->computeScore($txVolume, $accountAge, $kycTier, $txCount, $fraudFlag);

        return FinancingCreditScore::updateOrCreate(
            ['user_id' => $user->id],
            [
                'score' => $score,
                'transaction_volume' => $txVolume,
                'account_age_days' => $accountAge,
                'kyc_tier' => $kycTier,
                'factors' => [
                    'transaction_volume' => $txVolume,
                    'account_age_days' => $accountAge,
                    'kyc_tier' => $kycTier,
                    'transaction_count' => $txCount,
                    'has_fraud_flag' => $fraudFlag,
                ],
                'calculated_at' => now(),
            ]
        );
    }

    public function getScore(string $userId): ?FinancingCreditScore
    {
        return FinancingCreditScore::where('user_id', $userId)->first();
    }

    public function meetsThreshold(int $score): bool
    {
        return $score >= self::MIN_SCORE_THRESHOLD;
    }

    private function computeScore(int $volume, int $ageDays, string $kycTier, int $txCount, bool $fraudFlag): int
    {
        $score = 300;

        // Transaction volume component (max +200)
        if ($volume > 10_000_000) $score += 200;
        elseif ($volume > 5_000_000) $score += 150;
        elseif ($volume > 1_000_000) $score += 100;
        elseif ($volume > 100_000) $score += 50;

        // Account age component (max +150)
        $score += min(150, (int) ($ageDays * 0.5));

        // KYC tier component (max +100)
        $score += match ($kycTier) {
            'tier_3' => 100,
            'tier_2' => 60,
            'tier_1' => 30,
            default => 0,
        };

        // Transaction count (max +100)
        if ($txCount > 500) $score += 100;
        elseif ($txCount > 200) $score += 70;
        elseif ($txCount > 50) $score += 40;
        elseif ($txCount > 10) $score += 20;

        // Fraud penalty (-200)
        if ($fraudFlag) $score -= 200;

        return max(0, min(self::MAX_SCORE, $score));
    }
}
