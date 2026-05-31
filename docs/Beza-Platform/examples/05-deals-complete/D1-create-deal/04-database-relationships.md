# 04 - علاقات الجداول (Database Relationships)

## مخطط ER (ER Diagram)

```
┌─────────────────────────────────────────────────────────────┐
│                    ER Diagram — Deals System                  │
└─────────────────────────────────────────────────────────────┘

  ┌──────────────────┐          ┌──────────────────┐
  │      users       │          │      users       │
  │──────────────────│          │──────────────────│
  │ PK id            │          │ PK id            │
  │ name,phone,...   │          │ name,phone,...   │
  └────────┬─────────┘          └────────┬─────────┘
           │ 1                          │ 1
           │ created_by                  │
           ▼ (nullable)                  ▼ (investor)
  ┌──────────────────────────────────────────────┐
  │                  deals                        │
  │──────────────────────────────────────────────│
  │ PK id                                         │
  │ FK created_by (→ users.id)                    │
  │ title                                         │
  │ description                                   │
  │ target_amount                                 │
  │ current_amount (default 0)                    │
  │ currency (SYP/USD)                            │
  │ expected_profit_percentage                    │
  │ profit_actual (nullable)                      │
  │ duration_days                                 │
  │ category (trade/shipment/real_estate/...)     │
  │ risk_level (low/medium/high)                  │
  │ status (pending/review/active/filled/         │
  │         completed/cancelled)                  │
  │ cancellation_reason (nullable)                │
  │ starts_at (nullable)                          │
  │ completed_at (nullable)                       │
  │ cancelled_at (nullable)                       │
  │ created_at, updated_at                        │
  └──────────────────────┬───────────────────────┘
                         │ 1
                         │
                         │ hasMany
                         ▼
  ┌──────────────────────────────────────────────┐
  │              deal_investments                  │
  │──────────────────────────────────────────────│
  │ PK id                                         │
  │ FK deal_id (→ deals.id)                       │
  │ FK investor_id (→ users.id)                   │
  │ amount                                        │
  │ amount_in_usd                                 │
  │ currency                                      │
  │ profit_earned (nullable, بعد الإتمام)          │
  │ status (active/completed/refunded)            │
  │ created_at                                    │
  │                                               │
  │ UNIQUE (deal_id, investor_id)                 │
  └──────────────────────┬───────────────────────┘
                         │
                         │ hasMany (through deal)
                         ▼
  ┌──────────────────────────────────────────────┐
  │              transactions                       │
  │──────────────────────────────────────────────│
  │ PK id                                         │
  │ FK from_wallet_id / to_wallet_id              │
  │ amount, type, status, reference_number        │
  │ ...                                           │
  └──────────────────────────────────────────────┘
```

## SQL DDL

```sql
CREATE TABLE deals (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    created_by BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    target_amount DECIMAL(15,2) NOT NULL,
    current_amount DECIMAL(15,2) DEFAULT 0.00,
    currency ENUM('SYP','USD') NOT NULL,
    expected_profit_percentage DECIMAL(5,2) NOT NULL,
    profit_actual DECIMAL(5,2) NULL,
    duration_days INT UNSIGNED NOT NULL,
    category VARCHAR(100) NOT NULL,
    risk_level ENUM('low','medium','high') NOT NULL,
    status ENUM('pending','review','active','filled','completed','cancelled') DEFAULT 'pending',
    cancellation_reason TEXT NULL,
    starts_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    cancelled_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_category (category),
    INDEX idx_created_by (created_by),
    CONSTRAINT fk_deal_creator FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE deal_investments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    deal_id BIGINT UNSIGNED NOT NULL,
    investor_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    amount_in_usd DECIMAL(15,2) NOT NULL,
    currency ENUM('SYP','USD') NOT NULL,
    profit_earned DECIMAL(15,2) NULL,
    status ENUM('active','completed','refunded') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_deal_investor (deal_id, investor_id),
    CONSTRAINT fk_invest_deal FOREIGN KEY (deal_id) REFERENCES deals(id) ON DELETE CASCADE,
    CONSTRAINT fk_investor FOREIGN KEY (investor_id) REFERENCES users(id),
    INDEX idx_investor (investor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```
