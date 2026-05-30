<?php

declare(strict_types=1);

namespace Modules\Takaful\Controllers;

use Modules\Takaful\Exceptions\ClaimNotFoundException;
use Modules\Takaful\Exceptions\PolicyExpiredException;
use Modules\Takaful\Exceptions\PolicyNotFoundException;
use Modules\Takaful\Exceptions\TakafulProductNotFoundException;
use Modules\Takaful\Services\TakafulService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TakafulController
{
    public function __construct(
        private readonly TakafulService $takaful,
    ) {}

    public function indexProducts(Request $request): JsonResponse
    {
        $products = $this->takaful->listProducts($request->input('type'));

        return response()->json(['data' => $products]);
    }

    public function subscribe(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|string',
            'premium' => 'required|integer|min:1',
            'coverage_amount' => 'required|integer|min:1',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        try {
            $policy = $this->takaful->subscribe(
                $request->user()->getAuthIdentifier(),
                $request->input('product_id'),
                (int) $request->input('premium'),
                (int) $request->input('coverage_amount'),
                $request->input('start_date'),
                $request->input('end_date'),
            );

            return response()->json(['data' => $policy], 201);
        } catch (TakafulProductNotFoundException $e) {
            return response()->json(['error' => ['code' => 'PRODUCT_NOT_FOUND', 'message' => $e->getMessage()]], 404);
        }
    }

    public function indexPolicies(Request $request): JsonResponse
    {
        $policies = $this->takaful->listPolicies($request->user()->getAuthIdentifier());

        return response()->json(['data' => $policies]);
    }

    public function showPolicy(string $id): JsonResponse
    {
        try {
            $policy = $this->takaful->findPolicyOrFail($id);

            return response()->json(['data' => $policy]);
        } catch (PolicyNotFoundException $e) {
            return response()->json(['error' => ['code' => 'POLICY_NOT_FOUND', 'message' => $e->getMessage()]], 404);
        }
    }

    public function fileClaim(Request $request): JsonResponse
    {
        $request->validate([
            'policy_id' => 'required|string',
            'amount' => 'required|integer|min:1',
            'reason' => 'required|string',
        ]);

        try {
            $claim = $this->takaful->fileClaim(
                $request->input('policy_id'),
                (int) $request->input('amount'),
                $request->input('reason'),
            );

            return response()->json(['data' => $claim], 201);
        } catch (PolicyNotFoundException $e) {
            return response()->json(['error' => ['code' => 'POLICY_NOT_FOUND', 'message' => $e->getMessage()]], 404);
        } catch (PolicyExpiredException $e) {
            return response()->json(['error' => ['code' => 'POLICY_EXPIRED', 'message' => $e->getMessage()]], 422);
        }
    }

    public function indexClaims(Request $request): JsonResponse
    {
        $claims = $this->takaful->listClaims($request->user()->getAuthIdentifier());

        return response()->json(['data' => $claims]);
    }

    public function approveClaim(string $id, Request $request): JsonResponse
    {
        $request->validate([
            'approved_amount' => 'required|integer|min:1',
        ]);

        try {
            $claim = $this->takaful->approveClaim(
                $id,
                (int) $request->input('approved_amount'),
            );

            return response()->json(['data' => $claim]);
        } catch (ClaimNotFoundException $e) {
            return response()->json(['error' => ['code' => 'CLAIM_NOT_FOUND', 'message' => $e->getMessage()]], 404);
        }
    }

    public function rejectClaim(string $id, Request $request): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string',
        ]);

        try {
            $claim = $this->takaful->rejectClaim(
                $id,
                $request->input('reason'),
            );

            return response()->json(['data' => $claim]);
        } catch (ClaimNotFoundException $e) {
            return response()->json(['error' => ['code' => 'CLAIM_NOT_FOUND', 'message' => $e->getMessage()]], 404);
        }
    }

    public function adminDashboard(): JsonResponse
    {
        $dashboard = $this->takaful->adminDashboard();

        return response()->json(['data' => $dashboard]);
    }
}
