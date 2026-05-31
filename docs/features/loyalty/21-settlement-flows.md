# Loyalty Settlement Flows

## Settlement Types

### Instant Redemption (Fee Waiver)
```
Trigger: Points redeemed for fee discount coupon
Mechanism: Coupon code generated instantly, available immediately
Liability: Transferred from points liability to coupon liability
Settlement: Coupon value consumed when applied to future transaction
Coupon expiry: 30 days from issuance
```

### Batch Airtime Delivery
```
Trigger: Daily batch (03:00 AM) processes all airtime redemptions
Scope: All airtime redemptions from previous day
Mechanism: Aggregate list → send to telecom provider API
Execution: Automated job with retry 3x
Confirmation: Provider returns success/failure per redemption
Failed: Refund points to user, notify
```

### Weekly Gift Card Settlement
```
Trigger: Weekly batch (Saturday 03:00 AM)
Scope: All gift card redemptions from past week
Mechanism: Bulk order to gift card provider
Settlement: Provider sends gift card codes → stored in DB → delivered to user
```

### Merchant Campaign Settlement
```
Trigger: Real-time per transaction + EOD summary
Mechanism: Per-transaction bonus from campaign budget
Settlement: Deduct from merchant prefund, credit points liability
End of campaign: Remaining budget returned to merchant wallet
```

## Settlement Flow (Airtime Redemption)

```
User redeems 2,500 points for SYRIATEL 2,500 SYP airtime
  
  Step 1: Redemption created (status: completed)
          Points deducted: -2,500
          Liability transfer: Points → Airtime Payable

  Step 2: Daily batch (03:00 AM):
          Aggregate all SYRIATEL redemptions
          Total: 125 redemptions × 2,500 = 312,500 SYP
          Send bulk request to SYRIATEL API

  Step 3: SYRIATEL processes:
          - 120 successful → airtime credited
          - 5 failed (invalid numbers) → refund points

  Step 4: For successful:
          DR  1303  Airtime Redemption Payable     312,500 SYP
          CR  Bank/Payment to Provider             312,500 SYP

  Step 5: For failed:
          DR  1301  Points Liability Reserve        12,500 SYP
          CR  1303  Airtime Redemption Payable      12,500 SYP
          -- Failed redemptions, points returned
          loyalty_points: +2,500 (refund) per failed
          Notification: "تم إعادة 2,500 نقطة بسبب خطأ في الشحن"
```

## Settlement Flow (Merchant Campaign)

```
Merchant Al-Electronics campaign (500,000 SYP budget, 2× points, 30 days)

  Day 1:
    Campaign activated, funds deducted from merchant wallet
    30 redemptions, 45,000 bonus points awarded
    Budget remaining: 455,000 SYP

  Day 15:
    1,200 redemptions, 320,000 bonus points awarded
    Budget remaining: 180,000 SYP

  Day 20:
    Campaign extended: merchant adds 200,000 SYP
    Budget: 180,000 + 200,000 = 380,000 SYP

  Day 30:
    Campaign ends automatically
    2,500 total redemptions, 480,000 bonus points awarded
    Budget remaining: 20,000 SYP → returned to merchant wallet

  Settlement:
    DR  4201  Merchant Campaign Prefund          500,000 SYP
    CR  1301  Points Liability Reserve            480,000 SYP
    -- Points awarded during campaign
    DR  4201  Merchant Campaign Prefund           20,000 SYP
    CR  1101  Customer SYP Wallet (Merchant)      20,000 SYP
    -- Unused budget returned
```

## Reconciliation

### Daily Reconciliation
```
1. Points earned count vs wallet transaction count
2. Points redeemed vs rewards issued
3. Coupon liability vs unredeemed coupons
4. Campaign budget remaining vs merchant prefund accounts
5. Airtime provider delivery success rate (> 98%)
```

### Exception Handling
```
Mismatch < 100,000 SYP: Auto-adjust with memo
Mismatch 100,000-500,000 SYP: Flag for manual review
Mismatch > 500,000 SYP: Halt batch processing, notify finance + engineering

Common exceptions:
  - Airtime provider API failure → retry queue
  - Duplicate coupon usage → investigate fraud
  - Campaign budget overspend → check race condition
```
