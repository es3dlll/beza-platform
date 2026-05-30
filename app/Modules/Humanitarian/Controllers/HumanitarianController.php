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
}
