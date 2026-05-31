# Settlement Database Schema

## Tables

### settlement_batches
```sql
CREATE TABLE settlement_batches (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id           BIGINT UNSIGNED NOT NULL,
    batch_number        VARCHAR(32) NOT NULL UNIQUE,          -- STL-YYYYMMDD-NNNN or RT-YYYYMMDD-NNNN
    type                ENUM('eod', 'realtime') NOT NULL DEFAULT 'eod',
    status              ENUM('draft', 'processing', 'awaiting_confirmation', 'on_hold', 'settled', 'failed')
                        NOT NULL DEFAULT 'draft',
    currency            ENUM('SYP', 'USD') NOT NULL DEFAULT 'SYP',

    -- Cut-off
    cut_off_time        DATETIME NOT NULL,
    cut_off_timezone    VARCHAR(32) NOT NULL DEFAULT 'Asia/Damascus',

    -- Volume
    transaction_count   BIGINT UNSIGNED NOT NULL DEFAULT 0,
    total_debit         BIGINT NOT NULL DEFAULT 0,
    total_credit        BIGINT NOT NULL DEFAULT 0,
    total_amount        BIGINT NOT NULL DEFAULT 0,            -- max(debit, credit)
    net_amount          BIGINT NULL,                          -- Calculated after netting

    -- Processing
    processed_at        TIMESTAMP NULL,
    settled_at          TIMESTAMP NULL,

    -- Hold
    hold_reason         VARCHAR(500) NULL,
    held_at             TIMESTAMP NULL,
    released_at         TIMESTAMP NULL,

    -- Failure
    failure_reason      VARCHAR(500) NULL,
    failure_count       TINYINT UNSIGNED NOT NULL DEFAULT 0,

    -- Metadata
    metadata            JSON NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at          TIMESTAMP NULL,

    INDEX idx_sb_status (status),
    INDEX idx_sb_type (type),
    INDEX idx_sb_cutoff (cut_off_time),
    INDEX idx_sb_created (created_at),
    INDEX idx_sb_settled (settled_at),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id)
);
```

### settlement_batch_items
```sql
CREATE TABLE settlement_batch_items (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    batch_id            BIGINT UNSIGNED NOT NULL,
    tenant_id           BIGINT UNSIGNED NOT NULL,

    -- Entity
    entity_type         ENUM('bank', 'biller', 'merchant', 'agent', 'internal', 'cfe') NOT NULL,
    entity_id           VARCHAR(64) NOT NULL,                 -- UUID or code of the entity
    entity_name         VARCHAR(255) NULL,                    -- Denormalized for reporting

    -- Amounts
    total_debit         BIGINT NOT NULL DEFAULT 0,            -- Amounts owed from entity
    total_credit        BIGINT NOT NULL DEFAULT 0,            -- Amounts owed to entity
    net_amount          BIGINT GENERATED ALWAYS AS (total_credit - total_debit) STORED,

    -- Counts
    transaction_count   INT UNSIGNED NOT NULL DEFAULT 0,

    -- Status
    status              ENUM('pending', 'matched', 'unmatched', 'excluded') NOT NULL DEFAULT 'pending',

    -- External confirmation
    external_confirmed_amount BIGINT NULL,
    external_confirmed_at     TIMESTAMP NULL,
    external_reference        VARCHAR(128) NULL,

    -- Settlement account
    settlement_account_id BIGINT UNSIGNED NULL,

    -- Metadata
    metadata            JSON NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_sbi_batch (batch_id),
    INDEX idx_sbi_entity (entity_type, entity_id),
    INDEX idx_sbi_status (status),
    INDEX idx_sbi_entity_type (entity_type),
    FOREIGN KEY (batch_id) REFERENCES settlement_batches(id) ON DELETE CASCADE,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id),
    FOREIGN KEY (settlement_account_id) REFERENCES settlement_accounts(id)
);
```

### settlement_payment_orders
```sql
CREATE TABLE settlement_payment_orders (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    batch_id            BIGINT UNSIGNED NOT NULL,
    batch_item_id       BIGINT UNSIGNED NULL,
    tenant_id           BIGINT UNSIGNED NOT NULL,

    -- Entity
    entity_type         ENUM('bank', 'biller', 'merchant', 'agent', 'internal', 'cfe') NOT NULL,
    entity_id           VARCHAR(64) NOT NULL,

    -- Order details
    direction           ENUM('pay', 'receive') NOT NULL,      -- pay = Beza pays entity, receive = entity pays Beza
    amount              BIGINT NOT NULL,
    currency            ENUM('SYP', 'USD') NOT NULL DEFAULT 'SYP',
    settlement_account  VARCHAR(64) NOT NULL,                 -- CFE account reference

    -- Status
    status              ENUM('generated', 'transmitted', 'confirmed', 'rejected', 'cancelled')
                        NOT NULL DEFAULT 'generated',

    -- Transmission
    file_format         VARCHAR(32) NOT NULL DEFAULT 'CSV',   -- CSV, ISO_20022_CAMT_053, MT103
    file_content        LONGTEXT NULL,                        -- Generated file content
    transmitted_at      TIMESTAMP NULL,
    external_reference  VARCHAR(128) NULL,                    -- Bank reference number

    -- Confirmation
    confirmed_amount    BIGINT NULL,
    bank_reference      VARCHAR(128) NULL,
    confirmed_at        TIMESTAMP NULL,

    -- Rejection
    failure_reason      VARCHAR(500) NULL,
    retry_count         TINYINT UNSIGNED NOT NULL DEFAULT 0,
    last_retry_at       TIMESTAMP NULL,

    -- Metadata
    metadata            JSON NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at          TIMESTAMP NULL,

    INDEX idx_spo_batch (batch_id),
    INDEX idx_spo_entity (entity_type, entity_id),
    INDEX idx_spo_status (status),
    INDEX idx_spo_created (created_at),
    FOREIGN KEY (batch_id) REFERENCES settlement_batches(id) ON DELETE CASCADE,
    FOREIGN KEY (batch_item_id) REFERENCES settlement_batch_items(id) ON DELETE SET NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id)
);
```

### settlement_reconciliation
```sql
CREATE TABLE settlement_reconciliation (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    batch_id            BIGINT UNSIGNED NOT NULL,
    tenant_id           BIGINT UNSIGNED NOT NULL,
    date                DATE NOT NULL,

    -- Status
    status              ENUM('pending', 'matched', 'partially_matched', 'unmatched', 'failed')
                        NOT NULL DEFAULT 'pending',

    -- Counts
    total_items         INT UNSIGNED NOT NULL DEFAULT 0,
    matched_items       INT UNSIGNED NOT NULL DEFAULT 0,
    unmatched_items     INT UNSIGNED NOT NULL DEFAULT 0,
    match_rate          DECIMAL(5, 2) NULL,                  -- Percentage

    -- Amounts
    total_internal_amount   BIGINT NOT NULL DEFAULT 0,
    total_external_amount   BIGINT NULL,
    total_difference        BIGINT NULL,

    -- Timing
    started_at          TIMESTAMP NULL,
    completed_at        TIMESTAMP NULL,

    -- Metadata
    metadata            JSON NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_rec_batch (batch_id),
    INDEX idx_rec_date (date),
    INDEX idx_rec_status (status),
    UNIQUE INDEX idx_rec_batch_date (batch_id, date),
    FOREIGN KEY (batch_id) REFERENCES settlement_batches(id) ON DELETE CASCADE,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id)
);
```

### settlement_reconciliation_items
```sql
CREATE TABLE settlement_reconciliation_items (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reconciliation_id   BIGINT UNSIGNED NOT NULL,
    batch_item_id       BIGINT UNSIGNED NOT NULL,

    -- Match details
    internal_amount     BIGINT NOT NULL,
    external_amount     BIGINT NULL,
    difference          BIGINT NOT NULL DEFAULT 0,

    -- Match classification
    status              ENUM('matched_exact', 'matched_tolerance', 'unmatched_amount',
                             'unmatched_missing', 'unmatched_duplicate', 'unmatched_rejected')
                        NOT NULL DEFAULT 'unmatched_missing',
    match_tolerance     BIGINT NULL,                          -- Tolerance applied if matched_tolerance

    -- Cross-reference
    internal_reference  VARCHAR(128) NULL,
    external_reference  VARCHAR(128) NULL,

    -- Notes
    notes               TEXT NULL,

    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_ri_recon (reconciliation_id),
    INDEX idx_ri_item (batch_item_id),
    INDEX idx_ri_status (status),
    FOREIGN KEY (reconciliation_id) REFERENCES settlement_reconciliation(id) ON DELETE CASCADE,
    FOREIGN KEY (batch_item_id) REFERENCES settlement_batch_items(id) ON DELETE CASCADE
);
```

### settlement_exceptions
```sql
CREATE TABLE settlement_exceptions (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id           BIGINT UNSIGNED NOT NULL,
    batch_id            BIGINT UNSIGNED NOT NULL,
    batch_item_id       BIGINT UNSIGNED NULL,
    reconciliation_item_id BIGINT UNSIGNED NULL,

    -- Classification
    type                ENUM('amount_mismatch', 'missing_confirmation', 'duplicate', 'rejected',
                             'timing_mismatch', 'reference_mismatch', 'other')
                        NOT NULL DEFAULT 'other',
    severity            ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium',
    status              ENUM('open', 'investigating', 'resolved', 'closed') NOT NULL DEFAULT 'open',

    -- Amount details
    internal_amount     BIGINT NULL,
    external_amount     BIGINT NULL,
    difference          BIGINT NULL,

    -- Description
    description         TEXT NULL,
    entity_type         VARCHAR(32) NULL,
    entity_id           VARCHAR(64) NULL,

    -- Investigation
    assigned_to         BIGINT UNSIGNED NULL,
    investigation_notes TEXT NULL,

    -- Resolution
    resolution_type     ENUM('adjustment', 'manual_match', 'write_off', 'reprocess',
                             'accepted_tolerance', 'rejected', 'other') NULL,
    resolution_notes    TEXT NULL,
    attachment_reference VARCHAR(255) NULL,
    resolved_by         BIGINT UNSIGNED NULL,
    resolved_at         TIMESTAMP NULL,

    -- Escalation
    escalated_at        TIMESTAMP NULL,
    escalated_to        VARCHAR(128) NULL,

    -- Timestamps
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at          TIMESTAMP NULL,

    INDEX idx_exc_batch (batch_id),
    INDEX idx_exc_type (type),
    INDEX idx_exc_severity (severity),
    INDEX idx_exc_status (status),
    INDEX idx_exc_entity (entity_type, entity_id),
    INDEX idx_exc_created (created_at),
    FOREIGN KEY (batch_id) REFERENCES settlement_batches(id) ON DELETE CASCADE,
    FOREIGN KEY (batch_item_id) REFERENCES settlement_batch_items(id) ON DELETE SET NULL,
    FOREIGN KEY (reconciliation_item_id) REFERENCES settlement_reconciliation_items(id) ON DELETE SET NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id),
    FOREIGN KEY (assigned_to) REFERENCES users(id),
    FOREIGN KEY (resolved_by) REFERENCES users(id)
);
```

### settlement_accounts
```sql
CREATE TABLE settlement_accounts (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id           BIGINT UNSIGNED NOT NULL,

    -- Entity
    entity_type         ENUM('bank', 'biller', 'merchant', 'agent', 'internal', 'cfe') NOT NULL,
    entity_id           VARCHAR(64) NOT NULL,                 -- UUID or code

    -- Account details
    account_name        VARCHAR(255) NOT NULL,
    account_number      VARCHAR(64) NULL,                    -- Bank account number
    iban                VARCHAR(34) NULL,                    -- International bank account number
    bank_name           VARCHAR(255) NULL,
    bank_branch         VARCHAR(255) NULL,
    bank_code           VARCHAR(32) NULL,

    -- CFE
    cfe_account_id      VARCHAR(64) NOT NULL,                -- CFE ledger account ID
    currency            ENUM('SYP', 'USD') NOT NULL DEFAULT 'SYP',

    -- Status
    is_active           TINYINT(1) NOT NULL DEFAULT 1,
    is_default          TINYINT(1) NOT NULL DEFAULT 0,       -- Default account for entity type

    -- Contact
    contact_name        VARCHAR(255) NULL,
    contact_email       VARCHAR(255) NULL,
    contact_phone       VARCHAR(32) NULL,

    -- Settlement preferences
    settlement_type     ENUM('batch', 'realtime', 'both') NOT NULL DEFAULT 'batch',
    cut_off_time        TIME NULL,                            -- Default cut-off for this account
    minimum_settlement  BIGINT NULL,                          -- Minimum amount to trigger settlement

    -- Metadata
    metadata            JSON NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at          TIMESTAMP NULL,

    INDEX idx_sa_entity (entity_type, entity_id),
    INDEX idx_sa_active (is_active),
    INDEX idx_sa_type (settlement_type),
    UNIQUE INDEX idx_sa_cfe_account (cfe_account_id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id)
);
```

### settlement_reports
```sql
CREATE TABLE settlement_reports (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id           BIGINT UNSIGNED NOT NULL,
    type                ENUM('daily', 'monthly', 'quarterly', 'custom') NOT NULL DEFAULT 'daily',
    period_start        DATE NOT NULL,
    period_end          DATE NOT NULL,
    status              ENUM('generating', 'completed', 'failed') NOT NULL DEFAULT 'generating',
    report_data         JSON NULL,                            -- Full report data
    file_path           VARCHAR(500) NULL,                    -- Path to generated PDF/CSV
    generated_at        TIMESTAMP NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_sr_period (period_start, period_end),
    INDEX idx_sr_type (type),
    INDEX idx_sr_status (status),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id)
);
```

### settlement_audit_log
```sql
CREATE TABLE settlement_audit_log (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id           BIGINT UNSIGNED NOT NULL,
    batch_id            BIGINT UNSIGNED NULL,
    action              VARCHAR(64) NOT NULL,                 -- batch_created, batch_processed, etc.
    actor_id            BIGINT UNSIGNED NULL,                 -- User who performed action (null = system)
    actor_type          ENUM('user', 'system', 'cron', 'api') NOT NULL DEFAULT 'system',
    old_values          JSON NULL,
    new_values          JSON NULL,
    ip_address          VARCHAR(45) NULL,
    user_agent          VARCHAR(500) NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_sal_batch (batch_id),
    INDEX idx_sal_action (action),
    INDEX idx_sal_actor (actor_id),
    INDEX idx_sal_created (created_at),
    FOREIGN KEY (batch_id) REFERENCES settlement_batches(id) ON DELETE SET NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id)
);
```

## Migration Seed Data
```sql
-- Insert default settlement accounts for Beza's entity types
INSERT INTO settlement_accounts (tenant_id, entity_type, entity_id, account_name, cfe_account_id, is_active, is_default, settlement_type)
VALUES
    (1, 'bank', 'bemo_saudi_fransi', 'Bemo Saudi Fransi Settlement', 'cfe_acc_bank_bsf', 1, 1, 'both'),
    (1, 'bank', 'bank_of_syria', 'Bank of Syria Settlement', 'cfe_acc_bank_bos', 1, 0, 'batch'),
    (1, 'biller', 'syriatel', 'Syriatel Settlement', 'cfe_acc_bill_syriatel', 1, 1, 'batch'),
    (1, 'biller', 'mtn', 'MTN Syria Settlement', 'cfe_acc_bill_mtn', 1, 0, 'batch'),
    (1, 'merchant', 'default_merchant', 'Default Merchant Settlement', 'cfe_acc_merch_default', 1, 1, 'both'),
    (1, 'agent', 'default_agent', 'Default Agent Settlement', 'cfe_acc_agent_default', 1, 1, 'batch'),
    (1, 'internal', 'settlement_pool', 'Beza Settlement Pool', 'cfe_acc_int_settlement', 1, 1, 'batch'),
    (1, 'cfe', 'cfe_ledger', 'CFE Central Ledger', 'cfe_acc_cfe_main', 1, 1, 'both');
```
