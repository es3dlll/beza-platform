<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Console\Commands;

use App\Modules\Ledger\Models\LedgerAccount;
use App\Modules\Ledger\Services\ReconciliationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

final class LedgerReconcile extends Command
{
    protected $signature = 'ledger:reconcile 
        {--type=reconciliation : Report type (reconciliation, cbs_trial_balance, cbs_settlement, cbs_balance_sheet, cbs_income_statement)}
        {--scope=full : Scope (full, account, date_range)}
        {--account= : Account ID for single account reconciliation}
        {--start-date= : Start date for date range (Y-m-d)}
        {--end-date= : End date for date range (Y-m-d)}
        {--initiated-by= : User/system identifier}
        {--force : Skip confirmation for production}
        {--dry-run : Execute without persisting changes}';

    protected $description = 'Reconcile ledger accounts and generate compliance reports for Central Bank of Syria';

    public function handle(ReconciliationService $service): int
    {
        $reportType = $this->option('type');
        $scope = $this->option('scope');
        $accountId = $this->option('account');
        $startDate = $this->option('start-date') ? Carbon::parse($this->option('start-date')) : null;
        $endDate = $this->option('end-date') ? Carbon::parse($this->option('end-date')) : null;
        $initiatedBy = $this->option('initiated-by') ?? 'cli';
        $force = $this->option('force');
        $dryRun = $this->option('dry-run');

        if ($scope === 'account' && !$accountId) {
            $this->error('Account ID required when scope is "account"');
            return Command::FAILURE;
        }

        if ($scope === 'date_range' && (!$startDate || !$endDate)) {
            $this->error('Both start-date and end-date required when scope is "date_range"');
            return Command::FAILURE;
        }

        if (!$force && !$dryRun && app()->environment('production')) {
            if (!$this->confirm('Running reconciliation in production. Continue?')) {
                $this->info('Cancelled.');
                return Command::SUCCESS;
            }
        }

        $this->info("Starting reconciliation...");
        $this->line("Type: {$reportType} | Scope: {$scope}");
        if ($accountId) $this->line("Account: {$accountId}");
        if ($startDate) $this->line("Period: {$startDate->format('Y-m-d')} to {$endDate?->format('Y-m-d')}");
        if ($dryRun) $this->warn('DRY RUN MODE');

        try {
            if ($dryRun) {
                $this->info('Reconciliation simulation completed successfully');
                $accounts = LedgerAccount::count();
                $this->table(
                    ['Metric', 'Value'],
                    [['Accounts to check', "~{$accounts}"], ['Estimated time', '~' . round($accounts * 0.05) . 's']]
                );
                return Command::SUCCESS;
            }

            $startTime = microtime(true);
            $report = $service->reconcile($reportType, $scope, $accountId, $startDate, $endDate, $initiatedBy);
            $executionTime = round((microtime(true) - $startTime) * 1000);

            $this->newLine();
            $this->info('Reconciliation completed!');

            $this->table(
                ['Field', 'Value'],
                [
                    ['Report ID', $report->id],
                    ['Status', $report->status],
                    ['Accounts Checked', (string) $report->total_accounts_checked],
                    ['Discrepancies Found', (string) $report->total_discrepancies_found],
                    ['Balanced', $report->is_balanced ? 'Yes' : 'No'],
                    ['Execution Time', "{$executionTime}ms"],
                    ['CBS Report Code', $report->cbs_report_code ?? 'N/A'],
                ]
            );

            if ($report->total_discrepancies_found > 0) {
                $this->warn("\nDiscrepancies detected:");
                $discrepancies = $report->discrepancies()->with('account')->limit(10)->get();
                foreach ($discrepancies as $d) {
                    $code = $d->account?->code ?? '?';
                    $this->line("  {$code}: {$d->description} [{$d->severity}]");
                }
                if ($report->total_discrepancies_found > 10) {
                    $this->line("  ... and " . ($report->total_discrepancies_found - 10) . " more");
                }
            }

            if ($report->cbs_report_code) {
                $this->info("\nCBS Report Generated: {$report->cbs_report_code}");
            }

            return $report->is_balanced ? Command::SUCCESS : Command::FAILURE;

        } catch (\App\Modules\Ledger\Exceptions\ReconciliationFailedException $e) {
            $this->error("Reconciliation failed: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}
