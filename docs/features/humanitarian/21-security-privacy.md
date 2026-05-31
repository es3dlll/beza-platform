# Security & Data Privacy

## Threat Model

| Threat | Severity | Mitigation |
|--------|----------|------------|
| Beneficiary PII leak | Critical | AES-256-GCM encryption at rest, tokenisation in logs |
| Distribution fraud (agent) | Critical | Biometric verification, GPS location logging, agent session PIN |
| Sanctions evasion | Critical | Automated screening before enrolment, periodic re-screening |
| Man-in-the-middle on agent app | High | mTLS between agent app and server |
| Duplicate beneficiary registration | High | Phone hash + biometric dedup at enrolment |
| Unauthorised access to NGO dashboard | High | OAuth 2.0 + RBAC, session timeout 15 min |
| Wallet theft (beneficiary phone stolen) | High | PIN-protected wallet + biometric confirmation for cash-out |
| Replay attack on voucher redemption | Medium | Unique voucher code + PIN + single-use token |
| Data sovereignty violation | Medium | Data stored in-region (Jordan/Istanbul) |

## Data Encryption

| Data Category | At Rest | In Transit | Logging |
|---------------|---------|------------|---------|
| Beneficiary name | AES-256-GCM | TLS 1.3 | Tokenised (BNF-XXXX) |
| UNHCR ID | AES-256-GCM | TLS 1.3 | Tokenised |
| Phone number | AES-256-GCM | TLS 1.3 | Hash-only (SHA-256) |
| Biometric templates | AES-256-GCM | TLS 1.3 | Never logged |
| GPS coordinates | AES-256-GCM | TLS 1.3 | Encrypted |
| Financial transactions | AES-256-GCM | TLS 1.3 | Amount masked |
| Sanctions match details | AES-256-GCM | TLS 1.3 | Severity only |

## Access Control (RBAC)

| Role | Permissions |
|------|-------------|
| `ngo_admin` | Full CRUD on programs, beneficiaries, distributions; view reports; manage agents |
| `ngo_staff` | View programs, upload beneficiaries, trigger distributions, view reports |
| `compliance_officer` | View sanctions screening, resolve matches, view audit log |
| `field_agent` | Verify beneficiaries (enrolment + distribution), view assigned programs |
| `merchant` | Redeem vouchers, view settlement history |
| `donor` | View reports (read-only, no PII), export aggregated data |
| `system` | Internal service-to-service (queue workers, scheduled jobs) |

## Authentication Methods

| User Type | Method | MFA |
|-----------|--------|-----|
| NGO Staff (Web) | Email + password | TOTP (required) |
| Field Agent (Mobile) | PIN + device fingerprint | Biometric on device |
| Merchant (Mobile) | Phone + OTP | None |
| Donor (Web) | Email + password | TOTP (required) |
| API Clients | OAuth 2.0 client_credentials + mTLS | — |

## Logging & PII Masking

All logs must pass through a PII-stripping middleware:

```
Raw event:  "Verified beneficiary Fatima Al-Omar (SYR-8293-001) +963955123456"
Masked log: "Verified beneficiary [BNF-a1b2c3] [UNHCR-encrypted] [phone-hash:a1b2c3d4]"
```

## Audit Log Schema (Immutable)

| Column | Description |
|--------|-------------|
| event_id | UUID (monotonic) |
| event_type | `distribution.created`, `beneficiary.verified`, `sanctions.match`, etc. |
| actor_id | User or system that performed action |
| resource_type | `program`, `beneficiary`, `distribution`, `voucher` |
| resource_id | UUID of affected resource |
| action | `create`, `read`, `update`, `delete`, `verify`, `distribute` |
| metadata | JSON — non-PII context (status change, amounts, counts) |
| change_hash | SHA-256 of previous state for tamper-proof chain |
| timestamp | TIMESTAMPTZ |

## Data Retention

| Data | Retention | Deletion |
|------|-----------|----------|
| Beneficiary records | Duration of program + 5 years (donor requirement) | Anonymised after retention |
| Biometric templates | Duration of program + 1 year | Purged after retention |
| Distribution records | 10 years (donor audit requirement) | Archived after 10 years |
| Audit logs | 15 years (humanitarian legal requirement) | Never deleted |
| Verification photos | 30 days | Purged |
| Donor reports | 10 years | Archived |
| SMS logs | 2 years | Purged |

## Security Compliance

| Standard | Compliance |
|----------|------------|
| ISO 27001 | Aligned (certification planned) |
| PCI DSS | N/A (no card processing) |
| GDPR | Beneficiary data rights respected (Syria context — limited enforcement) |
| UN Data Privacy Principles | Full compliance |
| ICRC Data Protection in Humanitarian Action | Fully aligned |
