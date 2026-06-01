# 10 - CreateWalletService كامل

```php
<?php
// app/Services/CreateWalletService.php

namespace App\Services;

use App\Events\WalletCreated;
use App\Exceptions\UserNotActiveException;
use App\Exceptions\WalletsAlreadyExistException;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateWalletService
{
    public function __construct(
        private readonly WalletService $walletService
    ) {}

    /**
     * إنشاء محفظتين (SYP + USD) لمستخدم جديد
     * مع إضافة هدية 5$ إلى محفظة USD
     *
     * @param User $user المستخدم الجديد
     * @return array{wallets: array, bonus_transaction: Transaction|null}
     *
     * @throws UserNotActiveException
     * @throws WalletsAlreadyExistException
     * @throws \Throwable
     */
    public function createWallets(User $user): array
    {
        // ─── 1. التحقق من أن المستخدم نشط ───
        if ($user->status !== 'active') {
            throw new UserNotActiveException('لا يمكن إنشاء محفظة لمستخدم غير نشط');
        }

        // ─── 2. التحقق من عدم وجود محافظ مسبقة ───
        if ($user->wallets()->exists()) {
            throw new WalletsAlreadyExistException('المستخدم لديه محافظ مسبقاً');
        }

        // ─── 3. توليد أرقام المحافظ ───
        $sypNumber = $this->generateWalletNumber('SYP');
        $usdNumber = $this->generateWalletNumber('USD');

        // ─── 4. إنشاء المحافظ ───
        $sypWallet = Wallet::create([
            'user_id'       => $user->id,
            'currency'      => 'SYP',
            'wallet_number' => $sypNumber,
            'balance'       => 0.00,
            'frozen_balance'=> 0.00,
            'is_active'     => true,
        ]);

        $usdWallet = Wallet::create([
            'user_id'       => $user->id,
            'currency'      => 'USD',
            'wallet_number' => $usdNumber,
            'balance'       => 0.00,
            'frozen_balance'=> 0.00,
            'is_active'     => true,
        ]);

        // ─── 5. إضافة هدية 5$ (ضمن Transaction) ───
        $bonusTransaction = null;

        DB::transaction(function () use ($usdWallet, &$bonusTransaction) {
            // 5a. إضافة الرصيد
            $this->walletService->increment($usdWallet, 5.00);

            // 5b. تسجيل معاملة الإيداع
            $bonusTransaction = Transaction::create([
                'to_wallet_id'    => $usdWallet->id,
                'amount'          => 5.00,
                'amount_in_usd'   => 5.00,
                'type'            => 'deposit',
                'status'          => 'completed',
                'reference_number'=> Transaction::generateReferenceNumber(),
                'description'     => 'هدية ترحيبية — 5$',
                'fee'             => 0.00,
                'completed_at'    => now(),
            ]);
        }, attempts: 3);

        // ─── 6. إرسال حدث إنشاء المحفظة ───
        try {
            WalletCreated::dispatch($user, $sypWallet, $usdWallet, $bonusTransaction);
        } catch (\Throwable $e) {
            Log::warning('فشل إرسال حدث WalletCreated', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
        }

        return [
            'wallets' => [
                'syp' => $sypWallet,
                'usd' => $usdWallet,
            ],
            'bonus_transaction' => $bonusTransaction,
        ];
    }

    /**
     * توليد رقم محفظة فريد
     *
     * @param string $currency SYP أو USD
     * @return string رقم المحفظة المكون من 12 رقم
     */
    public function generateWalletNumber(string $currency): string
    {
        $prefix = $currency === 'SYP' ? '62' : '63';
        $digits = $prefix . str_pad((string) random_int(0, 9999999999), 10, '0', STR_PAD_LEFT);

        // ضمان التفرد — إعادة المحاولة إذا كان الرقم موجوداً
        while (Wallet::where('wallet_number', $digits)->exists()) {
            $digits = $prefix . str_pad((string) random_int(0, 9999999999), 10, '0', STR_PAD_LEFT);
        }

        return $digits;
    }

    /**
     * إعادة إنشاء المحافظ (للاستخدام الإداري فقط)
     */
    public function recreateWallets(User $user): array
    {
        // حذف المحافظ القديمة
        $user->wallets()->delete();

        // إنشاء جديدة
        return $this->createWallets($user);
    }
}
```

## تدفق CreateWalletService خطوة بخطوة

```
1. التحقق من نشاط المستخدم
         │
2. التحقق من عدم وجود محافظ مسبقة
         │
3. توليد أرقام المحافظ (62xxxx / 63xxxx)
         │
4. Wallet::create(SYP, 0)
         │
5. Wallet::create(USD, 0)
         │
6. DB::transaction {
    ├── increment(USD, +5)
    └── Transaction::create(deposit, 5)
   }
         │
7. dispatch(WalletCreated)    ← Async
         │
8. Return wallets + transaction
```

## لماذا 5$ هدية؟

| السبب | التفصيل |
|-------|---------|
| تحفيز المستخدم | يشعر بقيمة فورية عند التسجيل |
| تجربة المنتج | يستطيع تجربة التحويل أو الصرافة بدون شحن رصيد |
| تكلفة اكتساب | 5$ هي تكلفة تسويق منخفضة مقارنة بقيمة المستخدم مدى الحياة |
