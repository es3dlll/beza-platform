# 14 - ACID + الأقفال + الـ Race Conditions

## مشكلة الـ Race Condition

بدون أقفال، إذا أرسل المستخدم طلبين متزامنين:

```
Time  |  طلب 1                        |  طلب 2
------|-------------------------------|-------------------------------
T1    |  قرأ الرصيد = 500             |
T2    |                                |  قرأ الرصيد = 500
T3    |  خصم 400 → الرصيد = 100       |
T4    |                                |  خصم 400 → الرصيد = 100 ← خطأ!
T5    |  كتب 100                      |
T6    |                                |  كتب 100 ← 100 وليس -300 الصحيح!
```

**النتيجة**: تم تحويل 800 رغم أن الرصيد 500 فقط!
**الحل**: `SELECT ... FOR UPDATE` + `UPDATE ... WHERE balance >= amount`

## ضمان ACID الكامل

```
┌─────────────────────────────────────────────────────────────────┐
│                      DB::transaction(function) {                 │
│                                                                   │
│  Atomicity:     SELECT ... FOR UPDATE (قفل المحفظتين)            │
│                 UPDATE wallets SET balance = balance - amount     │
│                 UPDATE wallets SET balance = balance + amount     │
│                 INSERT INTO transactions (...)                    │
│                 → كل شيء ينجح أو كل شيء يفشل                      │
│                                                                   │
│  Consistency:   UNIQUE(user_id, currency) → لا يوجد محفظتين بنفس  │
│                 العملة لنفس المستخدم                              │
│                 UNIQUE(reference_number) → لا يوجد تكرار          │
│                 WHERE balance >= amount → منع الرصيد السالب       │
│                                                                   │
│  Isolation:     SELECT ... FOR UPDATE → أقفال Pessimistic         │
│                 → طلب 2 ينتظر حتى يكتمل طلب 1                     │
│                                                                   │
│  Durability:    InnoDB + binlog → حتى لو انقطع الكهرباء           │
│                 بعد COMMIT → البيانات محفوظة                      │
│                                                                   │
│  }                                                                │
└─────────────────────────────────────────────────────────────────┘
```

## آلية القفل (Locking Mechanism)

### 1️⃣ Pessimistic Lock (`FOR UPDATE`)

```php
// يمنع أي عملية كتابة أخرى على الصف حتى نهاية TRANSACTION
Wallet::where('id', $walletId)->lockForUpdate()->first();
```

```
المعاملة 1:  BEGIN
             SELECT * FROM wallets WHERE id=1 FOR UPDATE;   ← قفل
  
المعاملة 2:  BEGIN
             SELECT * FROM wallets WHERE id=1 FOR UPDATE;   ← ينتظر!

المعاملة 1:  UPDATE wallets SET balance = balance - 100;
             COMMIT;                                         ← تحرير القفل

المعاملة 2:  ↑ يستمر الآن — يقرأ الرصيد الجديد
```

### 2️⃣ Optimistic Lock (غير مستخدم هنا — مناسب للحالات قليلة التزاحم)

```php
// استخدام version column
UPDATE wallets SET balance = balance - 100, version = version + 1
WHERE id = ? AND version = ?;
// إذا تأثر 0 rows → شخص آخر عدّل البيانات → retry
```

## معالجة Deadlocks

عند قفل محفظتين بترتيب مختلف بين معاملتين:

```
المعاملة 1:  قفل المحفظة A → قفل المحفظة B  (ينتظر B)
المعاملة 2:  قفل المحفظة B → قفل المحفظة A  (ينتظر A)
             → DEADLOCK!
```

### الحل: قفل الترتيب الثابت (Fixed Order Locking)

```php
// ترتيب المحافظ تصاعدياً حسب ID — يضمن عدم Deadlock
$walletIds = [$fromWallet->id, $toWallet->id];
sort($walletIds);

foreach ($walletIds as $id) {
    Wallet::where('id', $id)->lockForUpdate()->first();
}
```

### أو باستخدام `attempts: 3`

```php
DB::transaction(function () {
    // ...
}, attempts: 3);  // ← يعيد المحاولة 3 مرات إذا حدث Deadlock
```

## الفرق بين القفل والتجميد (Lock vs Freeze)

| الميزة | FOR UPDATE | frozen_balance |
|--------|------------|----------------|
| المدة | أثناء DB transaction فقط | حتى يتم تحريرها يدوياً |
| الغرض | منع التعديل المتزامن على نفس الصف | حجز رصيد لمعاملة معلقة |
| المثال | تحويل P2P | طلب سحب قيد المعالجة |
| مؤقت؟ | نعم — ينتهي مع COMMIT/ROLLBACK | لا — يحتاج unfreeze() |

## استعلامات SQL الفعلية (مع أقفال)

```sql
-- 1. فتح المعاملة
START TRANSACTION;

-- 2. قفل محفظة المرسل
SELECT id, balance, frozen_balance, is_active
FROM wallets
WHERE id = 1
FOR UPDATE;

-- 3. التحقق من الرصيد يدوياً
-- (في التطبيق)

-- 4. خصم
UPDATE wallets
SET balance = balance - 100
WHERE id = 1 AND balance >= 100 AND is_active = 1;

-- 5. قفل محفظة المستقبل
SELECT id, balance, frozen_balance, is_active
FROM wallets
WHERE id = 2
FOR UPDATE;

-- 6. إضافة
UPDATE wallets
SET balance = balance + 100
WHERE id = 2 AND is_active = 1;

-- 7. تسجيل المعاملة
INSERT INTO transactions (from_wallet_id, to_wallet_id, amount, amount_in_usd,
    type, status, reference_number, fee, completed_at, created_at, updated_at)
VALUES (1, 2, 100, 100, 'transfer', 'completed', 'BZ260527...', 0, NOW(), NOW(), NOW());

-- 8. إغلاق المعاملة
COMMIT;
```

## سيناريوهات الفشل والتعافي

| السيناريو | ماذا يحدث؟ | التعافي |
|-----------|-----------|---------|
| قطع الكهرباء بعد الخصم وقبل الإضافة | `ROLLBACK` → لا شيء يتغير | يعيد المستخدم المحاولة |
| Deadlock بين معاملتين | InnoDB يختار "ضحية" ← `ROLLBACK` | `attempts: 3` يعيد المحاولة تلقائياً |
| انتهاء timeout القفل | استثناء `LockWaitTimeout` | زيادة `innodb_lock_wait_timeout` أو تحسين سرعة المعاملة |
| duplicate reference_number | Unique constraint violation | `generateReferenceNumber()` يستخدم timestamp + random لمنع التكرار |
