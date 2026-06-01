# 14 - ACID + الأقفال + الـ Race Conditions

## إيداع بنكي: ازدواجية تأكيد الإيداع وسير الموافقة
مشكلة: يرسل المستخدم إيصال إيداع بنكي، ثم يحاول تأكيد نفس الإيصال مرتين قبل معالجة الطلب.

```
Time  |  مستخدم                         |  Admin
------|---------------------------------|-------------------------------
T1    |  رفع إيصال إيداع 50000          |
T2    |  رفع نفس الإيصال مرة أخرى        |
T3    |                                  |  الموافقة على الأول → إضافة 50000
T4    |                                  |  الموافقة على الثاني → إضافة 50000 مُكررة!
```

**الحل**: UNIQUE constraint على transaction_reference + pending approval workflow مع حالة middleware (pending → approved → completed).

## ACID في الإيداع البنكي
```php
DB::transaction(function () use ($deposit, $wallet) {
    // Consistency: منع تكرار الإيصال
    $existing = BankDeposit::where('transaction_reference', $deposit->transaction_reference)
        ->lockForUpdate()
        ->first();

    // Atomicity: فقط pending يمكن الموافقة عليه
    $deposit = BankDeposit::where('id', $deposit->id)
        ->where('status', 'pending')
        ->lockForUpdate()
        ->firstOrFail();

    // تحديث الحالة وإضافة الرصيد
    $deposit->update(['status' => 'approved', 'approved_at' => now()]);
    Wallet::where('id', $wallet->id)->lockForUpdate()->increment('balance', $deposit->amount);
});
```

## سير الموافقة (Approval Workflow)
```
pending ──→ approved ──→ completed
  │            │
  └──rejected──┘
```

## Race Conditions المحددة
| السيناريو | المشكلة | الحل |
|-----------|---------|------|
| إيصال مكرر (duplicate slip) | إضافة رصيد مرتين | UNIQUE(transaction_reference) |
| موافقة مزدوجة من Admin | تحديث same deposit | WHERE status='pending' في UPDATE |
| تحرير الإيداع أثناء الموافقة | بيانات غير متناسقة | FOR UPDATE + transaction |
| رفض بعد الموافقة | فقدان رصيد | state machine مع validation |

## SQL الفعلي
```sql
START TRANSACTION;
SELECT * FROM bank_deposits WHERE id = ? AND status = 'pending' FOR UPDATE;
SELECT * FROM wallets WHERE id = ? FOR UPDATE;
UPDATE bank_deposits SET status = 'approved', approved_at = NOW() WHERE id = ? AND status = 'pending';
UPDATE wallets SET balance = balance + ? WHERE id = ?;
COMMIT;
```
