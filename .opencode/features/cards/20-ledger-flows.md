# Cards Ledger Flows

## Account Structure

### Chart of Accounts (Cards-Specific)
| Code | Account Name | Type | Normal Balance |
|------|-------------|------|---------------|
| 1201 | Card Wallets SYP | Asset | Debit |
| 1202 | Card Wallets USD | Asset | Debit |
| 1203 | Card Settlement Clearing | Asset | Debit |
| 1204 | Interchange Receivable | Asset | Debit |
| 2201 | Card Hold Clearing | Liability | Credit |
| 3201 | Card Fee Income | Revenue | Credit |
| 3202 | Card Interchange Income | Revenue | Credit |
| 3203 | Card FX Income | Revenue | Credit |
| 4201 | Card Processor Payable | Liability | Credit |
| 4202 | BIN Sponsor Payable | Liability | Credit |
| 5201 | Card Personalization Expense | Expense | Debit |
| 5202 | Card SMS Expense | Expense | Debit |

### Journal Entry Patterns

#### Card Authorization (Hold)
```
Card Purchase Auth (125,000 SYP)
Timestamp: 2026-06-15T18:30:00Z
Reference: AUTH-ABC123

DR  1201  Card Wallets SYP              125,000
CR  2201  Card Hold Clearing             125,000
-- Funds held pending settlement
```

#### Card Purchase Settlement
```
Card Purchase Settlement (125,000 SYP)
Timestamp: 2026-06-15T23:00:00Z
Reference: SETTLE-BATCH-001

DR  2201  Card Hold Clearing             125,000
CR  1201  Card Wallets SYP              125,000
-- Release hold, finalize debit

DR  1203  Card Settlement Clearing        125,000
CR  1201  Card Wallets SYP              125,000
-- Settlement clearing entry

CR  4201  Card Processor Payable           1,500
DR  3202  Card Interchange Income          1,500
-- Interchange accrual (1.2%)
```

#### Card Issuance Fee
```
Virtual Card Issuance (5,000 SYP fee)
Timestamp: 2026-06-01T10:00:00Z
Reference: CARD-ISS-001

DR  1101  Customer Main Wallet SYP       5,000
CR  1201  Card Wallets SYP               5,000
-- Fee transferred from main to card wallet

CR  3201  Card Fee Income                 5,000
DR  1201  Card Wallets SYP               5,000
-- Fee revenue recognition
```

#### ATM Withdrawal
```
ATM Withdrawal (50,000 SYP, fee 2,250 SYP)
Timestamp: 2026-06-16T09:00:00Z
Reference: ATM-12345

DR  1201  Card Wallets SYP               52,250
CR  2201  Card Hold Clearing             52,250
-- Hold: principal + estimated fee

[Settlement]
DR  2201  Card Hold Clearing             52,250
CR  1201  Card Wallets SYP              52,250
-- Release hold

DR  1203  Card Settlement Clearing        50,000
CR  1201  Card Wallets SYP              50,000
-- Settlement to ATM acquirer

CR  3201  Card Fee Income                 2,250
DR  4201  Card Processor Payable           250
-- Fee split: Beza 2,000, Processor 250
```

#### One-Time Card Unused Refund
```
One-Time Card Expiry (75,000 SYP unused)
Timestamp: 2026-06-18T10:00:00Z
Reference: OTC-REF-001

DR  2201  Card Hold Clearing              75,000
CR  1201  Card Wallets SYP               75,000
-- Release hold on unused one-time card

DR  1201  Card Wallets SYP               75,000
CR  1101  Customer Main Wallet SYP       75,000
-- Return funds to main wallet
```

## Daily Settlement Posting

### End of Day Batch
```
Daily Settlement (2026-06-15)
Total Card Purchases: 2,500,000 SYP
Total ATM Withdrawals: 500,000 SYP
Total Refunds: 50,000 SYP
Interchange Earned: 30,000 SYP
ATM Fees Earned: 22,500 SYP

Net: DR Card Wallets 2,972,500 SYP
     CR Various Accounts 2,972,500 SYP

Entry:
DR  2201  Card Hold Clearing (released)    3,000,000
CR  1201  Card Wallets SYP                          3,000,000
-- All holds released

DR  1201  Card Wallets SYP                           2,972,500
CR  1203  Card Settlement Clearing                    2,950,000
CR  3201  Card Fee Income                               22,500
-- Net settlement after fees

DR  1203  Card Settlement Clearing      2,950,000
CR  4201  Card Processor Payable           30,000
CR  4202  BIN Sponsor Payable              15,000
CR  1101  Customer Main Wallet SYP      2,905,000
-- Distribution: processor fee, sponsor fee, merchant/ATM settlement
```
