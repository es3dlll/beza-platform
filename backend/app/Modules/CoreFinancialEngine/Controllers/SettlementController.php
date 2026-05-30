<?php

namespace Modules\CoreFinancialEngine\Controllers;

use Modules\CoreFinancialEngine\Services\SettlementEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SettlementController
{
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
            return response()->json(['error' => $result->errorMessage, 'code' => $result->errorCode], 422);
        }

        return response()->json(['data' => $result]);
    }

    public function dailyCutoff(string $date): JsonResponse
    {
        return response()->json(['data' => $this->settlement->dailyCutoff($date)]);
    }
}
