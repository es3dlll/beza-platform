<?php

declare(strict_types=1);

namespace Modules\CoreFinancialEngine\Controllers;

use Modules\CoreFinancialEngine\DTOs\PostingInstructionDto;
use Modules\CoreFinancialEngine\DTOs\ReversalInstructionDto;
use Modules\CoreFinancialEngine\Services\PostingEngine;
use Modules\CoreFinancialEngine\Services\ReversalEngine;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TransactionController
{
    use ApiResponse;
    public function __construct(
        private readonly PostingEngine $posting,
        private readonly ReversalEngine $reversal,
    ) {}

    public function post(Request $request): JsonResponse
    {
        $dto = new PostingInstructionDto(
            referenceType: $request->input('reference_type'),
            referenceId: $request->input('reference_id'),
            description: $request->input('description'),
            lines: $request->input('lines', []),
            channel: $request->input('channel', 'api'),
            initiatedBy: $request->input('initiated_by'),
            metadata: $request->input('metadata', []),
        );

        $result = $this->posting->execute($dto);

        if (!$result->success) {
            return $this->respondError($result->errorCode ?? 'POSTING_FAILED', $result->errorMessage);
        }

        return $this->respondCreated($result);
    }

    public function reverse(string $id, Request $request): JsonResponse
    {
        $dto = new ReversalInstructionDto(
            originalTransactionId: $id,
            reason: $request->input('reason'),
            initiatedBy: $request->input('initiated_by', 'system'),
            metadata: $request->input('metadata', []),
        );

        $result = $this->reversal->reverse($dto);

        if (!$result->success) {
            return $this->respondError($result->errorCode ?? 'REVERSAL_FAILED', $result->errorMessage);
        }

        return $this->respond($result);
    }

    public function canReverse(string $id): JsonResponse
    {
        return $this->respond($this->reversal->canReverse($id));
    }
}
