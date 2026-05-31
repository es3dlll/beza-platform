<?php

declare(strict_types=1);

namespace Tests\Feature\Ledger;

use App\Modules\Ledger\Models\LedgerAccount;
use App\Modules\Ledger\Services\AccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AccountTest extends TestCase
{
    use RefreshDatabase;

    private AccountService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(AccountService::class);
    }

    public function test_can_create_account(): void
    {
        $account = $this->service->createAccount(
            code: '9999',
            name: 'Test Account',
            nameAr: 'حساب تجريبي',
            type: 'asset',
        );

        $this->assertNotNull($account->id);
        $this->assertEquals('9999', $account->code);
        $this->assertEquals('asset', $account->type);
        $this->assertEquals(0, $account->balance);
        $this->assertFalse($account->is_system);
    }

    public function test_can_list_accounts(): void
    {
        $this->service->createAccount('1001', 'One', 'واحد', 'asset');
        $this->service->createAccount('1002', 'Two', 'اثنان', 'liability');

        $all = $this->service->listAccounts();
        $this->assertCount(2, $all);

        $assets = $this->service->listAccounts('asset');
        $this->assertCount(1, $assets);
    }

    public function test_can_get_account_by_id(): void
    {
        $created = $this->service->createAccount('1001', 'Test', 'تجريبي', 'asset');
        $fetched = $this->service->getAccount($created->id);
        $this->assertEquals($created->id, $fetched->id);
    }

    public function test_can_get_account_by_code(): void
    {
        $this->service->createAccount('1001', 'Test', 'تجريبي', 'asset');
        $fetched = $this->service->getAccountByCode('1001');
        $this->assertEquals('1001', $fetched->code);
    }

    public function test_can_update_balance_debit_increases_asset(): void
    {
        $account = $this->service->createAccount('1001', 'Cash', 'نقد', 'asset');
        $this->service->updateBalance($account->id, 1000, 'debit');

        $refreshed = $account->fresh();
        $this->assertEquals(1000, $refreshed->balance);
    }

    public function test_can_update_balance_credit_increases_liability(): void
    {
        $account = $this->service->createAccount('2001', 'Payable', 'مستحق', 'liability');
        $this->service->updateBalance($account->id, 500, 'credit');

        $refreshed = $account->fresh();
        $this->assertEquals(500, $refreshed->balance);
    }

    public function test_can_update_balance_credit_decreases_asset(): void
    {
        $account = $this->service->createAccount('1001', 'Cash', 'نقد', 'asset', 'SYP', false);
        $this->service->updateBalance($account->id, 1000, 'debit');
        $this->service->updateBalance($account->id, 300, 'credit');

        $refreshed = $account->fresh();
        $this->assertEquals(700, $refreshed->balance);
    }

    public function test_get_chart_of_accounts_returns_ordered(): void
    {
        $this->service->createAccount('2001', 'B', 'ب', 'liability');
        $this->service->createAccount('1001', 'A', 'أ', 'asset');

        $chart = $this->service->getChartOfAccounts();
        $this->assertCount(2, $chart);
        $this->assertEquals('1001', $chart->first()->code);
    }
}
