<?php

declare(strict_types=1);

namespace Modules\Admin\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Admin\Services\HumanitarianAdminService;

final class HumanitarianAdminController extends Controller
{
    public function __construct(
        private readonly HumanitarianAdminService $service,
    ) {}

    public function dashboard(): JsonResponse
    {
        return response()->json(['data' => $this->service->dashboard()]);
    }

    public function listPrograms(): JsonResponse
    {
        return response()->json(['data' => $this->service->listActivePrograms()]);
    }

    public function programDetail(string $id): JsonResponse
    {
        return response()->json(['data' => $this->service->programDetail($id)]);
    }

    public function approveProgram(string $id): JsonResponse
    {
        $this->service->approveProgram($id);
        return response()->json(['data' => ['message' => 'Program approved']]);
    }

    public function suspendProgram(Request $request, string $id): JsonResponse
    {
        $request->validate(['reason' => 'required|string|max:500']);
        $this->service->suspendProgram($id, $request->input('reason'));
        return response()->json(['data' => ['message' => 'Program suspended']]);
    }

    public function budgetAlerts(): JsonResponse
    {
        return response()->json(['data' => $this->service->budgetAlerts()]);
    }

    public function donorReport(string $programId): JsonResponse
    {
        return response()->json(['data' => $this->service->donorReport($programId)]);
    }
}
