<?php

declare(strict_types=1);

namespace Modules\Admin\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Admin\Services\EscrowAdminService;

final class EscrowAdminController extends Controller
{
    public function __construct(
        private readonly EscrowAdminService $service,
    ) {}

    public function dashboard(): JsonResponse
    {
        return response()->json(['data' => $this->service->dashboard()]);
    }

    public function disputeQueue(): JsonResponse
    {
        return response()->json(['data' => $this->service->disputeQueue()]);
    }

    public function disputeDetail(string $id): JsonResponse
    {
        return response()->json(['data' => $this->service->disputeDetail($id)]);
    }

    public function resolveDispute(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'resolution' => 'required|string|max:1000',
            'action' => 'required|in:release,refund',
        ]);
        $this->service->resolveDispute($id, $request->input('resolution'), $request->input('action'));
        return response()->json(['data' => ['message' => 'Dispute resolved']]);
    }

    public function listAgreements(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->service->listAgreements($request->query('status'))]);
    }

    public function agreementDetail(string $id): JsonResponse
    {
        return response()->json(['data' => $this->service->agreementDetail($id)]);
    }
}
