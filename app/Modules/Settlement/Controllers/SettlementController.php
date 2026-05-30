<?php

declare(strict_types=1);

namespace Modules\Settlement\Controllers;

use Modules\Settlement\Services\SettlementService;
use Modules\Settlement\Services\AgentSettlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SettlementController
{
    public function __construct(
        private readonly SettlementService $settlements,
        private readonly AgentSettlementService $agent,
    ) {}

    public function show(string $id): JsonResponse
    {
        try {
            return response()->json(['data' => $this->settlements->getSummary($id)]);
        } catch (\Modules\Settlement\Exceptions\SettlementNotFoundException $e) {
            return response()->json(['error' => ['code' => 'SETTLEMENT_NOT_FOUND', 'message' => $e->getMessage()]], 404);
        }
    }

    public function execute(string $id): JsonResponse
    {
        try {
            $result = $this->settlements->execute($id);
            if (!$result->success) {
                return response()->json(['error' => ['code' => 'SETTLEMENT_EXECUTION_FAILED', 'message' => $result->error]], 422);
            }
            return response()->json(['data' => $result]);
        } catch (\Exception $e) {
            return response()->json(['error' => ['code' => 'SETTLEMENT_FAILED', 'message' => $e->getMessage()]], 422);
        }
    }

    public function settleAgentDaily(Request $request): JsonResponse
    {
        try {
            $settlement = $this->agent->settleDailyAgentNet(
                $request->input('agent_id'),
                (int) $request->input('cash_in_total', 0),
                (int) $request->input('cash_out_total', 0),
                (int) $request->input('commission_total', 0),
            );
            return response()->json(['data' => $settlement], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => ['code' => 'AGENT_SETTLEMENT_FAILED', 'message' => $e->getMessage()]], 422);
        }
    }
}
