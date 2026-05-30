# Loyalty Ledger Flows

## Account Structure

### Chart of Accounts (Loyalty-Specific)
| Code | Account Name | Type | Normal Balance |
|------|-------------|------|---------------|
| 1301 | Points Liability Reserve | Liability | Credit |
| 1302 | Fee Waiver Coupon Liability | Liability | Credit |
| 1303 | Airtime Redemption Payable | Liability | Credit |
| 1304 | Gift Card Redemption Payable | Liability | Credit |
| 3301 | Points Liability Expense | Expense | Debit |
| 3302 | Campaign Marketing Expense | Expense | Debit |
| 4201 | Merchant Campaign Prefund | Liability | Credit |

### Journal Entry Patterns

#### Points Earned (Monthly Accrual)
```
Monthly points earned accrual (150M points earned in June)
Timestamp: 2026-06-30T23:59:59Z
Reference: PTS-ACCRUAL-2026-06

DR  3301  Points Liability Expense          150,000,000 SYP
CR  1301  Points Liability Reserve          150,000,000 SYP
-- Monthly accrual of points earned (1 point = 1 SYP liability)
```

#### Points Redeemed (Fee Waiver)
```
User redeems 5,000 points for transfer fee waiver
Timestamp: 2026-06-01T10:00:00Z
Reference: RDM-ABC123

DR  1301  Points Liability Reserve            5,000 SYP
CR  1302  Fee Waiver Coupon Liability         5,000 SYP
-- Points liability transferred to coupon liability
```

#### Coupon Used
```
User's coupon applied to transfer (fee waived: 125 SYP, remaining value carried forward)
Timestamp: 2026-06-01T14:00:00Z
Reference: CPN-FD-ABC123-USE

DR  1302  Fee Waiver Coupon Liability           125 SYP
CR  3101  Beza Fee Income (Fee Foregone)        125 SYP
-- Coupon partially consumed against fee
-- (Note: 3101 is credited to reverse fee that would have been charged)
```

#### Airtime Redemption
```
User redeems 2,500 points for Syriatel airtime 2,500 SYP
Timestamp: 2026-06-01T11:00:00Z
Reference: RDM-DEF456

DR  1301  Points Liability Reserve            2,500 SYP
CR  1303  Airtime Redemption Payable          2,500 SYP
-- Liability transferred to airtime provider payable
```

#### Merchant Campaign Initial Funding
```
Merchant funds campaign with 500,000 SYP
Timestamp: 2026-06-01T08:00:00Z
Reference: CAMP-FUND-ABC123

DR  1101  Customer SYP Wallet (Merchant)     500,000 SYP
CR  4201  Merchant Campaign Prefund          500,000 SYP
-- Campaign budget held as liability
```

#### Merchant Campaign Points Awarded
```
Campaign awards 2× points on eligible transaction
Timestamp: 2026-06-01T12:00:00Z
Reference: CAMP-RDM-ABC123

DR  4201  Merchant Campaign Prefund              500 SYP
CR  1301  Points Liability Reserve                500 SYP
-- Bonus points funded from merchant campaign budget
-- (500 bonus points × 1 SYP value)
```

## Daily Settlement Process
```
Step 1: At 02:00, daily batch processes:
  - Expired points: reverse liability
  - Tier upgrades/downgrades
  - Merchant campaign budget checks

Step 2: Points expiry journal entry:
DR  1301  Points Liability Reserve           2,500 SYP
CR  3301  Points Liability Expense           2,500 SYP
-- Reverse liability for expired points (negative expense)

Step 3: Airtime provider settlement (weekly batch):
SELECT SUM(syp_value) FROM loyalty_redemptions
WHERE reward.category = 'airtime' AND status = 'completed'
AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)

DR  1303  Airtime Redemption Payable         50,000 SYP
CR  1101  Customer SYP Wallet (Provider)     50,000 SYP
-- Settle with airtime provider
```

## Reconciliation Checks
```
Daily Reconciliation (Automated, 03:00 AM):

1. Points Balance Check:
   SELECT SUM(amount) FROM loyalty_points WHERE expired_at IS NULL
   vs
   SELECT SUM(balance) FROM loyalty_points_balance
   → Must match

2. Points Liability Check:
   SELECT SUM(balance) * 1 SYP FROM loyalty_points_balance
   vs
   SELECT SUM(balance) FROM accounts WHERE account_code = '1301'
   → Must match within 1% tolerance

3. Campaign Budget Check:
   SELECT SUM(budget_remaining) FROM loyalty_merchant_campaigns WHERE status = 'active'
   vs
   SELECT SUM(balance) FROM accounts WHERE account_code = '4201'
   → Must match

4. Coupon Liability Check:
   SELECT SUM(syp_value - used_amount) FROM loyalty_redemption_coupons WHERE status = 'active'
   vs
   SELECT SUM(balance) FROM accounts WHERE account_code = '1302'
   → Must match

Alert if any check fails → Slack #ops-finance
```
