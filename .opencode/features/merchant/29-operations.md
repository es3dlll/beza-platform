# Merchant Operations

## Operational Workflows

### Merchant Onboarding Flow
```
Step 1: Merchant downloads app → registers (auto, ~3 min)
Step 2: Auto-verification runs (instant for micro, async for small+)
Step 3: If auto-approved: Merchant gets QR code immediately → can start accepting payments
Step 4: If manual review needed: Queued for ops team
Step 5: Ops team reviews within SLA:
    - Micro/Small: < 2 hours
    - Mid/Mid+: < 4 hours
    - Enterprise: < 24 hours

Manual Review Process:
  1. Open admin panel → Pending Merchants queue
  2. Review business license (OCR result + human check)
  3. Review shop photos (verify real store, not stock photo)
  4. Check sanctions list (auto-checked, but verify)
  5. Approve/Reject with reason
  6. If rejected: Send rejection reason to merchant
  7. Merchant can re-submit corrected documents

Escalation:
  - Suspicious merchant → Flag for compliance review
  - Celebrity/sensitive business → Flag for C-level review
  - Bulk registrations from same IP → Flag for fraud investigation
```

### Merchant Support Scenarios

#### Scenario 1: "I didn't receive my settlement"
```
1. Merchant contacts support
2. Agent looks up merchant by phone or business name
3. Check settlement status for last 3 days:
   - If settlement is "pending": Check if cron job ran
     → Run: php artisan merchant:settlement --merchant-id={id}
   - If settlement is "processing" for > 2 hours: Check CFE logs
   - If settlement is "failed": Check failure_reason
     → "merchant_wallet_frozen": Unfreeze wallet, retry
     → "cfe_error": Retry settlement, escalate if persists
4. If settlement shows "completed" but merchant doesn't see:
   → Refresh merchant wallet balance
   → Check if settlement was sent to correct wallet
   → Verify CFE transfer reference
5. Escalate to engineering if:
   - CFE transfer not found
   - Settlement amount mismatch
   - Database inconsistency
```

#### Scenario 2: "My QR code isn't working"
```
1. Check QR code status:
   SELECT status, type, expires_at FROM merchant_qr_codes WHERE id = {qrId}
2. If status = "inactive" or "expired":
   → Regenerate QR code
   → Merchant downloads new QR from app
3. If QR image not loading (CDN issue):
   → Test CDN URL: curl -I {image_url}
   → If CDN down: Regenerate QR, upload to different CDN edge
   → Fallback: Serve QR directly from API temporarily
4. If QR is scannable but payment fails:
   → Check merchant account status
   → Check merchant transaction limits
   → Test payment manually via admin panel
5. If QR is physically damaged:
   → Guide merchant to app → redownload QR → print new copy
   → Offer to courier laminated QR (free for first replacement)
```

#### Scenario 3: "Customer says they paid but I didn't receive"
```
1. Check merchant_transactions for that amount + approximate time
2. If transaction found:
   → Check wallet_transactions for matching record
   → Verify CFE transfer completed
   → If all OK: Transaction was completed, merchant may have missed notification
     → Resend notification manually
     → Check merchant notification settings
3. If no transaction found:
   → Ask for customer's transaction reference
   → Look up by customer phone + amount
   → Check failed/pending transactions
4. If customer claims payment was deducted:
   → Check if hold was placed and then released (not posted)
   → If money held on customer side but not posted:
     → Release hold if stuck
     → Customer balance should return automatically
5. If all else fails:
   → Escalate to engineering for CFE investigation
   → Compensate merchant if Beza error (credit settlement)
```

#### Scenario 4: "I want to close my merchant account"
```
1. Verify merchant identity (SMS OTP + PIN)
2. Check pending settlements:
   - If any unsettled transactions → process final settlement
3. Check merchant balance:
   - If positive → Guide withdrawal to bank/wallet
   - If negative (unlikely) → Request payment
4. Process closure:
   - Admin panel: "إغلاق الحساب التجاري"
   - Deactivate all QR codes
   - Unpair all POS terminals
   - Cancel pending payment links
   - Freeze merchant wallet (for refunds within 90 days)
   - Close merchant record after 30 days (refund window)
5. Data retention: Keep records per compliance policy
6. Send confirmation: "تم إغلاق حسابك التجاري — شكراً لتعاونك مع Beza"
```

### Daily Operations Checklist
```
☐ 08:00 — Check Grafana: merchant error rates, TPV, active merchants
☐ 08:30 — Review pending merchant verifications (> 2 hours old)
☐ 09:00 — Check last night's settlement completion (all merchants?)
☐ 09:30 — Review failed settlements (retry or escalate)
☐ 10:00 — Check webhook delivery rates (alert if < 99%)
☐ 11:00 — Review fraud flags from overnight batch
☐ 12:00 — Check POS terminal health (any offline > 24h?)
☐ 14:00 — Review merchant support tickets (merchant-related)
☐ 16:00 — Check QR CDN health and latency
☐ 18:00 — Spot-check merchant registration quality (random sample)
☐ 23:00 — Verify settlement cron job started
☐ 23:30 — Confirm settlement job is progressing
```

### Weekly Operations
```
☐ Sunday — Review merchant churn report (last 7 days)
☐ Monday — Process merchant tier upgrades/downgrades
☐ Tuesday — Review dynamic MDR adjustments
☐ Wednesday — Test webhook delivery with random merchant sample
☐ Thursday — Review compliance flags (STR readiness)
☐ Friday — Backup verification: merchant data export
☐ Saturday — Capacity planning: QR storage, CDN bandwidth
```

### Escalation Matrix
```
Level 1 (L1): Merchant Support
  - Handle: QR not working, settlement questions, app issues
  - Escalation to L2: Account suspension, complex refunds, fraud flags

Level 2 (L2): Operations Team
  - Handle: Manual settlement, account freeze/unfreeze, tier changes
  - Escalation to L3: CFE issues, webhook infrastructure, POS cert problems

Level 3 (L3): Engineering
  - Handle: QR generation bugs, settlement engine issues, CDN problems
  - Escalation to L4: Security incidents, architecture changes

Level 4 (L4): CTO / Security Lead / Compliance
  - Handle: Merchant fraud rings, sanctions matches, major outages
```

### SLA Targets
```
First Response Time:
  P0: 5 min (automated alert)
  P1: 15 min (agent acknowledges)
  P2: 1 hour (ticket assigned)
  P3: 4 hours (ticket assigned)

Resolution Time:
  P0: 30 min (payment outage)
  P1: 4 hours (settlement failure)
  P2: 24 hours (webhook degradation)
  P3: 72 hours (dashboard slow)

Merchant Verification SLA:
  Auto-verification (Micro): Instant
  Manual verification (Small/Mid): < 2 hours
  Enterprise verification: < 24 hours

Settlement SLA:
  Batch start: 23:59 (daily)
  Batch complete: 00:30 (within 31 minutes)
  Merchant wallet credited: 01:00
  Report available: 01:30

Support Volume:
  Expected: 200 tickets/day at 5K merchants
  Agent ratio: 1 agent per 1,000 active merchants
  CSAT target: > 90%
  First contact resolution: > 70%
```
