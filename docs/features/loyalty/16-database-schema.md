# Loyalty Database Schema

## Tables

### loyalty_points (Points Ledger)
```sql
CREATE TABLE loyalty_points (
    id                      BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id                 BIGINT UNSIGNED NOT NULL,
    tenant_id               BIGINT UNSIGNED NOT NULL,
    amount                  BIGINT NOT NULL,                    -- Positive = earned, Negative = redeemed/expired
    type                    ENUM('earned', 'redeemed', 'expired', 'adjusted') NOT NULL,
    source                  VARCHAR(100) NOT NULL,              -- transfer_send, transfer_receive, bill_payment, cash_in, cash_out, airtime, savings_deposit, redemption, referral, adjustment
    source_transaction_id   BIGINT UNSIGNED NULL,               -- Links to wallet_transactions.id or redemption id
    tier_multiplier         DECIMAL(3, 1) NOT NULL DEFAULT 1.0,
    running_balance         BIGINT NOT NULL,                    -- Balance after this entry
    expires_at              TIMESTAMP NULL,
    expired_at              TIMESTAMP NULL,
    metadata                JSON NULL,
    created_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_lp_user (user_id),
    INDEX idx_lp_user_type (user_id, type),
    INDEX idx_lp_user_created (user_id, created_at),
    INDEX idx_lp_expires (expires_at),
    INDEX idx_lp_source (source_transaction_id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id)
);

-- User points balance is derived: SUM(amount) WHERE user_id = ? AND expired_at IS NULL
```

### loyalty_points_balance (Cached Balance)
```sql
CREATE TABLE loyalty_points_balance (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         BIGINT UNSIGNED NOT NULL UNIQUE,
    balance         BIGINT NOT NULL DEFAULT 0,
    last_updated    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

### loyalty_tiers (Tier Definitions)
```sql
CREATE TABLE loyalty_tiers (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    level           ENUM('bronze', 'silver', 'gold', 'platinum') NOT NULL UNIQUE,
    name_ar         VARCHAR(50) NOT NULL,
    name_en         VARCHAR(50) NOT NULL,
    min_points_12mo BIGINT NOT NULL,                            -- Points needed in 12-month rolling window
    points_multiplier DECIMAL(3, 1) NOT NULL,
    transfer_fee    DECIMAL(4, 2) NOT NULL,                    -- Percentage
    cash_out_fee    DECIMAL(4, 2) NOT NULL,
    daily_send_limit BIGINT NOT NULL,
    daily_cashout_limit BIGINT NOT NULL,
    max_balance     BIGINT NOT NULL,
    fx_spread_discount TINYINT UNSIGNED NOT NULL DEFAULT 0,
    support_priority ENUM('standard', 'priority', 'vip') NOT NULL DEFAULT 'standard',
    sort_order      TINYINT UNSIGNED NOT NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO loyalty_tiers (level, name_ar, name_en, min_points_12mo, points_multiplier, transfer_fee, cash_out_fee, daily_send_limit, daily_cashout_limit, max_balance, fx_spread_discount, support_priority, sort_order) VALUES
    ('bronze', 'برونز', 'Bronze', 0, 1.0, 0.50, 1.50, 500000, 500000, 2000000, 0, 'standard', 1),
    ('silver', 'فضي', 'Silver', 10000, 1.2, 0.40, 1.20, 1000000, 1000000, 5000000, 10, 'priority', 2),
    ('gold', 'ذهبي', 'Gold', 50000, 1.5, 0.30, 1.00, 2000000, 2000000, 10000000, 20, 'priority', 3),
    ('platinum', 'بلاتيني', 'Platinum', 200000, 2.0, 0.20, 0.50, 5000000, 5000000, 25000000, 30, 'vip', 4);
```

### loyalty_rewards (Reward Catalog)
```sql
CREATE TABLE loyalty_rewards (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(255) NOT NULL,
    name_en         VARCHAR(255) NOT NULL,
    category        ENUM('fee_discount', 'airtime', 'gift_card', 'partner_offer') NOT NULL,
    description     TEXT NULL,
    description_en  TEXT NULL,
    point_cost      INT UNSIGNED NOT NULL,
    syp_value       INT UNSIGNED NOT NULL,                      -- Monetary value equivalence
    image_url       VARCHAR(500) NULL,
    provider        VARCHAR(255) NULL,                          -- SYRIATEL, MTN, BEMO, etc.
    featured        BOOLEAN NOT NULL DEFAULT FALSE,
    popular         BOOLEAN NOT NULL DEFAULT FALSE,
    stock           INT UNSIGNED NULL,                          -- NULL = unlimited
    status          ENUM('active', 'inactive', 'coming_soon') NOT NULL DEFAULT 'active',
    sort_order      INT UNSIGNED NOT NULL DEFAULT 0,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_rewards_category (category),
    INDEX idx_rewards_status (status),
    INDEX idx_rewards_featured (featured)
);
```

### loyalty_redemptions
```sql
CREATE TABLE loyalty_redemptions (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         BIGINT UNSIGNED NOT NULL,
    reward_id       BIGINT UNSIGNED NOT NULL,
    points_spent    INT UNSIGNED NOT NULL,
    syp_value       INT UNSIGNED NOT NULL,
    status          ENUM('completed', 'refunded', 'expired') NOT NULL DEFAULT 'completed',
    coupon_code     VARCHAR(50) NULL,                           -- For fee discount type
    coupon_expires_at TIMESTAMP NULL,
    metadata        JSON NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_redemptions_user (user_id),
    INDEX idx_redemptions_status (status),
    INDEX idx_redemptions_coupon (coupon_code),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (reward_id) REFERENCES loyalty_rewards(id)
);
```

### loyalty_member_tier_history
```sql
CREATE TABLE loyalty_member_tier_history (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id             BIGINT UNSIGNED NOT NULL,
    tier_level          ENUM('bronze', 'silver', 'gold', 'platinum') NOT NULL,
    rolling_total_points BIGINT NOT NULL,                       -- Rolling 12-month total at time of change
    action              ENUM('initial', 'upgrade', 'downgrade', 'manual_change') NOT NULL,
    reason              VARCHAR(500) NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_tier_history_user (user_id),
    INDEX idx_tier_history_user_date (user_id, created_at),
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

### loyalty_merchant_campaigns
```sql
CREATE TABLE loyalty_merchant_campaigns (
    id                      BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    merchant_id             BIGINT UNSIGNED NOT NULL,            -- Links to merchants table
    name                    VARCHAR(255) NOT NULL,
    type                    ENUM('multiplier', 'fixed_points', 'cashback') NOT NULL DEFAULT 'multiplier',
    multiplier              DECIMAL(3, 1) NULL DEFAULT 2.0,      -- For multiplier type
    fixed_points            INT UNSIGNED NULL,                   -- For fixed_points type
    min_transaction_amount  BIGINT NULL DEFAULT 0,
    budget_syp              BIGINT NOT NULL,                     -- Total campaign budget in SYP (funded by merchant)
    budget_remaining        BIGINT NOT NULL,
    start_date              DATE NOT NULL,
    end_date                DATE NOT NULL,
    status                  ENUM('draft', 'active', 'paused', 'ended') NOT NULL DEFAULT 'draft',
    redemption_count        INT UNSIGNED NOT NULL DEFAULT 0,
    total_points_awarded    BIGINT NOT NULL DEFAULT 0,
    created_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_campaigns_merchant (merchant_id),
    INDEX idx_campaigns_status (status),
    INDEX idx_campaigns_dates (start_date, end_date),
    FOREIGN KEY (merchant_id) REFERENCES merchants(id)
);
```

### loyalty_campaign_redemptions
```sql
CREATE TABLE loyalty_campaign_redemptions (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campaign_id     BIGINT UNSIGNED NOT NULL,
    user_id         BIGINT UNSIGNED NOT NULL,
    transaction_id  BIGINT UNSIGNED NULL,
    points_awarded  INT UNSIGNED NOT NULL,
    transaction_amount BIGINT NOT NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_cr_campaign (campaign_id),
    INDEX idx_cr_user (user_id),
    FOREIGN KEY (campaign_id) REFERENCES loyalty_merchant_campaigns(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```
