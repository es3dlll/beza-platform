<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Enums\Currency;
use App\Domain\ValueObjects\Money;
use App\Modules\Agent\Models\Agent;
use App\Modules\Agent\Models\AgentTransaction;
use App\Modules\Agent\Models\AgentWallet;
use App\Modules\Agent\Models\CommissionRule;
use App\Modules\Agent\Models\Settlement;
use App\Modules\Agent\Services\AgentService;
use App\Modules\Agent\Services\CashInOutService;
use App\Modules\Agent\Services\CommissionService;
use App\Modules\Agent\Services\SettlementService;
use App\Modules\FinancialCore\Services\Engines\PostingEngine;
use App\Modules\FinancialCore\Services\IdempotencyService;
use App\Modules\Ledger\Database\Seeders\LedgerSeeder;
use App\Modules\Ledger\Models\LedgerAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class AgentTest extends TestCase
{
    use RefreshDatabase;

    private AgentService $agentService;
    private CashInOutService $cashInOutService;
    private CommissionService $commissionService;
    private SettlementService $settlementService;
    private Agent $agent;
    private AgentWallet $wallet;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LedgerSeeder::class);
        $this->agentService = $this->app->make(AgentService::class);
        $this->cashInOutService = $this->app->make(CashInOutService::class);
        $this->commissionService = $this->app->make(CommissionService::class);
        $this->settlementService = $this->app->make(SettlementService::class);

        $customerAccount = LedgerAccount::where('code', '1100')->firstOrFail();
        $customerAccount->increment('balance', 100000000);

        $this->agent = $this->agentService->register([
            'user_id' => Str::ulid()->toBase32(),
            'phone' => '963944' . fake()->numerify('#######'),
            'name' => 'Test Agent',
            'name_ar' => 'وكيل اختبار',
            'kyc_tier' => 't2',
            'status' => 'active',
            'is_verified' => true,
            'verified_at' => now(),
        ]);

        $this->wallet = $this->agentService->activateWallet($this->agent->id, 'SYP');
        $this->wallet->increment('float_balance', 10000000);
    }

    public function test_register_agent(): void
    {
        $agent = $this->agentService->register([
            'user_id' => Str::ulid()->toBase32(),
            'phone' => '963955' . fake()->numerify('#######'),
            'name' => 'New Agent',
            'name_ar' => 'وكيل جديد',
        ]);

        $this->assertEquals('pending', $agent->status);
        $this->assertEquals('t0', $agent->kyc_tier);
    }

    public function test_verify_agent(): void
    {
        $this->agentService->verify($this->agent->id);
        $agent = $this->agentService->getAgent($this->agent->id);
        $this->assertTrue($agent->is_verified);
        $this->assertEquals('active', $agent->status);
    }

    public function test_suspend_agent(): void
    {
        $this->agentService->suspend($this->agent->id);
        $this->assertFalse($this->agentService->getAgent($this->agent->id)->canTransact());
    }

    public function test_cash_in(): void
    {
        $result = $this->cashInOutService->cashIn(
            agentId: $this->agent->id,
            customerWalletId: Str::ulid()->toBase32(),
            amount: 50000,
            idempotencyKey: 'cash-in-1',
        );

        $this->assertEquals('cash_in', $result['transaction']->type);
        $this->assertEquals('completed', $result['transaction']->status);

        $this->wallet->refresh();
        $this->assertEquals(10050000, $this->wallet->float_balance);
    }

    public function test_cash_out(): void
    {
        $result = $this->cashInOutService->cashOut(
            agentId: $this->agent->id,
            customerWalletId: Str::ulid()->toBase32(),
            amount: 30000,
            idempotencyKey: 'cash-out-1',
        );

        $this->assertEquals('cash_out', $result['transaction']->type);
        $this->assertEquals('completed', $result['transaction']->status);

        $this->wallet->refresh();
        $this->assertEquals(9970000, $this->wallet->float_balance);
    }

    public function test_cash_out_insufficient_float(): void
    {
        $this->wallet->update(['daily_limit' => 500000000, 'daily_used' => 0]);

        $this->expectException(\App\Modules\Agent\Exceptions\InsufficientFloatException::class);

        $this->cashInOutService->cashOut(
            agentId: $this->agent->id,
            customerWalletId: Str::ulid()->toBase32(),
            amount: 50000000,
            idempotencyKey: 'cash-out-fail',
        );
    }

    public function test_daily_limit_exceeded(): void
    {
        $this->wallet->update(['daily_used' => 4900000, 'daily_limit' => 5000000]);

        $this->expectException(\App\Modules\Agent\Exceptions\DailyLimitExceededException::class);

        $this->cashInOutService->cashIn(
            agentId: $this->agent->id,
            customerWalletId: Str::ulid()->toBase32(),
            amount: 200000,
        );
    }

    public function test_idempotency_on_cash_in(): void
    {
        $customerWallet = Str::ulid()->toBase32();

        $first = $this->cashInOutService->cashIn(
            agentId: $this->agent->id,
            customerWalletId: $customerWallet,
            amount: 25000,
            idempotencyKey: 'dup-cash-in',
        );

        $second = $this->cashInOutService->cashIn(
            agentId: $this->agent->id,
            customerWalletId: $customerWallet,
            amount: 25000,
            idempotencyKey: 'dup-cash-in',
        );

        $this->assertEquals(1, AgentTransaction::where('idempotency_key', 'dup-cash-in')->count());
    }

    public function test_commission_flat_rule(): void
    {
        $rule = CommissionRule::create([
            'name' => 'Cash In Flat',
            'name_ar' => 'عمولة إيداع ثابتة',
            'txn_type' => 'cash_in',
            'calc_type' => 'flat',
            'value' => 2000,
            'kyc_tier_min' => 't0',
        ]);

        $commission = $this->commissionService->calculateCommission(
            'cash_in',
            new Money(100000, Currency::SYP),
            't1',
        );

        $this->assertEquals(2000, $commission->amount());
    }

    public function test_commission_percentage_rule(): void
    {
        $rule = CommissionRule::create([
            'name' => 'Cash Out Percentage',
            'name_ar' => 'عمولة سحب نسبة',
            'txn_type' => 'cash_out',
            'calc_type' => 'percentage',
            'value' => 50,
            'kyc_tier_min' => 't0',
        ]);

        $commission = $this->commissionService->calculateCommission(
            'cash_out',
            new Money(100000, Currency::SYP),
            't1',
        );

        $this->assertEquals(500, $commission->amount());
    }

    public function test_commission_with_cap(): void
    {
        $rule = CommissionRule::create([
            'name' => 'Capped',
            'name_ar' => 'عمولة محدودة',
            'txn_type' => 'cash_in',
            'calc_type' => 'percentage',
            'value' => 200,
            'cap_amount' => 10000,
            'kyc_tier_min' => 't0',
        ]);

        $commission = $this->commissionService->calculateCommission(
            'cash_in',
            new Money(10000000, Currency::SYP),
            't1',
        );

        $this->assertEquals(10000, $commission->amount());
    }

    public function test_generate_settlement(): void
    {
        $this->cashInOutService->cashIn(
            agentId: $this->agent->id,
            customerWalletId: Str::ulid()->toBase32(),
            amount: 50000,
            idempotencyKey: 'settle-cash-in',
        );

        $settlement = $this->settlementService->generateForAgent(
            $this->agent,
            now()->addDay()->toDateString(),
        );

        $this->assertNotNull($settlement->id);
        $this->assertGreaterThanOrEqual(0, $settlement->expected_amount);
    }

    public function test_settlement_tracks_commission(): void
    {
        $rule = CommissionRule::create([
            'name' => 'Settlement Commission',
            'name_ar' => 'عمولة التسوية',
            'txn_type' => 'cash_in',
            'calc_type' => 'percentage',
            'value' => 100,
            'kyc_tier_min' => 't0',
        ]);

        $this->cashInOutService->cashIn(
            agentId: $this->agent->id,
            customerWalletId: Str::ulid()->toBase32(),
            amount: 100000,
            idempotencyKey: 'settle-comm',
        );

        $settlement = $this->settlementService->generateForAgent(
            $this->agent,
            now()->addDay()->toDateString(),
        );

        $this->assertGreaterThan(0, $settlement->commission_amount);
    }

    public function test_transaction_history(): void
    {
        AgentTransaction::factory()->count(3)->create([
            'agent_id' => $this->agent->id,
        ]);

        $history = $this->cashInOutService->getHistory($this->agent->id, 15);
        $this->assertCount(3, $history->items());
    }

    public function test_single_transaction_detail(): void
    {
        $result = $this->cashInOutService->cashIn(
            agentId: $this->agent->id,
            customerWalletId: Str::ulid()->toBase32(),
            amount: 75000,
            idempotencyKey: 'detail-cash-in',
        );

        $tx = AgentTransaction::findOrFail($result['transaction']->id);
        $this->assertEquals(75000, $tx->amount);
        $this->assertEquals('cash_in', $tx->type);
    }
}
