# 10 - TransferService كامل

```php
<?php
// app/Services/TransferService.php

namespace App\Services;

use App\Events\TransactionCompleted;
use App\Exceptions\DailyLimitExceededException;
use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\InvalidPinException;
use App\Exceptions\RecipientNotFoundException;
use App\Exceptions\SelfTransferException;
use App\Exceptions\WalletNotActiveException;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class TransferService
{
    public function __construct(
        private readonly WalletService $walletService
    ) {}

    /**
     * تنفيذ التحويل الكامل
     *
     * @param User   $fromUser    المستخدم المرسل
     * @param string $toPhone     رقم هاتف المستلم
     * @param float  $amount      المبلغ
     * @param string $currency    العملة (SYP/USD)
     * @param string $pin         PIN تأكيد
     * @param string|null $description وصف
     *
     * @return array{transaction: Transaction, new_balance: float}
     *
     * @throws SelfTransferException
     * @throws InvalidPinException
     * @throws RecipientNotFoundException
     * @throws InsufficientBalanceException
     * @throws DailyLimitExceededException
     * @throws WalletNotActiveException
     * @throws \Throwable
     */
    public function transfer(
        User    $fromUser,
        string  $toPhone,
        float   $amount,
        string  $currency,
        string  $pin,
        ?string $description = null,
    ): array {

        // ─── 1. منع التحويل للنفس ───
        $toUser = User::where('phone', $toPhone)
            ->where('status', 'active')
            ->first();

        if (!$toUser) {
            throw new RecipientNotFoundException();
        }

        if ($fromUser->id === $toUser->id) {
            throw new SelfTransferException();
        }

        // ─── 2. التحقق من PIN ───
        if (!Hash::check($pin, $fromUser->pin_code)) {
            throw new InvalidPinException();
        }

        // ─── 3. الحصول على المحافظ ───
        $fromWallet = $this->walletService->getWallet($fromUser->id, $currency);
        $toWallet   = $this->walletService->getWallet($toUser->id, $currency);

        if (!$fromWallet || !$fromWallet->is_active) {
            throw new WalletNotActiveException('محفظة المرسل غير نشطة');
        }
        if (!$toWallet || !$toWallet->is_active) {
            throw new WalletNotActiveException('محفظة المستلم غير نشطة');
        }

        // ─── 4. التحقق من الرصيد ───
        if ($fromWallet->available_balance < $amount) {
            throw new InsufficientBalanceException(
                available: (float) $fromWallet->available_balance,
                required:  $amount,
            );
        }

        // ─── 5. التحقق من الحد اليومي ───
        $dailyLimit = $currency === 'USD' ? 2000 : 2000000;

        $dailyTotal = Transaction::where('from_wallet_id', $fromWallet->id)
            ->where('type', 'transfer')
            ->where('status', 'completed')
            ->whereDate('created_at', today())
            ->sum('amount');

        if (($dailyTotal + $amount) > $dailyLimit) {
            throw new DailyLimitExceededException($dailyLimit, (float) $dailyTotal);
        }

        // ─── 6. حساب المبلغ بـ USD ───
        $amountInUsd = $currency === 'USD'
            ? $amount
            : $this->walletService->convertToUsd($amount);

        // ─── 7. التنفيذ الذري (Atomic DB Transaction) ───
        $transaction = DB::transaction(function () use (
            $fromWallet, $toWallet, $amount, $amountInUsd,
            $currency, $description, $fromUser, $toUser
        ) {
            // 7a. قفل المحافظ لمنع سباق التوقيت
            $this->walletService->lockForUpdate($fromWallet->id);
            $this->walletService->lockForUpdate($toWallet->id);

            // 7b. خصم من المرسل
            $this->walletService->decrement($fromWallet, $amount);

            // 7c. إضافة للمستلم
            $this->walletService->increment($toWallet, $amount);

            // 7d. تسجيل المعاملة
            $txn = Transaction::create([
                'from_wallet_id'  => $fromWallet->id,
                'to_wallet_id'    => $toWallet->id,
                'amount'          => $amount,
                'amount_in_usd'   => $amountInUsd,
                'type'            => 'transfer',
                'status'          => 'completed',
                'reference_number'=> Transaction::generateReferenceNumber(),
                'description'     => $description,
                'fee'             => 0.00,
                'completed_at'    => now(),
            ]);

            return $txn;
        }, attempts: 3); // إعادة المحاولة 3 مرات في حال Deadlock

        // ─── 8. إرسال الإشعارات (Async) ───
        try {
            TransactionCompleted::dispatch($transaction, $fromUser, $toUser);
        } catch (\Throwable $e) {
            // لا نوقف الاستجابة إذا فشل الإشعار
            Log::warning('فشل إرسال إشعار التحويل', [
                'transaction_id' => $transaction->id,
                'error'          => $e->getMessage(),
            ]);
        }

        // ─── 9. إرجاع النتيجة ───
        $fromWallet->refresh();

        return [
            'transaction' => $transaction,
            'new_balance' => (float) $fromWallet->balance,
        ];
    }
}
```

## تدفق TransferService خطوة بخطوة

```
1. منع التحويل للنفس
         │
2. التحقق من PIN
         │
3. الحصول على المحافظ
         │
4. التحقق من الرصيد
         │
5. التحقق من الحد اليومي
         │
6. حساب المبلغ بـ USD
         │
7. DB::transaction {                 ← Atomic
   ├── lockForUpdate(fromWallet)     ← Pessimistic Lock
   ├── lockForUpdate(toWallet)       ← Pessimistic Lock
   ├── decrement(fromWallet)
   ├── increment(toWallet)
   └── Transaction::create()
         │
8. dispatch(TransactionCompleted)    ← Async
         │
9. Return response
```
