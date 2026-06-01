# 14 - ACID + الأقفال + الـ Race Conditions

## سحب عبر وكيل: تسليم النقود والتأكيد
مشكلة: يؤكد الوكيل تسليم النقود بينما العميل يلغي الطلب في نفس اللحظة. أو يؤكد الوكيل التسليم مرتين.

```
Time  |  عميل                          |  وكيل
------|--------------------------------|-------------------------------
T1    |  طلب سحب 50000                |
T2    |  إلغاء الطلب                  |
T3    |                                |  تأكيد التسليم ← خصم 50000 بدون استلام!
```

**الحل**: State machine مع validation + قفل الطلب عند التأكيد.

## ACID في سحب وكيل
```php
DB::transaction(function () use ($request, $agentWallet, $customerWallet) {
    // Isolation: قفل طلب السحب لمنع التحديث المتزامن
    $withdraw = AgentWithdraw::where('id', $request->id)
        ->where('status', 'pending')
        ->lockForUpdate()
        ->firstOrFail();

    // قفل محفظة الوكيل (للعمولة) ومحفظة العميل
    Wallet::whereIn('id', [$agentWallet->id, $customerWallet->id])
        ->orderBy('id')->lockForUpdate()->get();

    // Atomicity: خصم من العميل + تسجيل التسليم
    $customerWallet->decrement('balance', $withdraw->amount);
    $agentWallet->increment('balance', $withdraw->fee);
    $withdraw->update(['status' => 'completed', 'completed_at' => now()]);
}, attempts: 3);
```

## Race Conditions المحددة
| السيناريو | المشكلة | الحل |
|-----------|---------|------|
| إلغاء وتأكيد متزامنان | خصم بعد الإلغاء | FOR UPDATE + status check |
| تأكيد تسليم مزدوج | خصم مرتين | WHERE status='pending' في UPDATE |
| استلام ناقص | وكيل يدخل مبلغاً أقل | التحقق من amount match |
| تعطل اتصال الوكيل | تسليم بدون تأكيد | مهلة (timeout) مع إلغاء تلقائي |

## SQL الفعلي
```sql
START TRANSACTION;
SELECT * FROM agent_withdrawals WHERE id = ? AND status = 'pending' FOR UPDATE;
SELECT * FROM wallets WHERE id IN (?, ?) ORDER BY id FOR UPDATE;
UPDATE wallets SET balance = balance - ? WHERE id = ? AND balance >= ?;
UPDATE wallets SET balance = balance + ? WHERE id = ?;
UPDATE agent_withdrawals SET status = 'completed', completed_at = NOW() WHERE id = ? AND status = 'pending';
COMMIT;
```
