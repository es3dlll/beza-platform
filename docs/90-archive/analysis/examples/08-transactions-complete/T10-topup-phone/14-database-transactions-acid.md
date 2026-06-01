# 14 - ACID + الأقفال + الـ Race Conditions

## شحن رصيد هاتف: ازدواجية طلب الشحن لنفس الرقم
مشكلة: المستخدم يضغط "شحن" مرتين لنفس رقم الهاتف، فيتم خصم مبلغين من المحفظة مع شحن الرصيد مرتين.

```
Time  |  طلب 1                          |  طلب 2
------|---------------------------------|-------------------------------
T1    |  شحن 25000 لرقم 0944123456      |
T2    |                                  |  شحن 25000 لرقم 0944123456
T3    |  خصم 25000 + إرسال للـ API      |
T4    |                                  |  خصم 25000 + إرسال للـ API ← شحن مزدوج!
```

**الحل**: Idempotency key فريد لكل طلب شحن + التحقق من المبلغ المتبقي من الحد اليومي للشحن.

## ACID في شحن الرصيد
```php
DB::transaction(function () use ($topup, $wallet) {
    // Atomicity: منع ازدواجية الطلب
    $existing = PhoneTopup::where('idempotency_key', $topup->idempotency_key)
        ->lockForUpdate()
        ->first();

    if ($existing && $existing->status === 'completed') {
        return $existing; // Idempotent: إرجاع النتيجة بدلاً من التكرار
    }

    // Isolation: قفل المحفظة + التحقق من الرصيد
    Wallet::where('id', $wallet->id)
        ->where('balance', '>=', $topup->amount)
        ->lockForUpdate()
        ->firstOrFail();

    // Atomicity: خصم + شحن عبر API خارجي + تسجيل
    $wallet->decrement('balance', $topup->amount);

    // استدعاء API مشغل الهاتف (مع retry)
    $providerResponse = PhoneTopupProvider::charge($topup->phone, $topup->amount);

    $topup->update([
        'status' => 'completed',
        'provider_ref' => $providerResponse['ref'],
        'completed_at' => now(),
    ]);
}, attempts: 3);
```

## Race Conditions المحددة
| السيناريو | المشكلة | الحل |
|-----------|---------|------|
| شحن مزدوج لنفس الرقم | خصم + شحن مرتين | Idempotency key |
| فشل API المشغل (timeout) | خصم بدون شحن | Retry + compensation |
| إعادة محاولة ناجحة بعد فشل | شحن مكرر | Idempotency key + provider_ref فريد |
| تجاوز الحد اليومي للشحن | شحن أكثر من المسموح | Daily limit check ضمن المعاملة |
| رصيد غير كافٍ للحظة الخصم | رصيد سلبي | WHERE balance >= amount في UPDATE |

## SQL الفعلي
```sql
START TRANSACTION;
SELECT * FROM phone_topups WHERE idempotency_key = ? FOR UPDATE;
SELECT * FROM wallets WHERE id = ? AND balance >= ? FOR UPDATE;
UPDATE wallets SET balance = balance - ? WHERE id = ?;
INSERT INTO phone_topups (phone, amount, idempotency_key, status, provider_ref) VALUES (?, ?, ?, 'completed', ?);
COMMIT;
```
