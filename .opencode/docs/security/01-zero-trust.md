# Zero Trust Security Model

## Principles
1. **Never trust, always verify** — Every request, internal or external, must be authenticated and authorized
2. **Least privilege** — Every service, user, and device gets minimum required access
3. **Assume breach** — Design for compromised credentials, insider threats, and zero-day exploits
4. **Micro-segmentation** — Network-level isolation between services
5. **Continuous verification** — Re-validate trust throughout session lifetime

## Authentication

### Multi-Factor Strategy
| Factor | Implementation | Used For |
|--------|---------------|----------|
| Something you know | PIN (6 digits), Password (12+ chars) | All logins |
| Something you have | TOTP (30s rotation), SMS OTP | Admin, High-value txns |
| Something you are | Face ID, Fingerprint | Mobile app |
| Something you do | Behavioral biometrics | Continuous auth |

### JWT Token Architecture
```
Access Token:
  - Type: JWT (RS256 signed)
  - Expiry: 15 minutes (mobile), 5 minutes (admin)
  - Claims: sub, tenant_id, roles[], permissions[], device_id, session_id
  - Bound to: IP (server), Device ID (mobile)

Refresh Token:
  - Type: Opaque (stored in Redis)
  - Expiry: 7 days (mobile), 1 day (admin), 30 days (remember me)
  - Rotation: Old token invalidated on refresh
  - Revocation: Immediate on password change, device removal

Session Token (Agent POS):
  - Type: Opaque (stored in Redis)
  - Expiry: 12 hours
  - Bound to: Terminal ID + Agent ID
```

## Authorization (RBAC + ABAC)

### Role Hierarchy
```
super_admin: Full system access, no restrictions
ops_manager: Financial operations, approvals, reports
compliance_officer: AML, KYC, fraud investigation, STR filing
loan_officer: Loan approval, collection management
support_agent: Ticket view/respond, account management (limited)
agent: Cash-in/cash-out, pickup, float management
merchant: QR generation, payment links, transaction history
user: Self-service, own data only
```

### Permission Matrix (Sample)
```
Permission                 User  Merchant  Agent  Support  Loan  Compliance  Ops  Super
wallet.view                ✓     ✓        ✓      ✓        ✓     ✓          ✓    ✓
wallet.transfer.send       ✓     ✗        ✗      ✗        ✗     ✗          ✓    ✓
wallet.agent.cash-in       ✗     ✗        ✓      ✗        ✗     ✗          ✓    ✓
admin.users.view           ✗     ✗        ✗      ✓        ✓     ✓          ✓    ✓
admin.users.suspend        ✗     ✗        ✗      ✗        ✗     ✓          ✓    ✓
admin.loans.approve        ✗     ✗        ✗      ✗        ✓     ✗          ✓    ✓
admin.compliance.aml       ✗     ✗        ✗      ✗        ✗     ✓          ✗    ✓
admin.reports.export       ✗     ✗        ✗      ✗        ✗     ✓          ✓    ✓
```

## Device Trust

### Device Fingerprint (40+ Signals)
```
Hardware: Device model, manufacturer, screen size, RAM, storage
OS: OS version, build number, security patch level, kernel version
Network: IP, carrier, WiFi SSID (hashed), connection type
Behavior: Typing speed, swipe patterns, app usage times
Security: Root/jailbreak status, developer mode, mock location
Biometric: Face ID enrolled, fingerprint enrolled, last verification
```

### Device Trust Scoring
```
Score 80-100: Trusted — Normal access
Score 50-79: Known — Step-up authentication
Score 0-49: Unknown — Full authentication, limit sensitive actions
Score < 0: Blocked — Security event, notify user
```

## Encryption Standards
```
Data at Rest:
  PII: AES-256-GCM with per-field keys (KMS)
  Financial data: AES-256-GCM with per-tenant keys
  Passwords: Argon2id (memory=64MB, iterations=3)
  PINs: bcrypt (cost=12) + pepper
  Session tokens: SHA-256 hashed in database

Data in Transit:
  External: TLS 1.3 (min), HSTS enabled
  Internal (service-to-service): mTLS (Istio)
  Database: TLS 1.3
  Redis: TLS + password

Key Rotation:
  KMS master keys: annually
  Data encryption keys: quarterly
  JWT signing keys: monthly
  API keys: on request / annually
```

## Audit Logging
Every security-relevant event must be logged:
```
Who: user_id, user_type, ip, device_id, session_id
What: action, resource_type, resource_id, changes (diff)
When: timestamp (microsecond precision)
Where: service_name, endpoint, geo_location
How: user_agent, device_fingerprint, risk_score
Why: permission_checked, rule_evaluated, policy_name
```

## Incident Response
| Severity | Response Time | Example | Actions |
|----------|--------------|---------|---------|
| SEV1 (Critical) | 15 min | Data breach, service outage | Incident call, contain, notify CISO, legal |
| SEV2 (High) | 1 hour | Suspicious login pattern | Investigate, block if confirmed |
| SEV3 (Medium) | 4 hours | Single account compromise | Reset credentials, notify user |
| SEV4 (Low) | 24 hours | Phishing attempt, policy violation | Log, monitor, user education |
