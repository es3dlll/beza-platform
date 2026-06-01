# 04 - علاقات قاعدة البيانات (Database Relationships)

## مخطط ER (ER Diagram)

```
  ┌──────────────────────────────────────┐
  │              users                   │
  ├──────────────────────────────────────┤
  │ PK id                                │
  │ phone (unique)                       │
  │ phone_verified_at (nullable, TIMESTAMP) │
  │ ...                                  │
  └──────────────────────────────────────┘
```

**ملاحظة**: OTP لا يخزن في MySQL — يخزن في Redis Cache فقط.

## Redis Schema (Cache)

| المفتاح | القيمة | TTL |
|---------|--------|-----|
| `otp_{phone}` | `{"code": "123456", "attempts": 0}` | 300s (5 دقائق) |
| `otp_{phone}_blocked` | `{"blocked_until": 1717000000}` | 600s (10 دقائق) |

## لماذا Redis وليس MySQL؟

| المقارنة | MySQL | Redis |
|-----------|-------|-------|
| سرعة القراءة/الكتابة | ~10ms | < 1ms |
| انتهاء تلقائي (TTL) | لا (يحتاج cron job) | نعم (مدمج) |
| مناسب للبيانات المؤقتة | لا | نعم |
| حجم البيانات | غير محدود | محدود (RAM) |

## SQL Queries المرتبطة

### تحديث phone_verified_at بعد التحقق الناجح
```sql
UPDATE users
SET phone_verified_at = NOW()
WHERE phone = ?
  AND phone_verified_at IS NULL;
```

### التحقق من أن الهاتف موثق
```sql
SELECT phone_verified_at FROM users WHERE phone = ?;
```

### البحث عن مستخدم برقم الهاتف (لطلب OTP)
```sql
SELECT id, phone FROM users WHERE phone = ? LIMIT 1;
```
