# 04 - علاقات الجداول — المصادقة الثنائية (2FA)

## مخطط ER (ER Diagram)

```
  ┌───────────────────────────────────────────────────┐
  │                     users                         │
  ├───────────────────────────────────────────────────┤
  │ PK id                                             │
  │ phone                                             │
  │ password                                          │
  │ ...                                               │
  │ two_factor_secret (TEXT, encrypted, nullable)     │
  │ two_factor_recovery_codes (TEXT, JSON, nullable)  │
  │ two_factor_confirmed (TINYINT(1), default: 0)     │
  │ created_at                                        │
  │ updated_at                                        │
  └───────────────────────────────────────────────────┘
```

**ملاحظة**: 2FA يستخدم أعمدة إضافية في جدول `users` فقط — لا يحتاج جداول إضافية.

## MySQL Schema (الأعمدة الإضافية)

```sql
ALTER TABLE users ADD COLUMN two_factor_secret TEXT NULL
    AFTER fcm_token;

ALTER TABLE users ADD COLUMN two_factor_recovery_codes TEXT NULL
    AFTER two_factor_secret;

ALTER TABLE users ADD COLUMN two_factor_confirmed TINYINT(1) DEFAULT 0
    AFTER two_factor_recovery_codes;
```

أو في ملف ميغريشن منفصل:

```php
Schema::table('users', function (Blueprint $table) {
    $table->text('two_factor_secret')->nullable()->after('fcm_token');
    $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
    $table->boolean('two_factor_confirmed')->default(false)->after('two_factor_recovery_codes');
});
```

## SQL Queries المرتبطة

### حفظ secret 2FA
```sql
UPDATE users
SET two_factor_secret = ENCODE(?, 'encryption_key'),
    two_factor_confirmed = 0
WHERE id = ?;
```

### تفعيل 2FA بعد التحقق
```sql
UPDATE users
SET two_factor_confirmed = 1,
    two_factor_recovery_codes = ?
WHERE id = ?;
```

### قراءة secret للتحقق
```sql
SELECT two_factor_secret FROM users WHERE id = ?;
```

### تعطيل 2FA
```sql
UPDATE users
SET two_factor_secret = NULL,
    two_factor_recovery_codes = NULL,
    two_factor_confirmed = 0
WHERE id = ?;
```

## هيكل recovery_codes (JSON)

```json
[
    "BZFA-1234-ABCD",
    "BZFA-5678-EFGH",
    "BZFA-9012-IJKL",
    "BZFA-3456-MNOP",
    "BZFA-7890-QRST",
    "BZFA-1111-UVWX",
    "BZFA-2222-YZAB",
    "BZFA-3333-CDEF"
]
```
