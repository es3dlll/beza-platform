# Loyalty Financial Flows

## Flow 1: Points Earning

### Transaction completed → 1 point per 1,000 SYP → stored in points ledger

```
Step 1: User completes transaction (e.g., sends 25,000 SYP)
Step 2: WalletDebited event emitted by WalletService
Step 3: Loyalty listener triggers EarnPointsAction
Step 4: PointsService.earn() calculates:
  - Base points: 25,000 / 1,000 = 25 points
  - Tier multiplier: 1.2× (Silver tier)
  - Final points: floor(25 × 1.2) = 30 points
Step 5: Points ledger entry:
  DR  User Points Receivable (Off-Balance Sheet)    30 pts
  CR  Points Liability (Off-Balance Sheet)          30 pts
Step 6: Update loyalty_points_balance.balance += 30
Step 7: Expiry set: 2027-06-01 (12 months)
Step 8: Emit PointsEarned event

Sequence:
  User sends 25,000 SYP
    → Wallet debited 25,000 SYP
    → PointsService.earn(user_id=42, amount=25000, source='transfer_send')
    → Points calculated: 30 pts (Silver 1.2×)
    → loyalty_points_balance: 10,000 → 10,030 pts
    → Notification: "+30 نقطة! رصيدك: 10,030 نقطة"

Revenue Impact:
  - Points cost: 30 pts × 1 SYP/pt = 30 SYP liability
  - Fee on transaction: 25,000 × 0.4% (Silver fee) = 100 SYP revenue
  - Net per transaction: 100 - 30 = 70 SYP (after points cost)
  - Points liability booked as accrued expense
```

## Flow 2: Points Redemption

### User redeems 5,000 points for 5,000 SYP fee waiver → journal entry DR liability

```
Step 1: User selects "Fee Discount 5,000" reward (5,000 pts → 5,000 SYP)
Step 2: User confirms with PIN
Step 3: RedemptionService.redeem() executes:
  - Check: balance (10,000) >= cost (5,000) ✓
  - Deduct: balance -= 5,000 pts
  - Generate: coupon code CPN_FD_ABC123 (valid 30 days)
  - Return: success + coupon
Step 4: Points ledger entry:
  DR  Points Liability                     5,000 pts
  CR  User Points Receivable               5,000 pts
Step 5: Coupon stored for future use
Step 6: On next transfer:
  - Coupon auto-applied: fee (125 SYP) → waived
  - Beza Fee Income: reduced by 5,000 SYP (up to coupon value)
  - Remaining coupon value: CPN_FD_ABC123 → depleted, marked used
Step 7: Journal entry for fee waiver:
  DR  Coupon Liability (Fee Waiver Reserve)    5,000 SYP
  CR  Beza Fee Income                         5,000 SYP
  -- Fee waived, covered by pre-funded liability

Step 8: Emit PointsRedeemed event

Revenue Impact:
  - Coupon used: 5,000 SYP fee revenue foregone
  - Points liability reduced: 5,000 SYP (recognized as expense previously)
  - Net P&L impact: 0 (liability release offsets revenue loss)
```

## Flow 3: Tier Upgrade

### 12-month rolling total > thresholds → immediate upgrade → benefits active

```
Step 1: User currently Silver tier (30,000 rolling points)
Step 2: User completes large transaction: receives 2,000,000 SYP
Step 3: Points earned: 2,000,000 / 1,000 × 1.2 (Silver) = 2,400 pts
Step 4: Rolling total updated: 30,000 + 2,400 = 32,400 pts
  -- Still below Gold threshold (50,000) → no upgrade

Step 5: Over next month, user earns 20,000 more points
Step 6: Daily batch job runs at 02:00
Step 7: TierService.checkAndUpgrade():
  - Rolling total: 52,400 pts
  - Gold threshold: 50,000 pts → EXCEEDED
  - Current tier: Silver → eligible for Gold
Step 8: Upgrade executed:
  - loyalty_member_tier_history: new entry (upgrade, silver→gold)
  - Tier benefits activated:
    - Transfer fee: 0.4% → 0.3%
    - Points multiplier: 1.2× → 1.5×
    - Daily limit: 1M → 2M
Step 9: Notification: "🎉 مبروك! لقد ترقيت إلى المستوى الذهبي!"
Step 10: Next transaction: fee at 0.3%, points at 1.5×

Revenue Impact of Upgrade:
  - Before (Silver): 0.4% fee, user pays 100 SYP on 25K transfer
  - After (Gold): 0.3% fee, user pays 75 SYP on same transfer
  - Revenue reduction: 25 SYP per transaction
  - But: higher transaction volume expected
  - Points cost increases: 1.5× vs 1.2× multiplier
  - Net: lower margin per txn, but higher retention + volume
```
