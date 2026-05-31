# 04 - علاقات الجداول (Database Relationships)

## مخطط ER (ER Diagram) — Balance Flow

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                         ER Diagram — Balance Query                          │
└─────────────────────────────────────────────────────────────────────────────┘

  ┌──────────────────┐
  │      users       │
  │──────────────────│
  │ PK id            │
  │ uuid (unique)    │
  │ name             │
  │ phone (unique)   │
  │ ...              │
  └────────┬─────────┘
           │ 1
           │ hasMany
           ▼
  ┌──────────────────┐
  │     wallets      │
  │──────────────────│
  │ PK id            │
  │ FK user_id       │
  │ currency (SYP/USD)│
  │ balance          │
  │ frozen_balance   │
  │ wallet_number    │
  │ is_active        │
  │                  │
  │ UNIQUE:          │
  │ (user_id,currency)│
  └──────────────────┘

  كل User → محفظتين بالضبط: SYP + USD
  الـ Balance Query تجلب المحفظتين دفعة واحدة
```

## SQL Queries المرتبطة

### جلب المحافظ (لكلا العملتين)
```sql
SELECT id, user_id, currency, balance, frozen_balance, wallet_number, is_active
FROM wallets
WHERE user_id = ?
  AND is_active = 1
  AND currency IN ('SYP', 'USD');
-- Index: (user_id, currency)
```

### مع Eloquent
```php
$wallets = Wallet::where('user_id', $userId)
    ->whereIn('currency', ['SYP', 'USD'])
    ->get();
// يعيد array بطول 2: [sypWallet, usdWallet]
```

## MySQL Schema

```sql
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
```
