# Beza Fraud Prevention Platform — Vision

## Executive Vision

Beza's Fraud Prevention Platform is the trusted shield for Syria's digital financial ecosystem. As Syria transitions from a cash-dominated economy (estimated 85%+ cash-based) to digital payments, new fraud vectors emerge that threaten the trust foundation of the entire system. Our platform detects, prevents, and mitigates fraud in real-time across ALL Beza financial transactions — wallet transfers, agent cash-in/out, remittances, merchant payments, bill payments, and payroll disbursements.

## The Syria Context

Syria presents a uniquely challenging fraud landscape:

### Weak National ID Infrastructure
- The Syrian National ID system is fragmented. Many citizens lack biometric registration.
- ID forgery is a known issue. Fraudsters exploit gaps in KYC verification.
- Civil registry data damaged or inaccessible due to conflict (2011-present).
- Result: synthetic identity fraud is harder to detect but lower volume due to physical KYC at agents.

### Carrier-Grade NAT & Shared IPs
- Syria's telecom infrastructure (Syriatel, MTN) uses carrier-grade NAT extensively.
- Hundreds of users share a single public IP, especially in Damascus, Aleppo, and Homs.
- IP-based geolocation and device fingerprinting are unreliable.
- Fraud detection must rely on behavioral and device fingerprinting, not IP reputation alone.

### Cash-Heavy Economy, Digital Leapfrogging
- Most Syrians still transact in cash. Digital adoption is accelerating via mobile wallets.
- Users unfamiliar with digital security — weak PINs, sharing OTPs, using family phones.
- Fraudsters exploit this naivety through social engineering and phishing.

### Population Displacement & Fragmented Geography
- ~6.7 million internally displaced + ~6.8 million refugees abroad (pre-spring 2025 estimates, context-dependent).
- Remittances from diaspora create high-value, high-risk corridors.
- Displaced users transact from new locations frequently — location anomaly detection must account for legitimate displacement.

### Informal Economy
- ~65% of Syria's economy is informal (World Bank estimates).
- Many transactions lack provenance — casual wages, family support, peer-to-peer lending.
- Fraud detection must avoid false-positives on informal-but-legitimate flows.

### CBS Regulatory Pressure
- The Central Bank of Syria (CBS) mandates suspicious transaction reporting (STR/SAR).
- CBS expects licensed PSPs (like Beza) to have robust fraud prevention.
- Fraud loss provisioning under IFRS 9 is required for financial institutions.

## Strategic Vision

```
┌─────────────────────────────────────────────────────────────┐
│                  Beza Fraud Prevention Platform              │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  "Real-time fraud screening for every transaction,           │
│   across every Beza product, with Syria-specific rules,      │
│   machine learning, and a continuous feedback loop."         │
│                                                             │
│  Principles:                                                 │
│  • Security without friction — legitimate users never notice │
│  • Syria-first — rules trained on Syrian fraud patterns      │
│  • Continuous learning — false positives retrain models      │
│  • Transparent — clear appeals process for flagged users     │
│  • Compliant — full CBS reporting and IFRS 9 provisioning    │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

## Key Capabilities

1. **Real-time rule engine** — 100+ rules across fraud typologies
2. **Machine learning scoring** — Gradient Boosted Trees + Deep Learning
3. **Case management workbench** — Operations team investigation tools
4. **Agent fraud detection** — Pattern analysis on agent float, cash-in/out
5. **SIM swap detection** — Porting interception via telecom partnerships
6. **Behavioral profiling** — User transaction pattern baselines
7. **Graph analysis** — Mule account detection via shared device/network
8. **CBS reporting** — Automated SAR/STR generation
9. **Feedback loop** — Confirmed false positives improve model

## North Star Metrics

| Metric | Target | Current (Baseline) |
|--------|--------|-------------------|
| Fraud rate | < 0.1% of txn volume | ~0.8% (Syria industry est.) |
| False positive rate | < 3% | N/A |
| Real-time decision time | < 200ms | N/A |
| Fraud recovery rate | > 20% | < 5% (Syria est.) |
| Model accuracy (AUC) | > 0.95 | N/A |

## Timeline

- **Phase 1 (Q1)**: Rule engine + basic scoring → manual review queue
- **Phase 2 (Q2)**: ML model v1 + real-time blocking → automated actions
- **Phase 3 (Q3)**: Graph analysis + SIM swap detection → advanced fraud types
- **Phase 4 (Q4)**: Full automation + CBS reporting + model self-training

---

*"Trust is the currency of digital payments. We protect it."*
