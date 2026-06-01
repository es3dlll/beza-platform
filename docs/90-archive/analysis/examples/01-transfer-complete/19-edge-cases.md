# 19 - حالات الحافة + سيناريوهات خطأ (Edge Cases)

## 1. معاملات متزامنة (Concurrent Transfers)

**المشكلة**: مستخدم يرسل طلبين في نفس اللحظة — الرصيد 500، يريد إرسال 400 لكل منهما.

| السيناريو | بدون حماية | مع `FOR UPDATE` |
|-----------|-----------|-----------------|
| طلب 1: 400 | يقرأ الرصيد = 500 | يقفل المحفظة |
| طلب 2: 400 | يقرأ الرصيد = 500 (قبل أن يكتب 1) | ينتظر حتى يتحرر القفل |
| النتيجة | كلا الطلبين ينجح → رصيد -300 (!) | طلب 1 ينجح، طلب 2 يفشل (رصيد غير كافٍ) |

**الحل**: `SELECT ... FOR UPDATE` + `UPDATE ... WHERE balance >= amount`

## 2. انقطاع الشبكة أثناء المعاملة

**المشكلة**: المستخدم يضغط "تحويل" وتنقطع الشبكة بعد خصم الرصيد.

**الحل**: `DB::transaction` يضمن Atomicity — إذا لم يصل COMMIT، InnoDB يعمل ROLLBACK تلقائياً.

```
T1: BEGIN
T2: UPDATE wallets SET balance = balance - 100 WHERE id = 1;  ← خصم
T3: [انقطاع التيار]
T4: [إعادة التشغيل] → InnoDB: ROLLBACK غير المكتملة
T5: الرصيد يعود كما كان
```

## 3. Deadlock بين معاملتين

**المشكلة**: معاملة 1 تقفل المحفظة A ثم B، ومعاملة 2 تقفل B ثم A.

```
T1: LOCK A → LOCK B (ينتظر B)
T2: LOCK B → LOCK A (ينتظر A)
→ DEADLOCK!
```

**الحلول**:
1. قفل بترتيب تصاعدي (sort wallet IDs)
2. `DB::transaction(..., attempts: 3)` — InnoDB يكشف Deadlock ويعيد المحاولة

## 4. duplicate reference_number

**المشكلة**: احتمال تصادم reference_number (نادر جداً لكن ممكن).

**الحل**: `Transaction::generateReferenceNumber()` يستخدم:
- بادئة `BZ`
- طابع زمني حتى الثانية (`ymdHis`)
- 6 أحرف عشوائية من `uniqid()`

```php
public static function generateReferenceNumber(): string
{
    do {
        $ref = 'BZ' . now()->format('ymdHis') . strtoupper(substr(uniqid(), -6));
    } while (self::where('reference_number', $ref)->exists());

    return $ref;
}
```

## 5. محفظة المستقبل غير نشطة

**المشكلة**: مستلم موجود لكن محفظته موقوفة.

**الحل**: التحقق من `is_active = true` قبل الخصم.

```php
$toWallet = $toUser->wallets()->where('currency', $currency)->where('is_active', true)->first();

if (!$toWallet) {
    throw new WalletNotActiveException('محفظة المستلم غير نشطة');
}
```

## 6. عدم تطابق العملة

**المشكلة**: المستخدم لديه USD فقط ويحاول التحويل بـ SYP.

**الحل**: التحقق من وجود المحفظة بالعملة المطلوبة قبل المتابعة.

```php
$fromWallet = $this->walletService->getWallet($fromUser->id, $currency);
if (!$fromWallet) {
    throw new WalletNotActiveException('لا تملك محفظة بهذه العملة');
}
```

## 7. مبلغ كبير جداً (Overflow)

**المشكلة**: إدخال `amount: 9999999999999999999999999999999999999999`.

**الحل**:
- Laravel Validation: `max:9999999.99` (قبل أن يصل إلى DB)
- MySQL: `DECIMAL(15,2)` — الحد الأقصى 9999999999999.99
- كلا المستويين يمنعان الـ overflow

## 8. مبلغ صغير جداً (Precision)

**المشكلة**: إدخال `amount: 0.001` — عملة SYP ليس لها كسور.

**الحل**: التقريب إلى منزلتين عشريتين.

```php
$amount = round($amount, 2);
```

## 9. مستخدم محظور (Suspended)

**المشكلة**: المستخدم كان نشطاً وقت التحقق لكن تم حظره قبل التنفيذ.

**الحل**: التأكد من أن `status = active` داخل DB transaction.

## 10. PIN فارغ (لم يتم تعيينه)

**المشكلة**: مستخدم ليس لديه PIN (لم يقم بتعيينه بعد).

**الحل**: التحقق من وجود `pin_code` قبل محاولة التحقق.

```php
if (!$fromUser->pin_code) {
    throw new \App\Exceptions\PinNotSetException();
}
```

## 11. التحويل برسوم (Fee Edge Cases)

حالياً الرسوم = 0، لكن في المستقبل:

```
إذا كان fee = 2%:
  - الخصم من المرسل: amount + fee
  - الإضافة للمستلم: amount
  - يتم إنشاء معاملة إضافية من نوع 'fee'
```

## 12. معاملة قديمة (Stale Transaction)

عند عرض المعاملات السابقة: المستخدم أو المستلم قد يكون محذوفاً (soft delete).

```php
// في TransactionResource
'sender' => $this->when($this->fromWallet?->user, function () {
    return [
        'id'    => $this->fromWallet->user->id ?? null,
        'name'  => $this->fromWallet->user->name ?? 'مستخدم محذوف',
        'phone' => $this->fromWallet->user->phone ?? '—',
    ];
}),
```

## 13. فرق التوقيت (Timezone)

**المشكلة**: `today()` يعتمد على timezone الخاص بالتطبيق.

**الحل**: ضبط `app.timezone` إلى `Asia/Damascus` في `config/app.php`.

## 14. مبلغ التحويل صفر أو سالب

**المشكلة**: `amount: 0` أو `amount: -100`.

**الحل**: Validation rule `min:1` — Laravel يرفضه حتى قبل أن يصل إلى Service.

## 15. رمز PIN مكرر (Brute Force)

**المشكلة**: هجوم تخمين PIN (0000, 0001, 0002...).

**الحل**: عدد محاولات PIN محدودة:
- 5 محاولات فاشلة → تعطيل المحفظة 15 دقيقة
- أو استخدام Throttle عام: `throttle:30,1`
- إضافة rate limit خاص بـ PIN في Redis

```php
// الأولوية: إصدار 2.0 — يتطلب Reverb + WebSocket
// if (PinAttempt::exceeded($fromUser->id, 5)) {
//     throw new PinLockedException();
// }
```

## جدول ملخص حالات الحافة

| # | الحالة | النتيجة | مستوى المعالجة |
|---|--------|---------|---------------|
| 1 | طلبين متزامنين | الرصيد ينفد → 1 ينجح، 1 يفشل | DB (FOR UPDATE) |
| 2 | انقطاع الشبكة | ROLLBACK — لا يتغير شيء | DB (InnoDB) |
| 3 | Deadlock | إعادة محاولة تلقائية | DB (attempts: 3) |
| 4 | تكرار reference | توليد رقم جديد | Application (do-while) |
| 5 | محفظة موقوفة | رفض التحويل | Application |
| 6 | عملة غير موجودة | رفض التحويل | Application |
| 7 | مبلغ ضخم | رفض التحقق | Validation |
| 8 | كسور عملة | تقريب لـ 2 decimal | Application |
| 9 | مستخدم محظور | رفض عند التنفيذ | Application + DB |
| 10 | PIN غير معيّن | يطلب تعيين PIN أولاً | Application |
| 11 | رسوم المعاملات | خصم إضافي (مستقبلاً) | Application |
| 12 | مستخدم محذوف | عرض "محذوف" | Resource |
| 13 | فرق توقيت | حسب Asia/Damascus | Config |
| 14 | مبلغ 0 أو سالب | رفض التحقق | Validation |
| 15 | Brute force PIN | تعطيل مؤقت بعد 5 محاولات — قفل 15 دقيقة | Middleware |
