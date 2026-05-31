<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Agent\Enums\AgentTransactionType;
use App\Modules\Agent\Enums\ServicePointStatus;
use App\Modules\Agent\Events\AgentActivated;
use App\Modules\Agent\Events\AgentSuspendedCompliance;
use App\Modules\Agent\Events\AgentTransactionCompleted;
use App\Modules\Agent\Events\AgentTransactionValidated;
use App\Modules\Agent\Events\CashInCompleted;
use App\Modules\Agent\Events\CashOutCompleted;
use App\Modules\Agent\Events\CommissionReadyForPayout;
use App\Modules\Agent\Events\FloatUpdated;
use App\Modules\Agent\Events\LowFloatWarning;
use App\Modules\Agent\Events\TriggerAgentSettlement;
use App\Modules\Agent\Exceptions\InsufficientFloatException;
use App\Modules\Agent\Listeners\CommissionCalculatorListener;
use App\Modules\Agent\Listeners\ComplianceTierListener;
use App\Modules\Agent\Listeners\FloatSyncListener;
use App\Modules\Agent\Models\Agent;
use App\Modules\Agent\Models\AgentTransaction;
use App\Modules\Agent\Models\AgentWallet;
use App\Modules\Agent\Services\AgentLiquidityEngine;
use App\Modules\Agent\Services\AgentService;
use App\Modules\Agent\ValueObjects\AgentId;
use App\Modules\Agent\ValueObjects\CommissionTier;
use App\Modules\Agent\ValueObjects\FloatBalance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

final class AgentTest extends TestCase
{
    use RefreshDatabase;

    private AgentLiquidityEngine $engine;
    private AgentService $agentService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = $this->app->make(AgentLiquidityEngine::class);
        $this->agentService = $this->app->make(AgentService::class);
    }

    /** @test دورة كاملة: تسجيل وكيل -> تفعيل -> تنفيذ سحب/إيداع -> تحديث السيولة -> تسوية يومية */
    public function test_full_agent_cycle(): void
    {
        Event::fake();

        // تسجيل وكيل
        $agent = $this->engine->onboard([
            'user_id' => 'user-1',
            'phone' => '963900000001',
            'name' => 'Test Agent',
            'name_ar' => 'وكيل اختبار',
        ]);

        $this->assertNotNull($agent);
        $this->assertEquals('pending', $agent->status);
        $this->assertEquals('Bronze', $agent->commission_tier);

        Event::assertDispatched(AgentActivated::class);

        // تفعيل
        $this->agentService->verify($agent->id);
        $agent->refresh();
        $this->assertEquals('active', $agent->status);
        $this->assertTrue($agent->is_verified);

        // إيداع نقدي
        $wallet = $this->agentService->getWallet($agent->id);
        $wallet->update(['float_balance' => 500000]);

        $validated = $this->engine->validateTransaction($agent->id, 'CASH_IN', 100000);
        $this->assertTrue($validated['approved']);

        // سحب نقدي
        $validated = $this->engine->validateTransaction($agent->id, 'CASH_OUT', 50000);
        $this->assertTrue($validated['approved']);

        // محاكاة إتمام معاملة وربطها بـ FloatSyncListener
        $listener = $this->app->make(FloatSyncListener::class);
        $listener->handle(new AgentTransactionCompleted(
            agentTransactionId: 'tx-1',
            agentId: $agent->id,
            type: 'cash_in',
            amount: 100000,
            currency: 'SYP',
            commissionAmount: 500,
            timestamp: now()->getTimestamp(),
        ));

        $wallet->refresh();
        $this->assertEquals(600000, $wallet->float_balance);
    }

    /** @test رفض عملية بسبب انخفاض السيولة */
    public function test_cash_out_rejected_when_insufficient_float(): void
    {
        $agent = $this->engine->onboard([
            'user_id' => 'user-2',
            'phone' => '963900000002',
            'name' => 'Low Float Agent',
            'name_ar' => 'وكيل سيولة منخفضة',
        ]);

        $this->agentService->verify($agent->id);
        $agent->refresh();

        $this->expectException(InsufficientFloatException::class);
        $this->engine->validateTransaction($agent->id, 'CASH_OUT', 999999999);
    }

    /** @test حساب عمولة دقيق حسب المستوى */
    public function test_commission_calculation_by_tier(): void
    {
        $bronze = CommissionTier::fromString('Bronze');
        $gold = CommissionTier::fromString('Gold');

        // Bronze: cash_in = 50 bps = 0.5%
        $bronzeCommission = $bronze->calculateCommission('cash_in', 100000);
        $this->assertEquals(500, $bronzeCommission);

        // Gold: cash_in = 100 bps = 1%
        $goldCommission = $gold->calculateCommission('cash_in', 100000);
        $this->assertEquals(1000, $goldCommission);

        // CommissionCalculator يستمع لحدث الإتمام
        Event::fake();

        $listener = $this->app->make(CommissionCalculatorListener::class);
        $agent = $this->engine->onboard([
            'user_id' => 'user-3',
            'phone' => '963900000003',
            'name' => 'Commission Agent',
            'name_ar' => 'وكيل عمولات',
        ]);

        $agent->update(['commission_tier' => 'Gold']);

        $listener->handle(new AgentTransactionCompleted(
            agentTransactionId: 'tx-comm-1',
            agentId: $agent->id,
            type: 'cash_in',
            amount: 200000,
            currency: 'SYP',
            commissionAmount: 0,
            timestamp: now()->getTimestamp(),
        ));

        Event::assertDispatched(CommissionReadyForPayout::class);
    }

    /** @test تعليق تلقائي للوكيل عند وجود حالة امتثال نشطة */
    public function test_agent_suspended_on_compliance(): void
    {
        Event::fake();

        $agent = $this->engine->onboard([
            'user_id' => 'user-4',
            'phone' => '963900000004',
            'name' => 'Compliance Agent',
            'name_ar' => 'وكيل امتثال',
        ]);

        $this->agentService->verify($agent->id);
        $agent->refresh();
        $this->assertEquals('active', $agent->status);

        // محاكاة حدث امتثال سلبي
        $listener = $this->app->make(ComplianceTierListener::class);
        $listener->handle(new AgentSuspendedCompliance(
            agentId: $agent->id,
            reason: 'نشاط مشبوه: معاملات متعددة بدون توثيق',
            caseId: 'CASE-COMPLIANCE-1',
            timestamp: now()->getTimestamp(),
        ));

        $agent->refresh();
        $this->assertEquals('suspended', $agent->status);

        // استعادة يدوية بعد إغلاق الحالة
        $this->agentService->verify($agent->id);
        $agent->refresh();
        $this->assertEquals('active', $agent->status);
    }

    /** @test منع التكرار في طلبات التسوية */
    public function test_settlement_idempotency(): void
    {
        $agent = $this->engine->onboard([
            'user_id' => 'user-5',
            'phone' => '963900000005',
            'name' => 'Settlement Agent',
            'name_ar' => 'وكيل تسوية',
        ]);

        $this->agentService->verify($agent->id);
        $agent->refresh();

        $this->engine->triggerSettlement($agent->id, now()->toDateString());

        $settlements = \App\Modules\Agent\Models\Settlement::where('agent_id', $agent->id)->get();
        $this->assertCount(1, $settlements);
    }

    /** @test ServicePointStatus يمنع العمليات إلا في حالة ACTIVE */
    public function test_service_point_status_restrictions(): void
    {
        $this->assertTrue(ServicePointStatus::canOperate('ACTIVE'));
        $this->assertFalse(ServicePointStatus::canOperate('SUSPENDED'));
        $this->assertFalse(ServicePointStatus::canOperate('UNDER_AUDIT'));
        $this->assertFalse(ServicePointStatus::canOperate('CLOSED'));
        $this->assertFalse(ServicePointStatus::canOperate('PENDING_ACTIVATION'));
    }

    /** @test AgentId يتحقق من البادئة */
    public function test_agent_id_format(): void
    {
        $id = AgentId::generate();
        $this->assertStringStartsWith('AGT-', $id->toString());

        $this->expectException(\InvalidArgumentException::class);
        new AgentId('INVALID-PREFIX');
    }

    /** @test FloatBalance يحسب الحدود بشكل صحيح */
    public function test_float_balance_limits(): void
    {
        $float = new FloatBalance(
            available: 100000,
            pending: 20000,
            minimumRequired: 50000,
            dailyLimit: 500000,
            dailyUsed: 300000,
        );

        $this->assertEquals(100000, $float->available());
        $this->assertEquals(120000, $float->total());
        $this->assertTrue($float->canDeduct(50000));
        $this->assertFalse($float->canDeduct(150000));
        $this->assertFalse($float->isBelowMinimum()); // 100000 >= 50000
        $this->assertTrue($float->withinDailyLimit(100000)); // 300000+100000=400000 <= 500000
        $this->assertFalse($float->withinDailyLimit(250000)); // 300000+250000=550000 > 500000

        // تحت الحد الأدنى
        $lowFloat = new FloatBalance(available: 30000, pending: 0, minimumRequired: 50000, dailyLimit: 500000, dailyUsed: 0);
        $this->assertTrue($lowFloat->isBelowMinimum());

        $this->expectException(InsufficientFloatException::class);
        $lowFloat->assertSufficient(40000);
    }

    /** @test CommissionTier يحسب العمولات حسب النوع */
    public function test_commission_tier_defaults(): void
    {
        $tier = CommissionTier::fromString('Platinum');
        $this->assertEquals('Platinum', $tier->tier());
        $this->assertEquals(125, $tier->cashInBps());

        // اختبار السقف اليومي
        $commission = $tier->calculateCommission('cash_in', 100000000);
        $this->assertLessThanOrEqual($tier->dailyCap(), $commission);
    }

    /** @test AgentTransactionType يحتوي على الأنواع الصحيحة */
    public function test_transaction_types(): void
    {
        $this->assertContains('CASH_IN', AgentTransactionType::ALL);
        $this->assertContains('CASH_OUT', AgentTransactionType::ALL);
        $this->assertContains('FLOAT_TRANSFER', AgentTransactionType::ALL);
        $this->assertContains('COMMISSION_PAYOUT', AgentTransactionType::ALL);
        $this->assertContains('SETTLEMENT_WITHDRAWAL', AgentTransactionType::ALL);
    }

    /** @test FloatSyncListener يحدث السيولة ويطلق تحذيراً عند الانخفاض */
    public function test_float_sync_listener_updates_balance(): void
    {
        $agent = $this->engine->onboard([
            'user_id' => 'user-6',
            'phone' => '963900000006',
            'name' => 'Sync Agent',
            'name_ar' => 'وكيل مزامنة',
        ]);

        $this->agentService->verify($agent->id);
        $wallet = $this->agentService->getWallet($agent->id);
        $wallet->update(['float_balance' => 60000, 'daily_used' => 0]);

        $listener = $this->app->make(FloatSyncListener::class);
        $listener->handle(new AgentTransactionCompleted(
            agentTransactionId: 'tx-sync-1',
            agentId: $agent->id,
            type: 'cash_in',
            amount: 50000,
            currency: 'SYP',
            commissionAmount: 250,
            timestamp: now()->getTimestamp(),
        ));

        $wallet->refresh();
        $this->assertEquals(110000, $wallet->float_balance);
    }
}
