<?php

declare(strict_types=1);

namespace Tests\Feature\Ledger;

use App\Modules\Ledger\Models\LedgerAccount;
use App\Modules\Ledger\Services\AccountService;
use App\Modules\Ledger\Services\JournalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class TrialBalanceTest extends TestCase
{
    use RefreshDatabase;

    private JournalService $journal;
    private AccountService $accounts;
    private LedgerAccount $cash;
    private LedgerAccount $receivable;
    private LedgerAccount $payable;
    private LedgerAccount $revenue;
    private LedgerAccount $expense;

    protected function setUp(): void
    {
        parent::setUp();
        $this->journal = $this->app->make(JournalService::class);
        $this->accounts = $this->app->make(AccountService::class);

        $this->cash = $this->accounts->createAccount('1100', 'Cash', 'نقد', 'asset');
        $this->receivable = $this->accounts->createAccount('1200', 'Receivable', 'ذمم مدينة', 'asset');
        $this->payable = $this->accounts->createAccount('2100', 'Payable', 'مستحق', 'liability');
        $this->revenue = $this->accounts->createAccount('4100', 'Revenue', 'إيراد', 'revenue');
        $this->expense = $this->accounts->createAccount('5100', 'Expense', 'مصروف', 'expense');
    }

    public function test_trial_balance_returns_correct_balances(): void
    {
        $this->journal->postEntry(
            transactionId: Str::ulid()->toBase32(),
            debits: [
                ['account_id' => $this->cash->id, 'amount' => 100000],
            ],
            credits: [
                ['account_id' => $this->revenue->id, 'amount' => 100000],
            ],
        );

        $this->journal->postEntry(
            transactionId: Str::ulid()->toBase32(),
            debits: [
                ['account_id' => $this->expense->id, 'amount' => 30000],
            ],
            credits: [
                ['account_id' => $this->cash->id, 'amount' => 30000],
            ],
        );

        $trialBalance = $this->journal->getTrialBalance();

        $this->assertEquals(70000, $trialBalance->firstWhere('code', '1100')['balance']);
        $this->assertEquals(0, $trialBalance->firstWhere('code', '1200')['balance']);
        $this->assertEquals(0, $trialBalance->firstWhere('code', '2100')['balance']);
        $this->assertEquals(100000, $trialBalance->firstWhere('code', '4100')['balance']);
        $this->assertEquals(30000, $trialBalance->firstWhere('code', '5100')['balance']);
    }

    public function test_trial_balance_normal_balance_direction(): void
    {
        $trialBalance = $this->journal->getTrialBalance();

        $this->assertEquals('debit', $trialBalance->firstWhere('code', '1100')['normal_balance']);
        $this->assertEquals('credit', $trialBalance->firstWhere('code', '2100')['normal_balance']);
        $this->assertEquals('credit', $trialBalance->firstWhere('code', '4100')['normal_balance']);
        $this->assertEquals('debit', $trialBalance->firstWhere('code', '5100')['normal_balance']);
    }

    public function test_trial_balance_total_debits_equals_total_credits(): void
    {
        $this->journal->postEntry(
            transactionId: Str::ulid()->toBase32(),
            debits: [
                ['account_id' => $this->cash->id, 'amount' => 50000],
                ['account_id' => $this->expense->id, 'amount' => 20000],
            ],
            credits: [
                ['account_id' => $this->revenue->id, 'amount' => 70000],
            ],
        );

        $tb = $this->journal->getTrialBalance();

        $normalDebitSum = collect($tb)->where('normal_balance', 'debit')->sum('balance');
        $normalCreditSum = collect($tb)->where('normal_balance', 'credit')->sum('balance');

        $this->assertEquals($normalCreditSum, $normalDebitSum);
    }
}
