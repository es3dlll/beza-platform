<?php

declare(strict_types=1);

namespace Modules\Settlement\Controllers;

use App\Support\ApiResponse;
use Modules\Settlement\Services\SettlementService;
use Modules\Settlement\Services\AgentSettlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SettlementController
{
    use ApiResponse;

    public function __construct(
        private readonly SettlementService $settlements,
        private readonly AgentSettlementService $agent,
    ) {}

    public function show(string $id): JsonResponse
    {
        try {
            return $this->respond($this->settlements->getSummary($id));
        } catch (\Modules\Settlement\Exceptions\SettlementNotFoundException $e) {
            return $this->respondError('SETTLEMENT_NOT_FOUND', $e->getMessage(), null, 404);
        }
    }

    public function execute(string $id): JsonResponse
    {
        try {
            $result = $this->settlements->execute($id);
            if (!$result->success) {
                return $this->respondError('SETTLEMENT_EXECUTION_FAILED', $result->error, null, 422);
            }
            return $this->respond($result);
        } catch (\Exception $e) {
            return $this->respondError('SETTLEMENT_FAILED', $e->getMessage(), null, 422);
        }
    }

    public function retry(string $id): JsonResponse
    {
        try {
            $result = $this->settlements->retry($id);
            if (!$result->success) {
                return $this->respondError('SETTLEMENT_RETRY_FAILED', $result->error, null, 422);
            }
            return $this->respond($result);
        } catch (\Exception $e) {
            return $this->respondError('SETTLEMENT_RETRY_ERROR', $e->getMessage(), null, 422);
        }
    }

    public function reconcile(string $id, Request $request): JsonResponse
    {
        try {
            $result = $this->settlements->reconcile($id, $request->validate([
                'amount' => 'required|integer',
                'reference' => 'nullable|string',
            ]));
            return $this->respond($result);
        } catch (\Modules\Settlement\Exceptions\SettlementNotFoundException $e) {
            return $this->respondError('SETTLEMENT_NOT_FOUND', $e->getMessage(), null, 404);
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
            return $this->respondCreated($settlement);
        } catch (\Exception $e) {
            return $this->respondError('AGENT_SETTLEMENT_FAILED', $e->getMessage(), null, 422);
        }
    }

    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status', '');
        $perPage = (int) $request->query('per_page', 15);

        if ($status) {
            $data = $this->settlements->listByStatus($status, $perPage);
        } else {
            $data = \Modules\Settlement\Models\Settlement::with('lines')
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);
        }

        return $this->respond($data);
    }

    public function processCutoff(): JsonResponse
    {
        try {
            $result = $this->settlements->processDailyCutoff();
            return $this->respond($result);
        } catch (\Exception $e) {
            return $this->respondError('CUTOFF_FAILED', $e->getMessage(), null, 422);
        }
    }
}
