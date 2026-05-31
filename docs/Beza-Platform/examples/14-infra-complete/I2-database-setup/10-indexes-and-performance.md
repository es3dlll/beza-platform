# 10 - الفهارس وتحسين الأداء (Indexes & Performance)

## قائمة الفهارس

| الجدول | الفهرس | النوع | السبب |
|--------|--------|-------|-------|
| users | phone | UNIQUE | البحث برقم الهاتف |
| users | email | UNIQUE | البحث بالبريد |
| users | (phone, status) | INDEX | التحقق من النشاط |
| wallets | (user_id, currency) | UNIQUE | منع تكرار المحفظة |
| wallets | wallet_number | UNIQUE | رقم المحفظة فريد |
| wallets | user_id | INDEX | استعلامات المحافظ |
| transactions | reference_number | UNIQUE | الرقم المرجعي |
| transactions | (from_wallet_id, status) | INDEX | بحث معاملات المصدر |
| transactions | (to_wallet_id, status) | INDEX | بحث معاملات الوجهة |
| transactions | (type, created_at) | INDEX | تصفية حسب النوع والتاريخ |
| transactions | created_at | INDEX | ترتيب زمني |
| audit_logs | (event_type, created_at) | INDEX | تصفية سجل التدقيق |
| notifications | (notifiable_id, notifiable_type) | INDEX | إشعارات المستخدم |
| jobs | (queue, reserved_at) | INDEX | معالجة قائمة الانتظار |

## أمثلة استعلامات مع تحسين

```sql
-- بطيء: بدون فهرس
SELECT * FROM transactions WHERE from_wallet_id = 1 AND status = 'completed';

-- سريع: مع فهرس مركب
-- INDEX idx_from_wallet_status (from_wallet_id, status)

-- بطيء: LIKE على عمود بدون فهرس
SELECT * FROM users WHERE email LIKE '%@beza.example';

-- سريع: فهرس على email
-- INDEX idx_email (email)
```

## تحليل الاستعلامات

```bash
# شرح خطة التنفيذ
EXPLAIN SELECT * FROM transactions WHERE reference_number = 'BZ260527143200A1B2C3';

# تحديد الاستعلامات البطيئة
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 2;
```
