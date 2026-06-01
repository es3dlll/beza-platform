# 09 - WalletService كامل

```php
<?php
// app/Services/WalletService.php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

class WalletService
{
    /**
     * خصم مبلغ من المحفظة مع قفل Pessimistic Lock
     *
     * @throws \RuntimeException عند فشل الخصم
     */
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

    /**
     * إضافة مبلغ إلى المحفظة
     */
    public function increment(Wallet $wallet, float $amount): void
    {
        DB::update(
            'UPDATE wallets SET balance = balance + ? WHERE id = ? AND is_active = ?',
            [$amount, $wallet->id, true]
        );
    }

    /**
     * الحصول على رصيد محفظة مع قفل Pessimistic Lock (للتحديثات)
     */
    public function lockForUpdate(int $walletId): ?Wallet
    {
        return Wallet::where('id', $walletId)
            ->lockForUpdate()
            ->first();
    }

    /**
     * الحصول على محفظة مستخدم بعملة معينة
     */
    public function getWallet(int $userId, string $currency): ?Wallet
    {
        return Wallet::where('user_id', $userId)
            ->where('currency', $currency)
            ->first();
    }

    /**
     * الحصول على رصيد المستخدم
     */
    public function getBalance(int $userId, string $currency): float
    {
        $wallet = $this->getWallet($userId, $currency);

        return $wallet ? (float) $wallet->available_balance : 0.00;
    }

    /**
     * تجميد رصيد (للمعاملات المعلقة)
     */
    public function freeze(Wallet $wallet, float $amount): void
    {
        DB::update(
            'UPDATE wallets SET frozen_balance = frozen_balance + ?, balance = balance - ? WHERE id = ? AND balance >= ?',
            [$amount, $amount, $wallet->id, $amount]
        );
    }

    /**
     * إلغاء تجميد الرصيد
     */
    public function unfreeze(Wallet $wallet, float $amount): void
    {
        DB::update(
            'UPDATE wallets SET frozen_balance = frozen_balance - ?, balance = balance + ? WHERE id = ? AND frozen_balance >= ?',
            [$amount, $amount, $wallet->id, $amount]
        );
    }

    /**
     * حساب سعر الصرف SYP → USD
     */
    public function convertToUsd(float $amountSyp, float $rate = 13000): float
    {
        return round($amountSyp / $rate, 2);
    }

    /**
     * حساب سعر الصرف USD → SYP
     */
    public function convertToSyp(float $amountUsd, float $rate = 13000): float
    {
        return round($amountUsd * $rate, 2);
    }
}
```

## سبب استخدام SQL Raw بدلاً من Eloquent

| الطريقة | المشكلة |
|---------|---------|
| `Wallet::find($id)->decrement('balance', $amount)` | لا يضمن `balance >= amount` — قد يصبح الرصيد سالباً |
| `Wallet::where('id', $id)->decrement(...)` | لا يدعم `lockForUpdate` في نفس الاستعلام |
| **SQL Raw** (`UPDATE ... WHERE balance >= ?`) | يضمن عدم تجاوز الرصيد في استعلام واحد |
