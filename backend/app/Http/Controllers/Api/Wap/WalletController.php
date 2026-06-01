<?php

namespace App\Http\Controllers\Api\Wap;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Modules\Wallet\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WalletController extends Controller
{
    public function __construct(
        private readonly WalletService $walletService,
    ) {}

    public function balance(Request $request): JsonResponse
    {
        $wallets = $this->walletService->getUserWallets($request->user()->id);

        if ($request->query('format') === 'minimal') {
            return response()->json([
                'success' => true,
                'data' => $wallets->map(fn($w) => [
                    'balance' => $w->balance,
                    'currency' => $w->currency,
                    'updated_at' => $w->updated_at,
                ]),
            ]);
        }

        return response()->json(['success' => true, 'data' => $wallets]);
    }

    public function transfer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'receiver_phone' => 'required|string|exists:users,phone',
            'amount' => 'required|integer|min:100',
            'currency' => 'required|in:SYP,USD',
            'idempotency_key' => 'required|string|uuid',
            'note' => 'nullable|string|max:255',
        ]);

        $existing = Transaction::where('idempotency_key', $validated['idempotency_key'])->first();
        if ($existing) {
            return response()->json([
                'success' => true,
                'data' => ['transaction' => $existing, 'idempotent' => true],
            ]);
        }

        $sender = $request->user();
        $receiver = \App\Models\User::where('phone', $validated['receiver_phone'])->first();

        $senderWallet = $sender->wallets()->where('currency', $validated['currency'])->first();
        $receiverWallet = $receiver->wallets()->where('currency', $validated['currency'])->first();

        if (!$senderWallet || !$receiverWallet) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'WALLET_NOT_FOUND', 'message' => 'المحفظة غير موجودة'],
            ], 404);
        }

        if ($senderWallet->balance < $validated['amount']) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'INSUFFICIENT_BALANCE', 'message' => 'الرصيد غير كافٍ'],
            ], 402);
        }

        $transaction = DB::transaction(function () use ($senderWallet, $receiverWallet, $validated) {
            $senderWallet->decrement('balance', $validated['amount']);
            $receiverWallet->increment('balance', $validated['amount']);

            return Transaction::create([
                'sender_wallet_id' => $senderWallet->id,
                'receiver_wallet_id' => $receiverWallet->id,
                'amount' => $validated['amount'],
                'currency' => $validated['currency'],
                'type' => 'transfer',
                'status' => 'completed',
                'idempotency_key' => $validated['idempotency_key'],
                'note' => $validated['note'] ?? null,
            ]);
        });

        return response()->json([
            'success' => true,
            'data' => ['transaction' => $transaction, 'idempotent' => false],
        ]);
    }
}
