# FX Engine Ledger Flows

## Account Structure

### Chart of Accounts (FX-Specific)
| Code | Account Name | Type | Normal Balance |
|------|-------------|------|---------------|
| 1101 | Customer SYP Wallets | Asset | Debit |
| 1102 | Customer USD Wallets | Asset | Debit |
| 1104 | Customer EUR Wallets | Asset | Debit |
| 2101 | CFE Internal Clearing | Liability | Credit |
| 3102 | Beza FX Income | Revenue | Credit |
| 3103 | Beza FX Hedge Account | Contra-Expense | Credit |
| 4102 | Provider Settlement Payable | Liability | Credit |
| 5102 | FX Settlement Clearing | Asset | Debit |
| 6101 | FX Hedge Expense | Expense | Debit |

### Journal Entry Patterns

#### SYP → USD Conversion
```
SYP → USD Conversion (5,000,000 SYP → $334.78 USD, spread 150,000 SYP)
Timestamp: 2026-06-01T10:00:15Z
Reference: FX-CONV-ABC123XYZ

DR  1101  Customer SYP Wallets (User)      5,000,000  SYP
CR  1102  Customer USD Wallets (User)            334.78 USD
-- User SYP debited, USD credited

DR  5102  FX Settlement Clearing            5,000,000  SYP
CR  1101  Customer SYP Wallets (User)       5,000,000  SYP
-- SYP leg settlement match

DR  1102  Customer USD Wallets (User)             334.78 USD
CR  5102  FX Settlement Clearing                    334.78 USD
-- USD leg settlement match

DR  2101  CFE Internal Clearing             5,000,000  SYP
CR  5102  FX Settlement Clearing            5,000,000  SYP
-- CFE clearing (SYP)

DR  5102  FX Settlement Clearing                    334.78 USD
CR  2101  CFE Internal Clearing                       334.78 USD
-- CFE clearing (USD)

DR  5102  FX Settlement Clearing              150,000  SYP
CR  3102  Beza FX Income                      150,000  SYP
-- Spread revenue (2.6% of 5,000,000 SYP)
```

#### USD → EUR Conversion
```
USD → EUR Conversion ($500 USD → €455.83 EUR, spread 0.75% premium rate)
Timestamp: 2026-06-01T11:00:00Z
Reference: FX-CONV-DEF456UVW
Rate: 1 EUR = 1.097 USD (Beza rate)

DR  1102  Customer USD Wallets (User)             500.00 USD
CR  1104  Customer EUR Wallets (User)              455.83 EUR
-- User USD debited, EUR credited

DR  5102  FX Settlement Clearing                   500.00 USD
CR  1102  Customer USD Wallets (User)              500.00 USD
-- USD leg settlement

DR  1104  Customer EUR Wallets (User)              455.83 EUR
CR  5102  FX Settlement Clearing                   455.83 EUR
-- EUR leg settlement

DR  5102  FX Settlement Clearing                     3.75 USD
CR  3102  Beza FX Income                             3.75 USD
-- Spread revenue (0.75% of $500)
```

#### EUR → SYP Conversion (Diaspora Remittance)
```
EUR → SYP Conversion (€300 EUR → 4,866,000 SYP, spread 2.0% merchant rate)
Timestamp: 2026-06-01T12:00:00Z
Reference: FX-CONV-GHI789XYZ
Rate: 1 EUR = 16,220 SYP (Beza rate, mid 16,100 + 0.75% premium spread)

DR  1104  Customer EUR Wallets (User)             300.00 EUR
CR  1101  Customer SYP Wallets (User)          4,866,000  SYP
-- User EUR debited, SYP credited

DR  5102  FX Settlement Clearing                   300.00 EUR
CR  1104  Customer EUR Wallets (User)              300.00 EUR
-- EUR leg settlement

DR  1101  Customer SYP Wallets (User)          4,866,000  SYP
CR  5102  FX Settlement Clearing               4,866,000  SYP
-- SYP leg settlement

DR  5102  FX Settlement Clearing                   100,500  SYP
CR  3102  Beza FX Income                           100,500  SYP
-- Spread revenue (2.0% of 5,025,000 SYP equivalent at mid rate)
```

## FX Exposure Management

### Net Open Position Tracking
```sql
-- Daily FX exposure calculation
SELECT
    currency,
    SUM(CASE WHEN direction = 'buy' THEN amount ELSE -amount END) as net_position
FROM fx_conversions
WHERE created_at >= CURDATE()
GROUP BY currency;

-- Example output:
-- SYP: -15,000,000 (sold SYP, bought USD/EUR)
-- USD: +1,000 (bought USD)
-- EUR: +500 (bought EUR)
-- Net exposure: Short SYP 15M, Long USD $1K, Long EUR €500
```

### Daily Settlement Process
```
Step 1: At 23:59, calculate net FX positions:
  Total conversions today:
    SYP→USD: 50,000,000 SYP → $3,350 USD
    USD→SYP: $10,000 USD → 147,500,000 SYP
    SYP→EUR: 20,000,000 SYP → €1,250 EUR
    EUR→SYP: €5,000 EUR → 81,100,000 SYP
    USD→EUR: $2,000 USD → €1,823 EUR
  
  Net position:
    SYP: -70,000,000 + 147,500,000 + 81,100,000 = +158,600,000 (Long SYP)
    USD: +3,350 - 10,000 - 2,000 = -$8,650 (Short USD)
    EUR: +1,250 - 5,000 + 1,823 = -€1,927 (Short EUR)

Step 2: Hedge requirement:
  Net USD short: $8,650 → hedge via provider
  Net EUR short: €1,927 → hedge via provider
  Net SYP long: 158,600,000 → matched against float

Step 3: End-of-day settlement entry:
DR  5102  FX Settlement Clearing       158,600,000  SYP
CR  1101  Customer SYP Wallets (Net)    158,600,000  SYP
-- Net SYP position settled

CR  5102  FX Settlement Clearing              8,650  USD
DR  1102  Customer USD Wallets (Net)           8,650  USD
-- Net USD position settled

CR  5102  FX Settlement Clearing              1,927  EUR
DR  1104  Customer EUR Wallets (Net)           1,927  EUR
-- Net EUR position settled
```

## Reconciliation Checks
```
Daily FX Reconciliation (Automated, 02:30 AM):

1. Conversion Count Match:
   SELECT COUNT(*) FROM fx_conversions WHERE date = TODAY AND status = 'completed'
   vs
   SELECT COUNT(*) FROM cfe_postings WHERE type = 'fx_conversion' AND date = TODAY
   → Must match exactly

2. Volume Match:
   SELECT SUM(source_amount) FROM fx_conversions WHERE date = TODAY AND status = 'completed'
   GROUP BY source_currency
   vs
   CFE posting totals for same period
   → Must match within 0.01%

3. Spread Revenue Match:
   SELECT SUM(spread_amount) FROM fx_conversions WHERE date = TODAY AND status = 'completed'
   vs
   SELECT SUM(amount) FROM cfe_posting WHERE account = 'fx_income' AND date = TODAY
   → Must match within 1,000 SYP tolerance

4. Rate Lock Audit:
   SELECT COUNT(*) FROM fx_rate_locks WHERE created_at >= TODAY
   SELECT COUNT(*) FROM fx_rate_locks WHERE status = 'used' AND created_at >= TODAY
   → Lock usage rate should be > 60%

5. Hedge Position Match:
   Net open position from conversions
   vs
   Actual hedge positions from providers
   → Must match within $500 USD tolerance

Alert if any check fails → Slack #ops-fx
```
