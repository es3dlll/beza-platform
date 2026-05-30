# 20 — Security Architecture

## 20.1 Threat Model

| Threat | Severity | Mitigation |
|---|---|---|
| Unauthorised payment | Critical | Biometric + PIN, device binding |
| Payment interception (MITM) | Critical | TLS 1.3, certificate pinning |
| Invoice manipulation | High | Server-side total recalculation |
| Fake school registration | High | KYC, physical verification for P0 |
| Receipt forgery | High | Digital signature on QR code |
| Session hijacking | High | Short-lived JWTs, refresh rotation |
| Wallet balance manipulation | Critical | Server-authoritative balance |
| SQL injection | High | Parameterised queries, ORM |
| XSS on school dashboard | Medium | Input sanitisation, CSP |
| Rate limiting bypass | Medium | Redis-based rate limiter |

## 20.2 Authentication & Authorisation

| Actor | Auth Method | Session |
|---|---|---|
| Parent | Phone OTP + biometric + PIN | JWT (15 min access, 7 day refresh) |
| School staff | Email/password + 2FA (WhatsApp OTP) | JWT (1 hour access) |
| Beza admin | SSO + hardware MFA key | JWT (30 min access) |

### Payment Authorisation
- Payment requires: valid JWT + device fingerprint match + correct PIN
- PIN hashed with argon2id before sending to server
- Server never stores raw PIN
- Idempotency key prevents duplicate charges

## 20.3 Data Encryption

| Data State | Mechanism |
|---|---|
| In transit (API) | TLS 1.3, PFS cipher suites |
| In transit (internal) | mTLS between services |
| At rest (database) | AES-256-GCM column-level encryption for PII |
| At rest (receipts) | Server-side encryption (S3 SSE) |
| Backups | AES-256 encrypted before upload |

### Encrypted Fields
- Parent name, phone, email
- Student name
- School bank account details
- Staff credentials

## 20.4 Payment Security

- **PCI DSS compliance**: Beza holds PCI DSS Level 1 (via underlying processor)
- **Tokenisation**: Card numbers never touch Beza servers; PSP token used
- **3DS**: All card payments require 3D Secure authentication
- **Velocity checks**: Max 10 payments per parent per hour
- **Unusual pattern detection**: Large payments (>5M SYP) flagged for OTP confirmation
- **Cooling period**: First payment to new school limited to 500K SYP until trust established

## 20.5 Fraud Detection Rules

| Rule | Action |
|---|---|
| 5+ failed PIN attempts in 10 min | Lock account 30 min |
| Payment from unrecognised device | Require OTP verification |
| School balance misreporting | Flag for manual audit |
| Same IP paying 50+ different students | Flag as potential fraud |
| Invoice amount tampered client-side | Reject, recalculate server-side |
| Rapid-fire payment attempts | Rate limit + CAPTCHA |

## 20.6 Audit Logging

All financial operations logged to immutable audit store:

```json
{
  "event_id": "uuid",
  "event_type": "payment.created",
  "actor": {"id": "parent-uuid", "role": "parent"},
  "resource": {"type": "payment", "id": "payment-uuid"},
  "changes": {"amount": 995000, "from_status": null, "to_status": "completed"},
  "ip_address": "x.x.x.x",
  "device_id": "device-fingerprint",
  "timestamp": "2026-05-15T09:30:00Z",
  "previous_hash": "sha256-of-previous-event",
  "signature": "ed25519-sig"
}
```

## 20.7 API Security

- Rate limits: 30 req/min per parent, 120 req/min per school
- JWT must include `scope: education:payment` for payment endpoints
- All POST/PUT requests require CSRF token (double-submit cookie pattern)
- API keys for school webhooks: rotated every 90 days
- IP whitelist optional for school API integrations
