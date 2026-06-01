# 09 - WalletService للتكامل (WalletService Integration)

## WalletService

```php
<?php
// app/Services/WalletService.php

namespace App\Services;

use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\WalletNotActiveException;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WalletService
{
    /**
     * الحصول على محفظة المستخدم بعملة محددة
     */
    public function getWallet(int $userId, string $currency): ?Wallet
    {
        return Wallet::where('user_id', $userId)
            ->where('currency', $currency)
            ->first();
    }

    /**
     * قفل صف المحفظة لمنع التحديث المتزامن (Pessimistic Lock)
     * يُستخدم داخل DB::transaction
     */
    public function lockForUpdate(int $walletId): Wallet
    {
        return Wallet::where('id', $walletId)
            ->lockForUpdate()
            ->firstOrFail();
    }

    /**
     * خصم من رصيد المحفظة
     */
    public function decrement(Wallet $wallet, float $amount): void
    {
        if ($wallet->balance < $amount) {
            throw new InsufficientBalanceException(
                available: (float) $wallet->balance,
                required:  $amount,
            );
        }

        $wallet->decrement('balance', $amount);

        Log::info('Wallet decremented', [
            'wallet_id' => $wallet->id,
            'amount'    => $amount,
            'new_balance' => (float) $wallet->fresh()->balance,
        ]);
    }

    /**
     * إضافة إلى رصيد المحفظة
     */
    public function increment(Wallet $wallet, float $amount): void
    {
        $wallet->increment('balance', $amount);

        Log::info('Wallet incremented', [
            'wallet_id' => $wallet->id,
            'amount'    => $amount,
            'new_balance' => (float) $wallet->fresh()->balance,
        ]);
    }

    /**
     * تجميد جزء من الرصيد (لأوامر limit المعلقة)
     */
    public function freeze(Wallet $wallet, float $amount): void
    {
        if ($wallet->available_balance < $amount) {
            throw new InsufficientBalanceException(
                available: (float) $wallet->available_balance,
                required:  $amount,
            );
        }

        $wallet->decrement('balance', $amount);
        $wallet->increment('frozen_balance', $amount);
    }

    /**
     * إلغاء تجميد الرصيد
     */
    public function unfreeze(Wallet $wallet, float $amount): void
    {
        $wallet->increment('balance', $amount);
        $wallet->decrement('frozen_balance', $amount);
    }

    /**
     * التحقق من أن المحفظة نشطة
     */
    public function ensureActive(Wallet $wallet): void
    {
        if (!$wallet || !$wallet->is_active) {
            throw new WalletNotActiveException('المحفظة غير نشطة');
        }
    }

    /**
     * تحويل المبلغ من SYP إلى USD بسعر الصرف الحالي
     */
    public function convertToUsd(float $amount): float
    {
        // سعر الصرف الافتراضي — يتم تحديثه من خدمة الصرافة
        $rate = $this->getExchangeRate('SYP', 'USD');
        return round($amount / $rate, 2);
    }

    /**
     * تحويل المبلغ من USD إلى SYP
     */
    public function convertToSyp(float $amountUsd): float
    {
        $rate = $this->getExchangeRate('SYP', 'USD');
        return round($amountUsd * $rate, 2);
    }

    /**
     * الحصول على سعر الصرف (من قاعدة البيانات أو API)
     */
    private function getExchangeRate(string $from, string $to): float
    {
        // في الإنتاج: يتم جلب السعر من جدول exchange_rates
        // للتبسيط: نستخدم ثابت
        return 13000; // 1 USD = 13000 SYP (مثال)
    }
}
```

## استخدام WalletService في CommodityService

```php
<?php
// مثال: كيف يستخدم CommodityService الـ WalletService

// في executeBuy:
$wallet = $this->walletService->getWallet($user->id, $currency);
$this->walletService->ensureActive($wallet);

// داخل DB::transaction:
$this->walletService->lockForUpdate($wallet->id);
$this->walletService->decrement($wallet, $totalUsd);

// في executeSell:
$totalReceived = $grams * $price['bid'];
$this->walletService->lockForUpdate($wallet->id);
$this->walletService->increment($wallet, $totalReceived);
```

## هيكل محفظة المستخدم

| الحقل | النوع | مثال | شرح |
|-------|-------|------|------|
| id | INT | 1 | — |
| user_id | INT | 42 | FK → users |
| currency | ENUM | USD | SYP أو USD |
| wallet_number | VARCHAR | 631234567890 | رقم فريد |
| balance | DECIMAL | 500.00 | الرصيد المتاح |
| frozen_balance | DECIMAL | 0.00 | رصيد مجمد للأوامر المعلقة |
| is_active | BOOLEAN | true | — |

## دوال WalletService المستخدمة في عمليات الذهب

| الدالة | مكان الاستخدام | ماذا تفعل |
|--------|---------------|-----------|
| getWallet() | Buy + Sell | الحصول على محفظة المستخدم |
| ensureActive() | Buy + Sell | التأكد من أن المحفظة نشطة |
| lockForUpdate() | Buy + Sell | قفل الصف (Pessimistic Lock) |
| decrement() | Buy | خصم المبلغ المصروف |
| increment() | Sell | إضافة حصيلة البيع |
| freeze() | Buy (limit orders) | تجميد الرصيد لأمر limit معلق |
