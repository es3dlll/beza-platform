# 04 - علاقات الجداول (Database Relationships)

## مخطط ER (ER Diagram) (Entity-Relationship)

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                          ER Diagram — Transfer Flow                         │
└─────────────────────────────────────────────────────────────────────────────┘

  ┌──────────────────┐              ┌──────────────────┐
  │      users       │              │      users       │
  │──────────────────│              │──────────────────│
  │ PK id            │              │ PK id            │
  │ uuid (unique)    │              │ uuid (unique)    │
  │ name             │              │ name             │
  │ phone (unique)   │◄────────────►│ phone (unique)   │
  │ password         │   Sender     │ password         │   Receiver
  │ pin_code         │              │ pin_code         │
  │ status           │              │ status           │
  │ kyc_status       │              │ kyc_status       │
  │ fcm_token        │              │ fcm_token        │
  │ ...              │              │ ...              │
  └────────┬─────────┘              └────────┬─────────┘
           │ 1                              │ 1
           │                                 │
           │ hasMany                        │ hasMany
           ▼                                 ▼
  ┌──────────────────┐              ┌──────────────────┐
  │     wallets      │              │     wallets      │
  │──────────────────│              │──────────────────│
  │ PK id            │              │ PK id            │
  │ FK user_id       │              │ FK user_id       │
  │ currency (SYP/USD)│              │ currency (SYP/USD)│
  │ balance          │              │ balance          │
  │ frozen_balance   │              │ frozen_balance   │
  │ wallet_number    │              │ wallet_number    │
  │ is_active        │              │ is_active        │
  │                  │              │                  │
  │ UNIQUE:          │              │ UNIQUE:          │
  │ (user_id,currency)│             │ (user_id,currency)│
  └────────┬─────────┘              └────────┬─────────┘
           │                                 │
           │ 1                               1│
           │                                 │
           └──────────────┬──────────────────┘
                          │
                          │ foreignId(from_wallet_id)
                          │ foreignId(to_wallet_id)
                          ▼
            ┌──────────────────────────────┐
            │        transactions          │
            │──────────────────────────────│
            │ PK id                        │
            │ FK from_wallet_id (nullable) │
            │ FK to_wallet_id (nullable)   │
            │ amount                       │
            │ amount_in_usd                │
            │ type: 'transfer'             │
            │ status: 'completed'          │
            │ reference_number (unique)    │
            │ description (nullable)       │
            │ fee: 0                       │
            │ metadata (nullable)          │
            │ completed_at                 │
            │ created_at                   │
            │                              │
            │ INDEX: from_wallet_id,       │
            │        to_wallet_id,         │
            │        type, created_at,     │
            │        reference_number      │
            └──────────────────────────────┘
```

## شرح العلاقات

### users → wallets (1:M)
- كل مستخدم يملك **محفظتين بالضبط**: SYP + USD
- العلاقة: `User hasMany Wallet`
- `Wallet belongsTo User`
- المفتاح: `wallets.user_id → users.id`

### wallets → transactions (1:M)
- كل محفظة يمكن أن تكون المصدر (from_wallet) أو الوجهة (to_wallet)
- `Transaction belongsTo fromWallet` (nullable — النقدي ليس له مصدر)
- `Transaction belongsTo toWallet` (nullable — السحب النقدي ليس له وجهة)
- `Wallet hasMany transactions (as sender)`
- `Wallet hasMany transactions (as receiver)`

## SQL Queries المرتبطة

### البحث عن المستخدم برقم الهاتف
```sql
SELECT * FROM users WHERE phone = ? AND deleted_at IS NULL LIMIT 1;
-- Index: (phone) — unique
```

### خصم من المحفظة
```sql
UPDATE wallets
SET balance = balance - ?
WHERE id = ? AND balance >= ? AND is_active = 1;
-- مهم: WHERE balance >= amount لمنع الرصيد السالب
```

### إضافة للمحفظة
```sql
UPDATE wallets
SET balance = balance + ?
WHERE id = ? AND is_active = 1;
```

### تسجيل المعاملة
```sql
INSERT INTO transactions (from_wallet_id, to_wallet_id, amount, amount_in_usd,
  type, status, reference_number, description, fee, completed_at, created_at, updated_at)
VALUES (?, ?, ?, ?, 'transfer', 'completed', ?, ?, 0, NOW(), NOW(), NOW());
```

### التحقق من الحد اليومي
```sql
SELECT COALESCE(SUM(amount), 0) as daily_total
FROM transactions
WHERE from_wallet_id = ?
  AND type = 'transfer'
  AND created_at >= CURDATE()
  AND status = 'completed';
```

## MySQL Schema (CREATE TABLE Statements)

```sql
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NULL UNIQUE,
    phone VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    pin_code VARCHAR(255) NULL,
    avatar VARCHAR(255) NULL,
    status ENUM('pending','active','suspended','blocked') DEFAULT 'pending',
    kyc_status ENUM('not_submitted','pending','verified','rejected') DEFAULT 'not_submitted',
    phone_verified_at TIMESTAMP NULL,
    email_verified_at TIMESTAMP NULL,
    two_factor_secret TEXT NULL,
    two_factor_recovery_codes TEXT NULL,
    is_admin TINYINT(1) DEFAULT 0,
    is_merchant TINYINT(1) DEFAULT 0,
    is_agent TINYINT(1) DEFAULT 0,
    preferences JSON NULL,
    device_id VARCHAR(255) NULL,
    fcm_token VARCHAR(255) NULL,
    last_login_ip VARCHAR(45) NULL,
    last_login_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    INDEX idx_phone_status (phone, status),
    INDEX idx_email (email)
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
    INDEX idx_wallet_number (wallet_number),
    CONSTRAINT fk_wallet_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    from_wallet_id BIGINT UNSIGNED NULL,
    to_wallet_id BIGINT UNSIGNED NULL,
    amount DECIMAL(15,2) NOT NULL,
    amount_in_usd DECIMAL(15,2) NOT NULL,
    type ENUM('deposit','withdraw','transfer','exchange','merchant_payment',
              'agent_cash_in','agent_cash_out','investment','investment_profit',
              'card_load','card_payment','fee') NOT NULL,
    status ENUM('pending','processing','completed','failed','cancelled','refunded') DEFAULT 'pending',
    reference_number VARCHAR(50) NOT NULL UNIQUE,
    description TEXT NULL,
    fee DECIMAL(15,2) DEFAULT 0.00,
    metadata JSON NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_from_wallet (from_wallet_id, status),
    INDEX idx_to_wallet (to_wallet_id, status),
    INDEX idx_type_created (type, created_at),
    INDEX idx_reference (reference_number),
    CONSTRAINT fk_txn_from_wallet FOREIGN KEY (from_wallet_id) REFERENCES wallets(id) ON DELETE SET NULL,
    CONSTRAINT fk_txn_to_wallet FOREIGN KEY (to_wallet_id) REFERENCES wallets(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```
