# 09 - WalletService كامل

```php
<?php
// app/Services/WalletService.php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class WalletService
{
    /**
     * خصم مبلغ من المحفظة مع ضمان الرصيد الكافي
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

        Cache::forget("balance:user:{$wallet->user_id}");
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

        Cache::forget("balance:user:{$wallet->user_id}");
    }

    /**
     * الحصول على محفظة مع قفل Pessimistic Lock
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
     * الحصول على جميع محافظ المستخدم
     */
    public function getUserWallets(int $userId): array
    {
        return Wallet::where('user_id', $userId)
            ->whereIn('currency', ['SYP', 'USD'])
            ->get()
            ->keyBy('currency')
            ->toArray();
    }

    /**
     * الحصول على رصيد المستخدم المتاح
     */
    public function getBalance(int $userId, string $currency): float
    {
        $wallet = $this->getWallet($userId, $currency);
        return $wallet ? (float) $wallet->available_balance : 0.00;
    }

    /**
     * تجميد رصيد
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
}
```

## الوظائف المستخدمة في W3

| الوظيفة | الاستخدام |
|---------|-----------|
| `decrement()` | خصم المبلغ + الرسوم من محفظة المصدر |
| `increment()` | إضافة المبلغ المحول إلى محفظة الوجهة |
| `lockForUpdate()` | قفل Pessimistic للمحفظتين |
| `getWallet()` | الحصول على المحفظتين |
