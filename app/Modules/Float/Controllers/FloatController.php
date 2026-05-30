<?php

declare(strict_types=1);

namespace Modules\Float\Controllers;

use Modules\Float\DTOs\CreateFloatAccountDto;
use Modules\Float\DTOs\FloatTransactionDto;
use Modules\Float\DTOs\FloatTransferDto;
use Modules\Float\Services\FloatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class FloatController
{
    public function __construct(
        private readonly FloatService $float,
    ) {}

    public function show(string $id): JsonResponse
    {
        try {
            $balance = $this->float->getBalance($id);
            return response()->json(['data' => $balance]);
        } catch (\Modules\Float\Exceptions\FloatAccountNotFoundException $e) {
            return response()->json(['error' => ['code' => 'FLOAT_NOT_FOUND', 'message' => $e->getMessage()]], 404);
        }
    }

    public function adjust(string $id, Request $request): JsonResponse
    {
        try {
            $account = $this->float->adjust(
                $id,
                (int) $request->input('new_balance'),
                $request->input('reason', 'manual adjustment'),
            );
            return response()->json(['data' => $account]);
        } catch (\Exception $e) {
            return response()->json(['error' => ['code' => 'FLOAT_ADJUST_FAILED', 'message' => $e->getMessage()]], 422);
        }
    }

    public function transactions(string $id): JsonResponse
    {
        $txns = app(\Modules\Float\Repositories\FloatRepository::class)->findTransactions($id);
        return response()->json(['data' => $txns]);
    }
}
