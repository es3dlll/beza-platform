# Open Finance Ledger Flows

## Account Structure

### Chart of Accounts (Open Finance Specific)
| Code | Account Name | Type | Normal Balance |
|------|-------------|------|---------------|
| 1201 | Developer Funding Wallets (SYP) | Asset | Debit |
| 1202 | Developer Funding Wallets (USD) | Asset | Debit |
| 2201 | API Fee Revenue Receivable | Liability | Credit |
| 3201 | Beza API Fee Income | Revenue | Credit |
| 3202 | Beza Subscription Income | Revenue | Credit |
| 5201 | Developer Settlement Clearing | Asset | Debit |

### Journal Entry Patterns

#### API Payment Initiation
```
API Payment (25,000 SYP, API fee 50 SYP, 0.2%)
Timestamp: 2026-06-01T10:00:00Z
Reference: PAY-ABC123XYZ

DR  1201  Developer Funding Wallet (Dev)     25,050
CR  1101  Customer SYP Wallet (Recipient)     25,000
-- Recipient wallet credited, dev wallet debited

DR  2101  CFE Internal Clearing               25,050
CR  1201  Developer Funding Wallet (Dev)       25,050
-- Internal clearing match

DR  1101  Customer SYP Wallet (Recipient)     25,000
CR  2101  CFE Internal Clearing               25,000
-- Internal clearing match

DR  2101  CFE Internal Clearing                   50
CR  3201  Beza API Fee Income                     50
-- API fee revenue recognition
```

#### Developer Subscription Payment
```
Monthly Startup Plan ($50 = 625,000 SYP at 12,500 rate)
Timestamp: 2026-06-01T00:00:00Z
Reference: SUB-ABC123

DR  1101  Customer SYP Wallet (Developer)    625,000
CR  3202  Beza Subscription Income           625,000
-- Monthly subscription fee collected
```

## Daily Settlement Process
```
Step 1: At 23:59, calculate developer settlement positions:
  - Total payments initiated: 50,000,000 SYP
  - Total API fees earned: 100,000 SYP
  - Net developer funding change: -50,000,000 SYP

Step 2: Developer funding reconciliation:
  - Each developer's net position calculated
  - Debited amount = sum of payments + fees
  - Remaining balance = previous - net debit

Step 3: Fee income aggregation:
  SELECT SUM(fee) FROM api_usage_logs
  WHERE endpoint LIKE '%payments%' AND status_code = 200
  AND created_at >= TODAY AND created_at < TOMORROW

Step 4: EOD balance snapshot for developer wallets
```

## Reconciliation Checks
```
Daily Reconciliation (Automated, 02:00 AM):

1. API Fee Match:
   SELECT SUM(fee) FROM api_usage_logs WHERE status_code = 200 AND date = TODAY
   vs
   SELECT SUM(amount) FROM ledger_entries WHERE account = '3201' AND date = TODAY
   → Must match within 0.01%

2. Payment Discrepancy:
   SELECT COUNT(*) FROM api_usage_logs WHERE endpoint LIKE '%payments%' AND status_code = 200
   vs
   SELECT COUNT(*) FROM wallet_transactions WHERE source = 'api' AND date = TODAY
   → Must match

3. Developer Balance Check:
   SELECT SUM(balance) FROM developer_funding_wallets
   vs
   Expected balance based on yesterday + funding - payments - fees
   → Must match

Alert if any check fails → Slack #ops-finance
```
