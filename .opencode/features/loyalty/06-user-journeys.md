# Loyalty User Journeys

## Journey 1: Points Earning (Automatic)
```
Step 1: User sends 25,000 SYP to friend
Step 2: Transaction completes successfully
Step 3: PointsService calculates: 25,000 / 1,000 × 1.0 (Bronze) = 25 points
Step 4: Points added to user's points ledger
Step 5: Push notification: "🟢 ربحت 25 نقطة! رصيدك: 12,500 نقطة"
Step 6: Points also appear in transaction detail: "نقاط مكتسبة: 25"
Step 7: Wallet home shows updated points balance

Edge Cases:
  - Transaction reversed: points deducted (negative entry in ledger)
  - Transaction failed: no points earned
  - Points at tier boundary: notify user of upgrade eligibility
  - Network issue: points credited when system processes async
```

## Journey 2: Tier Upgrade (Gold)
```
Step 1: User currently Silver (45,000 pts / 50,000 needed for Gold)
Step 2: Dashboard shows: "على بعد 5,000 نقطة من المستوى الذهبي!"
Step 3: User pays 500,000 SYP in bills → earns 750 pts (1.5× Silver bonus)
Step 4: Receives 200,000 SYP → earns 300 pts
Step 5: Sends 300,000 SYP → earns 450 pts
Step 6: Batch job runs at 02:00 — rolling 12-month total now: 50,200 pts
Step 7: System upgrades user to Gold tier
Step 8: Push notification: "🎉 مبروك! لقد ترقيت إلى المستوى الذهبي!"
Step 9: Tier benefits activate: 0.3% fee, 1.5× points, priority support
Step 10: Fee on next transfer: 0.3% instead of 0.4%

Edge Cases:
  - Batch job detects upgrade: immediate activation
  - User falls below threshold same day: grace period 30 days
  - Maximum tier: Platinum (200K pts) — invite-only celebration at 200K
```

## Journey 3: Points Redemption (Fee Discount)
```
Step 1: User opens Rewards section in app
Step 2: Shows: "رصيد النقاط: 15,000 نقطة (قيمة 15,000 ل.س)"
Step 3: Featured reward: "خصم رسوم تحويل — استبدل 5,000 نقطة = 5,000 ل.س"
Step 4: User taps "Fee Discount" → sees options:
  - 5,000 pts → 5,000 SYP fee waiver
  - 10,000 pts → 10,000 SYP fee waiver
  - 25,000 pts → 25,000 SYP fee waiver
Step 5: User selects 5,000 pts → confirms with PIN
Step 6: Points deducted: -5,000 (DR points ledger)
Step 7: Fee waiver coupon created (coupon_FD_123, valid 30 days)
Step 8: Coupon auto-applied on next transfer
Step 9: Confirmation: "تم الاستبدال! ستطبق على أول تحويلة قادمة"

Edge Cases:
  - Insufficient points: show maximum affordable option
  - Coupon expires unused: points returned after 30 days
  - User redeems multiple coupons: stacked, used FIFO
  - User requests refund: reversal requires points redeposit
```

## Journey 4: Merchant Campaign Setup
```
Step 1: Merchant opens Beza Business → Loyalty Campaigns
Step 2: Taps "إنشاء حملة" (Create Campaign)
Step 3: Sets campaign details:
  - Name: "عروض الصيف من اليكتترونكس"
  - Type: "2× points on purchases over 50,000 SYP"
  - Duration: 2026-06-01 to 2026-07-01
  - Budget: 500,000 SYP (funds reward pool)
Step 4: Funds campaign wallet: 500,000 SYP
Step 5: System activates campaign
Step 6: Customers see promotion in app: "🛍️ متجر اليكتترونكس — نقاط مضاعفة!"
Step 7: Customer shops, pays via QR, earns double points
Step 8: Merchant sees dashboard: 150 redemptions, 45,000 SYP used

Edge Cases:
  - Campaign budget exhausted: points revert to standard rate, merchant notified
  - Merchant wants early termination: remaining budget refunded
  - Fraud detection: unusual redemption velocity flagged
```
