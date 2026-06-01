# 14 - المعاملات الذرية ACID للتسوية البنكية

## نظرة عامة
المعاملات الذرية تضمن سلامة عملية التسوية البنكية من البداية إلى النهاية. إذا فشلت أي خطوة (مثل فشل التحويل البنكي)، يتم إرجاع جميع التغييرات تلقائياً، مما يمنع فقدان الأموال أو ازدواجية التحويلات.

## معالجة التسوية الذرية الكاملة

```php
<?php

namespace App\Services;

use App\Models\Merchant;
use App\Models\MerchantSettlement;
use App\Models\Wallet;
use App\Events\SettlementCompleted;
use App\Events\SettlementFailed;
use App\Exceptions\BankTransferFailedException;
use App\Exceptions\InsufficientMerchantBalanceException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AtomicSettlementService
{
    /**
     * تنفيذ التسوية البنكية كمعاملة ذرية واحدة
     *
     * الخطوات الذرية (كلها تنجح معاً أو تفشل معاً):
     * 1. قفل محفظة التاجر (SELECT FOR UPDATE)
     * 2. حساب المبلغ الصافي (المبيعات - العمولة - الرسوم - المرتجعات)
     * 3. خصم الرصيد من محفظة التاجر
     * 4. إنشاء سجل التسوية في قاعدة البيانات
     * 5. تنفيذ التحويل البنكي عبر API
     * 6. تحديث حالة التسوية إلى completed/failed
     *
     * في حال فشل الخطوة 5 (التحويل البنكي):
     * - Rollback تلقائي للخطوات 1-4
     * - إعادة الرصيد إلى المحفظة
     * - تسجيل الفشل وإعادة المحاولة
     */
    public function processSettlement(
        Merchant $merchant,
        string $currency,
        float $amount,
        ?int $bankAccountId = null
    ): MerchantSettlement {
        // قفل عام لمنع تسوية متزامنة لنفس التاجر
        $lockKey = "settlement-{$merchant->id}-{$currency}";
        $lock = Cache::lock($lockKey, 120); // قفل لمدة دقيقتين

        if (!$lock->get()) {
            throw new \RuntimeException('يتم معالجة طلب تسوية آخر، يرجى الانتظار');
        }

        try {
            return DB::transaction(function () use ($merchant, $currency, $amount, $bankAccountId) {
                // 1. قفل محفظة التاجر (يمنع أي عملية سحب أخرى)
                $wallet = Wallet::where('user_id', $merchant->id)
                    ->where('currency', $currency)
                    ->lockForUpdate()
                    ->firstOrFail();

                // 2. التحقق من كفاية الرصيد
                if ($wallet->balance < $amount) {
                    throw new InsufficientMerchantBalanceException(
                        required: $amount,
                        available: $wallet->balance,
                        currency: $currency
                    );
                }

                // 3. حساب الرسوم والعمولات
                $commissionPercentage = $merchant->commission_rate ?? 2.0;
                $commissionAmount = round($amount * ($commissionPercentage / 100), 2);
                $transferFee = round($amount * 0.01, 2); // 1% رسوم تحويل
                $netAmount = $amount - $commissionAmount - $transferFee;

                // 4. خصم الرصيد من المحفظة
                $wallet->decrement('balance', $amount);
                $wallet->increment('frozen_balance', $amount); // مجمد حتى تأكيد التحويل

                // 5. إنشاء سجل التسوية
                $settlement = MerchantSettlement::create([
                    'merchant_id'           => $merchant->id,
                    'period_start'          => now()->startOfDay(),
                    'period_end'            => now()->endOfDay(),
                    'gross_amount'          => $amount,
                    'commission_percentage' => $commissionPercentage,
                    'commission_amount'     => $commissionAmount,
                    'transfer_fee'          => $transferFee,
                    'refunds_deducted'      => 0,
                    'chargebacks_deducted'  => 0,
                    'net_amount'            => $netAmount,
                    'currency'              => $currency,
                    'status'                => 'processing',
                    'bank_transfer_initiated_at' => now(),
                ]);

                // 6. تنفيذ التحويل البنكي عبر API
                $transferResult = $this->initiateBankTransfer($settlement);

                if ($transferResult['success']) {
                    // نجاح التحويل
                    $settlement->update([
                        'status'                      => 'completed',
                        'bank_transaction_ref'         => $transferResult['transaction_ref'],
                        'bank_transfer_completed_at'   => now(),
                        'settlement_date'              => now(),
                    ]);

                    // خصم المبلغ المجمد نهائياً
                    $wallet->decrement('frozen_balance', $amount);

                    // إطلاق حدث الإتمام
                    event(new SettlementCompleted($settlement));

                    Log::info("تمت التسوية بنجاح: {$settlement->id} - {$netAmount} {$currency}");
                } else {
                    // فشل التحويل البنكي → Rollback
                    throw new BankTransferFailedException(
                        settlementId: $settlement->id,
                        amount: $netAmount,
                        currency: $currency,
                        bankErrorCode: $transferResult['error_code'],
                        bankErrorMessage: $transferResult['error_message']
                    );
                }

                return $settlement;

            }, attempts: 3); // إعادة محاولة المعاملة 3 مرات في حال Deadlock
        } catch (BankTransferFailedException $e) {
            // إعادة الرصيد المجمد إلى المحفظة
            // (هذا يحدث خارج الـ transaction لأن الـ transaction رولباك)

            $wallet = Wallet::where('user_id', $merchant->id)
                ->where('currency', $currency)
                ->first();

            if ($wallet) {
                $wallet->increment('balance', $amount);
                $wallet->decrement('frozen_balance', $amount);
            }

            event(new SettlementFailed($e->settlement, $e->getMessage()));

            throw $e;
        } finally {
            $lock->release();
        }
    }

    /**
     * تنفيذ التحويل البنكي عبر مزود الخدمة
     */
    private function initiateBankTransfer(MerchantSettlement $settlement): array
    {
        try {
            // استدعاء API البنك
            $response = \Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.bank_api.key'),
                'Content-Type'  => 'application/json',
            ])->post(config('services.bank_api.url') . '/api/v1/transfers', [
                'account_number' => $settlement->bank_account_number,
                'iban'           => $settlement->iban,
                'amount'         => $settlement->net_amount,
                'currency'       => $settlement->currency,
                'reference'      => "SETTLE-{$settlement->id}",
            ]);

            if ($response->successful()) {
                return [
                    'success'         => true,
                    'transaction_ref' => $response->json('transaction_id'),
                ];
            }

            return [
                'success'       => false,
                'error_code'    => $response->status(),
                'error_message' => $response->json('message') ?? 'خطأ غير معروف من البنك',
            ];

        } catch (\Exception $e) {
            Log::error("فشل الاتصال ببوابة البنك: {$e->getMessage()}");

            return [
                'success'       => false,
                'error_code'    => 'CONNECTION_ERROR',
                'error_message' => 'تعذر الاتصال ببوابة الدفع البنكية',
            ];
        }
    }
}
```

## منع التسوية المتزامنة (Concurrency Prevention)

```sql
-- ===== استعلامات منع التزامن =====

-- 1. قفل صف المحفظة (يمنع السحب المتزامن والتسوية في نفس الوقت)
BEGIN;
SELECT balance, frozen_balance FROM wallets
WHERE user_id = ? AND currency = ?
FOR UPDATE;

-- 2. التحقق من عدم وجود تسوية معلقة قبل إنشاء جديدة
SELECT id FROM merchant_settlements
WHERE merchant_id = ? AND currency = ? AND status IN ('pending', 'processing')
LIMIT 1;

-- 3. تحديث ذري لحالة التسوية (لمنع المعالجة المزدوجة)
UPDATE merchant_settlements
SET status = 'processing', bank_transfer_initiated_at = NOW()
WHERE id = ? AND status = 'pending';
```

## جدولة القفل لمعالجة التسوية الدورية

```php
/**
 * معالجة التسوية التلقائية مع قفل عام
 *
 * يستخدم Cache::lock لمنع تشغيل معالجة التسوية
 * التلقائية على خوادم متعددة في نفس الوقت
 */
class ProcessDailySettlements
{
    public function __invoke(): void
    {
        $lock = Cache::lock('process-daily-settlements', 600); // 10 دقائق

        if ($lock->get()) {
            try {
                Merchant::query()
                    ->whereHas('wallet', fn($q) => $q->where('balance', '>=', 50))
                    ->chunkById(50, function ($merchants) {
                        foreach ($merchants as $merchant) {
                            try {
                                app(AtomicSettlementService::class)
                                    ->processSettlement($merchant, 'USD', $merchant->wallet('USD')->balance);
                            } catch (\Exception $e) {
                                Log::error("فشلت التسوية التلقائية للتاجر {$merchant->id}: {$e->getMessage()}");
                            }
                        }
                    });
            } finally {
                $lock->release();
            }
        }
    }
}
```

## ملخص ACID للتسوية

| الخاصية | التطبيق في نظام التسوية |
|---------|------------------------|
| **Atomicity** | DB::transaction + rollback عند فشل التحويل البنكي |
| **Consistency** | التحقق من الرصيد + خصم متوازن (balance ← frozen_balance ← خصم نهائي) |
| **Isolation** | lockForUpdate() + Cache::lock + SELECT FOR UPDATE |
| **Durability** | InnoDB + سجل settlement_jobs + bank_transaction_ref |
