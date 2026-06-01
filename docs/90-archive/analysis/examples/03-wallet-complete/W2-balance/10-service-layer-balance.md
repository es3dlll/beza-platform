# 10 - BalanceService كامل

```php
<?php
// app/Services/BalanceService.php

namespace App\Services;

use App\Exceptions\WalletsNotFoundException;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class BalanceService
{
    /**
     * مدة التخزين المؤقت للرصيد (ثانية)
     */
    private const CACHE_TTL = 30;

    public function __construct(
        private readonly WalletService $walletService
    ) {}

    /**
     * الحصول على رصيد المستخدم لكل العملات
     *
     * يستخدم Cache لتقليل ضغط DB:
     * - إذا كان الرصيد في Cache → إرجاعه
     * - إذا لم يكن → Query من DB → تخزين في Cache
     *
     * @param User $user
     * @return array
     *
     * @throws WalletsNotFoundException
     */
    public function getBalance(User $user): array
    {
        $cacheKey = "balance:user:{$user->id}";

        // ─── 1. محاولة القراءة من Cache ───
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            Log::debug('Balance Cache HIT', [
                'user_id' => $user->id,
            ]);

            return $cached;
        }

        Log::debug('Balance Cache MISS', [
            'user_id' => $user->id,
        ]);

        // ─── 2. Cache MISS → القراءة من DB ───
        $wallets = $this->walletService->getUserWallets($user->id);

        if (empty($wallets)) {
            throw new WalletsNotFoundException();
        }

        // ─── 3. تخزين في Cache ───
        Cache::put($cacheKey, $wallets, self::CACHE_TTL);

        return $wallets;
    }

    /**
     * مسح Cache الرصيد لمستخدم معين
     * يُستدعى بعد أي معاملة تغيّر الرصيد
     */
    public function clearBalanceCache(int $userId): void
    {
        Cache::forget("balance:user:{$userId}");

        Log::debug('Balance Cache cleared', [
            'user_id' => $userId,
        ]);
    }

    /**
     * الحصول على رصيد محدد (SYP فقط)
     */
    public function getSypBalance(User $user): ?array
    {
        $wallets = $this->getBalance($user);
        $syp = collect($wallets)->firstWhere('currency', 'SYP');

        return $syp ? [
            'balance'        => (float) $syp['balance'],
            'frozen'         => (float) $syp['frozen_balance'],
            'available'      => (float) $syp['balance'] - (float) $syp['frozen_balance'],
            'wallet_number'  => $syp['wallet_number'],
        ] : null;
    }

    /**
     * الحصول على رصيد محدد (USD فقط)
     */
    public function getUsdBalance(User $user): ?array
    {
        $wallets = $this->getBalance($user);
        $usd = collect($wallets)->firstWhere('currency', 'USD');

        return $usd ? [
            'balance'        => (float) $usd['balance'],
            'frozen'         => (float) $usd['frozen_balance'],
            'available'      => (float) $usd['balance'] - (float) $usd['frozen_balance'],
            'wallet_number'  => $usd['wallet_number'],
        ] : null;
    }
}
```

## تدفق BalanceService خطوة بخطوة

```
1. إنشاء Cache Key: "balance:user:{id}"
         │
2. محاولة القراءة من Redis
         │
         ├── Found → إرجاع البيانات المخزنة (CACHE HIT)
         │
         └── Not Found → متابعة (CACHE MISS)
                  │
3. Query MySQL للحصول على المحافظ
         │
4. تخزين النتيجة في Redis لمدة 30 ثانية
         │
5. إرجاع البيانات
```

## إبطال Cache

يتم إبطال Cache عند:
- تنفيذ تحويل (decrement/increment)
- تنفيذ صرافة
- إيداع أو سحب
- يدوياً عبر `BalanceService::clearBalanceCache()`
