# 27 — Risk Management

## 27.1 Risk Register

| ID | Risk | Likelihood | Impact | Score | Mitigation |
|---|---|---|---|---|---|
| R01 | **Currency volatility**: SYP devalues 50%+ between invoice creation and payment | High | High | 25 | Real-time FX; re-invoice if >30 days old; diaspora USD-priced invoices |
| R02 | **School defaults on refund** (takes payment but closes) | Medium | Critical | 20 | Escrow 10% of monthly collection per school; limit on payout until trust built; parent refund guarantee fund |
| R03 | **Internet outage** (Syrian govt shutdown) | Medium | High | 16 | Offline-capable receipt QR; SMS fallback for payment confirmation; mobile network backup |
| R04 | **Sanctions impact**: EU/US sanctions on Syria affect cross-border payments | Medium | Critical | 20 | Diaspora via non-sanctioned corridors; legal review per transaction; segregated USD/EUR flows |
| R05 | **Fraudulent school registration** | Medium | Critical | 20 | KYC with physical verification for P0; document scanning; MOE licence cross-check |
| R06 | **Parent disputes payment** (claims unauthorised) | Medium | High | 16 | Biometric + PIN required; full audit trail; 48h dispute window; refund from school's escrow |
| R07 | **Data breach** (student/parent PII leaked) | Low | Critical | 15 | Encryption at rest & transit; least-privilege access; quarterly penetration tests; bug bounty |
| R08 | **System overload during term start** (100x normal traffic) | High | High | 25 | Auto-scaling; load testing; queue-based processing; static dashboard caching |
| R09 | **School fee regulations change** (government freezes or caps fees) | Medium | Medium | 12 | Flexible fee engine; re-invoicing with regulatory override; legal monitoring |
| R10 | **Payment provider downtime** (Payment Core, bank API) | Medium | Critical | 20 | Multi-provider fallback; offline-cash recording; automatic retry with backoff |
| R11 | **Diaspora remittance regulatory freeze** | Low | High | 10 | Legal counsel on retainer; multiple remittance corridors |
| R12 | **Key person dependency** (team member leaving) | Medium | Medium | 12 | Cross-training; documentation; code ownership rotation |

## 27.2 Risk Scoring Matrix

```
Likelihood:
  Low (1) — Improbable (<1% chance/year)
  Medium (2) — Possible (1-20% chance/year)
  High (3) — Likely (>20% chance/year)

Impact:
  Low (1) — <100K SYP loss, minor inconvenience
  Medium (2) — 100K-5M SYP loss, operational disruption
  High (3) — 5M-50M SYP loss, reputational damage
  Critical (4) — >50M SYP loss, regulatory action, business continuity threat

Score = Likelihood × Impact
  < 8   → Green (acceptable)
  8-15  → Amber (requires mitigation)
  > 15  → Red (immediate action required)
```

## 27.3 Fraud Runbook

### Suspicious Activity Response
1. **Detection**: Automated rule fires → alert sent to fraud team (WhatsApp + email)
2. **Triage**: Fraud analyst reviews within 1 hour (business hours) or 4 hours (after hours)
3. **Action**: Freeze transaction, notify parent, notify school
4. **Resolution**: Investigation within 24 hours; refund if genuine error; escalate to authorities if confirmed fraud

### Rapid Response Contacts
| Role | Name | Phone | Escalation |
|---|---|---|---|
| Fraud Lead | TBD | +963-XXX-XXXX | CTO |
| Compliance Officer | TBD | +963-XXX-XXXX | CEO |
| CBS AML Contact | TBD | +963-XXX-XXXX | Compliance |

## 27.4 Business Continuity Plan

| Scenario | Procedure | Recovery Time |
|---|---|---|
| Payment service down | Parents use offline-cash recording; school records manually; Beza reconciles post-restore. | 4 hours |
| Database corrupted | Restore from PITR (WAL archive, max 1 hour data loss) | 1 hour |
| Full DC outage | DNS failover to DR site in Latakia | 30 min |
| WhatsApp API outage | Fallback to SMS only (channels downgraded) | Immediate |
| Bank settlement failure | Retry every 30 min; if failed after 24h, manual treasury ops | 24 hours |
| Staff unavailable | On-call rotation; secondary engineer familiar with education domain | 2 hours |

## 27.5 Insurance

- **Cyber liability insurance**: Covers data breach, ransomware, business interruption
- **Fidelity insurance**: Covers internal fraud by Beza employees
- **Errors & omissions**: Covers mistakes in fee calculations, settlement errors
