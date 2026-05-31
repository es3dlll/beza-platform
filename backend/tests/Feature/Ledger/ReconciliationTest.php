<?php

declare(strict_types=1);

namespace Tests\Feature\Ledger;

use App\Modules\Ledger\Events\ReconciliationCompleted;
use App\Modules\Ledger\Models\JournalEntry;
use App\Modules\Ledger\Models\JournalLine;
use App\Modules\Ledger\Models\LedgerAccount;
use App\Modules\Ledger\Models\ReconciliationReport;
use App\Modules\Ledger\Services\ReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TestCase;

final class ReconciliationTest extends TestCase
{
    use RefreshDatabase;

    private ReconciliationService $service;

    private LedgerAccount $assetAccount;
    private LedgerAccount $liabilityAccount;
    private LedgerAccount $revenueAccount;
    private LedgerAccount $expenseAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(ReconciliationService::class);

        $this->assetAccount = LedgerAccount::create([
            'id' => Str::ulid()->toBase32(),
            'code' => '1100',
            'name' => 'Test Asset',
            'name_ar' => 'أصل اختبار',
            'type' => 'asset',
            'balance' => 0,
            'currency' => 'SYP',
        ]);

        $this->liabilityAccount = LedgerAccount::create([
            'id' => Str::ulid()->toBase32(),
            'code' => '2100',
            'name' => 'Test Liability',
            'name_ar' => 'التزام اختبار',
            'type' => 'liability',
            'balance' => 0,
            'currency' => 'SYP',
        ]);

        $this->revenueAccount = LedgerAccount::create([
            'id' => Str::ulid()->toBase32(),
            'code' => '4100',
            'name' => 'Test Revenue',
            'name_ar' => 'إيراد اختبار',
            'type' => 'revenue',
            'balance' => 0,
            'currency' => 'SYP',
        ]);

        $this->expenseAccount = LedgerAccount::create([
            'id' => Str::ulid()->toBase32(),
            'code' => '5100',
            'name' => 'Test Expense',
            'name_ar' => 'مصروف اختبار',
            'type' => 'expense',
            'balance' => 0,
            'currency' => 'SYP',
        ]);
    }

    public function test_reconciliation_matches_when_balanced(): void
    {
        $entry = JournalEntry::create([
            'id' => Str::ulid()->toBase32(),
            'transaction_id' => 'tx-rec-1',
            'hash' => Str::random(64),
        ]);

        JournalLine::create([
            'id' => Str::ulid()->toBase32(),
            'journal_entry_id' => $entry->id,
            'account_id' => $this->assetAccount->id,
            'type' => 'debit',
            'amount' => 100000,
            'currency' => 'SYP',
        ]);

        JournalLine::create([
            'id' => Str::ulid()->toBase32(),
            'journal_entry_id' => $entry->id,
            'account_id' => $this->liabilityAccount->id,
            'type' => 'credit',
            'amount' => 100000,
            'currency' => 'SYP',
        ]);

        $this->assetAccount->update(['balance' => 100000]);
        $this->liabilityAccount->update(['balance' => 100000]);

        $report = $this->service->reconcile();

        $this->assertEquals(0, $report->total_discrepancies_found);
        $this->assertEquals(4, $report->total_accounts_checked);
        $this->assertTrue($report->is_balanced);
        $this->assertEquals(ReconciliationReport::STATUS_COMPLETED, $report->status);
    }

    public function test_reconciliation_detects_balance_mismatch(): void
    {
        $entry = JournalEntry::create([
            'id' => Str::ulid()->toBase32(),
            'transaction_id' => 'tx-rec-2',
            'hash' => Str::random(64),
        ]);

        JournalLine::create([
            'id' => Str::ulid()->toBase32(),
            'journal_entry_id' => $entry->id,
            'account_id' => $this->assetAccount->id,
            'type' => 'debit',
            'amount' => 100000,
            'currency' => 'SYP',
        ]);

        JournalLine::create([
            'id' => Str::ulid()->toBase32(),
            'journal_entry_id' => $entry->id,
            'account_id' => $this->liabilityAccount->id,
            'type' => 'credit',
            'amount' => 100000,
            'currency' => 'SYP',
        ]);

        $this->assetAccount->update(['balance' => 50000]);

        $report = $this->service->reconcile();

        $this->assertGreaterThanOrEqual(1, $report->total_discrepancies_found);
        $this->assertFalse($report->is_balanced);

        $this->assertDatabaseHas('reconciliation_discrepancies', [
            'account_id' => $this->assetAccount->id,
        ]);
    }

    public function test_reconciliation_creates_report_record(): void
    {
        $report = $this->service->reconcile();

        $this->assertDatabaseHas('reconciliation_reports', [
            'id' => $report->id,
            'report_type' => 'reconciliation',
        ]);

        $this->assertNotNull($report->summary);
    }

    public function test_reconciliation_by_single_account(): void
    {
        $report = $this->service->reconcile(
            scope: 'account',
            accountId: $this->assetAccount->id,
        );

        $this->assertEquals(1, $report->total_accounts_checked);
    }

    public function test_trial_balance_returns_accounts(): void
    {
        $trialBalance = $this->service->trialBalance();

        $this->assertCount(4, $trialBalance);

        $asset = $trialBalance->firstWhere('code', '1100');
        $this->assertEquals('debit', $asset['normal_balance']);

        $liability = $trialBalance->firstWhere('code', '2100');
        $this->assertEquals('credit', $liability['normal_balance']);
    }

    public function test_income_statement_aggregates_revenue_and_expenses(): void
    {
        $this->revenueAccount->update(['balance' => 200000]);
        $this->expenseAccount->update(['balance' => 50000]);

        $income = $this->service->incomeStatement();

        $this->assertEquals(200000, $income['total_revenue']);
        $this->assertEquals(50000, $income['total_expenses']);
        $this->assertEquals(150000, $income['net_income']);
    }

    public function test_balance_sheet_verifies_accounting_equation(): void
    {
        $this->assetAccount->update(['balance' => 300000]);
        $this->liabilityAccount->update(['balance' => 100000]);

        $equityAccount = LedgerAccount::create([
            'id' => Str::ulid()->toBase32(),
            'code' => '3100',
            'name' => 'Test Equity',
            'name_ar' => 'حق ملكية اختبار',
            'type' => 'equity',
            'balance' => 200000,
            'currency' => 'SYP',
        ]);

        $bs = $this->service->balanceSheet();

        $this->assertEquals(300000, $bs['total_assets']);
        $this->assertEquals(100000, $bs['total_liabilities']);
        $this->assertEquals(200000, $bs['total_equity']);
        $this->assertTrue($bs['accounting_equation_balanced']);
    }

    public function test_cbs_daily_report_generates_all_sections(): void
    {
        $this->revenueAccount->update(['balance' => 150000]);
        $this->expenseAccount->update(['balance' => 50000]);
        $this->assetAccount->update(['balance' => 100000]);

        $report = $this->service->cbsDailyReport('2026-05-30');

        $this->assertEquals('cbs_daily', $report->report_type);
        $this->assertArrayHasKey('trial_balance', $report->summary);
        $this->assertArrayHasKey('income_statement', $report->summary);
        $this->assertArrayHasKey('balance_sheet', $report->summary);
    }

    public function test_reconciliation_completed_event_dispatched(): void
    {
        Event::fake();

        $this->service->reconcile();

        Event::assertDispatched(ReconciliationCompleted::class);
    }

    public function test_cbs_trial_balance_report_generated(): void
    {
        $entry = JournalEntry::create([
            'id' => Str::ulid()->toBase32(),
            'transaction_id' => 'tx-cbs-tb',
            'hash' => Str::random(64),
        ]);

        JournalLine::create([
            'id' => Str::ulid()->toBase32(),
            'journal_entry_id' => $entry->id,
            'account_id' => $this->assetAccount->id,
            'type' => 'debit',
            'amount' => 1_000_000,
            'currency' => 'SYP',
        ]);

        JournalLine::create([
            'id' => Str::ulid()->toBase32(),
            'journal_entry_id' => $entry->id,
            'account_id' => $this->liabilityAccount->id,
            'type' => 'credit',
            'amount' => 1_000_000,
            'currency' => 'SYP',
        ]);

        $this->assetAccount->update(['balance' => 1_000_000]);
        $this->liabilityAccount->update(['balance' => 1_000_000]);

        $report = $this->service->generateTrialBalance(Carbon::today(), 'SYP');

        $this->assertArrayHasKey('report_header', $report);
        $this->assertEquals('DAILY_TRIAL_BALANCE', $report['report_header']['report_type']);
        $this->assertEquals('BALANCED', $report['totals']['balance_check']);
    }

    public function test_reconciliation_by_date_range(): void
    {
        $entry = JournalEntry::create([
            'id' => Str::ulid()->toBase32(),
            'transaction_id' => 'tx-date-range',
            'hash' => Str::random(64),
        ]);

        JournalLine::create([
            'id' => Str::ulid()->toBase32(),
            'journal_entry_id' => $entry->id,
            'account_id' => $this->assetAccount->id,
            'type' => 'debit',
            'amount' => 100000,
            'currency' => 'SYP',
            'created_at' => Carbon::create(2026, 1, 15),
        ]);

        $report = $this->service->reconcile(
            scope: 'date_range',
            startDate: Carbon::create(2026, 1, 1),
            endDate: Carbon::create(2026, 1, 31),
        );

        $this->assertEquals(ReconciliationReport::STATUS_COMPLETED, $report->status);
    }
}
