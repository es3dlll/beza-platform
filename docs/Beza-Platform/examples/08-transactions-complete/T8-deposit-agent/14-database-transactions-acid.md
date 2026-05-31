# 14 - ACID + الأقفال + الـ Race Conditions

## إيداع عبر وكيل: استلام النقود وتأكيد الإيداع
مشكلة: العميل يسلم النقود للوكيل، والوكيل يؤكد الإيداع لكن الاتصال ينقطع. يعيد الوكيل التأكيد فتُضاف مرتين.

```
Time  |  وكيل                          |  عميل
------|--------------------------------|-------------------------------
T1    |  استلام 50000 نقداً           |
T2    |  تأكيد الإيداع → إضافة 50000  |
T3    |  انقطاع اتصال                 |
T4    |  إعادة تأكيد الإيداع           |
T5    |  إضافة 50000 مرة أخرى ← خطأ!  |
```

**الحل**: Idempotency key على كل تأكيد إيداع + حالة (pending → confirmed → completed).

## ACID في إيداع وكيل
```php
DB::transaction(function () use ($deposit, $agentWallet, $customerWallet) {
    // Atomicity: idempotency key يمنع الإضافة المكررة
    $existing = AgentDeposit::where('idempotency_key', $deposit->idempotency_key)
        ->lockForUpdate()
        ->first();

    if ($existing && $existing->status === 'completed') {
        throw new DuplicateDepositException();
    }

    // Isolation: قفل محفظة العميل
    Wallet::where('id', $customerWallet->id)->lockForUpdate()->first();

    // التحقق من أن الوكيل لديه سيولة نقدية كافية (optionally)
    // Atomicity: إضافة للعميل + عمولة للوكيل + تحديث الحالة
    $customerWallet->increment('balance', $deposit->amount);
    $agentWallet->increment('balance', $deposit->fee);
    $deposit->update(['status' => 'completed', 'confirmed_at' => now()]);
}, attempts: 3);
```

## Race Conditions المحددة
| السيناريو | المشكلة | الحل |
|-----------|---------|------|
| تأكيد مزدوج (انقطاع اتصال) | إضافة رصيد مرتين | Idempotency key + UNIQUE |
| عميل يلغي بعد التسليم | فقدان النقود | الوكيل يؤكد أولاً ثم تأكيد العميل |
| وكيل يضيف مبلغاً أكبر | إضافة رصيد زائد | التحقق من صحة المبلغ مع الطلب |
| ازدواجية في إضافة العمولة | الوكيل يكسب عمولة مضاعفة | Atomicity في تحديث الحالة |

## SQL الفعلي
```sql
START TRANSACTION;
SELECT * FROM agent_deposits WHERE idempotency_key = ? FOR UPDATE;
SELECT * FROM wallets WHERE id = ? FOR UPDATE;
UPDATE agent_deposits SET status = 'completed', confirmed_at = NOW() WHERE id = ? AND status = 'pending';
UPDATE wallets SET balance = balance + ? WHERE id = ?;
UPDATE wallets SET balance = balance + ? WHERE id = ?;
COMMIT;
```
