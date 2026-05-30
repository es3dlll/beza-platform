<?php

declare(strict_types=1);

namespace Tests\Feature\Ledger;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Ledger\Models\LedgerAccount;
use Tests\TestCase;

final class AccountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authenticateUser();
    }

    public function test_can_create_account(): void
    {
        $response = $this->postJson('/api/v1/ledger/accounts', [
            'account_number' => '1000-TEST',
            'name' => 'Test Account',
            'type' => 'asset',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.account_number', '1000-TEST')
            ->assertJsonPath('data.type', 'asset');
    }

    public function test_can_list_accounts(): void
    {
        LedgerAccount::create([
            'id' => '01AR12345678901234567891',
            'account_number' => '1000-001',
            'name' => 'Cash',
            'type' => 'asset',
            'currency' => 'SYP',
            'balance' => 0,
            'available_balance' => 0,
        ]);

        $response = $this->getJson('/api/v1/ledger/accounts');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_can_get_account_balance(): void
    {
        $account = LedgerAccount::create([
            'id' => '01AR12345678901234567892',
            'account_number' => '1000-002',
            'name' => 'Cash 2',
            'type' => 'asset',
            'currency' => 'SYP',
            'balance' => 500000,
            'available_balance' => 500000,
        ]);

        $response = $this->getJson("/api/v1/ledger/accounts/{$account->id}/balance");

        $response->assertStatus(200)
            ->assertJsonPath('data.amount', 500000)
            ->assertJsonPath('data.currency', 'SYP');
    }

    public function test_returns_404_for_nonexistent_account(): void
    {
        $response = $this->getJson('/api/v1/ledger/accounts/nonexistent');

        $response->assertStatus(404);
    }
}
