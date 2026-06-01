# 14 - المعاملات و ACID (Transactions & ACID)

## ACID في Beza

| الخاصية | التطبيق في Beza |
|---------|----------------|
| **Atomicity** | DB::transaction — كل العمليات تنجح أو تفشل معاً |
| **Consistency** | القيود (CHECK, FK, UNIQUE) تمنع البيانات الفاسدة |
| **Isolation** | FOR UPDATE (Pessimistic Lock) يمنع السباق |
| **Durability** | InnoDB + binlog + نسخ احتياطي |

## معاملة تحويل P2P (Atomic)

```php
DB::transaction(function () use ($fromWallet, $toWallet, $amount, $currency) {
    // 1. قفل محفظة المرسل
    $fromWallet = Wallet::where('id', $fromWallet->id)->lockForUpdate()->first();

    // 2. خصم من المرسل
    DB::update(
        'UPDATE wallets SET balance = balance - ? WHERE id = ? AND balance >= ?',
        [$amount, $fromWallet->id, $amount]
    );

    // 3. إضافة للمستقبل
    DB::update(
        'UPDATE wallets SET balance = balance + ? WHERE id = ?',
        [$amount, $toWallet->id]
    );

    // 4. تسجيل المعاملة
    Transaction::create([...]);
});
```

## مستويات العزل (Isolation Levels)

| المستوى | القراءات القذرة | القراءات غير المتكررة | القراءات الوهمية |
|---------|----------------|---------------------|----------------|
| READ UNCOMMITTED | ممكن | ممكن | ممكن |
| READ COMMITTED | آمن | ممكن | ممكن |
| REPEATABLE READ (افتراضي MySQL) | آمن | آمن | ممكن |
| SERIALIZABLE | آمن | آمن | آمن |

## التعامل مع Deadlocks

```php
// إعادة المحاولة تلقائيا عند Deadlock
use Illuminate\Support\Retry;

Retry::exponentialBackoff(3, 100)->retry(function () {
    DB::transaction(function () {
        // المعاملة الحساسة
    });
});
```

## Pessimistic vs Optimistic Locking

| النوع | متى يستخدم | مثال |
|-------|-----------|------|
| Pessimistic (FOR UPDATE) | الرصيد المالي | `Wallet::lockForUpdate()->find($id)` |
| Optimistic (version column) | تحديثات غير مالية | `updated_at` timestamp check |
