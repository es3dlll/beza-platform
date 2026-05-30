<?php

declare(strict_types=1);

namespace Modules\Humanitarian\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Humanitarian\Services\HumanitarianService;

class HumanitarianController extends Controller
{
    public function __construct(private readonly HumanitarianService $service) {}

    public function organizations(): JsonResponse { return response()->json(['data' => $this->service->listOrganizations()]); }

    public function programs(Request $request): JsonResponse { return response()->json(['data' => $this->service->listPrograms($request->input('org_id'))]); }

    public function disburse(Request $request): JsonResponse
    {
        $request->validate(['program_id'=>'required|string|size:26','type'=>'required|string|max:30','amount'=>'required|integer|min:100','beneficiary_id'=>'nullable|string']);
        return response()->json(['data' => $this->service->createDisbursement($request->input('program_id'), $request->user()->id, $request->input('type'), $request->integer('amount'), $request->input('beneficiary_id'))], 201);
    }

    public function history(): JsonResponse { return response()->json(['data' => $this->service->history()]); }

    public function ngoDashboard(string $orgId): JsonResponse { return response()->json(['data' => $this->service->ngoDashboard($orgId)]); }

    public function batchDisburse(Request $request): JsonResponse
    {
        $request->validate([
            'program_id' => 'required|string|size:26',
            'beneficiaries' => 'required|array|min:1',
            'beneficiaries.*.user_id' => 'required|string',
            'beneficiaries.*.amount' => 'required|integer|min:100',
            'beneficiaries.*.type' => 'required|string|in:cash,voucher',
        ]);
        return response()->json(['data' => $this->service->batchDisburse($request->input('program_id'), $request->input('beneficiaries'))]);
    }

    public function agentPickup(string $id): JsonResponse { return response()->json(['data' => ['pickup_code' => $this->service->agentPickupCode($id)]]); }

    public function donorReport(string $orgId): JsonResponse { return response()->json(['data' => $this->service->donorReport($orgId)]); }

    public function ofacScreen(Request $request): JsonResponse
    {
        $request->validate(['beneficiary_ids' => 'required|array|min:1', 'beneficiary_ids.*' => 'required|string']);
        return response()->json(['data' => $this->service->ofacScreen($request->input('beneficiary_ids'))]);
    }
}
