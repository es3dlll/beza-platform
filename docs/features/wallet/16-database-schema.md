# Wallet Database Schema

## Tables

### wallets
```sql
CREATE TABLE wallets (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         BIGINT UNSIGNED NOT NULL,
    tenant_id       BIGINT UNSIGNED NOT NULL,
    cfe_account_id  VARCHAR(64) NOT NULL UNIQUE,       -- CFE account reference
    currency        ENUM('SYP', 'USD') NOT NULL,
    type            ENUM('main', 'savings', 'card', 'merchant') NOT NULL DEFAULT 'main',
    status          ENUM('active', 'frozen', 'closed', 'dormant') NOT NULL DEFAULT 'active',
    kyc_level       TINYINT UNSIGNED NOT NULL DEFAULT 0,
    daily_sent      BIGINT NOT NULL DEFAULT 0,           -- Running daily counter
    daily_sent_at   DATETIME NULL,                       -- Last reset timestamp
    monthly_sent    BIGINT NOT NULL DEFAULT 0,
    monthly_sent_at DATETIME NULL,
    metadata        JSON NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at      TIMESTAMP NULL,

    INDEX idx_wallets_user_id (user_id),
    INDEX idx_wallets_tenant (tenant_id),
    INDEX idx_wallets_currency (currency),
    INDEX idx_wallets_status (status),
    UNIQUE INDEX idx_wallets_user_currency (user_id, currency, type, deleted_at),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id)
);
```

### wallet_transactions
```sql
CREATE TABLE wallet_transactions (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id         BIGINT UNSIGNED NOT NULL,
    uuid              CHAR(36) NOT NULL UNIQUE,          -- ULID for idempotency
    sender_wallet_id  BIGINT UNSIGNED NULL,
    recipient_wallet_id BIGINT UNSIGNED NULL,
    type              ENUM('send', 'receive', 'cash_in', 'cash_out',
                          'bill_payment', 'airtime', 'card_payment',
                          'loan_disbursement', 'loan_repayment',
                          'savings_deposit', 'savings_withdrawal',
                          'fee', 'refund', 'reversal') NOT NULL,
    status            ENUM('pending', 'completed', 'failed', 'reversed',
                          'disputed', 'expired') NOT NULL DEFAULT 'pending',
    amount            BIGINT NOT NULL,                    -- In smallest unit (SYP piasters or USD cents)
    fee               BIGINT NOT NULL DEFAULT 0,
    fee_vat           BIGINT NOT NULL DEFAULT 0,
    total             BIGINT GENERATED ALWAYS AS (amount + fee + fee_vat) STORED,
    currency          ENUM('SYP', 'USD') NOT NULL,
    fx_rate           DECIMAL(12, 4) NULL,
    fx_source_currency ENUM('SYP', 'USD') NULL,
    fx_target_currency ENUM('SYP', 'USD') NULL,
    cfe_reference     VARCHAR(64) NULL,                  -- CFE transaction ID
    cfe_hold_id       VARCHAR(64) NULL,
    cfe_posting_id    VARCHAR(64) NULL,
    idempotency_key   VARCHAR(64) NULL,
    note              VARCHAR(500) NULL,
    reference         VARCHAR(64) NULL,                  -- External reference (bill ref, etc.)
    sender_balance_before BIGINT NULL,
    sender_balance_after  BIGINT NULL,
    recipient_balance_before BIGINT NULL,
    recipient_balance_after  BIGINT NULL,
    device_id         VARCHAR(128) NULL,
    ip_address        VARCHAR(45) NULL,
    location          POINT NULL,
    agent_id          BIGINT UNSIGNED NULL,
    merchant_id       BIGINT UNSIGNED NULL,
    biller_id         BIGINT UNSIGNED NULL,
    metadata          JSON NULL,
    reversed_by       BIGINT UNSIGNED NULL,
    reversal_reason   VARCHAR(500) NULL,
    reversed_at       TIMESTAMP NULL,
    created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_txns_sender (sender_wallet_id),
    INDEX idx_txns_recipient (recipient_wallet_id),
    INDEX idx_txns_status (status),
    INDEX idx_txns_type (type),
    INDEX idx_txns_created (created_at),
    INDEX idx_txns_tenant_date (tenant_id, created_at),
    INDEX idx_txns_reference (reference),
    INDEX idx_txns_idempotency (idempotency_key),
    INDEX idx_txns_cfe_ref (cfe_reference),
    SPATIAL INDEX idx_txns_location (location),
    FOREIGN KEY (sender_wallet_id) REFERENCES wallets(id),
    FOREIGN KEY (recipient_wallet_id) REFERENCES wallets(id),
    FOREIGN KEY (agent_id) REFERENCES agents(id),
    FOREIGN KEY (merchant_id) REFERENCES merchants(id)
);
```

### wallet_balance_history
```sql
CREATE TABLE wallet_balance_history (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    wallet_id   BIGINT UNSIGNED NOT NULL,
    balance     BIGINT NOT NULL,
    held        BIGINT NOT NULL DEFAULT 0,
    recorded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_balance_wallet (wallet_id),
    INDEX idx_balance_time (recorded_at),
    FOREIGN KEY (wallet_id) REFERENCES wallets(id)
) PARTITION BY RANGE (UNIX_TIMESTAMP(recorded_at)) (
    PARTITION p_2026_01 VALUES LESS THAN (UNIX_TIMESTAMP('2026-02-01')),
    PARTITION p_2026_02 VALUES LESS THAN (UNIX_TIMESTAMP('2026-03-01')),
    PARTITION p_future VALUES LESS THAN MAXVALUE
);
```

### wallet_daily_limits
```sql
CREATE TABLE wallet_daily_limits (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id     BIGINT UNSIGNED NOT NULL,
    kyc_level     TINYINT UNSIGNED NOT NULL,
    currency      ENUM('SYP', 'USD') NOT NULL,
    txn_type      ENUM('send', 'cash_out', 'bill_payment', 'all') NOT NULL DEFAULT 'all',
    daily_max     BIGINT NOT NULL,
    monthly_max   BIGINT NOT NULL,
    per_txn_max   BIGINT NOT NULL,
    per_txn_min   BIGINT NOT NULL DEFAULT 1000,

    UNIQUE INDEX idx_limits_kyc_currency_type (kyc_level, currency, txn_type)
);

INSERT INTO wallet_daily_limits (kyc_level, currency, txn_type, daily_max, monthly_max, per_txn_max, per_txn_min)
VALUES
    (0, 'SYP', 'all', 50000, 500000, 25000, 1000),
    (1, 'SYP', 'all', 500000, 5000000, 200000, 1000),
    (2, 'SYP', 'all', 2000000, 20000000, 1000000, 1000),
    (0, 'USD', 'all', 50, 500, 25, 1),
    (1, 'USD', 'all', 500, 5000, 200, 1),
    (2, 'USD', 'all', 2000, 20000, 1000, 1);
```

### transfer_requests (pending money requests)
```sql
CREATE TABLE transfer_requests (
    id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id      BIGINT UNSIGNED NOT NULL,
    requester_id   BIGINT UNSIGNED NOT NULL,
    requestee_id   BIGINT UNSIGNED NOT NULL,
    amount         BIGINT NOT NULL,
    currency       ENUM('SYP', 'USD') NOT NULL,
    note           VARCHAR(500) NULL,
    status         ENUM('pending', 'accepted', 'declined', 'expired', 'cancelled') NOT NULL DEFAULT 'pending',
    expires_at     TIMESTAMP NOT NULL,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_req_requester (requester_id),
    INDEX idx_req_requestee (requestee_id),
    INDEX idx_req_status (status),
    FOREIGN KEY (requester_id) REFERENCES users(id),
    FOREIGN KEY (requestee_id) REFERENCES users(id)
);
```
