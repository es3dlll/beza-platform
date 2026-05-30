<?php

declare(strict_types=1);

namespace Tests\Feature\CoreFinancialEngine;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Ledger\Models\LedgerAccount;
use Tests\TestCase;

final class ReversalTest extends TestCase
{
    use RefreshDatabase;

    private string $fromId;
    private string $toId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fromId = LedgerAccount::create([
            'id' => '01AR123456789012345678r1',
            'account_number' => '1000-FROM',
            'name' => 'From Account',
            'type' => 'asset',
            'currency' => 'SYP',
            'balance' => 1000000,
            'available_balance' => 1000000,
        ])->id;

        $this->toId = LedgerAccount::create([
            'id' => '01AR123456789012345678r2',
            'account_number' => '2000-TO',
            'name' => 'To Account',
            'type' => 'liability',
            'currency' => 'SYP',
            'balance' => 0,
            'available_balance' => 0,
        ])->id;
    }

    public function test_can_check_reversibility(): void
    {
        $this->postJson('/api/v1/cfe/transactions', [
            'reference_type' => 'transaction',
            'reference_id' => 'rev-txn-001',
            'description' => 'To be reversed',
            'lines' => [
                ['account_id' => $this->fromId, 'amount' => 200000, 'type' => 'debit'],
                ['account_id' => $this->toId, 'amount' => 200000, 'type' => 'credit'],
            ],
        ]);

        $response = $this->getJson('/api/v1/cfe/transactions/rev-txn-001/reversible');

        $response->assertStatus(200)
            ->assertJsonPath('data.can_reverse', true);
    }

    public function test_can_reverse_transaction(): void
    {
        $this->postJson('/api/v1/cfe/transactions', [
            'reference_type' => 'transaction',
            'reference_id' => 'rev-txn-002',
            'description' => 'Will be reversed',
            'lines' => [
                ['account_id' => $this->fromId, 'amount' => 200000, 'type' => 'debit'],
                ['account_id' => $this->toId, 'amount' => 200000, 'type' => 'credit'],
            ],
        ]);

        $response = $this->postJson('/api/v1/cfe/transactions/rev-txn-002/reverse', [
            'reason' => 'duplicate transaction',
            'initiated_by' => 'system',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.success', true);

        $from = LedgerAccount::find($this->fromId);
        $to = LedgerAccount::find($this->toId);

        $this->assertEquals(1000000, $from->balance);
        $this->assertEquals(0, $to->balance);
    }

    public function test_cannot_reverse_nonexistent_transaction(): void
    {
        $response = $this->postJson('/api/v1/cfe/transactions/nonexistent/reverse', [
            'reason' => 'test',
            'initiated_by' => 'system',
        ]);

        $response->assertStatus(422)
            ->assertJson(['code' => 'ORIGINAL_NOT_FOUND']);
    }
}
