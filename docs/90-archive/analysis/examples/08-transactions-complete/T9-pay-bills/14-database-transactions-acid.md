# 14 - ACID + الأقفال + الـ Race Conditions

## دفع فواتير: ازدواجية الدفع لنفس الفاتورة
مشكلة: يضغط المستخدم على "دفع" مرتين بسرعة لفاتورة الكهرباء نفسها، فتُدفع مرتين.

```
Time  |  طلب 1                          |  طلب 2
------|---------------------------------|-------------------------------
T1    |  قراءة الفاتورة = غير مدفوعة   |
T2    |                                  |  قراءة الفاتورة = غير مدفوعة
T3    |  خصم 50000 ← دفع الفاتورة      |
T4    |                                  |  خصم 50000 ← دفع الفاتورة ← مدفوعة مرتين!
```

**الحل**: FOR UPDATE على سجل الفاتورة عند بدء عملية الدفع + التحقق من الحالة (status = unpaid).

## ACID في دفع الفواتير
```php
DB::transaction(function () use ($bill, $wallet) {
    // Consistency + Isolation: قفل الفاتورة
    $bill = Bill::where('id', $bill->id)
        ->where('status', 'unpaid')
        ->lockForUpdate()
        ->firstOrFail();

    // قفل المحفظة
    Wallet::where('id', $wallet->id)
        ->where('balance', '>=', $bill->amount)
        ->lockForUpdate()
        ->firstOrFail();

    // Atomicity: خصم + تحديث الفاتورة + تسجيل المعاملة
    $wallet->decrement('balance', $bill->amount);
    $bill->update(['status' => 'paid', 'paid_at' => now()]);
    BillPayment::create(['bill_id' => $bill->id, 'wallet_id' => $wallet->id, 'amount' => $bill->amount]);
}, attempts: 3);
```

## Race Conditions المحددة
| السيناريو | المشكلة | الحل |
|-----------|---------|------|
| دفع فاتورة مزدوجة | دفع 2× نفس الفاتورة | FOR UPDATE على الفاتورة + status check |
| دفع فاتورة منتهية | دفع بعد تاريخ الاستحقاق الخطأ | التحقق من due_date ضمن المعاملة |
| رصيد غير كافٍ عند الدفع | رصيد سلبي | WHERE balance >= amount |
| دفعة جزئية مكررة (installment) | دفع القسط مرتين | UNIQUE(bill_id, installment_number) |

## SQL الفعلي
```sql
START TRANSACTION;
SELECT * FROM bills WHERE id = ? AND status = 'unpaid' FOR UPDATE;
SELECT * FROM wallets WHERE id = ? AND balance >= ? FOR UPDATE;
UPDATE wallets SET balance = balance - ? WHERE id = ?;
UPDATE bills SET status = 'paid', paid_at = NOW() WHERE id = ? AND status = 'unpaid';
INSERT INTO bill_payments (bill_id, wallet_id, amount) VALUES (?, ?, ?);
COMMIT;
```
