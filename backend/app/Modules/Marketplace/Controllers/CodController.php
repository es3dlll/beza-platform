<?php

declare(strict_types=1);

namespace Modules\Marketplace\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Marketplace\Services\CodService;
use App\Support\ApiResponse;

final class CodController extends Controller
{
    use ApiResponse;
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

        return $this->respondCreated($collection);
    }

    public function remit(string $id): JsonResponse
    {
        $collection = $this->cod->remit($id);

        return $this->respond($collection);
    }

    public function pending(): JsonResponse
    {
        $pending = $this->cod->listPending();

        return $this->respond($pending);
    }

    public function agent(Request $request): JsonResponse
    {
        $agentId = $request->query('agent_id');

        if ($agentId === null) {
            return $this->respondError('MISSING_PARAMETER', 'agent_id query parameter is required');
        }

        $collections = $this->cod->listByAgent($agentId);

        return $this->respond($collections);
    }
}
