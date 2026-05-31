# 25 — Security Architecture

---

## Authentication & Authorization

| Actor | Auth Method | Session |
|-------|-------------|---------|
| Company HR (dashboard) | Email + password + TOTP 2FA | JWT (1 hour expiry) |
| Company CFO (dashboard) | Email + password + TOTP 2FA | JWT (1 hour expiry) |
| Employee (mobile) | Phone OTP | JWT (7 day refresh) |
| Beza Admin | Email + password + hardware key | JWT (30 min expiry) |
| API (direct integration) | API key (Bearer) + mTLS | Per-request |
| Webhook callbacks | HMAC-SHA256 signature | Per-payload |

## Critical Operations — Multi-Factor

| Operation | MFA Required | Method |
|-----------|-------------|--------|
| Create new batch | ✅ | PIN (6-digit, entered on dashboard) |
| Retry failed batch | ✅ | PIN |
| Add/remove employee | ✅ | PIN |
| Change settlement period | ✅ | TOTP |
| API key regeneration | ✅ | TOTP + email confirmation |
| Company suspension | ✅ (admin) | Hardware key |

## API Security

| Measure | Implementation |
|---------|---------------|
| mTLS | Client certificate required for direct API integration |
| Rate limiting | 100 req/min per API key; 10 req/s burst |
| Idempotency | All POST endpoints require Idempotency-Key header |
| Input validation | Zod schemas on all endpoints; reject unexpected fields |
| SQL injection | Parameterized queries (SQLAlchemy 2.0) |
| CORS | Whitelist: `https://dashboard.beza.sy`, `https://*.beza.sy` |

## Data Security

### At Rest

| Data | Encryption | Key Management |
|------|-----------|---------------|
| Database (PostgreSQL) | TDE (AES-256) | AWS KMS / HashiCorp Vault |
| Payslip PDFs | AES-256 per-file | Per-employee key derived from national ID |
| API keys (stored) | bcrypt hash | — |
| PII (phone, national ID) | Column-level encryption (pgcrypto) | Application-level key |
| Audit logs | Append-only, signed with SHA-256 hash chain | Immutable |

### In Transit

| Channel | Protocol |
|---------|----------|
| API (internal) | mTLS v1.3 |
| API (external) | HTTPS TLS v1.3 |
| Database | PostgreSQL SSL (mutual) |
| CFE calls | Internal gRPC (mTLS) |
| SMS gateway | HTTPS + API token |
| Webhook callbacks | HTTPS + HMAC signature |

## Audit Logging

```sql
CREATE TABLE payroll_audit_log (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    event       VARCHAR(50) NOT NULL,   -- "batch.created", "batch.confirmed", "transaction.retried"
    actor_id    UUID NOT NULL,
    actor_type  VARCHAR(20) NOT NULL,   -- "company_user", "employee", "admin", "system"
    resource    VARCHAR(50) NOT NULL,   -- "payroll_batch", "payroll_transaction"
    resource_id UUID NOT NULL,
    details     JSONB,
    ip_address  INET,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW(),

    -- Immutable chain
    prev_hash   VARCHAR(64),            -- SHA-256 of previous audit log row
    signature   VARCHAR(256)            -- Beza-issued signing
);

CREATE INDEX idx_audit_resource ON payroll_audit_log(event, created_at);
```

## Secrets Management

- All secrets stored in HashiCorp Vault (on-prem)
- Database passwords rotated every 90 days
- CFE API keys rotated every 30 days
- SMS gateway tokens rotated on compromise
- No secrets in code, config files, or env vars (injected at deploy time)

## Incident Response

| Severity | Example | Response Time | Actions |
|----------|---------|---------------|---------|
| P0 | Data breach, wallet compromise | 15 min | Isolate system, notify CBS, notify affected users |
| P1 | Batch processing down | 30 min | Failover to DR, engage on-call engineering |
| P2 | Single company cannot process | 2 hours | Investigate, support ticket |
| P3 | Dashboard slow | 24 hours | Performance tuning |
