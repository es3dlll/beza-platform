# 10 - ExchangeService كامل

```php
<?php
// app/Services/ExchangeService.php

namespace App\Services;

use App\Events\ExchangeCompleted;
use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\MinimumAmountException;
use App\Exceptions\SameCurrencyExchangeException;
use App\Exceptions\WalletNotActiveException;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExchangeService
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly RateService   $rateService,
    ) {}

    /**
     * تنفيذ عملية صرافة بين محفظة SYP و USD
     *
     * @param User   $user         المستخدم
     * @param string $fromCurrency عملة المصدر (SYP/USD)
     * @param string $toCurrency   عملة الوجهة (SYP/USD)
     * @param float  $amount       المبلغ المراد تحويله
     *
     * @return array{
     *   transaction: Transaction,
     *   from_currency: string,
     *   to_currency: string,
     *   converted_amount: float,
     *   rate: float,
     *   fee_percentage: float,
     *   new_balances: array{syp: float, usd: float}
     * }
     *
     * @throws SameCurrencyExchangeException
     * @throws MinimumAmountException
     * @throws WalletNotActiveException
     * @throws InsufficientBalanceException
     * @throws \Throwable
     */
    public function exchange(
        User   $user,
        string $fromCurrency,
        string $toCurrency,
        float  $amount,
    ): array {

        // ─── 1. التحقق من اختلاف العملات ───
        if ($fromCurrency === $toCurrency) {
            throw new SameCurrencyExchangeException();
        }

        // ─── 2. التحقق من الحد الأدنى ───
        $minAmount = config("beza.exchange.min_amounts.{$fromCurrency}", 0);
        if ($amount < $minAmount) {
            throw new MinimumAmountException($minAmount, $fromCurrency);
        }

        // ─── 3. الحصول على المحافظ ───
        $fromWallet = $this->walletService->getWallet($user->id, $fromCurrency);
        $toWallet   = $this->walletService->getWallet($user->id, $toCurrency);

        if (!$fromWallet || !$fromWallet->is_active) {
            throw new WalletNotActiveException("محفظة {$fromCurrency} غير نشطة");
        }
        if (!$toWallet || !$toWallet->is_active) {
            throw new WalletNotActiveException("محفظة {$toCurrency} غير نشطة");
        }

        // ─── 4. حساب سعر الصرف والرسوم ───
        $rateData = $this->rateService->getRate($fromCurrency, $toCurrency);

        $rate          = $rateData['rate'];
        $feePercentage = $rateData['fee_percentage'];
        $feeAmount     = $this->rateService->calculateFee($amount, $feePercentage);

        // المبلغ الإجمالي المخصوم = المبلغ + الرسوم
        $totalDeduction = $amount + $feeAmount;

        // ─── 5. حساب المبلغ المحول (بعد الخصم وقبل الرسوم) ───
        $convertedAmount = $fromCurrency === 'SYP'
            ? round($amount / $rate, 2)   // SYP → USD
            : round($amount * $rate, 2);  // USD → SYP

        $amountInUsd = $fromCurrency === 'USD'
            ? $amount
            : round($amount / $rate, 2);

        // ─── 6. التحقق من الرصيد ───
        if ($fromWallet->available_balance < $totalDeduction) {
            throw new InsufficientBalanceException(
                available: (float) $fromWallet->available_balance,
                required:  $totalDeduction,
            );
        }

        // ─── 7. التنفيذ الذري (Atomic DB Transaction) ───
        $transaction = DB::transaction(function () use (
            $fromWallet, $toWallet,
            $amount, $amountInUsd, $convertedAmount,
            $totalDeduction, $feeAmount,
            $rate, $feePercentage, $fromCurrency, $toCurrency
        ) {
            // 7a. قفل المحفظتين بترتيب تصاعدي لمنع Deadlock
            $walletIds = [$fromWallet->id, $toWallet->id];
            sort($walletIds);
            foreach ($walletIds as $id) {
                $this->walletService->lockForUpdate($id);
            }

            // 7b. خصم المبلغ + الرسوم من محفظة المصدر
            $this->walletService->decrement($fromWallet, $totalDeduction);

            // 7c. إضافة المبلغ المحول إلى محفظة الوجهة
            $this->walletService->increment($toWallet, $convertedAmount);

            // 7d. تسجيل معاملة الصرافة
            $txn = Transaction::createExchange(
                fromWallet: $fromWallet,
                toWallet:   $toWallet,
                amount:     $amount,
                amountInUsd: $amountInUsd,
                fee:        $feeAmount,
                metadata:   [
                    'rate'            => $rate,
                    'from_currency'   => $fromCurrency,
                    'to_currency'     => $toCurrency,
                    'converted_amount'=> $convertedAmount,
                    'fee_percentage'  => $feePercentage,
                ],
            );

            return $txn;
        }, attempts: 3);

        // ─── 8. إرسال الإشعارات (Async) ───
        try {
            ExchangeCompleted::dispatch($transaction, $user);
        } catch (\Throwable $e) {
            Log::warning('فشل إرسال إشعار الصرافة', [
                'transaction_id' => $transaction->id,
                'error'          => $e->getMessage(),
            ]);
        }

        // ─── 9. إرجاع النتيجة ───
        $fromWallet->refresh();
        $toWallet->refresh();

        return [
            'transaction'     => $transaction,
            'from_currency'   => $fromCurrency,
            'to_currency'     => $toCurrency,
            'converted_amount'=> $convertedAmount,
            'rate'            => $rate,
            'fee_percentage'  => $feePercentage,
            'new_balances'    => [
                'syp' => (float) ($fromCurrency === 'SYP' ? $fromWallet->balance : $toWallet->balance),
                'usd' => (float) ($fromCurrency === 'USD' ? $fromWallet->balance : $toWallet->balance),
            ],
        ];
    }
}
```

## تدفق ExchangeService خطوة بخطوة

```
1. التحقق من اختلاف العملات (SYP ≠ USD)
         │
2. التحقق من الحد الأدنى (1000 SYP / 1 USD)
         │
3. الحصول على المحافظ (from + to)
         │
4. حساب سعر الصرف + الرسوم
         │
5. حساب المبلغ المحول
         │
6. التحقق من الرصيد (amount + fee)
         │
7. DB::transaction {
    ├── lockForUpdate(fromWallet)  ← ترتيب تصاعدي
    ├── lockForUpdate(toWallet)    ← ترتيب تصاعدي
    ├── decrement(from, amount + fee)
    ├── increment(to, converted)
    └── Transaction::create(type: exchange)
   }
         │
8. dispatch(ExchangeCompleted)    ← Async
         │
9. Return response
```

## مثال: تحويل 100,000 SYP → USD

| البيان | القيمة |
|--------|--------|
| المبلغ | 100,000 SYP |
| سعر الصرف | 13,000 SYP/USD |
| الرسوم (1.5%) | 1,500 SYP |
| الإجمالي المخصوم | 101,500 SYP |
| المحول (بعد خصم الرسوم) | 100,000 / 13,000 = 7.69 USD |
| الرصيد الجديد (SYP) | -101,500 |
| الرصيد الجديد (USD) | +7.69 |
