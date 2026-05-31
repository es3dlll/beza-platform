<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Services;

use App\Domain\ValueObjects\Money;
use App\Modules\Ledger\Models\JournalLine;
use App\Modules\Ledger\Models\LedgerAccount;
use App\Modules\Ledger\Models\ReconciliationReport;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final class CBSReportGenerator
{
    public const CBS_REPORT_TYPES = [
        ReconciliationReport::TYPE_DAILY_TRIAL_BALANCE,
        ReconciliationReport::TYPE_SETTLEMENT_REPORT,
        ReconciliationReport::TYPE_BALANCE_SHEET,
        ReconciliationReport::TYPE_INCOME_STATEMENT,
    ];

    public function generateTrialBalance(Carbon $reportingDate, string $currency = 'SYP', bool $includeZeroBalances = false): array
    {
        $cutoff = $reportingDate->endOfDay();
        $accounts = LedgerAccount::where('currency', $currency)
            ->orderBy('code')
            ->get();

        $trialBalance = [
            'report_header' => [
                'report_type' => 'DAILY_TRIAL_BALANCE',
                'reporting_date' => $reportingDate->format('Y-m-d'),
                'currency' => $currency,
                'generated_at' => now()->toISOString(),
            ],
            'accounts' => [],
            'totals' => ['total_debits' => 0, 'total_credits' => 0, 'balance_check' => null],
        ];

        $totalDebits = Money::zero();
        $totalCredits = Money::zero();

        foreach ($accounts as $account) {
            $debits = (int) JournalLine::where('account_id', $account->id)
                ->where('type', 'debit')
                ->where('created_at', '<=', $cutoff)
                ->sum('amount');
            $credits = (int) JournalLine::where('account_id', $account->id)
                ->where('type', 'credit')
                ->where('created_at', '<=', $cutoff)
                ->sum('amount');

            $balance = $this->calculateBalanceInt($account->type, $debits, $credits);

            if (!$includeZeroBalances && $balance === 0) {
                continue;
            }

            $trialBalance['accounts'][] = [
                'account_code' => $account->code,
                'account_name' => $account->name,
                'account_type' => $account->type,
                'normal_side' => $this->normalSide($account->type),
                'total_debits' => $debits,
                'total_credits' => $credits,
                'balance' => $balance,
            ];

            $nativeDebit = in_array($account->type, ['asset', 'expense'], true);

            if ($balance > 0) {
                if ($nativeDebit) {
                    $totalDebits = $totalDebits->add(Money::fromInt($balance, $this->toCurrencyEnum($currency)));
                } else {
                    $totalCredits = $totalCredits->add(Money::fromInt($balance, $this->toCurrencyEnum($currency)));
                }
            } elseif ($balance < 0) {
                $abs = abs($balance);
                if ($nativeDebit) {
                    $totalCredits = $totalCredits->add(Money::fromInt($abs, $this->toCurrencyEnum($currency)));
                } else {
                    $totalDebits = $totalDebits->add(Money::fromInt($abs, $this->toCurrencyEnum($currency)));
                }
            }
        }

        $trialBalance['totals'] = [
            'total_debits' => $totalDebits->amount(),
            'total_credits' => $totalCredits->amount(),
            'balance_check' => $totalDebits->equals($totalCredits) ? 'BALANCED' : 'UNBALANCED',
            'difference' => Money::fromInt($totalDebits->amount() - $totalCredits->amount(), $this->toCurrencyEnum($currency))->amount(),
        ];

        return $trialBalance;
    }

    public function generateBalanceSheet(Carbon $asOfDate, string $currency = 'SYP'): array
    {
        $cutoff = $asOfDate->endOfDay();

        $assets = $this->balanceForTypes(['asset'], $cutoff, $currency);
        $liabilities = $this->balanceForTypes(['liability'], $cutoff, $currency);
        $equity = $this->balanceForTypes(['equity'], $cutoff, $currency);

        $totalAssets = $assets['total'] ?? Money::zero();
        $totalLiabilities = $liabilities['total'] ?? Money::zero();
        $totalEquity = $equity['total'] ?? Money::zero();

        $balanced = $totalAssets->equals($totalLiabilities->add($totalEquity));

        return [
            'report_header' => [
                'report_type' => 'BALANCE_SHEET',
                'as_of_date' => $asOfDate->format('Y-m-d'),
                'currency' => $currency,
                'generated_at' => now()->toISOString(),
            ],
            'assets' => [
                'items' => $assets['items'] ?? [],
                'total_assets' => $totalAssets->amount(),
            ],
            'liabilities' => [
                'items' => $liabilities['items'] ?? [],
                'total_liabilities' => $totalLiabilities->amount(),
            ],
            'equity' => [
                'items' => $equity['items'] ?? [],
                'total_equity' => $totalEquity->amount(),
            ],
            'balance_check' => [
                'assets' => $totalAssets->amount(),
                'liabilities_and_equity' => $totalLiabilities->add($totalEquity)->amount(),
                'balanced' => $balanced,
            ],
        ];
    }

    public function generateIncomeStatement(Carbon $startDate, Carbon $endDate, string $currency = 'SYP'): array
    {
        $revenue = $this->balanceForTypes(['revenue'], $endDate, $currency, $startDate);
        $expenses = $this->balanceForTypes(['expense'], $endDate, $currency, $startDate);

        $totalRevenue = $revenue['total'] ?? Money::zero();
        $totalExpenses = $expenses['total'] ?? Money::zero();

        $netIncome = $totalRevenue->subtract($totalExpenses);

        return [
            'report_header' => [
                'report_type' => 'INCOME_STATEMENT',
                'period_start' => $startDate->format('Y-m-d'),
                'period_end' => $endDate->format('Y-m-d'),
                'currency' => $currency,
                'generated_at' => now()->toISOString(),
            ],
            'revenue' => [
                'items' => $revenue['items'] ?? [],
                'total_revenue' => $totalRevenue->amount(),
            ],
            'expenses' => [
                'items' => $expenses['items'] ?? [],
                'total_expenses' => $totalExpenses->amount(),
            ],
            'net_income' => $netIncome->amount(),
        ];
    }

    public function generateSettlementReport(Carbon $settlementDate, ?string $counterparty = null): array
    {
        $startOfDay = $settlementDate->startOfDay()->toDateTimeString();
        $endOfDay = $settlementDate->endOfDay()->toDateTimeString();

        $query = JournalLine::whereBetween('created_at', [$startOfDay, $endOfDay]);
        if ($counterparty) {
            $query->where('description', 'like', "%{$counterparty}%");
        }

        $lines = $query->get();
        $grouped = $lines->groupBy(fn (JournalLine $line) => $line->account_id);

        $settlements = $grouped->map(function (Collection $lines, string $accountId): array {
            $totalDebits = $lines->where('type', 'debit')->sum('amount');
            $totalCredits = $lines->where('type', 'credit')->sum('amount');
            $netAmount = Money::fromInt($totalCredits - $totalDebits);

            return [
                'account_id' => $accountId,
                'transaction_count' => $lines->count(),
                'total_debits' => $totalDebits,
                'total_credits' => $totalCredits,
                'net_settlement_amount' => $netAmount->amount(),
                'currency' => $lines->first()->currency ?? 'SYP',
                'settlement_status' => $netAmount->amount() === 0 ? 'SETTLED' : 'PENDING',
            ];
        })->values();

        return [
            'report_header' => [
                'report_type' => 'SETTLEMENT_REPORT',
                'settlement_date' => $settlementDate->format('Y-m-d'),
                'counterparty_filter' => $counterparty,
                'generated_at' => now()->toISOString(),
            ],
            'settlements' => $settlements->toArray(),
            'summary' => [
                'total_accounts' => $settlements->count(),
                'total_transactions' => $settlements->sum('transaction_count'),
                'fully_settled' => $settlements->filter(fn ($s) => $s['settlement_status'] === 'SETTLED')->count(),
                'pending_settlement' => $settlements->filter(fn ($s) => $s['settlement_status'] === 'PENDING')->count(),
            ],
        ];
    }

    public function generateFromReport(ReconciliationReport $report): void
    {
        if (!in_array($report->report_type, self::CBS_REPORT_TYPES)) {
            return;
        }

        $cbsData = match ($report->report_type) {
            ReconciliationReport::TYPE_DAILY_TRIAL_BALANCE => $this->generateTrialBalance(
                $report->reporting_date ?? now(),
                $report->currency ?? 'SYP'
            ),
            ReconciliationReport::TYPE_BALANCE_SHEET => $this->generateBalanceSheet(
                $report->reporting_date ?? now(),
                $report->currency ?? 'SYP'
            ),
            ReconciliationReport::TYPE_INCOME_STATEMENT => $this->generateIncomeStatement(
                $report->start_date ?? $report->reporting_date ?? now(),
                $report->end_date ?? $report->reporting_date ?? now(),
                $report->currency ?? 'SYP'
            ),
            ReconciliationReport::TYPE_SETTLEMENT_REPORT => $this->generateSettlementReport(
                $report->reporting_date ?? now()
            ),
            default => null,
        };

        if ($cbsData) {
            $report->update([
                'cbs_report_code' => $cbsData['report_header']['report_type'] . '_' . now()->format('Ymd_His'),
                'summary' => $cbsData,
            ]);
        }
    }

    private function balanceForTypes(array $types, Carbon $endDate, string $currency, ?Carbon $startDate = null): array
    {
        $accounts = LedgerAccount::whereIn('type', $types)
            ->where('currency', $currency)
            ->orderBy('code')
            ->get();

        $items = [];
        $total = Money::zero();

        foreach ($accounts as $account) {
            $query = JournalLine::where('account_id', $account->id)
                ->where('created_at', '<=', $endDate->endOfDay()->toDateTimeString());

            if ($startDate) {
                $query->where('created_at', '>=', $startDate->toDateTimeString());
            }

            $debits = (int) (clone $query)->where('type', 'debit')->sum('amount');
            $credits = (int) (clone $query)->where('type', 'credit')->sum('amount');

            $balance = $this->calculateBalanceInt($account->type, $debits, $credits);

            if ($balance !== 0) {
                $items[] = [
                    'account_code' => $account->code,
                    'account_name' => $account->name,
                    'balance' => $balance,
                ];
                $total = $total->add(Money::fromInt(abs($balance), $this->toCurrencyEnum($currency)));
            }
        }

        return ['items' => $items, 'total' => $total];
    }

    private function calculateBalance(string $accountType, Money $debits, Money $credits): Money
    {
        $result = $this->calculateBalanceInt($accountType, $debits->amount(), $credits->amount());
        return Money::fromInt(abs($result), $this->toCurrencyEnum($debits->currency()->value));
    }

    private function calculateBalanceInt(string $accountType, int $debits, int $credits): int
    {
        return match ($accountType) {
            'asset', 'expense' => $debits - $credits,
            'liability', 'equity', 'revenue' => $credits - $debits,
            default => $debits - $credits,
        };
    }

    private function normalSide(string $accountType): string
    {
        return in_array($accountType, ['asset', 'expense'], true) ? 'debit' : 'credit';
    }

    private function toCurrencyEnum(string $currency): \App\Domain\Enums\Currency
    {
        return \App\Domain\Enums\Currency::from($currency);
    }
}
