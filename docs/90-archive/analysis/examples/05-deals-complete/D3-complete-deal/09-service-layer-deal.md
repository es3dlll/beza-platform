# 09 - DealService كامل (لإتمام الصفقة)

## ProfitDistributionService

```php
<?php
// app/Services/ProfitDistributionService.php

namespace App\Services;

use App\Events\DealCompleted;
use App\Exceptions\DealNotCompletableException;
use App\Exceptions\NoActiveInvestorsException;
use App\Models\Deal;
use App\Models\DealInvestment;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProfitDistributionService
{
    public function __construct(
        private readonly WalletService $walletService
    ) {}

    /**
     * توزيع الأرباح على المستثمرين
     */
    public function distribute(Deal $deal, float $profitActual): array
    {
        if (!$deal->canBeCompleted()) {
            throw new DealNotCompletableException($deal->status);
        }

        $deal->profit_actual = $profitActual;

        // ─── حساب إجمالي الاستثمارات النشطة ───
        $activeInvestments = $deal->investments()
            ->where('status', 'active')
            ->get();

        if ($activeInvestments->isEmpty()) {
            throw new NoActiveInvestorsException();
        }

        $totalInvested = $activeInvestments->sum('amount');

        // ─── حساب إجمالي الربح الفعلي ───
        $totalProfit = $deal->calculateTotalProfit();

        // ─── التوزيع في DB::transaction ───
        $distributions = DB::transaction(function () use (
            $deal, $activeInvestments, $totalInvested, $totalProfit
        ) {
            $results = [];

            foreach ($activeInvestments as $investment) {
                // حساب حصة هذا المستثمر
                $profitShare = $investment->calculateProfitShare($totalProfit, $totalInvested);

                // الحصول على محفظة المستثمر
                $wallet = $this->walletService->getWallet(
                    $investment->investor_id,
                    $deal->currency
                );

                if ($wallet && $wallet->is_active) {
                    // إضافة الربح إلى المحفظة
                    $this->walletService->increment($wallet, $profitShare);

                    // تسجيل معاملة الربح
                    $txn = Transaction::create([
                        'to_wallet_id'    => $wallet->id,
                        'amount'          => $profitShare,
                        'amount_in_usd'   => $deal->currency === 'USD'
                            ? $profitShare
                            : $this->walletService->convertToUsd($profitShare),
                        'type'            => 'investment_profit',
                        'status'          => 'completed',
                        'reference_number'=> Transaction::generateReferenceNumber(),
                        'description'     => "أرباح صفقة: {$deal->title}",
                        'completed_at'    => now(),
                    ]);

                    // تحديث profit_earned في الاستثمار
                    $investment->update([
                        'profit_earned' => $profitShare,
                        'status'        => 'completed',
                    ]);

                    $results[] = [
                        'investor_id'  => $investment->investor_id,
                        'amount'       => $investment->amount,
                        'profit_share' => $profitShare,
                        'transaction_id' => $txn->id,
                    ];
                }
            }

            // إنهاء الصفقة
            $deal->markAsCompleted();

            return $results;
        }, attempts: 3);

        // ─── إشعارات ───
        try {
            DealCompleted::dispatch($deal, $distributions);
        } catch (\Throwable $e) {
            Log::warning('فشل إرسال إشعار إتمام الصفقة', [
                'deal_id' => $deal->id,
                'error'   => $e->getMessage(),
            ]);
        }

        return [
            'deal'            => $deal->fresh(),
            'total_profit'    => $totalProfit,
            'investors_count' => $activeInvestments->count(),
            'distributions'   => $distributions,
        ];
    }
}
```
