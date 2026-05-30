# Agent Network Compliance

## Agent KYC (Know Your Customer)

### Required Documents
| Document | Purpose | Verification |
|----------|---------|-------------|
| هوية شخصية سارية (National ID) | Identity verification | Match against government database (if available) |
| سجل تجاري (Shop Registration) | Business legitimacy | Verify registration number with Ministry of Commerce |
| فاتورة كهرباء/ماء حديثة (Utility Bill <3 months) | Proof of address | Match address on application |
| صورتان شخصيتان (2 Personal Photos) | Agent identification | Stored with KYC record |
| صورة واجهة المحل (Shop Front Photo) | Business location verification | Compare with Google Street View / field visit |
| صورة داخل المحل (Shop Interior Photo) | Business operation verification | Field officer visit |

### KYC Process
```
Step 1: Agent submits documents via field officer
Step 2: Documents uploaded to Beza KYC system
Step 3: Automated checks:
  - National ID format validation
  - Duplicate check (same ID/phone used for other agent)
  - Age verification (>18 years old)
  - Blacklist check (sanctions, PEP, previous fraud)
Step 4: Manual review by compliance officer (within 24h):
  - Document authenticity (visual inspection)
  - Photo matches applicant
  - Shop location exists and is operational
Step 5: Decision:
  - Approved → agent activated, SMS sent
  - Rejected → reason provided, appeal process available
  - Pending more info → agent notified of missing documents

KYC Validity: 3 months (quarterly re-KYC)
KYC Renewal: Automated reminder 30 days before expiry
  SMS: "يرجى تجديد مستندات KYC قبل تاريخ X"
  If not renewed by expiry: agent suspended until renewal
```

### KYC Levels (Agent)
| Level | Requirements | Limits Impact |
|-------|-------------|---------------|
| Basic | National ID + Utility bill | Bronze tier max |
| Standard | Basic + Shop registration + Field visit | Silver tier max |
| Enhanced | Standard + Bank reference + Two field visits/year | Gold tier max |
| Premium | Enhanced + Financial statement + Monthly visits | Platinum tier max |

## AML (Anti-Money Laundering)

### Cash Threshold Monitoring
```
Agent transactions are monitored in real-time:

1. Single Transaction Threshold > 1,000,000 SYP:
   - Automatically flagged for AML review
   - Transaction still completes (not blocked)
   - Compliance team notified within 5 minutes
   - Agent notified: "تم تسجيل المعاملة للتدقيق الروتيني"

2. Cumulative Daily Threshold > 3,000,000 SYP (per customer across all agents):
   - Transaction blocked if would exceed
   - Customer must provide additional ID at agent
   - Agent enters ID details on POS
   - If ID verification fails: transaction declined

3. Cumulative Monthly Threshold > 20,000,000 SYP (per agent):
   - Agent flagged for enhanced due diligence
   - Monthly review triggered
   - Report generated for regulatory submission

Threshold Table:
| Monitoring Rule | Threshold SYP | Action |
|-----------------|---------------|--------|
| Single cash-in/cash-out | > 1,000,000 | Flag for review |
| Daily customer cumulative | > 3,000,000 | Block + additional ID |
| Monthly agent cumulative | > 20,000,000 | Enhanced due diligence |
| Monthly agent cash-out only | > 10,000,000 | Report to financial intelligence |
| Weekly net float change | > 5,000,000 | Investigation |
| Structuring detection | Multiple < 1M txns in short period | Pattern alert |
```

### Suspicious Activity Reporting (SAR)
```
Automatic indicators (SAR triggers):
  1. Multiple cash transactions just below threshold (structuring)
  2. Rapid cash-in followed by immediate cash-out (layering)
  3. Agent with no prior activity suddenly processes large volumes
  4. Customer uses multiple agents in same day for separate transactions
  5. Agent processes transactions outside declared operating hours (>3 times)
  6. Customer refuses biometric verification without valid reason

SAR Process:
  1. System detects trigger → creates SAR case
  2. Compliance officer reviews within 24 hours
  3. If suspicious:
     a. Freeze agent float temporarily
     b. File SAR with Financial Intelligence Unit (FIU)
     c. Document in compliance system
     d. If confirmed: suspend agent, file formal SAR
  4. If false positive:
     a. Close SAR case with justification
     b. Update ML model to reduce false positives

SAR Retention: 10 years (regulatory requirement)
```

## Agent Due Diligence

### Initial Due Diligence
```
Performed before agent activation:
  - Identity verification (National ID)
  - Address verification (utility bill)
  - Business verification (shop registration)
  - Background check (court records if available)
  - Sanctions screening (UN, EU, OFAC lists)
  - PEP (Politically Exposed Person) check
  - No existing agent within 500m (market saturation prevention)
```

### Quarterly Reviews
```
Every 3 months:
  1. KYC document renewal
  2. Transaction pattern review:
     - Volume trends (any unusual spikes?)
     - Transaction types (cash-in vs cash-out ratio)
     - Customer complaints (any disputes?)
  3. Float reconciliation check
  4. Device security check (MDM compliance)
  5. Training refresh (if new policies/procedures)

Review outcome:
  - Pass → continue operations
  - Pass with notes → action items assigned
  - Fail → suspension pending resolution
```

### Enhanced Due Diligence (EDD)
```
Applied when:
  - Agent monthly volume > 20,000,000 SYP
  - Agent flagged for suspicious activity (even if false positive)
  - Agent operates in high-risk area (border region, conflict zone)
  - Agent has frequent float discrepancies
  - Agent has multiple customer complaints

EDD measures:
  - Monthly field visits (instead of quarterly)
  - Additional document verification
  - Source of float funding investigation
  - Customer interview sample (call 10 random customers)
  - Transaction pattern analysis by compliance officer
  - Reduced transaction limits during EDD period
```

## Data Privacy & Retention

### Data Retention Policy
| Data Type | Retention Period | Justification |
|-----------|-----------------|---------------|
| Agent KYC documents | 5 years after termination | Regulatory |
| Transaction records | 10 years | AML/FIU requirement |
| Communication logs | 3 years | Dispute resolution |
| Device certificates | Until decommissioned + 1 year | Security audit |
| Session logs | 90 days | Operational |
| Biometric data | Not stored (device-local only) | Privacy |

### Compliance Reporting
```
Regular regulatory reports:

1. Daily:
   - Transaction volume report (automated)
   - Threshold breach log (auto-generated)
   - Float discrepancy report (auto-generated)

2. Weekly:
   - Suspicious activity summary
   - Agent performance review highlights
   - New agent activations/deactivations

3. Monthly:
   - SAR log (if any filed)
   - Agent KYC compliance rate
   - AML monitoring report
   - Agent network growth report

4. Quarterly:
   - Agent due diligence completion report
   - Compliance audit findings
   - Regulatory filing preparation

5. Annually:
   - Full compliance audit
   - Policy effectiveness review
   - Regulatory filing
```
