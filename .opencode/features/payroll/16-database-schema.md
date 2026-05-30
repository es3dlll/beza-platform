# 16 — Database Schema

> **Key File** — All tables, indexes, relationships for the Payroll module.

---

## Table: `payroll_companies`

```sql
CREATE TABLE payroll_companies (
    id                  UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name_ar             VARCHAR(200) NOT NULL,
    name_en             VARCHAR(200),
    license_number      VARCHAR(100) NOT NULL UNIQUE,
    tax_id              VARCHAR(100),
    status              VARCHAR(20) NOT NULL DEFAULT 'pending_review'
                        CHECK (status IN ('pending_review', 'active', 'suspended', 'rejected')),
    payroll_account_id  UUID NOT NULL,            -- FK → ledger.accounts (CFE)
    settlement_period   VARCHAR(5) NOT NULL DEFAULT 'T+0'
                        CHECK (settlement_period IN ('T+0', 'T+1', 'T+3')),
    settlement_limit    DECIMAL(18,2) DEFAULT 0,  -- Max unsettled amount (T+1 only)
    monthly_fee         DECIMAL(18,2) DEFAULT 50000,
    api_key_hash        VARCHAR(255),              -- bcrypt hash of API key
    webhook_url         VARCHAR(500),
    created_at          TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    approved_at         TIMESTAMPTZ,
    approved_by         UUID                       -- FK → admin_users
);

CREATE INDEX idx_payroll_companies_status ON payroll_companies(status);
CREATE INDEX idx_payroll_companies_license ON payroll_companies(license_number);
```

---

## Table: `payroll_employees`

```sql
CREATE TABLE payroll_employees (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    company_id      UUID NOT NULL REFERENCES payroll_companies(id) ON DELETE CASCADE,
    employee_ref    VARCHAR(50) NOT NULL,        -- Company's internal employee ID (e.g., "EMP-001")
    user_id         UUID,                         -- FK → users.id (Beza user, nullable until activation)
    full_name_ar    VARCHAR(200) NOT NULL,
    phone           VARCHAR(20) NOT NULL,
    department      VARCHAR(100),
    role            VARCHAR(100),
    salary_amount   DECIMAL(18,2) NOT NULL,
    currency        VARCHAR(3) NOT NULL DEFAULT 'SYP',
    status          VARCHAR(20) NOT NULL DEFAULT 'active'
                    CHECK (status IN ('active', 'terminated')),
    joined_at       DATE NOT NULL DEFAULT CURRENT_DATE,
    terminated_at   DATE,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),

    UNIQUE (company_id, employee_ref)
);

CREATE INDEX idx_payroll_employees_company ON payroll_employees(company_id);
CREATE INDEX idx_payroll_employees_user ON payroll_employees(user_id);
CREATE INDEX idx_payroll_employees_phone ON payroll_employees(phone);
```

---

## Table: `payroll_batches`

```sql
CREATE TABLE payroll_batches (
    id                UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    company_id        UUID NOT NULL REFERENCES payroll_companies(id) ON DELETE CASCADE,
    batch_ref         VARCHAR(30) NOT NULL,        -- e.g., "B-2026-05-001"
    total_employees   INTEGER NOT NULL,
    total_amount      DECIMAL(18,2) NOT NULL,
    total_fee         DECIMAL(18,2) NOT NULL DEFAULT 0,
    currency          VARCHAR(3) NOT NULL DEFAULT 'SYP',
    status            VARCHAR(20) NOT NULL DEFAULT 'pending'
                      CHECK (status IN ('pending', 'processing', 'completed', 'partial_failure', 'failed', 'settled')),
    schedule_date     DATE,                         -- NULL = immediate
    processed_at      TIMESTAMPTZ,
    failed_count      INTEGER NOT NULL DEFAULT 0,
    settled_at        TIMESTAMPTZ,
    hold_ref          VARCHAR(100),                 -- CFE hold reference
    hold_amount       DECIMAL(18,2),                -- Amount placed on hold
    payslip_generated_at TIMESTAMPTZ,
    created_at        TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at        TIMESTAMPTZ NOT NULL DEFAULT NOW(),

    UNIQUE (company_id, batch_ref)
);

CREATE INDEX idx_payroll_batches_company ON payroll_batches(company_id);
CREATE INDEX idx_payroll_batches_status ON payroll_batches(status);
CREATE INDEX idx_payroll_batches_created ON payroll_batches(created_at);
CREATE INDEX idx_payroll_batches_schedule ON payroll_batches(schedule_date)
    WHERE status = 'pending' AND schedule_date IS NOT NULL;
```

---

## Table: `payroll_transactions`

```sql
CREATE TABLE payroll_transactions (
    id                UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    batch_id          UUID NOT NULL REFERENCES payroll_batches(id) ON DELETE CASCADE,
    employee_id       UUID NOT NULL REFERENCES payroll_employees(id),
    amount            DECIMAL(18,2) NOT NULL,
    fee               DECIMAL(18,2) NOT NULL DEFAULT 0,
    currency          VARCHAR(3) NOT NULL DEFAULT 'SYP',
    status            VARCHAR(20) NOT NULL DEFAULT 'pending'
                      CHECK (status IN ('pending', 'success', 'failed', 'failed_permanent')),
    failure_reason    VARCHAR(100),                 -- "insufficient_balance", "wallet_not_active", "user_not_found", "cfe_error"
    retry_count       INTEGER NOT NULL DEFAULT 0,
    last_retry_at     TIMESTAMPTZ,
    paid_at           TIMESTAMPTZ,
    cfe_tx_ref        VARCHAR(100),                 -- CFE credit transaction reference
    idempotency_key   UUID NOT NULL,                -- Prevent duplicate processing
    created_at        TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at        TIMESTAMPTZ NOT NULL DEFAULT NOW(),

    UNIQUE (batch_id, employee_id)
);

CREATE INDEX idx_payroll_tx_batch ON payroll_transactions(batch_id);
CREATE INDEX idx_payroll_tx_employee ON payroll_transactions(employee_id);
CREATE INDEX idx_payroll_tx_status ON payroll_transactions(status);
CREATE INDEX idx_payroll_tx_failed_retry ON payroll_transactions(status, retry_count)
    WHERE status IN ('failed', 'failed_permanent');
```

---

## Table: `payroll_company_documents`

```sql
CREATE TABLE payroll_company_documents (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    company_id      UUID NOT NULL REFERENCES payroll_companies(id) ON DELETE CASCADE,
    doc_type        VARCHAR(30) NOT NULL
                    CHECK (doc_type IN ('license', 'tax_cert', 'id_authorized', 'beneficial_owner_id', 'board_resolution')),
    file_key        VARCHAR(500) NOT NULL,         -- S3 key
    file_name       VARCHAR(200) NOT NULL,
    file_size       INTEGER NOT NULL,              -- bytes
    uploaded_at     TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    verified_at     TIMESTAMPTZ,
    verified_by     UUID,
    status          VARCHAR(20) NOT NULL DEFAULT 'pending'
                    CHECK (status IN ('pending', 'verified', 'rejected'))

);

CREATE INDEX idx_payroll_docs_company ON payroll_company_documents(company_id);
```

---

## Table: `payroll_settlements`

```sql
CREATE TABLE payroll_settlements (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    company_id      UUID NOT NULL REFERENCES payroll_companies(id) ON DELETE CASCADE,
    batch_id        UUID REFERENCES payroll_batches(id),  -- NULL if bulk settlement
    amount          DECIMAL(18,2) NOT NULL,
    type            VARCHAR(10) NOT NULL
                    CHECK (type IN ('prefund', 't1_settlement', 'fee')),
    status          VARCHAR(20) NOT NULL DEFAULT 'pending'
                    CHECK (status IN ('pending', 'cleared', 'failed')),
    cfe_ref         VARCHAR(100),
    settled_at      TIMESTAMPTZ,
    due_at          DATE NOT NULL,                  -- Settlement due date
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_payroll_settlements_company ON payroll_settlements(company_id);
CREATE INDEX idx_payroll_settlements_due ON payroll_settlements(due_at)
    WHERE status = 'pending';
```

---

## Entity Relationship Summary

```
payroll_companies  1──N→ payroll_employees
payroll_companies  1──N→ payroll_batches
payroll_companies  1──N→ payroll_company_documents
payroll_companies  1──N→ payroll_settlements
payroll_batches    1──N→ payroll_transactions
payroll_employees  1──N→ payroll_transactions
```

---

## Retention & Archival

| Table | Retention | Action |
|-------|-----------|--------|
| `payroll_transactions` | Indefinite (7 years min) | Partition by year |
| `payroll_batches` | Indefinite | — |
| `payroll_employees` | Indefinite (even terminated) | Status = `terminated` |
| `payroll_settlements` | 7 years | Archive after 7 years |
| `payroll_company_documents` | 7 years after company closure | Archive |
