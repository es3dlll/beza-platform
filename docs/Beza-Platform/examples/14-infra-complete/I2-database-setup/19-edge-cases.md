# 19 - حالات الحافة (Edge Cases)

## 1. Deadlock بين معاملتين

```sql
-- المعاملة أ: خصم من wallet 1 → إضافة لـ wallet 2
-- المعاملة ب: خصم من wallet 2 → إضافة لـ wallet 1
-- ⚠️ Deadlock! كل معاملة تنتظر الأخرى
-- ✅ الحل: ترتيب موحد للأقفال (sorted lock ordering)
```

## 2. Overflow في DECIMAL

```sql
-- DECIMAL(15,2) max = 9999999999999.99
-- إذا تجاوز المبلغ هذا الرقم → Out of range error
-- ✅ الحل: التحقق قبل التحديث
```

## 3. NULL في المفاتيح الخارجية

```php
// from_wallet_id = NULL للإيداعات النقدية
// to_wallet_id = NULL للسحوبات النقدية
// ✅ الحل: استخدام ->nullable() في الميغريشن
```

## 4. سباق (Race Condition) على الرصيد

```php
// ❌ خطأ: قراءة ثم تحديث
$balance = $wallet->balance;
if ($balance >= 100) {
    $wallet->decrement('balance', 100); // قد يكون الرصيد تغير!
}

// ✅ صحيح: تحديث ذري
DB::update('UPDATE wallets SET balance = balance - ? WHERE id = ? AND balance >= ?', [100, $id, 100]);
```

## 5. ترميز utf8mb4

```sql
-- ❌ خطأ: emoji والرموز لا تدخل
INSERT INTO users (name) VALUES ('أحمد 👍');

-- ✅ صحيح: استخدام utf8mb4
ALTER DATABASE beza CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE users CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

## 6. ترحيل البيانات

```php
// إضافة عمود لجدول كبير
Schema::table('transactions', function (Blueprint $table) {
    // ❌ في الإنتاج: يقفل الجدول
    // ✅ صحيح: استخدام pt-online-schema-change أو分批
});

// الحل: استخدام package مثل:
// composer require khsci/laravel-schema-helper
```
