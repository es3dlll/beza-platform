<?php

declare(strict_types=1);

namespace App\Modules\Agent\Controllers;

use App\Modules\Agent\Models\Agent;
use App\Modules\Agent\Models\AgentTransaction;
use App\Modules\Agent\Services\AgentLiquidityEngine;
use App\Modules\Agent\Services\AgentService;
use App\Modules\Agent\Services\CashInOutService;
use App\Modules\Agent\Services\SettlementService;
use App\Modules\Agent\ValueObjects\CommissionTier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class AgentController extends Controller
{
    public function __construct(
        private readonly AgentService $agentService,
        private readonly CashInOutService $cashInOutService,
        private readonly SettlementService $settlementService,
        private readonly AgentLiquidityEngine $engine,
    ) {}

    public function onboard(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|string',
            'phone' => 'required|string|unique:agents,phone',
            'name' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
            'id_type' => 'sometimes|string|in:national_id,passport,residency',
            'id_number' => 'sometimes|string',
            'gps_lat' => 'sometimes|numeric|between:-90,90',
            'gps_lng' => 'sometimes|numeric|between:-180,180',
            'address' => 'sometimes|string',
            'address_ar' => 'sometimes|string',
        ]);

        $agent = $this->engine->onboard($validated);
        return response()->json(['data' => $agent], 201);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(['data' => $this->agentService->getAgent($id)]);
    }

    public function showFloat(string $id): JsonResponse
    {
        $float = $this->engine->getFloatStatus($id);
        return response()->json(['data' => $float]);
    }

    public function adjustFloat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'agent_id' => 'required|string|exists:agents,id',
            'adjustment' => 'required|integer',
            'reason' => 'required|string|max:500',
        ]);

        $wallet = $this->agentService->getWallet($validated['agent_id']);
        $newBalance = $wallet->float_balance + $validated['adjustment'];

        if ($newBalance < 0) {
            return response()->json(['error' => 'Adjustment would result in negative balance'], 422);
        }

        $wallet->update(['float_balance' => $newBalance]);

        $this->engine->processTransactionCompletion(
            agentId: $validated['agent_id'],
            type: 'FLOAT_TRANSFER',
            amount: abs($validated['adjustment']),
        );

        return response()->json([
            'data' => [
                'previous_balance' => $wallet->float_balance - $validated['adjustment'],
                'new_balance' => $newBalance,
                'adjustment' => $validated['adjustment'],
                'reason' => $validated['reason'],
            ],
        ]);
    }

    public function commissions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'agent_id' => 'required|string|exists:agents,id',
        ]);

        $agent = $this->agentService->getAgent($validated['agent_id']);
        $tier = CommissionTier::fromString($agent->commission_tier ?? 'Bronze');

        $transactions = AgentTransaction::where('agent_id', $validated['agent_id'])
            ->where('commission_amount', '>', 0)
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        $totalCommissions = AgentTransaction::where('agent_id', $validated['agent_id'])
            ->sum('commission_amount');

        return response()->json([
            'data' => [
                'tier' => $tier->tier(),
                'rates' => [
                    'cash_in_bps' => $tier->cashInBps(),
                    'cash_out_bps' => $tier->cashOutBps(),
                    'transfer_bps' => $tier->transferBps(),
                    'daily_cap' => $tier->dailyCap(),
                ],
                'total_commissions' => $totalCommissions,
                'history' => $transactions,
            ],
        ]);
    }

    public function settle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'agent_id' => 'required|string|exists:agents,id',
            'settlement_date' => 'sometimes|date',
        ]);

        $date = $validated['settlement_date'] ?? now()->toDateString();
        $this->engine->triggerSettlement($validated['agent_id'], $date);

        $settlements = $this->settlementService->getSettlements($validated['agent_id']);

        return response()->json(['data' => $settlements]);
    }

    public function cashIn(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'agent_id' => 'required|string',
            'customer_wallet_id' => 'required|string',
            'amount' => 'required|integer|min:100',
            'currency' => 'sometimes|string|in:SYP,USD',
            'idempotency_key' => 'sometimes|string',
            'location_lat' => 'sometimes|numeric|between:-90,90',
            'location_lng' => 'sometimes|numeric|between:-180,180',
            'customer_phone' => 'sometimes|string',
            'customer_name' => 'sometimes|string',
        ]);

        $result = $this->cashInOutService->cashIn(
            agentId: $validated['agent_id'],
            customerWalletId: $validated['customer_wallet_id'],
            amount: (int) $validated['amount'],
            currency: $validated['currency'] ?? 'SYP',
            idempotencyKey: $validated['idempotency_key'] ?? null,
            locationLat: isset($validated['location_lat']) ? (float) $validated['location_lat'] : null,
            locationLng: isset($validated['location_lng']) ? (float) $validated['location_lng'] : null,
            customerPhone: $validated['customer_phone'] ?? null,
            customerName: $validated['customer_name'] ?? null,
        );

        return response()->json(['data' => $result], 201);
    }

    public function cashOut(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'agent_id' => 'required|string',
            'customer_wallet_id' => 'required|string',
            'amount' => 'required|integer|min:100',
            'currency' => 'sometimes|string|in:SYP,USD',
            'idempotency_key' => 'sometimes|string',
            'location_lat' => 'sometimes|numeric|between:-90,90',
            'location_lng' => 'sometimes|numeric|between:-180,180',
            'customer_phone' => 'sometimes|string',
            'customer_name' => 'sometimes|string',
        ]);

        $result = $this->cashInOutService->cashOut(
            agentId: $validated['agent_id'],
            customerWalletId: $validated['customer_wallet_id'],
            amount: (int) $validated['amount'],
            currency: $validated['currency'] ?? 'SYP',
            idempotencyKey: $validated['idempotency_key'] ?? null,
            locationLat: isset($validated['location_lat']) ? (float) $validated['location_lat'] : null,
            locationLng: isset($validated['location_lng']) ? (float) $validated['location_lng'] : null,
            customerPhone: $validated['customer_phone'] ?? null,
            customerName: $validated['customer_name'] ?? null,
        );

        return response()->json(['data' => $result], 201);
    }

    public function verify(string $id): JsonResponse
    {
        $this->agentService->verify($id);
        return response()->json(['message' => 'Agent verified']);
    }

    public function transactions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'agent_id' => 'required|string',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        return response()->json([
            'data' => $this->cashInOutService->getHistory(
                $validated['agent_id'],
                (int) ($validated['per_page'] ?? 15),
            ),
        ]);
    }

    public function settlementsList(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'agent_id' => 'required|string',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        return response()->json([
            'data' => $this->settlementService->getSettlements(
                $validated['agent_id'],
                (int) ($validated['per_page'] ?? 15),
            ),
        ]);
    }
}
