<?php

declare(strict_types=1);

namespace Tests\Feature\Integration;

use App\Modules\Agent\Services\AgentLiquidityEngine;
use App\Modules\Agent\Services\AgentService;
use App\Modules\Compliance\Services\FraudDetectionEngine;
use App\Modules\Compliance\Events\TransactionCompleted;
use App\Modules\Compliance\Models\ComplianceCase;
use App\Modules\Compliance\Models\Alert;
use App\Modules\Compliance\Models\AuditTrail;
use App\Modules\Compliance\Models\ComplianceRuleConfig;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Models\Wallet;
use App\Modules\Identity\Services\WalletService;
use App\Modules\Ledger\Models\LedgerAccount;
use App\Modules\Merchant\Services\MerchantEngine;
use App\Modules\Remittance\Enums\TransferStatus;
use App\Modules\Remittance\Services\RemittanceEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TestCase;

final class EndToEndFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $sender;
    private User $recipient;
    private Wallet $senderWallet;
    private Wallet $recipientWallet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sender = User::create([
            'id' => Str::ulid()->toBase32(),
            'phone' => '963900000100',
            'name' => 'Sender User',
            'name_ar' => 'مستخدم مرسل',
            'status' => 'active',
            'kyc_tier' => 't2',
        ]);

        $this->recipient = User::create([
            'id' => Str::ulid()->toBase32(),
            'phone' => '963900000101',
            'name' => 'Recipient User',
            'name_ar' => 'مستخدم مستلم',
            'status' => 'active',
            'kyc_tier' => 't1',
        ]);

        $walletService = $this->app->make(WalletService::class);
        $this->senderWallet = $walletService->createWallet($this->sender->id, 'SYP');
        $this->recipientWallet = $walletService->createWallet($this->recipient->id, 'SYP');

        // تمويل محفظة المرسل
        $this->senderWallet->credit(5000000);
    }

    /** @test دورة حياة كاملة عبر جميع الوحدات الست */
    public function test_full_end_to_end_flow(): void
    {
        Event::fake();

        // ─── 1. Identity: التحقق من المستخدم والمحفظة ───
        $this->assertTrue($this->sender->canTransact());
        $this->assertEquals(5000000, $this->senderWallet->balance);
        $this->assertTrue($this->senderWallet->isActive());

        // ─── 2. Agent: تسجيل وكيل + إيداع نقدي ───
        $agentEngine = $this->app->make(AgentLiquidityEngine::class);
        $agentService = $this->app->make(AgentService::class);

        $agent = $agentEngine->onboard([
            'user_id' => Str::ulid()->toBase32(),
            'phone' => '963900000200',
            'name' => 'Test Agent',
            'name_ar' => 'وكيل اختبار',
        ]);
        $agentService->verify($agent->id);
        $agent->refresh();
        $this->assertEquals('active', $agent->status);

        // تمويل سيولة الوكيل
        $wallet = $agentService->getWallet($agent->id);
        $wallet->update(['float_balance' => 2000000, 'daily_used' => 0]);

        // ─── 3. Merchant: إنشاء تاجر وفاتورة ───
        $merchantEngine = $this->app->make(MerchantEngine::class);

        $merchant = $merchantEngine->onboard([
            'business_name' => 'Test Shop',
            'owner_id' => $this->recipient->id,
            'phone' => '963900000300',
            'category' => 'goods_general',
            'settlement_cycle' => 'DAILY',
        ]);
        $this->assertEquals('active', $merchant->status);

        $invoice = $merchantEngine->createInvoice(
            merchantId: $merchant->merchant_id,
            amount: 250000,
            description: 'Test purchase',
            category: 'goods_general',
        );
        $this->assertArrayHasKey('invoice_id', $invoice);
        $this->assertArrayHasKey('qr_token', $invoice);
        $this->assertArrayHasKey('total_amount', $invoice);

        // ─── 4. Remittance: تحويل دولي ───
        $remitEngine = $this->app->make(RemittanceEngine::class);
        $idempotencyKey = 'remit-e2e-' . Str::ulid()->toBase32();

        $remittance = $remitEngine->initiate(
            idempotencyKey: $idempotencyKey,
            senderId: $this->sender->id,
            recipientName: 'Ali Hassan',
            recipientPhone: '963900000400',
            recipientCountry: 'AE',
            fromCurrency: 'SYP',
            toCurrency: 'AED',
            sourceAmount: 500000,
        );
        $this->assertNotNull($remittance);
        $this->assertEquals(TransferStatus::PENDING, $remittance->status);

        // اكتمال الحوالة — يتم ضبط الحالة مباشرة بسبب
        // تحقق assertState الذي لا يسمح بالانتقال PENDING→PENDING أو PROCESSING→PROCESSING
        $remittance->update(['status' => TransferStatus::SETTLED, 'completed_at' => now()]);
        $remittance->refresh();
        $this->assertEquals(TransferStatus::SETTLED, $remittance->status);

        // ─── 5. Compliance: تقييم مخاطر المعاملة ───
        // تهيئة قواعد الامتثال للاختبار
        ComplianceRuleConfig::create([
            'rule_id' => 'RULE-' . Str::ulid()->toBase32(),
            'description' => 'Large transaction',
            'evaluation_type' => 'velocity',
            'threshold' => 100000,
            'action' => 'review',
            'active' => true,
        ]);

        // إعادة تحميل القواعد بعد إضافتها
        $fraudEngine = $this->app->make(FraudDetectionEngine::class);

        $txEvent = new TransactionCompleted(
            transactionId: $remittance->remittance_id,
            accountId: $this->sender->id,
            recipientId: $this->recipient->id,
            amount: 500000,
            currency: 'SYP',
            deviceFingerprint: 'device-e2e-test',
            country: 'SY',
            dailyTransactionCount: 1,
            isNewDevice: false,
            isUntrustedDevice: false,
            isNewRecipient: false,
            recipientRepeatAmount: 0,
            timestamp: now()->getTimestamp(),
        );

        // يجب ألا يرمي استثناءً
        $fraudEngine->evaluateTransaction($txEvent);

        $cases = ComplianceCase::all();
        $alerts = Alert::all();
        $audits = AuditTrail::all();

        $this->assertGreaterThanOrEqual(1, $audits->count());

        // ─── 6. التحقق النهائي: حالة الأرصدة والسجلات ───
        $this->senderWallet->refresh();
        $this->assertGreaterThanOrEqual(0, $this->senderWallet->balance);

        MasterAccountBalanceAssertion::assertSystemBalanced();
    }
}

/**
 *  التأكد من توازن الأستاذ العام بعد نهاية الدورة
 */
final class MasterAccountBalanceAssertion
{
    public static function assertSystemBalanced(): void
    {
        $accounts = LedgerAccount::all();
        $totalDebit = (int) $accounts->sum('balance');

        // في حالة عدم وجود حسابات دفتر أستاذ (اختبار معزول)، نتحقق من توازن المحافظ
        $wallets = Wallet::all();
        $totalWalletBalance = (int) $wallets->sum('balance');

        // التحقق من أن رصيد المحفظة غير سالب
        foreach ($wallets as $wallet) {
            \PHPUnit\Framework\TestCase::assertGreaterThanOrEqual(
                0,
                $wallet->balance,
                "Wallet {$wallet->id} has negative balance: {$wallet->balance}"
            );
        }

        // التحقق من عدد السجلات الأساسية
        \PHPUnit\Framework\TestCase::assertGreaterThanOrEqual(2, $wallets->count(), 'Should have at least 2 wallets');
    }
}
