# 09 - WalletService كامل (لعملية عرض الرصيد)

```php
<?php
// app/Services/WalletService.php

namespace App\Services;

use App\Models\Wallet;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class WalletService
{
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
     * الحصول على جميع محافظ المستخدم (SYP + USD)
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
     * خصم مبلغ من المحفظة
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

        // مسح Cache الرصيد
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

        // مسح Cache الرصيد
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
}
```

## سبب استخدام Cache

| بدون Cache | مع Cache (Redis) |
|-----------|-----------------|
| كل طلب يذهب إلى MySQL | الطلبات المتكررة تذهب إلى Redis |
| 1000 طلب = 1000 Query | 1000 طلب = 1 Query + 999 Redis GET |
| استجابة ~10ms | استجابة ~1ms |
| ضغط على DB | DB مطمئن |
