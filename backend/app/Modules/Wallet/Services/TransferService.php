<?php

namespace App\Modules\Wallet\Services;

use App\Core\Exceptions\InsufficientFundsException;
use App\Core\Exceptions\InvalidCurrencyException;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Modules\Wallet\Events\TransferCompleted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransferService
{
    public function transfer(
        User $sender,
        string $receiverPhone,
        int $amount,
        string $currency,
        string $idempotencyKey,
        ?string $note = null,
    ): Transaction {
        $existing = Transaction::where('idempotency_key', $idempotencyKey)->first();
        if ($existing) {
            return $existing;
        }

        $receiver = User::where('phone', $receiverPhone)->firstOrFail();

        return DB::transaction(function () use ($sender, $receiver, $amount, $currency, $idempotencyKey, $note) {
            $senderWallet = Wallet::where('user_id', $sender->id)
                ->where('currency', $currency)
                ->lockForUpdate()
                ->firstOrFail();

            $receiverWallet = Wallet::where('user_id', $receiver->id)
                ->where('currency', $currency)
                ->lockForUpdate()
                ->firstOrFail();

            if ($senderWallet->id === $receiverWallet->id) {
                throw new InvalidCurrencyException('لا يمكن التحويل إلى نفس المحفظة.');
            }

            if ((int) $senderWallet->balance < $amount) {
                throw new InsufficientFundsException;
            }

            $senderWallet->decrement('balance', $amount);
            $receiverWallet->increment('balance', $amount);

            $transaction = Transaction::create([
                'sender_wallet_id' => $senderWallet->id,
                'receiver_wallet_id' => $receiverWallet->id,
                'amount' => $amount,
                'currency' => $currency,
                'type' => 'transfer',
                'status' => 'completed',
                'idempotency_key' => $idempotencyKey,
                'reference_number' => $this->generateReferenceNumber(),
                'note' => $note,
            ]);

            TransferCompleted::dispatch($transaction);

            return $transaction;
        });
    }

    public function transferByWalletId(
        User $sender,
        int $receiverWalletId,
        int $amount,
        string $currency,
        string $idempotencyKey,
        ?string $note = null,
    ): Transaction {
        $existing = Transaction::where('idempotency_key', $idempotencyKey)->first();
        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($sender, $receiverWalletId, $amount, $currency, $idempotencyKey, $note) {
            $senderWallet = Wallet::where('user_id', $sender->id)
                ->where('currency', $currency)
                ->lockForUpdate()
                ->firstOrFail();

            $receiverWallet = Wallet::where('id', $receiverWalletId)
                ->where('currency', $currency)
                ->lockForUpdate()
                ->firstOrFail();

            if ($senderWallet->id === $receiverWallet->id) {
                throw new InvalidCurrencyException('لا يمكن التحويل إلى نفس المحفظة.');
            }

            if ((int) $senderWallet->balance < $amount) {
                throw new InsufficientFundsException;
            }

            $senderWallet->decrement('balance', $amount);
            $receiverWallet->increment('balance', $amount);

            $transaction = Transaction::create([
                'sender_wallet_id' => $senderWallet->id,
                'receiver_wallet_id' => $receiverWallet->id,
                'amount' => $amount,
                'currency' => $currency,
                'type' => 'transfer',
                'status' => 'completed',
                'idempotency_key' => $idempotencyKey,
                'reference_number' => $this->generateReferenceNumber(),
                'note' => $note,
            ]);

            TransferCompleted::dispatch($transaction);

            return $transaction;
        });
    }

    private function generateReferenceNumber(): string
    {
        $prefix = 'TXN';
        $timestamp = now()->format('ymdHis');
        $random = strtoupper(Str::random(4));

        return "{$prefix}-{$timestamp}-{$random}";
    }
}
