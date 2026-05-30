# Loyalty Operations

## Operational Workflows

### User Support Scenarios

#### Scenario 1: "My points balance is wrong"
```
1. Check points ledger for user:
   SELECT * FROM loyalty_points WHERE user_id = ? ORDER BY created_at DESC LIMIT 50;
2. Verify balance calculation:
   SELECT SUM(amount) FROM loyalty_points WHERE user_id = ? AND expired_at IS NULL;
3. Compare with displayed balance (loyalty_points_balance)
4. If mismatch:
   → Run reconciliation: UPDATE loyalty_points_balance SET balance = calculated
   → Check for duplicate entries (same source_transaction_id)
   → Check for missing expiry reversal
5. If user claims missing earnings:
   → Check wallet_transactions for same period
   → Points should exist for each eligible transaction
   → Manually credit if system missed
```

#### Scenario 2: "My coupon code is not working"
```
1. Check coupon validity:
   - Does coupon exist? SELECT * FROM loyalty_redemptions WHERE coupon_code = ?
   - Is it expired? coupon_expires_at < now()
   - Has it been fully used? remaining_value <= 0
2. If expired:
   → "انتهت صلاحية الكوبون في ..."
   → Offer to return points (manual redemption refund)
3. If fully used:
   → Show redemption history: when and where used
4. If valid but not applying:
   → Check if fee type matches transaction type
   → Check minimum amount requirement
   → Escalate to engineering for system bug
```

#### Scenario 3: "Why was I downgraded?"
```
1. Check tier history:
   SELECT * FROM loyalty_member_tier_history WHERE user_id = ? ORDER BY created_at DESC;
2. Explain rolling 12-month calculation:
   "يتم حساب النقاط على أساس آخر 12 شهراً متحركة"
3. Show current rolling total vs threshold:
   - Your rolling total: 48,000 points
   - Gold threshold: 50,000 points
   - "أنت على بعد 2,000 نقطة فقط للعودة إلى الذهبي"
4. Grace period: 30 days from downgrade
   - If within grace period → restore temporarily
   - Give tips to earn back: "إليك طرق لكسب النقاط أسرع"
```

#### Scenario 4: "I want to create a merchant campaign"
```
1. Verify merchant status:
   - Is user a verified merchant?
   - If not: guide to merchant registration
2. Walk through campaign creation:
   - Name your campaign (Arabic)
   - Select type: multiplier, fixed points, or cashback
   - Set budget (min 100,000 SYP)
   - Set duration (min 7 days, max 90 days)
   - Fund campaign wallet
3. Campaign goes live within 5 minutes
4. Show dashboard: monitor in real-time
```

### Daily Operations Checklist
```
☐ 08:00 — Check Grafana dashboard (points earning rate, redemption rate)
☐ 08:30 — Review failed redemptions from last 24h
☐ 09:00 — Check airtime provider settlement success rate (> 98%)
☐ 10:00 — Review tier change reports (upgrades/downgrades from overnight batch)
☐ 11:00 — Check campaign budgets (any nearly exhausted?)
☐ 12:00 — Review points expiry warnings triggered today
☐ 14:00 — Check reward catalog stock levels (add more if low)
☐ 16:00 — Review support tickets: loyalty-related issues
☐ 23:30 — Verify daily batch jobs completed (tier, expiry, settlement)
```

### Escalation Matrix
```
Level 1 (L1): Customer Support
  - Handle: Wrong balance display, coupon not working, tier questions
  - Escalation: Refunds, manual adjustments, provider issues

Level 2 (L2): Operations Team
  - Handle: Points adjustments, tier overrides, campaign issues
  - Escalation: System bugs, balance corruption, provider settlement

Level 3 (L3): Engineering
  - Handle: Points calculation bugs, batch job failures, database issues
  - Escalation: Architecture changes, security incidents

Level 4 (L4): CTO / Finance
  - Handle: Points liability discrepancies, compliance issues, major fraud
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

Points Adjustment:
  Balance correction: < 1 hour
  Redemption refund: < 24 hours
  Tier override: < 4 hours

Support Volume:
  Expected: 50 loyalty tickets/day at 200K users
  Agent ratio: 1 agent per 50K active loyalty members
  CSAT target: > 85%
  First contact resolution: > 70%
```
