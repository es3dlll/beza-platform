# Wallet Ledger Flows

## Account Structure

### Chart of Accounts (Wallet-Specific)
| Code | Account Name | Type | Normal Balance |
|------|-------------|------|---------------|
| 1101 | Customer SYP Wallets | Asset | Debit |
| 1102 | Customer USD Wallets | Asset | Debit |
| 1103 | Agent Float SYP | Asset | Debit |
| 1104 | Agent Float USD | Asset | Debit |
| 2101 | CFE Internal Clearing | Liability | Credit |
| 3101 | Beza Fee Income | Revenue | Credit |
| 3102 | Beza FX Income | Revenue | Credit |
| 3103 | Beza Commission Expense | Expense | Debit |
| 4101 | Agent Commission Payable | Liability | Credit |
| 5101 | Settlement Clearing | Asset | Debit |

### Journal Entry Patterns

#### P2P Transfer
```
P2P Transfer (25,000 SYP, fee 125 SYP)
Timestamp: 2026-06-01T10:00:00Z
Reference: TXN-ABC123XYZ

DR  1101  Customer SYP Wallets (Sender)    25,125
CR  1101  Customer SYP Wallets (Recipient)  25,000
-- Sender wallet debited, recipient credited

DR  2101  CFE Internal Clearing             25,125
CR  1101  Customer SYP Wallets (Sender)     25,125
-- Internal clearing match

DR  1101  Customer SYP Wallets (Recipient)  25,000
CR  2101  CFE Internal Clearing             25,000
-- Internal clearing match

DR  2101  CFE Internal Clearing                125
CR  3101  Beza Fee Income                      125
-- Fee revenue recognition
```

#### Agent Cash-in
```
Agent Cash-in (100,000 SYP, commission 1,000 SYP)
Timestamp: 2026-06-01T11:00:00Z
Reference: TXN-FUND-789

DR  1103  Agent Float SYP (Agent)          100,000
CR  1101  Customer SYP Wallets (User)      100,000
-- Agent float decreases, user wallet increases

CR  4101  Agent Commission Payable           1,000
DR  3103  Beza Commission Expense            1,000
-- Commission earned by agent, expensed by Beza
```

#### Agent Cash-out
```
Agent Cash-out (50,000 SYP, fee 750 SYP, commission 500 SYP)
Timestamp: 2026-06-01T12:00:00Z
Reference: TXN-WDRAW-456

DR  1101  Customer SYP Wallets (User)       50,750
CR  1103  Agent Float SYP (Agent)           50,000
CR  3101  Beza Fee Income                      750
-- User debited (amount + fee), agent float increases

CR  4101  Agent Commission Payable             500
DR  3103  Beza Commission Expense              500
-- Commission earned by agent
```

## Daily Settlement Process
```
Step 1: At 23:59, calculate net positions:
  - Total customer debits: 500,000,000 SYP
  - Total customer credits: 450,000,000 SYP
  - Net position: 50,000,000 SYP (Beza liability)

Step 2: Journal entry for daily settlement:
DR  1101  Customer SYP Wallets (Net)        50,000,000
CR  5101  Settlement Clearing               50,000,000
-- End-of-day settlement sweep

Step 3: Agent commission settlement:
  - Sum all agent commissions earned today: 5,000,000 SYP
DR  4101  Agent Commission Payable           5,000,000
CR  5101  Settlement Clearing                5,000,000
-- Agent commissions accrued

Step 4: EOD balance snapshot:
  INSERT INTO wallet_balance_history (wallet_id, balance, held, recorded_at)
  SELECT id, balance, held, NOW() FROM wallets WHERE status = 'active';
```

## Reconciliation Checks
```
Daily Reconciliation (Automated, 02:00 AM):

1. CFE Balance Check:
   SELECT SUM(available + held) FROM cfe_accounts
   vs
   SELECT SUM(balance) FROM wallet_balance_snapshots WHERE date = TODAY
   → Must match within 0.01%

2. Transaction Count Check:
   SELECT COUNT(*) FROM wallet_transactions WHERE date = TODAY
   vs
   SELECT SUM(total_transactions) FROM cfe_transaction_log WHERE date = TODAY
   → Must match

3. Fee Reconciliation:
   SELECT SUM(fee) FROM wallet_transactions WHERE date = TODAY AND status = 'completed'
   vs
   SELECT SUM(amount) FROM cfe_posting WHERE account_type = 'fee_income' AND date = TODAY
   → Must match

4. Agent Float Check:
   SELECT SUM(balance) FROM agent_float_accounts
   vs
   Cash-in total - Cash-out total (today) + previous balance
   → Must match within 5,000 SYP tolerance

Alert if any check fails → Slack #ops-finance
```
