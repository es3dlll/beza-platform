# 19 - حالات الحافة + سيناريوهات خطأ (Edge Cases)

## 1. صرافة SYP → SYP (نفس العملة)

**المشكلة**: المستخدم يختار نفس العملة للمصدر والوجهة.

**الحل**: التحقق في ExchangeService → `SameCurrencyExchangeException`.

## 2. أقل من الحد الأدنى

**المشكلة**: 0.5 USD أو 500 SYP (أقل من الحد).

**الحل**: `MinimumAmountException` — الحد 1 USD / 1000 SYP.

## 3. الرصيد لا يكفي لتغطية الرسوم

**المشكلة**: الرصيد 100,000 SYP، المبلغ 99,000 + رسوم 1,485 = 100,485 > 100,000.

**الحل**: التحقق من `balance >= amount + fee` في استعلام UPDATE.

## 4. سعر الصرف غير متاح

**المشكلة**: خطأ في جلب سعر الصرف (API طرف ثالث معطل).

**الحل**: استخدام السعر من config كـ Fallback.

```php
public function getRate(string $from, string $to): array
{
    $rate = config("beza.exchange.rates.{$from}_TO_{$to}");
    if (!$rate) {
        throw new RateNotFoundException($from, $to);
    }

    return [
        'rate'           => (float) $rate,
        'fee_percentage' => (float) config('beza.exchange.fee_percentage', 1.5),
    ];
}
```

## 5. محفظة المصدر غير نشطة

**المشكلة**: محفظة المستخدم موقوفة (is_active = false).

**الحل**: التحقق قبل المتابعة → `WalletNotActiveException`.

## 6. محفظة الوجهة غير نشطة

**المشكلة**: لا يمكن الإضافة إلى محفظة موقوفة.

**الحل**: `increment()` يتحقق من `is_active = 1` في WHERE.

## 7. التزامن (Concurrent Requests)

**المشكلة**: طلبين صرافة في نفس الوقت.

**الحل**: `FOR UPDATE` + `DB::transaction(attempts: 3)`.

## 8. Deadlock بين صرافتين

**المشكلة**: صرافة A تقفل SYP ثم USD، وصرافة B تقفل USD ثم SYP.

**الحل**: **قفل بترتيب تصاعدي** لـ wallet IDs.

## 9. overflow في المبلغ المحول

**المشكلة**: مبلغ USD كبير جداً × سعر الصرف = overflow.

**الحل**: Round إلى منزلتين + DECIMAL(15,2).

## 10. مبلغ الصرافة = 0 بعد الرسوم (نادر)

**المشكلة**: مبلغ صغير جداً بعد خصم الرسوم تصبح قيمته 0.

**الحل**: رفض الطلب عبر min amount check.

## 11. تغير سعر الصرف بين القراءة والتنفيذ

**المشكلة**: قرأ السعر 13,000 ثم تغير إلى 13,500.

**الحل**: سعر الصرف ثابت أثناء المعاملة (يُقرأ قبل DB::transaction). إذا تغير كثيراً → المستخدم يرى فرقاً بسيطاً.

## 12. رسوم الصرافة (Fee) — دقة الحساب

**المشكلة**: 1.5% من 100,000 SYP = 1,500 بالضبط.

**الحل**: `$fee = round($amount * $feePercentage / 100, 2)`.

## 13. المستخدم ليس لديه محفظة بالعملة المطلوبة

**المشكلة**: يريد صرافة USD لكن ليس لديه USD.

**الحل**: `WalletNotActiveException` — محفظة USD غير موجودة.

## 14. إعادة المحاولة بعد فشل (Idempotency)

**المشكلة**: المستخدم يضغط زر الصرافة مرتين.

**الحل**: reference_number فريد — إذا تكرر → Duplicate Entry. لكن كل محاولة جديدة تُنشئ transaction جديد.

## جدول ملخص حالات الحافة

| # | الحالة | النتيجة | مستوى المعالجة |
|---|--------|---------|---------------|
| 1 | نفس العملة | رفض (422) | Application |
| 2 | أقل من min | رفض (422) | Application |
| 3 | رصيد + رسوم > المتاح | رفض (422) | DB (WHERE) |
| 4 | سعر صرف غير متاح | 503 Service Unavailable | Application |
| 5 | محفظة مصدر موقوفة | رفض (422) | Application |
| 6 | محفظة وجهة موقوفة | رفض (422) | Application |
| 7 | طلبين متزامنين | الأقفال تمنع التناقض | DB (FOR UPDATE) |
| 8 | Deadlock | إعادة محاولة تلقائية | DB (attempts: 3) |
| 9 | Overflow | Round + DECIMAL محدود | DB + Application |
| 10 | مبلغ 0 بعد الرسوم | منع بـ min amount | Application |
| 11 | تغير السعر | سعر ثابت بالمعاملة | Application |
| 12 | دقة الرسوم | round(2) | Application |
| 13 | عملة غير موجودة | رفض (422) | Application |
| 14 | إعادة المحاولة | reference جديد كل مرة | Application |
