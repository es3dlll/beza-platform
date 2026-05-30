<?php

declare(strict_types=1);

namespace Tests\Feature\Ledger;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Ledger\Models\LedgerAccount;
use Modules\Ledger\Services\TrialBalanceService;
use Tests\TestCase;

final class TrialBalanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authenticateUser();

        LedgerAccount::create([
            'id' => '01AR123456789012345678t1',
            'account_number' => '1000-TB',
            'name' => 'TB Asset',
            'type' => 'asset',
            'currency' => 'SYP',
            'balance' => 100000,
            'available_balance' => 100000,
        ]);

        LedgerAccount::create([
            'id' => '01AR123456789012345678t2',
            'account_number' => '2000-TB',
            'name' => 'TB Liability',
            'type' => 'liability',
            'currency' => 'SYP',
            'balance' => 100000,
            'available_balance' => 100000,
        ]);

        LedgerAccount::create([
            'id' => '01AR123456789012345678t3',
            'account_number' => '3000-TB',
            'name' => 'TB Equity',
            'type' => 'equity',
            'currency' => 'SYP',
            'balance' => 0,
            'available_balance' => 0,
        ]);
    }

    public function test_trial_balance_generates_correctly(): void
    {
        $service = $this->app->make(TrialBalanceService::class);
        $result = $service->generate();

        $this->assertCount(3, $result['rows']);
        $this->assertTrue($result['totals']['balanced']);
        $this->assertEquals(100000, $result['totals']['debit']);
        $this->assertEquals(100000, $result['totals']['credit']);
    }

    public function test_trial_balance_api_returns_data(): void
    {
        $response = $this->getJson('/api/v1/ledger/trial-balance');

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['rows', 'totals', 'generated_at']]);
    }
}
