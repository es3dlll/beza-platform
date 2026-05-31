<?php

declare(strict_types=1);

namespace App\Modules\Escrow\Controllers;

use App\Models\User;
use App\Modules\Escrow\Models\DisputeCase;
use App\Modules\Escrow\Models\EscrowTransaction;
use App\Modules\Escrow\Services\EscrowCustodianService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class EscrowController extends Controller
{
    public function __construct(
        private readonly EscrowCustodianService $escrow,
    ) {}

    public function initiate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'buyer_id' => 'required|string|exists:users,id',
            'seller_id' => 'required|string|exists:marketplace_sellers,id',
            'amount_fils' => 'required|integer|min:1000',
            'marketplace_ref_id' => 'nullable|string',
        ]);

        $buyer = User::find($validated['buyer_id']);
        if (!$buyer) return response()->json(['error' => 'المشتري غير موجود'], 404);

        try {
            $tx = $this->escrow->initiate(
                buyer: $buyer,
                sellerId: $validated['seller_id'],
                amountFils: $validated['amount_fils'],
                marketplaceRef: $validated['marketplace_ref_id'] ?? null,
            );
            return response()->json(['data' => $tx], 201);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function fund(string $id): JsonResponse
    {
        $tx = EscrowTransaction::find($id);
        if (!$tx) return response()->json(['error' => 'المعاملة غير موجودة'], 404);
        try {
            return response()->json(['data' => $this->escrow->fund($tx)]);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function release(string $id): JsonResponse
    {
        $tx = EscrowTransaction::find($id);
        if (!$tx) return response()->json(['error' => 'المعاملة غير موجودة'], 404);
        try {
            return response()->json(['data' => $this->escrow->release($tx)]);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function refund(string $id): JsonResponse
    {
        $tx = EscrowTransaction::find($id);
        if (!$tx) return response()->json(['error' => 'المعاملة غير موجودة'], 404);
        try {
            return response()->json(['data' => $this->escrow->refund($tx)]);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function dispute(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'raised_by' => 'required|string',
            'reason' => 'required|string|max:100',
            'description' => 'required|string|max:2000',
            'documents' => 'nullable|array',
        ]);

        $tx = EscrowTransaction::find($id);
        if (!$tx) return response()->json(['error' => 'المعاملة غير موجودة'], 404);

        try {
            $dispute = $this->escrow->openDispute(
                transaction: $tx,
                raisedBy: $validated['raised_by'],
                reason: $validated['reason'],
                description: $validated['description'],
                documents: $validated['documents'] ?? [],
            );
            return response()->json(['data' => $dispute], 201);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function resolveDispute(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'decision' => 'required|in:buyer,seller,split',
            'decision_reason' => 'required|string|max:1000',
            'resolved_by' => 'required|string',
        ]);

        $dispute = DisputeCase::find($id);
        if (!$dispute) return response()->json(['error' => 'النزاع غير موجود'], 404);
        if ($dispute->isResolved()) return response()->json(['error' => 'النزاع مقرّر مسبقاً'], 422);

        try {
            $result = $this->escrow->resolveDispute(
                dispute: $dispute,
                decision: $validated['decision'],
                reason: $validated['decision_reason'],
                resolvedBy: $validated['resolved_by'],
            );
            return response()->json(['data' => $result]);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function index(Request $request): JsonResponse
    {
        $query = EscrowTransaction::with('dispute');
        if ($s = $request->get('status')) $query->byStatus($s);
        if ($b = $request->get('buyer_id')) $query->where('buyer_id', $b);
        if ($sel = $request->get('seller_id')) $query->where('seller_id', $sel);
        return response()->json(['data' => $query->orderBy('created_at', 'desc')->paginate(20)]);
    }

    public function show(string $id): JsonResponse
    {
        $tx = EscrowTransaction::with('dispute')->find($id);
        if (!$tx) return response()->json(['error' => 'المعاملة غير موجودة'], 404);
        return response()->json(['data' => $tx]);
    }

    public function disputes(Request $request): JsonResponse
    {
        $query = DisputeCase::with('transaction');
        if ($s = $request->get('status')) $query->where('status', $s);
        return response()->json(['data' => $query->orderBy('created_at', 'desc')->paginate(20)]);
    }

    public function stats(): JsonResponse
    {
        return response()->json([
            'data' => [
                'total_active' => EscrowTransaction::active()->count(),
                'total_disputed' => EscrowTransaction::where('status', 'disputed')->count(),
                'total_released' => EscrowTransaction::where('status', 'released')->count(),
                'total_refunded' => EscrowTransaction::where('status', 'refunded')->count(),
                'total_hold_fils' => EscrowTransaction::active()->sum('amount_fils'),
            ],
        ]);
    }
}
