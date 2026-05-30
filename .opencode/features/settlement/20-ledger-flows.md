# Settlement Ledger Flows

## CFE Ledger Integration

Every settlement event generates double-entry journal entries in the CFE (Central Financial Engine) ledger. The following accounts are used:

### Settlement Accounts
```
Account                          Type         Description
──────────────────────────────────────────────────────────────────
cfe_acc_cfe_main                 Asset        Beza CFE Central Ledger (master account)
cfe_acc_int_settlement           Liability    Settlement Pool — holds funds for pending settlements
cfe_acc_bank_bsf                 Liability    Bemo Saudi Fransi settlement account
cfe_acc_bank_bos                 Liability    Bank of Syria settlement account
cfe_acc_bill_syriatel            Asset        Syriatel biller settlement (receivable)
cfe_acc_bill_mtn                 Asset        MTN biller settlement (receivable)
cfe_acc_merch_default            Liability    Merchant settlement pool
cfe_acc_agent_default            Asset        Agent settlement pool (receivable)
cfe_acc_income_bank_charges      Income       Bank transaction fees
cfe_acc_expense_write_off        Expense      Settlement write-offs
cfe_acc_income_settlement_fee    Income       Settlement processing fees
```

## Ledger Flow 1: EOD Batch — Fund Settlement Pool

```
When: Batch processing begins
Journal: Transfer from CFE main ledger to settlement pool

  DR: CFE Central Ledger (cfe_acc_cfe_main)       34,000,000 SYP
  CR: Settlement Pool (cfe_acc_int_settlement)     34,000,000 SYP

  Explanation: Transfer funds from CFE to settlement pool for EOD payouts
  Reference: STL-20260529-0001-POOL
```

## Ledger Flow 2: EOD Batch — Collect Receivables

```
When: Netting calculates entities that owe Beza

  DR: Syriatel Account (cfe_acc_bill_syriatel)     12,500,000 SYP
  CR: Settlement Pool (cfe_acc_int_settlement)      12,500,000 SYP

  DR: MTN Account (cfe_acc_bill_mtn)                6,000,000 SYP
  CR: Settlement Pool (cfe_acc_int_settlement)       6,000,000 SYP

  DR: Agent Account (cfe_acc_agent_default)          3,500,000 SYP
  CR: Settlement Pool (cfe_acc_int_settlement)       3,500,000 SYP

  Reference: STL-20260529-0001-COLLECT
  Settlement Pool after collect: 34,000,000 + 12,500,000 + 6,000,000 + 3,500,000 = 56,000,000 SYP
```

## Ledger Flow 3: EOD Batch — Pay Out

```
When: Payment orders transmitted and confirmed

  DR: Settlement Pool (cfe_acc_int_settlement)      45,000,000 SYP
  CR: Bemo Saudi Fransi (cfe_acc_bank_bsf)          45,000,000 SYP

  DR: Settlement Pool (cfe_acc_int_settlement)      11,000,000 SYP
  CR: Merchant Pool (cfe_acc_merch_default)          11,000,000 SYP

  Reference: STL-20260529-0001-PAY
  Settlement Pool after pay: 56,000,000 - 45,000,000 - 11,000,000 = 0 SYP ✓
```

## Ledger Flow 4: Real-Time Settlement

```
When: Single transaction settled in real-time

  DR: CFE Central Ledger (cfe_acc_cfe_main)         100,000 SYP
  CR: Settlement Pool (cfe_acc_int_settlement)       100,000 SYP
  (Transfer to pool)

  DR: Settlement Pool (cfe_acc_int_settlement)       100,000 SYP
  CR: Merchant Account (cfe_acc_merch_default)       100,000 SYP
  (Pay merchant immediately)

  Reference: RT-20260529-0001
```

## Ledger Flow 5: Exception Adjustment (Bank Charges)

```
When: Exception resolved — bank deducted fee

  DR: Bank Charges Income (cfe_acc_income_bank_charges)   5,000 SYP
  CR: Settlement Pool (cfe_acc_int_settlement)             5,000 SYP

  Reference: EXC-001-RESOLUTION
```

## Ledger Flow 6: Write-Off

```
When: Small difference written off as operational cost

  DR: Write-Off Expense (cfe_acc_expense_write_off)       500 SYP
  CR: Settlement Pool (cfe_acc_int_settlement)             500 SYP

  Reference: EXC-002-WRITEOFF
```

## Settlement Pool Balance Tracking

```
End of Day Settlement Pool Balance:

  Opening Balance:                                     0 SYP
  + Transfer from CFE to Pool (EOD)          34,000,000 SYP
  + Collect from Receivables (Biller/Agent)  22,000,000 SYP
  - Pay to Payables (Bank/Merchant)         -56,000,000 SYP
  = Closing Balance                                   0 SYP ✓

  + Real-time settlements (throughout day)     X,XXX,XXX SYP
  - Real-time payouts                          X,XXX,XXX SYP
  = End of Day Settlement Pool Balance                 0 SYP ✓
```

## Journal Entry Validation Rules
```
1. Every journal entry must have equal DR and CR amounts
2. Every journal entry must reference a settlement batch
3. Settlement pool must always balance to 0 at end of cut-off cycle
4. Real-time transactions must complete both phases atomically
5. Adjustment entries must reference exception ID
6. Write-off entries require approval (configurable threshold)
```
