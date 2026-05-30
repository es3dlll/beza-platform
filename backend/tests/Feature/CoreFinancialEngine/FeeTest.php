<?php

declare(strict_types=1);

namespace Tests\Feature\CoreFinancialEngine;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CoreFinancialEngine\Models\FeeRule;
use Modules\Ledger\Models\LedgerAccount;
use Tests\TestCase;
use Illuminate\Support\Str;

final class FeeTest extends TestCase
{
    use RefreshDatabase;

    private string $customerAccountId;
    private string $feeRevenueAccountId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authenticateUser();

        $this->customerAccountId = LedgerAccount::create([
            'id' => '01AR123456789012345678f1',
            'account_number' => '1000-FEE',
            'name' => 'Fee Customer',
            'type' => 'asset',
            'currency' => 'SYP',
            'balance' => 1000000,
            'available_balance' => 1000000,
        ])->id;

        $this->feeRevenueAccountId = LedgerAccount::create([
            'id' => '01AR123456789012345678f2',
            'account_number' => '4000-FEE',
            'name' => 'Fee Revenue',
            'type' => 'income',
            'currency' => 'SYP',
            'balance' => 0,
            'available_balance' => 0,
        ])->id;

        FeeRule::create([
            'id' => Str::ulid()->toBase32(),
            'fee_type' => 'transfer_out',
            'calculation_type' => 'flat',
            'value' => 50000,
            'currency' => 'SYP',
            'fee_account_number' => '4000-FEE',
            'is_active' => true,
        ]);

        FeeRule::create([
            'id' => Str::ulid()->toBase32(),
            'fee_type' => 'cash_withdrawal',
            'calculation_type' => 'percentage',
            'value' => 50,
            'currency' => 'SYP',
            'fee_account_number' => '4000-FEE',
            'max_cap' => 250000,
            'is_active' => true,
        ]);
    }

    public function test_can_calculate_flat_fee(): void
    {
        $response = $this->postJson('/api/v1/cfe/fees/calculate', [
            'fee_type' => 'transfer_out',
            'account_id' => $this->customerAccountId,
            'transaction_amount' => 500000,
            'currency' => 'SYP',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.fee_amount', 50000);
    }

    public function test_can_calculate_percentage_fee(): void
    {
        $response = $this->postJson('/api/v1/cfe/fees/calculate', [
            'fee_type' => 'cash_withdrawal',
            'account_id' => $this->customerAccountId,
            'transaction_amount' => 1000000,
            'currency' => 'SYP',
        ]);

        $expected = (int) round(1000000 * (50 / 10000));
        $response->assertStatus(200)
            ->assertJsonPath('data.fee_amount', $expected);
    }

    public function test_can_apply_fee_as_journal_entry(): void
    {
        $response = $this->postJson('/api/v1/cfe/fees/apply', [
            'fee_type' => 'transfer_out',
            'account_id' => $this->customerAccountId,
            'transaction_amount' => 500000,
            'currency' => 'SYP',
            'reference_type' => 'transfer',
            'reference_id' => 'txn-fee-001',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.applied', true)
            ->assertJsonPath('data.fee_amount', 50000);

        $customer = LedgerAccount::find($this->customerAccountId);
        $revenue = LedgerAccount::find($this->feeRevenueAccountId);

        $this->assertEquals(1050000, $customer->balance);
        $this->assertEquals(50000, $revenue->balance);
    }

    public function test_fee_returns_error_for_unknown_type(): void
    {
        $response = $this->postJson('/api/v1/cfe/fees/calculate', [
            'fee_type' => 'nonexistent',
            'account_id' => $this->customerAccountId,
            'transaction_amount' => 500000,
            'currency' => 'SYP',
        ]);

        $response->assertStatus(422);
    }
}
