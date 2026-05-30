<?php

declare(strict_types=1);

namespace Modules\Admin\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Admin\Services\TakafulAdminService;

final class TakafulAdminController extends Controller
{
    public function __construct(
        private readonly TakafulAdminService $service,
    ) {}

    public function dashboard(): JsonResponse
    {
        return response()->json(['data' => $this->service->dashboard()]);
    }

    public function listPolicies(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->service->listPolicies($request->query('status'))]);
    }

    public function policyDetail(string $id): JsonResponse
    {
        return response()->json(['data' => $this->service->policyDetail($id)]);
    }

    public function listClaims(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->service->listClaims($request->query('status'))]);
    }

    public function claimDetail(string $id): JsonResponse
    {
        return response()->json(['data' => $this->service->claimDetail($id)]);
    }

    public function approveClaim(Request $request, string $id): JsonResponse
    {
        $request->validate(['approved_amount' => 'required|integer|min:1']);
        $this->service->approveClaim($id, (int) $request->input('approved_amount'));
        return response()->json(['data' => ['message' => 'Claim approved']]);
    }

    public function rejectClaim(Request $request, string $id): JsonResponse
    {
        $request->validate(['reason' => 'required|string|max:500']);
        $this->service->rejectClaim($id, $request->input('reason'));
        return response()->json(['data' => ['message' => 'Claim rejected']]);
    }
}
