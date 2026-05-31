# Cards Operations

## Operational Workflows

### User Support Scenarios

#### Scenario 1: "Card was declined but I have balance"
```
1. User contacts support via in-app chat
2. Agent looks up card by last 4 digits or user phone
3. Check card status:
   - If status = "frozen": "بطاقتك مجمدة. قم بإلغاء التجميد من التطبيق"
   - If status = "active": proceed to next step
4. Check last declined transaction:
   - Decline reason: "limit_exceeded" → Check category limits
     → "تم تجاوز حد المعاملات الإلكترونية. يمكنك تعديل الحدود من التطبيق"
   - Decline reason: "insufficient_balance" → Check card wallet
     → "الرصيد غير كافٍ. قم بشحن البطاقة من محفظتك"
   - Decline reason: "fraud_declined" → Review fraud alert
     → "تم رفض المعاملة لأسباب أمنية. هل قمت بهذه المعاملة؟"
     → If yes: Mark as false positive → retry
     → If no: Freeze card + investigate
   - Decline reason: "invalid_cvv" → Check CVV generation
     → "الرمز السري (CVV) غير صحيح. قم بعرض الرمز من التطبيق"
   - Decline reason: "card_expired" → Check expiry
     → "البطاقة منتهية الصلاحية. قم بإصدار بطاقة جديدة"
5. If issue persists: Escalate to L2 operations
```

#### Scenario 2: "I found a charge I didn't make"
```
1. User reports unauthorized transaction
2. Agent looks up transaction by reference or card
3. Verify transaction details:
   - Amount, merchant, timestamp, location
   - IP address and device fingerprint of auth
4. If confirmed unauthorized:
   → Freeze card immediately (if not already frozen)
   → Create dispute record
   → Initiate chargeback (if merchant is international)
   → Reverse transaction (if within 24h and domestic)
   → Issue new card (same PAN, new expiry if virtual; replace if physical)
5. Notify user: "تم فتح نزاع على المعاملة. سيتم رد المبلغ خلال 5-7 أيام عمل"
6. File regulatory report if amount > 1,000,000 SYP
7. Monitor for additional unauthorized attempts
```

#### Scenario 3: "Physical card lost/stolen"
```
1. User reports lost card
2. Agent: Verify identity (SMS OTP + security questions)
3. Freeze card immediately:
   → Mark card status = "lost"
   → Revoke all Apple Pay / Google Pay tokens
   → Block further auths
4. Check for unauthorized transactions:
   - Any txns after reported loss time → dispute
   - ATM withdrawals → check CCTV with bank
5. Order replacement card:
   - Same PAN, BIN, last 4 — new expiry, new CVV
   - Delivery method: Agent pickup (preferred) or courier
   - Fee: 10,000 SYP (waived if first replacement)
6. Notify user: "تم تجميد البطاقة وطلب بديلة. ستكون جاهزة خلال 5 أيام عمل"
7. If unauthorized txns found: File police report + regulatory report
```

#### Scenario 4: "My Apple Pay / Google Pay stopped working"
```
1. User reports digital wallet issue
2. Check token status in card_tokens table:
   - If token revoked: "تم إلغاء رمز المحفظة الرقمية. أضف البطاقة مرة أخرى"
   - If token suspended: Check if card is frozen
   - If token expired: "انتهت صلاحية الرمز. أضف البطاقة مرة أخرى"
3. If card was replaced: Old tokens automatically revoked
   → "تم استبدال البطاقة. استخدم البطاقة الجديدة مع المحفظة"
4. If device changed: "المحفظة مرتبطة بجهاز مختلف. قم بإزالة البطاقة وإعادة إضافتها"
5. Escalate to TSP if issue persists
```

### Daily Operations Checklist
```
☐ 06:00 — Check settlement batch completed (local + international)
☐ 07:00 — Review rejected clearing transactions (auth-clearing mismatches)
☐ 08:00 — Check Grafana dashboard (auth rate, errors, latency)
☐ 08:30 — Review fraud declined transactions from last 24h (false positives?)
☐ 09:00 — Approve pending card replacements (L1 support)
☐ 10:00 — Check BIN range exhaustion levels (alert if < 10% remaining)
☐ 12:00 — Review high-value transactions flagged for compliance
☐ 14:00 — Check HSM status (certificate expiry, key rotation schedule)
☐ 16:00 — Review card portfolio report (issued, active, frozen, lost)
☐ 18:00 — Verify daily reconciliation (card wallet vs CFE)
☐ 22:00 — Check one-time card cleanup batch completed
☐ 23:00 — Verify EOD clearing file generation
```

### Escalation Matrix
```
Level 1 (L1): Customer Support
  - Handle: Card declined, wrong amount, lost card report
  - Escalation to L2: Unauthorized txns, technical issues, disputes

Level 2 (L2): Operations Team
  - Handle: Chargebacks, manual reversals, card replacement orders
  - Escalation to L3: Settlement mismatches, processor issues, fraud response

Level 3 (L3): Card Engineering
  - Handle: Card processor bugs, HSM issues, ISO 8583 connectivity
  - Escalation to L4: Switch outage, security incidents, BIN sponsor issues

Level 4 (L4): CTO / Security Lead
  - Handle: PCI-DSS breach, major fraud ring, regulatory escalations
```

### SLA Targets
```
First Response Time:
  P0: 5 min (automated alert)
  P1: 15 min (agent acknowledges)
  P2: 1 hour (ticket assigned)
  P3: 4 hours (ticket assigned)

Resolution Time:
  P0: 30 min (processor down)
  P1: 4 hours (high decline rate)
  P2: 24 hours (HSM degradation)
  P3: 72 hours (settlement delay)

Card Operations:
  Virtual card issuance: < 30 seconds
  Card freeze/unfreeze: < 5 seconds
  Physical card delivery: 5 business days (agent), 10 days (courier)
  PIN change: < 10 seconds
  Card replacement: 5 business days
  Chargeback processing: 5 business days
  Card transaction notification: < 3 seconds

Support Volume:
  Expected: 200 tickets/day at 100K card users
  Agent ratio: 1 agent per 15K active cards
  CSAT target: > 90%
  First contact resolution: > 75%
```
