# الأمان وإدارة المخاطر — Security & Risk Management

## Threat Model

| Threat | Impact | Mitigation |
|--------|--------|------------|
| Application fraud (fake identity) | High | Multi-level KYC, biometric verification, device fingerprinting |
| Loan stacking (multiple apps across platforms) | High | Syria Credit Bureau integration (planned), internal cross-product limits |
| Synthetic identity | High | Liveness detection, document verification, wallet history ≥ 3 months |
| Guarantor collusion | Medium | Guarantor credit score check, relationship verification, max 3 guarantees/guarantor |
| Account takeover | High | 2FA for financial actions, device binding, transaction signing |
| Repayment evasion (disposable wallets) | Medium | Wallet minimum balance requirement, agent network monitoring |
| Insider fraud (admin abuse) | High | All admin actions logged, dual approval for high-value, quarterly audits |
| Data scraping | Medium | API rate limiting, PII encryption, field-level access control |

## Security Controls

### Authentication & Authorization
```
API:
  - JWT access tokens (15 min expiry)
  - Refresh tokens (7 days, rotating)
  - OAuth 2.0 scopes: financing:read, financing:write, financing:admin
  - Device binding for sensitive operations (disbursement, e-sign)

Mobile:
  - PIN/biometric for app access
  - Transaction signing for payments > SYP 100,000
  - Device fingerprint (hardware + software attestation)
```

### Data Protection
```yaml
encryption:
  at_rest: AES-256-GCM (PostgreSQL TDE or column-level)
  in_transit: TLS 1.3 minimum
  pii_fields: [national_id, phone, address, bank_account]
  encryption_key_rotation: 90 days

masking:
  in_logs: 
    phone: "+963********34"
    national_id: "**************78"
    name: "ليلى ***"
  in_admin_ui:
    full_details revealed only with "view sensitive" permission

audit:
  all financial transactions: immutable log, 7-year retention
  admin actions: who, what, when, IP, user-agent
  data access: query logging for PII access
```

### Fraud Detection Rules
```typescript
const fraudRules = [
  {
    id: 'F001',
    name: 'Multiple applications from same device',
    action: 'block',
    threshold: 3,
    window: '24h'
  },
  {
    id: 'F002',
    name: 'Amount significantly higher than historical income',
    action: 'manual_review',
    threshold: 10, // 10x average monthly wallet inflow
    window: '6months'
  },
  {
    id: 'F003',
    name: 'Guarantor linked to multiple active defaults',
    action: 'reject',
    threshold: 2,
    window: 'all'
  },
  {
    id: 'F004',
    name: 'Wallet created < 30 days before application',
    action: 'reject',
    threshold: 30,
    window: 'days'
  },
  {
    id: 'F005',
    name: 'New device + high amount + no previous activity',
    action: 'manual_review',
    threshold: 500000,
    window: 'immediate'
  },
  {
    id: 'F006',
    name: 'Rapid repayment from suspicious source',
    action: 'review',
    threshold: 500000, // lump sum from unknown source
    window: 'immediate'
  }
];
```

## Risk Limits & Controls

### Concentration Limits
| Risk Type | Limit | Action |
|-----------|-------|--------|
| Single user exposure | SYP 10,000,000 | Hard cap |
| Single product exposure | 40% of financing pool | Hard cap |
| Geographic concentration | 30% in any governorate | Monitor & adjust |
| Sector concentration (Micro) | 25% of Micro portfolio | Monitor & adjust |

### Capital Adequacy (CBS Requirement)
| Ratio | Requirement | Current Target |
|-------|-------------|----------------|
| Capital Adequacy Ratio (CAR) | ≥ 12% | 15% |
| Non-Performing Loan (NPL) Ratio | ≤ 5% | < 3% |
| Provision Coverage Ratio | ≥ 70% | 80% |
| Loan-to-Value (Murabaha assets) | ≤ 85% | 80% |

## Incident Response
```
Level 1 (Minor):
  - Single failed payment retry exhausted
  - Action: Auto-retry, log, notify support
  
Level 2 (Moderate):
  - Fraud pattern detected (3+ similar cases)
  - Action: Block applications, investigate, notify fraud team
  
Level 3 (Critical):
  - System breach or data leak
  - Action: Freeze all financing, notify compliance, regulatory report, user communication
```
