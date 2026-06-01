# 09 - WalletService — QR Transfer (T2)

يستخدم WalletService لخصم رصيد المرسل وتجميد المبلغ أثناء مسح QR.

```php
<?php
// app/Services/WalletService.php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

class WalletService
{
    public function decrement(Wallet $wallet, float $amount): void
    {
        $affected = DB::update(
            'UPDATE wallets SET balance = balance - ? WHERE id = ? AND balance >= ? AND is_active = ?',
            [$amount, $wallet->id, $amount, true]
        );

        if ($affected === 0) {
            throw new \RuntimeException('فشل خصم الرصيد — رصيد غير كافٍ أو المحفظة موقوفة');
        }
    }

    public function increment(Wallet $wallet, float $amount): void
    {
        DB::update(
            'UPDATE wallets SET balance = balance + ? WHERE id = ? AND is_active = ?',
            [$amount, $wallet->id, true]
        );
    }

    public function lockForUpdate(int $walletId): ?Wallet
    {
        return Wallet::where('id', $walletId)
            ->lockForUpdate()
            ->first();
    }

    public function getWallet(int $userId, string $currency): ?Wallet
    {
        return Wallet::where('user_id', $userId)
            ->where('currency', $currency)
            ->first();
    }

    public function getBalance(int $userId, string $currency): float
    {
        $wallet = $this->getWallet($userId, $currency);
        return $wallet ? (float) $wallet->available_balance : 0.00;
    }

    public function freeze(Wallet $wallet, float $amount): void
    {
        DB::update(
            'UPDATE wallets SET frozen_balance = frozen_balance + ?, balance = balance - ? WHERE id = ? AND balance >= ?',
            [$amount, $amount, $wallet->id, $amount]
        );
    }

    public function unfreeze(Wallet $wallet, float $amount): void
    {
        DB::update(
            'UPDATE wallets SET frozen_balance = frozen_balance - ?, balance = balance + ? WHERE id = ? AND frozen_balance >= ?',
            [$amount, $amount, $wallet->id, $amount]
        );
    }

    public function convertToUsd(float $amountSyp, float $rate = 13000): float
    {
        return round($amountSyp / $rate, 2);
    }

    public function convertToSyp(float $amountUsd, float $rate = 13000): float
    {
        return round($amountUsd * $rate, 2);
    }
}
```
