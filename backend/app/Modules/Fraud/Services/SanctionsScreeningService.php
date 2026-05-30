<?php

declare(strict_types=1);

namespace Modules\Fraud\Services;

final class SanctionsScreeningService
{
    private const SANCTIONED_COUNTRIES = ['IR', 'KP', 'CU', 'SY', 'MM'];
    private const HIGH_RISK_TERMS = ['terrorist', 'sanctions', 'watchlist'];

    public function check(?string $fullName, ?string $iban, ?float $amount): int
    {
        $riskScore = 0;

        if ($fullName) {
            foreach (self::HIGH_RISK_TERMS as $term) {
                if (stripos($fullName, $term) !== false) {
                    $riskScore += 300;
                }
            }
        }

        if ($amount !== null && $amount > 10000000) {
            $riskScore += 150;
        }

        return $riskScore;
    }
}
