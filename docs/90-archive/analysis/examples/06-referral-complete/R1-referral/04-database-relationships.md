# 04 - علاقات الجداول (Database Relationships)

## مخطط ER (ER Diagram)

```
  ┌──────────────────┐
  │      users        │
  │──────────────────│
  │ PK id             │
  │ name, phone       │
  │ referred_by (FK)──┼───┐
  └────────┬─────────┘   │
           │ 1            │
           │              │
           ▼              │
  ┌──────────────────┐    │
  │  referral_codes   │    │
  │──────────────────│    │
  │ PK id             │    │
  │ FK user_id        │───┘
  │ code (unique)     │
  │ is_active         │
  │ usage_count       │
  │ created_at        │
  └──────────────────┘

  ┌────────────────────────────────┐
  │      referral_rewards           │
  │────────────────────────────────│
  │ PK id                           │
  │ FK referrer_id (→ users.id)    │
  │ FK referred_id (→ users.id)    │
  │ FK referral_code_id            │
  │ reward_type (signup/transaction)│
  │ referrer_amount (2.00 USD)      │
  │ referred_amount (2.00 USD)      │
  │ status (pending/paid)           │
  │ transaction_id (→ transactions) │
  │ created_at                      │
  └────────────────────────────────┘
```

## SQL DDL

```sql
CREATE TABLE referral_codes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    code VARCHAR(20) NOT NULL UNIQUE,
    is_active BOOLEAN DEFAULT TRUE,
    usage_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_code (code),
    INDEX idx_user (user_id),
    CONSTRAINT fk_ref_code_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE referral_rewards (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    referrer_id BIGINT UNSIGNED NOT NULL,
    referred_id BIGINT UNSIGNED NOT NULL,
    referral_code_id BIGINT UNSIGNED NOT NULL,
    reward_type ENUM('signup','transaction') DEFAULT 'signup',
    referrer_amount DECIMAL(15,2) DEFAULT 2.00,
    referred_amount DECIMAL(15,2) DEFAULT 2.00,
    status ENUM('pending','paid') DEFAULT 'pending',
    trigger_transaction_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_reward_referrer FOREIGN KEY (referrer_id) REFERENCES users(id),
    CONSTRAINT fk_reward_referred FOREIGN KEY (referred_id) REFERENCES users(id),
    CONSTRAINT fk_reward_code FOREIGN KEY (referral_code_id) REFERENCES referral_codes(id),
    INDEX idx_referrer (referrer_id),
    INDEX idx_referred (referred_id)
) ENGINE=InnoDB;

-- إضافة عمود referred_by للمستخدمين
ALTER TABLE users ADD COLUMN referred_by BIGINT UNSIGNED NULL;
ALTER TABLE users ADD CONSTRAINT fk_user_referred FOREIGN KEY (referred_by) REFERENCES users(id);
```
