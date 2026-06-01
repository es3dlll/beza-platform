<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Services\AgentAdminService;
use App\Modules\Agent\Models\AgentCommission;
use App\Modules\Agent\Models\AgentSettlement;
use App\Modules\Agent\Models\FraudAlert;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentOversightController extends Controller
{
    public function __construct(
        private readonly AgentAdminService $agentAdminService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'nullable|string|in:pending,active,suspended,closed',
            'region' => 'nullable|string|max:100',
            'search' => 'nullable|string|max:255',
            'balance_min' => 'nullable|integer|min:0',
            'balance_max' => 'nullable|integer|min:0',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $agents = $this->agentAdminService->listAgents($validated);

        return response()->json([
            'success' => true,
            'data' => $agents->items(),
            'meta' => [
                'current_page' => $agents->currentPage(),
                'last_page' => $agents->lastPage(),
                'per_page' => $agents->perPage(),
                'total' => $agents->total(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $agent = $this->agentAdminService->getAgentDetail($id);

        return response()->json([
            'success' => true,
            'data' => $agent,
        ]);
    }

    public function commissions(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'nullable|string|in:accrued,settled,voided',
            'type' => 'nullable|string|max:50',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $commissions = $this->agentAdminService->getAgentCommissions($id, $validated);

        return response()->json([
            'success' => true,
            'data' => $commissions->items(),
            'meta' => [
                'current_page' => $commissions->currentPage(),
                'last_page' => $commissions->lastPage(),
                'per_page' => $commissions->perPage(),
                'total' => $commissions->total(),
            ],
        ]);
    }

    public function approveCommission(Request $request, int $id): JsonResponse
    {
        AgentCommission::findOrFail($id);

        $validated = $request->validate([
            'note' => 'nullable|string|max:500',
        ]);

        try {
            $commission = $this->agentAdminService->approveCommission(
                $id,
                $request->user(),
                $validated['note'] ?? null,
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $commission->id,
                    'status' => $commission->status,
                    'settled_at' => $commission->settled_at,
                ],
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'APPROVE_FAILED', 'message' => $e->getMessage()],
            ], 422);
        }
    }

    public function settlements(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'nullable|string|in:pending,completed,approved,failed',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $settlements = $this->agentAdminService->getAgentSettlements($id, $validated);

        return response()->json([
            'success' => true,
            'data' => $settlements->items(),
            'meta' => [
                'current_page' => $settlements->currentPage(),
                'last_page' => $settlements->lastPage(),
                'per_page' => $settlements->perPage(),
                'total' => $settlements->total(),
            ],
        ]);
    }

    public function approveSettlement(Request $request, int $id): JsonResponse
    {
        AgentSettlement::findOrFail($id);

        $validated = $request->validate([
            'note' => 'nullable|string|max:500',
        ]);

        try {
            $settlement = $this->agentAdminService->approveSettlement(
                $id,
                $request->user(),
                $validated['note'] ?? null,
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $settlement->id,
                    'status' => $settlement->status,
                    'processed_at' => $settlement->processed_at,
                ],
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'APPROVE_FAILED', 'message' => $e->getMessage()],
            ], 422);
        }
    }

    public function fraudAlerts(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'severity' => 'nullable|string|in:low,medium,high,critical',
            'status' => 'nullable|string|in:open,investigating,resolved',
            'type' => 'nullable|string|max:50',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $alerts = $this->agentAdminService->getFraudAlerts($validated);

        return response()->json([
            'success' => true,
            'data' => $alerts->items(),
            'meta' => [
                'current_page' => $alerts->currentPage(),
                'last_page' => $alerts->lastPage(),
                'per_page' => $alerts->perPage(),
                'total' => $alerts->total(),
            ],
        ]);
    }

    public function resolveFraudAlert(Request $request, int $id): JsonResponse
    {
        FraudAlert::findOrFail($id);

        $validated = $request->validate([
            'action' => 'required|string|in:investigate,dismiss,block_agent,escalate',
            'note' => 'nullable|string|max:500',
        ]);

        try {
            $alert = $this->agentAdminService->resolveFraudAlert(
                $id,
                $request->user(),
                $validated['action'],
                $validated['note'] ?? null,
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $alert->id,
                    'status' => $alert->status,
                    'resolved_at' => $alert->resolved_at,
                ],
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'RESOLVE_FAILED', 'message' => $e->getMessage()],
            ], 422);
        }
    }
}
