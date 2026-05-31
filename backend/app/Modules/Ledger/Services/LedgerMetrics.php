<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Services;

use App\Modules\Ledger\Models\LedgerAccount;
use App\Modules\Ledger\Models\ReconciliationDiscrepancy;
use App\Modules\Ledger\Models\ReconciliationReport;

final class LedgerMetrics
{
    public function getAccountBalanceTrend(string $accountId, int $days = 30): array
    {
        return [];
    }

    public function getReconciliationSuccessRate(int $days = 7): float
    {
        $total = ReconciliationReport::where('created_at', '>=', now()->subDays($days))->count();
        if ($total === 0) {
            return 100.0;
        }
        $balanced = ReconciliationReport::where('created_at', '>=', now()->subDays($days))
            ->where('is_balanced', true)
            ->count();
        return round(($balanced / $total) * 100, 2);
    }

    public function getAverageExecutionTime(int $days = 7): float
    {
        return (float) ReconciliationReport::where('created_at', '>=', now()->subDays($days))
            ->avg('execution_time_ms') ?? 0;
    }

    public function getDiscrepancySummary(int $days = 7): array
    {
        return [
            'critical' => ReconciliationDiscrepancy::where('severity', 'critical')
                ->where('created_at', '>=', now()->subDays($days))->count(),
            'high' => ReconciliationDiscrepancy::where('severity', 'high')
                ->where('created_at', '>=', now()->subDays($days))->count(),
            'medium' => ReconciliationDiscrepancy::where('severity', 'medium')
                ->where('created_at', '>=', now()->subDays($days))->count(),
            'low' => ReconciliationDiscrepancy::where('severity', 'low')
                ->where('created_at', '>=', now()->subDays($days))->count(),
        ];
    }

    public function getCBSSubmissionRate(int $days = 30): array
    {
        $total = ReconciliationReport::cbsReports()
            ->where('created_at', '>=', now()->subDays($days))
            ->count();
        $synced = ReconciliationReport::cbsReports()
            ->where('created_at', '>=', now()->subDays($days))
            ->whereNotNull('cbs_reference')
            ->count();
        return [
            'total' => $total,
            'synced' => $synced,
            'rate' => $total > 0 ? round(($synced / $total) * 100, 2) : 100.0,
        ];
    }
}
