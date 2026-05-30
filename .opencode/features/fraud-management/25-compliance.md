# Compliance & CBS Reporting — Fraud Management

## Regulatory Compliance Framework

### Applicable Laws & Regulations

| Law/Regulation | Scope | Key Requirements |
|---------------|-------|-----------------|
| Syrian AML Law No. 31/2010 | AML/CFT | SAR filing at 1M SYP, CDD, record keeping 10 years |
| CBS Decision No. 17/2021 | PSP Licensing | Fraud prevention system mandatory for PSP license |
| CBS Circular No. 5/2022 | Agent Network | Agent fraud monitoring and reporting |
| CBS Decision No. 23/2023 | Cybersecurity | Fraud system security requirements |
| IFRS 9 | Financial Reporting | Expected credit loss provisioning for fraud |
| FATF Recommendations | International | Risk-based approach, SAR filing, beneficial ownership |

### CBS Reporting Obligations

#### 1. Suspicious Activity Report (SAR)

**Trigger Conditions:**
- Fraud amount ≥ 1,000,000 SYP (or equivalent USD 2,000/EUR 1,700)
- Fraud involving sanctioned persons/entities (UNSC, Syrian sanctions lists)
- Fraud linked to organized crime or terrorism financing indicators
- Any fraud amount if the victim is a politically exposed person (PEP)
- Repeated small-value fraud suggesting structuring (under 1M SYP but systematic)

**SAR Content Requirements:**
```
SAR Reference: SAR-2025-XXXXX
Date: YYYY-MM-DD
Reporting Entity: BEZA PSP

SECTION A: REPORTING ENTITY
  Entity Name: Beza Financial Services
  CBS License No: [Beza License Number]
  Reporting Officer: [Compliance Officer Name]
  Contact: [Phone/Email]

SECTION B: SUBJECT DETAILS
  Subject Name: [Full Name as per National ID]
  National ID No: [Syrian National ID]
  Phone Number: [Mobile]
  Wallet ID: [Beza Wallet ID]
  Address: [Governorate, City, District]
  Occupation: [If known]
  Employer: [If known]

SECTION C: TRANSACTION DETAILS
  Transaction Reference: [TXN ID]
  Date & Time: YYYY-MM-DD HH:MM (Syria time)
  Amount: [SYP amount] ([USD/EUR equivalent])
  Currency: SYP / USD / EUR
  Transaction Type: [P2P Transfer / Cash-out / Remittance / etc.]
  Sender: [Name, Wallet ID]
  Recipient: [Name, Wallet ID]
  Channel: [Mobile App / USSD / Agent POS / Web]
  Device: [Device fingerprint if available]
  Location: [GPS coordinates or city]

SECTION D: SUSPICIOUS INDICATORS
  □ Amount exceeds threshold (≥ 1M SYP)
  □ Transaction involves sanctioned entity
  □ Unusual transaction pattern
  □ Account recently dormant
  □ New device / location
  □ SIM recently changed
  □ Multiple transactions under threshold
  □ Transaction with high-risk jurisdiction
  □ Inconsistent with customer profile
  □ Customer unable to explain source of funds

SECTION E: NARRATIVE
  [Detailed description of why this transaction is suspicious.
   Include: how detected, patterns observed, customer behavior,
   any communication with customer, other related transactions.]

SECTION F: ATTACHMENTS
  □ Transaction history (last 30 days)
  □ KYC documents
  □ Communication records
  □ Device fingerprint data
  □ Location data
```

#### 2. Material Fraud Notification (Within 24 Hours)

**Trigger:** Any single fraud incident ≥ 5,000,000 SYP OR fraud involving systemic failure

**Notification Format:**
```
URGENT — MATERIAL FRAUD NOTIFICATION
To: CBS AML Division
From: BEZA PSP — Compliance Department
Date: [Current Date]

Incident Summary:
  Case ID: FR-2025-XXXXX
  Fraud Type: [Account Takeover / Agent Fraud / SIM Swap / etc.]
  Amount: [SYP amount]
  Detection Method: [Rule engine / ML model / User report / CBS alert]
  Status: [Blocked / Under Investigation / Confirmed / Recovered]

Impact Assessment:
  Customers Affected: [Number]
  Total Exposure: [SYP]
  Current Loss: [SYP] (if confirmed)
  Recovery Efforts: [Description]

Actions Taken:
  □ Transaction blocked
  □ Account(s) frozen
  □ Investigation initiated
  □ User notified
  □ Law enforcement notified
  □ Additional controls deployed

Required CBS Action:
  □ Confirmation of receipt
  □ Guidance on next steps
  □ Request for account freeze order
```

#### 3. Quarterly Fraud Statistics Report

(Full template in 06-regulatory.md — summary here)

**Content:**
- Transaction volume and value
- Fraud cases and value
- Fraud rate trend (compared to previous quarters)
- Fraud by type breakdown
- Regional breakdown
- System effectiveness metrics
- Recovery rate
- SAR filing summary
- False positive analysis

**Submission Timeline:**
- Q1: By April 15
- Q2: By July 15
- Q3: By October 15
- Q4: By January 15 (following year)

#### 4. Annual Fraud Audit

**CBS requires:** Annual independent review of fraud prevention system

**Audit scope:**
- Governance and oversight
- Risk assessment methodology
- Rule engine effectiveness
- ML model performance and bias
- Case management procedures
- Recovery processes
- CBS reporting compliance
- System security and access controls

## IFRS 9 Fraud Loss Provisioning

### Expected Credit Loss (ECL) Calculation

Fraud losses are a component of ECL under IFRS 9:

```
ECL = PD × LGD × EAD × (1 + discount_rate)^(-time)

Where:
PD (Probability of Default) = Fraud-adjusted PD
  = Base PD × (1 + fraud_rate_multiplier)
LGD (Loss Given Default) = 1 - recovery_rate  
EAD (Exposure at Default) = Transaction amount × fraud_exposure_factor

Fraud-specific contribution:
  fraud_ECL = fraud_PD × fraud_LGD × fraud_EAD
  fraud_PD = historical fraud rate (12-month rolling)
  fraud_LGD = 1 - historical recovery rate (12-month rolling)
  fraud_EAD = weighted average transaction amount
```

### Provision Calculation Example

```
Historical data (12 months):
  Total transactions: 10,000,000 txns
  Total value: 500,000,000,000 SYP
  Confirmed fraud cases: 1,842 cases
  Fraud value: 2,183,000 SYP
  Amount recovered: 458,430 SYP
  Recovery rate: 21%

Fraud ECL calculation:
  fraud_PD = 1,842 / 10,000,000 = 0.0001842 (0.01842%)
  fraud_LGD = 1 - 0.21 = 0.79
  fraud_EAD = 2,183,000 / 1,842 = 1,185 SYP (avg fraud amount)
  
  Monthly fraud provision = 0.0001842 × 0.79 × 1,185 × monthly_txn_count
  
  For 1M transactions/month:
  monthly_provision = 0.0001842 × 0.79 × 1,185 × 1,000,000
                    = 172,500 SYP
```

## Compliance Workflow

```
┌──────────┐   ┌──────────┐   ┌──────────┐   ┌──────────┐
│ FRAUD    │──▶│ FRAUD    │──▶│ CBS      │──▶│ ARCHIVE  │
│ DETECTED │   │ CONFIRMED│   │ REPORTED │   │ (10 yrs) │
└──────────┘   └──────────┘   └──────────┘   └──────────┘
                  │               │
                  ▼               ▼
           ┌────────────┐  ┌──────────────┐
           │ SANCTIONS  │  │ SAR          │
           │ SCREENING  │  │ GENERATION   │
           └────────────┘  │ (auto if >   │
                           │  1M SYP)    │
                           └──────────────┘
```

### Compliance Integration Points

| System | Integration | Data Shared |
|--------|-------------|-------------|
| CBS Portal | API or manual | SARs, fraud reports |
| AML Screening | Automated match | Suspect names checked against sanctions lists |
| Sanctions List | Real-time check | UNSC sanctions, Syrian sanctions, OFAC (remittance) |
| Law Enforcement | Email/Paper | Fraud referral, evidence package |
| External Audit | Read-only access | Case data, decision logs, model metrics |
| Internal Audit | Read-only access | Compliance with procedures, SAR timeliness |

## Role: Compliance Officer

### Compliance Officer Responsibilities for Fraud

- Review all confirmed fraud cases ≥ 1M SYP within 24h
- Approve and file SARs with CBS AML Commission
- Maintain CBS quarterly fraud reporting calendar
- Coordinate with CBS on material fraud incidents
- Ensure IFRS 9 provisioning data is accurate
- Conduct annual fraud risk assessment
- Maintain fraud prevention policy documentation
- Train fraud team on regulatory requirements
- Liaise with external auditors on fraud controls
- Report to board on fraud regulatory compliance

### Compliance Checklist

```
Daily:
☐ Review new confirmed fraud cases ≥ 500K SYP
☐ Check for any fraud requiring CBS notification within 24h

Weekly:
☐ Review pending SAR queue
☐ Verify IFRS 9 provision calculations
☐ Check for new regulatory circulars from CBS

Monthly:
☐ Prepare fraud loss data for finance (provisioning)
☐ Review fraud typology trends for regulatory relevance
☐ Update sanctions screening lists

Quarterly:
☐ Prepare CBS fraud statistics report
☐ File quarterly report with CBS
☐ Review system effectiveness against CBS expectations

Annually:
☐ Coordinate external fraud audit
☐ Update fraud risk assessment
☐ Review and update fraud prevention policy
☐ Submit annual fraud compliance report to board
☐ Review and test CBS reporting automation
```
