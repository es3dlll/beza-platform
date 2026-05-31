# Bill Payment Database Schema

## Tables

### billers
```sql
CREATE TABLE billers (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id       BIGINT UNSIGNED NOT NULL,
    type            VARCHAR(50) NOT NULL UNIQUE,           -- peed, damascus_water, syriatel, mtn, syria_telecom, aya, saman, government_fees, university_fees
    name_ar         VARCHAR(255) NOT NULL,                 -- name in Arabic
    name_en         VARCHAR(255) NOT NULL,                 -- name in English
    category        ENUM('electricity', 'water', 'telecom', 'internet', 'government', 'education') NOT NULL,
    interface_type  ENUM('api', 'csv', 'manual') NOT NULL,
    config          JSON NOT NULL,                         -- biller-specific configuration (API URLs, auth keys, CSV format)
    customer_id_format VARCHAR(255) NOT NULL,              -- regex pattern for customer ID validation
    customer_id_example VARCHAR(255) NOT NULL,             -- example for help text
    customer_id_length INT NOT NULL,                       -- expected digit count
    supports_fetch  BOOLEAN NOT NULL DEFAULT TRUE,
    supports_pay    BOOLEAN NOT NULL DEFAULT TRUE,
    supports_status_check BOOLEAN NOT NULL DEFAULT TRUE,
    supports_auto_pay BOOLEAN NOT NULL DEFAULT FALSE,
    supports_partial_pay BOOLEAN NOT NULL DEFAULT FALSE,
    fee_percentage  DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
    fee_fixed       INT NOT NULL DEFAULT 0,                -- fixed fee in SYP
    status          ENUM('active', 'inactive', 'maintenance') NOT NULL DEFAULT 'active',
    display_order   INT NOT NULL DEFAULT 0,
    logo_url        VARCHAR(500) NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at      TIMESTAMP NULL,

    INDEX idx_billers_category (category),
    INDEX idx_billers_status (status),
    INDEX idx_billers_interface (interface_type),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id)
);

-- Seed data for Syrian billers
INSERT INTO billers (tenant_id, type, name_ar, name_en, category, interface_type, config, customer_id_format, customer_id_example, customer_id_length, fee_percentage, fee_fixed, display_order)
VALUES
(1, 'peed',                     'الشركة العامة للكهرباء',                          'PEED',                              'electricity', 'api',  '{"base_url":"https://api.peed.gov.sy/v1","timeout":15,"retry_count":3}',              '^\d{24}$',                          '1234-5678-9012-3456-7890',  24, 0.50, 0,    1),
(1, 'damascus_water',           'مؤسسة مياه الشرب والصرف الصحي بدمشق',            'Damascus Water Authority',          'water',        'api',  '{"base_url":"https://api.damascuswater.gov.sy/v1","timeout":10,"retry_count":3}',    '^\d{10}$',                          '1234567890',                    10, 0.75, 0,    2),
(1, 'syriatel',                 'سيريتل',                                          'Syriatel',                          'telecom',      'api',  '{"base_url":"https://api.syriatel.sy/beza/v1","timeout":10,"retry_count":3}',        '^(093|094)\d{7}$',                  '0933123456',                    10, 1.00, 100,  3),
(1, 'mtn',                      'إم تي إن',                                        'MTN Syria',                         'telecom',      'api',  '{"base_url":"https://api.mtn.sy/beza/v1","timeout":10,"retry_count":3}',            '^(095|096)\d{7}$',                  '0954123456',                    10, 1.00, 100,  4),
(1, 'syria_telecom',            'الاتصالات (الخط الأرضي + ADSL)',                   'Syria Telecom',                     'telecom',      'api',  '{"base_url":"https://api.sptnet.sy/billing/v1","timeout":15,"retry_count":3}',      '^\d{7}$',                           '1123456',                        7, 0.50, 0,    5),
(1, 'aya_internet',             'آية للإنترنت',                                     'Aya Internet',                      'internet',     'api',  '{"base_url":"https://api.aya.sy/billing/v1","timeout":10,"retry_count":3}',          '^\d{8}$',                           '12345678',                       8, 1.00, 0,    6),
(1, 'saman_internet',           'سامان للإنترنت',                                   'Saman Internet',                    'internet',     'api',  '{"base_url":"https://api.saman.sy/billing/v1","timeout":10,"retry_count":3}',        '^\d{8}$',                           '12345678',                       8, 1.00, 0,    7),
(1, 'government_fees',          'الرسوم الحكومية',                                  'Government Fees',                   'government',   'csv',  '{"csv_source":"ftp://csv.gateway.gov.sy/fees","schedule":"daily 03:00","fields":["national_id","fee_type","amount","reference","ministry"]}', '^\d{16}$', '1234567890123456', 16, 1.50, 0, 8),
(1, 'university_fees',          'الرسوم الجامعية',                                  'University Fees',                   'education',    'csv',  '{"csv_source":"ftp://csv.damascusuniversity.edu.sy/fees","schedule":"weekly sun 04:00","fields":["student_id","university","semester","amount","due_date"]}', '^\d{12}$', '123456789012', 12, 1.00, 0, 9);
```

### bill_transactions
```sql
CREATE TABLE bill_transactions (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id         BIGINT UNSIGNED NOT NULL,
    user_id           BIGINT UNSIGNED NOT NULL,
    wallet_id         BIGINT UNSIGNED NULL,
    biller_id         BIGINT UNSIGNED NOT NULL,
    customer_id       VARCHAR(64) NOT NULL,                -- Customer/subscription ID
    customer_name     VARCHAR(255) NULL,                   -- Fetched from biller
    invoice_number    VARCHAR(100) NULL,                   -- Biller invoice number
    billing_period    VARCHAR(100) NULL,                   -- e.g. "مايو 2026"
    bill_amount       INT NOT NULL,                        -- Bill face amount (SYP)
    late_fee          INT NOT NULL DEFAULT 0,              -- Late payment penalty
    fee               INT NOT NULL DEFAULT 0,              -- Beza service fee
    fee_vat           INT NOT NULL DEFAULT 0,              -- VAT on fee
    total             INT GENERATED ALWAYS AS (bill_amount + late_fee + fee + fee_vat) STORED,
    currency          ENUM('SYP') NOT NULL DEFAULT 'SYP',
    reference         VARCHAR(64) NOT NULL UNIQUE,         -- Beza reference (BILL-{BILLER}-{DATE}-{RANDOM})
    biller_reference  VARCHAR(128) NULL,                   -- Biller-side confirmation ref
    status            ENUM('pending', 'paid', 'failed', 'refunded', 'disputed') NOT NULL DEFAULT 'pending',
    failure_reason    VARCHAR(500) NULL,
    paid_at           TIMESTAMP NULL,
    receipt_url       VARCHAR(500) NULL,
    cfe_reference     VARCHAR(64) NULL,
    cfe_hold_id       VARCHAR(64) NULL,
    cfe_posting_id    VARCHAR(64) NULL,
    idempotency_key   VARCHAR(64) NULL,
    wallet_balance_before INT NULL,
    wallet_balance_after  INT NULL,
    device_id         VARCHAR(128) NULL,
    ip_address        VARCHAR(45) NULL,
    metadata          JSON NULL,                           -- Full biller response for audit
    refunded_at       TIMESTAMP NULL,
    refund_reason     VARCHAR(500) NULL,
    created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_bt_user (user_id),
    INDEX idx_bt_biller (biller_id),
    INDEX idx_bt_status (status),
    INDEX idx_bt_created (created_at),
    INDEX idx_bt_customer (customer_id),
    INDEX idx_bt_reference (reference),
    INDEX idx_bt_biller_ref (biller_reference),
    INDEX idx_bt_idempotency (idempotency_key),
    INDEX idx_bt_paid_at (paid_at),
    INDEX idx_bt_tenant_date (tenant_id, created_at),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (biller_id) REFERENCES billers(id),
    FOREIGN KEY (wallet_id) REFERENCES wallets(id)
);
```

### scheduled_bills
```sql
CREATE TABLE scheduled_bills (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id         BIGINT UNSIGNED NOT NULL,
    user_id           BIGINT UNSIGNED NOT NULL,
    biller_id         BIGINT UNSIGNED NOT NULL,
    customer_id       VARCHAR(64) NOT NULL,
    amount            INT NULL,                            -- NULL if variable bill (electricity, water); fixed for phone/internet
    schedule_type     ENUM('once', 'monthly', 'bi_monthly', 'quarterly') NOT NULL DEFAULT 'monthly',
    reminder_days     TINYINT UNSIGNED NOT NULL DEFAULT 3, -- Days before due to send reminder
    reminder_method   ENUM('push', 'sms', 'both') NOT NULL DEFAULT 'push',
    next_due          DATE NOT NULL,                       -- Next expected due date
    auto_pay_enabled  BOOLEAN NOT NULL DEFAULT FALSE,
    auto_pay_status   ENUM('active', 'failed', 'paused', 'already_paid') NULL,
    auto_pay_failures TINYINT UNSIGNED NOT NULL DEFAULT 0,
    last_error        VARCHAR(500) NULL,
    last_reminded_at  TIMESTAMP NULL,
    status            ENUM('active', 'paused', 'cancelled', 'completed') NOT NULL DEFAULT 'active',
    cancelled_at      TIMESTAMP NULL,
    created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_sb_user (user_id),
    INDEX idx_sb_biller (biller_id),
    INDEX idx_sb_next_due (next_due),
    INDEX idx_sb_status (status),
    INDEX idx_sb_auto_pay (auto_pay_enabled, auto_pay_status),
    INDEX idx_sb_reminder (status, next_due, last_reminded_at),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (biller_id) REFERENCES billers(id)
);
```

### biller_connection_logs
```sql
CREATE TABLE biller_connection_logs (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id       BIGINT UNSIGNED NOT NULL,
    biller_id       BIGINT UNSIGNED NOT NULL,
    biller_type     VARCHAR(50) NOT NULL,
    operation       ENUM('fetch', 'pay', 'status_check', 'confirm') NOT NULL,
    customer_id     VARCHAR(64) NULL,
    request_url     VARCHAR(500) NULL,
    request_body    JSON NULL,
    response_body   JSON NULL,
    http_status     SMALLINT NULL,
    success         BOOLEAN NOT NULL,
    error_message   TEXT NULL,
    duration_ms     INT NOT NULL DEFAULT 0,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_bcl_biller (biller_id),
    INDEX idx_bcl_operation (operation),
    INDEX idx_bcl_created (created_at),
    INDEX idx_bcl_success (success),
    INDEX idx_bcl_customer (customer_id),
    FOREIGN KEY (biller_id) REFERENCES billers(id)
)
PARTITION BY RANGE (UNIX_TIMESTAMP(created_at)) (
    PARTITION p_2026_01 VALUES LESS THAN (UNIX_TIMESTAMP('2026-02-01')),
    PARTITION p_2026_02 VALUES LESS THAN (UNIX_TIMESTAMP('2026-03-01')),
    PARTITION p_2026_03 VALUES LESS THAN (UNIX_TIMESTAMP('2026-04-01')),
    PARTITION p_2026_04 VALUES LESS THAN (UNIX_TIMESTAMP('2026-05-01')),
    PARTITION p_2026_05 VALUES LESS THAN (UNIX_TIMESTAMP('2026-06-01')),
    PARTITION p_future VALUES LESS THAN MAXVALUE
);
```

### csv_batch_files (for CSV-based billers)
```sql
CREATE TABLE csv_batch_files (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id       BIGINT UNSIGNED NOT NULL,
    biller_id       BIGINT UNSIGNED NOT NULL,
    filename        VARCHAR(255) NOT NULL,
    original_name   VARCHAR(255) NOT NULL,
    file_size       INT NOT NULL,                          -- bytes
    total_records   INT NOT NULL DEFAULT 0,
    processed_records INT NOT NULL DEFAULT 0,
    failed_records  INT NOT NULL DEFAULT 0,
    status          ENUM('uploaded', 'processing', 'ready', 'completed', 'failed') NOT NULL DEFAULT 'uploaded',
    error_message   TEXT NULL,
    processed_at    TIMESTAMP NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_cbf_biller (biller_id),
    INDEX idx_cbf_status (status),
    FOREIGN KEY (biller_id) REFERENCES billers(id)
);

CREATE TABLE csv_billable_items (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    csv_batch_file_id BIGINT UNSIGNED NOT NULL,
    customer_id       VARCHAR(64) NOT NULL,
    reference         VARCHAR(128) NULL,                   -- Biller reference number
    amount            INT NOT NULL,
    due_date          DATE NULL,
    fee_type          VARCHAR(100) NULL,                   -- For government fees
    ministry          VARCHAR(100) NULL,                   -- For government fees
    university        VARCHAR(100) NULL,                   -- For university fees
    semester          VARCHAR(50) NULL,                    -- For university fees
    metadata          JSON NULL,                           -- Full CSV row
    status            ENUM('pending', 'available', 'paid', 'expired') NOT NULL DEFAULT 'pending',
    paid_at           TIMESTAMP NULL,
    bill_transaction_id BIGINT UNSIGNED NULL,

    INDEX idx_cbi_batch (csv_batch_file_id),
    INDEX idx_cbi_customer (customer_id),
    INDEX idx_cbi_status (status),
    FOREIGN KEY (csv_batch_file_id) REFERENCES csv_batch_files(id),
    FOREIGN KEY (bill_transaction_id) REFERENCES bill_transactions(id)
);
```

### receipt_templates (for PDF generation)
```sql
CREATE TABLE receipt_templates (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    biller_id       BIGINT UNSIGNED NULL,                  -- NULL = default template
    locale          ENUM('ar', 'en') NOT NULL DEFAULT 'ar',
    template_html   LONGTEXT NOT NULL,                     -- Blade template
    is_default      BOOLEAN NOT NULL DEFAULT FALSE,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (biller_id) REFERENCES billers(id)
);
```

## ER Diagram (Text)
```
billers 1──N bill_transactions
billers 1──N scheduled_bills
billers 1──N biller_connection_logs
billers 1──N csv_batch_files

csv_batch_files 1──N csv_billable_items
csv_billable_items N──1 bill_transactions

users 1──N bill_transactions
users 1──N scheduled_bills
wallets 1──N bill_transactions

bill_transactions 1──1 biller_connection_logs (reference via beza_reference)
```
