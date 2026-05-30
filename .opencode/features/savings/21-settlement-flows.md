# Savings Settlement Flows

## Settlement Types

| Settlement Type | Frequency | Parties | Description |
|---------------|-----------|---------|-------------|
| Auto-save settlement | Per execution | User → Savings | Daily/weekly transfer main wallet to sub-wallet |
| Round-up settlement | Per transaction | User → Savings | Round-up transfer after wallet txn |
| Goal completion settlement | Per completion | Savings → User | Release savings to main wallet |
| Early withdrawal settlement | Per request | Savings → User | Withdrawal with penalty |
| Profit distribution settlement | Monthly | Pool → Savings | Proportional profit credit |
| Team contribution settlement | Per deposit | Member → Team Goal | Contribution tracked per member |

## Settlement Flow: Auto-Save Batch

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│  AutoSave   │     │   CFE       │     │   Ledger    │     │  Analytics  │
│  Service    │     │             │     │   Service   │     │             │
├─────────────┤     ├─────────────┤     ├─────────────┤     ├─────────────┤
│ 1. Batch    │────>│ 2. Hold     │────>│ 3. DR/CR    │────>│ 4. Record   │
│    query    │     │    funds    │     │    entries  │     │    txn      │
│    due      │     │             │     │             │     │             │
│    goals    │     │             │     │             │     │             │
└─────────────┘     └─────────────┘     └─────────────┘     └─────────────┘

Batch processing:
  1. Query all due auto-save goals (every hour)
  2. For each goal in batch:
     a. CFE hold on main wallet → success
     b. CFE post: debit main wallet, credit savings sub-wallet
     c. Record double-entry in ledger
     d. Record savings_transaction
     e. Release hold
  3. On any failure: retry 3x, then skip with log
  4. Batch size limit: 500 goals per execution
```

## Settlement Flow: Profit Distribution

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│  Profit     │     │   CFE       │     │   Ledger    │     │  Notification│
│  Service    │     │   Engine    │     │   Service   │     │  Service    │
├─────────────┤     ├─────────────┤     ├─────────────┤     ├─────────────┤
│ 1. Calc     │────>│ 2. Get pool │     │             │     │             │
│    pool     │     │    return   │     │             │     │             │
│    total    │     │             │     │             │     │             │
├─────────────┤     ├─────────────┤     │             │     │             │
│ 3. Calc     │────>│ 4. Validate │     │             │     │             │
│    profit   │     │    return   │     │             │     │             │
│    shares   │     │    amount   │     │             │     │             │
├─────────────┤     ├─────────────┤     ├─────────────┤     ├─────────────┤
│ 5. Batch    │────>│ 6. Credit   │────>│ 7. Record   │────>│ 8. Push     │
│    dist.    │     │    each     │     │    each     │     │    notify   │
│    jobs     │     │    sub-     │     │    profit   │     │    each     │
│             │     │    wallet   │     │    entry    │     │    user     │
└─────────────┘     └─────────────┘     └─────────────┘     └─────────────┘

Settlement rules:
  - All profit distributions within a single batch must succeed or rollback as group
  - If CFE return is negative (loss): no distribution, principal guaranteed
  - Management fee credited to Beza Fee Income account same day
  - Audit log: every profit distribution has unique reference
```

## Settlement Reconciliation

### Daily Reconciliation (02:00 AM)
```
1. Compare savings_goals.current_amount total
   vs CFE savings sub-wallet balances total
   → Must match within 0.1% tolerance

2. Compare savings_transactions daily volume
   vs wallet_transactions linked via reference
   → Must match 1:1

3. Report discrepancies:
   - Missing transactions → auto-repair via event replay
   - Balance mismatch → flag for manual review
   - Duplicate entries → reverse duplicate
```

### Monthly Profit Reconciliation (1st, 01:00 AM)
```
1. Verify pool total calculation
   → SUM(goals.current_amount) = pool total
   
2. Verify return amount from CFE
   → CFE pool return report signed by CFE operations
   
3. Verify distribution math
   → SUM(weights) = 1.0 (within 0.01% tolerance)
   → SUM(distributions) + mgmt_fee = pool return
   
4. Check each goal received correct profit
   → profit / pool_total ≈ goal.current_amount / pool_total
```

### Backup Settlement (Manual)
```
If automated settlement fails:
  1. Export settlement report: savings_goals, transactions, CFE balances
  2. Operations team reviews discrepancy
  3. Manual CFE adjust entry created
  4. Manual adjustment transaction recorded in savings_transactions
  5. Approval: 2-person rule (ops manager + compliance)
```
