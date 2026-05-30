# Cards Database Schema

## Tables

### cards
```sql
CREATE TABLE cards (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id           BIGINT UNSIGNED NOT NULL,
    tenant_id         BIGINT UNSIGNED NOT NULL,
    bin               VARCHAR(6) NOT NULL,                  -- BIN (639123 for local, 512345 for intl)
    pan_hash          VARCHAR(64) NOT NULL,                 -- SHA-256 hashed PAN
    pan_suffix        VARCHAR(4) NOT NULL,                  -- Last 4 digits
    expiry            VARCHAR(7) NOT NULL,                  -- MM/YYYY
    card_type         ENUM('virtual', 'physical') NOT NULL,
    card_network      ENUM('mastercard', 'visa', 'local_scheme') NOT NULL,
    status            ENUM('active', 'frozen', 'closed', 'lost', 'expired') NOT NULL DEFAULT 'active',
    issuer_id         VARCHAR(32) NOT NULL,                 -- Issuer identifier (beza_syria)
    card_program      VARCHAR(32) NOT NULL,                 -- beza_standard_syp, beza_premium_usd
    currency          ENUM('SYP', 'USD') NOT NULL,
    limits            JSON NOT NULL,                        -- {online: 500000, pos: 200000, atm: 0, intl: 0}
    kyc_level_at_issue TINYINT UNSIGNED NOT NULL,
    nickname          VARCHAR(50) NULL,
    metadata          JSON NULL,                            -- {card_art: "standard", delivery_agent_id: null, ...}
    spent_today       BIGINT NOT NULL DEFAULT 0,
    spent_today_at    DATE NULL,                            -- Date of last reset
    last_used_at      TIMESTAMP NULL,
    issued_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    activated_at      TIMESTAMP NULL,
    closed_at         TIMESTAMP NULL,
    lost_at           TIMESTAMP NULL,
    created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at        TIMESTAMP NULL,

    INDEX idx_cards_user_id (user_id),
    INDEX idx_cards_tenant (tenant_id),
    INDEX idx_cards_status (status),
    INDEX idx_cards_type (card_type),
    INDEX idx_cards_bin (bin),
    UNIQUE INDEX idx_cards_pan_hash (pan_hash),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id)
);
```

### card_transactions
```sql
CREATE TABLE card_transactions (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    card_id           BIGINT UNSIGNED NOT NULL,
    tenant_id         BIGINT UNSIGNED NOT NULL,
    uuid              CHAR(36) NOT NULL UNIQUE,             -- ULID for idempotency
    type              ENUM('purchase', 'atm', 'refund', 'fee', 'reversal') NOT NULL,
    amount            BIGINT NOT NULL,                      -- In smallest unit (piasters or cents)
    fee               BIGINT NOT NULL DEFAULT 0,
    tip               BIGINT NOT NULL DEFAULT 0,
    currency          ENUM('SYP', 'USD') NOT NULL,
    billing_currency  ENUM('SYP', 'USD') NULL,
    fx_rate           DECIMAL(12, 6) NULL,
    original_amount   BIGINT NULL,                          -- Amount in transaction currency before FX
    merchant_name     VARCHAR(128) NOT NULL,
    merchant_category VARCHAR(32) NOT NULL,                 -- MCC code or category slug
    merchant_country  CHAR(2) NOT NULL,                     -- ISO 3166-1 alpha-2
    merchant_city     VARCHAR(64) NULL,
    merchant_id       VARCHAR(64) NULL,                     -- Acquiring merchant ID
    status            ENUM('authorized', 'settled', 'declined', 'refunded', 'reversed') NOT NULL DEFAULT 'authorized',
    decline_reason    VARCHAR(64) NULL,                     -- insufficient_balance, limit_exceeded, card_frozen, fraud_declined
    auth_code         VARCHAR(32) NULL,                     -- Authorization code from switch
    rrn               VARCHAR(32) NULL,                     -- Retrieval Reference Number
    stan              VARCHAR(32) NULL,                     -- System Trace Audit Number
    local_txn_time    DATETIME NULL,                        -- Local transaction time
    auth_response     VARCHAR(8) NULL,                      -- ISO 8583 response code (00=approved, 05=declined)
    card_present      BOOLEAN NOT NULL DEFAULT FALSE,       -- Card-present transaction (POS/ATM)
    chip_transaction  BOOLEAN NOT NULL DEFAULT FALSE,       -- EMV chip transaction
    contactless       BOOLEAN NOT NULL DEFAULT FALSE,       -- NFC transaction
    online_auth       BOOLEAN NOT NULL DEFAULT TRUE,        -- Online vs offline auth
    recurring         BOOLEAN NOT NULL DEFAULT FALSE,       -- Recurring payment
    tokenized         BOOLEAN NOT NULL DEFAULT FALSE,       -- Tokenized transaction (Apple Pay)
    eci               VARCHAR(2) NULL,                      -- Electronic Commerce Indicator (3DS)
    fraud_score       DECIMAL(5, 2) NULL,                   -- Fraud score at time of auth
    settled_at        TIMESTAMP NULL,
    reversal_at       TIMESTAMP NULL,
    created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_ct_card_id (card_id),
    INDEX idx_ct_tenant (tenant_id),
    INDEX idx_ct_status (status),
    INDEX idx_ct_created_at (created_at),
    INDEX idx_ct_merchant (merchant_category, merchant_country),
    INDEX idx_ct_auth_code (auth_code),
    INDEX idx_ct_rrn (rrn),
    INDEX idx_ct_stan (stan),
    FOREIGN KEY (card_id) REFERENCES cards(id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id)
);
```

### card_pins
```sql
CREATE TABLE card_pins (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    card_id           BIGINT UNSIGNED NOT NULL UNIQUE,
    pin_hash          VARCHAR(128) NOT NULL,                -- HSM-encrypted PIN block
    pin_attempts      TINYINT UNSIGNED NOT NULL DEFAULT 0,  -- Failed attempt counter
    last_attempt_at   TIMESTAMP NULL,
    blocked_until     TIMESTAMP NULL,                       -- NULL = not blocked
    pin_changed_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (card_id) REFERENCES cards(id)
);
```

### card_tokens
```sql
CREATE TABLE card_tokens (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    card_id           BIGINT UNSIGNED NOT NULL,
    token             VARCHAR(64) NOT NULL UNIQUE,          -- DPAN from TSP (Apple Pay / Google Pay)
    token_expires     TIMESTAMP NOT NULL,
    device_id         VARCHAR(128) NOT NULL,                -- Device identifier hash
    device_name       VARCHAR(128) NULL,                    -- "iPhone 14", "Samsung Galaxy S23"
    wallet_type       ENUM('apple_pay', 'google_pay') NOT NULL,
    status            ENUM('active', 'revoked', 'suspended') NOT NULL DEFAULT 'active',
    tsp_reference     VARCHAR(64) NULL,                     -- Token Service Provider reference ID
    last_used_at      TIMESTAMP NULL,
    revoked_at        TIMESTAMP NULL,
    created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_ctkn_card_id (card_id),
    INDEX idx_ctkn_status (status),
    INDEX idx_ctkn_device (device_id),
    FOREIGN KEY (card_id) REFERENCES cards(id)
);
```

### card_bins
```sql
CREATE TABLE card_bins (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    bin               VARCHAR(6) NOT NULL UNIQUE,           -- BIN prefix
    card_network      ENUM('mastercard', 'visa', 'local_scheme') NOT NULL,
    card_type         ENUM('virtual', 'physical', 'both') NOT NULL DEFAULT 'both',
    currency          ENUM('SYP', 'USD', 'both') NOT NULL,
    issuer_id         VARCHAR(32) NOT NULL,
    card_program      VARCHAR(32) NOT NULL,
    status            ENUM('active', 'exhausted', 'inactive') NOT NULL DEFAULT 'active',
    pan_range_start   VARCHAR(19) NOT NULL,                 -- First PAN in BIN range
    pan_range_end     VARCHAR(19) NOT NULL,                 -- Last PAN in BIN range
    next_available    VARCHAR(19) NOT NULL,                 -- Next PAN to issue
    total_pans        INT NOT NULL,
    used_pans         INT NOT NULL DEFAULT 0,
    routing           ENUM('local_switch', 'international_sponsor') NOT NULL,
    metadata          JSON NULL,
    created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_cb_bin (bin),
    INDEX idx_cb_network (card_network),
    INDEX idx_cb_status (status)
);
```

### card_spending_totals
```sql
CREATE TABLE card_spending_totals (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    card_id           BIGINT UNSIGNED NOT NULL,
    category          ENUM('online', 'pos', 'atm', 'international') NOT NULL,
    period            ENUM('daily', 'weekly', 'monthly') NOT NULL,
    period_start      DATE NOT NULL,
    total_spent       BIGINT NOT NULL DEFAULT 0,
    transaction_count INT NOT NULL DEFAULT 0,
    created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE INDEX idx_cst_card_category_period (card_id, category, period, period_start),
    FOREIGN KEY (card_id) REFERENCES cards(id)
);
```

## BIN Ranges (Initial Allocation)

| BIN | Network | Type | Currency | Purpose | Range Start | Range End |
|-----|---------|------|----------|---------|-------------|-----------|
| 639123 | local_scheme | virtual | SYP | Standard virtual SYP | 6391230000000000 | 6391230999999999 |
| 639124 | local_scheme | physical | SYP | Standard physical SYP | 6391240000000000 | 6391240999999999 |
| 639125 | local_scheme | virtual | SYP | One-time use cards | 6391250000000000 | 6391250999999999 |
| 512345 | mastercard | both | USD | International via BIN sponsor | 5123450000000000 | 5123450999999999 |
| 512346 | mastercard | virtual | USD | Premium international virtual | 5123460000000000 | 5123460999999999 |
