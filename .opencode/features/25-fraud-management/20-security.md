# Security — Fraud Management Module

## Security Principles

1. **Defense in depth**: Multiple layers of security, no single point of failure
2. **Least privilege**: Roles have minimum required access
3. **Security by obscurity is not security**: Rules are secret but assume they will leak
4. **Adversarial awareness**: Fraudsters actively probe defenses
5. **Data protection**: Fraud data is sensitive — treat it as PII

## Adversarial ML Security

Fraudsters will systematically probe and attempt to reverse-engineer the fraud detection system. This is an active adversarial environment.

### Threat Model

| Attack | Description | Syria-Specific | Mitigation |
|--------|-------------|---------------|------------|
| **Probing** | Fraudsters send test transactions to determine rule thresholds | Low-value probing (50 SYP transactions) | Threshold randomization, honeypot rules |
| **Feature poisoning** | Fraudsters manipulate behavior to influence training data | Creating fake "normal" patterns | Outlier detection, robust training |
| **Evasion** | Crafting transactions to avoid detection (just below thresholds) | Amount thresholds (49,999 instead of 50,000) | Ensemble methods, soft thresholds |
| **Model extraction** | Reverse-engineering ML model via API queries | Limited (low volume) but growing | Rate limiting, query monitoring |
| **Data poisoning** | Injecting false fraud reports to corrupt training data | Fake false positive claims | Verification before feedback loop |
| **Insider threat** | Fraud team member abuses access | Higher risk in Syria (economic pressure) | All actions logged, dual control for critical ops |

### Feature Obfuscation

**What NOT to expose to users:**
- Exact risk score (show "medium" not "72/100")
- Which rules triggered (show "unusual activity" not "VEL-003")
- Feature weights (don't reveal amount counts more than device)
- Model confidence (don't show "75% probability of fraud")

**What to expose:**
- Transaction is "paused for security" (generic)
- "Verify your identity" (action-oriented)
- "Contact support if this wasn't you" (help-oriented)

### Model Rotation

| Strategy | Frequency | Benefit |
|----------|-----------|---------|
| Model version update | Every 24–48h | Fraudsters can't learn current model |
| Feature set rotation | Every 7 days | Different features used in scoring |
| Threshold randomization | Per-transaction ±5% | Can't probe exact thresholds |
| Ensemble switching | Every hour | Randomly select from 3 active models |

### Honeypot Rules

Deploy decoy rules that look exploitable but are monitored:

```
Honeypot Rule Example:
- Name: "AMT-GOLD-001"
- Visible trigger: "Amount = 99,999 SYP exactly" (looks exploitable)
- Actual behavior: Logs all transactions that avoid this exact amount
- Purpose: Detect fraudsters who probe and avoid "thresholds"
- Action: Flag all accounts that systematically avoid honeypot rules
```

## Access Control

### Role-Based Access Control (RBAC)

| Role | Permissions | Description |
|------|------------|-------------|
| **fraud_viewer** | Read dashboard, read cases | View-only access |
| **fraud_analyst** | View + investigate cases, mark false positives | Day-to-day ops |
| **fraud_senior** | Analyst + confirm fraud, freeze accounts | Experienced analysts |
| **fraud_manager** | Senior + rule management, model management | Team lead |
| **fraud_admin** | Manager + user management, audit log | System admin |
| **compliance** | View + SAR filing, CBS reporting | Compliance team |
| **data_scientist** | View + model management, training | ML team |

### Dual Control Requirements

| Action | Required Approvals |
|--------|-------------------|
| Freeze a user account | 1 fraud analyst → approved by senior |
| Confirm fraud > 1M SYP | 1 senior analyst + compliance officer |
| Deploy new ML model | Data scientist + fraud manager |
| Disable a rule | Fraud manager (logged) |
| Restore incorrectly frozen account | Senior analyst + manager |
| Access raw ML training data | Data scientist + security officer |

### API Authentication

| Endpoint | Auth Method | Rate Limit |
|----------|------------|------------|
| POST /screen (internal) | Internal API key + HMAC | 10,000 req/min |
| GET /cases | JWT (ops team) | 100 req/min |
| POST /cases/{id}/decision | JWT + signature | 30 req/min |
| GET /reports | JWT (compliance) | 20 req/min |
| Admin endpoints | JWT + IP whitelist | 10 req/min |

## Rules Secrecy

### Rule Storage

- Rules stored encrypted at rest (AES-256)
- Rule conditions encrypted in database
- Rule source code accessible only to fraud_admin + data_scientist
- Rule evaluation happens server-side only (never client-side)
- Rule configurations NOT returned in any API response (even to internal)
- Rule audit log: who viewed/modified each rule

### Rule Deployment Security

```
┌──────────┐    ┌──────────┐    ┌──────────┐
│ Dev      │───▶│ Staging  │───▶│ Prod     │
│ (data    │    │ (anonym. │    │ (encrypt │
│  scientist│   │  data)   │    │  rules)  │
└──────────┘    └──────────┘    └──────────┘
     │               │               │
     │ Rule: unenc.  │ Rule: encrypt │ Rule: AES-256
     │ Config: plain │ Config: env   │ Config: vault
     └───────────────┴───────────────┘
```

## Data Security

### Sensitive Data Classification

| Data Class | Examples | Storage Requirement |
|------------|----------|---------------------|
| Fraud case data | Investigation notes, evidence, user statements | Encrypted at rest (AES-256) |
| Transaction data | Amount, sender, recipient, device | Encrypted at rest in DB (columns) |
| ML features | Engineered features (not raw data) | Encrypted at rest |
| ML model | Model weights, feature importance | Access controlled, encrypted |
| Audit logs | All state transitions, decisions | Append-only, tamper-proof |
| User PII | National ID, phone, address | Only via KYC module (not stored in fraud) |

### Data Minimization

- Fraud module stores ONLY fraud-relevant data (not full user profiles)
- KYC data accessed via reference, not copied
- ML features are aggregated/anonymized
- Raw transaction data purged after 90 days (only features retained)

## Audit Trail

| Event | Logged | Retention |
|-------|--------|-----------|
| Every fraud decision | Who, what, when, result | 10 years |
| Case state change | From, to, who, why | 10 years |
| Rule modification | Before, after, who | 10 years |
| Model deployment | Version, who, metrics | 10 years |
| User appeal | Appeal + resolution | 5 years |
| Access to sensitive data | Who, what, when | 2 years |
| Failed login attempts | IP, user, timestamp | 90 days |

## Incident Response

### Security Incident Severity

| Level | Description | Response Time |
|-------|-------------|---------------|
| SEV-1 | Fraud rules leaked publicly | Immediate (15 min) |
| SEV-2 | Unauthorized access to fraud data | < 1 hour |
| SEV-3 | Suspicious access pattern detected | < 4 hours |
| SEV-4 | Security policy violation | < 24 hours |

### Breach Response Plan

```
1. DETECT: Automated alert or manual report
2. CONTAIN: Revoke access, rotate keys, block IPs (within 15 min)
3. ASSESS: Determine scope, data accessed, fraud cases affected
4. REMEDIATE: Patch vulnerability, reset credentials
5. NOTIFY: Affected users (within 72h), CBS (if required)
6. REVIEW: Post-mortem, update security measures
```

## Syria-Specific Security Considerations

| Concern | Implication | Mitigation |
|---------|-------------|------------|
| Economic pressure on employees | Insider threat risk higher | All actions logged, dual control, salary loading for fraud team = premium |
| Limited cybersecurity talent | Harder to hire security staff | Automated security tooling, external audit |
| State-sponsored actor risk | Nation-state interest in financial data | Data localization, encryption, access restriction |
| Device security (users) | Many users on old/unpatched Android | Client-side fingerprinting, behavioral checks compensate |
| Syriatel/MTN as transit | Network-level interception possible | End-to-end encryption for mobile API |
| Social engineering risk | Fraudsters target Beza staff | Security awareness training, verification protocols |
