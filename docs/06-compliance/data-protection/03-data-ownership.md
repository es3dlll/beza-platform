# Data Ownership — Beza Platform

**Owner:** Compliance Officer | **Version:** 1.0 | **Last updated:** Platform launch

---

## Data Ownership Matrix

| Data Domain | Data Owner | Data Steward | Data Custodian | Description of Responsibilities |
|------------|-----------|-------------|---------------|-------------------------------|
| **Wallet Core** (balances, limits, tiers, wallet IDs, currency settings) | Product Manager — Wallet | Backend Engineering Team (Wallet Squad) | DevOps / SRE | **Owner:** Defines what data is collected, business rules for limits/tiers, approves schema changes, owns data quality. **Steward:** Implements data rules, maintains DB schemas, ensures data integrity, performs data quality checks. **Custodian:** Physical storage, backup/restore, encryption at rest, DB access controls, performance monitoring. |
| **Transactions** (P2P, agent cash-in/out, merchant payments, bill payments) | Operations Manager | Backend Engineering Team (Transactions Squad) | DevOps / SRE | **Owner:** Defines transaction data requirements for reconciliation, dispute resolution, reporting; approves retention policy. **Steward:** Ensures transaction atomicity, idempotency, correct fee calculation; maintains transaction logs. **Custodian:** DB clustering, transaction log archiving, disaster recovery. |
| **FX** (rates, conversion records, spread configuration) | Product Manager — FX & Remittance | Backend Engineering Team (FX Squad) | DevOps / SRE | **Owner:** Rate sourcing strategy (CBS feed, parallel market reference), spread/margin decisions, corridor prioritization. **Steward:** Rate feed integration, rate validation (circuit breaker for stale rates), conversion accuracy. **Custodian:** Rate cache infrastructure, FX transaction storage. |
| **Remittance** (corridors, sender data, reference numbers, AML screening) | Compliance Officer (shared ownership with Product) | Compliance Team & Backend Engineering | DevOps / SRE | **Owner:** Corridor compliance (sanctions, CBS), sender KYC requirements, AML screening rules. **Steward:** Sender data verification, sanctions list updates, SAR generation. **Custodian:** Secure remittance data storage, archive, encryption key management. |
| **Agent Network** (agent profiles, locations, commissions, float, performance) | Agent Network Manager | Backend Engineering Team (Agent Squad) | DevOps / SRE | **Owner:** Agent onboarding criteria, commission structure, performance KPIs, agent tiers. **Steward:** Agent data accuracy, duplicate detection, commission calculation engine. **Custodian:** Agent DB performance, GPS data storage, commission payout system. |
| **Merchant QR** (merchant profiles, QR codes, settlement data, transaction volumes) | Product Manager — Merchant | Backend Engineering Team (Merchant Squad) | DevOps / SRE | **Owner:** Merchant pricing (MDR), settlement terms, onboarding requirements. **Steward:** QR code generation and validation, settlement batch processing, merchant data quality. **Custodian:** Merchant DB, QR static file storage, settlement system infrastructure. |
| **Bill Payment** (biller data, biller APIs, payment records) | Product Manager — Bill Payment | Backend Engineering Team (Integration Squad) | DevOps / SRE | **Owner:** Biller prioritization, SLA negotiation with billers, fee structure. **Steward:** Biller API integration, bill inquiry/ payment routing, error handling. **Custodian:** Biller integration middleware, API gateway, payment journal storage. |
| **KYC / Identity** (national ID images, selfies, proof of address, verification status, tier data) | Compliance Officer | Compliance Team | DevOps / SRE | **Owner:** KYC policy, document requirements per tier, verification workflow design, acceptance criteria. **Steward:** Manual verification (if auto-verification fails), document quality checks, verification SLA tracking. **Custodian:** Secure document storage (separate encrypted bucket), access logging, retention enforcement, secure deletion. |
| **Authentication** (password hashes, PIN hashes, biometric templates, session tokens) | Security Lead | Security Team | DevOps / SRE | **Owner:** Authentication policy, password/PIN complexity rules, session management, biometric policy. **Steward:** Hash algorithm selection (bcrypt cost factor, Argon2 params), rate limiting rules, brute force detection. **Custodian:** HSM/Key management infrastructure, auth DB security, token signing keys. |
| **Device Fingerprint** (device ID, Android ID, IMEI, ICCID, IP, user-agent) | Security Lead (Fraud team) | Security Team (Fraud Squad) | DevOps / SRE | **Owner:** Device trust scoring, fraud rules, device binding policy. **Steward:** Device ID generation and deduplication, anomaly detection rules, device blacklist management. **Custodian:** Device fingerprint DB, fraud detection infrastructure, PI (protected information) storage. |
| **Compliance / AML** (SARs, flagged transactions, sanctions screening matches, audit trail) | Compliance Officer | Compliance Team | DevOps / SRE | **Owner:** AML policy, SAR filing process, CBS reporting, sanctions list management. **Steward:** Screening rule configuration, false positive tuning, SAR drafting and filing, CBS daily report generation. **Custodian:** Compliance DB (separate schema), WORM storage for audit logs, archive infrastructure. |
| **Support / Disputes** (tickets, chat messages, call recordings, dispute resolutions) | Customer Support Lead | Support Team | DevOps / SRE | **Owner:** Ticket categorization, SLA definitions, dispute resolution policy, customer communication. **Steward:** Ticket routing, dispute investigation, resolution documentation, customer feedback analysis. **Custodian:** Support system DB, chat message storage, call recording storage. |
| **Analytics / Behavioral** (feature usage, navigation, session metrics, funnel data) | Product Manager — Growth | AI / Data Team | DevOps / SRE | **Owner:** Analytics KPIs, tracking plan, feature instrumentation priorities, data retention for product decisions. **Steward:** Analytics SDK integration, data pipeline, dashboard creation, A/B test analysis. **Custodian:** Data warehouse (e.g., ClickHouse, BigQuery), ETL pipeline infrastructure. |
| **Marketing** (consent records, campaign data, opt-in/out preferences, SMS/email send logs) | Marketing Lead | Marketing Team | DevOps / SRE | **Owner:** Marketing campaigns, consent collection, channel selection, messaging content. **Steward:** Consent records management (GDPR-style — withdrawal, erasure), campaign performance tracking, audience segmentation. **Custodian:** Marketing automation platform, SMS gateway integration, consent DB. |
| **System / Audit Logs** (admin actions, config changes, compliance actions, server logs) | Security Lead | Security Team & SRE | DevOps / SRE | **Owner:** Audit requirements, log retention policy, integrity requirements (WORM). **Steward:** Log shipping, integrity verification, log query tooling. **Custodian:** Centralized logging infrastructure (e.g., ELK, Loki), WORM storage, archive management. |
| **Public Data** (agent locations, FX rates, merchant directory, help center) | Product Manager — Growth | Backend Engineering Team | DevOps / SRE | **Owner:** What data is made public, accuracy requirements, update frequency. **Steward:** Public API endpoint development, data freshness monitoring, rate limiting. **Custodian:** Public-facing API infrastructure, CDN caching, DDoS protection. |

---

## Data Lineage — Critical Flows

### Flow 1: Money Movement (P2P Transfer)

```
Sender initiates P2P transfer
    │
    ├──► Auth Service validates PIN / biometric
    │       Data: PIN hash (Auth DB) → match → token issued
    │
    ├──► Wallet Service checks sender balance
    │       Data: Wallet balance (Wallet DB)
    │       Retention: 10 years (part of transaction record)
    │
    ├──► Fraud Detection checks:
    │       ├── Device fingerprint (Device DB) — retention: 30d
    │       ├── IP geolocation (Session logs) — retention: 90d
    │       ├── Transaction velocity (in-memory cache) — retention: 24h
    │       └── Amount vs user history (Transaction DB) — retention: 10yr
    │
    ├──► Transaction Engine:
    │       ├── Debit sender wallet (Wallet DB)
    │       ├── Credit receiver wallet (Wallet DB)
    │       ├── Record transaction (Transaction DB) — retention: 10yr
    │       └── Apply fee: debit fee account (Wallet DB)
    │       Data owners: Wallet PM (wallet), Ops Manager (transaction)
    │
    ├──► Notification Service:
    │       ├── SMS to sender: "تم تحويل [amount] SYP إلى [receiver_name]"
    │       ├── SMS to receiver: "استلمت [amount] SYP من [sender_name]"
    │       └── Push notification (if app open)
    │       Data: phone numbers — PII, restricted
    │
    └──► Audit Log:
            └── Log transaction ID, amount, timestamp, success/fail
            Data owner: Security Lead — retention: 7 years
```

### Flow 2: Agent Cash-In

```
Customer gives cash to agent
    │
    ├──► Agent app: agent selects "Cash-In" → enters customer phone + amount
    │       Data logged: agent ID, customer phone, amount, timestamp
    │
    ├──► Agent Service:
    │       ├── Check agent float balance (Agent DB) — sufficient?
    │       ├── Debit agent float + Credit customer wallet
    │       └── Record agent transaction (Agent Transaction DB) — retention: 10yr
    │       Data owner: Agent Network Manager
    │
    ├──► Compliance check:
    │       ├── Amount > SYP 1,000,000? → Flag for AML review (Compliance DB)
    │       └── Customer Tier 1 daily limit exceeded? → Block
    │       Data owner: Compliance Officer
    │
    ├──► Customer: SMS notification "تم إيداع [amount] SYP في محفظتك. الرصيد: [balance]"
    │
    └──► Agent: SMS confirmation "تمت عملية الإيداع. عمولتك: [commission] SYP"
```

### Flow 3: KYC Verification

```
User submits KYC documents (Tier 1 → Tier 2 upgrade)
    │
    ├──► Document Upload Service:
    │       ├── National ID photo (front + back) → KYC Storage (encrypted)
    │       ├── Selfie → KYC Storage (encrypted)
    │       └── Proof of address (utility bill) → KYC Storage (encrypted)
    │       Data owner: Compliance Officer — retention: 10yr after closure
    │
    ├──► Auto-verification (if supported):
    │       ├── OCR: extract name, NID number, date of birth from ID
    │       ├── Face match: selfie vs ID photo (cosine similarity > 0.75)
    │       └── Check NID format against Syrian civil registry pattern (11 digits)
    │       Data: extracted fields → temporarily stored in verification DB
    │       Retention: 30 days after verification (then purged, retain only pass/fail)
    │
    ├──► Manual verification (if auto-fails):
    │       ├── Compliance agent reviews uploaded documents
    │       ├── Agent can request additional documents (message to user)
    │       └── Agent approves/rejects with reason
    │       Data: agent action logged in audit trail — retention: 7yr
    │
    ├──► User profile updated:
    │       ├── Tier changed to 2 (User DB)
    │       └── Limits increased (Wallet DB)
    │
    └──► Compliance record:
            ├── Verification completed timestamp
            ├── Verification method (auto/manual)
            └── Retention: 10yr after account closure
```

### Flow 4: Remittance Payout

```
Remittance partner (e.g., Lebanon corridor) sends payment request
    │
    ├──► Compliance Screening:
    │       ├── Sanctions check: sender name against OFAC/CBS/Syria sanctions list
    │       ├── Amount check: >$10,000 equivalent → enhanced due diligence
    │       └── Beneficiary relationship: verify declared relationship
    │       Data: screening result, transaction flagged or cleared
    │       Retention: 10yr (AML requirement)
    │
    ├──► FX Service (if SYP payout):
    │       ├── Apply CBS daily rate (or reference rate if parallel)
    │       ├── Convert USD → SYP
    │       └── Display rate to user: "سعر الصرف: 1 USD = [rate] SYP"
    │       Data: FX conversion record — retention: 10yr
    │
    ├──► Wallet Service:
    │       ├── Credit beneficiary wallet (SYP or USD wallet)
    │       └── Apply remittance fee (3-4%)
    │       Data owner: Ops Manager + Product FX
    │
    ├──► Notification:
    │       └── SMS: "تحويل وارد من [sender_name] بقيمة [amount]. الرقم المرجعي: [ref]"
    │
    └──► CBS Reporting:
            ├── Daily report: aggregate remittance volume, top corridors
            └── Data owner: Compliance Officer
```

### Flow 5: Merchant QR Payment

```
Customer scans merchant QR code
    │
    ├──► QR Service:
    │       ├── Decode QR → merchant ID
    │       └── Lookup merchant wallet address (Merchant DB)
    │       Data owner: Product Manager — Merchant
    │
    ├──► Wallet Service:
    │       ├── Customer confirms amount → debit customer wallet
    │       ├── Credit merchant wallet (immediate, available D+1 for withdrawal)
    │       └── Record merchant payment (Transaction DB)
    │       Data owner: Ops Manager
    │
    ├──► Merchant Service:
    │       ├── Increment merchant daily sales counter (Merchant DB)
    │       └── Update settlement batch (Settlement DB)
    │       Data owner: Product Manager — Merchant
    │
    ├──► Notification:
    │       ├── Customer SMS: "تم الدفع [amount] SYP إلى [merchant_name]"
    │       └── Merchant SMS: "استلمت [amount] SYP من [customer_name]"
    │
    └──► Settlement (D+1):
            ├── Settlement batch: aggregate all merchant transactions
            ├── Transfer from settlement pool to merchant wallet
            └── Data owner: Product Manager — Merchant / Ops Manager
```

---

## Ownership Governance

### RACI per Data Activity

| Activity | Data Owner | Data Steward | Data Custodian | Compliance Officer |
|----------|-----------|-------------|---------------|-------------------|
| Define data requirements | **A** / **R** | C | I | C |
| Design data schema | C | **R** | C | I |
| Implement data storage | I | C | **R** | I |
| Data quality monitoring | A | **R** | C | I |
| Access grant/revoke | A | C | **R** | C |
| Retention policy | **R** | C | C | A |
| Secure deletion | A | I | **R** | C |
| Breach notification | C | I | **R** | **A** |
| Audit response | C | C | C | **R** |
| Policy exceptions | **R** | I | C | A |

**R** = Responsible (does the work)  
**A** = Accountable (ultimately answerable)  
**C** = Consulted (consulted before decision)  
**I** = Informed (informed after decision)

### Data Ownership Review Cadence

| Review Type | Frequency | Participants | Output |
|-------------|-----------|-------------|--------|
| Ownership mapping review | Quarterly | All Data Owners + Compliance Officer | Updated ownership matrix |
| Data quality review | Monthly | Data Stewards + Ops Manager | Data quality scorecard |
| Access review | Quarterly | Data Custodians + Security Lead | Access rights report, revoked stale access |
| Retention compliance check | Semi-annual | Compliance Officer + Data Custodians | Retention audit report for CBS |
| Data lineage audit | Annual | Compliance Officer + Engineering Leads | Updated lineage documentation |
| Data Owner roster review | Bi-annual | CEO + Compliance Officer | Confirmed/updated Data Owner assignments |

### Escalation Path

1. **Data quality / integrity issue:** Data Steward → Data Owner → Engineering Manager
2. **Access / security issue:** Data Custodian → Security Lead → Data Owner
3. **Policy / compliance issue:** Data Steward → Compliance Officer → Data Owner
4. **Unresolved ownership dispute:** CEO (final arbitrator)

---

## Syria-Specific Ownership Considerations

- **CBS Liaison**: Compliance Officer is the single point of contact for all CBS data requests. No Data Owner communicates directly with CBS without Compliance Officer involvement.
- **Multi-currency Data**: Both SYP and USD data fall under the same ownership structure. However, USD transaction data is additionally subject to reporting to CBS Foreign Exchange department — the Product Manager — FX & Remittance is responsible for USD data accuracy for regulatory reporting.
- **Civil Registry Integration**: If/when Beza integrates with the Ministry of Interior civil registry for e-KYC, a separate Data Sharing Agreement must be signed. The Compliance Officer becomes Data Owner for any civil registry data received, with the MoI as the original Data Controller.
- **Sanctions Screening Vendor**: If a third-party sanctions screening service is used (e.g., World-Check, LexisNexis), the Compliance Officer is Data Owner for screening results, and the vendor is a Data Processor. A Data Processing Agreement (DPA) must be in place per Syria data protection requirements.
- **Humanitarian Data (V3)**: When processing humanitarian cash transfers (UNHCR, WFP), the humanitarian partner is typically the Data Controller, and Beza is the Data Processor. Ownership and processing boundaries must be defined per-program in a Data Processing Agreement.
