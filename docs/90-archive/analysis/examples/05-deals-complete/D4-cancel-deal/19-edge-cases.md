# 19 - حالات الحافة + سيناريوهات خطأ (Edge Cases) - إلغاء الصفقة

## نظرة عامة

إلغاء الصفقة يتطلب إرجاع الأموال للمستثمرين بشكل آمن ودقيق. يجب التعامل مع حالات مثل تجميد المحافظ، تحويل العملات، والإلغاء المتزامن بحذر شديد لضمان عدم فقدان الأموال.

## جدول حالات الحافة

| # | الحالة | النتيجة | مستوى المعالجة | كود الخطأ |
|---|--------|---------|---------------|-----------|
| 1 | إلغاء صفقة بدون مستثمرين | إلغاء مباشر | مسموح | - |
| 2 | إلغاء صفقة مكتملة | رفض | Business | DEAL_ALREADY_COMPLETED |
| 3 | إلغاء صفقة ملغاة مسبقاً | رفض | Business | ALREADY_CANCELLED |
| 4 | محفظة مستثمر موقوفة أثناء الاسترجاع | تخطي وتسجيل مشكلة | Service | FROZEN_WALLET_SKIPPED |
| 5 | استرجاع مبلغ كبير جداً (ملايين) | يعمل ضمن DB::transaction | Performance | - |
| 6 | إلغاء في نفس وقت استثمار جديد | القفل يمنع الاستثمار الجديد | ACID | CONCURRENT_CANCEL_INVEST |
| 7 | سبب الإلغاء أقل من 10 أحرف | رفض | Validation | REASON_TOO_SHORT |
| 8 | المستثمر غير نشط (suspended) | الاسترجاع يتم للمحفظة | Business | - |
| 9 | إلغاء بعد استثمارات موجودة | استرجاع كامل + إلغاء | Business | INVESTORS_EXIST |
| 10 | إلغاء قبل أي استثمار | إلغاء مباشر (تحديث حالة فقط) | Business | - |
| 11 | إلغاء جزئي (إلغاء جزء من الصفقة) | غير مسموح (إلغاء كلي فقط) | Business | PARTIAL_CANCEL_DISALLOWED |
| 12 | رسوم إلغاء (Cancellation Fee) | خصم رسوم قبل الاسترجاع | Business | CANCEL_FEE_APPLIED |
| 13 | تحويل عملة الاسترجاع (USD → SYP) | تحويل بسعر الصرف الحالي | Service | CURRENCY_CONVERSION |
| 14 | إلغاء صفقة في طور الإتمام | رفض (انتظار حتى يكتمل) | Business | DEAL_COMPLETING |
| 15 | فشل الإشعار أثناء الإلغاء | استمرار الإلغاء + إعادة محاولة الإشعار | Service | NOTIFICATION_FAILED |

## تحليل الحالات بالتفصيل مع أكواد PHP

### 1. إلغاء صفقة بدون مستثمرين
```php
public function cancel(Deal $deal, string $reason): void
{
    $investorCount = $deal->investments()->count();

    if ($investorCount === 0) {
        // إلغاء مباشر - لا يوجد أموال لإرجاعها
        $deal->status = DealStatus::CANCELLED;
        $deal->cancelled_at = now();
        $deal->cancel_reason = $reason;
        $deal->save();
        return;
    }

    // يوجد مستثمرون - نحتاج استرجاع الأموال
    $this->cancelWithRefund($deal, $reason);
}
```

### 2-3. إلغاء صفقة مكتملة أو ملغاة بالفعل
```php
public function validateCancellation(Deal $deal): void
{
    if ($deal->status === DealStatus::COMPLETED) {
        throw new CancelException(
            'لا يمكن إلغاء صفقة مكتملة. تم توزيع الأرباح بالفعل.',
            'DEAL_ALREADY_COMPLETED'
        );
    }

    if ($deal->status === DealStatus::CANCELLED) {
        throw new CancelException(
            'هذه الصفقة ملغاة مسبقاً بتاريخ: ' . $deal->cancelled_at->format('Y-m-d'),
            'ALREADY_CANCELLED'
        );
    }
}
```

### 4. محفظة مستثمر موقوفة أثناء الاسترجاع
```php
public function refundInvestors(Deal $deal): void
{
    $failedRefunds = [];

    DB::transaction(function () use ($deal, &$failedRefunds) {
        foreach ($deal->investments as $investment) {
            $wallet = $investment->user->wallets()
                ->where('currency', $deal->currency)
                ->first();

            if (!$wallet || $wallet->is_frozen) {
                // تسجيل الفشل والاستمرار
                $failedRefunds[] = [
                    'user_id' => $investment->user_id,
                    'investment_id' => $investment->id,
                    'amount' => $investment->amount,
                    'reason' => $wallet->is_frozen ? 'frozen' : 'no_wallet',
                ];
                continue;
            }

            // إرجاع المبلغ
            $wallet->increment('balance', $investment->amount);
            $investment->update([
                'refunded_at' => now(),
                'refund_status' => 'completed',
            ]);
        }

        $deal->status = DealStatus::CANCELLED;
        $deal->cancelled_at = now();
        $deal->save();
    });

    // محاولة إعادة المحاولة للفاشلة لاحقاً
    if (!empty($failedRefunds)) {
        PendingRefund::insert($failedRefunds);
        RetryFailedRefundsJob::dispatch($deal->id)->delay(now()->addHour());
    }
}
```

### 5. استرجاع مبلغ كبير جداً
```php
// المبالغ الكبيرة (ملايين) يتم استرجاعها بشكل طبيعي
// لكن نضيف تحقق إضافي للسلامة
if ($totalRefund > 1000000) {
    Log::warning("استرجاع مبلغ كبير للصفقة {$deal->id}: \${$totalRefund}");

    // إشعار مسؤولي الامتثال
    NotificationService::notifyCompliance(
        "طلب استرجاع مبلغ كبير: {$totalRefund} USD للصفقة {$deal->title}"
    );
}
```

### 6. إلغاء في نفس وقت استثمار جديد
```php
// يتم استخدام Advisory Lock على مستوى الصفقة
public function cancelWithLock(Deal $deal, string $reason): void
{
    DB::beginTransaction();
    try {
        // قفل الصفقة لمنع أي استثمار جديد
        $lockedDeal = Deal::where('id', $deal->id)
            ->lockForUpdate()
            ->firstOrFail();

        // التحقق من عدم وجود استثمارات جديدة منذ بدء الطلب
        $this->validateCancellation($lockedDeal);

        // استرجاع الأموال
        $this->refundInvestors($lockedDeal);

        DB::commit();
    } catch (Throwable $e) {
        DB::rollBack();
        throw new CancelException('فشل إلغاء الصفقة: ' . $e->getMessage());
    }
}
```

### 7. سبب الإلغاء أقل من 10 أحرف
```php
$request->validate([
    'reason' => 'required|string|min:10|max:1000',
], [
    'reason.min' => 'سبب الإلغاء يجب أن يكون 10 أحرف على الأقل للتوثيق',
]);
```

### 8. المستثمر غير نشط
```php
// حتى لو كان المستثمر suspended، يتم إيداع المبلغ في محفظته
// يمكنه السحب لاحقاً بعد حل المشكلة
$refundAmount = $investment->amount;
$wallet = $investment->user->wallets()
    ->where('currency', $deal->currency)
    ->first();

if ($wallet) {
    $wallet->increment('balance', $refundAmount);
    // تسجيل وصول
    activity()
        ->performedOn($wallet)
        ->log("استرجاع مبلغ {$refundAmount} من صفقة ملغاة {$deal->id}");
}
```

### 9. إلغاء بعد استثمارات
```php
public function cancelWithRefund(Deal $deal, string $reason): void
{
    $totalInvested = $deal->investments()->sum('amount');

    if ($totalInvested > 0) {
        // تطبيق رسوم الإلغاء إن وجدت
        $feePercent = $this->getCancellationFeePercent($deal);
        if ($feePercent > 0) {
            $this->applyCancellationFee($deal, $feePercent);
        }

        // استرجاع المتبقي
        $this->refundInvestors($deal);
    }

    $deal->status = DealStatus::CANCELLED;
    $deal->cancel_reason = $reason;
    $deal->cancelled_at = now();
    $deal->save();

    // إشعار جميع المستثمرين
    event(new DealCancelledEvent($deal));
}
```

### 10. إلغاء قبل أي استثمار
```php
// الحالة الأبسط - مجرد تحديث حالة
$deal->status = DealStatus::CANCELLED;
$deal->cancelled_at = now();
$deal->cancel_reason = $reason;
$deal->save();
```

### 11. إلغاء جزئي
```php
// غير مسموح - يتم إلغاء الصفقة كاملة أو لا شيء
throw new CancelException(
    'لا يمكن إلغاء صفقة بشكل جزئي. '
    . 'يمكنك إلغاء الصفقة بالكامل وسيتم استرجاع جميع الأموال للمستثمرين.',
    'PARTIAL_CANCEL_DISALLOWED'
);
```

### 12. رسوم الإلغاء
```php
public function applyCancellationFee(Deal $deal, float $feePercent): void
{
    $totalInvested = $deal->investments()->sum('amount');
    $totalFee = $totalInvested * ($feePercent / 100);

    // خصم رسوم الإلغاء من المبلغ المسترجع
    $refundAmount = $totalInvested - $totalFee;

    // إضافة الرسوم إلى محفظة المنصة
    $platformWallet = Wallet::where('is_platform', true)->first();
    $platformWallet->increment('balance', $totalFee);

    // تسجيل الرسوم
    CancellationFee::create([
        'deal_id' => $deal->id,
        'total_fee' => $totalFee,
        'fee_percent' => $feePercent,
    ]);

    Log::info("رسوم إلغاء للصفقة {$deal->id}: {$totalFee} USD");
}
```

### 13. تحويل عملة الاسترجاع
```php
public function refundWithCurrencyConversion(Investment $investment, Deal $deal): void
{
    $refundCurrency = $investment->user->preferred_currency;

    if ($refundCurrency !== $deal->currency) {
        // استخدام سعر الصرف من خدمة الطرف الثالث
        $rate = ExchangeRateService::getRate($deal->currency, $refundCurrency);
        $convertedAmount = $investment->amount * $rate;

        // تسجيل عملية التحويل
        CurrencyConversion::create([
            'investment_id' => $investment->id,
            'from_currency' => $deal->currency,
            'to_currency' => $refundCurrency,
            'original_amount' => $investment->amount,
            'converted_amount' => $convertedAmount,
            'rate' => $rate,
        ]);

        $wallet = $investment->user->wallets()
            ->where('currency', $refundCurrency)
            ->first();

        if ($wallet) {
            $wallet->increment('balance', $convertedAmount);
        }
    } else {
        // استرجاع عادي بنفس العملة
        $wallet = $investment->user->wallets()
            ->where('currency', $deal->currency)
            ->first();
        $wallet->increment('balance', $investment->amount);
    }
}
```

### 14. إلغاء صفقة في طور الإتمام
```php
if ($deal->status === DealStatus::COMPLETING) {
    throw new CancelException(
        'الصفقة قيد الإتمام حالياً. يرجى الانتظار حتى اكتمال العملية أو الاتصال بالدعم.',
        'DEAL_COMPLETING'
    );
}
```

### 15. فشل الإشعار أثناء الإلغاء
```php
// الإلغاء يتم حتى لو فشل الإشعار
try {
    NotificationService::notifyInvestors($deal, 'deal_cancelled');
} catch (NotificationException $e) {
    // تسجيل الفشل وإعادة المحاولة لاحقاً
    FailedNotification::create([
        'deal_id' => $deal->id,
        'type' => 'deal_cancelled',
        'error' => $e->getMessage(),
    ]);
    RetryNotificationJob::dispatch($deal->id, 'deal_cancelled')->delay(now()->addMinutes(5));
    Log::warning("فشل إشعار إلغاء الصفقة {$deal->id}: " . $e->getMessage());
}
```

## مصفوفة القرار للإلغاء

| الحالة | هل يُسمح بالإلغاء؟ | استرجاع الأموال؟ | رسوم؟ | إشعارات؟ |
|--------|-------------------|------------------|-------|---------|
| بدون مستثمرين | نعم | لا | لا | لا |
| بعد استثمارات | نعم | نعم | حسب السياسة | نعم |
| صفقة مكتملة | لا | - | - | - |
| صفقة ملغاة | لا | - | - | - |
| قيد الإتمام | لا (انتظار) | - | - | - |
| إلغاء جزئي | لا | - | - | - |
| محفظة مجمدة | نعم | تخطي + محاولة لاحقة | لا | نعم |
| عملة مختلفة | نعم | تحويل بسعر الصرف | قد توجد رسوم تحويل | نعم |

## توصيات إضافية

1. تطبيق **Two-Factor Authentication** لتأكيد عملية الإلغاء (خاصة للمبالغ الكبيرة)
2. تسجيل **نص سبب الإلغاء** كاملاً في Audit Log لأغراض قانونية
3. إضافة **فترة سماح** للتراجع عن الإلغاء (مثلاً 24 ساعة قبل التنفيذ الفعلي)
4. إتاحة **إلغاء جماعي** لعدة صفقات في وقت واحد عبر واجهة Admin
5. إرسال إشعار **قبل الإلغاء بفترة** للمستثمرين لإعلامهم بالإلغاء المزمع
