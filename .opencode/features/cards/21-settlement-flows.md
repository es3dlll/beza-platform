# Cards Settlement Flows

## Settlement Types

### Daily Clearing (Local Switch)
```
Trigger: End of day (23:59 daily)
Scope: All local card transactions routed through Syrian switch
Mechanism:
  1. Switch sends clearing file (ISO 8583 0420) at 23:59
  2. CardProcessor matches auth records with clearing records
  3. Calculates interchange fees per transaction
  4. Generates net settlement report
Timeline:
  T+0 23:59 — Clearing file received
  T+1 01:00 — Auth-clearing reconciliation
  T+1 02:00 — Net settlement positions calculated
  T+1 03:00 — Settlement file sent to CFE for posting
```

### International Settlement (BIN Sponsor)
```
Trigger: Daily batch via BIN sponsorship partner
Scope: International transactions routed through sponsor's network (Mastercard/Visa)
Mechanism:
  1. Sponsor sends daily settlement report (ISO 20022 XML or CSV)
  2. CardProcessor imports report, matches internal auth records
  3. Calculates: interchange earned, sponsor fees, FX spread
  4. Net funding received from sponsor (T+1) or sent (T+2)
Timeline:
  T+0 — Transactions occur (real-time auth)
  T+1 06:00 — Sponsor sends settlement report
  T+1 08:00 — Reconciliation complete
  T+1 12:00 — Sponsor nets settles (receives net from sponsor)
  T+2 — Full reconciliation with CFE
```

### ATM Settlement
```
Trigger: Daily batch per ATM acquirer
Scope: ATM withdrawals at participating bank ATMs
Mechanism:
  1. ATM acquirer sends daily settlement
  2. CardProcessor matches ATM auths with acquirer file
  3. Calculates: ATM fees (2,000 SYP + 0.5%), acquirer fees
  4. Net transfer to acquirer for cash dispensed
Fee Split:
  Beza: 2,000 SYP + 0.25% (caller's portion)
  Acquirer: 0.25% (ATM owner)
Timeline:
  T+0 — ATM transactions throughout day
  T+1 01:00 — Acquirer sends file
  T+1 03:00 — Settlement posted
```

## Settlement Reconciliation

### Auth-Clearing Matching
```
Matching Rules:
  1. Exact match: All fields (PAN, amount, date, auth code, STAN) → auto-settle
  2. Amount mismatch (< 5%): Flag for review, auto-approve if within tolerance
  3. Amount mismatch (> 5%): Hold for manual reconciliation
  4. Missing auth in DB but present in clearing:
     → Possible: switch approved, Beza didn't receive auth
     → Action: Create transaction record, post settlement
  5. Missing clearing for auth:
     → Possible: auth was reversed before settlement
     → Action: Release hold after 7 days if no clearing received

Unmatched Handling:
  - Auth without clearing (3 days): Auto-release hold
  - Clearing without auth (same day): Investigate with switch
  - Amount difference < 5%: Auto-resolve with tolerance
  - Amount difference >= 5%: Manual review queue
```

### Settlement File Format (Internal)
```csv
SETTLEMENT_DATE,TRANSACTION_ID,CARD_ID,TYPE,AMOUNT,CURRENCY,FEE,INTERCHANGE,STATUS
2026-06-15,TXN-001,1,purchase,125000,SYP,0,1500,settled
2026-06-15,TXN-002,1,purchase,75000,SYP,0,900,settled
2026-06-15,TXN-003,2,atm,50000,SYP,2250,0,settled
2026-06-15,TXN-004,1,refund,-25000,SYP,0,-300,settled
```

## Settlement Schedule
```
Hour    Activity
00:00  — Local switch clearing file received
01:00  — Auth-clearing reconciliation
02:00  — Interchange/fee calculation
03:00  — Net settlement posting to CFE
04:00  — Settlement report generated
06:00  — International sponsor report received
08:00  — International reconciliation
12:00  — International net settlement
23:59  — Next day's clearing file generated
```
