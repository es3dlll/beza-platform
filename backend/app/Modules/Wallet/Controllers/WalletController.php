<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Controllers;

use App\Modules\Wallet\Services\DynamicLimitService;
use App\Modules\Wallet\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class WalletController
{
    public function __construct(
        private readonly DynamicLimitService $limitService,
        private readonly WalletService $walletService,
    ) {}

    public function limits(Request $request): JsonResponse
    {
        $userId = $request->user()->id ?? $request->input('user_id');
        $tier = $request->input('tier', 'T1');

        $limits = $this->limitService->getLimits($userId, $tier);

        return response()->json([
            'data' => $limits->toArray(),
        ]);
    }

    public function requestIncrease(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|string',
            'new_daily_limit' => 'required|integer|min:1000000',
            'reason' => 'required|string|max:500',
        ]);

        return response()->json([
            'message' => 'تم استلام طلب زيادة الحد وهو قيد المراجعة',
            'data' => [
                'user_id' => $validated['user_id'],
                'requested_limit' => $validated['new_daily_limit'],
                'status' => 'pending_review',
            ],
        ], 202);
    }

    public function balance(Request $request): JsonResponse
    {
        $walletId = $request->user()->wallet_id ?? $request->input('wallet_id');
        $balance = $this->walletService->getBalance($walletId);

        return response()->json([
            'data' => $balance->toArray(),
        ]);
    }
}
