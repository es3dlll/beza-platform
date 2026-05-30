<?php

declare(strict_types=1);

namespace Modules\Admin\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Admin\Services\FinancingAdminService;

final class FinancingAdminController extends Controller
{
    public function __construct(
        private readonly FinancingAdminService $service,
    ) {}

    public function dashboard(): JsonResponse
    {
        return response()->json(['data' => $this->service->dashboard()]);
    }

    public function pendingApprovals(): JsonResponse
    {
        return response()->json(['data' => $this->service->pendingApprovals()]);
    }

    public function approveLoan(string $id): JsonResponse
    {
        $this->service->approveLoan($id);
        return response()->json(['data' => ['message' => 'Loan approved']]);
    }

    public function rejectLoan(Request $request, string $id): JsonResponse
    {
        $request->validate(['reason' => 'required|string|max:500']);
        $this->service->rejectLoan($id, $request->input('reason'));
        return response()->json(['data' => ['message' => 'Loan rejected']]);
    }

    public function loanDetail(string $id): JsonResponse
    {
        return response()->json(['data' => $this->service->loanDetail($id)]);
    }

    public function writeOff(string $id): JsonResponse
    {
        $this->service->writeOff($id);
        return response()->json(['data' => ['message' => 'Loan written off']]);
    }

    public function listLoans(Request $request): JsonResponse
    {
        $result = $this->service->listLoans(
            $request->query('status'),
            (int) $request->query('per_page', 15),
        );
        return response()->json(['data' => $result]);
    }

    public function productConfig(): JsonResponse
    {
        return response()->json(['data' => $this->service->productConfig()]);
    }

    public function updateProductConfig(Request $request, string $id): JsonResponse
    {
        $this->service->updateProductConfig($id, $request->all());
        return response()->json(['data' => ['message' => 'Product config updated']]);
    }
}
