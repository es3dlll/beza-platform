<?php
declare(strict_types=1);

namespace Modules\Ledger\Services;

use Modules\Ledger\Repositories\LedgerAccountRepository;
use Illuminate\Support\Collection;

final class TrialBalanceService
{
    public function __construct(
        private readonly LedgerAccountRepository $accounts,
    ) {}

    public function generate(): array
    {
        $accounts = $this->accounts->findAll();
        $totalDebit = 0;
        $totalCredit = 0;
        $rows = [];

        foreach ($accounts as $account) {
            $balance = $account->balance;
            $isDebitNormal = in_array($account->type, ['asset', 'expense']);
            $debit = $isDebitNormal ? max($balance, 0) : 0;
            $credit = $isDebitNormal ? 0 : max($balance, 0);

            $totalDebit += $debit;
            $totalCredit += $credit;

            $rows[] = [
                'account_number' => $account->account_number,
                'account_name' => $account->name,
                'type' => $account->type,
                'debit' => $debit,
                'credit' => $credit,
            ];
        }

        return [
            'rows' => $rows,
            'totals' => [
                'debit' => $totalDebit,
                'credit' => $totalCredit,
                'balanced' => $totalDebit === $totalCredit,
            ],
            'generated_at' => now()->toIso8601String(),
        ];
    }
}
