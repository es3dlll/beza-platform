<?php

declare(strict_types=1);

namespace Modules\Admin\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Admin\Services\HumanitarianAdminService;
use App\Support\ApiResponse;

final class HumanitarianAdminController extends Controller
{
    use ApiResponse;
    public function __construct(
        private readonly HumanitarianAdminService $service,
    ) {}

    public function dashboard(): JsonResponse
    {
        return $this->respond($this->service->dashboard());
    }

    public function listPrograms(): JsonResponse
    {
        return $this->respond($this->service->listActivePrograms());
    }

    public function programDetail(string $id): JsonResponse
    {
        return $this->respond($this->service->programDetail($id));
    }

    public function approveProgram(string $id): JsonResponse
    {
        $this->service->approveProgram($id);
        return $this->respond(['message' => 'Program approved']);
    }

    public function suspendProgram(Request $request, string $id): JsonResponse
    {
        $request->validate(['reason' => 'required|string|max:500']);
        $this->service->suspendProgram($id, $request->input('reason'));
        return $this->respond(['message' => 'Program suspended']);
    }

    public function budgetAlerts(): JsonResponse
    {
        return $this->respond($this->service->budgetAlerts());
    }

    public function donorReport(string $programId): JsonResponse
    {
        return $this->respond($this->service->donorReport($programId));
    }
}
