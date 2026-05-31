<?php

declare(strict_types=1);

namespace App\Modules\Fraud\Controllers;

use App\Modules\Fraud\Models\FraudDecision;
use App\Modules\Fraud\Models\FraudRule;
use App\Modules\Fraud\Services\FraudGuard;
use App\Modules\Fraud\Services\ScoringPipeline;
use App\Modules\Fraud\Services\VelocityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class FraudController extends Controller
{
    public function __construct(
        private readonly FraudGuard $fraudGuard,
        private readonly VelocityService $velocityService,
        private readonly ScoringPipeline $scoringPipeline,
    ) {}

    public function check(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'wallet_id' => 'required|string',
            'amount' => 'required|integer|min:1',
            'device_data' => 'sometimes|array',
            'kyc_tier' => 'sometimes|string|in:t0,t1,t2,t3',
        ]);

        $decision = $this->fraudGuard->preCheck(
            walletId: $validated['wallet_id'],
            amount: (int) $validated['amount'],
            deviceData: $validated['device_data'] ?? [],
            kycTier: $validated['kyc_tier'] ?? 't0',
        );

        return response()->json(['data' => $decision]);
    }

    public function monitor(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'wallet_id' => 'required|string',
            'transaction_id' => 'required|string',
            'amount' => 'required|integer|min:1',
            'device_data' => 'sometimes|array',
            'kyc_tier' => 'sometimes|string|in:t0,t1,t2,t3',
        ]);

        $this->fraudGuard->postMonitor(
            walletId: $validated['wallet_id'],
            transactionId: $validated['transaction_id'],
            amount: (int) $validated['amount'],
            deviceData: $validated['device_data'] ?? [],
            kycTier: $validated['kyc_tier'] ?? 't0',
        );

        return response()->json(['message' => 'ok']);
    }

    public function decisions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'wallet_id' => 'required|string',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $decisions = FraudDecision::where('wallet_id', $validated['wallet_id'])
            ->orderBy('created_at', 'desc')
            ->paginate((int) ($validated['per_page'] ?? 15));

        return response()->json(['data' => $decisions]);
    }

    public function rules(): JsonResponse
    {
        return response()->json(['data' => FraudRule::where('is_active', true)->orderBy('priority', 'desc')->get()]);
    }

    public function resolve(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'resolution' => 'required|string|in:confirmed_fraud,false_positive,overridden',
            'resolved_by' => 'required|string',
        ]);

        $decision = FraudDecision::findOrFail($id);
        $decision->update([
            'resolution' => $validated['resolution'],
            'resolved_by' => $validated['resolved_by'],
            'resolved_at' => now(),
        ]);

        return response()->json(['data' => $decision]);
    }
}
