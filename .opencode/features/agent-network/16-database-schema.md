# Agent Network Database Schema

## Tables

### agents
```sql
CREATE TABLE agents (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id           BIGINT UNSIGNED NULL,                   -- Beza user account (for commission payouts)
    tenant_id         BIGINT UNSIGNED NOT NULL,
    code              VARCHAR(16) NOT NULL UNIQUE,            -- e.g., BZ-10234
    full_name         VARCHAR(100) NOT NULL,
    phone             VARCHAR(20) NOT NULL UNIQUE,
    shop_name         VARCHAR(200) NOT NULL,
    shop_type         ENUM('grocery', 'pharmacy', 'mobile_shop', 'stationery',
                          'mini_market', 'fuel_station', 'other') NOT NULL,
    address           TEXT NOT NULL,
    city              VARCHAR(50) NOT NULL,
    district          VARCHAR(50) NOT NULL,
    location          POINT NOT NULL,                          -- SRID 4326
    status            ENUM('pending', 'active', 'suspended', 'terminated') NOT NULL DEFAULT 'pending',
    tier              ENUM('bronze', 'silver', 'gold', 'platinum') NOT NULL DEFAULT 'bronze',
    float_balance     BIGINT NOT NULL DEFAULT 0,               -- Current float in SYP (smallest unit)
    commission_rate_cash_in   DECIMAL(5, 4) NOT NULL DEFAULT 0.0050,  -- 0.50%
    commission_rate_cash_out  DECIMAL(5, 4) NOT NULL DEFAULT 0.0075,  -- 0.75%
    max_cash_in_per_txn       BIGINT NOT NULL DEFAULT 5000000,
    max_cash_out_per_txn      BIGINT NOT NULL DEFAULT 500000,
    max_cash_in_daily         BIGINT NOT NULL DEFAULT 5000000,
    max_cash_out_daily        BIGINT NOT NULL DEFAULT 2000000,
    max_float_balance         BIGINT NOT NULL DEFAULT 5000000,
    pending_commission        BIGINT NOT NULL DEFAULT 0,       -- Unsettled commission accruals
    total_commission_earned   BIGINT NOT NULL DEFAULT 0,       -- Lifetime commission
    total_transactions        INT UNSIGNED NOT NULL DEFAULT 0, -- Lifetime txn count
    operating_hours           JSON NULL,                        -- Daily operating hours
    preferred_language        ENUM('ar', 'en') NOT NULL DEFAULT 'ar',
    kyc_status                ENUM('pending', 'approved', 'rejected', 'expired') NOT NULL DEFAULT 'pending',
    kyc_approved_at           TIMESTAMP NULL,
    kyc_approved_by           BIGINT UNSIGNED NULL,
    kyc_expires_at            DATE NULL,                        -- Quarterly re-KYC
    last_login_at             TIMESTAMP NULL,
    last_activity_at          TIMESTAMP NULL,
    metadata                  JSON NULL,                        -- Flexible agent attributes
    created_at                TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at                TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at                TIMESTAMP NULL,

    INDEX idx_agents_status (status),
    INDEX idx_agents_tier (tier),
    INDEX idx_agents_city (city),
    INDEX idx_agents_district (district),
    INDEX idx_agents_phone (phone),
    INDEX idx_agents_last_activity (last_activity_at),
    SPATIAL INDEX idx_agents_location (location),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id)
);
```

### agent_transactions
```sql
CREATE TABLE agent_transactions (
    id                    BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id             BIGINT UNSIGNED NOT NULL,
    agent_id              BIGINT UNSIGNED NOT NULL,
    uuid                  CHAR(36) NOT NULL UNIQUE,            -- ULID for idempotency
    type                  ENUM('cash_in', 'cash_out', 'float_funding',
                               'float_transfer_in', 'float_transfer_out',
                               'commission', 'adjustment') NOT NULL,
    status                ENUM('pending', 'completed', 'failed', 'reversed') NOT NULL DEFAULT 'pending',
    amount                BIGINT NOT NULL,                     -- In smallest unit (piasters)
    fee                   BIGINT NOT NULL DEFAULT 0,
    commission            BIGINT NOT NULL DEFAULT 0,            -- Agent commission for this txn
    balance_before        BIGINT NOT NULL,                     -- Agent float balance before
    balance_after         BIGINT NOT NULL,                     -- Agent float balance after
    customer_phone        VARCHAR(20) NULL,
    customer_wallet_id    BIGINT UNSIGNED NULL,                -- Customer's wallet (for cash-in/out)
    customer_balance_before BIGINT NULL,
    customer_balance_after  BIGINT NULL,
    counterparty_agent_id BIGINT UNSIGNED NULL,                -- For agent-to-agent transfers
    idempotency_key       VARCHAR(64) NULL,
    verification_id       VARCHAR(64) NULL,                    -- Customer verification reference
    verification_method   ENUM('sms_code', 'ussd', 'biometric', 'qr') NULL,
    device_id             VARCHAR(128) NULL,                   -- POS device serial
    ip_address            VARCHAR(45) NULL,
    location              POINT NULL,
    offline_queued        BOOLEAN NOT NULL DEFAULT FALSE,      -- True if queued offline
    offline_queued_at     TIMESTAMP NULL,
    synced_at             TIMESTAMP NULL,                      -- When offline txn was synced
    notes                 VARCHAR(500) NULL,
    metadata              JSON NULL,
    reversed_by           BIGINT UNSIGNED NULL,
    reversal_reason       VARCHAR(500) NULL,
    reversed_at           TIMESTAMP NULL,
    created_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_agent_txns_agent (agent_id),
    INDEX idx_agent_txns_agent_date (agent_id, created_at),
    INDEX idx_agent_txns_type (type),
    INDEX idx_agent_txns_status (status),
    INDEX idx_agent_txns_customer (customer_phone),
    INDEX idx_agent_txns_idempotency (idempotency_key),
    INDEX idx_agent_txns_device (device_id),
    INDEX idx_agent_txns_sync (offline_queued, synced_at),
    SPATIAL INDEX idx_agent_txns_location (location),
    FOREIGN KEY (agent_id) REFERENCES agents(id),
    FOREIGN KEY (customer_wallet_id) REFERENCES wallets(id),
    FOREIGN KEY (counterparty_agent_id) REFERENCES agents(id)
);
```

### agent_float_funding
```sql
CREATE TABLE agent_float_funding (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id         BIGINT UNSIGNED NOT NULL,
    agent_id          BIGINT UNSIGNED NOT NULL,
    amount            BIGINT NOT NULL,
    balance_before    BIGINT NOT NULL,
    balance_after     BIGINT NOT NULL,
    source            ENUM('wallet', 'cash_deposit', 'agent_to_agent', 'commission_settlement',
                           'adjustment') NOT NULL,
    source_wallet_id  BIGINT UNSIGNED NULL,                   -- Beza wallet used for top-up
    source_agent_id   BIGINT UNSIGNED NULL,                    -- Source agent for transfers
    reference         VARCHAR(64) NULL,
    status            ENUM('pending', 'completed', 'failed', 'cancelled') NOT NULL DEFAULT 'pending',
    verified_by       BIGINT UNSIGNED NULL,                    -- Hub operator for cash deposits
    verified_at       TIMESTAMP NULL,
    notes             VARCHAR(500) NULL,
    metadata          JSON NULL,
    created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_float_funding_agent (agent_id),
    INDEX idx_float_funding_source (source),
    INDEX idx_float_funding_status (status),
    FOREIGN KEY (agent_id) REFERENCES agents(id),
    FOREIGN KEY (source_wallet_id) REFERENCES wallets(id),
    FOREIGN KEY (source_agent_id) REFERENCES agents(id)
);
```

### agent_commissions
```sql
CREATE TABLE agent_commissions (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id         BIGINT UNSIGNED NOT NULL,
    agent_id          BIGINT UNSIGNED NOT NULL,
    transaction_id    BIGINT UNSIGNED NULL,                    -- Agent transaction that earned this commission
    amount            BIGINT NOT NULL,
    type              ENUM('cash_in', 'cash_out') NOT NULL,
    rate_applied      DECIMAL(5, 4) NOT NULL,                  -- Commission rate used
    status            ENUM('accrued', 'settled', 'reversed') NOT NULL DEFAULT 'accrued',
    settlement_id     BIGINT UNSIGNED NULL,                    -- Commission settlement batch
    settled_at        TIMESTAMP NULL,
    created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_comm_agent (agent_id),
    INDEX idx_comm_agent_status (agent_id, status),
    INDEX idx_comm_settlement (settlement_id),
    INDEX idx_comm_created (created_at),
    FOREIGN KEY (agent_id) REFERENCES agents(id),
    FOREIGN KEY (transaction_id) REFERENCES agent_transactions(id)
);
```

### agent_commission_settlements
```sql
CREATE TABLE agent_commission_settlements (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id         BIGINT UNSIGNED NOT NULL,
    batch_reference   VARCHAR(32) NOT NULL UNIQUE,             -- e.g., SET-20260601
    settled_date      DATE NOT NULL,                           -- The date being settled
    total_agents      INT UNSIGNED NOT NULL,
    total_amount      BIGINT NOT NULL,
    status            ENUM('processing', 'completed', 'failed') NOT NULL DEFAULT 'processing',
    processed_by      BIGINT UNSIGNED NULL,
    processed_at      TIMESTAMP NULL,
    completed_at      TIMESTAMP NULL,
    error_log         JSON NULL,
    created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_settlements_date (settled_date),
    INDEX idx_settlements_status (status)
);
```

### agent_devices
```sql
CREATE TABLE agent_devices (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id         BIGINT UNSIGNED NOT NULL,
    agent_id          BIGINT UNSIGNED NOT NULL,
    device_serial     VARCHAR(128) NOT NULL UNIQUE,
    device_model      VARCHAR(100) NOT NULL,
    device_type       ENUM('android_tablet', 'android_phone', 'pos_terminal') NOT NULL,
    certificate       TEXT NOT NULL,                           -- X.509 device certificate
    certificate_expires_at TIMESTAMP NOT NULL,
    os_version        VARCHAR(50) NULL,
    app_version       VARCHAR(20) NULL,
    bluetooth_printer_mac VARCHAR(20) NULL,
    last_seen_at      TIMESTAMP NULL,
    last_ip_address   VARCHAR(45) NULL,
    status            ENUM('active', 'lost', 'stolen', 'decommissioned') NOT NULL DEFAULT 'active',
    activation_date   TIMESTAMP NOT NULL,
    deactivation_date TIMESTAMP NULL,
    metadata          JSON NULL,
    created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_devices_agent (agent_id),
    INDEX idx_devices_serial (device_serial),
    INDEX idx_devices_status (status),
    FOREIGN KEY (agent_id) REFERENCES agents(id)
);
```

### agent_tier_config
```sql
CREATE TABLE agent_tier_config (
    id                    BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tier                  ENUM('bronze', 'silver', 'gold', 'platinum') NOT NULL UNIQUE,
    max_cash_in_per_txn   BIGINT NOT NULL,
    max_cash_out_per_txn  BIGINT NOT NULL,
    max_cash_in_daily     BIGINT NOT NULL,
    max_cash_out_daily    BIGINT NOT NULL,
    max_float_balance     BIGINT NOT NULL,
    commission_rate_cash_in  DECIMAL(5, 4) NOT NULL,
    commission_rate_cash_out DECIMAL(5, 4) NOT NULL,
    monthly_fee           BIGINT NOT NULL DEFAULT 0,           -- In SYP
    float_insurance_cover BIGINT NOT NULL DEFAULT 0,
    support_priority      ENUM('standard', 'priority', 'vip') NOT NULL DEFAULT 'standard',
    min_monthly_volume    BIGINT NULL,                         -- Volume needed to maintain tier
    min_monthly_txns      INT UNSIGNED NULL,
    min_active_days       INT UNSIGNED NULL,
    min_rating            DECIMAL(2, 1) NULL,                  -- Customer satisfaction rating
    metadata              JSON NULL,
    created_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Seed tier config
INSERT INTO agent_tier_config (tier, max_cash_in_per_txn, max_cash_out_per_txn,
    max_cash_in_daily, max_cash_out_daily, max_float_balance,
    commission_rate_cash_in, commission_rate_cash_out, monthly_fee,
    float_insurance_cover, support_priority, min_monthly_volume,
    min_monthly_txns, min_active_days, min_rating)
VALUES
    ('bronze',   5000000, 500000, 5000000,  2000000,   5000000,  0.0030, 0.0050, 0,      0,    'standard', NULL, NULL, NULL, NULL),
    ('silver',   10000000, 2000000, 10000000, 5000000,   15000000, 0.0040, 0.0060, 0,      1000000, 'standard', 50000000, 100, 20, 4.0),
    ('gold',     30000000, 5000000, 30000000, 15000000,  50000000, 0.0050, 0.0075, 25000,  3000000, 'priority', 200000000, 300, 25, 4.5),
    ('platinum', 50000000, 10000000, 50000000, 40000000, 100000000, 0.0060, 0.0100, 50000, 10000000, 'vip',     500000000, 500, 28, 4.8);
```
