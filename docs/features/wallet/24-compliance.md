# Wallet Compliance

## KYC Requirements
| KYC Level | Requirements | Verification Method |
|-----------|-------------|-------------------|
| 0 — Anonymous | Phone number only | SMS OTP |
| 1 — Basic | Full name, National ID number | ID number format validation |
| 2 — Standard | National ID photo + Selfie | OCR + Liveness + Manual review |
| 3 — Enhanced | Proof of address, Source of funds, Occupation | Document upload + Manual review |

## AML Screening

### Transaction Monitoring Rules
```
Rule AML-1: Structuring Detection
  - Multiple transfers to same recipient below reporting threshold (1,000,000 SYP)
  - Pattern: 3+ transfers < 1,000,000 SYP within 24h to same recipient
  - Action: Flag for manual review

Rule AML-2: High-Value Transaction
  - Single transfer > 5,000,000 SYP
  - Action: Automatic hold + compliance notification

Rule AML-3: Rapid Movement
  - Wallet: Funded → multiple transfers → empty within 1 hour
  - Action: Flag for smurfing investigation

Rule AML-4: Cross-Border Pattern
  - Frequent small transfers from diaspora to multiple recipients
  - Action: Enhanced due diligence on sender

Rule AML-5: Sanctions Screening
  - Every transaction sender + recipient checked against:
    - UN Sanctions List (Syria-specific)
    - OFAC SDN List
    - EU Sanctions List
    - Local CBL Sanctions List
  - Match: ≥ 85% name similarity → Manual review
  - Match: ≥ 95% name similarity → Auto-block
```

### SANCTIONS SCREENING IMPLEMENTATION
```php
class SanctionsScreener
{
    public function screen(Transaction $transaction): ScreeningResult
    {
        $senderRisk = $this->screenEntity($transaction->sender);
        $recipientRisk = $this->screenEntity($transaction->recipient);

        if ($senderRisk->isBlocked || $recipientRisk->isBlocked) {
            $transaction->status = TransactionStatus::FAILED;
            $transaction->failure_reason = 'SANCTIONS_BLOCK';
            $transaction->save();

            $this->eventService->emitComplianceAlert($transaction, 'sanctions_block');
            $this->notificationService->alertComplianceTeam($transaction);

            return ScreeningResult::blocked('Entity found on sanctions list');
        }

        if ($senderRisk->score > 85 || $recipientRisk->score > 85) {
            return ScreeningResult::pendingReview('Name similarity exceeds threshold');
        }

        return ScreeningResult::passed();
    }

    private function screenEntity(User $user): EntityRisk
    {
        $nameScore = $this->fuzzyMatch->compare($user->fullName, $this->sanctionsList);
        $idScore = in_array($user->nationalId, $this->sanctionsIdList) ? 100 : 0;

        return new EntityRisk(
            score: max($nameScore, $idScore),
            isBlocked: $idScore === 100 || $nameScore >= 95,
        );
    }
}
```

## Reporting

### Regulatory Reports
```
Report Name                    Frequency    Recipient
─────────────                   ─────────    ────────
Suspicious Transaction Report  Within 24h   CBL (Central Bank)
Large Cash Transaction Report  Daily        CBL
Monthly Transaction Summary    Monthly      CBL
Annual AML Report              Annually     CBL
Cross-Border Transfer Report   Weekly       CBL

STR Filing (Simplified):
  1. Compliance officer identifies suspicious transaction
  2. Gather evidence (transaction, user, device, IP, location)
  3. Write STR narrative (Arabic + English)
  4. Submit through CBL portal
  5. Flag user for enhanced monitoring
  6. Keep records: 10 years
```

### Compliance Queue (Admin Panel)
```
Priority Queue:
  P0 — Sanctions Match (auto-block) → Notify within 5 min
  P1 — Suspicious Pattern (auto-flag) → Review within 1 hour
  P2 — High Value (>5M SYP) → Review within 4 hours
  P3 — New KYC Applications → Review within 24 hours
  P4 — Periodic Review → Review within 7 days
```
