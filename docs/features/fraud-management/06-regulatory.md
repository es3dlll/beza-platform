# Regulatory Framework — Fraud Management in Syria

## Primary Legislation

### Syrian AML Law No. 31 of 2010 (قانون مكافحة غسل الأموال رقم 31)

The cornerstone of Syria's anti-money laundering and counter-terrorist financing (AML/CFT) framework. Key articles relevant to fraud management:

| Article | Requirement                                                 | Implication for Fraud System                                                        |
| ------- | ----------------------------------------------------------- | ----------------------------------------------------------------------------------- |
| Art. 4  | Financial institutions must monitor suspicious transactions | Fraud engine must flag AML-relevant patterns (structuring, rapid movement)          |
| Art. 5  | Reporting obligations to the AML Commission                 | SAR generation for fraud cases ≥ 1M SYP or any amount involving sanctioned entities |
| Art. 6  | Customer due diligence (CDD) requirements                   | KYC data must be part of fraud scoring                                              |
| Art. 7  | Record keeping (10 years)                                   | ALL fraud decisions, investigations, and case data stored for 10 years              |
| Art. 8  | Beneficial ownership identification                         | Fraud engine must analyze transaction chains to identify true parties               |
| Art. 10 | Prohibition of anonymous accounts                           | All accounts in fraud system must be linked to verified identity                    |
| Art. 12 | Reporting threshold (SYP 1,000,000 or equivalent)           | Automatic SAR generation at threshold                                               |
| Art. 15 | Freezing orders                                             | Fraud system must support immediate account freezing on CBS/AML Commission order    |

### Central Bank of Syria Regulations

| Regulation                         | Requirement                                              | Fraud System Impact                                                    |
| ---------------------------------- | -------------------------------------------------------- | ---------------------------------------------------------------------- |
| CBS Decision No. 17/2021           | Electronic payment service provider licensing conditions | Fraud prevention system is a licensing requirement                     |
| CBS Circular No. 5/2022            | Agent network management rules                           | Agent fraud monitoring mandatory                                       |
| CBS Decision No. 23/2023           | Cybersecurity requirements for PSPs                      | Fraud system must meet security standards (encryption, access control) |
| CBS Expected Credit Loss framework | IFRS 9 provisioning for financial assets                 | Fraud loss provisioning must be calculated and reported                |

## CBS Supervisory Expectations for Fraud Prevention

Based on CBS guidance (2020–2024) and international standards adapted for Syria:

### 1. Fraud Risk Management Framework

- Board-approved fraud policy
- Dedicated fraud management function
- Fraud risk assessment methodology
- Fraud prevention, detection, and response procedures

### 2. Transaction Monitoring System

- Real-time monitoring of all transactions
- Automated screening against fraud rules
- Scenario-based monitoring (Syria-specific patterns)
- Customer behavior profiling

### 3. Suspicious Transaction Reporting

- STR/SAR filing to AML Commission
- Reporting threshold: 1M SYP (or equivalent in foreign currency)
- Reporting timeline: within 24 hours of detection for material fraud
- Quarterly consolidated fraud report to CBS

### 4. Data Protection & Privacy

**Status:** Syria does not have a comprehensive data protection law as of 2025. However:

- CBS expects customer data confidentiality
- International partners (remittance corridors) require GDPR or equivalent compliance
- Best practice: implement GDPR-like protections for fraud data

**Requirements for Fraud System:**
| Requirement | Implementation |
|-------------|---------------|
| Data minimization | Only collect fraud-relevant data, not excessive personal data |
| Purpose limitation | Fraud data used ONLY for fraud prevention, not marketing |
| Access control | Role-based access to fraud case data |
| Data retention | 10 years per AML law; automated purge after retention period |
| User rights | Users can request fraud data (limited by fraud prevention necessity) |

## International Standards

### FATF Recommendations (Syria is a member)

| Recommendation                           | Relevance                  | Implementation                    |
| ---------------------------------------- | -------------------------- | --------------------------------- |
| R.1 – Risk-based approach                | Fraud risk assessment      | Fraud scoring tiers by risk level |
| R.10 – CDD                               | Customer due diligence     | KYC data fed into fraud engine    |
| R.11 – Record keeping                    | 10-year retention          | Fraud case archive                |
| R.16 – Wire transfers                    | Remittance fraud screening | Corridor-specific rules           |
| R.20 – Reporting suspicious transactions | SAR filing                 | Automated SAR generation          |
| R.24 – Transparency of legal persons     | Beneficial ownership       | Fraud engine entity resolution    |

### IFRS 9 – Expected Credit Loss

Fraud losses are a component of Expected Credit Loss (ECL) calculation:

| Component                   | Fraud Contribution                  |
| --------------------------- | ----------------------------------- |
| Probability of Default (PD) | Fraud-adjusted PD                   |
| Loss Given Default (LGD)    | Fraud recovery rate                 |
| Exposure at Default (EAD)   | Fraud exposure (transaction amount) |

**Fraud system must provide:** Historical fraud loss data segmented by product, region, user segment.

## Reporting Templates

### Fraud Incident Report (to CBS within 24 hours)

```
Fraud Incident Report
────────────────────
BEZA PSP – Fraud Case #[ID]

Date of Incident: YYYY-MM-DD HH:MM
Transaction Reference: [TXN_ID]
Fraud Type: [Account Takeover / SIM Swap / Agent Fraud / Other]
Amount: [SYP amount]
Status: [Blocked / Under Investigation / Confirmed]
Customer Involvement: [Customer-initiated / Unauthorized]
Action Taken: [Transaction blocked / Account frozen / Funds reversed]
Law Enforcement Notified: [Yes/No]
```

### Quarterly Fraud Statistics Report (to CBS)

```
Quarterly Fraud Report – [Q1/Q2/Q3/Q4] [YEAR]
BEZA PSP – Confidential

1. Summary
   • Total transactions: [N]
   • Total transaction value: [SYP]
   • Fraud cases: [N]
   • Fraud value: [SYP]
   • Fraud rate: [%]

2. Fraud by Type
   • Account Takeover: [N] cases / [SYP]
   • SIM Swap: [N] cases / [SYP]
   • Agent Fraud: [N] cases / [SYP]
   • Phishing/Social Engineering: [N] cases / [SYP]
   • Other: [N] cases / [SYP]

3. Recovery
   • Amount recovered: [SYP]
   • Recovery rate: [%]

4. System Performance
   • Transactions screened: [N]
   • Fraud alerts generated: [N]
   • False positives: [N]
   • False positive rate: [%]
   • Average decision time: [ms]

5. Regulatory Reporting
   • SARs filed: [N]
   • Law enforcement referrals: [N]
   • Pending investigations: [N]
```

## Compliance Risk Matrix

| Regulatory Risk             | Likelihood | Impact                            | Mitigation                                        |
| --------------------------- | ---------- | --------------------------------- | ------------------------------------------------- |
| Late SAR filing             | Medium     | High (CBS fine)                   | Automated 24h SAR generation with alert if missed |
| Inadequate fraud monitoring | Low        | Very High (license risk)          | 100% transaction screening                        |
| Data privacy violation      | Medium     | Medium (reputation + CBS censure) | Access controls, data minimization                |
| Insufficient record keeping | Low        | Medium (CBS audit finding)        | Automated 10-year retention                       |
| IFRS 9 provisioning error   | Medium     | High (audit failure)              | Accurate fraud loss tracking                      |

## Implementation Checklist

- [ ] CBS licensing requirement: fraud prevention system operational
- [ ] AML Law 31/2010 compliance: automated SAR generation
- [ ] Record keeping: 10-year archive for ALL fraud decisions
- [ ] Reporting: quarterly fraud statistics template approved by CBS
- [ ] Data protection: fraud data handling policy documented
- [ ] Access controls: role-based access to fraud case management
- [ ] Audit trail: all fraud decisions logged with timestamp + user
- [ ] IFRS 9: fraud loss provisioning calculation documented
- [ ] SAR threshold: 1M SYP automatic reporting (or equivalent USD/EUR)
- [ ] Law enforcement: protocol for fraud referral to Syrian judicial authorities
