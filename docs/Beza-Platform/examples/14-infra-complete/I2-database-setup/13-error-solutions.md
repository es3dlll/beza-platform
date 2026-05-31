# 13 - حلول أخطاء قاعدة البيانات (Error Solutions)

## أخطاء الاتصال

| الخطأ | الحل |
|-------|------|
| `SQLSTATE[HY000] [2002] Connection refused` | تأكد من تشغيل MySQL Service |
| `SQLSTATE[HY000] [1045] Access denied` | تحقق من اسم المستخدم وكلمة المرور |
| `SQLSTATE[HY000] [2003] Can't connect to MySQL server` | MySQL لا يستمع على المنفذ 3306 |
| `PDO::__construct(): The server requested authentication method unknown` | غير الـ authentication plugin لـ mysql_native_password |

## أخطاء الترحيلات

| الخطأ | الحل |
|-------|------|
| `SQLSTATE[42S01]: Base table or view already exists` | شغل `php artisan migrate:fresh` |
| `SQLSTATE[42S02]: Base table or view not found` | تأكد من ترتيب الترحيلات |
| `SQLSTATE[23000]: Integrity constraint violation` | المفتاح الخارجي يشير إلى سجل غير موجود |
| `Class 'CreateUsersTable' not found` | شغل `composer dump-autoload` |

## أخطاء البيانات

| الخطأ | الحل |
|-------|------|
| `Data too long for column 'phone'` | زيادة الطول في الميغريشن |
| `Duplicate entry for key 'users_phone_unique'` | رقم الهاتف موجود مسبقاً |
| `Out of range value for column 'amount'` | المبلغ أكبر من DECIMAL(15,2) |
| `Deadlock found when trying to get lock` | إعادة المحاولة (Laravel retry) |

## أخطاء الأداء

| المشكلة | الحل |
|---------|------|
| استعلام بطيء | استخدم EXPLAIN لإيجاد الفهارس المفقودة |
| جداول كبيرة | أضف فهارس مركبة للاستعلامات المتكررة |
| Deadlocks متكررة | تأكد من ترتيب الأقفال (lock ordering) |
| اتصالات كثيرة | زد max_connections أو استخدم connection pooling |

## أخطاء النسخ الاحتياطي

| الخطأ | الحل |
|-------|------|
| `mysqldump: Error 2020: Got packet bigger than 'max_allowed_packet'` | زد max_allowed_packet |
| `Access denied` للمستخدم | GRANT LOCK TABLES, SELECT على المستخدم |
| `Table doesn't exist` عند الاستعادة | تأكد من وجود قاعدة البيانات |
