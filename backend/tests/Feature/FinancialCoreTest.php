<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Enums\Currency;
use App\Domain\ValueObjects\Money;
use App\Modules\FinancialCore\Models\FeeRule;
use App\Modules\FinancialCore\Models\Transaction;
use App\Modules\FinancialCore\Services\Engines\FeeEngine;
use App\Modules\FinancialCore\Services\Engines\HoldEngine;
use App\Modules\FinancialCore\Services\Engines\PostingEngine;
use App\Modules\FinancialCore\Services\Engines\ReversalEngine;
use App\Modules\FinancialCore\Services\IdempotencyService;
use App\Modules\FinancialCore\Services\TransactionService;
use App\Modules\Ledger\Database\Seeders\LedgerSeeder;
use App\Modules\Ledger\Models\LedgerAccount;
use App\Modules\Ledger\Services\AccountService;
use App\Modules\Ledger\Services\JournalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class FinancialCoreTest extends TestCase
{
    use RefreshDatabase;

    private TransactionService $transactionService;
    private HoldEngine $holdEngine;
    private PostingEngine $postingEngine;
    private FeeEngine $feeEngine;
    private ReversalEngine $reversalEngine;
    private IdempotencyService $idempotencyService;
    private AccountService $accountService;
    private JournalService $journalService;
    private string $fromWalletId;
    private string $toWalletId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LedgerSeeder::class);
        $this->holdEngine = $this->app->make(HoldEngine::class);
        $this->postingEngine = $this->app->make(PostingEngine::class);
        $this->feeEngine = $this->app->make(FeeEngine::class);
        $this->reversalEngine = $this->app->make(ReversalEngine::class);
        $this->idempotencyService = $this->app->make(IdempotencyService::class);
        $this->transactionService = $this->app->make(TransactionService::class);
        $this->accountService = $this->app->make(AccountService::class);
        $this->journalService = $this->app->make(JournalService::class);
        $this->fromWalletId = Str::ulid()->toBase32();
        $this->toWalletId = Str::ulid()->toBase32();
    }

    public function test_transfer_posts_small_amount(): void
    {
        $result = $this->transactionService->transfer(
            fromWalletId: $this->fromWalletId,
            toWalletId: $this->toWalletId,
            amount: 50000,
            idempotencyKey: 'small-transfer-1',
        );

        $this->assertEquals('post', $result['transaction']->type);
        $this->assertEquals('posted', $result['transaction']->status);
        $this->assertEquals(50000, $result['transaction']->amount);
    }

    public function test_large_amount_creates_hold(): void
    {
        $customerAccount = LedgerAccount::where('code', '1100')->firstOrFail();
        $customerAccount->increment('balance', 500000);

        $result = $this->transactionService->transfer(
            fromWalletId: $this->fromWalletId,
            toWalletId: $this->toWalletId,
            amount: 200000,
            idempotencyKey: 'hold-key-1',
        );

        $this->assertEquals('hold', $result['transaction']->type);
        $this->assertEquals('held', $result['transaction']->status);
        $this->assertEquals(200000, $result['transaction']->amount);
    }

    public function test_hold_capture_release_flow(): void
    {
        $customerAccount = LedgerAccount::where('code', '1100')->firstOrFail();
        $customerAccount->increment('balance', 500000);

        $money = Money::fromInt(100000, Currency::SYP);
        $hold = $this->holdEngine->placeHold(
            walletId: $this->fromWalletId,
            amount: $money,
            description: 'Test hold',
            descriptionAr: 'حجز اختبار',
            idempotencyKey: 'hold-flow-1',
        );

        $this->assertEquals('held', $hold['transaction']->status);

        $captured = $this->holdEngine->captureHold($hold['transaction']->id);
        $this->assertEquals('posted', $captured->status);
    }

    public function test_hold_release(): void
    {
        $customerAccount = LedgerAccount::where('code', '1100')->firstOrFail();
        $customerAccount->increment('balance', 500000);

        $money = Money::fromInt(100000, Currency::SYP);
        $hold = $this->holdEngine->placeHold(
            walletId: $this->fromWalletId,
            amount: $money,
            description: 'Hold to release',
            descriptionAr: 'حجز للإلغاء',
            idempotencyKey: 'hold-release-1',
        );

        $released = $this->holdEngine->releaseHold($hold['transaction']->id);
        $this->assertEquals('reversed', $released['transaction']->status);
    }

    public function test_deposit(): void
    {
        $result = $this->transactionService->deposit(
            walletId: $this->fromWalletId,
            amount: 100000,
            idempotencyKey: 'deposit-1',
        );

        $this->assertEquals('posted', $result['transaction']->status);
        $this->assertEquals(100000, $result['transaction']->amount);
    }

    public function test_withdraw(): void
    {
        $result = $this->transactionService->withdraw(
            walletId: $this->fromWalletId,
            amount: 50000,
            idempotencyKey: 'withdraw-1',
        );

        $this->assertEquals('posted', $result['transaction']->status);
        $this->assertEquals(50000, $result['transaction']->amount);
    }

    public function test_reverse_posted_transaction(): void
    {
        $customerAccount = LedgerAccount::where('code', '1100')->firstOrFail();
        $customerAccount->increment('balance', 500000);

        $result = $this->postingEngine->execute(
            fromWalletId: $this->fromWalletId,
            toWalletId: $this->toWalletId,
            amount: Money::fromInt(100000, Currency::SYP),
            description: 'To reverse',
            descriptionAr: 'للإلغاء',
            idempotencyKey: 'post-rev-1',
        );

        $reversal = $this->reversalEngine->reverse(
            originalTransactionId: $result['transaction']->id,
            reason: 'Customer request',
            reasonAr: 'طلب الزبون',
            idempotencyKey: 'rev-1',
        );

        $this->assertEquals('reversal', $reversal['reversal']->type);
        $this->assertEquals('posted', $reversal['reversal']->status);
        $this->assertEquals($result['transaction']->id, $reversal['reversal']->reversal_of);
    }

    public function test_idempotency_returns_same_result(): void
    {
        $first = $this->transactionService->deposit(
            walletId: $this->fromWalletId,
            amount: 75000,
            idempotencyKey: 'idemp-1',
        );

        $second = $this->transactionService->deposit(
            walletId: $this->fromWalletId,
            amount: 75000,
            idempotencyKey: 'idemp-1',
        );

        $this->assertEquals(1, Transaction::where('idempotency_key', 'idemp-1')->count());
    }

    public function test_idempotency_detects_processing(): void
    {
        $this->idempotencyService->checkOrCreate('processing-key-1');
        $this->assertTrue($this->idempotencyService->isProcessing('processing-key-1'));
    }

    public function test_fee_rule_flat_calculation(): void
    {
        $rule = FeeRule::create([
            'name' => 'Flat Fee',
            'name_ar' => 'رسوم ثابتة',
            'type' => 'flat',
            'value' => 5000,
            'account_code' => '4100',
        ]);

        $fee = $rule->calculateFee(new Money(100000, Currency::SYP));
        $this->assertEquals(5000, $fee->amount());
    }

    public function test_fee_rule_percentage_calculation(): void
    {
        $rule = FeeRule::create([
            'name' => 'Percentage Fee',
            'name_ar' => 'رسوم نسبة',
            'type' => 'percentage',
            'value' => 100,
            'account_code' => '4100',
        ]);

        $fee = $rule->calculateFee(new Money(100000, Currency::SYP));
        $this->assertEquals(1000, $fee->amount());
    }

    public function test_fee_rule_with_cap(): void
    {
        $rule = FeeRule::create([
            'name' => 'Percentage With Cap',
            'name_ar' => 'نسبة مع حد أقصى',
            'type' => 'percentage',
            'value' => 500,
            'cap_amount' => 20000,
            'account_code' => '4100',
        ]);

        $fee = $rule->calculateFee(new Money(10000000, Currency::SYP));
        $this->assertEquals(20000, $fee->amount());
    }

    public function test_fee_rule_with_min(): void
    {
        $rule = FeeRule::create([
            'name' => 'Percentage With Min',
            'name_ar' => 'نسبة مع حد أدنى',
            'type' => 'percentage',
            'value' => 50,
            'min_amount' => 3000,
            'account_code' => '4100',
        ]);

        $fee = $rule->calculateFee(new Money(10000, Currency::SYP));
        $this->assertEquals(3000, $fee->amount());
    }

    public function test_post_multiline_entry_to_ledger(): void
    {
        $customerAccount = LedgerAccount::where('code', '1100')->firstOrFail();
        $revenueAccount = LedgerAccount::where('code', '4100')->firstOrFail();
        $customerAccount->increment('balance', 500000);

        $entry = $this->journalService->postEntry(
            'multi-line-test-tx',
            [['account_id' => $customerAccount->id, 'amount' => 50000]],
            [['account_id' => $revenueAccount->id, 'amount' => 50000]],
            'Multi-line test',
            'اختبار متعدد',
        );

        $this->assertNotNull($entry->id);
        $this->assertNotNull($entry->hash);
        $this->assertEquals(2, $entry->lines()->count());
    }

    public function test_transaction_history(): void
    {
        Transaction::factory()->count(5)->create([
            'wallet_id' => $this->fromWalletId,
        ]);

        $transactions = $this->transactionService->getWalletTransactions($this->fromWalletId, 15);
        $this->assertCount(5, $transactions->items());
    }

    public function test_show_single_transaction(): void
    {
        $tx = Transaction::factory()->create();
        $found = $this->transactionService->getTransaction($tx->id);
        $this->assertEquals($tx->id, $found->id);
    }
}
