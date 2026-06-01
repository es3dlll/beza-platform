# 14 - ACID + الأقفال + الـ Race Conditions

## مشكلة Race Condition في الاستثمار

بدون أقفال، إذا أرسل مستثمران طلبين متزامنين:

```
T1: طلب 1 يقرأ current_amount = 45000 (متبقي 5000)
T2: طلب 2 يقرأ current_amount = 45000 (متبقي 5000)
T1: يستثمر 5000 → current_amount = 50000
T2: يستثمر 5000 → current_amount = 50000 (يتجاوز الهدف!)
```

**الحل**: `UPDATE deals SET current_amount = current_amount + ? WHERE id = ? AND current_amount + ? <= target_amount`

## Atomicity في الاستثمار

```php
DB::transaction(function () use ($user, $deal, $amount, $wallet) {
    // 1. قفل المحفظة
    Wallet::where('id', $wallet->id)->lockForUpdate();

    // 2. خصم ذري مع شرط الرصيد
    DB::update('UPDATE wallets SET balance = balance - ?
                WHERE id = ? AND balance >= ?', [$amount, $wallet->id, $amount]);

    // 3. زيادة ذرية مع شرط عدم التجاوز
    DB::update('UPDATE deals SET current_amount = current_amount + ?
                WHERE id = ? AND current_amount + ? <= target_amount',
                [$amount, $deal->id, $amount]);

    // 4. تسجيل الاستثمار
    DealInvestment::create([...]);
}, attempts: 3);
```

## Isolation Levels

| المستوى | الاستخدام |
|---------|-----------|
| READ COMMITTED | افتراضي — يمنع القراءة المتسخة |
| REPEATABLE READ | InnoDB الافتراضي |
| FOR UPDATE | قفل Pessimistic لمنع التعديل المتزامن |
