<?php

declare(strict_types=1);

namespace Modules\Wallet\Services;

use Modules\Wallet\Models\Wallet;
use Modules\Wallet\Models\WalletLimit;
use Modules\Wallet\Models\WalletTransaction;

final class WalletLimitsEngine
{
    public function check(Wallet $wallet, int $amount, string $type = 'withdrawal'): array
    {
        $violations = [];
        $limits = WalletLimit::where('kyc_tier', $wallet->kyc_tier_required)
            ->where('limit_type', $type)
            ->where('is_active', true)
            ->get();

        foreach ($limits as $limit) {
            $current = $this->usageForPeriod($wallet, $limit->period, $type);

            if ($limit->max_amount && ($current['amount'] + $amount) > $limit->max_amount) {
                $violations[] = [
                    'limit_id' => $limit->id,
                    'name' => $limit->name,
                    'period' => $limit->period,
                    'max_amount' => $limit->max_amount,
                    'current_amount' => $current['amount'],
                    'requested' => $amount,
                ];
            }

            if ($limit->max_count && ($current['count'] + 1) > $limit->max_count) {
                $violations[] = [
                    'limit_id' => $limit->id,
                    'name' => $limit->name,
                    'period' => $limit->period,
                    'max_count' => $limit->max_count,
                    'current_count' => $current['count'],
                ];
            }
        }

        return $violations;
    }

    public function usageForPeriod(Wallet $wallet, string $period, string $type): array
    {
        $query = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('type', $type);

        $query = match ($period) {
            'daily' => $query->whereDate('created_at', today()),
            'weekly' => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]),
            'monthly' => $query->whereMonth('created_at', now()->month)
                               ->whereYear('created_at', now()->year),
            default => $query,
        };

        return [
            'amount' => (int) $query->sum('amount'),
            'count' => $query->count(),
        ];
    }
}
