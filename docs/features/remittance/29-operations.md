# Remittance Operations

## Operational Workflows

### User Support Scenarios

#### Scenario 1: "I sent money but recipient hasn't received it"
```
1. User contacts support via in-app chat or call center
2. Agent looks up by remittance reference (REM-XXXXX) or sender phone
3. Check remittance status:

   If status = "completed":
     → Check recipient wallet transaction history
     → If credited: "تم إيداع المبلغ في محفظة المستلم. يرجى التحقق من الرصيد"
     → If not showing: Clear cache, refresh, verify again

   If status = "processing" OR "fx_locked":
     → "العملية قيد التنفيذ، يرجى الانتظار"
     → If > 10 min: Escalate to engineering (check CFE + FX)

   If status = "failed":
     → "فشلت العملية وتم إرجاع المبلغ إلى محفظتك"
     → Verify funds returned: Check sender wallet balance
     → If funds not returned: Manual reversal via admin panel

   If status = "pending" AND FX lock expired:
     → "انتهت صلاحية سعر الصرف، يرجى إعادة المحاولة"
     → Cancel pending transfer, funds available immediately

4. For unregistered recipient:
   → "المستلم غير مسجل في Beza. تم إرسال رمز الاستلام عبر SMS"
   → Provide pickup code for agent cash-out
   → If code lost: Resend SMS or generate new code
```

#### Scenario 2: "FX rate was wrong / I was charged more"
```
1. Verify the locked rate and execution rate:
   SELECT fx_rate, fx_mid_market_rate, fx_lock_id FROM remittances WHERE id = 'rem_abc123';

2. Compare with rate at time of lock:
   SELECT * FROM fx_rate_logs WHERE lock_id = 'fx_lock_abc123';

3. If rate used ≠ locked rate:
   → Calculate difference: $500 × (12,400 - 12,300) = 50,000 SYP
   → If difference > 0.5%: Initiate partial refund
   → Refund: 50,000 SYP to sender wallet
   → Log: "FX_RATE_MISMATCH_REFUND"

4. If rate used = locked rate but user misunderstood:
   → Explain: "سعر الصرف كان 12,400 ل.س لكل دولار عند تثبيت السعر"
   → Show comparison with mid-market rate
   → Explain spread: "فارق السعر 1.5% وهو ضمن النطاق المعلن"

5. If user claims hidden fees:
   → Show fee breakdown from transaction
   → Reiterate: "جميع الرسوم معروضة قبل التأكيد"
   → Offer: "سأرسل لك فيديو توضيحي للعملية"

6. Escalate to finance if:
   - Systematic rate discrepancy (> 0.5% from mid-market + spread)
   - Multiple complaints about FX rates
   - Potential technical issue
```

#### Scenario 3: "My recurring transfer didn't execute"
```
1. Check recurring transfer status:
   SELECT * FROM recurring_transfers WHERE id = 7;
   → Status, next_execution_at, failed_count, last_executed_at

2. If status = "active" AND next_execution_at is past:
   → Check if execution was attempted:
     → SELECT * FROM remittances WHERE recurring_id = 7 ORDER BY created_at DESC LIMIT 3;
   → If no remittance found:
     → "فشل التنفيذ التلقائي. قد يكون الرصيد غير كافٍ"
     → Check sender wallet balance on execution date
     → If insufficient: "تمت محاولة 3 مرات. يرجى تعبئة الرصيف"

3. If status = "paused":
   → "تم إيقاف التحويل الدوري مؤقتاً"
   → Show pause date and reason
   → "هل ترغب في استئنافه؟"

4. If status = "cancelled":
   → "تم إلغاء التحويل الدوري"
   → Show cancellation date
   → If unexpected: Investigate who cancelled (user or system)

5. Manual trigger:
   → If user requests immediate execution:
   → Verify balance and limits
   → Admin: POST /api/v1/remittance/recurring/{id}/execute
   → Notify user of result
```

#### Scenario 4: "I sent to the wrong beneficiary"
```
1. Verify transaction is within 30-minute cancel window:
   SELECT created_at FROM remittances WHERE id = 'rem_abc123';
   → If < 30 min ago: Cancel immediately
     → Admin panel: Cancel transfer → funds returned immediately
     → "تم إلغاء التحويل وإرجاع المبلغ"

2. If > 30 min:
   → Cannot cancel automatically
   → Contact wrong recipient via registered phone
   → If recipient agrees: Initiate reversal
     → Requires: Recipient confirmation + compliance approval
     → Reverse via admin: "إرجاع التحويل"
     → Fee: If > $100, reversal fee may apply

3. If wrong recipient doesn't respond or refuses:
   → Open dispute case
   → Send formal request to recipient (via SMS + app notification)
   → If no response in 7 days: Escalate to compliance
   → Compliance reviews transaction and contacts recipient formally

4. Prevention:
   → Suggest: "يمكنك حفظ المستفيدين لمنع حدوث ذلك مستقبلاً"
   → Enable: Beneficiary confirmation popup for large amounts
```

### Daily Operations Checklist
```
☐ 07:00 CET — Check Grafana dashboard (errors, latency, queue depth)
☐ 07:30 — Review failed remittances from last 24h by corridor
☐ 08:00 — Check recurring transfer execution batch (runs at 08:00)
☐ 08:30 — Verify FX rates are updating (all active corridors)
☐ 09:00 — Check compliance queue (P0/P1 items first)
☐ 10:00 — Review corridor limits (daily usage vs max)
☐ 11:00 — Check correspondent bank settlement confirmations
☐ 12:00 — Verify suspicious activity reports filed (if any)
☐ 14:00 — Review AI fraud model performance (false positives)
☐ 16:00 — Check beneficiary sanctions re-screening results
☐ 18:00 — Review support tickets: remittance-related issues
☐ 20:00 — Verify EOD settlement process started
☐ 22:00 — Confirm daily reports generated (per corridor)
☐ 23:00 — Review next day's recurring transfer schedule
```

### Recurring Transfer Operations (Cron Jobs)
```
Every 5 minutes:
  - Query: recurring_transfers WHERE status = 'active' AND next_execution_at <= NOW()
  - Process: For each due recurring transfer:
    1. Verify sender balance
    2. Get current FX rate
    3. Check limits
    4. Execute remittance
    5. Update next_execution_at
    6. Emit RecurringTransferExecuted event

Retry Policy:
  Attempt 1: At scheduled time
  Attempt 2: 1 hour later
  Attempt 3: 6 hours later
  Attempt 4: 24 hours later
  After 4 failures: Set status = 'paused', notify user
  
  Notification: Push + SMS + Email
  "تعذر تنفيذ التحويل الدوري. الرجاء التحقق من الرصيف"

Failed Count Reset:
  - On successful execution: failed_count = 0
  - On consecutive failures: failed_count++
  - At 4 failures: paused automatically
```

### Escalation Matrix
```
Level 1 (L1): Customer Support
  - Handle: Wrong beneficiary, FX rate explanation, failed txn, recurring inquiry
  - Escalation to L2: Disputes, reversals, compliance flags, >$10K transactions

Level 2 (L2): Operations Team
  - Handle: Manual reversals, recurring manual trigger, corridor maintenance,
            FX rate discrepancies, compliance queue review
  - Escalation to L3: FX engine issues, compliance service issues, 
                       correspondent bank issues

Level 3 (L3): Engineering
  - Handle: FX engine bugs, compliance screening bugs, database issues,
            infrastructure incidents, AI model issues
  - Escalation to L4: Architecture changes, security incidents, major outages

Level 4 (L4): CTO / Security Lead / Compliance Officer
  - Handle: Security breaches, regulatory escalations, sanctions-evasion attempts,
            correspondent bank relationship issues, major outages
```

### SLA Targets
```
First Response Time:
  P0 (Complete Outage): 5 min (automated alert)
  P1 (Corridor Outage): 15 min (agent acknowledges)
  P2 (FX / Recurring): 30 min (ticket assigned)
  P3 (Support Inquiry): 4 hours (ticket assigned)

Resolution Time:
  P0: 30 min
  P1: 4 hours
  P2: 8 hours
  P3: 72 hours

Support Volume:
  Expected: 300 tickets/day at 100K diaspora users
  Agent ratio: 1 agent per 15K active diaspora senders
  CSAT target: > 90%
  First contact resolution: > 75%
  Arabic support: 80% of staff fluent
  Multilingual support: English, German, Swedish, Turkish
```
