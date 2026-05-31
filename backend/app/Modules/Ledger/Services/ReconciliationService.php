<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Services;

use App\Modules\Ledger\Events\ReconciliationCompleted;
use App\Modules\Ledger\Exceptions\ReconciliationFailedException;
use App\Modules\Ledger\Models\JournalLine;
use App\Modules\Ledger\Models\LedgerAccount;
use App\Modules\Ledger\Models\ReconciliationDiscrepancy;
use App\Modules\Ledger\Models\ReconciliationReport;
use App\Modules\Ledger\Services\AlertingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

final class ReconciliationService
{
    public function __construct(
        private readonly CBSReportGenerator $cbsReportGenerator,
    ) {}

    public function reconcile(
        string $reportType = ReconciliationReport::TYPE_RECONCILIATION,
        string $scope = 'full',
        ?string $accountId = null,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        ?string $initiatedBy = null,
    ): ReconciliationReport {
        $startTime = microtime(true);

        $report = ReconciliationReport::create([
            'id' => Str::ulid()->toBase32(),
            'report_type' => $reportType,
            'scope' => $scope,
            'account_id' => $accountId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => ReconciliationReport::STATUS_RUNNING,
            'currency' => 'SYP',
            'reporting_date' => $startDate?->startOfDay() ?? now()->startOfDay(),
            'initiated_by' => $initiatedBy,
            'started_at' => now(),
        ]);

        try {
            match ($scope) {
                'account' => $this->reconcileSingleAccount($report, $accountId),
                'date_range' => $this->reconcileByDateRange($report, $startDate, $endDate),
                default => $this->reconcileAllAccounts($report, $startDate, $endDate),
            };

            $executionTime = (int) ((microtime(true) - $startTime) * 1000);
            $report->update([
                'status' => ReconciliationReport::STATUS_COMPLETED,
                'completed_at' => now(),
                'execution_time_ms' => $executionTime,
                'is_balanced' => $report->total_discrepancies_found === 0,
                'summary' => [
                    'report_date' => now()->toDateString(),
                    'total_accounts' => $report->total_accounts_checked,
                    'matched_accounts' => $report->total_accounts_checked - $report->total_discrepancies_found,
                    'discrepancy_count' => $report->total_discrepancies_found,
                    'is_balanced' => $report->total_discrepancies_found === 0,
                ],
            ]);

            if (in_array($reportType, CBSReportGenerator::CBS_REPORT_TYPES)) {
                $this->cbsReportGenerator->generateFromReport($report);
            }

            event(new ReconciliationCompleted($report));
            return $report;

        } catch (Throwable $e) {
            Log::error('Reconciliation failed', ['report_id' => $report->id, 'error' => $e->getMessage()]);
            $report->update(['status' => ReconciliationReport::STATUS_FAILED]);
            throw new ReconciliationFailedException("Reconciliation failed: {$e->getMessage()}", previous: $e);
        }
    }

    private function reconcileAllAccounts(ReconciliationReport $report, ?Carbon $startDate, ?Carbon $endDate): void
    {
        $query = LedgerAccount::query();

        $accountsChecked = 0;
        $totalDiscrepancies = 0;

        $query->chunkById(100, function (Collection $accounts) use (&$accountsChecked, &$totalDiscrepancies, $report, $startDate, $endDate) {
            foreach ($accounts as $account) {
                $result = $this->checkAccountBalance($account, $startDate, $endDate);
                if ($result['has_discrepancy']) {
                    $this->recordDiscrepancy($report, $account, $result);
                    $totalDiscrepancies++;
                }
                $accountsChecked++;
            }
        });

        $report->update([
            'total_accounts_checked' => $accountsChecked,
            'total_discrepancies_found' => $totalDiscrepancies,
        ]);
    }

    private function reconcileSingleAccount(ReconciliationReport $report, ?string $accountId): void
    {
        if (!$accountId) {
            throw new ReconciliationFailedException('Account ID required for single account reconciliation');
        }

        $account = LedgerAccount::findOrFail($accountId);
        $result = $this->checkAccountBalance($account);

        if ($result['has_discrepancy']) {
            $this->recordDiscrepancy($report, $account, $result);
            $report->update(['total_accounts_checked' => 1, 'total_discrepancies_found' => 1]);
        } else {
            $report->update(['total_accounts_checked' => 1, 'total_discrepancies_found' => 0]);
        }
    }

    private function reconcileByDateRange(ReconciliationReport $report, ?Carbon $startDate, ?Carbon $endDate): void
    {
        if (!$startDate || !$endDate) {
            throw new ReconciliationFailedException('Start and end dates required for date range reconciliation');
        }

        $accountIds = JournalLine::whereBetween('created_at', [$startDate, $endDate])
            ->distinct()
            ->pluck('account_id');

        $accountsChecked = 0;
        $totalDiscrepancies = 0;

        foreach ($accountIds as $accountId) {
            $account = LedgerAccount::find($accountId);
            if (!$account) continue;

            $result = $this->checkAccountBalance($account, $startDate, $endDate);
            if ($result['has_discrepancy']) {
                $this->recordDiscrepancy($report, $account, $result);
                $totalDiscrepancies++;
            }
            $accountsChecked++;
        }

        $report->update([
            'total_accounts_checked' => $accountsChecked,
            'total_discrepancies_found' => $totalDiscrepancies,
        ]);
    }

    public function checkAccountBalance(LedgerAccount $account, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = JournalLine::where('account_id', $account->id);
        if ($startDate) {
            $query->where('created_at', '>=', $startDate->toDateTimeString());
        }
        if ($endDate) {
            $query->where('created_at', '<=', $endDate->endOfDay()->toDateTimeString());
        }

        $totalDebits = (int) (clone $query)->where('type', 'debit')->sum('amount');
        $totalCredits = (int) (clone $query)->where('type', 'credit')->sum('amount');

        $expectedBalance = $this->calculateExpectedBalance($account->type, $totalDebits, $totalCredits);
        $actualBalance = (int) $account->balance;
        $discrepancy = $expectedBalance - $actualBalance;

        return [
            'has_discrepancy' => $discrepancy !== 0,
            'expected' => $expectedBalance,
            'actual' => $actualBalance,
            'difference' => $discrepancy,
            'context' => [
                'total_debits' => $totalDebits,
                'total_credits' => $totalCredits,
                'line_count' => $query->count(),
                'account_type' => $account->type,
                'normal_side' => $this->normalSide($account->type),
            ],
        ];
    }

    public function trialBalance(?string $currency = 'SYP'): Collection
    {
        return LedgerAccount::where('currency', $currency)
            ->orderBy('code')
            ->get()
            ->map(fn (LedgerAccount $account) => [
                'code' => $account->code,
                'name' => $account->name,
                'name_ar' => $account->name_ar,
                'type' => $account->type,
                'balance' => $account->balance,
                'currency' => $account->currency,
                'normal_balance' => $this->normalSide($account->type),
            ]);
    }

    public function incomeStatement(?string $currency = 'SYP'): array
    {
        $revenueAccounts = LedgerAccount::where('type', 'revenue')
            ->where('currency', $currency)
            ->orderBy('code')
            ->get()
            ->map(fn (LedgerAccount $a) => [
                'code' => $a->code,
                'name' => $a->name,
                'name_ar' => $a->name_ar,
                'balance' => $a->balance,
            ]);

        $expenseAccounts = LedgerAccount::where('type', 'expense')
            ->where('currency', $currency)
            ->orderBy('code')
            ->get()
            ->map(fn (LedgerAccount $a) => [
                'code' => $a->code,
                'name' => $a->name,
                'name_ar' => $a->name_ar,
                'balance' => $a->balance,
            ]);

        $totalRevenue = $revenueAccounts->sum('balance');
        $totalExpenses = $expenseAccounts->sum('balance');
        $netIncome = $totalRevenue - $totalExpenses;

        return [
            'currency' => $currency,
            'revenue' => $revenueAccounts,
            'total_revenue' => $totalRevenue,
            'expenses' => $expenseAccounts,
            'total_expenses' => $totalExpenses,
            'net_income' => $netIncome,
        ];
    }

    public function balanceSheet(?string $currency = 'SYP'): array
    {
        $assets = LedgerAccount::where('type', 'asset')
            ->where('currency', $currency)
            ->orderBy('code')
            ->get()
            ->map(fn (LedgerAccount $a) => [
                'code' => $a->code,
                'name' => $a->name,
                'name_ar' => $a->name_ar,
                'balance' => $a->balance,
            ]);

        $liabilities = LedgerAccount::where('type', 'liability')
            ->where('currency', $currency)
            ->orderBy('code')
            ->get()
            ->map(fn (LedgerAccount $a) => [
                'code' => $a->code,
                'name' => $a->name,
                'name_ar' => $a->name_ar,
                'balance' => $a->balance,
            ]);

        $equities = LedgerAccount::where('type', 'equity')
            ->where('currency', $currency)
            ->orderBy('code')
            ->get()
            ->map(fn (LedgerAccount $a) => [
                'code' => $a->code,
                'name' => $a->name,
                'name_ar' => $a->name_ar,
                'balance' => $a->balance,
            ]);

        $totalAssets = $assets->sum('balance');
        $totalLiabilities = $liabilities->sum('balance');
        $totalEquity = $equities->sum('balance');

        return [
            'currency' => $currency,
            'assets' => $assets,
            'total_assets' => $totalAssets,
            'liabilities' => $liabilities,
            'total_liabilities' => $totalLiabilities,
            'equity' => $equities,
            'total_equity' => $totalEquity,
            'accounting_equation_balanced' => $totalAssets === ($totalLiabilities + $totalEquity),
        ];
    }

    public function cbsDailyReport(?string $date = null): ReconciliationReport
    {
        $reportDate = $date ?? now()->toDateString();
        $trialBalance = $this->trialBalance();
        $income = $this->incomeStatement();
        $balanceSheet = $this->balanceSheet();

        $report = ReconciliationReport::create([
            'id' => Str::ulid()->toBase32(),
            'report_type' => 'cbs_daily',
            'reporting_date' => $reportDate,
            'status' => ReconciliationReport::STATUS_COMPLETED,
            'currency' => 'SYP',
            'total_accounts_checked' => $trialBalance->count(),
            'total_discrepancies_found' => 0,
            'is_balanced' => true,
            'summary' => [
                'report_date' => $reportDate,
                'report_type' => 'cbs_daily',
                'total_assets' => $balanceSheet['total_assets'],
                'total_liabilities' => $balanceSheet['total_liabilities'],
                'total_equity' => $balanceSheet['total_equity'],
                'net_income' => $income['net_income'],
                'accounting_equation' => $balanceSheet['accounting_equation_balanced'],
                'trial_balance' => $trialBalance,
                'income_statement' => $income,
                'balance_sheet' => $balanceSheet,
            ],
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        event(new ReconciliationCompleted($report));
        return $report;
    }

    public function generateTrialBalance(Carbon $reportingDate, string $currency = 'SYP', bool $includeZeroBalances = false): array
    {
        return $this->cbsReportGenerator->generateTrialBalance($reportingDate, $currency, $includeZeroBalances);
    }

    public function generateBalanceSheet(Carbon $asOfDate, string $currency = 'SYP'): array
    {
        return $this->cbsReportGenerator->generateBalanceSheet($asOfDate, $currency);
    }

    public function generateIncomeStatement(Carbon $startDate, Carbon $endDate, string $currency = 'SYP'): array
    {
        return $this->cbsReportGenerator->generateIncomeStatement($startDate, $endDate, $currency);
    }

    public function generateSettlementReport(Carbon $settlementDate, ?string $counterparty = null): array
    {
        return $this->cbsReportGenerator->generateSettlementReport($settlementDate, $counterparty);
    }

    private function calculateExpectedBalance(string $accountType, int $totalDebits, int $totalCredits): int
    {
        if (in_array($accountType, ['asset', 'expense'], true)) {
            return $totalDebits - $totalCredits;
        }

        return $totalCredits - $totalDebits;
    }

    private function normalSide(string $accountType): string
    {
        return in_array($accountType, ['asset', 'expense'], true) ? 'debit' : 'credit';
    }

    private function recordDiscrepancy(ReconciliationReport $report, LedgerAccount $account, array $result): void
    {
        $severity = $this->determineSeverity($result['difference'], $account->type);

        ReconciliationDiscrepancy::create([
            'id' => Str::ulid()->toBase32(),
            'report_id' => $report->id,
            'account_id' => $account->id,
            'discrepancy_type' => ReconciliationDiscrepancy::TYPE_BALANCE_MISMATCH,
            'severity' => $severity,
            'expected_balance' => $result['expected'],
            'actual_balance' => $result['actual'],
            'difference' => $result['difference'],
            'currency' => $account->currency,
            'context' => $result['context'],
            'description' => sprintf(
                'Balance mismatch for account %s (%s): expected %d, actual %d, difference %d',
                $account->code, $account->name, $result['expected'], $result['actual'], $result['difference']
            ),
            'resolution_steps' => $this->generateResolutionSteps($account, $result),
            'requires_cbs_notification' => $severity === ReconciliationDiscrepancy::SEVERITY_CRITICAL,
        ]);

        if ($severity === ReconciliationDiscrepancy::SEVERITY_CRITICAL) {
            app(AlertingService::class)->alertCriticalDiscrepancy([
                'account_code' => $account->code,
                'difference_formatted' => number_format($result['difference']),
                'discrepancy_type' => ReconciliationDiscrepancy::TYPE_BALANCE_MISMATCH,
                'report_url' => '#',
            ]);
        }
    }

    private function determineSeverity(int $difference, string $accountType): string
    {
        $absDiff = abs($difference);

        $thresholds = match ($accountType) {
            'asset' => ['critical' => 10_000_000_00, 'high' => 1_000_000_00, 'medium' => 100_000],
            'liability', 'equity' => ['critical' => 50_000_000_00, 'high' => 5_000_000_00, 'medium' => 500_000],
            default => ['critical' => 1_000_000_00, 'high' => 100_000_00, 'medium' => 10_000],
        };

        return match (true) {
            $absDiff >= $thresholds['critical'] => ReconciliationDiscrepancy::SEVERITY_CRITICAL,
            $absDiff >= $thresholds['high'] => ReconciliationDiscrepancy::SEVERITY_HIGH,
            $absDiff >= $thresholds['medium'] => ReconciliationDiscrepancy::SEVERITY_MEDIUM,
            default => ReconciliationDiscrepancy::SEVERITY_LOW,
        };
    }

    private function generateResolutionSteps(LedgerAccount $account, array $result): array
    {
        $steps = [];

        if ($result['difference'] > 0) {
            $steps[] = "Review journal entries for unrecorded credits to account {$account->code}";
        } else {
            $steps[] = "Review journal entries for unrecorded debits to account {$account->code}";
        }

        $steps[] = 'Verify no duplicate journal lines exist for recent transactions';
        $steps[] = 'Check for manual balance adjustments outside the journal system';
        $steps[] = 'Audit recent transactions affecting this account for data integrity';

        return $steps;
    }

    public const SEVERITY_CRITICAL = 'critical';
    public const SEVERITY_HIGH = 'high';
    public const SEVERITY_MEDIUM = 'medium';
    public const SEVERITY_LOW = 'low';
}
