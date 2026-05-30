# Backend Architecture

## Domain-Driven Design

The Humanitarian module follows Domain-Driven Design with these bounded contexts:

```
┌─────────────────────────────────────────────────────────┐
│                   Humanitarian Context                    │
│                                                          │
│  ┌─────────────────┐  ┌──────────────────────────┐      │
│  │  AidProgram      │  │  BeneficiaryVerification │      │
│  │  Service         │  │  Service                 │      │
│  │                  │  │                          │      │
│  │  - createProgram │  │  - biometricVerify       │      │
│  │  - defineRules   │  │  - checkSanctions        │      │
│  │  - enrollBenefs  │  │  - resolveMatch          │      │
│  │  - pause/resume  │  │  - validateID            │      │
│  └────────┬─────────┘  └────────────┬─────────────┘      │
│           │                         │                     │
│  ┌────────▼─────────┐  ┌────────────▼─────────────┐      │
│  │  Distribution    │  │  MPCSpendingMonitor       │      │
│  │  Service         │  │                           │      │
│  │                  │  │  - aggregateByCategory    │      │
│  │  - batchCredit   │  │  - calculateBurnRate      │      │
│  │  - issueVoucher  │  │  - detectAnomaly          │      │
│  │  - redeemVoucher │  │  - generateInsights       │      │
│  │  - settleMerch   │  └────────────┬─────────────┘      │
│  └────────┬─────────┘               │                     │
│           │                         │                     │
│  ┌────────▼─────────┐  ┌────────────▼─────────────┐      │
│  │  Reporting       │  │  ComplianceService        │      │
│  │  Service         │  │                           │      │
│  │                  │  │  - sanctionsScreening     │      │
│  │  - donorReport   │  │  - dueDiligenceCheck      │      │
│  │  - spendingRpt   │  │  - auditLogWrite          │      │
│  │  - reconciliation│  │  - fraudDetection         │      │
│  └──────────────────┘  └───────────────────────────┘      │
└─────────────────────────────────────────────────────────┘
```

## Service Specifications

### AidProgramService
- **Create Program:** Validates budget against NGO wallet balance, creates `aid_programs` record
- **Define Rules:** Stores distribution rules (amount, frequency, conditional triggers)
- **Enroll Beneficiaries:** Handles CSV upload, validates each row, triggers sanctions screening async
- **Get Program:** Returns enriched program data (budget used, beneficiary count, distribution count)

### BeneficiaryVerificationService
- **Biometric Verify at Agent:** Compares live fingerprint + face against stored templates; returns match score
- **UNHCR ID Check:** Validates UNHCR registration number against UNHCR API (proxy)
- **Fallback Verification:** When biometric fails, agent takes photo + collects manual ID info for manual verification
- **Idempotency:** Ensures each beneficiary can only be verified once per distribution cycle

### DistributionService
- **Batch Wallet Credit:** For MPC — credits each beneficiary's wallet in batches via core Wallet Service
- **Issue Voucher:** Generates unique 12-digit voucher codes with PIN, stores in `aid_vouchers`
- **Redeem Voucher:** Validates voucher code + PIN, checks expiry, checks remaining balance, deducts items
- **Settle Merchant:** Credits merchant wallet T+2 days after redemption, deducts from NGO program budget

### MPCSpendingMonitor
- **Aggregate by Category:** Groups merchant transactions by MCC code (food, rent, health, education, transport)
- **Calculate Burn Rate:** Tracks % of MPC spent within 7/14/30 days per program and governorate
- **Detect Anomaly:** Flags unusual spending patterns (e.g., single large withdrawal, cluster of same-merchant spending)
- **Generate Insights:** Produces natural-language summary for NGO program managers

### ReportingService
- **Donor Report:** Aggregates program-level data: total disbursed, beneficiaries reached, avg transfer, spending breakdown, photos/stories (with consent)
- **Spending Analysis Report:** Deep dive into category spending by governorate, household size
- **Reconciliation Report:** Tracks NGO funds sent → Beza → beneficiary → merchant: end-to-end proof of funds

### ComplianceService
- **Humanitarian Principles Checks:** Ensures program design respects neutrality, impartiality, do-no-harm
- **Sanctions Screening:** Benchmarks every beneficiary name against UN Consolidated List, EU CFSP list, OFAC SDN list
- **Fraud Detection:** Detects duplicate beneficiaries (same phone, same address, same biometric across multiple names)
- **Audit Trail:** Immutable event log for all humanitarian operations

## Event-Driven Communication

| Event | Producer | Consumer(s) |
|-------|----------|-------------|
| `beneficiary.enrolled` | AidProgramService | ComplianceService (sanctions check), NotificationService (SMS) |
| `sanctions.screening.complete` | ComplianceService | AidProgramService (approve/reject beneficiary) |
| `distribution.triggered` | DistributionService | WalletService (batch credit), NotificationService |
| `distribution.completed` | DistributionService | ReportingService (update stats), MPCSpendingMonitor |
| `voucher.redeemed` | DistributionService | MerchantService (settle), MPCSpendingMonitor |
| `voucher.settled` | MerchantService | ReportingService (reconciliation update) |
| `spending.anomaly.detected` | MPCSpendingMonitor | AlertService (notify program manager) |
