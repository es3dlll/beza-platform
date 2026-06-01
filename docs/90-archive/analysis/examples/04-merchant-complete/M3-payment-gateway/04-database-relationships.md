# 04 - علاقات قاعدة البيانات (Database Relationships)

```
┌──────────────────┐        ┌─────────────────────────────────────────┐
│    merchants      │───────>│            payment_links                │
│──────────────────│ 1     M│─────────────────────────────────────────│
│ id               │        │ PK id                                   │
└──────────────────┘        │ FK merchant_id                          │
                            │ token (UNIQUE)                          │
                            │ amount                                  │
                            │ currency (SYP/USD)                      │
                            │ description                             │
                            │ redirect_url                            │
                            │ status (active/used/expired/cancelled)  │
                            │ expires_at                              │
                            │ paid_at                                 │
                            └─────────────────────────────────────────┘
```

```sql
CREATE TABLE payment_links (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    merchant_id BIGINT UNSIGNED NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    amount DECIMAL(15,2) NOT NULL,
    currency ENUM('SYP','USD') NOT NULL,
    description TEXT,
    redirect_url VARCHAR(500),
    status ENUM('active','used','expired','cancelled') DEFAULT 'active',
    expires_at TIMESTAMP NOT NULL,
    paid_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (merchant_id) REFERENCES merchants(id) ON DELETE CASCADE
) ENGINE=InnoDB;
```
