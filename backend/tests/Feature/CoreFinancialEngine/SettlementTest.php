<?php

declare(strict_types=1);

namespace Tests\Feature\CoreFinancialEngine;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Ledger\Models\LedgerAccount;
use Tests\TestCase;

final class SettlementTest extends TestCase
{
    use RefreshDatabase;

    private string $aliceId;
    private string $bobId;
    private string $settlementId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->aliceId = LedgerAccount::create([
            'id' => '01AR123456789012345678s1',
            'account_number' => '1000-ALICE',
            'name' => 'Alice Wallet',
            'type' => 'asset',
            'currency' => 'SYP',
            'balance' => 500000,
            'available_balance' => 500000,
        ])->id;

        $this->bobId = LedgerAccount::create([
            'id' => '01AR123456789012345678s2',
            'account_number' => '2000-BOB',
            'name' => 'Bob Wallet',
            'type' => 'liability',
            'currency' => 'SYP',
            'balance' => 0,
            'available_balance' => 0,
        ])->id;

        $this->settlementId = LedgerAccount::create([
            'id' => '01AR123456789012345678s3',
            'account_number' => '9000-SETTLE',
            'name' => 'Settlement Account',
            'type' => 'liability',
            'currency' => 'SYP',
            'balance' => 1000000,
            'available_balance' => 1000000,
        ])->id;
    }

    public function test_can_execute_batch_settlement(): void
    {
        $response = $this->postJson('/api/v1/cfe/settlements/batch', [
            'settlement_account_id' => $this->settlementId,
            'transactions' => [
                [
                    'account_id' => $this->aliceId,
                    'amount' => 100000,
                    'direction' => 'debit',
                    'description' => 'Alice payout',
                ],
                [
                    'account_id' => $this->bobId,
                    'amount' => 100000,
                    'direction' => 'credit',
                    'description' => 'Bob receive',
                ],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.success', true);
    }

    public function test_daily_cutoff_returns_summary(): void
    {
        $today = now()->format('Y-m-d');

        $response = $this->getJson("/api/v1/cfe/settlements/daily-cutoff/{$today}");

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['date', 'total_entries', 'total_amount', 'by_type']]);
    }

    public function test_settlement_can_be_queried(): void
    {
        $this->postJson('/api/v1/cfe/settlements/batch', [
            'settlement_account_id' => $this->settlementId,
            'transactions' => [
                [
                    'account_id' => $this->aliceId,
                    'amount' => 50000,
                    'direction' => 'debit',
                    'description' => 'Alice settlement',
                ],
                [
                    'account_id' => $this->bobId,
                    'amount' => 50000,
                    'direction' => 'credit',
                    'description' => 'Bob settlement',
                ],
            ],
        ]);

        $alice = LedgerAccount::find($this->aliceId);
        $bob = LedgerAccount::find($this->bobId);

        $this->assertEquals(550000, $alice->balance);
        $this->assertEquals(50000, $bob->balance);
    }
}
