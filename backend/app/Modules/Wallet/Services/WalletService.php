<?php

namespace App\Modules\Wallet\Services;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Collection;

class WalletService
{
    public function createWallet(User $user, string $currency): Wallet
    {
        return Wallet::create([
            'user_id' => $user->id,
            'currency' => strtoupper($currency),
            'balance' => 0,
            'blocked' => 0,
            'status' => 'active',
        ]);
    }

    public function createDefaultWallets(User $user): array
    {
        return [
            $this->createWallet($user, 'SYP'),
            $this->createWallet($user, 'USD'),
        ];
    }

    public function getUserWallets(int $userId): Collection
    {
        return Wallet::where('user_id', $userId)->get();
    }
}
