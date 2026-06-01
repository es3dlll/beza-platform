# 04 - علاقات قاعدة البيانات (Database Relationships)

## مخطط ER (ER Diagram)

```
  ┌────────────────────────────────┐
  │            users               │
  ├────────────────────────────────┤
  │ PK id                          │
  │ uuid (unique)                  │
  │ name                           │
  │ phone (unique)                 │
  │ password (Hash)                │
  │ pin_code (Hash)                │
  │ status: pending                │
  │ kyc_status: not_submitted      │
  │ phone_verified_at (nullable)   │
  │ fcm_token (nullable)           │
  │ device_id (nullable)           │
  │ created_at                     │
  │ updated_at                     │
  └───────────┬────────────────────┘
              │ 1
              │ hasMany
              ▼
  ┌────────────────────────────────┐
  │           wallets              │
  ├────────────────────────────────┤
  │ PK id                          │
  │ FK user_id                     │
  │ currency (SYP/USD)             │
  │ wallet_number (unique)         │
  │ balance (decimal: 15,2)        │
  │ frozen_balance (decimal: 15,2) │
  │ is_active (boolean: true)      │
  │ created_at                     │
  │ updated_at                     │
  │                                │
  │ UNIQUE: (user_id, currency)    │
  └────────────────────────────────┘
```

## شرح العلاقات

### users → wallets (1:M)
- مستخدم واحد يملك **محفظتين بالضبط**: SYP + USD
- `User hasMany Wallet`
- `Wallet belongsTo User`
- يتم إنشاء المحافظ تلقائياً بعد إنشاء المستخدم
- محفظة USD تبدأ بـ 5 USD (هدية ترحيب)
- محفظة SYP تبدأ بـ 0

## MySQL Schema

```sql
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    pin_code VARCHAR(255) NOT NULL,
    status ENUM('pending','active','suspended','blocked') DEFAULT 'pending',
    kyc_status ENUM('not_submitted','pending','verified','rejected') DEFAULT 'not_submitted',
    phone_verified_at TIMESTAMP NULL,
    fcm_token VARCHAR(255) NULL,
    device_id VARCHAR(255) NULL,
    last_login_ip VARCHAR(45) NULL,
    last_login_at TIMESTAMP NULL,
    two_factor_secret TEXT NULL,
    two_factor_recovery_codes TEXT NULL,
    two_factor_confirmed TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_phone (phone),
    INDEX idx_phone_status (phone, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE wallets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    currency ENUM('SYP','USD') NOT NULL,
    wallet_number VARCHAR(20) NOT NULL UNIQUE,
    balance DECIMAL(15,2) DEFAULT 0.00,
    frozen_balance DECIMAL(15,2) DEFAULT 0.00,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_user_currency (user_id, currency),
    CONSTRAINT fk_wallet_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## SQL Queries المرتبطة

### إدخال المستخدم
```sql
INSERT INTO users (uuid, name, phone, password, pin_code, status)
VALUES (?, ?, ?, ?, ?, 'pending');
```

### إدخال المحافظ
```sql
INSERT INTO wallets (user_id, currency, wallet_number, balance)
VALUES (?, 'SYP', ?, 0.00), (?, 'USD', ?, 5.00);
```

### التحقق من أن رقم الهاتف غير مكرر
```sql
SELECT id FROM users WHERE phone = ? LIMIT 1;
-- Index: (phone) — unique
```
