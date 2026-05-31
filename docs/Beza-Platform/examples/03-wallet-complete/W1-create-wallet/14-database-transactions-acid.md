# 14 - ACID + الأقفال + الـ Race Conditions

## مشكلة الـ Race Condition

بدون أقفال، إذا انطلق حدث User::created مرتين للمستخدم نفسه:

```
Time  |  مستمع 1                      |  مستمع 2
------|-------------------------------|-------------------------------
T1    |  Wallet::create(SYP)          |
T2    |                                |  Wallet::create(SYP)  ← خطأ!
T3    |  Wallet::create(USD)          |
T4    |                                |  Wallet::create(USD)  ← خطأ!
```

**النتيجة**: 4 محافظ بدلاً من 2!
**الحل**: التحقق من عدم وجود محافظ مسبقة + UNIQUE(user_id, currency)

## ضمان ACID الكامل

```
┌─────────────────────────────────────────────────────────────────┐
│                      إنشاء المحافظ + الهدية                      │
│                                                                   │
│  Atomicity:     Wallet::create(SYP) + Wallet::create(USD)        │
│                 DB::transaction {                                 │
│                   increment(USD, +5)                               │
│                   Transaction::create(deposit)                     │
│                 } ← كل شيء ينجح أو كل شيء يفشل                     │
│                                                                   │
│  Consistency:   UNIQUE(user_id, currency) → لا يوجد محفظتين       │
│                 بنفس العملة لنفس المستخدم                          │
│                 UNIQUE(wallet_number) → لا يوجد تكرار أرقام        │
│                 CHECK(balance >= 0) → منع الرصيد السالب            │
│                                                                   │
│  Isolation:     التحقق من عدم وجود محافظ مسبقة قبل الإنشاء        │
│                                                                   │
│  Durability:    InnoDB + binlog → حتى لو انقطع الكهرباء           │
│                 بعد COMMIT → البيانات محفوظة                      │
│                                                                   │
└─────────────────────────────────────────────────────────────────┘
```

## آلية القفل (Locking Mechanism)

### في W1، لا نحتاج FOR UPDATE لأن:
1. إنشاء المحافظ يتم مرة واحدة فقط لكل مستخدم
2. UNIQUE(user_id, currency) يمنع التكرار
3. التحقق من `$user->wallets()->exists()` يمنع إعادة الإنشاء

### لكن الهدية (5$) تحتاج DB::transaction:

```php
DB::transaction(function () use ($usdWallet, &$bonusTransaction) {
    // increment يستخدم UPDATE ... WHERE is_active = 1
    $this->walletService->increment($usdWallet, 5.00);

    // تسجيل معاملة الإيداع
    $bonusTransaction = Transaction::create([...]);
}, attempts: 3);
```

## معالجة Deadlocks

في W1، Deadlock unlikely لأن:
- لا يوجد قفل تنافسي على المحافظ (المستخدم جديد)
- الإيداع يتم لمرة واحدة
- محفظة USD جديدة ليس عليها منافسة

## استعلامات SQL الفعلية

```sql
-- 1. إنشاء محفظة SYP
INSERT INTO wallets (user_id, currency, wallet_number, balance, frozen_balance, is_active, created_at, updated_at)
VALUES (1, 'SYP', '621234567890', 0.00, 0.00, 1, NOW(), NOW());

-- 2. إنشاء محفظة USD
INSERT INTO wallets (user_id, currency, wallet_number, balance, frozen_balance, is_active, created_at, updated_at)
VALUES (1, 'USD', '631234567890', 0.00, 0.00, 1, NOW(), NOW());

-- 3. بدء المعاملة للهدية
START TRANSACTION;

-- 4. إضافة هدية 5$ مع التحقق من نشاط المحفظة
UPDATE wallets
SET balance = balance + 5.00
WHERE id = 2 AND is_active = 1;

-- 5. تسجيل الإيداع
INSERT INTO transactions (to_wallet_id, amount, amount_in_usd, type, status, reference_number, description, fee, completed_at, created_at, updated_at)
VALUES (2, 5.00, 5.00, 'deposit', 'completed', 'BZ270526...', 'هدية ترحيبية', 0.00, NOW(), NOW(), NOW());

-- 6. إغلاق المعاملة
COMMIT;
```

## سيناريوهات الفشل والتعافي

| السيناريو | ماذا يحدث؟ | التعافي |
|-----------|-----------|---------|
| قطع الكهرباء بعد إنشاء المستخدم وقبل إنشاء المحفظة | المستخدم موجود بدون محافظ | Job مخصص يعيد المحاولة |
| فشل إيداع الهدية بعد إنشاء المحفظة | ROLLBACK → رصيد 0 بدون هدية | إعادة المحاولة يدوياً |
| duplicate wallet_number | Unique constraint | do-while يضمن التفرد |
| duplicate (user_id, currency) | Unique constraint | التحقق المسبق يمنعه |
