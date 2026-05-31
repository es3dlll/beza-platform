<?php

declare(strict_types=1);

namespace Tests\Feature\Ledger;

use App\Modules\Ledger\Exceptions\ImbalancedJournalException;
use App\Modules\Ledger\Models\LedgerAccount;
use App\Modules\Ledger\Services\AccountService;
use App\Modules\Ledger\Services\JournalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class JournalTest extends TestCase
{
    use RefreshDatabase;

    private JournalService $journal;
    private LedgerAccount $assetAccount;
    private LedgerAccount $revenueAccount;
    private LedgerAccount $liabilityAccount;

    protected function setUp(): void
    {
        parent::setUp();
        $this->journal = $this->app->make(JournalService::class);
        $accounts = $this->app->make(AccountService::class);

        $this->assetAccount = $accounts->createAccount('1100', 'Cash', 'نقد', 'asset');
        $this->revenueAccount = $accounts->createAccount('4100', 'Revenue', 'إيراد', 'revenue');
        $this->liabilityAccount = $accounts->createAccount('2100', 'Payable', 'مستحق', 'liability');
    }

    public function test_can_post_entry(): void
    {
        $entry = $this->journal->postEntry(
            transactionId: Str::ulid()->toBase32(),
            debits: [
                ['account_id' => $this->assetAccount->id, 'amount' => 50000],
            ],
            credits: [
                ['account_id' => $this->revenueAccount->id, 'amount' => 50000],
            ],
            description: 'Test transaction',
            descriptionAr: 'عملية تجريبية',
        );

        $this->assertNotNull($entry->id);
        $this->assertNotNull($entry->hash);
        $this->assertCount(2, $entry->lines);

        $this->assertEquals(50000, $this->assetAccount->fresh()->balance);
        $this->assertEquals(50000, $this->revenueAccount->fresh()->balance);
    }

    public function test_rejects_imbalanced_entry(): void
    {
        $this->expectException(ImbalancedJournalException::class);

        $this->journal->postEntry(
            transactionId: Str::ulid()->toBase32(),
            debits: [
                ['account_id' => $this->assetAccount->id, 'amount' => 50000],
            ],
            credits: [
                ['account_id' => $this->revenueAccount->id, 'amount' => 30000],
            ],
        );
    }

    public function test_can_get_entry(): void
    {
        $entry = $this->journal->postEntry(
            transactionId: Str::ulid()->toBase32(),
            debits: [
                ['account_id' => $this->assetAccount->id, 'amount' => 10000],
            ],
            credits: [
                ['account_id' => $this->revenueAccount->id, 'amount' => 10000],
            ],
        );

        $fetched = $this->journal->getEntry($entry->id);
        $this->assertEquals($entry->id, $fetched->id);
        $this->assertCount(2, $fetched->lines);
    }

    public function test_can_get_account_balance(): void
    {
        $this->journal->postEntry(
            transactionId: Str::ulid()->toBase32(),
            debits: [
                ['account_id' => $this->assetAccount->id, 'amount' => 25000],
            ],
            credits: [
                ['account_id' => $this->liabilityAccount->id, 'amount' => 25000],
            ],
        );

        $this->assertEquals(25000, $this->journal->getAccountBalance($this->assetAccount->id));
        $this->assertEquals(25000, $this->journal->getAccountBalance($this->liabilityAccount->id));
    }

    public function test_multi_line_entry(): void
    {
        $entry = $this->journal->postEntry(
            transactionId: Str::ulid()->toBase32(),
            debits: [
                ['account_id' => $this->assetAccount->id, 'amount' => 75000],
            ],
            credits: [
                ['account_id' => $this->revenueAccount->id, 'amount' => 50000],
                ['account_id' => $this->liabilityAccount->id, 'amount' => 25000],
            ],
        );

        $this->assertCount(3, $entry->lines);
        $this->assertEquals(75000, $this->assetAccount->fresh()->balance);
        $this->assertEquals(50000, $this->revenueAccount->fresh()->balance);
        $this->assertEquals(25000, $this->liabilityAccount->fresh()->balance);
    }
}
