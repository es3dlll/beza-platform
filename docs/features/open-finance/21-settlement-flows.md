# Open Finance Settlement Flows

## Settlement Types

### Real-time Settlement (API Payments)
```
Trigger: Every completed API payment
Mechanism: Immediate double-entry posting via CFE
Recipient: Available immediately
Settlement: Developer wallet debited, recipient credited
Ledger: Real-time CFE posting
```

### Batch Settlement (Bulk Payments)
```
Trigger: Completion of bulk payment job
Scope: All payments in a single bulk request
Mechanism: Net position → single wallet debit + individual credits
Execution: Async queue processing
Confirmation: Bulk summary webhook + downloadable report
```

### Subscription Settlement
```
Trigger: Monthly billing cycle (1st of each month)
Scope: All developer subscriptions due
Mechanism: Automated charge to developer's funding wallet
Execution: Cron job at 00:00 on 1st
Failure: If insufficient balance → downgrade to free tier
```

## Settlement Flow (Developer Daily)

```
Developer PayFast's Day:
  Starting Balance: 5,000,000 SYP
  
  09:00 — API Payment (Order #123): -25,050 SYP → Balance: 4,974,950
  09:30 — API Payment (Order #124): -10,025 SYP → Balance: 4,964,925
  10:00 — Wallet Top-up (bank transfer): +2,000,000 → Balance: 6,964,925
  14:00 — Bulk Payment (NGO batch 50K each × 100): -5,010,000 → Balance: 1,954,925
  ...
  End of Day:
    Total Payments Initiated: 3,000,000 SYP
    Total API Fees: 6,000 SYP
    Total Funded: 2,000,000 SYP
    End Balance: 3,994,000 SYP
```

## Settlement Flow (Platform Fee Model)

```
Open Finance Fee Structure:
  Transaction Fee: 0.2% per payment (capped at 2,000 SYP)
  Bulk Fee: 0.15% per payment (capped at 1,000 SYP)
  
  Daily Fee Summary:
    Payments: 3,000,000 × 0.2% = 6,000 SYP
    Bulk: 5,000,000 × 0.15% = 7,500 SYP
    Total Daily Fee Revenue: 13,500 SYP
  
  Monthly Fee Revenue (est. at 5M API calls): 
    0.2% × 500M SYP TP = 1,000,000 SYP ≈ $80K
```

## Reconciliation

### Daily Reconciliation
```
1. Match all API-initiated payments to wallet_transactions
2. Verify API fee calculation (0.2% of payment amount)
3. Check idempotency: no duplicate payments with same key
4. Verify webhook delivery count vs payment count
5. Generate developer statement (preview for upcoming billing)
```

### Exception Handling
```
Mismatch < 1,000 SYP: Auto-adjust with memo
Mismatch 1,000-100,000 SYP: Flag for manual review
Mismatch > 100,000 SYP: Halt API, notify finance + engineering

Common exceptions:
  - Idempotency key reused with different params → investigate
  - Webhook delivery count < payment count → retry webhooks
  - Fee calculation mismatch → review rate limit config
```
