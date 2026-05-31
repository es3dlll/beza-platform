# Government Collections Database Schema

## Tables

### government_billers
```sql
CREATE TABLE government_billers (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id           BIGINT UNSIGNED NOT NULL,
    code                VARCHAR(20) NOT NULL UNIQUE,          -- MOF, MOI, MOHE, TRAF, COURT, MUNI, CIVIL
    name_ar             VARCHAR(200) NOT NULL,                 -- وزارة المالية
    name_en             VARCHAR(200) NULL,                     -- Ministry of Finance
    type                ENUM('ministry', 'department', 'municipality',
                             'university', 'court', 'authority')
                        NOT NULL DEFAULT 'ministry',
    service_types       JSON NOT NULL,                         -- List of supported services
    integration_type    ENUM('api', 'file_batch', 'portal', 'manual')
                        NOT NULL DEFAULT 'api',
    adapter_class       VARCHAR(255) NOT NULL,                 -- Integration adapter class
    config              JSON NULL,                             -- Adapter configuration (endpoints, credentials)
    fee_percentage      DECIMAL(5, 2) NOT NULL DEFAULT 0.50,   -- Beza fee for this biller
    settlement_method   ENUM('wire', 'batch_api', 'file_based', 'manual')
                        NOT NULL DEFAULT 'batch_api',
    settlement_terms    VARCHAR(500) NULL,                     -- T+1, etc.
    is_active           TINYINT(1) NOT NULL DEFAULT 1,
    status              ENUM('active', 'suspended', 'disconnected')
                        NOT NULL DEFAULT 'active',
    metadata            JSON NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_gb_type (type),
    INDEX idx_gb_status (status),
    INDEX idx_gb_active (is_active),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id)
);
```

### government_transactions
```sql
CREATE TABLE government_transactions (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id           BIGINT UNSIGNED NOT NULL,
    user_id             BIGINT UNSIGNED NULL,                  -- Null for guest payments
    uuid                CHAR(36) NOT NULL UNIQUE,              -- Idempotency key
    transaction_id      VARCHAR(64) NOT NULL UNIQUE,           -- gov_txn_{ulid}

    -- Biller
    biller_id           BIGINT UNSIGNED NOT NULL,
    service_type        ENUM('tax_income', 'tax_property', 'traffic_fine', 'court_fee',
                             'passport', 'tuition', 'vehicle_registration',
                             'vehicle_license', 'municipality_fee',
                             'civil_registry', 'other') NOT NULL,
    biller_reference    VARCHAR(100) NOT NULL,                 -- Tax ID, passport app#, student ID, plate
    biller_obligation_id VARCHAR(100) NULL,                    -- Ministry's obligation reference

    -- Amounts
    amount              BIGINT NOT NULL,                       -- Total to ministry (before Beza fee)
    beza_fee            BIGINT NOT NULL DEFAULT 0,
    penalty             BIGINT NOT NULL DEFAULT 0,              -- Late payment penalties collected
    discount            BIGINT NOT NULL DEFAULT 0,              -- Early payment discounts
    total_charged       BIGINT GENERATED ALWAYS AS (amount + beza_fee + penalty - discount) STORED,

    currency            ENUM('SYP', 'USD', 'EUR') NOT NULL DEFAULT 'SYP',

    -- Status
    status              ENUM('initiated', 'pending_minitry', 'completed',
                             'failed', 'refunded', 'settled')
                        NOT NULL DEFAULT 'initiated',
    failure_reason      VARCHAR(500) NULL,
    failure_code        VARCHAR(50) NULL,

    -- Ministry
    ministry_confirmed  TINYINT(1) NOT NULL DEFAULT 0,
    ministry_reference  VARCHAR(100) NULL,                     -- Ministry's confirmation reference
    ministry_confirmed_at TIMESTAMP NULL,
    ministry_acknowledged_at TIMESTAMP NULL,

    -- Beza wallet
    wallet_transaction_id BIGINT UNSIGNED NULL,                -- CFE wallet transaction ID
    settlement_status   ENUM('pending', 'settled', 'failed')
                        NOT NULL DEFAULT 'pending',
    settled_at          TIMESTAMP NULL,

    -- Receipt
    receipt_ref         VARCHAR(64) NULL UNIQUE,
    receipt_hash        VARCHAR(128) NULL,
    receipt_qr          TEXT NULL,

    -- Audit
    idempotency_key     VARCHAR(64) NULL UNIQUE,
    ip_address          VARCHAR(45) NULL,
    user_agent          VARCHAR(500) NULL,
    metadata            JSON NULL,

    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_gt_user (user_id),
    INDEX idx_gt_biller (biller_id),
    INDEX idx_gt_service (service_type),
    INDEX idx_gt_status (status),
    INDEX idx_gt_biller_ref (biller_reference),
    INDEX idx_gt_created (created_at),
    INDEX idx_gt_settlement (settlement_status),
    INDEX idx_gt_ministry_ref (ministry_reference),
    FOREIGN KEY (biller_id) REFERENCES government_billers(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id)
);
```

### government_receipts
```sql
CREATE TABLE government_receipts (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id           BIGINT UNSIGNED NOT NULL,
    transaction_id      BIGINT UNSIGNED NOT NULL,
    receipt_ref         VARCHAR(64) NOT NULL UNIQUE,           -- GOV-YYYY-MMDD-XXXX

    -- Receipt content
    receipt_type        ENUM('payment', 'refund', 'void')
                        NOT NULL DEFAULT 'payment',
    biller_name_ar      VARCHAR(200) NOT NULL,
    service_name_ar     VARCHAR(200) NOT NULL,
    payer_name          VARCHAR(200) NULL,                     -- From ministry or user
    payer_id_number     VARCHAR(100) NULL,                     -- National ID / Tax ID
    amount_paid         BIGINT NOT NULL,
    fee_paid            BIGINT NOT NULL DEFAULT 0,
    total_paid          BIGINT NOT NULL,
    currency            ENUM('SYP', 'USD', 'EUR') NOT NULL DEFAULT 'SYP',

    -- Ministry details
    ministry_name_ar    VARCHAR(200) NOT NULL,
    ministry_reference  VARCHAR(100) NULL,

    -- Digital signature
    receipt_hash        VARCHAR(128) NOT NULL,                 -- SHA-256 of receipt content
    qr_data             TEXT NOT NULL,                          -- Full QR content (URL + hash)
    qr_generated_at     TIMESTAMP NOT NULL,

    -- File
    pdf_path            VARCHAR(500) NULL,                     -- Path to generated PDF
    pdf_generated_at    TIMESTAMP NULL,

    -- Verification
    verification_url    VARCHAR(500) NULL,                     -- URL to verify receipt
    is_revoked          TINYINT(1) NOT NULL DEFAULT 0,
    revoked_at          TIMESTAMP NULL,
    revoked_reason      VARCHAR(500) NULL,

    -- Shared/exported
    shared_count        INT NOT NULL DEFAULT 0,
    downloaded_count    INT NOT NULL DEFAULT 0,

    timestamps
    generated_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    viewed_at           TIMESTAMP NULL,

    INDEX idx_gr_transaction (transaction_id),
    INDEX idx_gr_ref (receipt_ref),
    INDEX idx_gr_hash (receipt_hash),
    INDEX idx_gr_generated (generated_at),
    FOREIGN KEY (transaction_id) REFERENCES government_transactions(id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id)
);
```

### government_reconciliation
```sql
CREATE TABLE government_reconciliation (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id           BIGINT UNSIGNED NOT NULL,
    biller_id           BIGINT UNSIGNED NOT NULL,

    -- Period
    period_start        DATE NOT NULL,
    period_end          DATE NOT NULL,
    reconciliation_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    run_type            ENUM('daily', 'weekly', 'monthly', 'on_demand')
                        NOT NULL DEFAULT 'daily',

    -- Totals
    beza_total          BIGINT NOT NULL,                       -- Total Beza collected in period
    ministry_total      BIGINT NOT NULL,                       -- Total ministry recorded in period
    variance            BIGINT GENERATED ALWAYS AS (beza_total - ministry_total) STORED,
    variance_pct        DECIMAL(10, 4) GENERATED ALWAYS AS
                        (CASE WHEN ministry_total > 0
                              THEN ((beza_total - ministry_total) / ministry_total) * 100
                              ELSE 0 END) STORED,

    -- Counts
    beza_count          INT NOT NULL,
    ministry_count      INT NOT NULL,
    matched_count       INT NOT NULL,
    mismatched_count    INT NOT NULL,
    missing_from_beza   INT NOT NULL DEFAULT 0,
    missing_from_ministry INT NOT NULL DEFAULT 0,

    -- Status
    status              ENUM('pending', 'matched', 'has_mismatches',
                             'investigating', 'resolved')
                        NOT NULL DEFAULT 'pending',
    resolution_notes    TEXT NULL,

    -- Report
    report_path         VARCHAR(500) NULL,                     -- Path to reconciliation report
    auto_resolved       TINYINT(1) NOT NULL DEFAULT 0,

    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_grec_biller (biller_id),
    INDEX idx_grec_period (period_start, period_end),
    INDEX idx_grec_status (status),
    INDEX idx_grec_date (reconciliation_date),
    FOREIGN KEY (biller_id) REFERENCES government_billers(id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id)
);
```

### government_reconciliation_items
```sql
CREATE TABLE government_reconciliation_items (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reconciliation_id   BIGINT UNSIGNED NOT NULL,
    transaction_id      BIGINT UNSIGNED NULL,                  -- Beza transaction (null if missing from Beza)

    -- Match
    match_status        ENUM('matched', 'amount_mismatch', 'missing_from_beza',
                             'missing_from_ministry', 'date_mismatch',
                             'reference_mismatch', 'duplicate')
                        NOT NULL,
    match_confidence    DECIMAL(5, 2) NOT NULL DEFAULT 100.00, -- 0.00–100.00

    -- Ministry side
    ministry_reference  VARCHAR(100) NULL,
    ministry_amount     BIGINT NULL,
    ministry_date       DATE NULL,

    -- Beza side
    beza_reference      VARCHAR(100) NULL,
    beza_amount         BIGINT NULL,
    beza_date           DATE NULL,

    -- Resolution
    variance_amount     BIGINT NULL,
    variance_reason     VARCHAR(500) NULL,
    resolved            TINYINT(1) NOT NULL DEFAULT 0,
    resolved_at         TIMESTAMP NULL,
    resolved_by         BIGINT UNSIGNED NULL,
    resolution_note     VARCHAR(500) NULL,

    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_gri_reconciliation (reconciliation_id),
    INDEX idx_gri_match_status (match_status),
    INDEX idx_gri_transaction (transaction_id),
    FOREIGN KEY (reconciliation_id) REFERENCES government_reconciliation(id) ON DELETE CASCADE,
    FOREIGN KEY (transaction_id) REFERENCES government_transactions(id)
);
```

### saved_payers
```sql
CREATE TABLE saved_payers (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id             BIGINT UNSIGNED NOT NULL,
    label               VARCHAR(100) NULL,                     -- "ضريبة العقار — منزل العائلة"
    service_type        ENUM('tax_income', 'tax_property', 'traffic_fine',
                             'passport', 'tuition', 'vehicle_registration',
                             'civil_registry') NOT NULL,
    reference_id        VARCHAR(100) NOT NULL,                 -- Tax ID, Student ID, etc.
    biller_id           BIGINT UNSIGNED NULL,
    is_favourite        TINYINT(1) NOT NULL DEFAULT 0,
    last_used_at        TIMESTAMP NULL,
    metadata            JSON NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE INDEX idx_sp_user_service_ref (user_id, service_type, reference_id),
    INDEX idx_sp_user (user_id),
    INDEX idx_sp_favourite (is_favourite),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (biller_id) REFERENCES government_billers(id)
);
```

## Migration Seed Data
```sql
-- Insert core government billers
INSERT INTO government_billers (code, name_ar, name_en, type, service_types, integration_type, adapter_class, fee_percentage) VALUES
('MOF', 'وزارة المالية', 'Ministry of Finance', 'ministry',
 '["tax_income", "tax_property"]', 'api', 'MinistryOfFinanceAdapter', 0.50),
('MOI', 'وزارة الداخلية', 'Ministry of Interior', 'ministry',
 '["passport", "civil_registry"]', 'api', 'MinistryOfInteriorAdapter', 0.50),
('TRAF', 'مديرية المرور', 'Traffic Directorate', 'department',
 '["traffic_fine", "vehicle_registration", "vehicle_license"]', 'api', 'TrafficAuthorityAdapter', 0.50),
('COURT', 'وزارة العدل — المحاكم', 'Ministry of Justice', 'court',
 '["court_fee"]', 'file_batch', 'CourtSystemAdapter', 0.75),
('DAMASCUS_UNI', 'جامعة دمشق', 'Damascus University', 'university',
 '["tuition"]', 'api', 'UniversityPortalAdapter', 0.25),
('ALEPPO_UNI', 'جامعة حلب', 'Aleppo University', 'university',
 '["tuition"]', 'api', 'UniversityPortalAdapter', 0.25),
('DAMASCUS_MUNI', 'محافظة دمشق', 'Damascus Governorate', 'municipality',
 '["municipality_fee"]', 'portal', 'MunicipalityPortalAdapter', 0.50),
('CIVIL_REG', 'مديرية الأحوال المدنية', 'Civil Registry Directorate', 'department',
 '["civil_registry"]', 'api', 'CivilRegistryAdapter', 0.50);
```
