# Government Collections Compliance

## Regulatory Framework

### Syrian Laws & Regulations
| Law/Regulation | Relevance | Requirements |
|----------------|-----------|--------------|
| Syrian Tax Law No. 24 of 2003 | Tax collection | Authorised tax collection agents; daily settlement; detailed reporting |
| Electronic Transactions Law No. 18 of 2010 | Digital payments | Digital signature recognition; e-receipt validity; data protection |
| Central Bank of Syria Law No. 23 of 2002 | Payment services | Licensed payment service provider; settlement through CBS |
| Anti-Money Laundering Law No. 31 of 2003 | All financial transactions | KYC for payments >500K SYP; transaction monitoring; SAR filing |
| Personal Data Protection Law (draft) | Data privacy | Consent for data processing; data minimisation; right to deletion |
| E-Government Initiative Decree | Digital transformation | Compliance with national e-gov standards; interoperability guidelines |
| Ministry-specific regulations | Per ministry | Varies — reporting formats, settlement timing, reconciliation requirements |

### Licences Required
| Licence | Issuer | Status |
|---------|--------|--------|
| Payment Services Provider | Central Bank of Syria | Required — apply or partner with licensed PSP |
| Tax Collection Agent | Ministry of Finance | Required — direct agreement |
| Government Payment Aggregator | Ministry of Communications & Technology | Required — under e-government programme |
| Data Processor Registration | Ministry of Interior / NDMO | If personal data processed |

## Compliance Controls

### KYC & Transaction Monitoring
```php
class ComplianceService
{
    public function checkTransaction(GovernmentTransaction $txn): ComplianceResult
    {
        // 1. Amount threshold check
        if ($txn->total_charged > 500000) {
            // Enhanced KYC required — verify identity document
            if (!$txn->user->is_fully_verified) {
                return ComplianceResult::blocked('enhanced_kyc_required');
            }
        }

        // 2. Velocity check per biller reference
        $recentCount = GovernmentTransaction::where('biller_reference', $txn->biller_reference)
            ->where('created_at', '>=', now()->subHours(1))
            ->count();
        if ($recentCount > 5) {
            return ComplianceResult::flagForReview('high_velocity');
        }

        // 3. Sanctions / PEP screening (if applicable)
        if ($this->sanctionsService->match($txn->user)) {
            return ComplianceResult::blocked('sanctions_match');
        }

        // 4. Unusual amount pattern
        $avgAmount = GovernmentTransaction::where('user_id', $txn->user_id)
            ->where('service_type', $txn->service_type)
            ->average('amount');
        if ($avgAmount && $txn->amount > $avgAmount * 3) {
            return ComplianceResult::flagForReview('unusual_amount');
        }

        return ComplianceResult::approved();
    }
}
```

### AML Transaction Thresholds
| Threshold | Action |
|-----------|--------|
| > 500,000 SYP single payment | Enhanced KYC — identity document verification |
| > 2,000,000 SYP cumulative (30 days) | Manual review required before settlement |
| > 10,000,000 SYP cumulative (year) | SAR filing to Syrian AML Commission |
| > 5 transactions/hour per biller ref | Flag for review |
| > 50 transactions/day per user | Temporary account suspension + manual KYC |

### Data Retention
| Data Type | Retention | Rationale |
|-----------|-----------|-----------|
| Government transaction records | 7 years after transaction | Tax law, financial audit |
| Receipts (PDF + QR) | 7 years | Legal validity, dispute resolution |
| Audit logs | 7 years | Regulatory requirement |
| KYC documents | 5 years after account closure | AML law |
| Idempotency keys | 24 hours | Operational — then purged |
| Session data | Until session end | Operational |
| Guest payment identifiers | 90 days | Balance privacy vs operational need |

## Ministry-Specific Compliance

### Ministry of Finance (Tax)
- Daily reconciliation report in prescribed format
- Monthly aggregated tax collection report
- Quarterly audit: Beza must provide full transaction log
- No commingling of tax funds with other ministry funds in Beza settlement pool
- Separate settlement account per ministry

### Ministry of Interior (Passport/Civil)
- Real-time confirmation required (not batch)
- Biometric verification for passport payments >75,000 SYP (applies to urgent)
- Receipt must include passport applicant photo (from ministry API)
- Data sharing limited to payment confirmation only

### Ministry of Higher Education (Tuition)
- Compliance with university academic calendar for deadlines
- Refund policy: 100% refund within 14 days of payment (minus Beza fee)
- Student data privacy: Beza cannot retain student grades or academic records
- Integration with university bursary system for automatic status updates

## Audit Requirements

```php
// Annual external audit required by:
// 1. Central Bank of Syria — payment operations
// 2. Ministry of Finance — tax collection accuracy
// 3. External auditor (Big 4 or approved local firm)

// Quarterly internal audit:
// - Sample 100 transactions per ministry
// - Verify: amount, receipt, ministry_confirmed_at, settlement_status
// - Report: variance analysis, unreconciled items
// - Submit to board audit committee

// Monthly system audit:
// - Automated reconciliation completeness
// - Idempotency key effectiveness (no duplicates)
// - Rate limiting effectiveness (no abuse)
// - Backup and DR readiness
```
