# Remittance Database Schema

## Tables

### remittances
```sql
CREATE TABLE remittances (
    id                    BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id             BIGINT UNSIGNED NOT NULL,
    uuid                  CHAR(36) NOT NULL UNIQUE,
    sender_id             BIGINT UNSIGNED NOT NULL,
    beneficiary_id        BIGINT UNSIGNED NULL,
    recipient_id          BIGINT UNSIGNED NOT NULL,
    corridor_id           BIGINT UNSIGNED NOT NULL,
    recurring_id          BIGINT UNSIGNED NULL,

    -- Source (sender pays in this currency)
    source_amount         DECIMAL(14, 2) NOT NULL,
    source_currency       ENUM('SYP', 'USD', 'EUR') NOT NULL,

    -- FX
    fx_rate               DECIMAL(12, 4) NOT NULL,
    fx_lock_id            VARCHAR(64) NULL,
    fx_locked_at          TIMESTAMP NULL,
    fx_mid_market_rate    DECIMAL(12, 4) NULL,

    -- Target (recipient gets in this currency)
    target_amount         BIGINT NOT NULL,                  -- In smallest unit of target currency
    target_currency       ENUM('SYP', 'USD', 'EUR') NOT NULL,

    -- Fees
    fee                   DECIMAL(14, 2) NOT NULL DEFAULT 0,
    fee_currency          ENUM('SYP', 'USD', 'EUR') NOT NULL,
    fx_spread_income      BIGINT NOT NULL DEFAULT 0,         -- Income from FX spread

    -- Status & type
    type                  ENUM('local_p2p', 'diaspora', 'recurring', 'request') NOT NULL,
    status                ENUM('pending', 'fx_locked', 'processing', 'completed',
                               'failed', 'cancelled', 'disputed') NOT NULL DEFAULT 'pending',
    delivery_method       ENUM('wallet', 'agent_pickup', 'bank_deposit') NOT NULL DEFAULT 'wallet',

    -- References
    sender_wallet_debit_id    BIGINT UNSIGNED NULL,
    recipient_wallet_credit_id BIGINT UNSIGNED NULL,
    idempotency_key       VARCHAR(64) NULL,
    note                  VARCHAR(500) NULL,
    reference             VARCHAR(64) NULL,
    receipt_url           VARCHAR(500) NULL,

    -- Compliance
    compliance_status     ENUM('pending', 'passed', 'flagged', 'blocked') NOT NULL DEFAULT 'pending',
    compliance_notes      JSON NULL,
    sanctions_screened_at TIMESTAMP NULL,
    source_of_funds       VARCHAR(100) NULL,

    -- Audit
    sender_ip             VARCHAR(45) NULL,
    sender_country        CHAR(2) NULL,
    device_id             VARCHAR(128) NULL,
    cancelled_at          TIMESTAMP NULL,
    cancel_reason         VARCHAR(500) NULL,
    disputed_at           TIMESTAMP NULL,
    dispute_reason        VARCHAR(500) NULL,

    created_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at            TIMESTAMP NULL,

    INDEX idx_rem_sender (sender_id),
    INDEX idx_rem_recipient (recipient_id),
    INDEX idx_rem_beneficiary (beneficiary_id),
    INDEX idx_rem_corridor (corridor_id),
    INDEX idx_rem_status (status),
    INDEX idx_rem_type (type),
    INDEX idx_rem_created (created_at),
    INDEX idx_rem_idempotency (idempotency_key),
    INDEX idx_rem_reference (reference),
    INDEX idx_rem_compliance (compliance_status),
    FOREIGN KEY (sender_id) REFERENCES users(id),
    FOREIGN KEY (beneficiary_id) REFERENCES beneficiaries(id),
    FOREIGN KEY (recipient_id) REFERENCES users(id),
    FOREIGN KEY (corridor_id) REFERENCES remittance_corridors(id),
    FOREIGN KEY (recurring_id) REFERENCES recurring_transfers(id)
);
```

### remittance_corridors
```sql
CREATE TABLE remittance_corridors (
    id                    BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id             BIGINT UNSIGNED NOT NULL,
    source_currency       ENUM('USD', 'EUR', 'GBP', 'SEK', 'TRY', 'AED', 'SAR') NOT NULL,
    target_currency       ENUM('SYP', 'USD', 'EUR') NOT NULL,
    source_country        CHAR(2) NOT NULL,                 -- ISO country code (DE, SE, TR, AE, etc.)
    corridor_key          VARCHAR(20) NOT NULL UNIQUE,      -- e.g. "EUR_DE->SYP"
    name_ar               VARCHAR(100) NOT NULL,            -- "ألمانيا → سوريا"
    name_en               VARCHAR(100) NOT NULL,            -- "Germany → Syria"

    -- Limits
    daily_max_sender      DECIMAL(14, 2) NOT NULL,
    monthly_max_sender    DECIMAL(14, 2) NOT NULL,
    per_txn_max           DECIMAL(14, 2) NOT NULL,
    per_txn_min           DECIMAL(14, 2) NOT NULL,

    -- FX configuration
    fx_spread_percent     DECIMAL(5, 2) NOT NULL DEFAULT 1.50,
    fee_percent           DECIMAL(5, 2) NOT NULL DEFAULT 1.50,
    fee_fixed             DECIMAL(14, 2) NOT NULL DEFAULT 0,
    fee_currency          ENUM('SYP', 'USD', 'EUR') NOT NULL,

    -- Compliance requirements
    required_kyc_level    TINYINT UNSIGNED NOT NULL DEFAULT 2,
    source_of_funds_threshold DECIMAL(14, 2) NOT NULL DEFAULT 1000,
    sanctions_list        VARCHAR(50) NOT NULL DEFAULT 'UN,OFAC,EU',
    travel_rule_threshold DECIMAL(14, 2) NOT NULL DEFAULT 1000,

    -- Status
    status                ENUM('active', 'maintenance', 'inactive') NOT NULL DEFAULT 'inactive',
    maintenance_message   VARCHAR(500) NULL,
    estimated_restore_at  TIMESTAMP NULL,

    -- Settlement
    settlement_method     ENUM('correspondent_bank', 'crypto', 'local_partner') NOT NULL,
    correspondent_bank_id VARCHAR(100) NULL,
    settlement_currency   ENUM('USD', 'EUR') NOT NULL,
    settlement_account    VARCHAR(100) NULL,
    settlement_fee        DECIMAL(5, 2) NOT NULL DEFAULT 0.50,

    metadata              JSON NULL,
    created_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at            TIMESTAMP NULL,

    INDEX idx_corridor_source (source_country),
    INDEX idx_corridor_currency (source_currency, target_currency),
    INDEX idx_corridor_status (status),
    INDEX idx_corridor_key (corridor_key)
);

-- Seed corridors
INSERT INTO remittance_corridors (source_currency, target_currency, source_country, corridor_key,
    name_ar, name_en, daily_max_sender, monthly_max_sender, per_txn_max, per_txn_min,
    fx_spread_percent, fee_percent, required_kyc_level, status, settlement_method)
VALUES
    ('EUR', 'SYP', 'DE', 'EUR_DE->SYP', 'ألمانيا → سوريا', 'Germany → Syria',
     2000, 20000, 1000, 10, 1.80, 1.50, 2, 'active', 'correspondent_bank'),
    ('EUR', 'SYP', 'SE', 'EUR_SE->SYP', 'السويد → سوريا', 'Sweden → Syria',
     1500, 15000, 800, 10, 1.80, 1.50, 2, 'active', 'correspondent_bank'),
    ('EUR', 'SYP', 'NL', 'EUR_NL->SYP', 'هولندا → سوريا', 'Netherlands → Syria',
     1500, 15000, 800, 10, 1.80, 1.50, 2, 'active', 'correspondent_bank'),
    ('USD', 'SYP', 'US', 'USD_US->SYP', 'أمريكا → سوريا', 'United States → Syria',
     2000, 25000, 1000, 10, 1.50, 1.50, 2, 'active', 'correspondent_bank'),
    ('USD', 'SYP', 'AE', 'USD_AE->SYP', 'الإمارات → سوريا', 'UAE → Syria',
     3000, 30000, 2000, 10, 1.50, 1.50, 2, 'active', 'correspondent_bank'),
    ('USD', 'SYP', 'SA', 'USD_SA->SYP', 'السعودية → سوريا', 'Saudi Arabia → Syria',
     3000, 30000, 2000, 10, 1.50, 1.50, 2, 'active', 'correspondent_bank'),
    ('TRY', 'SYP', 'TR', 'TRY_TR->SYP', 'تركيا → سوريا', 'Turkey → Syria',
     5000, 50000, 3000, 50, 2.00, 1.00, 1, 'active', 'local_partner'),
    ('EUR', 'USD', 'DE', 'EUR_DE->USD', 'ألمانيا → دولار', 'Germany → USD',
     5000, 50000, 3000, 10, 1.00, 0.50, 2, 'active', 'correspondent_bank'),
    ('USD', 'USD', 'US', 'USD_US->USD', 'أمريكا → دولار', 'United States → USD (same)',
     5000, 50000, 3000, 10, 0.00, 0.50, 2, 'active', 'correspondent_bank'),
    ('GBP', 'SYP', 'GB', 'GBP_GB->SYP', 'بريطانيا → سوريا', 'United Kingdom → Syria',
     1500, 15000, 800, 10, 1.80, 1.50, 2, 'inactive', 'correspondent_bank');

-- Local P2P corridors (internal)
INSERT INTO remittance_corridors (source_currency, target_currency, source_country, corridor_key,
    name_ar, name_en, daily_max_sender, monthly_max_sender, per_txn_max, per_txn_min,
    fx_spread_percent, fee_percent, required_kyc_level, status, settlement_method)
VALUES
    ('SYP', 'SYP', 'SY', 'SYP_SY->SYP', 'داخلي ل.س', 'Local SYP',
     2000000, 20000000, 1000000, 1000, 0.00, 0.50, 0, 'active', 'local_partner'),
    ('USD', 'USD', 'SY', 'USD_SY->USD', 'داخلي دولار', 'Local USD',
     2000, 20000, 1000, 1, 0.00, 0.50, 1, 'active', 'local_partner');
```

### recurring_transfers
```sql
CREATE TABLE recurring_transfers (
    id                    BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id             BIGINT UNSIGNED NOT NULL,
    sender_id             BIGINT UNSIGNED NOT NULL,
    beneficiary_id        BIGINT UNSIGNED NOT NULL,
    corridor_id           BIGINT UNSIGNED NOT NULL,

    amount                DECIMAL(14, 2) NOT NULL,
    source_currency       ENUM('USD', 'EUR', 'GBP', 'SEK', 'TRY', 'AED', 'SAR') NOT NULL,
    target_currency       ENUM('SYP', 'USD', 'EUR') NOT NULL DEFAULT 'SYP',

    frequency             ENUM('weekly', 'biweekly', 'monthly', 'quarterly') NOT NULL DEFAULT 'monthly',
    day_of_month          TINYINT UNSIGNED NULL,            -- 1-31 (for monthly)
    day_of_week           TINYINT UNSIGNED NULL,            -- 0=Sun, 1=Mon (for weekly)
    execution_time        TIME NOT NULL DEFAULT '08:00:00',

    -- Duration
    duration_type         ENUM('ongoing', 'fixed_count', 'end_date') NOT NULL DEFAULT 'ongoing',
    max_executions        INT UNSIGNED NULL,
    end_date              DATE NULL,
    executions_count      INT UNSIGNED NOT NULL DEFAULT 0,
    failed_count          INT UNSIGNED NOT NULL DEFAULT 0,

    -- FX preference
    fx_locking            ENUM('at_execution', 'at_setup') NOT NULL DEFAULT 'at_execution',
    locked_fx_rate        DECIMAL(12, 4) NULL,

    status                ENUM('active', 'paused', 'cancelled', 'completed') NOT NULL DEFAULT 'active',
    next_execution_at     TIMESTAMP NOT NULL,
    last_executed_at      TIMESTAMP NULL,
    paused_at             TIMESTAMP NULL,
    pause_reason          VARCHAR(500) NULL,
    cancelled_at          TIMESTAMP NULL,
    cancel_reason         VARCHAR(500) NULL,

    total_sent_amount     DECIMAL(18, 2) NOT NULL DEFAULT 0,
    total_sent_currency   ENUM('USD', 'EUR', 'GBP', 'SEK', 'TRY', 'AED', 'SAR') NOT NULL,

    metadata              JSON NULL,
    created_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at            TIMESTAMP NULL,

    INDEX idx_rec_sender (sender_id),
    INDEX idx_rec_beneficiary (beneficiary_id),
    INDEX idx_rec_status (status),
    INDEX idx_rec_next_exec (next_execution_at),
    INDEX idx_rec_frequency (frequency),
    FOREIGN KEY (sender_id) REFERENCES users(id),
    FOREIGN KEY (beneficiary_id) REFERENCES beneficiaries(id),
    FOREIGN KEY (corridor_id) REFERENCES remittance_corridors(id)
);
```

### beneficiaries
```sql
CREATE TABLE beneficiaries (
    id                    BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id             BIGINT UNSIGNED NOT NULL,
    user_id               BIGINT UNSIGNED NOT NULL,          -- Owner of this beneficiary
    recipient_user_id     BIGINT UNSIGNED NULL,              -- Beza user (if registered)

    name                  VARCHAR(200) NOT NULL,             -- In Arabic
    name_en               VARCHAR(200) NULL,                 -- In English (optional)
    relationship          ENUM('mother', 'father', 'brother', 'sister', 'spouse',
                               'son', 'daughter', 'friend', 'other') NOT NULL,
    relationship_custom   VARCHAR(100) NULL,                 -- If relationship = other
    phone                 VARCHAR(20) NOT NULL,
    city                  VARCHAR(100) NULL,
    country               CHAR(2) NOT NULL DEFAULT 'SY',     -- Recipient country

    currency_preference   ENUM('SYP', 'USD') NOT NULL DEFAULT 'SYP',
    delivery_preference   ENUM('wallet', 'agent_pickup') NOT NULL DEFAULT 'wallet',

    -- Stats
    total_transfers       INT UNSIGNED NOT NULL DEFAULT 0,
    total_sent_amount     DECIMAL(18, 2) NOT NULL DEFAULT 0,
    total_sent_currency   ENUM('SYP', 'USD', 'EUR') NOT NULL DEFAULT 'SYP',
    last_sent_at          TIMESTAMP NULL,
    is_favorite           BOOLEAN NOT NULL DEFAULT FALSE,

    -- Compliance
    sanctions_status      ENUM('pending', 'passed', 'flagged', 'blocked') NOT NULL DEFAULT 'pending',
    sanctions_screened_at TIMESTAMP NULL,
    notes                 VARCHAR(500) NULL,

    status                ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    is_archived           BOOLEAN NOT NULL DEFAULT FALSE,

    created_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at            TIMESTAMP NULL,

    INDEX idx_ben_user (user_id),
    INDEX idx_ben_recipient (recipient_user_id),
    INDEX idx_ben_phone (phone),
    INDEX idx_ben_status (status),
    INDEX idx_ben_sanctions (sanctions_status),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (recipient_user_id) REFERENCES users(id)
);
```

### transfer_requests
```sql
CREATE TABLE transfer_requests (
    id                    BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id             BIGINT UNSIGNED NOT NULL,
    requester_id          BIGINT UNSIGNED NOT NULL,          -- Person asking for money
    requestee_id          BIGINT UNSIGNED NOT NULL,          -- Person being asked

    amount                DECIMAL(14, 2) NOT NULL,
    currency              ENUM('SYP', 'USD', 'EUR') NOT NULL,
    note                  VARCHAR(500) NULL,

    status                ENUM('pending', 'accepted', 'declined', 'expired', 'cancelled') NOT NULL DEFAULT 'pending',
    expires_at            TIMESTAMP NOT NULL,
    accepted_at           TIMESTAMP NULL,
    declined_at           TIMESTAMP NULL,
    decline_reason        VARCHAR(500) NULL,
    cancelled_at          TIMESTAMP NULL,

    -- If accepted, links to the resulting remittance
    remittance_id         BIGINT UNSIGNED NULL,

    created_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_req_requester (requester_id),
    INDEX idx_req_requestee (requestee_id),
    INDEX idx_req_status (status),
    INDEX idx_req_expires (expires_at),
    FOREIGN KEY (requester_id) REFERENCES users(id),
    FOREIGN KEY (requestee_id) REFERENCES users(id),
    FOREIGN KEY (remittance_id) REFERENCES remittances(id)
);
```

### fx_rate_logs
```sql
CREATE TABLE fx_rate_logs (
    id                    BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    corridor_id           BIGINT UNSIGNED NOT NULL,
    lock_id               VARCHAR(64) UNIQUE,

    rate                  DECIMAL(12, 4) NOT NULL,
    mid_market_rate       DECIMAL(12, 4) NOT NULL,
    spread_percent        DECIMAL(5, 2) NOT NULL,

    source_currency       ENUM('USD', 'EUR', 'GBP', 'SEK', 'TRY', 'AED', 'SAR') NOT NULL,
    target_currency       ENUM('SYP', 'USD', 'EUR') NOT NULL,
    amount                DECIMAL(14, 2) NULL,              -- Amount being converted

    locked_by_user_id     BIGINT UNSIGNED NULL,
    locked_at             TIMESTAMP NULL,
    expires_at            TIMESTAMP NULL,
    consumed_at           TIMESTAMP NULL,                   -- When rate was used for transfer
    expired               BOOLEAN NOT NULL DEFAULT FALSE,

    created_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_fx_corridor (corridor_id),
    INDEX idx_fx_lock (lock_id),
    INDEX idx_fx_created (created_at),
    INDEX idx_fx_expires (expires_at),
    FOREIGN KEY (corridor_id) REFERENCES remittance_corridors(id)
);
```

### corridor_daily_limits (per-sender per-corridor tracking)
```sql
CREATE TABLE corridor_daily_limits (
    id                    BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    corridor_id           BIGINT UNSIGNED NOT NULL,
    user_id               BIGINT UNSIGNED NOT NULL,
    date                  DATE NOT NULL,

    total_sent            DECIMAL(14, 2) NOT NULL DEFAULT 0,
    currency              ENUM('USD', 'EUR', 'GBP', 'SEK', 'TRY', 'AED', 'SAR') NOT NULL,

    UNIQUE INDEX idx_cdl_unique (corridor_id, user_id, date),
    FOREIGN KEY (corridor_id) REFERENCES remittance_corridors(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```
