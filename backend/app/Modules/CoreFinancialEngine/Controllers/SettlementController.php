<?php

declare(strict_types=1);

namespace Modules\CoreFinancialEngine\Controllers;

use Modules\CoreFinancialEngine\Services\SettlementEngine;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SettlementController
{
    use ApiResponse;
    public function __construct(
        private readonly SettlementEngine $settlement,
    ) {}

    public function batch(Request $request): JsonResponse
    {
        $result = $this->settlement->settleBatch(
            $request->input('transactions', []),
            $request->input('settlement_account_id'),
        );

        if (!$result->success) {
            return $this->respondError($result->errorCode ?? 'SETTLEMENT_FAILED', $result->errorMessage);
        }

        return $this->respond($result);
    }

    public function dailyCutoff(string $date): JsonResponse
    {
        return $this->respond($this->settlement->dailyCutoff($date));
    }
}
