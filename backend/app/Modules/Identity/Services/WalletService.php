<?php

declare(strict_types=1);

namespace App\Modules\Identity\Services;

use App\Modules\Identity\Exceptions\InsufficientBalanceException;
use App\Modules\Identity\Exceptions\UserNotFoundException;
use App\Modules\Identity\Exceptions\WalletNotFoundException;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Models\Wallet;
use Illuminate\Support\Str;

final class WalletService
{
    public function createWallet(string $userId, string $currency = 'SYP'): Wallet
    {
        $user = User::find($userId);

        if (! $user) {
            throw new UserNotFoundException($userId);
        }

        return Wallet::create([
            'id' => Str::ulid()->toBase32(),
            'user_id' => $userId,
            'currency' => $currency,
            'balance' => 0,
            'status' => 'active',
        ]);
    }

    public function getWallet(string $id): Wallet
    {
        $wallet = Wallet::find($id);

        if (! $wallet) {
            throw new WalletNotFoundException($id);
        }

        return $wallet;
    }

    public function getUserWallet(string $userId, string $currency = 'SYP'): Wallet
    {
        $wallet = Wallet::where('user_id', $userId)->where('currency', $currency)->first();

        if (! $wallet) {
            throw new WalletNotFoundException("User {$userId} has no {$currency} wallet");
        }

        return $wallet;
    }

    public function getBalance(string $walletId): int
    {
        return $this->getWallet($walletId)->balance;
    }

    public function credit(string $walletId, int $amount): void
    {
        $wallet = $this->getWallet($walletId);
        $wallet->credit($amount);
    }

    public function debit(string $walletId, int $amount): void
    {
        $wallet = $this->getWallet($walletId);

        if ($wallet->balance < $amount) {
            throw new InsufficientBalanceException($walletId, $amount, $wallet->balance);
        }

        $wallet->debit($amount);
    }

    public function freeze(string $walletId): void
    {
        $wallet = $this->getWallet($walletId);
        $wallet->frozen();
    }
}
