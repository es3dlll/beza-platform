# Remittance Compliance

## KYC Requirements by Corridor

| KYC Level | Requirements | Verification Method | Corridors Available |
|-----------|-------------|-------------------|-------------------|
| 0 — Anonymous | Phone number only | SMS OTP | Local SYP P2P only |
| 1 — Basic | Full name, National ID | ID number validation | Local P2P, Turkey corridor |
| 2 — Standard | Passport/ID photo + Selfie + Proof of address | OCR + Liveness + Manual review | All diaspora corridors |
| 3 — Enhanced | Source of funds docs, Occupation, Tax ID | Document upload + video call | >$5K transfers, EDD cases |

### Diaspora-Specific KYC Requirements
```
EU Diaspora (Germany, Sweden, Netherlands):
  Required: Valid EU passport or national ID + proof of address (utility bill, bank statement)
  Additional for Level 3: Employment contract, tax returns, bank statements (3 months)
  Verification: Video call for first-time senders >$500

Turkey Diaspora:
  Required: Turkish residence permit or passport + Turkish phone number
  Additional: For >$1,000 sender → proof of legal status in Turkey

UAE/Saudi Diaspora:
  Required: Emirates ID / Iqama + passport + proof of address
  Additional: For >$2,000 → employment letter + salary certificate

US/Canada Diaspora:
  Required: Passport + SSN/SIN + proof of address
  Additional: For >$1,000 → W-8BEN tax form
  Regulatory: FinCEN registration as MSB, state-level licenses
```

## AML for Cross-Border Remittances

### Transaction Monitoring Rules
```
Rule AML-R1: Structuring Detection (Cross-Border)
  - Multiple remittances to same beneficiary below $10K (STR threshold)
  - Pattern: 3+ transfers < $10K within 24h to same beneficiary
  - Action: Flag for manual review, aggregate for STR filing

Rule AML-R2: High-Value Remittance
  - Single remittance > $10K USD equivalent
  - Action: Automatic STR filing + compliance hold + source of funds EDD

Rule AML-R3: Rapid Movement (Smurfing)
  - Sender: Funded from external source → immediate remittance → empty wallet
  - Pattern: Within 2 hours of funding
  - Action: Flag for smurfing investigation

Rule AML-R4: Split Beneficiary Structuring
  - Same sender to multiple beneficiaries, total > $10K within 24h
  - Action: Aggregate and flag

Rule AML-R5: Inconsistent Behavior
  - Sender historically sends $200-300/month, suddenly sends $5,000
  - Action: Flag for manual review + source of funds check

Rule AML-R6: High-Risk Corridor Monitoring
  - Corridors from countries with weak AML frameworks flagged for 100% review
  - Currently: No high-risk corridor active (all EU/US/TR have adequate AML)

Rule AML-R7: Recurring Transfer Monitoring
  - Sudden change in recurring frequency or amount flagged
  - Multiple recurring transfers to different beneficiaries from one sender
  - Action: Pattern analysis + source of funds verification
```

### STR Filing for >$10K Equivalent
```
Suspicious Transaction Report Process:

Threshold: $10,000 USD equivalent in a single transaction (or aggregate within 24h)
Filing Deadline: Within 24 hours of detection
Filed With: CBL (Central Bank of Lebanon) AML Unit

Step 1 — Detection (Automatic):
  Remittance amount > $10K or aggregate > $10K within 24h
  → Auto-hold on transfer
  → Compliance notification via Slack + email

Step 2 — Compliance Review (within 2 hours):
  - Gather transaction details: sender, beneficiary, IP, device, corridor
  - Review source of funds documents
  - Check transaction history for related transfers
  - Interview sender (via secure video call)

Step 3 — Decision:
  - If legitimate: release hold, file STR as informational
  - If suspicious: freeze funds, file STR, notify CBL
  - If blocked: freeze, file STR, notify CBL + relevant authorities

Step 4 — STR Filing:
  - Format: CBL STR Form (Arabic + English)
  - Fields: Sender details, recipient details, amount, currency, 
            corridor, date, reason for suspicion, supporting evidence
  - Evidence: Transaction logs, KYC documents, communication records
  - Submission: Via CBL secure portal

Step 5 — Post-Filing:
  - Flag user for enhanced monitoring (all future transfers reviewed)
  - Retain all records for 10 years per CBL requirements
  - No tipping off (do not inform user of STR filing)

STR Filing Example:
  Subject: Khalid Al-Hassan (ID: 42)
  Amount: $12,500 USD (single remittance to Syria)
  Corridor: USD_US->SYP
  Reason: Amount exceeds $10K threshold, sender salary €2,800/month 
          inconsistent with remittance size
  Decision: Released after source of funds verification (inheritance)
  Filing: Informational STR filed
```

### Sanctions Screening (UN Syria List, OFAC, EU)

#### Screening Implementation
```php
class SanctionsScreener
{
    private array $lists = [
        'un_syria' => 'https://scsanctions.un.org/syria.xml',
        'ofac_sdn' => 'https://www.treasury.gov/ofac/downloads/sdn.xml',
        'eu_syria' => 'https://sanctionsmap.eu/api/v1/syria',
        'cbl' => 'internal:cbl_sanctions_list',
    ];

    public function screenRemittance(Remittance $remittance): ScreeningResult
    {
        try {
            // Screen sender
            $senderResult = $this->screenEntity($remittance->sender);

            // Screen beneficiary
            $beneficiaryResult = $this->screenEntity($remittance->beneficiary);

            // Screen recipient (if different from beneficiary)
            $recipientResult = $this->screenEntity($remittance->recipient);

            // Aggregate results
            $allPassed = $senderResult->passed && $beneficiaryResult->passed && $recipientResult->passed;

            if (!$allPassed) {
                $remittance->status = RemittanceStatus::FAILED;
                $remittance->compliance_status = ComplianceStatus::BLOCKED;
                $remittance->save();

                $this->eventService->emitComplianceAlert($remittance, 'sanctions_block');
                $this->notificationService->alertComplianceTeam($remittance);

                return ScreeningResult::blocked('Entity found on sanctions list');
            }

            $remittance->compliance_status = ComplianceStatus::PASSED;
            $remittance->sanctions_screened_at = now();
            $remittance->save();

            return ScreeningResult::passed();

        } catch (\Exception $e) {
            // Fail open — log error but allow transaction
            Log::error('Sanctions screening failed', [
                'remittance_id' => $remittance->id,
                'error' => $e->getMessage(),
            ]);
            $remittance->compliance_status = ComplianceStatus::PASSED;
            $remittance->save();
            return ScreeningResult::passedWithWarning('Screening unavailable, manually review');
        }
    }

    private function screenEntity(User $user): EntityRisk
    {
        $maxScore = 0;

        foreach ($this->lists as $listName => $listSource) {
            $nameScore = $this->fuzzyMatch->compare(
                $user->fullName,
                $this->getSanctionsList($listName),
                ['threshold' => 0.85]
            );

            $idScore = $this->checkIdAgainstList($user->nationalId, $listName) ? 100 : 0;
            $phoneScore = $this->checkPhoneAgainstList($user->phone, $listName) ? 100 : 0;

            $score = max($nameScore, $idScore, $phoneScore);
            $maxScore = max($maxScore, $score);

            if ($score >= 95) {
                return new EntityRisk(
                    score: $score,
                    passed: false,
                    matchType: 'exact',
                    listName: $listName,
                );
            }
        }

        return new EntityRisk(
            score: $maxScore,
            passed: $maxScore < 85,
            matchType: $maxScore >= 85 ? 'fuzzy' : 'none',
        );
    }
}
```

#### Screening Thresholds
```
Score Thresholds:
  0-84:    Passed — no match found
  85-94:   Fuzzy match — manual review required within 1 hour
  95-100:  Exact match — auto-block, compliance notified immediately

Re-Screening:
  - Beneficiaries: every 30 days (cron job)
  - Senders: every 90 days (cron job)
  - Sanctions list updates: immediate re-screen of all active users
```

### Correspondent Banking Compliance

#### Due Diligence Requirements
```
Correspondent Bank Requirements (Deutsche Bank, JP Morgan):

  Onboarding Due Diligence:
    - Beza's AML/CTF policy and procedures
    - Beneficial ownership structure
    - Regulatory licenses (CBL, FinCEN, BaFin)
    - Audit reports (last 2 years)
    - List of senior management + compliance officer

  Ongoing Monitoring (per correspondent):
    - Monthly transaction reporting
    - Quarterly compliance calls
    - Annual AML audit
    - Immediate notification of:
      - Regulatory changes
      - Sanctions list updates
      - Suspicious activity

  Correspondent Bank Fees:
    EUR corridor (Deutsche Bank): 0.25% of volume + €25/month
    USD corridor (JP Morgan): 0.30% of volume + $50/month
    TRY corridor (Ziraat): 0.15% of volume + 50 TRY/month
```

### FATF Travel Rule for >$1K

#### Compliance Implementation
```
FATF Travel Rule Compliance:
  Threshold: >$1,000 USD equivalent (or €1,000 / 1,000,000 SYP)
  Data Required for Each Transfer:
    Sender: Full name, address, national ID/passport number, date of birth
    Beneficiary: Full name, phone number, relationship to sender

  Implementation:
    1. Capture travel rule data at transfer initiation
    2. Verify data matches KYC records
    3. Transmit to recipient's PSP (if applicable) via secure API
    4. For Syria: no PSP-to-PSP travel rule transmission (no counterparty MSB)
    5. Internal record-keeping only (store for 10 years)

  For transfers >$1,000:
    - Sender must have completed Level 2 KYC (ID + address)
    - Beneficiary must have verified phone number and name
    - Transfer is logged with all required travel rule fields
    - Report available for CBL inspection within 24h
```

## Reporting

### Regulatory Reports
```
Report Name                       Frequency    Recipient
─────────────────────────────     ─────────    ────────────────
Suspicious Transaction Report     Within 24h   CBL AML Unit
Large Cash Transaction Report     Daily        CBL
Cross-Border Remittance Summary   Weekly       CBL
Monthly Transaction Report        Monthly      CBL + Correspondent Banks
Annual AML/CTF Report             Annually     CBL
Travel Rule Data Export           On Request   CBL
Sanctions Screening Log           Monthly      Internal Audit
Corridor Performance Report       Monthly      Finance + Compliance

STR Filing (Cross-Border):
  1. Compliance officer identifies suspicious remittance
  2. Gather evidence: transaction + sender KYC + IP + device + comms
  3. Write STR narrative: Arabic + English (both required)
  4. Submit through CBL AML portal
  5. Flag sender + beneficiary for enhanced monitoring
  6. Keep records: 10 years
```

### Compliance Queue (Admin Panel)
```
Priority Queue:
  P0 — Sanctions Match (auto-block) → Notify compliance within 5 min
  P1 — Suspicious Pattern (auto-flag) → Review within 1 hour
  P2 — High Value (>$10K) → Review within 4 hours
  P3 — Source of Funds Required → Review within 24 hours
  P4 — New KYC Applications (diaspora) → Review within 24 hours
  P5 — Periodic Review (90-day re-screen) → Review within 7 days
```
