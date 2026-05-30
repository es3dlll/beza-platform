# 16 — Database Schema

## 16.1 PostgreSQL Tables

### schools
```sql
CREATE TABLE schools (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name_ar VARCHAR(200) NOT NULL,
    name_en VARCHAR(200),
    type VARCHAR(20) NOT NULL CHECK (type IN ('PUBLIC','PRIVATE','UNIVERSITY','TUTORING','ONLINE')),
    licence_number VARCHAR(50),
    tax_number VARCHAR(50),
    governorate VARCHAR(50) NOT NULL,
    city VARCHAR(50) NOT NULL,
    district VARCHAR(50),
    principal_name VARCHAR(100),
    finance_phone VARCHAR(20) NOT NULL,
    finance_email VARCHAR(100),
    status VARCHAR(20) DEFAULT 'PENDING' CHECK (status IN ('PENDING','ACTIVE','SUSPENDED','CLOSED')),
    tier VARCHAR(20) DEFAULT 'FREE' CHECK (tier IN ('FREE','STARTER','PRO','ENTERPRISE')),
    max_students INTEGER DEFAULT 100,
    bank_account_iban VARCHAR(34),
    bank_name VARCHAR(100),
    settlement_currency VARCHAR(3) DEFAULT 'SYP',
    onboarding_completed BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX idx_schools_status ON schools(status);
CREATE INDEX idx_schools_governorate ON schools(governorate);
CREATE INDEX idx_schools_type ON schools(type);
```

### fee_templates
```sql
CREATE TABLE fee_templates (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    school_id UUID NOT NULL REFERENCES schools(id) ON DELETE CASCADE,
    name VARCHAR(200) NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    term VARCHAR(20) NOT NULL,
    grade VARCHAR(50) NOT NULL,
    due_date DATE NOT NULL,
    late_fee_percent DECIMAL(5,2) DEFAULT 2.00,
    late_fee_max_percent DECIMAL(5,2) DEFAULT 10.00,
    instalment_allowed BOOLEAN DEFAULT TRUE,
    num_instalments INTEGER DEFAULT 1,
    sibling_discount_percent DECIMAL(5,2) DEFAULT 0,
    early_bird_discount_amount DECIMAL(12,2) DEFAULT 0,
    early_bird_due_date DATE,
    status VARCHAR(20) DEFAULT 'DRAFT' CHECK (status IN ('DRAFT','PUBLISHED','ARCHIVED')),
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX idx_ft_school ON fee_templates(school_id);
CREATE INDEX idx_ft_status ON fee_templates(status);
```

### fee_line_items
```sql
CREATE TABLE fee_line_items (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    fee_template_id UUID NOT NULL REFERENCES fee_templates(id) ON DELETE CASCADE,
    name VARCHAR(200) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    is_mandatory BOOLEAN DEFAULT TRUE,
    sort_order INTEGER DEFAULT 0
);

CREATE INDEX idx_fli_template ON fee_line_items(fee_template_id);
```

### students
```sql
CREATE TABLE students (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    school_id UUID NOT NULL REFERENCES schools(id) ON DELETE CASCADE,
    student_id_local VARCHAR(50),
    full_name_ar VARCHAR(200) NOT NULL,
    full_name_en VARCHAR(200),
    grade VARCHAR(50) NOT NULL,
    section VARCHAR(50),
    date_of_birth DATE,
    gender VARCHAR(10) CHECK (gender IN ('MALE','FEMALE')),
    guardian_primary_id UUID REFERENCES users(id),
    guardian_secondary_id UUID REFERENCES users(id),
    status VARCHAR(20) DEFAULT 'ACTIVE' CHECK (status IN ('ACTIVE','GRADUATED','TRANSFERRED','WITHDRAWN')),
    enrolment_date DATE NOT NULL DEFAULT CURRENT_DATE,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX idx_students_school ON students(school_id);
CREATE INDEX idx_students_guardian ON students(guardian_primary_id);
CREATE INDEX idx_students_status ON students(status);
CREATE INDEX idx_students_grade ON students(grade);
```

### fee_invoices
```sql
CREATE TABLE fee_invoices (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    student_id UUID NOT NULL REFERENCES students(id) ON DELETE CASCADE,
    fee_template_id UUID NOT NULL REFERENCES fee_templates(id),
    invoice_number VARCHAR(50) UNIQUE NOT NULL,
    total_amount DECIMAL(12,2) NOT NULL,
    discount_amount DECIMAL(12,2) DEFAULT 0,
    discount_type VARCHAR(20),
    late_fee_amount DECIMAL(12,2) DEFAULT 0,
    total_due DECIMAL(12,2) NOT NULL,
    total_paid DECIMAL(12,2) DEFAULT 0,
    balance DECIMAL(12,2) NOT NULL,
    status VARCHAR(20) DEFAULT 'PENDING' CHECK (status IN ('PENDING','PAID','PARTIAL','OVERDUE','CANCELLED')),
    due_date DATE NOT NULL,
    issued_date DATE NOT NULL DEFAULT CURRENT_DATE,
    settled_date DATE,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX idx_fi_student ON fee_invoices(student_id);
CREATE INDEX idx_fi_status ON fee_invoices(status);
CREATE INDEX idx_fi_due_date ON fee_invoices(due_date);
CREATE INDEX idx_fi_school ON fee_invoices(student_id); -- via student join
```

### payments
```sql
CREATE TABLE payments (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    invoice_id UUID NOT NULL REFERENCES fee_invoices(id),
    parent_id UUID NOT NULL REFERENCES users(id),
    amount DECIMAL(12,2) NOT NULL,
    payment_method VARCHAR(20) NOT NULL CHECK (payment_method IN ('BEZA_WALLET','CARD','BANK_TRANSFER','OFFLINE_CASH')),
    payment_reference VARCHAR(100) UNIQUE NOT NULL,
    idempotency_key VARCHAR(100) UNIQUE NOT NULL,
    status VARCHAR(20) DEFAULT 'PENDING' CHECK (status IN ('PENDING','COMPLETED','FAILED','REFUNDED')),
    fx_rate DECIMAL(10,4),
    fx_from_currency VARCHAR(3),
    failure_reason VARCHAR(500),
    settled_to_school BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    completed_at TIMESTAMPTZ
);

CREATE INDEX idx_payments_invoice ON payments(invoice_id);
CREATE INDEX idx_payments_parent ON payments(parent_id);
CREATE INDEX idx_payments_status ON payments(status);
CREATE INDEX idx_payments_created ON payments(created_at);
```

### receipts
```sql
CREATE TABLE receipts (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    payment_id UUID UNIQUE NOT NULL REFERENCES payments(id),
    receipt_number VARCHAR(50) UNIQUE NOT NULL,
    pdf_url VARCHAR(500),
    qr_code TEXT,
    generated_at TIMESTAMPTZ DEFAULT NOW()
);
```

### schools_staff
```sql
CREATE TABLE schools_staff (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    school_id UUID NOT NULL REFERENCES schools(id) ON DELETE CASCADE,
    user_id UUID NOT NULL REFERENCES users(id),
    role VARCHAR(20) NOT NULL CHECK (role IN ('PRINCIPAL','FINANCE_MANAGER','ADMIN','TEACHER')),
    permissions JSONB,
    status VARCHAR(20) DEFAULT 'ACTIVE',
    created_at TIMESTAMPTZ DEFAULT NOW()
);
```

### settlement_batches
```sql
CREATE TABLE settlement_batches (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    school_id UUID NOT NULL REFERENCES schools(id),
    batch_date DATE NOT NULL,
    total_payments DECIMAL(14,2) NOT NULL,
    total_fees DECIMAL(14,2) NOT NULL,
    net_settlement DECIMAL(14,2) NOT NULL,
    num_transactions INTEGER NOT NULL,
    status VARCHAR(20) DEFAULT 'PENDING' CHECK (status IN ('PENDING','PROCESSING','COMPLETED','FAILED')),
    bank_reference VARCHAR(100),
    created_at TIMESTAMPTZ DEFAULT NOW(),
    completed_at TIMESTAMPTZ
);
```

### auto_pay_schedules
```sql
CREATE TABLE auto_pay_schedules (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    invoice_id UUID NOT NULL REFERENCES fee_invoices(id),
    parent_id UUID NOT NULL REFERENCES users(id),
    scheduled_date DATE NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    retry_count INTEGER DEFAULT 0,
    max_retries INTEGER DEFAULT 3,
    status VARCHAR(20) DEFAULT 'ACTIVE' CHECK (status IN ('ACTIVE','COMPLETED','FAILED','CANCELLED')),
    created_at TIMESTAMPTZ DEFAULT NOW()
);
```

## 16.2 Redis Data

| Key Pattern | Value | TTL |
|---|---|---|
| `edu:dashboard:{school_id}` | Aggregated dashboard JSON | 5 minutes |
| `edu:invoice:{invoice_id}:lock` | Processing flag | 30 seconds |
| `edu:rate:syp:eur` | Current FX rate | 1 hour |
| `edu:session:{admin_id}` | Admin session data | 24 hours |

## 16.3 Index Strategy

- All foreign keys indexed
- Composite index on `(school_id, status)` for dashboard queries
- Composite on `(parent_id, created_at DESC)` for payment history
- `invoice_number` and `receipt_number` unique for audit trail
