# Open Finance Compliance

## Developer KYC Requirements
| KYC Level | Requirements | Verification Method |
|-----------|-------------|-------------------|
| Basic | Email + phone + company name | Email OTP + SMS OTP |
| Standard | Company registration, owner ID | Document upload + OCR |
| Enhanced | Business license, proof of address | Manual review + background check |

## API Usage Monitoring & Compliance

### Transaction Monitoring Rules
```
Rule OF-AML-1: Structured API Payments
  - Multiple payments to same recipient < 1,000,000 SYP threshold
  - Pattern: 3+ payments within 1 hour
  - Action: Flag for review

Rule OF-AML-2: High-Volume Developer
  - Developer initiates > 100M SYP in payments per day
  - Action: Enhanced monitoring, daily compliance report

Rule OF-AML-3: Suspicious Bulk Patterns
  - Bulk payments to many new wallets (< 24h old)
  - Action: Pause bulk job, manual review

Rule OF-AML-4: Cross-Border Concern
  - Developer from non-Syrian entity sending to Syrian wallets
  - Action: Enhanced due diligence

Rule OF-AML-5: Sanctions Screening
  - Every API payment recipient checked against sanctions lists
  - UN Sanctions List (Syria-specific)
  - OFAC SDN List
  - EU Sanctions List
  - Match >= 85%: Manual review
  - Match >= 95%: Auto-block
```

## Developer Onboarding Compliance
```
1. Registration:
   - Collect: email, company name, phone, website
   - Verify: email OTP + SMS OTP
   - Tier: free (sandbox only)

2. KYC Submission:
   - Upload: company registration certificate
   - Upload: owner/operator national ID
   - Verify: automated document check

3. KYC Approval:
   - Manual review by compliance officer
   - Background check (sanctions, PEP)
   - Approve/reject within 48 hours

4. Production Access:
   - KYC approved → production API keys enabled
   - Gradual rate limit increase (30-day ramp-up)
   - Monthly usage review for first 3 months
```

## Data Protection & Privacy
```
Data Collected:
  - Developer: email, phone, company info, KYC docs
  - API Usage: request logs, IP addresses, user agents
  - Transaction: payment amounts, recipient phone numbers

Data Retention:
  - Developer records: duration of account + 5 years
  - API usage logs: 12 months (hot), 5 years (cold archive)
  - KYC documents: duration + 10 years (regulatory)
  - Webhook payloads: 30 days

Data Access Controls:
  - Developers access only their own data (tenant isolation)
  - Beza staff: role-based access (admin, support, compliance)
  - All access logged and auditable
```

## Regulatory Reports
```
Report Name                   Frequency    Recipient
─────────────                  ─────────    ────────
API Payment Volume Report     Weekly       Internal
Developer Onboarding Report   Monthly      Internal
Large API Transaction Report  Daily        CBL (if > threshold)
Suspicious API Activity       Within 24h   CBL
Data Access Audit             Quarterly    Internal
```
