<?php

declare(strict_types=1);

namespace App\Modules\Agent\Services;

use App\Modules\Agent\Models\Agent;
use App\Modules\Core\ValueObjects\Money;
use App\Modules\Ledger\Models\LedgerEntry;
use Illuminate\Support\Facades\DB;

final class LiquidityPoolService
{
    private const REGION_PRIORITY = [
        'damascus' => 1,
        'aleppo' => 2,
        'homs' => 3,
        'latakia' => 4,
        'default' => 5,
    ];

    public function requestLiquidity(Agent $agent, Money $amount): array
    {
        if ($agent->status !== 'active') {
            throw new \RuntimeException('الوكيل غير نشط');
        }

        if ($amount->fils() > $agent->daily_liquidity_limit_fils) {
            throw new \RuntimeException('يتجاوز حد السيولة اليومي');
        }

        $todayUsage = $this->getTodayUsage($agent);
        $remainingDaily = $agent->daily_liquidity_limit_fils - $todayUsage;

        if ($amount->fils() > $remainingDaily) {
            throw new \RuntimeException('الحد اليومي المتبقي غير كافٍ');
        }

        $priority = self::REGION_PRIORITY[$agent->region] ?? self::REGION_PRIORITY['default'];
        $processingTime = $priority * 10;

        return [
            'approved' => true,
            'amount_fils' => $amount->fils(),
            'currency' => $amount->currency()->value,
            'priority' => $priority,
            'estimated_processing_minutes' => $processingTime,
            'agent_balance_after_fils' => $agent->available_balance_fils + $amount->fils(),
        ];
    }

    public function calculateTotalLiquidityNeeded(): int
    {
        return (int) Agent::where('status', 'active')->sum('daily_liquidity_limit_fils');
    }

    public function getAvailablePool(): int
    {
        return (int) Agent::where('status', 'active')->sum('available_balance_fils');
    }

    public function getRegionalDistribution(): array
    {
        return Agent::where('status', 'active')
            ->select('region', DB::raw('SUM(daily_liquidity_limit_fils) as total_limit'))
            ->groupBy('region')
            ->get()
            ->toArray();
    }

    private function getTodayUsage(Agent $agent): int
    {
        return (int) LedgerEntry::where('created_at', '>=', now()->startOfDay())
            ->where(function ($q) use ($agent) {
                $q->where('debit_wallet_id', $agent->id)
                  ->orWhere('credit_wallet_id', $agent->id);
            })
            ->sum('amount_fils');
    }
}
