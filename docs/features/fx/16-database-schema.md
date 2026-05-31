# FX Engine Database Schema

## Tables

### fx_rates
```sql
CREATE TABLE fx_rates (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id       BIGINT UNSIGNED NOT NULL,
    pair            ENUM('SYP/USD', 'SYP/EUR', 'USD/EUR') NOT NULL,
    bid             DECIMAL(14, 4) NOT NULL,              -- Buy rate
    ask             DECIMAL(14, 4) NOT NULL,              -- Sell rate
    mid             DECIMAL(14, 4) NOT NULL,              -- Mid-point rate
    spread_pct      DECIMAL(6, 4) NOT NULL DEFAULT 0.0000, -- Spread applied
    beza_rate       DECIMAL(14, 4) NOT NULL,              -- Beza effective rate
    source          VARCHAR(64) NOT NULL,                  -- Provider name
    provider_id     BIGINT UNSIGNED NOT NULL,              -- FK to fx_rate_providers
    response_time_ms INT UNSIGNED NULL,                    -- Provider response time
    is_stale        BOOLEAN NOT NULL DEFAULT FALSE,
    is_override     BOOLEAN NOT NULL DEFAULT FALSE,
    override_by     BIGINT UNSIGNED NULL,                  -- Admin user ID if override
    override_reason VARCHAR(500) NULL,
    recorded_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,  -- When fetched
    expires_at      TIMESTAMP NULL,                        -- When rate expires (TTL)

    INDEX idx_fx_rates_pair (pair),
    INDEX idx_fx_rates_recorded (recorded_at),
    INDEX idx_fx_rates_pair_time (pair, recorded_at),
    INDEX idx_fx_rates_provider (provider_id),
    INDEX idx_fx_rates_source (source),
    FOREIGN KEY (provider_id) REFERENCES fx_rate_providers(id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id)
) PARTITION BY RANGE (UNIX_TIMESTAMP(recorded_at)) (
    PARTITION p_2026_01 VALUES LESS THAN (UNIX_TIMESTAMP('2026-02-01')),
    PARTITION p_2026_02 VALUES LESS THAN (UNIX_TIMESTAMP('2026-03-01')),
    PARTITION p_2026_03 VALUES LESS THAN (UNIX_TIMESTAMP('2026-04-01')),
    PARTITION p_2026_04 VALUES LESS THAN (UNIX_TIMESTAMP('2026-05-01')),
    PARTITION p_2026_05 VALUES LESS THAN (UNIX_TIMESTAMP('2026-06-01')),
    PARTITION p_2026_06 VALUES LESS THAN (UNIX_TIMESTAMP('2026-07-01')),
    PARTITION p_2026_07 VALUES LESS THAN (UNIX_TIMESTAMP('2026-08-01')),
    PARTITION p_2026_08 VALUES LESS THAN (UNIX_TIMESTAMP('2026-09-01')),
    PARTITION p_2026_09 VALUES LESS THAN (UNIX_TIMESTAMP('2026-10-01')),
    PARTITION p_2026_10 VALUES LESS THAN (UNIX_TIMESTAMP('2026-11-01')),
    PARTITION p_2026_11 VALUES LESS THAN (UNIX_TIMESTAMP('2026-12-01')),
    PARTITION p_2026_12 VALUES LESS THAN (UNIX_TIMESTAMP('2027-01-01')),
    PARTITION p_future VALUES LESS THAN MAXVALUE
);
```

### fx_rate_providers
```sql
CREATE TABLE fx_rate_providers (
    id                    BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id             BIGINT UNSIGNED NOT NULL,
    name                  VARCHAR(100) NOT NULL,           -- e.g., "CBS Official", "Parallel Market"
    type                  ENUM('api', 'scraper', 'manual') NOT NULL,
    handler_class         VARCHAR(255) NOT NULL,            -- PHP class implementing RateProviderInterface
    priority              TINYINT UNSIGNED NOT NULL DEFAULT 10,  -- Lower = higher priority
    status                ENUM('active', 'inactive', 'degraded') NOT NULL DEFAULT 'active',
    supported_pairs       JSON NOT NULL,                    -- ["SYP/USD", "SYP/EUR"]
    base_url              VARCHAR(500) NULL,                -- API endpoint URL
    health_url            VARCHAR(500) NULL,                -- Health check URL
    credentials_encrypted TEXT NULL,                        -- Encrypted API keys/tokens
    timeout_ms            INT UNSIGNED NOT NULL DEFAULT 2000,
    retry_count           TINYINT UNSIGNED NOT NULL DEFAULT 3,
    rate_limit_per_minute INT UNSIGNED NOT NULL DEFAULT 60,
    consecutive_failures  TINYINT UNSIGNED NOT NULL DEFAULT 0,
    max_consecutive_failures TINYINT UNSIGNED NOT NULL DEFAULT 3,
    circuit_breaker_until TIMESTAMP NULL,                   -- Cool-off period
    metadata              JSON NULL,                        -- Additional config per provider
    last_success_at       TIMESTAMP NULL,
    last_failure_at       TIMESTAMP NULL,
    last_failure_reason   VARCHAR(500) NULL,
    avg_response_time_ms  INT UNSIGNED NULL,
    uptime_24h            DECIMAL(5, 2) NULL,              -- Percentage
    created_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at            TIMESTAMP NULL,

    INDEX idx_providers_status (status),
    INDEX idx_providers_priority (priority),
    INDEX idx_providers_type (type),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id)
);

-- Encrypted credentials are decrypted at runtime using Laravel's encryption
-- Encryption key stored in environment: FX_PROVIDER_ENCRYPTION_KEY
```

### fx_rate_locks
```sql
CREATE TABLE fx_rate_locks (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id       BIGINT UNSIGNED NOT NULL,
    lock_id         VARCHAR(64) NOT NULL UNIQUE,            -- UUID for Redis reference
    user_id         BIGINT UNSIGNED NOT NULL,
    pair            ENUM('SYP/USD', 'SYP/EUR', 'USD/EUR') NOT NULL,
    rate            DECIMAL(14, 4) NOT NULL,                -- Locked rate
    amount          BIGINT NOT NULL,                        -- Amount in source currency (smallest unit)
    source_currency ENUM('SYP', 'USD', 'EUR') NOT NULL,
    target_currency ENUM('SYP', 'USD', 'EUR') NOT NULL,
    status          ENUM('active', 'used', 'expired', 'released') NOT NULL DEFAULT 'active',
    transaction_id  VARCHAR(64) NULL,                       -- FK to fx_conversions once used
    idempotency_key VARCHAR(64) NULL,
    expires_at      TIMESTAMP NOT NULL,                     -- Lock TTL expiry
    used_at         TIMESTAMP NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_locks_user (user_id),
    INDEX idx_locks_status (status),
    INDEX idx_locks_expires (expires_at),
    INDEX idx_locks_transaction (transaction_id),
    UNIQUE INDEX idx_locks_user_pair_active (user_id, pair, status),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id)
);
```

### fx_conversions
```sql
CREATE TABLE fx_conversions (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id         BIGINT UNSIGNED NOT NULL,
    uuid              CHAR(36) NOT NULL UNIQUE,             -- ULID for idempotency
    user_id           BIGINT UNSIGNED NOT NULL,
    lock_id           VARCHAR(64) NULL,                     -- FK to fx_rate_locks
    rate_id           BIGINT UNSIGNED NULL,                 -- FK to fx_rates
    source_wallet_id  BIGINT UNSIGNED NOT NULL,
    target_wallet_id  BIGINT UNSIGNED NOT NULL,
    source_currency   ENUM('SYP', 'USD', 'EUR') NOT NULL,
    target_currency   ENUM('SYP', 'USD', 'EUR') NOT NULL,
    source_amount     BIGINT NOT NULL,                      -- In smallest unit (SYP piasters or USD cents)
    target_amount     DECIMAL(14, 4) NOT NULL,              -- Converted amount
    rate_used         DECIMAL(14, 4) NOT NULL,              -- Actual rate used for conversion
    mid_rate          DECIMAL(14, 4) NOT NULL,              -- Mid-market rate at time of conversion
    spread_pct        DECIMAL(6, 4) NOT NULL,               -- Spread percentage applied
    spread_amount     BIGINT NOT NULL DEFAULT 0,            -- Spread in source currency
    fee               BIGINT NOT NULL DEFAULT 0,            -- Additional fee if any
    fee_currency      ENUM('SYP', 'USD', 'EUR') NULL,
    total             BIGINT NOT NULL,                      -- source_amount + fee (if same currency)
    status            ENUM('pending', 'completed', 'failed', 'reversed') NOT NULL DEFAULT 'pending',
    cfe_hold_id       VARCHAR(64) NULL,
    cfe_posting_id    VARCHAR(64) NULL,
    cfe_reference     VARCHAR(64) NULL,
    source_balance_before BIGINT NULL,
    source_balance_after  BIGINT NULL,
    target_balance_before DECIMAL(14, 4) NULL,
    target_balance_after  DECIMAL(14, 4) NULL,
    idempotency_key   VARCHAR(64) NULL,
    reference         VARCHAR(64) NULL,                     -- External reference
    ip_address        VARCHAR(45) NULL,
    user_agent        VARCHAR(500) NULL,
    metadata          JSON NULL,
    reversed_at       TIMESTAMP NULL,
    reversal_reason   VARCHAR(500) NULL,
    created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_conversions_user (user_id),
    INDEX idx_conversions_status (status),
    INDEX idx_conversions_created (created_at),
    INDEX idx_conversions_pair (source_currency, target_currency),
    INDEX idx_conversions_lock (lock_id),
    INDEX idx_conversions_idempotency (idempotency_key),
    INDEX idx_conversions_cfe_ref (cfe_reference),
    FOREIGN KEY (source_wallet_id) REFERENCES wallets(id),
    FOREIGN KEY (target_wallet_id) REFERENCES wallets(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (rate_id) REFERENCES fx_rates(id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id)
);
```

### fx_conversion_limits
```sql
CREATE TABLE fx_conversion_limits (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id     BIGINT UNSIGNED NOT NULL,
    kyc_level     TINYINT UNSIGNED NOT NULL,
    source_currency ENUM('SYP', 'USD', 'EUR') NOT NULL,
    target_currency ENUM('SYP', 'USD', 'EUR') NOT NULL,
    daily_max     BIGINT NOT NULL,                          -- In source currency smallest unit
    monthly_max   BIGINT NOT NULL,
    per_txn_max   BIGINT NOT NULL,
    per_txn_min   BIGINT NOT NULL DEFAULT 1000,

    UNIQUE INDEX idx_conv_limits_kyc_pair (kyc_level, source_currency, target_currency)
);

INSERT INTO fx_conversion_limits (kyc_level, source_currency, target_currency, daily_max, monthly_max, per_txn_max, per_txn_min)
VALUES
    (0, 'SYP', 'USD', 50000, 500000, 25000, 1000),
    (1, 'SYP', 'USD', 500000, 5000000, 200000, 1000),
    (2, 'SYP', 'USD', 2000000, 20000000, 1000000, 1000),
    (0, 'SYP', 'EUR', 50000, 500000, 25000, 1000),
    (1, 'SYP', 'EUR', 500000, 5000000, 200000, 1000),
    (2, 'SYP', 'EUR', 2000000, 20000000, 1000000, 1000),
    (0, 'USD', 'SYP', 50, 500, 25, 1),
    (1, 'USD', 'SYP', 500, 5000, 200, 1),
    (2, 'USD', 'SYP', 2000, 20000, 1000, 1),
    (0, 'USD', 'EUR', 50, 500, 25, 1),
    (1, 'USD', 'EUR', 500, 5000, 200, 1),
    (2, 'USD', 'EUR', 2000, 20000, 1000, 1);
```

### fx_spread_config
```sql
CREATE TABLE fx_spread_config (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id         BIGINT UNSIGNED NOT NULL,
    pair              ENUM('SYP/USD', 'SYP/EUR', 'USD/EUR') NOT NULL,
    user_tier         ENUM('basic', 'standard', 'premium', 'merchant') NOT NULL DEFAULT 'standard',
    spread_pct        DECIMAL(6, 4) NOT NULL,               -- e.g., 0.0300 = 3%
    min_spread_amount BIGINT NULL,                          -- Minimum spread in source currency
    max_spread_amount BIGINT NULL,                          -- Maximum spread cap
    is_active         BOOLEAN NOT NULL DEFAULT TRUE,
    created_by        BIGINT UNSIGNED NULL,
    created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE INDEX idx_spread_pair_tier (pair, user_tier, is_active),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id)
);

INSERT INTO fx_spread_config (pair, user_tier, spread_pct) VALUES
    ('SYP/USD', 'basic', 0.0400),
    ('SYP/USD', 'standard', 0.0300),
    ('SYP/USD', 'premium', 0.0150),
    ('SYP/USD', 'merchant', 0.0200),
    ('SYP/EUR', 'basic', 0.0450),
    ('SYP/EUR', 'standard', 0.0350),
    ('SYP/EUR', 'premium', 0.0200),
    ('SYP/EUR', 'merchant', 0.0250),
    ('USD/EUR', 'basic', 0.0200),
    ('USD/EUR', 'standard', 0.0150),
    ('USD/EUR', 'premium', 0.0075),
    ('USD/EUR', 'merchant', 0.0100);
```

### fx_cbs_reports
```sql
CREATE TABLE fx_cbs_reports (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id       BIGINT UNSIGNED NOT NULL,
    report_date     DATE NOT NULL,
    report_type     ENUM('daily', 'weekly', 'monthly') NOT NULL DEFAULT 'daily',
    pair            ENUM('SYP/USD', 'SYP/EUR', 'USD/EUR') NOT NULL,
    cbs_official_rate DECIMAL(14, 4) NOT NULL,
    beza_avg_rate   DECIMAL(14, 4) NOT NULL,
    beza_spread_avg DECIMAL(6, 4) NOT NULL,
    volume_converted BIGINT NOT NULL,                       -- Total volume converted (SYP equivalent)
    transaction_count INT UNSIGNED NOT NULL,
    generated_by    BIGINT UNSIGNED NULL,
    report_data     JSON NULL,                              -- Full report payload
    exported_at     TIMESTAMP NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE INDEX idx_cbs_report_date_pair (report_date, pair, report_type),
    INDEX idx_cbs_report_date (report_date),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id)
);
```
