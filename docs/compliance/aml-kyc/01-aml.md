# Anti-Money Laundering (AML) Framework

> Single source of truth for AML rules across ALL Beza Platform features. Every feature that handles money references this document.

## Regulatory Basis

| Regulation | Jurisdiction | Scope |
|-----------|-------------|-------|
| Syrian AML Law No. 31 of 2010 | Syria | All transactions in SYP |
| FATF Recommendations 1-40 | International | Cross-border, correspondent banking |
| UN Security Council Resolutions | International | Sanctions screening |
| OFAC Sanctions | US | USD transactions, US persons |
| EU Sanctions | EU | EUR transactions, EU persons |

## Structuring Detection

### Definition
Structuring (smurfing): Splitting a large transaction into smaller amounts to avoid reporting thresholds.

### Detection Rules
```php
class StructuringDetectionRule implements AmlRule
{
    public function evaluate(Transaction $transaction): AmlResult
    {
        // Rule: Multiple transactions below threshold within time window
        $window = now()->subHours(24);
        $threshold = 1000000; // SYP

        $recentTransactions = Transaction::where('sender_wallet_id', $transaction->sender_wallet_id)
            ->where('created_at', '>=', $window)
            ->where('status', 'completed')
            ->get();

        $totalInWindow = $recentTransactions->sum('amount') + $transaction->amount;

        // Pattern 1: Total exceeds threshold within 24h
        if ($totalInWindow >= $threshold) {
            return AmlResult::flag('AML_STRUCT_001', 'Total transactions exceed 1M SYP in 24h window');
        }

        // Pattern 2: Multiples above 80% of threshold
        $nearThreshold = $recentTransactions->filter(fn($t) => $t->amount >= ($threshold * 0.8));
        if ($nearThreshold->count() >= 2 && now()->diffInHours($nearThreshold->first()->created_at) < 6) {
            return AmlResult::flag('AML_STRUCT_002', 'Multiple transactions near threshold within 6h');
        }

        // Pattern 3: Same amount pattern (e.g., multiple 950,000 SYP sends)
        $amounts = $recentTransactions->pluck('amount');
        $roundedCount = $amounts->filter(fn($a) => $a == intval($a / 100000) * 100000)->count();
        if ($roundedCount >= 3 && $totalInWindow >= ($threshold * 0.5)) {
            return AmlResult::flag('AML_STRUCT_003', 'Multiple rounded amounts suggesting structuring');
        }

        return AmlResult::pass();
    }
}
```

### Structuring Thresholds
| Currency | Threshold | Window | Action |
|----------|-----------|--------|--------|
| SYP | 1,000,000 | 24h | Flag + manual review |
| SYP | 5,000,000 | 7 days | Automatic STR filing |
| USD | 10,000 | 24h | Flag + manual review |
| USD | 50,000 | 7 days | Automatic STR filing |
| EUR | 10,000 | 24h | Flag + manual review |

## High-Value Thresholds

### Transaction Thresholds
| Threshold | SYP | USD | Action |
|-----------|-----|-----|--------|
| Reportable | 1,000,000 | 10,000 | AML review before processing |
| High-Risk | 5,000,000 | 50,000 | Enhanced Due Diligence + compliance approval |
| Critical | 25,000,000 | 250,000 | Board-level approval + STR |

### Monitoring Points
1. **Single transaction** exceeding reportable threshold
2. **Aggregate monthly volume** exceeding 10x reportable threshold
3. **First large transaction** for new user (account age < 90 days)
4. **Rapid velocity**: >3 high-value transactions in 7 days

## Rapid Movement Detection

### Rules
```php
class RapidMovementRule implements AmlRule
{
    public function evaluate(Transaction $transaction): AmlResult
    {
        // Rule: Funds moved through multiple wallets in short time
        // Example: A → B (5 min) → C (5 min) → D (5 min)

        $window = now()->subMinutes(30);

        $inboundChain = Transaction::where('recipient_wallet_id', $transaction->sender_wallet_id)
            ->where('created_at', '>=', $window)
            ->where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get();

        $outboundChain = Transaction::where('sender_wallet_id', $transaction->recipient_wallet_id)
            ->where('created_at', '>=', now()->subMinutes(30))
            ->where('status', 'pending')
            ->get();

        // Pattern: Rapid in-and-out (layering)
        if ($inboundChain->count() >= 2 && $outboundChain->count() >= 1) {
            $amountsMatch = abs(
                $inboundChain->sum('amount') - $outboundChain->sum('amount')
            ) < ($transaction->amount * 0.1);

            if ($amountsMatch) {
                return AmlResult::flag('AML_RAPID_001', 'Rapid in-and-out pattern detected - potential layering');
            }
        }

        return AmlResult::pass();
    }
}
```

### Detection Patterns
| Pattern | Description | Action |
|---------|-------------|--------|
| In-and-out | Fund received, same amount sent within 30min | Flag + review |
| Circular | A→B→C→A within 1 hour | Flag + review |
| Splitting | Single large → multiple smaller within 30min | Flag + review |
| Consolidation | Multiple small → single large within 30min | Flag + review |
| Rapid escalation | Transaction amount doubles each time in <1h | Flag + review |

## Cross-Border Monitoring

### Supported Corridors
| Corridor | Risk Level | Enhanced Due Diligence |
|----------|-----------|----------------------|
| SYP → SYP (domestic) | Low | No |
| SYP → USD (via agent) | Medium | Transaction > $2,000 |
| SYP → EUR | Medium | Transaction > $2,000 |
| SYP → TRY | High | All transactions |
| SYP → AED | Medium | Transaction > $5,000 |
| SYP → any other | High | All transactions + source of funds |

### Cross-Border Rules
1. **Source of funds** required for any cross-border transaction > $2,000
2. **Purpose of remittance** required for all cross-border transactions
3. **Recipient identity verification** required for cross-border first-time recipients
4. **Monthly limit**: $10,000 per user for cross-border (unless EDD approved)

## Sanctions Screening

### Screening Sources
| Source | Update Frequency | Implementation |
|--------|-----------------|----------------|
| UN Sanctions List | Daily | API + local cache |
| OFAC SDN List | Daily | OFAC API + CSV fallback |
| EU Consolidated List | Daily | EU API |
| Syrian Central Bank List | As published | Manual import |
| Local PEP List | Monthly | Manual import |

### Screening Points
```php
class SanctionsScreener
{
    public function screen(SanctionsSubject $subject): SanctionsResult
    {
        // Screened fields
        $fields = [
            'full_name' => $subject->fullName,
            'date_of_birth' => $subject->dateOfBirth,
            'phone' => $subject->phone,
            'email' => $subject->email,
            'address' => $subject->address,
            'national_id' => $subject->nationalId,
            'bank_account' => $subject->bankAccount,
        ];

        // Fuzzy matching
        foreach ($fields as $field => $value) {
            if (empty($value)) continue;

            $matches = SanctionsList::search($field, $value);

            foreach ($matches as $match) {
                $score = $this->calculateMatchScore($value, $match, $field);

                if ($score >= 95) {
                    return SanctionsResult::block('AML_SANCTIONS_001', "Exact match on {$field}");
                }

                if ($score >= 75) {
                    return SanctionsResult::flag('AML_SANCTIONS_002', "Potential match on {$field} (score: {$score})");
                }
            }
        }

        return SanctionsResult::pass();
    }

    private function calculateMatchScore(string $value, SanctionsEntry $entry, string $field): int
    {
        return match ($field) {
            'full_name' => $this->fuzzyNameMatch($value, $entry->name),
            'date_of_birth' => $value === $entry->dateOfBirth ? 100 : 0,
            'phone' => $this->phoneMatch($value, $entry->phone),
            'national_id' => $value === $entry->nationalId ? 100 : 0,
            'bank_account' => $value === $entry->bankAccount ? 100 : 0,
            default => 0,
        };
    }
}
```

### Match Thresholds
| Score | Action | Review |
|-------|--------|--------|
| 95-100 | Block transaction | Automatic block, compliance notified |
| 75-94 | Flag for review | Manual review by compliance team |
| 50-74 | Flag for monitoring | User risk score increased |
| <50 | Pass | No action |

### Screening Triggers
1. **User registration** — All new users screened against sanctions lists
2. **KYC submission** — Name on document screened
3. **Beneficiary addition** — Beneficiary name screened
4. **Cross-border transaction** — Both parties screened
5. **High-value transaction** — Both parties screened
6. **Agent onboarding** — Agent screened
7. **Merchant onboarding** — Merchant + beneficial owners screened

## Suspicious Transaction Report (STR) Filing

### STR Trigger Conditions
- Transaction flagged by any AML rule
- Customer cannot explain source of funds
- Customer provides false/inconsistent identity documents
- Transaction appears structured
- Transaction involves sanctioned person/entity
- Customer is a PEP (Politically Exposed Person)
- Customer requests unusual secrecy or refuses KYC

### STR Filing Procedure
```
1. Detection → AML rule flags transaction
2. Initial review (24h) → Compliance analyst reviews within 24 hours
3. Investigation (5 days) → Full investigation, gather documents
4. Compliance officer review (24h) → Approve or reject STR filing
5. STR submission → File with Syrian AML Authority (CAML)
6. Filing details:
   - Customer name, ID, address, occupation
   - Transaction details (amount, date, currency)
   - Suspicion rationale
   - Supporting documents
7. Internal record → Retain STR copy for 10 years
8. No tipping off → Customer NOT informed about STR filing
```

### STR Filing Timeframes
| Severity | Deadline | Format |
|----------|----------|--------|
| Urgent (ongoing crime) | 24 hours | Verbal notification + written within 3 days |
| Standard | 7 working days | Written STR form |
| Complex investigation | 15 working days | Written STR form with extension request |

### STR Data Retention
- STR records retained for **10 years** after filing
- STR database is air-gapped from production systems
- Access restricted to Compliance team only (3 people)
- Access logged and audited quarterly

## AML System Testing

### Required Tests per Release
1. Each structuring detection rule triggers correctly with fabricated test transactions
2. Sanctions screening correctly blocks exact-match names
3. Sanctions screening correctly flags fuzzy-match names (e.g., slight spelling variation)
4. Cross-border monitoring applies EDD correctly per corridor
5. Rapid movement rules detect in-and-out patterns
6. STR filing workflow completes end-to-end
7. No false positives on typical user behavior (salary deposits, regular bill payments)
8. Rate of false positives < 5% per month (monitored in dashboard)
