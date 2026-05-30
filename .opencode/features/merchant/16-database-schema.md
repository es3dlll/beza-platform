# Merchant Database Schema

## Tables

### merchants
```sql
CREATE TABLE merchants (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id           BIGINT UNSIGNED NOT NULL,
    tenant_id         BIGINT UNSIGNED NOT NULL,
    business_name     VARCHAR(200) NOT NULL,
    business_type     ENUM('grocery', 'restaurant', 'retail', 'electronics',
                          'pharmacy', 'clothing', 'bakery', 'butcher',
                          'fruit_vegetables', 'stationery', 'home_business',
                          'e_commerce', 'other') NOT NULL,
    license_number    VARCHAR(100) NULL,
    license_verified  BOOLEAN NOT NULL DEFAULT FALSE,
    shop_photos       JSON NULL,                          -- Array of photo URLs
    location          POINT NULL,
    customer_phone    VARCHAR(20) NULL,                   -- Public-facing phone
    status            ENUM('pending', 'verified', 'rejected', 'suspended',
                          'closed') NOT NULL DEFAULT 'pending',
    tier              ENUM('micro', 'small', 'mid', 'enterprise') NOT NULL DEFAULT 'micro',
    mdr_rate          DECIMAL(5, 2) NOT NULL DEFAULT 1.50, -- Percentage
    mdr_qr_rate       DECIMAL(5, 2) NOT NULL DEFAULT 1.50,
    mdr_pos_rate      DECIMAL(5, 2) NOT NULL DEFAULT 2.00,
    mdr_link_rate     DECIMAL(5, 2) NOT NULL DEFAULT 2.00,
    mdr_web_rate      DECIMAL(5, 2) NOT NULL DEFAULT 2.50,
    settlement_period ENUM('daily', 'weekly', 'monthly') NOT NULL DEFAULT 'daily',
    settlement_to_wallet BOOLEAN NOT NULL DEFAULT TRUE,
    webhook_url       VARCHAR(500) NULL,
    webhook_secret    VARCHAR(100) NULL,
    webhook_events    JSON NULL,
    daily_txn_limit   BIGINT NOT NULL DEFAULT 5000000,
    monthly_txn_limit BIGINT NOT NULL DEFAULT 50000000,
    per_txn_max       BIGINT NOT NULL DEFAULT 1000000,
    per_txn_min       BIGINT NOT NULL DEFAULT 1000,
    referral_code     VARCHAR(20) NULL UNIQUE,
    referred_by       BIGINT UNSIGNED NULL,
    metadata          JSON NULL,
    verified_at       TIMESTAMP NULL,
    rejected_at       TIMESTAMP NULL,
    rejection_reason  VARCHAR(500) NULL,
    created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at        TIMESTAMP NULL,

    INDEX idx_merchants_user (user_id),
    INDEX idx_merchants_tenant (tenant_id),
    INDEX idx_merchants_status (status),
    INDEX idx_merchants_tier (tier),
    INDEX idx_merchants_business_type (business_type),
    INDEX idx_merchants_referral (referral_code),
    SPATIAL INDEX idx_merchants_location (location),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id)
);
```

### merchant_qr_codes
```sql
CREATE TABLE merchant_qr_codes (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    merchant_id   BIGINT UNSIGNED NOT NULL,
    type          ENUM('static', 'dynamic') NOT NULL DEFAULT 'static',
    amount        BIGINT NULL,                             -- NULL for static, set for dynamic
    qr_data       VARCHAR(500) NOT NULL,                   -- Encoded payload string
    image_url     VARCHAR(500) NOT NULL,                   -- CDN URL to PNG
    status        ENUM('active', 'inactive', 'expired') NOT NULL DEFAULT 'active',
    scan_count    BIGINT UNSIGNED NOT NULL DEFAULT 0,
    expires_at    TIMESTAMP NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_qr_merchant (merchant_id),
    INDEX idx_qr_status (status),
    INDEX idx_qr_type (type),
    FOREIGN KEY (merchant_id) REFERENCES merchants(id)
);
```

### merchant_payment_links
```sql
CREATE TABLE merchant_payment_links (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid          CHAR(36) NOT NULL UNIQUE,                -- Public ID for URL
    merchant_id   BIGINT UNSIGNED NOT NULL,
    amount        BIGINT NOT NULL,
    currency      ENUM('SYP', 'USD') NOT NULL DEFAULT 'SYP',
    description   VARCHAR(500) NULL,
    status        ENUM('pending', 'paid', 'expired', 'cancelled') NOT NULL DEFAULT 'pending',
    paid_at       TIMESTAMP NULL,
    paid_by       BIGINT UNSIGNED NULL,                    -- User who paid
    transaction_id BIGINT UNSIGNED NULL,                   -- Linked transaction
    short_url     VARCHAR(200) NOT NULL,
    expires_at    TIMESTAMP NOT NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_pl_merchant (merchant_id),
    INDEX idx_pl_status (status),
    INDEX idx_pl_expires (expires_at),
    INDEX idx_pl_uuid (uuid),
    FOREIGN KEY (merchant_id) REFERENCES merchants(id),
    FOREIGN KEY (paid_by) REFERENCES users(id)
);
```

### merchant_pos_terminals
```sql
CREATE TABLE merchant_pos_terminals (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    merchant_id     BIGINT UNSIGNED NOT NULL,
    terminal_id     VARCHAR(100) NOT NULL,                 -- Terminal's self-identifier
    serial_number   VARCHAR(100) NOT NULL UNIQUE,
    model           VARCHAR(100) NOT NULL,
    certificate_sn  VARCHAR(100) NULL UNIQUE,              -- mTLS certificate serial
    certificate_pem TEXT NULL,                              -- mTLS certificate (encrypted)
    last_paired_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_seen_at    TIMESTAMP NULL,
    status          ENUM('active', 'inactive', 'lost', 'decommissioned') NOT NULL DEFAULT 'active',
    firmware_version VARCHAR(50) NULL,
    metadata        JSON NULL,                             -- App version, battery, etc.
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_pos_merchant (merchant_id),
    INDEX idx_pos_status (status),
    INDEX idx_pos_serial (serial_number),
    FOREIGN KEY (merchant_id) REFERENCES merchants(id)
);
```

### merchant_transactions
```sql
CREATE TABLE merchant_transactions (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id         BIGINT UNSIGNED NOT NULL,
    merchant_id       BIGINT UNSIGNED NOT NULL,
    wallet_transaction_id BIGINT UNSIGNED NULL,            -- Link to wallet_transactions
    customer_id       BIGINT UNSIGNED NULL,                -- Beza user who paid
    customer_phone    VARCHAR(20) NULL,
    method            ENUM('qr', 'payment_link', 'pos', 'web_checkout') NOT NULL,
    qr_id             BIGINT UNSIGNED NULL,
    payment_link_id   BIGINT UNSIGNED NULL,
    pos_terminal_id   BIGINT UNSIGNED NULL,
    amount            BIGINT NOT NULL,
    mdr_rate          DECIMAL(5, 2) NOT NULL,
    mdr_amount        BIGINT NOT NULL,
    net_amount        BIGINT GENERATED ALWAYS AS (amount - mdr_amount) STORED,
    currency          ENUM('SYP', 'USD') NOT NULL DEFAULT 'SYP',
    status            ENUM('pending', 'completed', 'refunded', 'disputed', 'failed') NOT NULL DEFAULT 'pending',
    reference         VARCHAR(64) NULL,
    cfe_reference     VARCHAR(64) NULL,
    settled           BOOLEAN NOT NULL DEFAULT FALSE,
    settled_at        TIMESTAMP NULL,
    settlement_id     BIGINT UNSIGNED NULL,
    refunded_at       TIMESTAMP NULL,
    refund_reason     VARCHAR(500) NULL,
    metadata          JSON NULL,                           -- Location, device info, table number
    created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_merchtxn_merchant (merchant_id),
    INDEX idx_merchtxn_method (method),
    INDEX idx_merchtxn_status (status),
    INDEX idx_merchtxn_created (created_at),
    INDEX idx_merchtxn_settlement (settlement_id),
    INDEX idx_merchtxn_customer (customer_id),
    INDEX idx_merchtxn_reference (reference),
    FOREIGN KEY (merchant_id) REFERENCES merchants(id),
    FOREIGN KEY (wallet_transaction_id) REFERENCES wallet_transactions(id),
    FOREIGN KEY (settlement_id) REFERENCES merchant_settlements(id)
);
```

### merchant_settlements
```sql
CREATE TABLE merchant_settlements (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    merchant_id     BIGINT UNSIGNED NOT NULL,
    period_start    TIMESTAMP NOT NULL,
    period_end      TIMESTAMP NOT NULL,
    gross_amount    BIGINT NOT NULL,
    mdr_amount      BIGINT NOT NULL,
    net_amount      BIGINT NOT NULL,
    currency        ENUM('SYP', 'USD') NOT NULL DEFAULT 'SYP',
    transaction_count INT UNSIGNED NOT NULL DEFAULT 0,
    cfe_reference   VARCHAR(64) NULL,
    status          ENUM('pending', 'processing', 'completed', 'failed') NOT NULL DEFAULT 'pending',
    paid_at         TIMESTAMP NULL,
    failure_reason  VARCHAR(500) NULL,
    metadata        JSON NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_settle_merchant (merchant_id),
    INDEX idx_settle_status (status),
    INDEX idx_settle_period (period_start, period_end),
    FOREIGN KEY (merchant_id) REFERENCES merchants(id)
);
```

### merchant_webhook_deliveries
```sql
CREATE TABLE merchant_webhook_deliveries (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    merchant_id     BIGINT UNSIGNED NOT NULL,
    event_type      VARCHAR(100) NOT NULL,
    payload         JSON NOT NULL,
    url             VARCHAR(500) NOT NULL,
    status          ENUM('pending', 'delivered', 'failed', 'cancelled') NOT NULL DEFAULT 'pending',
    attempt_count   TINYINT UNSIGNED NOT NULL DEFAULT 0,
    max_attempts    TINYINT UNSIGNED NOT NULL DEFAULT 3,
    last_attempt_at TIMESTAMP NULL,
    last_response_code INT NULL,
    last_response_body TEXT NULL,
    next_retry_at   TIMESTAMP NULL,
    signature       VARCHAR(100) NULL,
    completed_at    TIMESTAMP NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_wh_merchant (merchant_id),
    INDEX idx_wh_status (status),
    INDEX idx_wh_retry (next_retry_at),
    INDEX idx_wh_event (event_type),
    FOREIGN KEY (merchant_id) REFERENCES merchants(id)
);
```
