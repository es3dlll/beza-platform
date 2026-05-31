# 09 - DealService كامل (للاستثمار)

## InvestService

```php
<?php
// app/Services/InvestService.php

namespace App\Services;

use App\Events\InvestmentMade;
use App\Exceptions\AmountExceedsRemainingException;
use App\Exceptions\CannotInvestInOwnDealException;
use App\Exceptions\DealFullyFundedException;
use App\Exceptions\DealNotActiveException;
use App\Exceptions\InsufficientBalanceException;
use App\Models\Deal;
use App\Models\DealInvestment;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvestService
{
    public function __construct(
        private readonly WalletService $walletService
    ) {}

    /**
     * تنفيذ الاستثمار في صفقة
     */
    public function invest(User $user, Deal $deal, float $amount): array
    {
        // ─── 1. التحقق من حالة الصفقة ───
        if (!in_array($deal->status, ['active', 'filled'])) {
            throw new DealNotActiveException();
        }

        if ($deal->current_amount >= $deal->target_amount) {
            throw new DealFullyFundedException();
        }

        // ─── 2. منع الاستثمار في صفقة المستخدم نفسه ───
        if ($deal->created_by === $user->id) {
            throw new CannotInvestInOwnDealException();
        }

        // ─── 3. التحقق من الحد الأدنى ───
        if ($amount < 10) {
            throw new \InvalidArgumentException('أقل مبلغ للاستثمار هو 10 USD');
        }

        // ─── 4. التحقق من عدم تجاوز المبلغ المتبقي ───
        $remaining = $deal->remaining_amount;
        if ($amount > $remaining) {
            throw new AmountExceedsRemainingException($remaining);
        }

        // ─── 5. الحصول على محفظة المستثمر ───
        $wallet = $this->walletService->getWallet($user->id, $deal->currency);
        if (!$wallet || !$wallet->is_active) {
            throw new \RuntimeException('محفظتك غير نشطة');
        }

        if ($wallet->available_balance < $amount) {
            throw new InsufficientBalanceException(
                available: (float) $wallet->available_balance,
                required:  $amount,
            );
        }

        // ─── 6. حساب المبلغ بـ USD ───
        $amountInUsd = $deal->currency === 'USD'
            ? $amount
            : $this->walletService->convertToUsd($amount);

        // ─── 7. التنفيذ الذري ───
        $result = DB::transaction(function () use (
            $user, $deal, $amount, $amountInUsd, $wallet
        ) {
            // قفل المحفظة
            $this->walletService->lockForUpdate($wallet->id);

            // خصم من المستثمر
            $this->walletService->decrement($wallet, $amount);

            // زيادة current_amount في الصفقة
            $deal->incrementCurrentAmount($amount);
            $deal->refresh();

            // تسجيل الاستثمار
            $investment = DealInvestment::create([
                'deal_id'      => $deal->id,
                'investor_id'  => $user->id,
                'amount'       => $amount,
                'amount_in_usd'=> $amountInUsd,
                'currency'     => $deal->currency,
                'status'       => 'active',
            ]);

            // إذا اكتمل المبلغ → تغيير الحالة إلى filled
            if ($deal->current_amount >= $deal->target_amount) {
                $deal->update(['status' => 'filled']);
            }

            return ['investment' => $investment, 'deal' => $deal];
        }, attempts: 3);

        // ─── 8. إرسال الإشعارات ───
        try {
            InvestmentMade::dispatch($result['investment'], $user, $deal);
        } catch (\Throwable $e) {
            Log::warning('فشل إرسال إشعار الاستثمار', [
                'investment_id' => $result['investment']->id,
                'error'         => $e->getMessage(),
            ]);
        }

        $wallet->refresh();

        return [
            'investment'  => $result['investment'],
            'deal'        => $result['deal'],
            'new_balance' => (float) $wallet->balance,
        ];
    }
}
```
