# 14 - ACID + الأقفال + الـ Race Conditions

## سحب بنكي: ازدواجية طلب السحب مع رصيد غير كافٍ
مشكلة: يرسل المستخدم طلبي سحب متزامنين والمحفظة لا تكفي إلا لطلب واحد فقط.

```
Time  |  طلب سحب 1                      |  طلب سحب 2
------|---------------------------------|-------------------------------
T1    |  قرأ الرصيد = 100000            |
T2    |                                  |  قرأ الرصيد = 100000
T3    |  كتابة خصم 80000 → الباقي 20000 |
T4    |                                  |  كتابة خصم 80000 → الباقي -60000 ← خطأ!
```

**الحل**: `UPDATE ... WHERE balance >= amount` + `FOR UPDATE` + التحقق في same transaction.

## ACID في السحب البنكي
```php
DB::transaction(function () use ($withdraw, $wallet) {
    // Isolation + Consistency: قفل + تحقق من الرصيد
    $updated = Wallet::where('id', $wallet->id)
        ->where('balance', '>=', $withdraw->amount)
        ->lockForUpdate()
        ->decrement('balance', $withdraw->amount);

    if (!$updated) {
        throw new InsufficientBalanceException();
    }

    // Atomicity: إنشاء سجل السحب
    BankWithdraw::create([
        'user_id' => $wallet->user_id,
        'amount' => $withdraw->amount,
        'status' => 'pending',
        'bank_account_id' => $withdraw->bank_account_id,
    ]);
}, attempts: 3);
```

## Race Conditions المحددة
| السيناريو | المشكلة | الحل |
|-----------|---------|------|
| سحب مزدوج (double withdraw) | سحب أكثر من الرصيد | UPDATE balance WHERE balance >= amount |
| رصيد غير كافٍ | رصيد سلبي | التحقق + decrement في نفس الاستعلام |
| إلغاء بعد الموافقة | إعادة رصيد بدون تحقق | حقن قفل المحفظة |
| معالجة مصرفية بطيئة | السحب قبل اكتمال التحويل | state machine pending→processing→completed |

## SQL الفعلي
```sql
START TRANSACTION;
SELECT * FROM wallets WHERE id = ? FOR UPDATE;
UPDATE wallets SET balance = balance - ? WHERE id = ? AND balance >= ?;
INSERT INTO bank_withdrawals (user_id, amount, status, bank_account_id) VALUES (?, ?, 'pending', ?);
COMMIT;
```
