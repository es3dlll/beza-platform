<?php

declare(strict_types=1);

namespace Modules\CoreFinancialEngine\Controllers;

use Modules\CoreFinancialEngine\DTOs\FeeAssessmentDto;
use Modules\CoreFinancialEngine\Services\FeeEngine;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class FeeController
{
    use ApiResponse;
    public function __construct(
        private readonly FeeEngine $fees,
    ) {}

    public function calculate(Request $request): JsonResponse
    {
        $dto = new FeeAssessmentDto(
            feeType: $request->input('fee_type'),
            accountId: $request->input('account_id'),
            transactionAmount: $request->input('transaction_amount'),
            currency: $request->input('currency', 'SYP'),
            referenceType: $request->input('reference_type'),
            referenceId: $request->input('reference_id'),
            metadata: $request->input('metadata', []),
        );

        try {
            $result = $this->fees->calculate($dto);
            return $this->respond($result);
        } catch (\Exception $e) {
            return $this->respondError('FEE_CALCULATION_FAILED', $e->getMessage());
        }
    }

    public function apply(Request $request): JsonResponse
    {
        $dto = new FeeAssessmentDto(
            feeType: $request->input('fee_type'),
            accountId: $request->input('account_id'),
            transactionAmount: $request->input('transaction_amount'),
            currency: $request->input('currency', 'SYP'),
            referenceType: $request->input('reference_type'),
            referenceId: $request->input('reference_id'),
            metadata: $request->input('metadata', []),
        );

        $result = $this->fees->apply($dto);

        if (!$result->applied && $result->error) {
            return $this->respondError('FEE_APPLY_FAILED', $result->error);
        }

        return $this->respond($result);
    }
}
