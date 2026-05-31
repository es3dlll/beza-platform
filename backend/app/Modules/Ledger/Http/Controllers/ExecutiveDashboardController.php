<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Http\Controllers;

use App\Modules\Core\Services\CacheOrchestrator;
use App\Modules\Ledger\Models\LedgerAccount;
use App\Modules\Ledger\Models\ReconciliationDiscrepancy;
use App\Modules\Ledger\Models\ReconciliationReport;
use App\Modules\Ledger\Services\LedgerMetrics;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final class ExecutiveDashboardController
{
    private const CACHE_NAMESPACE = 'dashboard';

    public function __construct(
        private readonly LedgerMetrics $metrics,
        private readonly CacheOrchestrator $cache,
    ) {}

    public function summary(): JsonResponse
    {
        Gate::authorize('view-executive-dashboard');

        $data = $this->cache->cacheAside(self::CACHE_NAMESPACE, 'summary', 300, function () {
            return [
                'timestamp' => now()->toISOString(),
                'financial_health' => $this->calculateFinancialHealth(),
                'reconciliation_status' => $this->getLatestReconciliationStatus(),
                'cbs_compliance' => $this->getCBSComplianceStatus(),
                'alerts_summary' => $this->getActiveAlertsCount(),
                'system_performance' => $this->getPerformanceMetrics(),
            ];
        });

        return response()->json($data);
    }

    private function calculateFinancialHealth(): array
    {
        $totalAccounts = LedgerAccount::count();
        $balancedAccounts = ReconciliationReport::query()
            ->where('is_balanced', true)
            ->where('created_at', '>=', now()->subDays(7))
            ->count();
        $healthScore = $totalAccounts > 0 ? round(($balancedAccounts / $totalAccounts) * 100) : 100;

        return [
            'score' => $healthScore,
            'status' => $healthScore >= 95 ? 'excellent' : ($healthScore >= 80 ? 'good' : 'attention_required'),
            'total_accounts' => $totalAccounts,
            'balanced_last_7d' => $balancedAccounts,
        ];
    }

    private function getLatestReconciliationStatus(): array
    {
        $latest = ReconciliationReport::query()
            ->latest('created_at')
            ->first();

        return $latest ? [
            'last_run' => $latest->created_at->toISOString(),
            'status' => $latest->status,
            'is_balanced' => $latest->is_balanced,
            'discrepancies' => $latest->total_discrepancies_found,
            'execution_time_ms' => $latest->execution_time_ms,
        ] : ['status' => 'no_data'];
    }

    private function getCBSComplianceStatus(): array
    {
        $cbsReports = ReconciliationReport::query()
            ->cbsReports()
            ->where('created_at', '>=', now()->subDays(30))
            ->get();

        return [
            'total_submitted' => $cbsReports->count(),
            'successfully_synced' => $cbsReports->whereNotNull('cbs_reference')->count(),
            'pending' => $cbsReports->whereNull('cbs_reference')->where('status', 'completed')->count(),
            'last_submission' => $cbsReports->max('completed_at')?->toISOString(),
        ];
    }

    private function getActiveAlertsCount(): array
    {
        return [
            'critical' => ReconciliationDiscrepancy::query()
                ->where('severity', 'critical')
                ->where('resolution_status', 'open')
                ->count(),
            'high' => ReconciliationDiscrepancy::query()
                ->where('severity', 'high')
                ->where('resolution_status', 'open')
                ->count(),
        ];
    }

    private function getPerformanceMetrics(): array
    {
        return [
            'avg_reconciliation_time_ms' => ReconciliationReport::query()
                ->where('created_at', '>=', now()->subDays(7))
                ->avg('execution_time_ms') ?? 0,
            'api_response_time_ms' => 0,
            'queue_depth' => 0,
        ];
    }
}
