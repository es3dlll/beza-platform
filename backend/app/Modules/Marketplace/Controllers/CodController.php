<?php

declare(strict_types=1);

namespace Modules\Marketplace\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Marketplace\Services\CodService;

class CodController extends Controller
{
    public function __construct(
        private CodService $cod,
    ) {}

    public function collect(Request $request): JsonResponse
    {
        $data = $request->validate([
            'shipment_id' => 'required|string',
            'agent_id' => 'required|string',
        ]);

        $collection = $this->cod->collect($data['shipment_id'], $data['agent_id']);

        return response()->json([
            'success' => true,
            'data' => $collection,
        ], 201);
    }

    public function remit(string $id): JsonResponse
    {
        $collection = $this->cod->remit($id);

        return response()->json([
            'success' => true,
            'data' => $collection,
        ]);
    }

    public function pending(): JsonResponse
    {
        $pending = $this->cod->listPending();

        return response()->json([
            'success' => true,
            'data' => $pending,
        ]);
    }

    public function agent(Request $request): JsonResponse
    {
        $agentId = $request->query('agent_id');

        if ($agentId === null) {
            return response()->json([
                'success' => false,
                'error' => 'agent_id query parameter is required',
            ], 422);
        }

        $collections = $this->cod->listByAgent($agentId);

        return response()->json([
            'success' => true,
            'data' => $collections,
        ]);
    }
}
