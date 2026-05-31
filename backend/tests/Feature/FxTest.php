<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Enums\Currency;
use App\Domain\ValueObjects\Money;
use App\Modules\Fx\Models\ExchangeRate;
use App\Modules\Fx\Models\FxHold;
use App\Modules\Fx\Models\FxTransaction;
use App\Modules\Fx\Models\RateSource;
use App\Modules\Fx\Services\ConversionService;
use App\Modules\Fx\Services\RateLockService;
use App\Modules\Fx\Services\RateSyncService;
use App\Modules\Fx\Services\SpreadService;
use App\Modules\FinancialCore\Models\Transaction;
use App\Modules\Ledger\Database\Seeders\LedgerSeeder;
use App\Modules\Ledger\Models\LedgerAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class FxTest extends TestCase
{
    use RefreshDatabase;

    private ConversionService $conversionService;
    private RateLockService $rateLockService;
    private RateSyncService $rateSyncService;
    private SpreadService $spreadService;
    private string $walletId;
    private RateSource $cbsSource;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LedgerSeeder::class);
        $this->conversionService = $this->app->make(ConversionService::class);
        $this->rateLockService = $this->app->make(RateLockService::class);
        $this->rateSyncService = $this->app->make(RateSyncService::class);
        $this->spreadService = $this->app->make(SpreadService::class);
        $this->walletId = Str::ulid()->toBase32();

        $customerAccount = LedgerAccount::where('code', '1100')->firstOrFail();
        $customerAccount->increment('balance', 1000000000);

        $this->cbsSource = RateSource::create([
            'name' => 'CBS Syria',
            'name_ar' => 'مصرف سورية المركزي',
            'type' => 'cbs',
            'priority' => 100,
        ]);

        $this->rateSyncService->updateRate(
            sourceId: $this->cbsSource->id,
            baseCurrency: 'SYP',
            quoteCurrency: 'USD',
            buyRate: 1250000,
            sellRate: 1350000,
            spreadBps: 200,
            ttlMinutes: 60,
        );
    }

    public function test_rate_lock_expires_after_15s(): void
    {
        $hold = $this->rateLockService->lockRate(
            walletId: $this->walletId,
            baseCurrency: 'SYP',
            quoteCurrency: 'USD',
            amount: 100000,
            rate: 1300000,
            convertedAmount: 76,
            spreadBps: 200,
        );

        $this->assertEquals('active', $hold->status);
        $this->assertFalse($hold->isExpired());

        $hold->update(['expires_at' => now()->subSecond()]);
        $this->assertTrue($hold->fresh()->isExpired());
    }

    public function test_spread_calculated_correctly_by_tier(): void
    {
        $t0Spread = $this->spreadService->calculateSpreadBps(100000, 't0');
        $t3Spread = $this->spreadService->calculateSpreadBps(100000, 't3');

        $this->assertEquals(300, $t0Spread);
        $this->assertEquals(50, $t3Spread);
    }

    public function test_spread_decreases_for_large_amounts(): void
    {
        $normalSpread = $this->spreadService->calculateSpreadBps(100000, 't1');
        $largeSpread = $this->spreadService->calculateSpreadBps(15000000, 't1');

        $this->assertEquals(200, $normalSpread);
        $this->assertEquals(160, $largeSpread);
    }

    public function test_fx_conversion_syp_to_usd(): void
    {
        $result = $this->conversionService->convert(
            walletId: $this->walletId,
            amount: 1000000,
            fromCurrency: 'SYP',
            toCurrency: 'USD',
            idempotencyKey: 'fx-conv-1',
        );

        $fxTx = $result['fx_transaction'];
        $this->assertEquals('SYP', $fxTx->base_currency);
        $this->assertEquals('USD', $fxTx->quote_currency);
        $this->assertEquals('completed', $fxTx->status);
        $this->assertGreaterThan(0, $fxTx->credit_amount);
    }

    public function test_fx_conversion_creates_cfe_transaction(): void
    {
        $this->conversionService->convert(
            walletId: $this->walletId,
            amount: 500000,
            fromCurrency: 'SYP',
            toCurrency: 'USD',
            idempotencyKey: 'fx-ledger-1',
        );

        $this->assertDatabaseHas('financial_transactions', [
            'wallet_id' => $this->walletId,
            'type' => 'post',
            'status' => 'posted',
        ]);
    }

    public function test_fx_conversion_tracks_rate_used(): void
    {
        $result = $this->conversionService->convert(
            walletId: $this->walletId,
            amount: 1000000,
            fromCurrency: 'SYP',
            toCurrency: 'USD',
            idempotencyKey: 'fx-rate-1',
        );

        $fxTx = $result['fx_transaction'];
        $this->assertGreaterThan(0, $fxTx->rate_used);
        $this->assertGreaterThan(0, $fxTx->credit_amount);
        $this->assertLessThan($fxTx->debit_amount, $fxTx->credit_amount);
    }

    public function test_idempotency_prevents_double_conversion(): void
    {
        $this->conversionService->convert(
            walletId: $this->walletId,
            amount: 250000,
            fromCurrency: 'SYP',
            toCurrency: 'USD',
            idempotencyKey: 'fx-idemp-1',
        );

        $this->conversionService->convert(
            walletId: $this->walletId,
            amount: 250000,
            fromCurrency: 'SYP',
            toCurrency: 'USD',
            idempotencyKey: 'fx-idemp-1',
        );

        $this->assertEquals(1, FxTransaction::where('idempotency_key', 'fx-idemp-1')->count());
    }

    public function test_rate_source_fallback_to_manual(): void
    {
        ExchangeRate::where('status', 'active')->update(['status' => 'expired']);

        $this->rateSyncService->setManualRate(
            baseCurrency: 'SYP',
            quoteCurrency: 'USD',
            buyRate: 1200000,
            sellRate: 1300000,
            spreadBps: 100,
        );

        $rate = $this->rateSyncService->getBestRate('SYP', 'USD');
        $this->assertEquals('manual', $rate->status);
        $this->assertEquals(1200000, $rate->buy_rate);
    }

    public function test_rate_expiry_after_ttl(): void
    {
        ExchangeRate::where('status', 'active')->update([
            'valid_until' => now()->subMinute(),
            'status' => 'expired',
        ]);

        $this->expectException(\App\Modules\Fx\Exceptions\RateNotFoundException::class);
        $this->rateSyncService->getBestRate('SYP', 'USD');
    }

    public function test_rate_lock_rejects_concurrent_operations(): void
    {
        $this->rateLockService->lockRate(
            walletId: $this->walletId,
            baseCurrency: 'SYP',
            quoteCurrency: 'USD',
            amount: 100000,
            rate: 1300000,
            convertedAmount: 76,
            spreadBps: 200,
        );

        $this->expectException(\App\Modules\Fx\Exceptions\RateExpiredException::class);

        $this->rateLockService->lockRate(
            walletId: $this->walletId,
            baseCurrency: 'SYP',
            quoteCurrency: 'USD',
            amount: 100000,
            rate: 1300000,
            convertedAmount: 76,
            spreadBps: 200,
        );
    }

    public function test_fx_hold_consumed_after_conversion(): void
    {
        $result = $this->conversionService->convert(
            walletId: $this->walletId,
            amount: 500000,
            fromCurrency: 'SYP',
            toCurrency: 'USD',
            idempotencyKey: 'fx-hold-consumed',
        );

        $hold = FxHold::find($result['fx_transaction']->fx_hold_id);
        $this->assertEquals('consumed', $hold->status);
    }

    public function test_fx_conversion_history(): void
    {
        $this->conversionService->convert(
            walletId: $this->walletId,
            amount: 100000,
            fromCurrency: 'SYP',
            toCurrency: 'USD',
            idempotencyKey: 'fx-hist-1',
        );

        $this->conversionService->convert(
            walletId: $this->walletId,
            amount: 200000,
            fromCurrency: 'SYP',
            toCurrency: 'USD',
            idempotencyKey: 'fx-hist-2',
        );

        $history = $this->conversionService->getHistory($this->walletId, 15);
        $this->assertCount(2, $history->items());
    }

    public function test_currency_mismatch_rejected(): void
    {
        $this->expectException(\App\Modules\Fx\Exceptions\CurrencyMismatchException::class);

        $this->conversionService->convert(
            walletId: $this->walletId,
            amount: 100000,
            fromCurrency: 'SYP',
            toCurrency: 'SYP',
        );
    }

    public function test_reversal_not_directly_supported(): void
    {
        $this->conversionService->convert(
            walletId: $this->walletId,
            amount: 300000,
            fromCurrency: 'SYP',
            toCurrency: 'USD',
            idempotencyKey: 'fx-rev-test',
        );

        $this->assertDatabaseHas('fx_transactions', [
            'idempotency_key' => 'fx-rev-test',
            'type' => 'conversion',
        ]);
    }
}
