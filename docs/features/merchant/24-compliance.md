# Merchant Compliance

## KYC Requirements by Merchant Tier
| Tier | Requirements | Verification Method | Review Time |
|------|-------------|-------------------|-------------|
| Micro | Phone number, business name, location photo | SMS OTP + auto-image analysis | Instant (auto) |
| Small | + License photo (optional), 2 shop photos | OCR + manual spot-check | < 2 hours |
| Mid | + Valid license (mandatory), national ID | OCR + manual review | < 4 hours |
| Enterprise | + Commercial registry, tax number, bank account | Enhanced due diligence | < 24 hours |

## Screening Requirements

### Sanctions Screening
```
Every merchant is screened at registration against:
  - UN Sanctions List (Syria-specific designations)
  - OFAC SDN List (Syria-related entries)
  - EU Sanctions List (Syria)
  - Local CBL (Central Bank of Lebanon) list
  - Local Syrian Central Bank list (if accessible)

Screening fields:
  - Business name (fuzzy match, 3 languages: AR/EN/FR)
  - Owner name (from user profile)
  - Phone number
  - License number
  - Location (high-risk areas flagged)

Match thresholds:
  > 95% similarity: Auto-block, compliance alert, STR filed
  85-95% similarity: Manual review required within 1 hour
  < 85% similarity: Auto-approve with audit log
```

### Transaction Monitoring Rules
```
Rule AMLM-1: Structuring Detection (Merchant)
  - Multiple customers making payments < 1,000,000 SYP to same merchant
  - Pattern: 5+ payments to same merchant from different users, same device/IP
  - Action: Flag merchant for review, potential money muling

Rule AMLM-2: High-Value Merchant Transaction
  - Single transaction > 10,000,000 SYP
  - Action: Automatic hold + compliance notification

Rule AMLM-3: Rapid Settlement Withdrawal
  - Merchant receives settlement → immediately withdraws > 80% to external account
  - Pattern: Repeated over 5+ days
  - Action: Flag for enhanced due diligence

Rule AMLM-4: Round-Trip Transaction
  - Same customer pays → refunds → pays again
  - Pattern: Same customer refunded > 50% of transactions
  - Action: Flag merchant + customer for collusion investigation

Rule AMLM-5: High-Risk Business Types
  - Gold/jewelry, electronics, auto parts, currency exchange
  - Enhanced monitoring: 2x scrutiny on all transactions
  - Lower thresholds: Flag transactions > 500,000 SYP (vs 5,000,000 for grocery)

Rule AMLM-6: Geographic Anomaly
  - Merchant in Damascus receiving payments from multiple users in Idlib
  - Pattern: Payments from conflict zone areas
  - Action: Flag for review, possible illicit goods
```

### Merchant Blacklist
```php
class MerchantBlacklist
{
    public function check(Merchant $merchant): BlacklistResult
    {
        $matches = [];

        // Check against internal blacklist
        $internalMatch = BlacklistedEntity::where('type', 'merchant')
            ->where(function ($q) use ($merchant) {
                $q->where('value', $merchant->businessName)
                  ->orWhere('value', $merchant->userId)
                  ->orWhere('value', $merchant->licenseNumber);
            })->first();

        if ($internalMatch) {
            $matches[] = [
                'list' => 'internal',
                'field' => $internalMatch->field,
                'match' => $internalMatch->value,
                'reason' => $internalMatch->reason,
            ];
        }

        // Check against phone number blacklist
        if ($merchant->customerPhone) {
            $phoneMatch = BlacklistedPhone::where('phone', $merchant->customerPhone)->first();
            if ($phoneMatch) {
                $matches[] = ['list' => 'phone_blacklist', 'reason' => $phoneMatch->reason];
            }
        }

        return new BlacklistResult(
            isBlacklisted: count($matches) > 0,
            matches: $matches,
        );
    }
}
```

## Regulatory Reports
```
Report Name                    Frequency    Recipient
─────────────                   ─────────    ────────
Merchant Registration Report   Weekly       CBL (if required)
Suspicious Merchant Activity   Within 24h   CBL
Large Merchant Transaction     Daily        CBL
Monthly Merchant Volume Report Monthly      CBL
Merchant Portfolio Risk Review Quarterly    Internal

STR Filing for Merchant Activity:
  1. Compliance officer identifies suspicious merchant pattern
  2. Gather evidence (merchant profile, transaction history, customer data)
  3. Write STR narrative (Arabic + English, focusing on merchant activity)
  4. Submit through CBL portal or regulatory channel
  5. Flag merchant for enhanced monitoring
  6. If deemed high-risk: consider suspending merchant
  7. Keep records: 10 years
```

## Merchant Suspension Policy
```
Grounds for Immediate Suspension:
  1. Confirmed sanctions match
  2. Law enforcement request
  3. Evidence of fraudulent activity (chargebacks, fake transactions)
  4. Merchant engaged in illegal goods (weapons, drugs, stolen goods)
  5. POS terminal reported stolen
  6. Compromised account (unauthorized access)

Grounds for Warning → Suspension:
  1. Refund rate > 30% for 7 consecutive days
  2. Customer complaints > 5 in 30 days
  3. Suspicious transaction pattern (potential money laundering)
  4. Inactive > 90 days (dormant)
  5. Webhook endpoint consistently failing (> 50 failures/day)

Suspension Process:
  Step 1: Auto-flag → compliance review
  Step 2: Compliance officer reviews evidence
  Step 3: If confirmed: Suspend merchant
  Step 4: Notify merchant: "تم تعليق حسابك التجاري — يرجى التواصل مع الدعم"
  Step 5: Provide appeal process
  Step 6: If appealed: Review within 48 hours
  Step 7: If not appealed or upheld: Permanent closure after 30 days
```

## Data Retention
```
Retention Periods:
  Merchant profile: Lifetime of account + 10 years after closure
  Transaction records: 10 years (regulatory requirement)
  Settlement records: 10 years
  Webhook delivery logs: 1 year
  QR code images: Lifetime of merchant account
  Payment link records: 2 years after expiry
  POS pairing logs: Lifetime of terminal
  Refund records: 10 years
  Verification documents: 10 years after account closure
  Merchant communications: 5 years
```
