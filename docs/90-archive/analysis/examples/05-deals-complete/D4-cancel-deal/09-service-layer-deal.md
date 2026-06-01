# 09 - DealService كامل (للإلغاء)

## RefundService

```php
<?php
// app/Services/RefundService.php

namespace App\Services;

use App\Events\DealCancelled;
use App\Exceptions\DealNotCancellableException;
use App\Models\Deal;
use App\Models\DealInvestment;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RefundService
{
    public function __construct(
        private readonly WalletService $walletService
    ) {}

    /**
     * إلغاء صفقة واسترجاع المبالغ
     */
    public function refund(Deal $deal, string $reason): array
    {
        if (!$deal->canBeCancelled()) {
            throw new DealNotCancellableException($deal->status);
        }

        // ─── المستثمرون النشطون ───
        $activeInvestments = $deal->investments()
            ->where('status', 'active')
            ->get();

        $totalRefunded = 0;

        // ─── الاسترجاع في DB::transaction ───
        DB::transaction(function () use (
            $deal, $activeInvestments, $reason, &$totalRefunded
        ) {
            foreach ($activeInvestments as $investment) {
                // الحصول على محفظة المستثمر
                $wallet = $this->walletService->getWallet(
                    $investment->investor_id,
                    $deal->currency
                );

                if ($wallet && $wallet->is_active) {
                    // إعادة المبلغ
                    $this->walletService->increment($wallet, $investment->amount);

                    // تسجيل معاملة refund
                    Transaction::create([
                        'to_wallet_id'    => $wallet->id,
                        'amount'          => $investment->amount,
                        'amount_in_usd'   => $investment->amount_in_usd,
                        'type'            => 'refund',
                        'status'          => 'completed',
                        'reference_number'=> Transaction::generateReferenceNumber(),
                        'description'     => "استرجاع صفقة ملغاة: {$deal->title}",
                        'completed_at'    => now(),
                    ]);

                    // تحديث حالة الاستثمار
                    $investment->update([
                        'status'      => 'refunded',
                        'refunded_at' => now(),
                    ]);

                    $totalRefunded += $investment->amount;
                }
            }

            // إلغاء الصفقة
            $deal->markAsCancelled($reason);
        }, attempts: 3);

        // ─── إشعارات ───
        try {
            DealCancelled::dispatch($deal, $activeInvestments, $reason);
        } catch (\Throwable $e) {
            Log::warning('فشل إرسال إشعار الإلغاء', [
                'deal_id' => $deal->id,
                'error'   => $e->getMessage(),
            ]);
        }

        return [
            'deal'           => $deal->fresh(),
            'total_refunded' => $totalRefunded,
            'investors_count'=> $activeInvestments->count(),
        ];
    }
}
```
