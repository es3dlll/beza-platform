<?php
declare(strict_types=1);

namespace Modules\Ledger\Controllers;

use Modules\Ledger\DTOs\CreateHoldDto;
use Modules\Ledger\Services\HoldService;
use Modules\Ledger\Repositories\LedgerHoldRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class HoldController
{
    public function __construct(
        private readonly HoldService $holds,
        private readonly LedgerHoldRepository $holdRepo,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $dto = new CreateHoldDto(
            accountId: $request->input('account_id'),
            amount: $request->input('amount'),
            currency: $request->input('currency', 'SYP'),
            reason: $request->input('reason'),
            referenceType: $request->input('reference_type'),
            referenceId: $request->input('reference_id'),
            expiresAt: $request->input('expires_at') ? new \DateTime($request->input('expires_at')) : null,
        );

        $hold = $this->holds->place($dto);
        return response()->json(['data' => $hold], 201);
    }

    public function release(string $id, Request $request): JsonResponse
    {
        $hold = $this->holds->release($id, $request->input('reason', 'manual release'));
        return response()->json(['data' => $hold]);
    }

    public function byAccount(string $accountId): JsonResponse
    {
        $holds = $this->holdRepo->findByAccount($accountId);
        return response()->json(['data' => $holds]);
    }
}
