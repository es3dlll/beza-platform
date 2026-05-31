# Wallet Operations

## Operational Workflows

### User Support Scenarios

#### Scenario 1: "My transfer failed but money was deducted"
```
1. User contacts support via in-app chat or call center
2. Agent looks up user by phone number or transaction reference
3. Check transaction status:
   - If status = "failed" AND CFE shows hold released:
     → "تم إلغاء الحجز، المبلغ سيعود إلى محفظتك خلال دقائق"
   - If status = "completed" OR "pending":
     → Check CFE posting status
4. If money held in CFE but not posted:
   → Manual release via admin panel (requires 2FA approval)
5. If money posted but user doesn't see:
   → Clear cache → refresh balance → verify
6. Escalate to engineering if:
   - CFE hold/post mismatch
   - Database inconsistency
```

#### Scenario 2: "I sent to the wrong number"
```
1. Verify transaction status
2. If status = "pending" (still in 30-min hold window):
   → Cancel hold via admin → money returned
3. If status = "completed" < 1 hour ago:
   → Attempt reversal (only if both parties agree)
   → Contact recipient via registered phone
   → If recipient agrees: reverse transaction
4. If status = "completed" > 1 hour:
   → Cannot reverse
   → Guide user to contact recipient directly
   → Provide transaction proof
   → Flag for dispute resolution if needed
```

#### Scenario 3: "Someone accessed my wallet"
```
1. Immediately freeze wallet:
   → Admin panel: "تجميد المحفظة"
   → Change auth token → invalidate all sessions
2. Review last 10 transactions for unauthorized activity
3. If unauthorized transfers found:
   → Freeze recipient accounts
   → Attempt reversal (within 24h window)
   → Report to compliance (STR if > 1M SYP)
4. Help user:
   → Reset PIN
   → Register new device
   → Review security settings
5. Investigate:
   → How was PIN compromised?
   → Device fingerprint of unauthorized access
   → IP/geolocation analysis
```

### Daily Operations Checklist
```
☐ 08:00 — Check Grafana dashboard (errors, latency, queue depth)
☐ 08:30 — Review failed transactions from last 24h
☐ 09:00 — Approve pending KYC applications (if compliance officer)
☐ 10:00 — Daily reconciliation check (verify CFE matches DB)
☐ 12:00 — Check agent float levels (alert if any < 50K)
☐ 14:00 — Review fraud flags from overnight batch
☐ 16:00 — Check batch jobs completed (savings roundup, insights)
☐ 18:00 — Review support tickets: wallet-related issues
☐ 23:00 — Verify EOD settlement process started
☐ 23:30 — Confirm daily reports generated
```

### Escalation Matrix
```
Level 1 (L1): Customer Support
  - Handle: Wrong number, failed txn explanation, balance display
  - Escalation to L2: Technical issues, disputes, compliance flags

Level 2 (L2): Operations Team
  - Handle: Manual reversals, account freeze/unfreeze, KYC review
  - Escalation to L3: CFE issues, data inconsistency, fraud response

Level 3 (L3): Engineering
  - Handle: CFE bugs, database issues, infrastructure incidents
  - Escalation to L4: Architecture changes, security incidents

Level 4 (L4): CTO / Security Lead
  - Handle: Security breaches, regulatory escalations, major outages
```

### SLA Targets
```
First Response Time:
  P0: 5 min (automated alert)
  P1: 15 min (agent acknowledges)
  P2: 1 hour (ticket assigned)
  P3: 4 hours (ticket assigned)

Resolution Time:
  P0: 30 min
  P1: 4 hours
  P2: 24 hours
  P3: 72 hours

Support Volume:
  Expected: 500 tickets/day at 100K users
  Agent ratio: 1 agent per 10K active users
  CSAT target: > 90%
  First contact resolution: > 70%
```
