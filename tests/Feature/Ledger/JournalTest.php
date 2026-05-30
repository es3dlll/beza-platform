<?php

declare(strict_types=1);

namespace Tests\Feature\Ledger;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Ledger\Models\LedgerAccount;
use Tests\TestCase;

final class JournalTest extends TestCase
{
    use RefreshDatabase;

    private string $assetId;
    private string $liabilityId;
    private string $incomeId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assetId = LedgerAccount::create([
            'id' => '01AR123456789012345678a1',
            'account_number' => '1000-TEST',
            'name' => 'Test Asset',
            'type' => 'asset',
            'currency' => 'SYP',
            'balance' => 1000000,
            'available_balance' => 1000000,
        ])->id;

        $this->liabilityId = LedgerAccount::create([
            'id' => '01AR123456789012345678a2',
            'account_number' => '2000-TEST',
            'name' => 'Test Liability',
            'type' => 'liability',
            'currency' => 'SYP',
            'balance' => 0,
            'available_balance' => 0,
        ])->id;

        $this->incomeId = LedgerAccount::create([
            'id' => '01AR123456789012345678a3',
            'account_number' => '4000-TEST',
            'name' => 'Test Income',
            'type' => 'income',
            'currency' => 'SYP',
            'balance' => 0,
            'available_balance' => 0,
        ])->id;
    }

    public function test_can_post_balanced_journal_entry(): void
    {
        $response = $this->postJson('/api/v1/ledger/journal/entries', [
            'reference_type' => 'test',
            'reference_id' => 'txn-001',
            'description' => 'Test entry',
            'lines' => [
                [
                    'account_id' => $this->assetId,
                    'amount' => 50000,
                    'type' => 'debit',
                    'description' => 'Dr test',
                ],
                [
                    'account_id' => $this->liabilityId,
                    'amount' => 50000,
                    'type' => 'credit',
                    'description' => 'Cr test',
                ],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.total_amount', 50000);
    }

    public function test_rejects_unbalanced_entry(): void
    {
        $response = $this->postJson('/api/v1/ledger/journal/entries', [
            'reference_type' => 'test',
            'reference_id' => 'txn-002',
            'description' => 'Unbalanced entry',
            'lines' => [
                [
                    'account_id' => $this->assetId,
                    'amount' => 50000,
                    'type' => 'debit',
                ],
            ],
        ]);

        $response->assertStatus(422);
    }

    public function test_updates_account_balances_after_posting(): void
    {
        $this->postJson('/api/v1/ledger/journal/entries', [
            'reference_type' => 'test',
            'reference_id' => 'txn-003',
            'description' => 'Balance update test',
            'lines' => [
                ['account_id' => $this->assetId, 'amount' => 20000, 'type' => 'debit'],
                ['account_id' => $this->liabilityId, 'amount' => 20000, 'type' => 'credit'],
            ],
        ]);

        $asset = LedgerAccount::find($this->assetId);
        $liability = LedgerAccount::find($this->liabilityId);

        $this->assertEquals(1020000, $asset->balance);
        $this->assertEquals(20000, $liability->balance);
    }

    public function test_can_lookup_entry_by_reference(): void
    {
        $this->postJson('/api/v1/ledger/journal/entries', [
            'reference_type' => 'test',
            'reference_id' => 'txn-ref-001',
            'description' => 'Reference lookup test',
            'lines' => [
                ['account_id' => $this->assetId, 'amount' => 10000, 'type' => 'debit'],
                ['account_id' => $this->liabilityId, 'amount' => 10000, 'type' => 'credit'],
            ],
        ]);

        $response = $this->getJson('/api/v1/ledger/journal/reference/test/txn-ref-001');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }
}
