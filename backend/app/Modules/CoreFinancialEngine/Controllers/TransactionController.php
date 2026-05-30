<?php

namespace Modules\CoreFinancialEngine\Controllers;

use Modules\CoreFinancialEngine\DTOs\PostingInstructionDto;
use Modules\CoreFinancialEngine\DTOs\ReversalInstructionDto;
use Modules\CoreFinancialEngine\Services\PostingEngine;
use Modules\CoreFinancialEngine\Services\ReversalEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TransactionController
{
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
            return response()->json(['error' => $result->errorMessage, 'code' => $result->errorCode], 422);
        }

        return response()->json(['data' => $result], 201);
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
            return response()->json(['error' => $result->errorMessage, 'code' => $result->errorCode], 422);
        }

        return response()->json(['data' => $result]);
    }

    public function canReverse(string $id): JsonResponse
    {
        return response()->json(['data' => $this->reversal->canReverse($id)]);
    }
}
