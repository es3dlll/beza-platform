# 04 - علاقات الجداول (Database Relationships)

## مخطط ER (ER Diagram) (Entity-Relationship)

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                     ER Diagram — Bank Withdrawal                                │
└─────────────────────────────────────────────────────────────────────────────┘

  ┌──────────────────┐              ┌──────────────────┐
  │      users       │              │      users       │
  │──────────────────│              │──────────────────│
  │ PK id            │              │ PK id            │
  │ uuid (unique)    │              │ uuid (unique)    │
  │ name             │              │ name             │
  │ phone (unique)   │              │ phone (unique)   │
  │ pin_code         │              │ pin_code         │
  │ status           │              │ status           │
  └────────┬─────────┘              └────────┬─────────┘
           │ 1                              │ 1
           │                                 │
           ▼                                 ▼
  ┌──────────────────┐              ┌──────────────────┐
  │     wallets      │              │     wallets      │
  │──────────────────│              │──────────────────│
  │ PK id            │              │ PK id            │
  │ FK user_id       │              │ FK user_id       │
  │ currency         │              │ currency         │
  │ balance          │              │ balance          │
  │ frozen_balance   │              │ frozen_balance   │
  └────────┬─────────┘              └────────┬─────────┘
           │                                 │
           └──────────────┬──────────────────┘
                          │
                          ▼
             ┌────────────────────────────────┐
             │        transactions            │
             │────────────────────────────────│
             │ PK id                          │
             │ from_wallet_id (nullable)      │
             │ to_wallet_id (nullable)        │
             │ amount                         │
             │ amount_in_usd                  │
             │ type: 'bank_withdrawal'     │
             │ status                         │
             │ reference_number (unique)      │
             │ fee                            │
             │ metadata (nullable)            │
             │ completed_at                   │
             └────────────────────────────────┘
```

## جداول إضافية خاصة بالعملية

```sql
CREATE TABLE bank_withdrawals (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    -- الحقول الخاصة بالعملية
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```
