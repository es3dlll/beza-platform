<?php

declare(strict_types=1);

namespace Modules\Wallet\Repositories;

use Illuminate\Support\Collection;
use Modules\Wallet\Models\Wallet;
use Modules\Wallet\Models\WalletTransaction;

final class WalletRepository
{
    public function findById(string $id): ?Wallet
    {
        return Wallet::with('user')->find($id);
    }

    public function findByUser(string $userId): Collection
    {
        return Wallet::where('user_id', $userId)->get();
    }

    public function findByUserAndCurrency(string $userId, string $currency): ?Wallet
    {
        return Wallet::where('user_id', $userId)
            ->where('currency', $currency)
            ->first();
    }

    public function save(Wallet $wallet): Wallet
    {
        $wallet->save();
        return $wallet;
    }

    public function findTransactions(string $walletId, int $limit = 20): Collection
    {
        return WalletTransaction::where('wallet_id', $walletId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function saveTransaction(WalletTransaction $txn): WalletTransaction
    {
        $txn->save();
        return $txn;
    }

    public function todayTotal(string $walletId, string $type): int
    {
        return (int) WalletTransaction::where('wallet_id', $walletId)
            ->where('type', $type)
            ->whereDate('created_at', today())
            ->sum('amount');
    }
}
