# 20 - أمن قاعدة البيانات (Database Security)

## 1. مبادئ أمن قاعدة البيانات

1. **أقل الصلاحيات** — كل مستخدم لديه أقل صلاحية يحتاجها
2. **تشفير البيانات الحساسة** — كلمات المرور، PIN، رموز 2FA
3. **منع SQL Injection** — parameterized queries
4. **تدقيق (Audit)** — تسجيل كل عملية حساسة
5. **النسخ الاحتياطي المشفر** — حماية النسخ الاحتياطية

## 2. صلاحيات MySQL

```sql
-- مستخدم التطبيق (قراءة/كتابة فقط)
CREATE USER 'beza_app'@'localhost' IDENTIFIED BY 'strong_password';
GRANT SELECT, INSERT, UPDATE, DELETE ON beza.* TO 'beza_app'@'localhost';

-- مستخدم الترحيلات (هيكل only)
CREATE USER 'beza_migration'@'localhost' IDENTIFIED BY 'strong_password';
GRANT ALTER, CREATE, INDEX, DROP ON beza.* TO 'beza_migration'@'localhost';

-- مستخدم النسخ الاحتياطي
CREATE USER 'beza_backup'@'localhost' IDENTIFIED BY 'strong_password';
GRANT SELECT, LOCK TABLES, SHOW VIEW, TRIGGER ON beza.* TO 'beza_backup'@'localhost';

-- رفض الوصول من الخارج
DROP USER IF EXISTS 'beza_app'@'%';
DROP USER IF EXISTS 'root'@'%';
```

## 3. تشفير البيانات الحساسة

```php
// ✅ تخزين كلمة المرور
$user->password = Hash::make($request->password);

// ✅ تخزين PIN
$user->pin_code = Hash::make($request->pin);

// ✅ تخزين سر 2FA مشفر
$user->two_factor_secret = encrypt($secret);

// ✅ إخفاء الحقول من JSON
protected $hidden = ['password', 'pin_code', 'two_factor_secret'];
```

## 4. منع SQL Injection

```php
// ❌ خطر: SQL Injection
DB::statement("UPDATE wallets SET balance = balance - {$amount} WHERE id = {$id}");

// ✅ آمن: Parameterized Query
DB::update('UPDATE wallets SET balance = balance - ? WHERE id = ?', [$amount, $id]);

// ✅ آمن: Eloquent
Wallet::where('id', $id)->decrement('balance', $amount);
```

## 5. قائمة التحقق الأمني لقاعدة البيانات

- [ ] لا يوجد مستخدم root مع صلاحية وصل عن بعد
- [ ] جميع كلمات المرور مشفرة (bcrypt/argon2)
- [ ] جميع الاستعلامات تستخدم parameterized queries
- [ ] عمود deleted_at للـ soft delete
- [ ] audit_logs يسجل كل التغييرات المهمة
- [ ] النسخ الاحتياطي مشفر
- [ ] المهلة (wait_timeout) مضبوطة
- [ ] SQL mode: STRICT_TRANS_TABLES, NO_ZERO_DATE
- [ ] لا يوجد تخزين لنصوص حساسة (كلمات مرور، PIN)
- [ ] الوصول إلى قاعدة البيانات من localhost فقط
