<?php

declare(strict_types=1);

namespace App\Modules\FinancialCore\Controllers;

use App\Modules\FinancialCore\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class FinancialController extends Controller
{
    public function __construct(
        private readonly TransactionService $transactionService,
    ) {}

    public function transfer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_wallet_id' => 'required|string',
            'to_wallet_id' => 'required|string',
            'amount' => 'required|integer|min:1',
            'currency' => 'sometimes|string|in:SYP,USD',
            'idempotency_key' => 'sometimes|string',
            'fee' => 'sometimes|array',
            'fee.rule_id' => 'required_with:fee|string',
            'fee.description' => 'sometimes|string',
            'fee.description_ar' => 'sometimes|string',
        ]);

        $result = $this->transactionService->transfer(
            fromWalletId: $validated['from_wallet_id'],
            toWalletId: $validated['to_wallet_id'],
            amount: (int) $validated['amount'],
            currency: $validated['currency'] ?? 'SYP',
            idempotencyKey: $validated['idempotency_key'] ?? null,
            fee: $validated['fee'] ?? null,
        );

        return response()->json(['data' => $result], 201);
    }

    public function deposit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'wallet_id' => 'required|string',
            'amount' => 'required|integer|min:1',
            'currency' => 'sometimes|string|in:SYP,USD',
            'idempotency_key' => 'sometimes|string',
        ]);

        $result = $this->transactionService->deposit(
            walletId: $validated['wallet_id'],
            amount: (int) $validated['amount'],
            currency: $validated['currency'] ?? 'SYP',
            idempotencyKey: $validated['idempotency_key'] ?? null,
        );

        return response()->json(['data' => $result], 201);
    }

    public function withdraw(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'wallet_id' => 'required|string',
            'amount' => 'required|integer|min:1',
            'currency' => 'sometimes|string|in:SYP,USD',
            'idempotency_key' => 'sometimes|string',
        ]);

        $result = $this->transactionService->withdraw(
            walletId: $validated['wallet_id'],
            amount: (int) $validated['amount'],
            currency: $validated['currency'] ?? 'SYP',
            idempotencyKey: $validated['idempotency_key'] ?? null,
        );

        return response()->json(['data' => $result], 201);
    }

    public function reverse(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
            'reason_ar' => 'required|string|max:500',
            'idempotency_key' => 'sometimes|string',
        ]);

        $result = $this->transactionService->reverse(
            transactionId: $id,
            reason: $validated['reason'],
            reasonAr: $validated['reason_ar'],
            idempotencyKey: $validated['idempotency_key'] ?? null,
        );

        return response()->json(['data' => $result]);
    }

    public function transactions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'wallet_id' => 'required|string',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $transactions = $this->transactionService->getWalletTransactions(
            walletId: $validated['wallet_id'],
            perPage: (int) ($validated['per_page'] ?? 15),
        );

        return response()->json(['data' => $transactions]);
    }

    public function show(string $id): JsonResponse
    {
        $transaction = $this->transactionService->getTransaction($id);
        return response()->json(['data' => $transaction]);
    }
}
