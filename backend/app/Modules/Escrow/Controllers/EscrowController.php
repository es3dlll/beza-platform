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
use App\Support\ApiResponse;

final class EscrowController extends Controller
{
    use ApiResponse;
    public function __construct(private readonly EscrowService $service) {}

    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status');
        if ($status) {
            return $this->respond($this->service->listByStatus($status, (int) $request->query('per_page', 15)));
        }
        return $this->respond($this->service->listByUser($request->user()->id));
    }

    public function show(string $id): JsonResponse
    {
        try {
            return $this->respond($this->service->findOrFail($id));
        } catch (EscrowNotFoundException $e) {
            return $this->respondNotFound('Escrow');
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

        return $this->respondCreated($agreement);
    }

    public function release(string $id): JsonResponse
    {
        try {
            return $this->respond($this->service->release($id));
        } catch (EscrowAlreadyResolvedException $e) {
            return $this->respondError('ALREADY_RESOLVED', $e->getMessage());
        } catch (EscrowExpiredException $e) {
            return $this->respondError('ESCOW_EXPIRED', $e->getMessage());
        } catch (EscrowNotFoundException $e) {
            return $this->respondNotFound('Escrow');
        }
    }

    public function refund(string $id): JsonResponse
    {
        try {
            return $this->respond($this->service->refund($id));
        } catch (EscrowAlreadyResolvedException $e) {
            return $this->respondError('ALREADY_RESOLVED', $e->getMessage());
        } catch (EscrowExpiredException $e) {
            return $this->respondError('ESCOW_EXPIRED', $e->getMessage());
        } catch (EscrowNotFoundException $e) {
            return $this->respondNotFound('Escrow');
        }
    }

    public function dispute(Request $request, string $id): JsonResponse
    {
        $request->validate(['reason' => 'required|string|max:500']);

        try {
            $this->service->findOrFail($id);
        } catch (EscrowNotFoundException $e) {
            return $this->respondNotFound('Escrow');
        }

        return $this->respondCreated($this->service->openDispute($id, $request->user()->id, $request->input('reason')));
    }

    public function resolveDispute(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'resolution' => 'required|string|max:500',
            'action' => 'nullable|in:release,refund',
        ]);

        try {
            return $this->respond($this->service->resolveDispute($id, $request->user()->id, $request->input('resolution'), $request->input('action', 'release')));
        } catch (EscrowDisputeNotFoundException $e) {
            return $this->respondError('DISPUTE_NOT_FOUND', $e->getMessage());
        }
    }
}
