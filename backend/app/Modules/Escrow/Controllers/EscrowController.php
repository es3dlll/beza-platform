<?php

declare(strict_types=1);

namespace Modules\Escrow\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Escrow\Exceptions\EscrowNotFoundException;
use Modules\Escrow\Exceptions\EscrowAlreadyResolvedException;
use Modules\Escrow\Exceptions\EscrowExpiredException;
use Modules\Escrow\Exceptions\EscrowDisputeNotFoundException;
use Modules\Escrow\Models\EscrowAgreement;
use Modules\Escrow\Services\EscrowService;

class EscrowController extends Controller
{
    public function __construct(private readonly EscrowService $service) {}

    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status');
        if ($status) {
            return response()->json(['data' => $this->service->listByStatus($status, (int) $request->query('per_page', 15))]);
        }
        return response()->json(['data' => $this->service->listByUser($request->user()->id)]);
    }

    public function show(string $id): JsonResponse
    {
        try {
            return response()->json(['data' => $this->service->findOrFail($id)]);
        } catch (EscrowNotFoundException $e) {
            return response()->json(['error' => 'ESCOW_NOT_FOUND'], 404);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'seller_id' => 'required|string|size:26',
            'amount' => 'required|integer|min:100',
            'reference_type' => 'required|string|max:30',
            'reference_id' => 'required|string|max:50',
            'description' => 'nullable|string|max:500',
            'fee_percent' => 'nullable|integer|min:0|max:100',
        ]);

        $agreement = $this->service->create(
            $request->user()->id,
            $request->input('seller_id'),
            $request->integer('amount'),
            $request->input('reference_type'),
            $request->input('reference_id'),
            $request->input('description'),
            $request->integer('fee_percent', 1),
        );

        return response()->json(['data' => $agreement], 201);
    }

    public function release(string $id): JsonResponse
    {
        try {
            return response()->json(['data' => $this->service->release($id)]);
        } catch (EscrowAlreadyResolvedException $e) {
            return response()->json(['error' => 'ALREADY_RESOLVED'], 422);
        } catch (EscrowExpiredException $e) {
            return response()->json(['error' => 'ESCOW_EXPIRED'], 422);
        } catch (EscrowNotFoundException $e) {
            return response()->json(['error' => 'ESCOW_NOT_FOUND'], 404);
        }
    }

    public function refund(string $id): JsonResponse
    {
        try {
            return response()->json(['data' => $this->service->refund($id)]);
        } catch (EscrowAlreadyResolvedException $e) {
            return response()->json(['error' => 'ALREADY_RESOLVED'], 422);
        } catch (EscrowExpiredException $e) {
            return response()->json(['error' => 'ESCOW_EXPIRED'], 422);
        } catch (EscrowNotFoundException $e) {
            return response()->json(['error' => 'ESCOW_NOT_FOUND'], 404);
        }
    }

    public function dispute(Request $request, string $id): JsonResponse
    {
        $request->validate(['reason' => 'required|string|max:500']);

        try {
            $this->service->findOrFail($id);
        } catch (EscrowNotFoundException $e) {
            return response()->json(['error' => 'ESCOW_NOT_FOUND'], 404);
        }

        return response()->json(['data' => $this->service->openDispute($id, $request->user()->id, $request->input('reason'))], 201);
    }

    public function resolveDispute(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'resolution' => 'required|string|max:500',
            'action' => 'nullable|in:release,refund',
        ]);

        try {
            return response()->json(['data' => $this->service->resolveDispute($id, $request->user()->id, $request->input('resolution'), $request->input('action', 'release'))]);
        } catch (EscrowDisputeNotFoundException $e) {
            return response()->json(['error' => 'DISPUTE_NOT_FOUND'], 404);
        }
    }
}
