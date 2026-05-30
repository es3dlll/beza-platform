# Remittance Ledger Flows

## Account Structure

### Chart of Accounts (Remittance-Specific)
| Code | Account Name | Type | Normal Balance |
|------|-------------|------|---------------|
| 1101 | Customer SYP Wallets | Asset | Debit |
| 1102 | Customer USD Wallets | Asset | Debit |
| 1103 | Customer EUR Wallets | Asset | Debit |
| 1201 | Correspondent Bank USD (Nostro) | Asset | Debit |
| 1202 | Correspondent Bank EUR (Nostro) | Asset | Debit |
| 2101 | Settlement Clearing | Liability | Credit |
| 3101 | Beza Remittance Fee Income | Revenue | Credit |
| 3102 | Beza FX Spread Income | Revenue | Credit |
| 3103 | Beza Recurring Fee Income | Revenue | Credit |
| 4101 | Correspondent Bank Fee Expense | Expense | Debit |
| 4102 | FX Hedging Expense | Expense | Debit |
| 5101 | Remittance Suspense (Unclaimed) | Liability | Credit |

### Journal Entry Patterns

#### Local P2P Transfer (50,000 SYP, fee 250 SYP)
```
DR  1101  Customer SYP Wallets (Sender)       50,250
CR  1101  Customer SYP Wallets (Recipient)    50,000
CR  3101  Beza Remittance Fee Income              250
-- Local P2P: sender debited, recipient credited, fee recognized
```

#### Diaspora USD→SYP Remittance ($500 USD → 6,200,000 SYP)
```
DR  1102  Customer USD Wallets (Sender)          $507.50
CR  3101  Beza Remittance Fee Income                $7.50
-- Fee income in source currency

DR  2101  Settlement Clearing                   $500.00
CR  1102  Customer USD Wallets (Sender)          $500.00
-- Fund movement to settlement for FX conversion

DR  1201  Correspondent Bank USD (Nostro)        $500.00
CR  2101  Settlement Clearing                    $500.00
-- USD moved to nostro account

DR  2101  Settlement Clearing                6,200,000 SYP
CR  1101  Customer SYP Wallets (Recipient)   6,200,000 SYP
-- SYP credited to recipient from settlement

DR  4101  Correspondent Bank Fee Expense         2,000 SYP
CR  3102  Beza FX Spread Income                 93,000 SYP
CR  2101  Settlement Clearing                   95,000 SYP
-- FX spread: (mid-market 6,295,000 - Beza rate 6,200,000) = 95,000 SYP
-- Less correspondent fee 2,000 SYP = net FX income 93,000 SYP
```

#### Recurring Transfer Execution (€200 → 2,640,000 SYP)
```
DR  1103  Customer EUR Wallets (Sender)          €203.00
CR  3103  Beza Recurring Fee Income                €3.00
-- Recurring fee (1.5%)

DR  2101  Settlement Clearing                    €200.00
CR  1103  Customer EUR Wallets (Sender)           €200.00
-- Fund movement

DR  1202  Correspondent Bank EUR (Nostro)         €200.00
CR  2101  Settlement Clearing                     €200.00
-- EUR moved to nostro

DR  2101  Settlement Clearing                2,640,000 SYP
CR  1101  Customer SYP Wallets (Recipient)   2,640,000 SYP
-- SYP credited

DR  4102  FX Hedging Expense                      1,500 SYP
CR  3102  Beza FX Spread Income                  42,500 SYP
CR  2101  Settlement Clearing                    44,000 SYP
-- FX spread income net of hedging cost
```

#### Failed Transfer Reversal (Release Holds)
```
// If transfer fails after hold but before posting:
DR  1102  Customer USD Wallets (Sender)          $507.50
CR  2101  Settlement Clearing                    $507.50
-- Hold released, funds returned to sender

// FX Lock released (no journal entry — rate lock is not a balance event)
// Logged in fx_rate_logs with consumed_at = NULL, expired = TRUE
```

#### Remittance Suspense (Unregistered Recipient)
```
// When recipient is not on Beza, funds held in suspense:
DR  2101  Settlement Clearing                6,200,000 SYP
CR  5101  Remittance Suspense                6,200,000 SYP
-- Funds held pending recipient registration

// When recipient registers and claims:
DR  5101  Remittance Suspense                6,200,000 SYP
CR  1101  Customer SYP Wallets (Recipient)   6,200,000 SYP
-- Funds released to new wallet

// If unclaimed after 90 days (reversed to sender):
DR  5101  Remittance Suspense                6,200,000 SYP
CR  1102  Customer USD Wallets (Sender)          $500.00
CR  3101  Beza Remittance Reversal Fee            $15.00
-- Reversal fee covers processing cost
```

## Daily Settlement Process
```
Step 1: At 23:59 CET, calculate corridor net positions:

  EUR→SYP Corridor:
    Total EUR received: €45,000
    Total SYP paid out: 594,000,000 SYP
    Net EUR position: €45,000 in nostro

  USD→SYP Corridor:
    Total USD received: $62,500
    Total SYP paid out: 775,000,000 SYP
    Net USD position: $62,500 in nostro

  SYP→SYP (local P2P):
    Total sent: 125,000,000 SYP
    Net position: 0 (internal transfer)

Step 2: Correspondent settlement entries:
DR  1201  Correspondent Bank USD Nostro     $62,500
CR  2101  Settlement Clearing                $62,500
-- USD nostro funded by customer debits

DR  2101  Settlement Clearing          594,000,000 SYP
CR  1101  Customer SYP Wallets          594,000,000 SYP
-- SYP settlement from nostro conversion

Step 3: Revenue recognition (EOD batch):
  Total Fee Income: $4,250 + €2,800 = ~$7,200
  Total FX Income: 4,250,000 SYP (~$340)
  Total Correspondent Costs: $1,200
  Net Remittance Revenue: ~$6,340/day

Step 4: EOD balance snapshot:
  INSERT INTO remittance_balance_history (corridor_id, date, total_sent,
    total_received, fee_income, fx_income, settlement_cost)
  VALUES (1, CURDATE(), 45000, 594000000, 4250, 4250000, 1200);
```

## Reconciliation Checks
```
Daily Reconciliation (Automated, 03:00 AM):

1. Corridor Balance Check:
   SELECT SUM(source_amount) FROM remittances WHERE date = TODAY AND corridor_id = 1
   vs
   SELECT SUM(amount) FROM nostro_transactions WHERE corridor_id = 1 AND date = TODAY
   → Must match within 0.1%

2. FX Rate Check:
   SELECT AVG(fx_rate) FROM remittances WHERE date = TODAY AND corridor_id = 1
   vs
   SELECT rate FROM fx_rate_logs WHERE date = TODAY
   → Rates must be within tolerance

3. Suspense Account Check:
   SELECT SUM(amount) FROM remittance_suspense WHERE created_at > 90 days ago
   → Report aging of unclaimed funds

4. Revenue Reconciliation:
   SELECT SUM(fee) + SUM(fx_spread_income) FROM remittances WHERE date = TODAY
   vs
   SELECT SUM(amount) FROM revenue_accounts WHERE source = 'remittance' AND date = TODAY
   → Must match

Alert if any check fails → Slack #ops-finance
```
