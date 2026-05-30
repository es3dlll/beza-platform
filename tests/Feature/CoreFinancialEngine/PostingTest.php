<?php

declare(strict_types=1);

namespace Tests\Feature\CoreFinancialEngine;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Ledger\Models\LedgerAccount;
use Tests\TestCase;

final class PostingTest extends TestCase
{
    use RefreshDatabase;

    private string $assetId;
    private string $liabilityId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assetId = LedgerAccount::create([
            'id' => '01AR123456789012345678p1',
            'account_number' => '1000-CFE',
            'name' => 'CFE Asset',
            'type' => 'asset',
            'currency' => 'SYP',
            'balance' => 500000,
            'available_balance' => 500000,
        ])->id;

        $this->liabilityId = LedgerAccount::create([
            'id' => '01AR123456789012345678p2',
            'account_number' => '2000-CFE',
            'name' => 'CFE Liability',
            'type' => 'liability',
            'currency' => 'SYP',
            'balance' => 0,
            'available_balance' => 0,
        ])->id;
    }

    public function test_cfe_posting_creates_transaction(): void
    {
        $response = $this->postJson('/api/v1/cfe/transactions', [
            'reference_type' => 'transfer',
            'reference_id' => 'cfe-txn-001',
            'description' => 'CFE posting test',
            'lines' => [
                [
                    'account_id' => $this->assetId,
                    'amount' => 75000,
                    'type' => 'debit',
                    'description' => 'Debit leg',
                ],
                [
                    'account_id' => $this->liabilityId,
                    'amount' => 75000,
                    'type' => 'credit',
                    'description' => 'Credit leg',
                ],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.success', true);

        $this->assertDatabaseHas('cfe_transactions', [
            'reference_type' => 'transfer',
            'reference_id' => 'cfe-txn-001',
            'status' => 'completed',
        ]);
    }

    public function test_cfe_rejects_invalid_transaction(): void
    {
        $response = $this->postJson('/api/v1/cfe/transactions', [
            'reference_type' => 'transfer',
            'reference_id' => 'cfe-txn-002',
            'description' => 'Invalid',
            'lines' => [],
        ]);

        $response->assertStatus(422)
            ->assertJson(['code' => 'VALIDATION_ERROR']);
    }

    public function test_cfe_updates_ledger_balances(): void
    {
        $this->postJson('/api/v1/cfe/transactions', [
            'reference_type' => 'transfer',
            'reference_id' => 'cfe-txn-003',
            'description' => 'Balance check',
            'lines' => [
                ['account_id' => $this->assetId, 'amount' => 100000, 'type' => 'debit'],
                ['account_id' => $this->liabilityId, 'amount' => 100000, 'type' => 'credit'],
            ],
        ]);

        $asset = LedgerAccount::find($this->assetId);
        $liability = LedgerAccount::find($this->liabilityId);

        $this->assertEquals(600000, $asset->balance);
        $this->assertEquals(100000, $liability->balance);
    }
}
