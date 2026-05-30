# Open Finance Database Schema

## Tables

### developer_accounts
```sql
CREATE TABLE developer_accounts (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         BIGINT UNSIGNED NOT NULL,                    -- Links to Beza user
    tenant_id       BIGINT UNSIGNED NOT NULL,
    email           VARCHAR(255) NOT NULL UNIQUE,
    company_name    VARCHAR(255) NOT NULL,
    company_website VARCHAR(500) NULL,
    phone           VARCHAR(20) NOT NULL,
    password_hash   VARCHAR(255) NOT NULL,
    tier            ENUM('free', 'startup', 'business', 'enterprise') NOT NULL DEFAULT 'free',
    kyc_status      ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    kyc_approved_at TIMESTAMP NULL,
    is_active       BOOLEAN NOT NULL DEFAULT TRUE,
    metadata        JSON NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_dev_accounts_user (user_id),
    INDEX idx_dev_accounts_tier (tier),
    INDEX idx_dev_accounts_email (email),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id)
);
```

### api_keys
```sql
CREATE TABLE api_keys (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    developer_id    BIGINT UNSIGNED NOT NULL,
    label           VARCHAR(255) NOT NULL,
    key_prefix      VARCHAR(20) NOT NULL,                        -- Display only (sk_live_abc...)
    key_hash        VARCHAR(64) NOT NULL UNIQUE,                 -- SHA-256 of full key
    environment     ENUM('sandbox', 'production') NOT NULL,
    scopes          JSON NOT NULL,                                -- ["payments.write", "accounts.read"]
    status          ENUM('active', 'revoked', 'expired') NOT NULL DEFAULT 'active',
    last_used_at    TIMESTAMP NULL,
    expires_at      TIMESTAMP NULL,
    revoked_at      TIMESTAMP NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_apikeys_developer (developer_id),
    INDEX idx_apikeys_status (status),
    INDEX idx_apikeys_environment (environment),
    FOREIGN KEY (developer_id) REFERENCES developer_accounts(id)
);
```

### oauth_clients
```sql
CREATE TABLE oauth_clients (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    developer_id    BIGINT UNSIGNED NOT NULL,
    client_id       VARCHAR(64) NOT NULL UNIQUE,
    client_secret   VARCHAR(64) NOT NULL,                        -- SHA-256 hash of secret
    name            VARCHAR(255) NOT NULL,
    grant_types     JSON NOT NULL,                                -- ["client_credentials", "authorization_code"]
    redirect_uris   JSON NULL,                                    -- For authorization_code flow
    default_scopes  JSON NOT NULL,
    is_confidential BOOLEAN NOT NULL DEFAULT TRUE,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_oauth_developer (developer_id),
    FOREIGN KEY (developer_id) REFERENCES developer_accounts(id)
);
```

### oauth_tokens
```sql
CREATE TABLE oauth_tokens (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    oauth_client_id BIGINT UNSIGNED NOT NULL,
    token_hash      VARCHAR(64) NOT NULL UNIQUE,                 -- SHA-256 of access token
    scopes          JSON NOT NULL,
    expires_at      TIMESTAMP NOT NULL,
    revoked_at      TIMESTAMP NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_oauth_tokens_client (oauth_client_id),
    INDEX idx_oauth_tokens_expires (expires_at),
    FOREIGN KEY (oauth_client_id) REFERENCES oauth_clients(id)
);
```

### webhook_endpoints
```sql
CREATE TABLE webhook_endpoints (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    developer_id    BIGINT UNSIGNED NOT NULL,
    url             VARCHAR(1000) NOT NULL,
    signing_secret  VARCHAR(64) NOT NULL,
    events          JSON NOT NULL,                                -- ["payment.completed", "payment.failed"]
    description     VARCHAR(500) NULL,
    status          ENUM('active', 'paused', 'disabled') NOT NULL DEFAULT 'active',
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_webhooks_developer (developer_id),
    INDEX idx_webhooks_status (status),
    FOREIGN KEY (developer_id) REFERENCES developer_accounts(id)
);
```

### webhook_deliveries
```sql
CREATE TABLE webhook_deliveries (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    webhook_endpoint_id BIGINT UNSIGNED NOT NULL,
    event_type          VARCHAR(100) NOT NULL,
    payload             JSON NOT NULL,
    status              ENUM('pending', 'delivered', 'failed') NOT NULL DEFAULT 'pending',
    attempts            TINYINT UNSIGNED NOT NULL DEFAULT 0,
    max_attempts        TINYINT UNSIGNED NOT NULL DEFAULT 3,
    response_code       SMALLINT UNSIGNED NULL,
    response_body       TEXT NULL,
    last_error          TEXT NULL,
    delivered_at        TIMESTAMP NULL,
    next_retry_at       TIMESTAMP NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_deliveries_endpoint (webhook_endpoint_id),
    INDEX idx_deliveries_status (status),
    INDEX idx_deliveries_next_retry (next_retry_at),
    INDEX idx_deliveries_created (created_at),
    FOREIGN KEY (webhook_endpoint_id) REFERENCES webhook_endpoints(id)
);
```

### api_usage_logs
```sql
CREATE TABLE api_usage_logs (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    developer_id    BIGINT UNSIGNED NOT NULL,
    tenant_id       BIGINT UNSIGNED NOT NULL,
    api_key_id      BIGINT UNSIGNED NULL,
    method          VARCHAR(10) NOT NULL,                         -- GET, POST, PUT, DELETE
    endpoint        VARCHAR(500) NOT NULL,                        -- /v1/of/payments
    status_code     SMALLINT UNSIGNED NOT NULL,
    latency_ms      SMALLINT UNSIGNED NOT NULL,
    ip_address      VARCHAR(45) NULL,
    user_agent      VARCHAR(500) NULL,
    request_id      VARCHAR(64) NULL,
    idempotency_key VARCHAR(64) NULL,
    error_code      VARCHAR(100) NULL,
    request_body    JSON NULL,
    response_body   JSON NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_usage_developer (developer_id),
    INDEX idx_usage_created (created_at),
    INDEX idx_usage_developer_date (developer_id, created_at),
    INDEX idx_usage_status (status_code),
    INDEX idx_usage_endpoint (endpoint(100)),
    FOREIGN KEY (developer_id) REFERENCES developer_accounts(id)
) PARTITION BY RANGE (UNIX_TIMESTAMP(created_at)) (
    PARTITION p_2026_06 VALUES LESS THAN (UNIX_TIMESTAMP('2026-07-01')),
    PARTITION p_2026_07 VALUES LESS THAN (UNIX_TIMESTAMP('2026-08-01')),
    PARTITION p_future VALUES LESS THAN MAXVALUE
);
```

### rate_limit_config (developer tier overrides)
```sql
CREATE TABLE rate_limit_config (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tier                ENUM('free', 'startup', 'business', 'enterprise') NOT NULL UNIQUE,
    requests_per_minute INT UNSIGNED NOT NULL,
    requests_per_hour   INT UNSIGNED NOT NULL,
    requests_per_day    INT UNSIGNED NOT NULL,
    burst_limit         INT UNSIGNED NOT NULL DEFAULT 10,
    concurrency_limit   INT UNSIGNED NOT NULL DEFAULT 5,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO rate_limit_config (tier, requests_per_minute, requests_per_hour, requests_per_day, burst_limit, concurrency_limit)
VALUES
    ('free', 10, 100, 1000, 5, 2),
    ('startup', 100, 1000, 10000, 20, 10),
    ('business', 500, 10000, 100000, 50, 25),
    ('enterprise', 2000, 50000, 1000000, 100, 100);
```

### sandbox_accounts (sandbox test wallets)
```sql
CREATE TABLE sandbox_accounts (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    developer_id    BIGINT UNSIGNED NOT NULL,
    phone           VARCHAR(20) NOT NULL,
    balance_syp     BIGINT NOT NULL DEFAULT 1000000,
    balance_usd     BIGINT NOT NULL DEFAULT 10000,                -- In cents
    is_default      BOOLEAN NOT NULL DEFAULT FALSE,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_sandbox_developer (developer_id),
    FOREIGN KEY (developer_id) REFERENCES developer_accounts(id)
);

INSERT INTO sandbox_accounts (developer_id, phone, is_default) VALUES
    (1, '+963900000001', TRUE),
    (1, '+963900000002', FALSE);
```
