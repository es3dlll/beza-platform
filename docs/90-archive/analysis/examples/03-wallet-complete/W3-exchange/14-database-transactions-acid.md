# 14 - ACID + الأقفال + الـ Race Conditions

## مشكلة الـ Race Condition

بدون أقفال، إذا أرسل المستخدم طلبين صرافة متزامنين:

```
Time  |  طلب 1 (SYP→USD 50,000)        |  طلب 2 (SYP→USD 50,000)
------|---------------------------------|---------------------------------
T1    |  قرأ رصيد SYP = 100,000         |
T2    |                                  |  قرأ رصيد SYP = 100,000
T3    |  خصم 50,000 + 750 رسوم          |
T4    |                                  |  خصم 50,000 + 750 رسوم
T5    |  كتب رصيد = 49,250              |
T6    |                                  |  كتب رصيد = 49,250 ← خطأ!
```

**النتيجة**: تم صرف 101,500 رغم أن الرصيد 100,000 فقط!
**الحل**: `SELECT ... FOR UPDATE` + `UPDATE ... WHERE balance >= amount`

## ضمان ACID الكامل

```
┌─────────────────────────────────────────────────────────────────┐
│                      DB::transaction(function) {                 │
│                                                                   │
│  Atomicity:     SELECT ... FOR UPDATE (قفل المحفظتين)            │
│                 UPDATE wallets SET balance = balance - (amt+رسوم) │
│                 UPDATE wallets SET balance = balance + converted  │
│                 INSERT INTO transactions (...)                    │
│                 → كل شيء ينجح أو كل شيء يفشل                      │
│                                                                   │
│  Consistency:   منع تحويل SYP → SYP (منطق التطبيق)               │
│                 UNIQUE(reference_number) → لا يوجد تكرار          │
│                 WHERE balance >= (amount + fee) → منع الرصيد      │
│                 Config يحدد min amount                             │
│                                                                   │
│  Isolation:     SELECT ... FOR UPDATE → أقفال Pessimistic         │
│                 → طلب 2 ينتظر حتى يكتمل طلب 1                     │
│                 ترتيب تصاعدي لـ ID يمنع Deadlock                   │
│                                                                   │
│  Durability:    InnoDB + binlog → حتى لو انقطع الكهرباء           │
│                 بعد COMMIT → البيانات محفوظة                      │
│                                                                   │
│  }                                                                │
└─────────────────────────────────────────────────────────────────┘
```

## آلية القفل (Locking Mechanism)

### 1️⃣ قفل الترتيب الثابت (Fixed Order Locking)

```php
// ترتيب المحافظ تصاعدياً حسب ID — يضمن عدم Deadlock
$walletIds = [$fromWallet->id, $toWallet->id];
sort($walletIds);

foreach ($walletIds as $id) {
    $this->walletService->lockForUpdate($id);
}
```

### 2️⃣ UPDATE الآمن

```php
// WHERE balance >= amount + fee → يضمن عدم الرصيد السالب
DB::update(
    'UPDATE wallets SET balance = balance - ? WHERE id = ? AND balance >= ? AND is_active = ?',
    [$totalDeduction, $wallet->id, $totalDeduction, true]
);
// إذا كانت 0 rows متأثرة → رصيد غير كافٍ
```

## معالجة Deadlocks

```
المعاملة 1:  قفل المحفظة A → قفل المحفظة B  (ينتظر B)
المعاملة 2:  قفل المحفظة B → قفل المحفظة A  (ينتظر A)
             → DEADLOCK!
```

### الحل:
1. **قفل بترتيب تصاعدي** (sort wallet IDs)
2. **`DB::transaction(..., attempts: 3)`** — InnoDB يكشف Deadlock ويعيد المحاولة

## استعلامات SQL الفعلية (مع أقفال)

```sql
-- 1. فتح المعاملة
START TRANSACTION;

-- 2. قفل محفظة المصدر (SYP)
SELECT id, balance FROM wallets WHERE id = 1 FOR UPDATE;

-- 3. قفل محفظة الوجهة (USD)
SELECT id, balance FROM wallets WHERE id = 2 FOR UPDATE;

-- 4. خصم (amount + fee = 100000 + 1500)
UPDATE wallets SET balance = balance - 101500
WHERE id = 1 AND balance >= 101500 AND is_active = 1;

-- 5. إضافة المحول (100000 / 13000 = 7.69)
UPDATE wallets SET balance = balance + 7.69
WHERE id = 2 AND is_active = 1;

-- 6. تسجيل المعاملة
INSERT INTO transactions (from_wallet_id, to_wallet_id, amount, amount_in_usd,
    type, status, reference_number, fee, metadata, completed_at)
VALUES (1, 2, 100000.00, 7.69, 'exchange', 'completed', 'BZ270526...', 1500.00,
    '{"rate":13000,"from_currency":"SYP","to_currency":"USD","converted_amount":7.69,"fee_percentage":1.5}',
    NOW());

-- 7. إغلاق المعاملة
COMMIT;
```

## سيناريوهات الفشل والتعافي

| السيناريو | ماذا يحدث؟ | التعافي |
|-----------|-----------|---------|
| قطع الكهرباء بعد الخصم وقبل الإضافة | `ROLLBACK` → لا شيء يتغير | يعيد المستخدم المحاولة |
| Deadlock بين صرافتين | InnoDB يختار "ضحية" ← `ROLLBACK` | `attempts: 3` يعيد المحاولة |
| duplicate reference_number | Unique constraint violation | `generateReferenceNumber()` يمنع التكرار |
| فشل جلب سعر الصرف | `RateNotFoundException` | 503 Service Unavailable |
