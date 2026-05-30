<?php

declare(strict_types=1);

namespace Modules\Float\Controllers;

use App\Support\ApiResponse;
use Modules\Float\DTOs\CreateFloatAccountDto;
use Modules\Float\DTOs\FloatTransactionDto;
use Modules\Float\DTOs\FloatTransferDto;
use Modules\Float\Services\FloatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class FloatController
{
    use ApiResponse;

    public function __construct(
        private readonly FloatService $float,
    ) {}

    public function show(string $id): JsonResponse
    {
        try {
            $balance = $this->float->getBalance($id);
            return $this->respond($balance);
        } catch (\Modules\Float\Exceptions\FloatAccountNotFoundException $e) {
            return $this->respondError('FLOAT_NOT_FOUND', $e->getMessage(), null, 404);
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
            return $this->respond($account);
        } catch (\Exception $e) {
            return $this->respondError('FLOAT_ADJUST_FAILED', $e->getMessage(), null, 422);
        }
    }

    public function transactions(string $id): JsonResponse
    {
        $txns = app(\Modules\Float\Repositories\FloatRepository::class)->findTransactions($id);
        return $this->respond($txns);
    }
}
