# 14 - المعاملات الذرية ACID للاشتراكات المتكررة

## نظرة عامة
المعاملات الذرية تضمن عدم فقدان أو ازدواجية عمليات الدفع المتكرر. النظام يستخدم قواعد بيانات InnoDB مع قفل الصفوف (Row Locking) لضمان التناسق.

## معالجة الخصم التلقائي (Atomic Recurring Payment)

```php
<?php

namespace App\Services;

use App\Models\MerchantSubscription;
use App\Models\SubscriptionCharge;
use App\Models\Wallet;
use App\Exceptions\InsufficientBalanceForRecurringException;
use App\Exceptions\RecurringPaymentFailedException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class RecurringPaymentProcessor
{
    /**
     * معالجة دفع اشتراك متكرر بذرية كاملة
     *
     * الخطوات الذرية:
     * 1. قفل محفظة العميل لمنع الإنفاق المتزامن
     * 2. التحقق من الرصيد الكافي
     * 3. خصم المبلغ من محفظة العميل
     * 4. إضافة المبلغ لمحفظة التاجر
     * 5. تحديث دورة الاشتراك الحالية
     * 6. تسجيل عملية الدفع
     * 7. تحرير القفل
     *
     * في حال فشل أي خطوة → Rollback كامل
     */
    public function processRecurringCharge(MerchantSubscription $sub): void
    {
        // قفل متشائم لمنع المعالجة المزدوجة (lock for 10 دقائق)
        $lockKey = "recurring-charge-{$sub->id}";

        Cache::lock($lockKey, 600)->block(30, function () use ($sub) {
            DB::transaction(function () use ($sub) {
                // 1. التحقق من إمكانية الشحن
                $this->validateChargeability($sub);

                // 2. الحصول على محفظة العميل مع قفل الصف (SELECT FOR UPDATE)
                $customerWallet = Wallet::where('user_id', $sub->customer_id)
                    ->where('currency', $sub->currency)
                    ->lockForUpdate()
                    ->firstOrFail();

                // 3. التحقق من كفاية الرصيد
                if ($customerWallet->balance < $sub->amount) {
                    throw new InsufficientBalanceForRecurringException(
                        required: $sub->amount,
                        available: $customerWallet->balance,
                        currency: $sub->currency
                    );
                }

                // 4. خصم الرصيد من محفظة العميل
                $customerWallet->decrement('balance', $sub->amount);

                // 5. إضافة الرصيد لمحفظة التاجر
                $merchantWallet = Wallet::where('user_id', $sub->merchant_id)
                    ->where('currency', $sub->currency)
                    ->lockForUpdate()
                    ->firstOrFail();

                $merchantWallet->increment('balance', $sub->amount);

                // 6. تحديث الاشتراك (زيادة الدورة الحالية)
                $updated = DB::update(
                    "UPDATE merchant_subscriptions
                     SET current_cycle = current_cycle + 1, updated_at = NOW()
                     WHERE id = ? AND current_cycle < max_cycles",
                    [$sub->id]
                );

                if ($updated === 0) {
                    throw new MaxCyclesReachedException($sub->max_cycles);
                }

                // 7. تسجيل عملية الدفع
                SubscriptionCharge::create([
                    'subscription_id' => $sub->id,
                    'cycle_number'    => $sub->current_cycle + 1,
                    'amount'          => $sub->amount,
                    'currency'        => $sub->currency,
                    'status'          => 'completed',
                    'processed_at'    => now(),
                ]);

                // 8. إعادة تحميل العلاقات المحدثة
                $sub->refresh();
            }, attempts: 3); // إعادة المحاولة 3 مرات في حال Deadlock
        });
    }

    /**
     * التحقق من إمكانية معالجة الدفع
     */
    private function validateChargeability(MerchantSubscription $sub): void
    {
        // التأكد من أن الاشتراك نشط
        if ($sub->status !== 'active') {
            throw new \RuntimeException("الاشتراك {$sub->id} غير نشط (الحالة: {$sub->status})");
        }

        // التأكد من عدم تجاوز الحد الأقصى للدورات
        if ($sub->current_cycle >= $sub->max_cycles) {
            throw new MaxCyclesReachedException($sub->max_cycles);
        }

        // التأكد من أن تاريخ الدفع هو اليوم أو مضى
        if ($sub->next_charge_at && $sub->next_charge_at->isFuture()) {
            throw new \RuntimeException("تاريخ الدفع لم يحن بعد: {$sub->next_charge_at}");
        }
    }
}
```

## منع الشحن المزدوج (Race Condition Prevention)

```sql
-- ===== المعاملات الذرية على مستوى قاعدة البيانات =====

-- 1. تحديث شرطي يضمن عدم معالجة الاشتراك مرتين
UPDATE merchant_subscriptions
SET current_cycle = current_cycle + 1,
    last_charge_at = NOW(),
    next_charge_at = DATE_ADD(NOW(), INTERVAL 1 MONTH)
WHERE id = ?
  AND current_cycle < max_cycles
  AND status = 'active'
  AND (next_charge_at IS NULL OR next_charge_at <= NOW());

-- 2. قفل الصف عند قراءة المحفظة (يمنع السحب المتزامن)
SELECT balance FROM wallets
WHERE user_id = ? AND currency = ?
FOR UPDATE;

-- 3. التحقق من عدم وجود شحن مكرر للدورة نفسها
SELECT id FROM subscription_charges
WHERE subscription_id = ? AND cycle_number = ?
FOR UPDATE;
```

## جدولة القفل (Scheduling Lock)

```php
/**
 * جدولة الدفع التلقائي مع قلم لمنع ازدواجية المعالجة
 *
 * يستخدم Scheduled Task في Laravel مع Cache Lock
 * لمنع تشغيل نفس الدفع على عدة خوادم في آن واحد
 */
class ScheduleRecurringPayments
{
    public function __invoke(): void
    {
        $lock = Cache::lock('process-recurring-payments', 300); // 5 دقائق

        if ($lock->get()) {
            try {
                MerchantSubscription::query()
                    ->where('status', 'active')
                    ->where('current_cycle', '<', DB::raw('max_cycles'))
                    ->where('next_charge_at', '<=', now())
                    ->chunkById(100, function ($subscriptions) {
                        foreach ($subscriptions as $sub) {
                            try {
                                $this->processRecurringCharge($sub);
                            } catch (InsufficientBalanceForRecurringException $e) {
                                // تسجيل الفشل وإعادة المحاولة لاحقاً
                                event(new PaymentFailed($sub, $sub->current_cycle + 1, 'insufficient_balance', 1));
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

## جدول ملخص ACID

| الخاصية | التطبيق في النظام |
|---------|-------------------|
| **Atomicity** | DB::transaction مع rollback تلقائي عند أي استثناء |
| **Consistency** | قيود SQL (CHECK, FOREIGN KEY) + تحديثات شرطية |
| **Isolation** | lockForUpdate() + Cache::lock |
| **Durability** | InnoDB + Binary Log + معاملات مسجلة في subscription_charges |
