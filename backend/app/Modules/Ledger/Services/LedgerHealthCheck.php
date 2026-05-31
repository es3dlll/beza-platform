<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Services;

use App\Modules\Ledger\Models\LedgerAccount;
use App\Modules\Ledger\Models\ReconciliationDiscrepancy;
use App\Modules\Ledger\Models\ReconciliationReport;
use Illuminate\Support\Carbon;

final class LedgerHealthCheck
{
    public function __construct(
        private readonly HashChainService $hashChainService,
    ) {}

    public function check(): array
    {
        $chainStatus = $this->hashChainService->verifyIntegrity();
        $lastReconciliation = ReconciliationReport::where('report_type', 'reconciliation')
            ->latest()
            ->first();
        $lastCbsReport = ReconciliationReport::cbsReports()
            ->latest()
            ->first();
        $openDiscrepancies = ReconciliationDiscrepancy::whereIn('resolution_status', ['open', 'investigating'])
            ->count();
        $criticalDiscrepancies = ReconciliationDiscrepancy::where('severity', ReconciliationDiscrepancy::SEVERITY_CRITICAL)
            ->where('resolution_status', '!=', 'resolved')
            ->count();
        $pendingCbsReports = ReconciliationReport::where('status', ReconciliationReport::STATUS_COMPLETED)
            ->whereNull('cbs_report_code')
            ->count();
        $totalAccounts = LedgerAccount::count();
        $imbalancedAccounts = ReconciliationDiscrepancy::where('resolution_status', 'open')
            ->distinct('account_id')
            ->count('account_id');

        $checks = [
            'chain_integrity' => [
                'status' => ($chainStatus['passed'] ?? false) ? 'passed' : 'failed',
                'details' => $chainStatus,
            ],
            'last_reconciliation' => [
                'status' => $lastReconciliation ? ($lastReconciliation->is_balanced ? 'passed' : 'warning') : 'passed',
                'last_run' => $lastReconciliation?->completed_at?->toISOString(),
                'discrepancies_found' => $lastReconciliation?->total_discrepancies_found ?? 0,
                'is_balanced' => $lastReconciliation?->is_balanced ?? false,
            ],
            'open_discrepancies' => [
                'status' => $criticalDiscrepancies > 0 ? 'critical' : ($openDiscrepancies > 0 ? 'warning' : 'passed'),
                'count' => $openDiscrepancies,
                'critical_count' => $criticalDiscrepancies,
            ],
            'cbs_reporting' => [
                'status' => $pendingCbsReports > 0 ? 'warning' : 'passed',
                'last_report' => $lastCbsReport?->cbs_report_code ?? 'none',
                'pending_submissions' => $pendingCbsReports,
            ],
            'imbalanced_accounts' => [
                'status' => $imbalancedAccounts > 0 ? 'warning' : 'passed',
                'count' => $imbalancedAccounts,
                'total_accounts' => $totalAccounts,
            ],
        ];

        $overallStatus = $this->calculateOverallStatus($checks);

        return [
            'status' => $overallStatus,
            'timestamp' => now()->toISOString(),
            'service' => 'ledger',
            'checks' => $checks,
            'summary' => [
                'total_accounts' => $totalAccounts,
                'open_discrepancies' => $openDiscrepancies,
                'critical_discrepancies' => $criticalDiscrepancies,
                'pending_cbs_submissions' => $pendingCbsReports,
                'chain_valid' => $chainStatus['passed'] ?? false,
            ],
        ];
    }

    public function run(): array
    {
        return $this->check();
    }

    public function isHealthy(): bool
    {
        $result = $this->check();
        return $result['status'] === 'healthy';
    }

    public function getLastReconciliation(): ?ReconciliationReport
    {
        return ReconciliationReport::where('report_type', 'reconciliation')
            ->latest('completed_at')
            ->first();
    }

    public function getLastCbsReport(): ?ReconciliationReport
    {
        return ReconciliationReport::cbsReports()
            ->latest('completed_at')
            ->first();
    }

    public function getStaleReconciliationHours(): ?int
    {
        $last = $this->getLastReconciliation();
        if (!$last?->completed_at) return null;

        return (int) now()->diffInHours($last->completed_at);
    }

    private function calculateOverallStatus(array $checks): string
    {
        $priorities = ['critical' => 0, 'failed' => 1, 'warning' => 2, 'passed' => 3];

        $lowest = 'passed';
        foreach ($checks as $check) {
            $status = $check['status'] ?? 'passed';
            if (($priorities[$status] ?? 3) < ($priorities[$lowest] ?? 3)) {
                $lowest = $status;
            }
        }

        return match ($lowest) {
            'critical', 'failed' => 'degraded',
            'warning' => 'warning',
            default => 'healthy',
        };
    }
}
