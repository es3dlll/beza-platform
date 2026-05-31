<?php

declare(strict_types=1);

namespace Tests\Feature\Ledger;

use App\Modules\Ledger\Models\JournalEntry;
use App\Modules\Ledger\Services\AccountService;
use App\Modules\Ledger\Services\HashChainService;
use App\Modules\Ledger\Services\JournalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

final class HashChainTest extends TestCase
{
    use RefreshDatabase;

    private JournalService $journal;
    private HashChainService $hashChain;
    private string $assetId;
    private string $revenueId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->journal = $this->app->make(JournalService::class);
        $this->hashChain = $this->app->make(HashChainService::class);
        $accounts = $this->app->make(AccountService::class);

        $asset = $accounts->createAccount('1100', 'Cash', 'نقد', 'asset');
        $revenue = $accounts->createAccount('4100', 'Revenue', 'إيراد', 'revenue');

        $this->assetId = $asset->id;
        $this->revenueId = $revenue->id;
    }

    public function test_genesis_entry_has_no_previous_hash(): void
    {
        $entry = $this->journal->postEntry(
            transactionId: Str::ulid()->toBase32(),
            debits: [['account_id' => $this->assetId, 'amount' => 1000]],
            credits: [['account_id' => $this->revenueId, 'amount' => 1000]],
        );

        $this->assertNull($entry->previous_hash);
        $this->assertNotNull($entry->hash);
    }

    public function test_chain_links_three_entries_correctly(): void
    {
        $e1 = $this->journal->postEntry(
            transactionId: Str::ulid()->toBase32(),
            debits: [['account_id' => $this->assetId, 'amount' => 1000]],
            credits: [['account_id' => $this->revenueId, 'amount' => 1000]],
        );

        $e2 = $this->journal->postEntry(
            transactionId: Str::ulid()->toBase32(),
            debits: [['account_id' => $this->assetId, 'amount' => 2000]],
            credits: [['account_id' => $this->revenueId, 'amount' => 2000]],
        );

        $e3 = $this->journal->postEntry(
            transactionId: Str::ulid()->toBase32(),
            debits: [['account_id' => $this->assetId, 'amount' => 3000]],
            credits: [['account_id' => $this->revenueId, 'amount' => 3000]],
        );

        $this->assertNull($e1->previous_hash);
        $this->assertEquals($e1->hash, $e2->previous_hash);
        $this->assertEquals($e2->hash, $e3->previous_hash);
    }

    public function test_verify_integrity_passes_with_valid_chain(): void
    {
        $this->journal->postEntry(
            transactionId: Str::ulid()->toBase32(),
            debits: [['account_id' => $this->assetId, 'amount' => 1000]],
            credits: [['account_id' => $this->revenueId, 'amount' => 1000]],
        );

        $this->journal->postEntry(
            transactionId: Str::ulid()->toBase32(),
            debits: [['account_id' => $this->assetId, 'amount' => 2000]],
            credits: [['account_id' => $this->revenueId, 'amount' => 2000]],
        );

        $result = $this->journal->verifyChain();
        $this->assertTrue($result['passed']);
        $this->assertEquals(2, $result['total']);
        $this->assertEquals(2, $result['verified']);
        $this->assertEquals(0, $result['failed']);
    }

    public function test_tamper_detection_detects_hash_change(): void
    {
        $this->journal->postEntry(
            transactionId: Str::ulid()->toBase32(),
            debits: [['account_id' => $this->assetId, 'amount' => 1000]],
            credits: [['account_id' => $this->revenueId, 'amount' => 1000]],
        );

        $this->journal->postEntry(
            transactionId: Str::ulid()->toBase32(),
            debits: [['account_id' => $this->assetId, 'amount' => 2000]],
            credits: [['account_id' => $this->revenueId, 'amount' => 2000]],
        );

        $tampered = JournalEntry::orderBy('created_at')->first();
        $tampered->update(['hash' => hash('sha256', 'tampered_data')]);

        $result = $this->journal->verifyChain();
        $this->assertFalse($result['passed']);
        $this->assertGreaterThan(0, $result['failed']);
    }

    public function test_tamper_detection_detects_broken_link(): void
    {
        $e1 = $this->journal->postEntry(
            transactionId: Str::ulid()->toBase32(),
            debits: [['account_id' => $this->assetId, 'amount' => 1000]],
            credits: [['account_id' => $this->revenueId, 'amount' => 1000]],
        );

        $e2 = $this->journal->postEntry(
            transactionId: Str::ulid()->toBase32(),
            debits: [['account_id' => $this->assetId, 'amount' => 2000]],
            credits: [['account_id' => $this->revenueId, 'amount' => 2000]],
        );

        $e2->update(['previous_hash' => hash('sha256', 'fake_previous')]);

        $result = $this->journal->verifyChain();
        $this->assertFalse($result['passed']);
    }

    public function test_validate_chain_pair(): void
    {
        $e1 = $this->journal->postEntry(
            transactionId: Str::ulid()->toBase32(),
            debits: [['account_id' => $this->assetId, 'amount' => 1000]],
            credits: [['account_id' => $this->revenueId, 'amount' => 1000]],
        );

        $e2 = $this->journal->postEntry(
            transactionId: Str::ulid()->toBase32(),
            debits: [['account_id' => $this->assetId, 'amount' => 2000]],
            credits: [['account_id' => $this->revenueId, 'amount' => 2000]],
        );

        $this->assertTrue($this->hashChain->validateChain($e1, $e2));
        $this->assertFalse($this->hashChain->validateChain($e2, $e1));
    }
}
