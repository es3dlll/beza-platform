<?php

declare(strict_types=1);

namespace Modules\Admin\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Admin\Services\EscrowAdminService;
use App\Support\ApiResponse;

final class EscrowAdminController extends Controller
{
    use ApiResponse;
    public function __construct(
        private readonly EscrowAdminService $service,
    ) {}

    public function dashboard(): JsonResponse
    {
        return $this->respond($this->service->dashboard());
    }

    public function disputeQueue(): JsonResponse
    {
        return $this->respond($this->service->disputeQueue());
    }

    public function disputeDetail(string $id): JsonResponse
    {
        return $this->respond($this->service->disputeDetail($id));
    }

    public function resolveDispute(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'resolution' => 'required|string|max:1000',
            'action' => 'required|in:release,refund',
        ]);
        $this->service->resolveDispute($id, $request->input('resolution'), $request->input('action'));
        return $this->respond(['message' => 'Dispute resolved']);
    }

    public function listAgreements(Request $request): JsonResponse
    {
        return $this->respond($this->service->listAgreements($request->query('status')));
    }

    public function agreementDetail(string $id): JsonResponse
    {
        return $this->respond($this->service->agreementDetail($id));
    }
}
