<?php

declare(strict_types=1);

namespace Modules\FX\Controllers;

use App\Support\ApiResponse;
use Modules\FX\DTOs\CreateFxRateDto;
use Modules\FX\DTOs\GetQuoteDto;
use Modules\FX\DTOs\ExecuteConversionDto;
use Modules\FX\Http\Requests\CreateRateRequest;
use Modules\FX\Http\Requests\GetQuoteRequest;
use Modules\FX\Http\Requests\ExecuteConversionRequest;
use Modules\FX\Services\FxRateService;
use Modules\FX\Services\FxQuoteService;
use Modules\FX\Services\FxConversionService;
use Illuminate\Http\JsonResponse;

final class FxController
{
    use ApiResponse;

    public function __construct(
        private readonly FxRateService $rates,
        private readonly FxQuoteService $quotes,
        private readonly FxConversionService $conversions,
    ) {}

    public function rates(): JsonResponse
    {
        return $this->respond($this->rates->getAllActive());
    }

    public function rateHistory(string $base, string $quote): JsonResponse
    {
        try {
            return $this->respond($this->rates->getRateHistory($base, $quote));
        } catch (\Modules\FX\Exceptions\FxInvalidPairException $e) {
            return $this->respondError('FX_INVALID_PAIR', $e->getMessage(), null, 400);
        }
    }

    public function createRate(CreateRateRequest $request): JsonResponse
    {
        $dto = new CreateFxRateDto(
            baseCurrency: $request->input('base_currency'),
            quoteCurrency: $request->input('quote_currency'),
            midRate: (float) $request->input('mid_rate'),
            rateType: $request->input('rate_type'),
            source: $request->input('source'),
            spreadPct: $request->input('spread_pct') ? (float) $request->input('spread_pct') : null,
            bidRate: $request->input('bid_rate') ? (float) $request->input('bid_rate') : null,
            askRate: $request->input('ask_rate') ? (float) $request->input('ask_rate') : null,
            validTo: $request->input('valid_to'),
        );

        try {
            $rate = $this->rates->create($dto);
            return $this->respondCreated($rate, __('fx::messages.rate_created'));
        } catch (\Modules\FX\Exceptions\FxInvalidPairException $e) {
            return $this->respondError('FX_INVALID_PAIR', $e->getMessage(), null, 400);
        }
    }

    public function getQuote(GetQuoteRequest $request): JsonResponse
    {
        $dto = new GetQuoteDto(
            requestorId: $request->user()->id,
            requestorType: 'wallet',
            baseCurrency: $request->input('base_currency'),
            quoteCurrency: $request->input('quote_currency'),
            amount: (int) $request->input('amount'),
            rateType: $request->input('rate_type', 'cbs_official'),
            ttlSeconds: $request->input('ttl_seconds') ? (int) $request->input('ttl_seconds') : 60,
        );

        try {
            $quote = $this->quotes->generate($dto);
            return $this->respond($quote, __('fx::messages.quote_generated'));
        } catch (\Modules\FX\Exceptions\FxInvalidPairException $e) {
            return $this->respondError('FX_INVALID_PAIR', $e->getMessage(), null, 400);
        } catch (\Modules\FX\Exceptions\FxAmountBelowMinimumException $e) {
            return $this->respondError('FX_AMOUNT_BELOW_MINIMUM', $e->getMessage(), null, 422);
        } catch (\Modules\FX\Exceptions\FxRateUnavailableException $e) {
            return $this->respondError('FX_RATE_UNAVAILABLE', $e->getMessage(), null, 503);
        } catch (\Modules\FX\Exceptions\FxRateStaleException $e) {
            return $this->respondError('FX_RATE_STALE', $e->getMessage(), null, 503);
        }
    }

    public function executeConversion(ExecuteConversionRequest $request): JsonResponse
    {
        $dto = new ExecuteConversionDto(
            quoteId: $request->input('quote_id'),
            fromWalletId: $request->input('from_wallet_id'),
            toWalletId: $request->input('to_wallet_id'),
        );

        try {
            $conversion = $this->conversions->execute($dto);
            return $this->respond($conversion, __('fx::messages.conversion_completed'));
        } catch (\Modules\FX\Exceptions\FxRateExpiredException $e) {
            return $this->respondError('FX_RATE_EXPIRED', $e->getMessage(), null, 422);
        } catch (\Modules\FX\Exceptions\FxRateLockContentionException $e) {
            return $this->respondError('FX_RATE_LOCK_CONTENTION', $e->getMessage(), null, 409);
        } catch (\Modules\FX\Exceptions\FxAmountExceedsLimitException $e) {
            return $this->respondError('FX_AMOUNT_EXCEEDS_LIMIT', $e->getMessage(), null, 422);
        }
    }

    public function quoteHistory(): JsonResponse
    {
        $history = $this->quotes->getHistory(request()->user()->id, 'wallet');
        return $this->respond($history);
    }

    public function conversionHistory(string $walletId): JsonResponse
    {
        return $this->respond($this->conversions->getWalletConversions($walletId));
    }

    public function showConversion(string $id): JsonResponse
    {
        $conversion = $this->conversions->getConversion($id);
        if (!$conversion) {
            return $this->respondError('FX_NOT_FOUND', 'Conversion not found', null, 404);
        }
        return $this->respond($conversion);
    }
}
