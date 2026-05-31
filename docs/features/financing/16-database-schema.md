# مخطط قاعدة البيانات — Database Schema

## Entity Relationship Overview
```
financing_products
    1│
    ├──< financing_applications
    │       1│
    │        ├──< financing_contracts
    │        │       1│
    │        │        ├──< financing_repayments
    │        │        └──< financing_restructures
    │        └──< application_documents
    │
    └──< financing_credit_scores
```

---

## 1. financing_products
Defines available financing products and their parameters.

```sql
CREATE TABLE financing_products (
    id                  SERIAL PRIMARY KEY,
    name_ar             VARCHAR(100) NOT NULL,
    name_en             VARCHAR(100) NOT NULL,
    description_ar      TEXT,
    description_en      TEXT,
    type                VARCHAR(20) NOT NULL CHECK (type IN ('qard_hasan', 'murabaha', 'micro')),
    min_amount          BIGINT NOT NULL DEFAULT 50000,
    max_amount          BIGINT NOT NULL DEFAULT 500000,
    min_term_days       INT NOT NULL DEFAULT 30,
    max_term_days       INT NOT NULL DEFAULT 180,
    profit_rate_min     DECIMAL(5,2) DEFAULT 0,
    profit_rate_max     DECIMAL(5,2),
    late_fee_type       VARCHAR(20) NOT NULL DEFAULT 'fixed' CHECK (late_fee_type IN ('fixed', 'percentage')),
    late_fee_amount     BIGINT NOT NULL DEFAULT 5000,
    late_fee_percentage DECIMAL(5,2),
    admin_fee_type      VARCHAR(20) NOT NULL DEFAULT 'percentage' CHECK (admin_fee_type IN ('fixed', 'percentage')),
    admin_fee_value     DECIMAL(10,2) NOT NULL DEFAULT 1.00,
    admin_fee_cap       BIGINT,
    grace_period_days   INT NOT NULL DEFAULT 3,
    max_active_loans    INT NOT NULL DEFAULT 1,
    requires_guarantor  BOOLEAN NOT NULL DEFAULT false,
    requires_downpayment BOOLEAN NOT NULL DEFAULT false,
    downpayment_min_pct DECIMAL(5,2),
    status              VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'inactive', 'coming_soon')),
    display_order       INT DEFAULT 0,
    created_at          TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- Seed data
INSERT INTO financing_products (name_ar, name_en, type, min_amount, max_amount, min_term_days, max_term_days, profit_rate_min, profit_rate_max, late_fee_type, late_fee_amount, admin_fee_type, admin_fee_value, admin_fee_cap, grace_period_days, max_active_loans, requires_guarantor, display_order)
VALUES
    ('قرض حسن', 'Qard Hasan', 'qard_hasan', 50000, 500000, 30, 180, 0, 0, 'fixed', 5000, 'percentage', 1.00, 5000, 3, 1, true, 1),
    ('مرابحة', 'Murabaha', 'murabaha', 200000, 5000000, 90, 730, 5.00, 12.00, 'fixed', 10000, 'percentage', 2.00, 50000, 7, 3, false, 2),
    ('تمويل المنشآت الصغيرة', 'Micro-Enterprise Financing', 'micro', 500000, 10000000, 90, 365, 7.00, 15.00, 'fixed', 15000, 'percentage', 2.50, 100000, 7, 1, false, 3);
```

---

## 2. financing_applications
Stores all financing applications.

```sql
CREATE TABLE financing_applications (
    id                  SERIAL PRIMARY KEY,
    user_id             INT NOT NULL REFERENCES users(id),
    product_id          INT NOT NULL REFERENCES financing_products(id),
    amount              BIGINT NOT NULL,
    term_days           INT NOT NULL,
    purpose             VARCHAR(50) NOT NULL CHECK (purpose IN ('medical', 'education', 'business', 'home_appliance', 'electronics', 'furniture', 'renovation', 'vehicle', 'wedding', 'other')),
    purpose_details     TEXT,
    status              VARCHAR(20) NOT NULL DEFAULT 'draft' CHECK (status IN ('draft', 'submitted', 'underwriting', 'approved', 'rejected', 'offer_accepted', 'disbursed', 'completed', 'defaulted')),
    credit_score        SMALLINT,
    credit_score_factors JSONB,
    decision_engine     VARCHAR(20) CHECK (decision_engine IN ('auto', 'manual', 'hybrid')),
    documents           JSONB DEFAULT '[]'::jsonb,
    guarantor_id        INT REFERENCES users(id),
    guarantor_status    VARCHAR(20) CHECK (guarantor_status IN ('pending', 'approved', 'rejected')),
    guarantor_approved_at TIMESTAMPTZ,
    approved_amount     BIGINT,
    approved_term_days  INT,
    offered_rate        DECIMAL(5,2),
    offer_expires_at    TIMESTAMPTZ,
    accepted_at         TIMESTAMPTZ,
    rejection_reason    TEXT,
    rejected_at         TIMESTAMPTZ,
    disbursed_at        TIMESTAMPTZ,
    underwriter_id      INT REFERENCES admin_users(id),
    underwriting_notes  TEXT,
    submitted_at        TIMESTAMPTZ,
    created_at          TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_applications_user ON financing_applications(user_id);
CREATE INDEX idx_applications_status ON financing_applications(status);
CREATE INDEX idx_applications_product ON financing_applications(product_id);
CREATE INDEX idx_applications_submitted ON financing_applications(submitted_at) WHERE status = 'submitted';
```

---

## 3. financing_contracts
Generated contracts for approved and disbursed applications.

```sql
CREATE TABLE financing_contracts (
    id                  SERIAL PRIMARY KEY,
    application_id      INT NOT NULL REFERENCES financing_applications(id),
    contract_number     VARCHAR(50) NOT NULL UNIQUE,
    contract_date       DATE NOT NULL DEFAULT CURRENT_DATE,
    product_type        VARCHAR(20) NOT NULL,
    principal           BIGINT NOT NULL,
    profit_amount       BIGINT NOT NULL DEFAULT 0,
    profit_rate         DECIMAL(5,2),
    admin_fee           BIGINT NOT NULL DEFAULT 0,
    total_amount        BIGINT NOT NULL,
    installment_count   INT NOT NULL,
    installment_amount  BIGINT NOT NULL,
    installment_frequency VARCHAR(20) NOT NULL DEFAULT 'daily' CHECK (installment_frequency IN ('daily', 'weekly', 'monthly')),
    status              VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'completed', 'defaulted', 'restructured')),
    restructure_count   INT NOT NULL DEFAULT 0,
    defaulted_at        TIMESTAMPTZ,
    completed_at        TIMESTAMPTZ,
    murabaha_item_description TEXT,
    murabaha_supplier_id    INT,
    murabaha_purchase_price  BIGINT,
    murabaha_sale_price      BIGINT,
    guarantor_contract_signed_at TIMESTAMPTZ,
    contract_pdf_url    VARCHAR(500),
    charity_fees_accrued BIGINT NOT NULL DEFAULT 0,
    charity_fees_paid   BIGINT NOT NULL DEFAULT 0,
    created_at          TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_contracts_application ON financing_contracts(application_id);
CREATE INDEX idx_contracts_status ON financing_contracts(status);
CREATE INDEX idx_contracts_user ON financing_contracts(user_id);
CREATE UNIQUE INDEX idx_contracts_number ON financing_contracts(contract_number);

-- Contract number format: BZ-{PRODUCT_PREFIX}-{YEAR}-{SEQUENTIAL}
-- QH = Qard Hasan, MR = Murabaha, ME = Micro Enterprise
-- Example: BZ-QH-2026-00001
```

---

## 4. financing_repayments
Individual repayment installments and payment records.

```sql
CREATE TABLE financing_repayments (
    id                  SERIAL PRIMARY KEY,
    contract_id         INT NOT NULL REFERENCES financing_contracts(id),
    installment_number  INT NOT NULL,
    due_date            DATE NOT NULL,
    principal_part      BIGINT NOT NULL,
    profit_part         BIGINT NOT NULL DEFAULT 0,
    late_fee_part       BIGINT NOT NULL DEFAULT 0,
    charity_part        BIGINT NOT NULL DEFAULT 0,
    total_due           BIGINT NOT NULL,
    paid_amount         BIGINT NOT NULL DEFAULT 0,
    paid_at             TIMESTAMPTZ,
    payment_method      VARCHAR(20) CHECK (payment_method IN ('auto_deduct', 'manual_wallet', 'manual_cash', 'agent')),
    transaction_id      VARCHAR(100),
    status              VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('paid', 'pending', 'overdue', 'partial', 'waived')),
    retry_count         INT NOT NULL DEFAULT 0,
    last_retry_at       TIMESTAMPTZ,
    waived_at           TIMESTAMPTZ,
    waived_by           INT REFERENCES admin_users(id),
    notes               TEXT,
    created_at          TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_repayments_contract ON financing_repayments(contract_id);
CREATE INDEX idx_repayments_due_date ON financing_repayments(due_date);
CREATE INDEX idx_repayments_status ON financing_repayments(status);
CREATE INDEX idx_repayments_overdue ON financing_repayments(due_date, status) WHERE status IN ('pending', 'overdue');
CREATE UNIQUE INDEX idx_repayments_contract_installment ON financing_repayments(contract_id, installment_number);

-- Partial payment allocation:
-- 1st: late_fee_part (if any) → goes to charity liability
-- 2nd: profit_part
-- 3rd: principal_part
-- Status = 'partial' if paid_amount < total_due but > 0
```

---

## 5. financing_credit_scores
Credit score records and history.

```sql
CREATE TABLE financing_credit_scores (
    id                  SERIAL PRIMARY KEY,
    user_id             INT NOT NULL REFERENCES users(id),
    score               SMALLINT NOT NULL CHECK (score >= 300 AND score <= 850),
    score_tier          VARCHAR(20) GENERATED ALWAYS AS (
        CASE 
            WHEN score >= 750 THEN 'excellent'
            WHEN score >= 650 THEN 'good'
            WHEN score >= 550 THEN 'fair'
            WHEN score >= 450 THEN 'poor'
            ELSE 'very_poor'
        END
    ) STORED,
    factors             JSONB,
    model_version       VARCHAR(20) NOT NULL,
    feature_snapshot    JSONB,
    calculated_at       TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    created_at          TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_credit_scores_user ON financing_credit_scores(user_id);
CREATE INDEX idx_credit_scores_user_latest ON financing_credit_scores(user_id, calculated_at DESC);

-- JSON structure for factors:
-- {
--   "transaction_activity": {"score": 476, "weight": 25, "details": "3 transactions/day average"},
--   "savings_behavior": {"score": 272, "weight": 20, "details": "Avg balance 75,000 SYP"},
--   "bill_payment": {"score": 612, "weight": 15, "details": "95% on-time payment"},
--   "wallet_usage": {"score": 442, "weight": 10, "details": "Wallet age 8 months"},
--   "agent_interaction": {"score": 374, "weight": 10, "details": "5 active agents"},
--   "kyc_level": {"score": 510, "weight": 10, "details": "KYC Level 2"},
--   "existing_products": {"score": 300, "weight": 10, "details": "1 active product"}
-- }
```

---

## 6. application_documents
Uploaded documents for applications.

```sql
CREATE TABLE application_documents (
    id                  SERIAL PRIMARY KEY,
    application_id      INT NOT NULL REFERENCES financing_applications(id),
    document_type       VARCHAR(50) NOT NULL,
    file_id             VARCHAR(100) NOT NULL,
    file_name           VARCHAR(255),
    file_size_bytes     INT,
    mime_type           VARCHAR(100),
    status              VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'verified', 'rejected')),
    verified_by         INT REFERENCES admin_users(id),
    verified_at         TIMESTAMPTZ,
    rejection_reason    TEXT,
    uploaded_at         TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_app_docs_application ON application_documents(application_id);
```

---

## 7. financing_restructures
Restructuring requests and history.

```sql
CREATE TABLE financing_restructures (
    id                  SERIAL PRIMARY KEY,
    contract_id         INT NOT NULL REFERENCES financing_contracts(id),
    request_type        VARCHAR(20) NOT NULL CHECK (request_type IN ('extend', 'reduce', 'holiday')),
    reason              TEXT NOT NULL,
    previous_term_days  INT NOT NULL,
    new_term_days       INT NOT NULL,
    previous_installment BIGINT NOT NULL,
    new_installment     BIGINT NOT NULL,
    fee_amount          BIGINT NOT NULL DEFAULT 25000,
    status              VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'approved', 'rejected')),
    approved_by         INT REFERENCES admin_users(id),
    approved_at         TIMESTAMPTZ,
    created_at          TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
```

---

## 8. charity_fees_account
Tracks late fees allocated to charity.

```sql
CREATE TABLE charity_fees_account (
    id                  SERIAL PRIMARY KEY,
    contract_id         INT NOT NULL REFERENCES financing_contracts(id),
    installment_id      INT REFERENCES financing_repayments(id),
    amount              BIGINT NOT NULL,
    currency            VARCHAR(3) NOT NULL DEFAULT 'SYP',
    recorded_at         TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    disbursed           BOOLEAN NOT NULL DEFAULT false,
    disbursed_at        TIMESTAMPTZ,
    disbursed_to        VARCHAR(255),
    disbursement_ref    VARCHAR(100),
    created_at          TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_charity_disbursed ON charity_fees_account(disbursed);
```

---

## 9. financing_disbursements
Disbursement transaction records.

```sql
CREATE TABLE financing_disbursements (
    id                  SERIAL PRIMARY KEY,
    contract_id         INT NOT NULL REFERENCES financing_contracts(id),
    disbursement_type   VARCHAR(20) NOT NULL CHECK (disbursement_type IN ('wallet', 'merchant', 'supplier', 'split')),
    target_id           VARCHAR(100),
    target_type         VARCHAR(20) CHECK (target_type IN ('user_wallet', 'merchant_account', 'supplier_account')),
    amount              BIGINT NOT NULL,
    cfe_transaction_id  VARCHAR(100),
    status              VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'completed', 'failed', 'reversed')),
    failure_reason      TEXT,
    completed_at        TIMESTAMPTZ,
    created_at          TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
```

## Partitioning Strategy
- `financing_repayments`: Partitioned by `due_date` (quarterly) for large scale
- `financing_applications`: Archived after 1 year of completion
- `financing_credit_scores`: Keep last 24 months online, archive older
