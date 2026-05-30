<?php

declare(strict_types=1);

namespace Modules\Admin\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Admin\Services\FinancingAdminService;
use App\Support\ApiResponse;

final class FinancingAdminController extends Controller
{
    use ApiResponse;
    public function __construct(
        private readonly FinancingAdminService $service,
    ) {}

    public function dashboard(): JsonResponse
    {
        return $this->respond($this->service->dashboard());
    }

    public function pendingApprovals(): JsonResponse
    {
        return $this->respond($this->service->pendingApprovals());
    }

    public function approveLoan(string $id): JsonResponse
    {
        $this->service->approveLoan($id);
        return $this->respond(['message' => 'Loan approved']);
    }

    public function rejectLoan(Request $request, string $id): JsonResponse
    {
        $request->validate(['reason' => 'required|string|max:500']);
        $this->service->rejectLoan($id, $request->input('reason'));
        return $this->respond(['message' => 'Loan rejected']);
    }

    public function loanDetail(string $id): JsonResponse
    {
        return $this->respond($this->service->loanDetail($id));
    }

    public function writeOff(string $id): JsonResponse
    {
        $this->service->writeOff($id);
        return $this->respond(['message' => 'Loan written off']);
    }

    public function listLoans(Request $request): JsonResponse
    {
        $result = $this->service->listLoans(
            $request->query('status'),
            (int) $request->query('per_page', 15),
        );
        return $this->respond($result);
    }

    public function productConfig(): JsonResponse
    {
        return $this->respond($this->service->productConfig());
    }

    public function updateProductConfig(Request $request, string $id): JsonResponse
    {
        $this->service->updateProductConfig($id, $request->all());
        return $this->respond(['message' => 'Product config updated']);
    }
}
