# Cards Compliance

## KYC Requirements for Cards
| KYC Level | Card Types Allowed | Verification Requirements |
|-----------|-------------------|--------------------------|
| 0 — Anonymous | None | SMS OTP only |
| 1 — Basic | None | Full name, national ID number |
| 2 — Standard | Virtual card | National ID photo + selfie + liveness |
| 3 — Enhanced | Virtual + Physical | Level 2 + proof of address, source of funds |

## AML Transaction Monitoring (Cards-Specific)

### Card Transaction Rules
```
Rule CAML-1: Card Loading Pattern
  - Multiple small loads to card from different sources in < 24h
  - Threshold: 3+ loads totaling < 500,000 SYP each from different wallets
  - Action: Flag for structuring review

Rule CAML-2: ATM Structuring
  - Multiple ATM withdrawals below reporting threshold (1,000,000 SYP)
  - Pattern: 3+ withdrawals within 2h across different ATMs
  - Action: Flag for manual review

Rule CAML-3: Cross-Border Card Use
  - Card used in 2+ countries in 24 hours (impossible travel)
  - Action: Decline + compliance notification

Rule CAML-4: High-Velocity Spending
  - > 10 transactions in 15 minutes on same card
  - Action: Auto-freeze + compliance review

Rule CAML-5: Merchant Category Monitoring
  - High-risk MCCs: Gambling (7800-7999), Money Transfer (4829), Crypto
  - Action: Decline high-risk MCCs for KYC Level 2, flag at Level 3

Rule CAML-6: Card-to-Card Transfers
  - Transferring funds between own cards (possible layering)
  - Threshold: > 3 transfers in 24h or > 2,000,000 SYP
  - Action: Flag for review
```

### Sanctions Screening for Cards
```php
class CardSanctionsScreener
{
    public function screenBeforeIssuance(User $user, Card $card): ScreeningResult
    {
        // Screen user against sanctions lists
        $userRisk = $this->screenEntity($user);

        if ($userRisk->isBlocked) {
            $card->status = CardStatus::CLOSED;
            $card->save();
            $this->eventService->emitComplianceAlert($card, 'sanctions_block_card');
            return ScreeningResult::blocked('User on sanctions list');
        }

        // Screen issuing BIN/card program
        if (in_array($card->bin, $this->sanctionsBinList)) {
            return ScreeningResult::blocked('BIN on restricted list');
        }

        return ScreeningResult::passed();
    }

    public function screenTransaction(CardTransaction $txn): ScreeningResult
    {
        // Screen merchant
        $merchantRisk = $this->screenMerchant($txn->merchantName, $txn->merchantCountry);

        if ($merchantRisk->isBlocked) {
            return ScreeningResult::blocked('Merchant sanctioned');
        }

        // Screen country
        if (in_array($txn->merchantCountry, $this->sanctionedCountries)) {
            return ScreeningResult::blocked('Transaction to sanctioned country');
        }

        return ScreeningResult::passed();
    }
}
```

## Reporting

### Regulatory Reports (Cards-Specific)
```
Report Name                            Frequency    Recipient
─────────────                           ─────────    ────────
Card Issuance Report                   Monthly      CBL
Card Transaction Summary               Monthly      CBL
ATM Withdrawal Report                   Weekly       CBL
International Card Usage Report        Weekly       CBL
Suspicious Card Transaction Report     Within 24h   CBL
Card Portfolio Summary                  Quarterly    CBL + BIN Sponsor
PCI-DSS Compliance Attestation         Annually     Acquiring Banks

STR Filing for Card Transactions:
  1. Compliance officer identifies suspicious card pattern
  2. Gather: transaction records, device fingerprints, merchant data
  3. Write STR narrative (Arabic + English)
  4. Submit through CBL portal
  5. Freeze card if needed
  6. Flag user for enhanced monitoring
  7. Keep records: 10 years (PCI-DSS requirement)
```

### Card Portfolio Metrics (Monthly)
```
Metric                            Target    Reporting
────────────────────               ──────    ────────
Cards Issued (Virtual)            100,000   Monthly
Cards Issued (Physical)           20,000    Monthly
Active Cards (30-day)             80%       Monthly
Card Transaction Volume           Varies    Monthly
Card Transaction Count            Varies    Monthly
Chargeback Rate                   < 0.5%    Monthly
Fraud Rate (by value)             < 0.1%    Monthly
Average Spend Per Card            Varies    Monthly
Card Replacement Rate             < 5%      Monthly
Card Closure Rate                 < 3%      Monthly
```
