# 04 - علاقات قاعدة البيانات (Database Relationships)

## مخطط ER (ER Diagram)

```
  ┌────────────────────────────────┐
  │            users               │
  ├────────────────────────────────┤
  │ PK id                          │
  │ phone (unique)                 │
  │ password (Hash)                │
  │ status                         │
  │ kyc_status                     │
  │ last_login_at (nullable)       │
  │ last_login_ip (nullable)       │
  │ device_id (nullable)           │
  │ fcm_token (nullable)           │
  │ two_factor_confirmed           │
  └────────────────────────────────┘
          │ 1
          │ hasMany
          ▼
  ┌────────────────────────────────┐
  │   token_blacklist      │
  ├────────────────────────────────┤
  │ PK id                          │
  │ tokenable_type: 'App\Models\..'│
  │ tokenable_id (user id)         │
  │ name                           │
  │ token (unique, hash)           │
  │ abilities (JSON)               │
  │ last_used_at (nullable)        │
  │ expires_at (nullable)          │
  │ created_at                     │
  │ updated_at                     │
  └────────────────────────────────┘
```

## شرح العلاقات

### users → token_blacklist (1:M)
- مستخدم واحد يملك **توكنات متعددة** (لكل جهاز/جلسة)
- `User hasMany personalAccessTokens` (JWT)
- عند تسجيل الدخول: يتم حذف التوكنات القديمة ثم إنشاء توكن جديد
- JWT يدير جدول `token_blacklist` تلقائياً

## MySQL Schema

```sql
-- جدول token_blacklist (ينشأ تلقائياً من JWT)
CREATE TABLE token_blacklist (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tokenable_type VARCHAR(255) NOT NULL,
    tokenable_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    abilities TEXT NULL,
    last_used_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tokenable (tokenable_type, tokenable_id),
    INDEX idx_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## SQL Queries المرتبطة

### البحث عن المستخدم
```sql
SELECT * FROM users WHERE phone = ? LIMIT 1;
```

### حذف التوكنات القديمة
```sql
DELETE FROM token_blacklist
WHERE tokenable_id = ? AND tokenable_type = 'App\\Models\\User'
  AND id != ?;
```

### إنشاء توكن جديد (JWT)
```sql
INSERT INTO token_blacklist (tokenable_type, tokenable_id, name, token, abilities)
VALUES ('App\\Models\\User', ?, 'auth_token', ?, '["user"]');
```

### التحقق من محاولات الدخول الفاشلة
```sql
SELECT COUNT(*) as attempts
FROM token_blacklist
WHERE tokenable_id = ?
  AND name = 'failed_attempt'
  AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE);
```
