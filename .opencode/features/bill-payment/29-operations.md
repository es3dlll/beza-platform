# Bill Payment Operations

## Operational Workflows

### User Support Scenarios

#### Scenario 1: "I paid but the biller says unpaid"
```
1. User contacts support via in-app chat
2. Agent looks up by phone number or Beza reference
3. Check transaction status:
   - If status = "paid" AND biller_reference exists:
     → Verify with biller via status_check API
     → If biller confirms: "تم الدفع — يرجى الانتظار 24 ساعة لتحديث نظام المزوّد"
     → Provide receipt PDF as proof
   - If status = "paid" BUT biller_reference is empty:
     → Beza debited wallet but biller didn't confirm
     → Check biller_connection_logs for pay operation
     → If connection logged: retry confirmPayment with biller
     → If connection NOT logged (unlikely): payment may have failed
       → Check CFE status: if money held, release hold + refund
   - If status = "pending":
     → Payment still processing
     → Check queue: has it been picked up?
     → If stuck > 30 min: manually process or cancel
4. Escalate to engineering if biller API mismatch
```

#### Scenario 2: "Wrong amount was charged"
```
1. Verify the bill amount that was fetched:
   Check bill_transactions.bill_amount vs what user claims
2. Verify the biller's stated amount:
   Check biller_connection_logs response_body for fetch operation
3. If Beza overcharged:
   → Refund difference immediately
   → Transaction note: "تم إرجاع المبلغ الزائد"
4. If biller overcharged:
   → Contact biller for correction
   → Refund user while waiting for biller adjustment
5. If user misread:
   → Walk through bill breakdown (consumption, tax, late fees)
   → Confirm via receipt
```

#### Scenario 3: "Auto-pay didn't work — I got a late fee"
```
1. Check scheduled_bill record:
   - auto_pay_status: 'failed'
   - last_error: 'insufficient_balance'
   - auto_pay_failures: 3
2. Explain to user:
   "تمت محاولة الدفع التلقائي 3 مرات ولكن الرصيد لم يكن كافياً"
   "الرصيد المطلوب: 46,431 ل.س — الرصيد المتوفر: 30,000 ل.س"
3. Compensate (goodwill policy):
   - If auto-pay had been working for 3+ months:
     → Beza covers late fee (2,125 SYP) as one-time courtesy
   - If first time:
     → Waive Beza fee for this payment (224 SYP)
4. Help user:
   - Set up balance alert at 50,000 SYP
   - Suggest funding source (auto-fund from card or bank)
   - Recommend premium subscription (unlimited auto-pay + priority)
```

#### Scenario 4: "I entered the wrong customer ID and paid someone else's bill"
```
1. Verify payment was completed:
   - Check transaction: status = 'paid', biller confirmed
2. Explain:
   "تم دفع الفاتورة للرقم الذي أدخلته — لا يمكن استرداد المبلغ بعد تأكيد المزوّد"
3. Attempt to help:
   - Contact biller for potential reversal (rarely possible)
   - Provide proof of payment to user
   - Suggest user contact the actual account holder
4. If within 48 hours and biller supports reversal:
   → Initiate reversal with biller (may take 3-5 business days)
   → Fee: 5,000 SYP administrative fee
5. Prevention:
   - Remind user: "تأكد من رقم المشترك قبل الدفع"
   - Suggest saving customer IDs as favorites
```

### Daily Operations Checklist
```
☐ 06:00 — Check CSV batch processing (government fees)
  - Verify FTP pull completed
  - Verify parser ran without errors
  - Check: SELECT status, COUNT(*) FROM csv_batch_files WHERE created_at::date = CURRENT_DATE
☐ 07:00 — Review failed bill payments from last 24h
  - Check: SELECT failure_reason, COUNT(*) FROM bill_transactions WHERE created_at > NOW() - INTERVAL '24h' AND status = 'failed' GROUP BY failure_reason
☐ 08:00 — Check auto-pay batch results
  - Success rate > 90%? Any recurring failures?
  - Contact users whose auto-pay failed 3+ times
☐ 09:00 — Grafana dashboard review
  - Biller API health (latency, error rate)
  - Queue depth (bill-payment, auto-pay, csv-processing)
  - Transaction success rate by biller
☐ 10:00 — Daily settlement reconciliation
  - Match bill_transactions to ledger entries
  - Verify per-biller settlement totals
☐ 12:00 — Biller circuit breaker status check
  - Any circuits open? Any recent flapping?
  - Review manual overrides
☐ 14:00 — Support ticket review: bill payment issues
  - Pending refunds? Unresolved disputes?
☐ 16:00 — Check new biller onboarding progress
  - Any billers in maintenance or pending activation?
☐ 18:00 — Verify EOD batch jobs scheduled
  - Daily settlement calculation
  - Biller settlement transfers
  - Analytics ingestion
☐ 23:00 — Confirm daily reports generated
  - Bill payment volume report
  - Commission report
  - Settlement report
```

### Escalation Matrix
```
Level 1 (L1): Customer Support
  - Handle: Receipt not received, wrong amount displayed, payment pending > 5 min
  - Escalation to L2: Failed payment with wallet debited, biller API error, refund request

Level 2 (L2): Operations Team
  - Handle: Manual refunds, biller status change, CSV batch re-processing, circuit breaker reset
  - Escalation to L3: Biller API integration bug, database inconsistency, settlement failure

Level 3 (L3): Engineering
  - Handle: Biller integration bugs, CFE issues, queue failures, auto-pay pipeline bugs
  - Escalation to L4: Architecture changes, new biller integration, security incidents

Level 4 (L4): CTO / Product Lead
  - Handle: Biller contract disputes, major financial discrepancies, regulatory escalations
```

### SLA Targets
```
First Response Time:
  P0: 5 min (automated alert + engineer)
  P1: 15 min (engineer acknowledges)
  P2: 1 hour (ticket assigned)
  P3: 4 hours (ticket assigned)

Resolution Time:
  P0: 30 min
  P1: 4 hours
  P2: 24 hours
  P3: 72 hours

Biller-specific SLAs (outgoing to biller API):
  PEED: 99.5% uptime (monthly)
  Syriatel: 99.9% uptime (monthly)
  MTN: 99.9% uptime (monthly)
  Other API billers: 99.0% uptime
  CSV billers: Daily batch by 06:00

Support Volume:
  Expected: 200 tickets/day at 100K bill payment users
  Agent ratio: 1 agent per 15K active bill payment users
  CSAT target: > 90%
  First contact resolution: > 75%
```
