# 10 - Service Layer الكامل

```php
<?php
// app/Services/CardDepositService.php

namespace App\Services;

use App\Events\CardDepositCompleted;
use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\InvalidPinException;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class CardDepositService
{
    public function __construct(
        private readonly WalletService $walletService
    ) {}

    public function process(
        User    $user,
        float   $amount,
        string  $currency,
        string  $pin,
    ): array {

        // ─── 1. التحقق من PIN ───
        if (!Hash::check($pin, $user->pin_code)) {
            throw new InvalidPinException();
        }

        // ─── 2. الحصول على المحفظة ───
        $wallet = $this->walletService->getWallet($user->id, $currency);
        if (!$wallet || !$wallet->is_active) {
            throw new WalletNotActiveException();
        }

        // ─── 3. التحقق من الرصيد ───
        if ($wallet->available_balance < $amount) {
            throw new InsufficientBalanceException(
                available: (float) $wallet->available_balance,
                required:  $amount,
            );
        }

        // ─── 4. التحقق من الحد اليومي ───
        $dailyLimit = $currency === 'USD' ? 2000 : 2000000;
        $dailyTotal = Transaction::where('from_wallet_id', $wallet->id)
            ->whereDate('created_at', today())
            ->where('status', 'completed')
            ->sum('amount');

        if (($dailyTotal + $amount) > $dailyLimit) {
            throw new DailyLimitExceededException($dailyLimit, (float) $dailyTotal);
        }

        // ─── 5. حساب المبلغ بـ USD ───
        $amountInUsd = $currency === 'USD'
            ? $amount
            : $this->walletService->convertToUsd($amount);

        // ─── 6. التنفيذ الذري ───
        $transaction = DB::transaction(function () use (
            $wallet, $amount, $amountInUsd, $currency, $user
        ) {
            $this->walletService->lockForUpdate($wallet->id);
            $this->walletService->decrement($wallet, $amount);

            $txn = Transaction::create([
                'from_wallet_id'  => $wallet->id,
                'to_wallet_id'    => null,
                'amount'          => $amount,
                'amount_in_usd'   => $amountInUsd,
                'type'            => 'card_deposit',
                'status'          => 'completed',
                'reference_number'=> Transaction::generateReferenceNumber(),
                'fee'             => round($amount * 0.025, 2),
                'completed_at'    => now(),
            ]);

            return $txn;
        }, attempts: 3);

        // ─── 7. إرسال الإشعارات ───
        try {
            CardDepositCompleted::dispatch($transaction);
        } catch (\Throwable $e) {
            Log::warning('فشل إرسال الإشعار', [
                'transaction_id' => $transaction->id,
                'error'          => $e->getMessage(),
            ]);
        }

        $wallet->refresh();

        return [
            'transaction' => $transaction,
            'new_balance' => (float) $wallet->balance,
        ];
    }
}
```

## تدفق Service Layer

```
1. التحقق من PIN
2. الحصول على المحفظة
3. التحقق من الرصيد
4. التحقق من الحد اليومي
5. حساب المبلغ بـ USD
6. DB::transaction {
   ├── lockForUpdate(wallet)
   ├── decrement(wallet)
   └── Transaction::create()
   }
7. dispatch(Event)
8. Return response
```
