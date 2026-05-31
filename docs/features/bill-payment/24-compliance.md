# Bill Payment Compliance

## Regulatory Framework
- **Syria**: Central Bank of Syria (CBS) — payment aggregator license requirements
- **Syria**: Ministry of Communications & Technology — telecom bill aggregation oversight
- **Syria**: Ministry of Finance — government fee collection authorization
- **International**: FATF guidance on digital payment aggregators
- **Data Protection**: Syrian e-Transaction Law No. 12 of 2010

## KYC Requirements for Bill Payment
| Feature | KYC Level 0 | KYC Level 1 | KYC Level 2 |
|---------|------------|-------------|-------------|
| View billers | ✓ | ✓ | ✓ |
| Fetch bill | ✗ | ✓ | ✓ |
| Pay bill (per txn) | ✗ | 200,000 SYP max | 2,000,000 SYP max |
| Pay bill (monthly) | ✗ | 500,000 SYP | No limit |
| Schedule bill | ✗ | ✓ | ✓ |
| Auto-pay | ✗ | ✗ | ✓ |
| CSV bill payment | ✗ | ✗ | ✓ |

## AML Monitoring (Bill Payment Specific)

### Transaction Monitoring Rules
```
Rule BAML-1: Structuring Bill Payments
  - Multiple small bill payments to the same customer ID below 200,000 SYP
  - Pattern: 3+ payments < 200,000 SYP within 24h to same customer ID
  - Action: Flag for manual review

Rule BAML-2: High-Value Bill Payment
  - Single bill payment > 2,000,000 SYP
  - Action: Automatic hold + compliance notification

Rule BAML-3: Bill Payment Velocity
  - More than 20 bill payments in 1 hour from same user
  - Action: Flag for smurfing investigation

Rule BAML-4: Cross-Border Bill Payment
  - Diaspora user paying bills for multiple unrelated customer IDs
  - Action: Enhanced due diligence on sender

Rule BAML-5: Biller Arbitrage
  - Paying bill then immediately requesting refund (suspicious pattern)
  - Action: Flag + restrict refunds for 48h

Rule BAML-6: Sanctions Screening
  - Every bill payment customer name checked against:
    - UN Sanctions List (Syria-specific)
    - OFAC SDN List
    - EU Sanctions List
  - Customer name match > 85% → Manual review
  - Customer name match > 95% → Auto-block payment
```

### Sanctions Screening for Bill Payments
```php
class BillSanctionsScreener
{
    public function screen(string $customerName, string $customerId, string $billerType): ScreeningResult
    {
        // Check customer name against sanctions lists
        $nameScore = $this->fuzzyMatch->compare($customerName, $this->sanctionsList);

        // Check if customer ID appears on any watchlist (for government fees)
        $idScore = 0;
        if ($billerType === 'government_fees') {
            $idScore = in_array($customerId, $this->sanctionsIdList) ? 100 : 0;
        }

        $score = max($nameScore, $idScore);

        if ($score >= 95) {
            return ScreeningResult::blocked('Sanctions match');
        }

        if ($score >= 85) {
            return ScreeningResult::pendingReview('Name similarity threshold exceeded');
        }

        return ScreeningResult::passed();
    }
}
```

## Reporting

### Regulatory Reports (Bill Payment)
```
Report Name                        Frequency    Recipient
─────────────────────────          ─────────    ────────
Bill Payment Volume Report         Daily        CBS
Bill Payment Aggregator Report     Monthly      CBS + MCT
Government Fee Collection Report   Monthly      Ministry of Finance
Biller Settlement Report           Per batch    Each biller
Suspicious Bill Payment Report     Within 24h   CBS (if applicable)
Annual Bill Payment Audit          Annually     External auditor
```

### Data Retention
```
Bill Transactions: 10 years (CBS requirement)
Biller Connection Logs: 2 years (reduced after partitioning)
CSV Batch Files: 5 years (original files archived)
Receipts: 5 years (PDF storage)
Scheduled Bills: 2 years after cancellation
User Customer IDs: Retained with user account
```

### Compliance Queue (Admin Panel)
```
Bill Payment Priority Queue:
  P0 — Sanctions Match (auto-block) → Notify within 5 min
  P1 — Suspicious Payment Pattern → Review within 1 hour
  P2 — High-Value Payment (>2M SYP) → Review within 4 hours
  P3 — New Biller Onboarding → Review within 48 hours
  P4 — Refund Request → Review within 24 hours

Compliance Officer Actions:
  - Approve/block flagged payments
  - Release administrative holds
  - Generate STR narratives
  - Export transaction data for audit
```
