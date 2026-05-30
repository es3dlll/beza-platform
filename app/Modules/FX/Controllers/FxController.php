<?php

declare(strict_types=1);

namespace Modules\FX\Controllers;

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
    public function __construct(
        private readonly FxRateService $rates,
        private readonly FxQuoteService $quotes,
        private readonly FxConversionService $conversions,
    ) {}

    public function rates(): JsonResponse
    {
        return response()->json(['data' => $this->rates->getAllActive()]);
    }

    public function rateHistory(string $base, string $quote): JsonResponse
    {
        try {
            return response()->json(['data' => $this->rates->getRateHistory($base, $quote)]);
        } catch (\Modules\FX\Exceptions\FxInvalidPairException $e) {
            return response()->json([
                'error' => ['code' => 'FX_INVALID_PAIR', 'message' => $e->getMessage()],
            ], 400);
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
            return response()->json(['data' => $rate, 'message' => __('fx::messages.rate_created')], 201);
        } catch (\Modules\FX\Exceptions\FxInvalidPairException $e) {
            return response()->json([
                'error' => ['code' => 'FX_INVALID_PAIR', 'message' => $e->getMessage()],
            ], 400);
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
            return response()->json(['data' => $quote, 'message' => __('fx::messages.quote_generated')]);
        } catch (\Modules\FX\Exceptions\FxInvalidPairException $e) {
            return response()->json(['error' => ['code' => 'FX_INVALID_PAIR', 'message' => $e->getMessage()]], 400);
        } catch (\Modules\FX\Exceptions\FxAmountBelowMinimumException $e) {
            return response()->json(['error' => ['code' => 'FX_AMOUNT_BELOW_MINIMUM', 'message' => $e->getMessage()]], 422);
        } catch (\Modules\FX\Exceptions\FxRateUnavailableException $e) {
            return response()->json(['error' => ['code' => 'FX_RATE_UNAVAILABLE', 'message' => $e->getMessage()]], 503);
        } catch (\Modules\FX\Exceptions\FxRateStaleException $e) {
            return response()->json(['error' => ['code' => 'FX_RATE_STALE', 'message' => $e->getMessage()]], 503);
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
            return response()->json(['data' => $conversion, 'message' => __('fx::messages.conversion_completed')]);
        } catch (\Modules\FX\Exceptions\FxRateExpiredException $e) {
            return response()->json(['error' => ['code' => 'FX_RATE_EXPIRED', 'message' => $e->getMessage()]], 422);
        } catch (\Modules\FX\Exceptions\FxRateLockContentionException $e) {
            return response()->json(['error' => ['code' => 'FX_RATE_LOCK_CONTENTION', 'message' => $e->getMessage()]], 409);
        } catch (\Modules\FX\Exceptions\FxAmountExceedsLimitException $e) {
            return response()->json(['error' => ['code' => 'FX_AMOUNT_EXCEEDS_LIMIT', 'message' => $e->getMessage()]], 422);
        }
    }

    public function quoteHistory(): JsonResponse
    {
        $history = $this->quotes->getHistory(request()->user()->id, 'wallet');
        return response()->json(['data' => $history]);
    }

    public function conversionHistory(string $walletId): JsonResponse
    {
        return response()->json(['data' => $this->conversions->getWalletConversions($walletId)]);
    }

    public function showConversion(string $id): JsonResponse
    {
        $conversion = $this->conversions->getConversion($id);
        if (!$conversion) {
            return response()->json(['error' => ['code' => 'FX_NOT_FOUND', 'message' => 'Conversion not found']], 404);
        }
        return response()->json(['data' => $conversion]);
    }
}
