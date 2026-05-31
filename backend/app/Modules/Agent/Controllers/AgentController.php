<?php

declare(strict_types=1);

namespace App\Modules\Agent\Controllers;

use App\Modules\Agent\Models\Agent;
use App\Modules\Agent\Services\AgentService;
use App\Modules\Agent\Services\CashInOutService;
use App\Modules\Agent\Services\SettlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class AgentController extends Controller
{
    public function __construct(
        private readonly AgentService $agentService,
        private readonly CashInOutService $cashInOutService,
        private readonly SettlementService $settlementService,
    ) {}

    public function register(Request $request): JsonResponse
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

        $agent = $this->agentService->register($validated);
        return response()->json(['data' => $agent], 201);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(['data' => $this->agentService->getAgent($id)]);
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

    public function settlements(Request $request): JsonResponse
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
