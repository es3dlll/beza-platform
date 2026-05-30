# Security Architecture

## Threat Model

| Threat | Severity | Mitigation |
|---|---|---|
| Unauthorized API access | Critical | JWT authentication + API key for telecom endpoints |
| Payment fraud (double-spend) | Critical | Idempotency keys on all payment/top-up operations |
| Gift card code brute-force | High | Rate limiting (5 attempts/min per IP) + code entropy (16-char alphanumeric) |
| Vendor payout manipulation | Critical | Commission calculation server-side only; immutable ledger |
| Telecom API credential leak | Critical | Secrets stored in vault; IP-whitelisted access |
| SQL injection | High | Parameterized queries via ORM + input sanitization |
| XSS in product listings | Medium | HTML sanitization on all vendor inputs |
| Insecure direct object reference | High | Row-level security; user/vendor scoped queries |

## Authentication & Authorization

### API Authentication
```
Mobile App: JWT (access token: 15min, refresh token: 7 days)
Vendor/Admin: JWT (access token: 15min, refresh token: 24h)
Telecom API: Static API Key + Mutual TLS
Webhooks: HMAC-SHA256 signature verification
```

### Role-Based Access Control
| Role | Permissions |
|---|---|
| `user` | Browse products, purchase, view own orders/gift cards, create reviews |
| `vendor` | All user + manage own products, view orders, request payouts |
| `admin` | All vendor + moderate products, manage vendors, configure commissions, refunds |
| `superadmin` | All admin + platform config, financial reports, audit logs |

## Data Protection

### At Rest
- Database encryption at rest (AES-256)
- PII fields pseudonymized in logs
- Gift card codes hashed in database (SHA-256 + salt)
- Vendor payout bank details encrypted (AES-256-GCM)

### In Transit
- TLS 1.3 for all external communication
- Mutual TLS for telecom provider connections
- Internal service mesh with mTLS

## Fraud Prevention

### Rules Engine
- Maximum 10 top-ups per user per hour
- Maximum 50,000 SYP total top-up per day per user
- New accounts (< 7 days) limited to 25,000 SYP total orders
- Velocity check: > 5 failed top-ups in 10 min → block 1 hour
- Device fingerprinting for high-value orders (> 100,000 SYP)
- Gift card purchase limit: 500,000 SYP/day per user

### Monitoring Alerts
| Rule | Action |
|---|---|
| > 10 failed top-ups from same user in 1 hour | Suspend account, notify security |
| > 5 gift card redemptions on same code | Block code, notify admin |
| > 3 vendor payout requests in 24h | Flag for manual review |
| Price manipulation (product price changed by > 50% in 1h) | Auto-suspend product, notify admin |
