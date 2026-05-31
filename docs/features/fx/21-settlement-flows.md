# FX Engine Settlement Flows

## Settlement Types

### Instant Settlement (Default)
```
Trigger: Every completed FX conversion
Mechanism: Real-time double-entry posting via CFE
Timeline: Source debited, target credited within 2 seconds
Recipient: Available immediately for use
Reverse: Standard CFE reversal within 24h (same rate applied)
Ledger: Immediate debit/credit on wallet accounts
```

### Batch Settlement (Provider)
```
Trigger: End of day (23:59 daily)
Scope: Net FX provider settlements
Mechanism: Net position calculation → hedge execution
Execution: Automated cron job
Timeline:
  23:59 — Calculate net open positions
  00:15 — Hedge execution with provider
  00:30 — Settlement report generated
  01:00 — Provider invoices reconciled
```

### Hedge Settlement
```
Trigger: Net open position exceeds thresholds
Thresholds:
  USD: $10,000 net short or long
  EUR: €5,000 net short or long
  SYP: net position automatically matched (no external hedge needed)

Mechanism:
  1. Calculate net open position per currency
  2. If above threshold → execute hedge trade with provider
  3. Hedge trade: reverse the net position to zero
  4. Cost of hedge: recorded as FX Hedge Expense

Hedge execution example:
  Net position: Short USD $8,650, Short EUR €1,927
  Hedge: Buy $8,650 USD, Buy €1,927 EUR via parallel market provider
  Cost: Provider spread on hedge (typically 0.5% = $43.25 + €9.64)
  Entry:
    DR  6101  FX Hedge Expense                $52.89 USD equiv
    CR  4102  Provider Settlement Payable     $52.89 USD equiv
```

## Settlement Flow (Provider Daily)

```
Provider Settlement (Parallel Market API):

Provider Delivered Rates Today:
  SYP/USD: 14,500 - 14,600 (fluctuated during day)
  Total conversions referencing this provider: 120
  Total SYP sold: 250,000,000 SYP
  Total USD bought: $17,250 USD

Settlement with Provider:
  Beza used Provider's rates for 120 conversions
  Provider expected mid rate: 14,550 (average)
  Beza must settle net difference:
    - Beza bought $17,250 USD at various rates
    - Provider's consolidated rate: 14,500 (wholesale)
    - Beza owes: $17,250 × 14,500 = 250,125,000 SYP
    - Difference: 125,000 SYP (Beza profit on this provider leg)

  Settlement Entry:
    DR  4102  Provider Settlement Payable    250,125,000  SYP
    CR  5102  FX Settlement Clearing         250,125,000  SYP
    -- Payment to parallel market provider

    DR  5102  FX Settlement Clearing         250,000,000  SYP
    CR  1101  Customer SYP Wallets            250,000,000  SYP
    -- Customer SYP wallets settled
```

## Settlement Flow (CBS Reporting)

```
CBS Daily Rate Report Generation (01:00 AM):

Step 1: Gather data:
   SELECT 
       report_date,
       pair,
       AVG(cbs_official_rate) as cbs_rate,
       AVG(beza_rate) as beza_avg_rate,
       SUM(volume_converted) as total_volume,
       COUNT(*) as txn_count
   FROM fx_cbs_reports
   WHERE report_date = YESTERDAY
   GROUP BY pair;

Step 2: Generate report:
   CBS Rate Declaration — 2026-06-01
   
   Pair      | CBS Official | Beza Avg | Volume (SYP) | Txns
   ──────────┼──────────────┼──────────┼──────────────┼─────
   SYP/USD   | 13,100       | 14,935   | 250,000,000  | 120
   SYP/EUR   | 14,200       | 16,413   | 45,000,000   | 30
   USD/EUR   | N/A          | 1.099    | 0            | 0

   Spread Compliance:
     Max spread applied: 3.0% (SYP/USD standard tier)
     Average spread: 2.4%
     Within regulatory limit of 5%: ✓

Step 3: Export:
   - PDF (Arabic) → CBS submission
   - CSV → Internal records
   - JSON → API export for CBS digital portal

Step 4: Archive:
   - Store in fx_cbs_reports table
   - Retention: 10 years (regulatory requirement)
```

## Exception Handling
```
Mismatch Scenarios:

[Provider Rate Discrepancy > 1%]
  → Rate used in conversion differs from provider's recorded rate
  → Auto-investigate: refund spread difference to user
  → Notify ops: "Provider rate mismatch detected for conversion conv_abc123"

[Lock Expired Before Conversion Used]
  → Conversion attempted but lock expired (30s TTL passed)
  → Auto-retry: fetch fresh rate, present new lock to user
  → If user already confirmed: rate protected at new rate, notify difference

[CFE Posting Failure]
  → Conversion recorded in DB but CFE posting failed
  → Auto-retry: 3 attempts with 5s interval
  → If all fail: reverse transaction, refund user, alert ops
  
[Midnight Rate Change]
  → Conversion straddles midnight with locked rate
  → Rate honored at locked value (locked rate supersedes day boundary)
```

## Reconciliation
```
Daily FX Reconciliation (02:30 AM):

1. Match all CFE postings to fx_conversions:
   SELECT cfe_reference, conversion_id
   FROM fx_conversions 
   WHERE status = 'completed' AND date = TODAY
   FULL OUTER JOIN cfe_postings ON reference
   → Report orphans (DB but no CFE, CFE but no DB)

2. Verify total FX income:
   SELECT SUM(spread_amount) FROM fx_conversions WHERE date = TODAY
   vs
   SELECT SUM(amount) FROM ledger WHERE account = '3102' AND date = TODAY
   → Must match within 0.5%

3. Check provider settlement accuracy:
   Beza's recorded provider rates vs provider invoice
   → Flag any rate > 0.5% different

4. Hedge position verification:
   Net open position from conversions
   vs
   Executed hedge positions
   → Must net to < $100 residual exposure
```
