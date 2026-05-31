<?php

declare(strict_types=1);

namespace App\Modules\Fx\Services;

use App\Domain\Enums\Currency;
use App\Domain\ValueObjects\Money;
use App\Modules\FinancialCore\Services\Engines\PostingEngine;
use App\Modules\FinancialCore\Services\IdempotencyService;
use App\Modules\Fx\Events\FxConversionCompleted;
use App\Modules\Fx\Events\FxRateLocked;
use App\Modules\Fx\Models\FxTransaction;
use App\Modules\Fx\Exceptions\CurrencyMismatchException;
use Illuminate\Support\Facades\DB;

final class ConversionService
{
    public function __construct(
        private readonly PostingEngine $postingEngine,
        private readonly IdempotencyService $idempotencyService,
        private readonly RateLockService $rateLockService,
        private readonly RateSyncService $rateSyncService,
        private readonly SpreadService $spreadService,
    ) {}

    public function convert(
        string $walletId,
        int $amount,
        string $fromCurrency,
        string $toCurrency,
        string $kycTier = 't0',
        ?string $idempotencyKey = null,
        ?string $description = null,
        ?string $descriptionAr = null,
    ): array {
        if ($fromCurrency === $toCurrency) {
            throw new CurrencyMismatchException("Cannot convert {$fromCurrency} to itself");
        }

        if ($idempotencyKey !== null) {
            $cached = $this->idempotencyService->checkOrCreate($idempotencyKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        $rate = $this->rateSyncService->getBestRate($fromCurrency, $toCurrency);
        $spreadBps = $this->spreadService->calculateSpreadBps($amount, $kycTier);

        $effectiveRate = $fromCurrency === 'SYP' ? $rate->sell_rate : $rate->buy_rate;
        $convertedAmount = $rate->convert($amount, $fromCurrency !== 'SYP');

        $hold = $this->rateLockService->lockRate(
            walletId: $walletId,
            baseCurrency: $fromCurrency,
            quoteCurrency: $toCurrency,
            amount: $amount,
            rate: $effectiveRate,
            convertedAmount: $convertedAmount,
            spreadBps: $spreadBps,
            idempotencyKey: $idempotencyKey,
        );

        event(new FxRateLocked(
            walletId: $walletId,
            baseCurrency: $fromCurrency,
            quoteCurrency: $toCurrency,
            amount: $amount,
            lockedRate: $effectiveRate,
            spreadBps: $spreadBps,
            expiresAt: $hold->expires_at->toIso8601String(),
        ));

        $result = DB::transaction(function () use (
            $walletId, $amount, $fromCurrency, $toCurrency, $convertedAmount,
            $effectiveRate, $spreadBps, $rate, $hold, $idempotencyKey,
            $description, $descriptionAr
        ) {
            $cfeResult = $this->postingEngine->execute(
                fromWalletId: $walletId,
                toWalletId: $walletId,
                amount: new Money($amount, Currency::from($fromCurrency)),
                description: $description ?? "FX conversion {$fromCurrency}→{$toCurrency}",
                descriptionAr: $descriptionAr ?? "تحويل عملة {$fromCurrency}→{$toCurrency}",
                currency: $fromCurrency,
                idempotencyKey: $idempotencyKey,
            );

            $fxTx = FxTransaction::create([
                'wallet_id' => $walletId,
                'type' => 'conversion',
                'status' => 'completed',
                'base_currency' => $fromCurrency,
                'quote_currency' => $toCurrency,
                'debit_amount' => $amount,
                'credit_amount' => $convertedAmount,
                'rate_used' => $effectiveRate,
                'spread_bps_applied' => $spreadBps,
                'rate_source_id' => $rate->rate_source_id,
                'fx_hold_id' => $hold->id,
                'cfe_transaction_id' => $cfeResult['transaction']->id,
                'idempotency_key' => $idempotencyKey,
                'description' => $description,
                'description_ar' => $descriptionAr,
            ]);

            $hold->consume();

            if ($idempotencyKey !== null) {
                $this->idempotencyService->complete($idempotencyKey, $fxTx->id, $fxTx->toArray());
            }

            return ['fx_transaction' => $fxTx, 'cfe_result' => $cfeResult, 'hold' => $hold];
        });

        event(new FxConversionCompleted(
            fxTransactionId: $result['fx_transaction']->id,
            walletId: $walletId,
            baseCurrency: $fromCurrency,
            quoteCurrency: $toCurrency,
            debitAmount: $amount,
            creditAmount: $convertedAmount,
            rateUsed: $effectiveRate,
            spreadBps: $spreadBps,
            cfeTransactionId: $result['cfe_result']['transaction']->id,
        ));

        return $result;
    }

    public function getRate(string $fromCurrency, string $toCurrency): array
    {
        $rate = $this->rateSyncService->getBestRate($fromCurrency, $toCurrency);
        return $rate->toArray();
    }

    public function getHistory(string $walletId, int $perPage = 15): \Illuminate\Pagination\LengthAwarePaginator
    {
        return FxTransaction::where('wallet_id', $walletId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}
