<?php

declare(strict_types=1);

namespace App\Modules\Fx\Controllers;

use App\Modules\Fx\Services\ConversionService;
use App\Modules\Fx\Services\RateSyncService;
use App\Modules\Fx\Services\SpreadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class FxController extends Controller
{
    public function __construct(
        private readonly ConversionService $conversionService,
        private readonly RateSyncService $rateSyncService,
        private readonly SpreadService $spreadService,
    ) {}

    public function convert(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'wallet_id' => 'required|string',
            'amount' => 'required|integer|min:100',
            'from_currency' => 'required|string|in:SYP,USD',
            'to_currency' => 'required|string|in:SYP,USD|different:from_currency',
            'kyc_tier' => 'sometimes|string|in:t0,t1,t2,t3',
            'idempotency_key' => 'sometimes|string',
            'description' => 'sometimes|string',
            'description_ar' => 'sometimes|string',
        ]);

        $result = $this->conversionService->convert(
            walletId: $validated['wallet_id'],
            amount: (int) $validated['amount'],
            fromCurrency: $validated['from_currency'],
            toCurrency: $validated['to_currency'],
            kycTier: $validated['kyc_tier'] ?? 't0',
            idempotencyKey: $validated['idempotency_key'] ?? null,
            description: $validated['description'] ?? null,
            descriptionAr: $validated['description_ar'] ?? null,
        );

        return response()->json(['data' => $result], 201);
    }

    public function rate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_currency' => 'required|string|in:SYP,USD',
            'to_currency' => 'required|string|in:SYP,USD',
        ]);

        $rate = $this->conversionService->getRate($validated['from_currency'], $validated['to_currency']);
        return response()->json(['data' => $rate]);
    }

    public function updateRate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_currency' => 'required|string|in:SYP,USD',
            'to_currency' => 'required|string|in:SYP,USD',
            'buy_rate' => 'required|integer|min:1',
            'sell_rate' => 'required|integer|min:1',
            'spread_bps' => 'sometimes|integer|min:0',
            'ttl_minutes' => 'sometimes|integer|min:1|max:1440',
        ]);

        $rate = $this->rateSyncService->setManualRate(
            baseCurrency: $validated['from_currency'],
            quoteCurrency: $validated['to_currency'],
            buyRate: (int) $validated['buy_rate'],
            sellRate: (int) $validated['sell_rate'],
            spreadBps: (int) ($validated['spread_bps'] ?? 0),
        );

        return response()->json(['data' => $rate], 201);
    }

    public function spread(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|integer|min:1',
            'kyc_tier' => 'sometimes|string|in:t0,t1,t2,t3',
        ]);

        $bps = $this->spreadService->calculateSpreadBps(
            amount: (int) $validated['amount'],
            kycTier: $validated['kyc_tier'] ?? 't0',
        );

        return response()->json(['data' => ['spread_bps' => $bps]]);
    }

    public function history(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'wallet_id' => 'required|string',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        return response()->json([
            'data' => $this->conversionService->getHistory(
                $validated['wallet_id'],
                (int) ($validated['per_page'] ?? 15),
            ),
        ]);
    }
}
