<?php

namespace App\Modules\Wallet\Controllers;

use App\Core\Exceptions\InsufficientFundsException;
use App\Core\Exceptions\InvalidCurrencyException;
use App\Http\Controllers\Controller;
use App\Modules\Wallet\Services\TransferService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransferController extends Controller
{
    public function __construct(
        private readonly TransferService $transferService,
    ) {}

    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'receiver_phone' => 'required_without:receiver_wallet_id|string|exists:users,phone',
            'receiver_wallet_id' => 'required_without:receiver_phone|integer|exists:wallets,id',
            'amount' => 'required|integer|min:100',
            'currency' => 'required|string|in:SYP,USD',
            'idempotency_key' => 'required|string|uuid',
            'note' => 'nullable|string|max:255',
        ]);

        try {
            if (! empty($validated['receiver_wallet_id'])) {
                $transaction = $this->transferService->transferByWalletId(
                    $request->user(),
                    (int) $validated['receiver_wallet_id'],
                    (int) $validated['amount'],
                    $validated['currency'],
                    $validated['idempotency_key'],
                    $validated['note'] ?? null,
                );
            } else {
                $transaction = $this->transferService->transfer(
                    $request->user(),
                    $validated['receiver_phone'],
                    (int) $validated['amount'],
                    $validated['currency'],
                    $validated['idempotency_key'],
                    $validated['note'] ?? null,
                );
            }

            $idempotent = $transaction->wasRecentlyCreated === false;

            return response()->json([
                'success' => true,
                'data' => [
                    'transaction' => [
                        'id' => $transaction->id,
                        'reference_number' => $transaction->reference_number,
                        'amount' => $transaction->amount,
                        'currency' => $transaction->currency,
                        'status' => $transaction->status,
                        'created_at' => $transaction->created_at,
                    ],
                    'idempotent' => $idempotent,
                ],
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'NOT_FOUND', 'message' => 'المستلم أو المحفظة غير موجودة'],
            ], 404);
        } catch (InsufficientFundsException $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'INSUFFICIENT_BALANCE', 'message' => $e->getMessage()],
            ], 402);
        } catch (InvalidCurrencyException $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'INVALID_CURRENCY', 'message' => $e->getMessage()],
            ], 422);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'TRANSFER_FAILED', 'message' => $e->getMessage()],
            ], 400);
        }
    }
}
