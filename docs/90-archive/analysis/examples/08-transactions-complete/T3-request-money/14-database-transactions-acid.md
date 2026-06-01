# 14 - ACID + الأقفال + الـ Race Conditions

## طلب مال: ازدواجية الطلب والموافقة
مشكلة: يرسل المستخدم طلبين متطابقين في نفس الوقت، أو يوافق الدافع على نفس الطلب مرتين.

```
Time  |  طالب المال                     |  دافع
------|---------------------------------|-------------------------------
T1    |  إرسال طلب بـ 50000             |
T2    |  إرسال طلب بـ 50000 (مكرر)      |
T3    |                                  |  الموافقة على الطلب الأول
T4    |                                  |  الموافقة على الطلب الثاني ← خطأ!
T5    |  خصم 50000 + 50000 = 100000     |
```

**الحل**: UNIQUE constraint على (requester_id + payee_id + amount + status pending) + قفل الطلب عند الموافقة.

## ACID في طلب المال
```php
DB::transaction(function () use ($request, $fromWallet, $toWallet) {
    // Consistency: لا يوجد طلب pending مكرر
    $existing = MoneyRequest::where('requester_id', $request->requester_id)
        ->where('payee_id', $request->payee_id)
        ->where('amount', $request->amount)
        ->where('status', 'pending')
        ->lockForUpdate()
        ->first();

    // Isolation: قفل محفظة الدافع للخصم
    Wallet::where('id', $fromWallet->id)->lockForUpdate()->first();

    // Atomicity: تحديث الطلب + خصم + إضافة
    $request->update(['status' => 'approved', 'approved_at' => now()]);
    $fromWallet->decrement('balance', $request->amount);
    $toWallet->increment('balance', $request->amount);
}, attempts: 3);
```

## Race Conditions المحددة
| السيناريو | المشكلة | الحل |
|-----------|---------|------|
| طلب مكرر (double request) | إنشاء طلبين pending | UNIQUE (requester, payee, amount, status=pending) |
| موافقة مزدوجة (double approve) | تحويل المبلغ مرتين | FOR UPDATE + status check قبل التحديث |
| إلغاء بعد الموافقة | تناقض البيانات | التحقق من status بالفعل |
| قبول طلب منتهي | خصم بعد انتهاء الطلب | التحقق من expires_at |

## SQL الفعلي
```sql
START TRANSACTION;
SELECT * FROM money_requests WHERE id = ? AND status = 'pending' FOR UPDATE;
SELECT * FROM wallets WHERE id = ? FOR UPDATE;
UPDATE money_requests SET status = 'approved', approved_at = NOW() WHERE id = ? AND status = 'pending';
UPDATE wallets SET balance = balance - ? WHERE id = ? AND balance >= ?;
UPDATE wallets SET balance = balance + ? WHERE id = ?;
COMMIT;
```
