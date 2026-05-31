# Wallet Settlement Flows

## Settlement Types

### Instant Settlement (Default)
```
Trigger: Every completed P2P transfer
Mechanism: Real-time double-entry posting
Recipient: Available immediately for use
Reverse: Standard CFE reversal within 24h
Ledger: Immediate debit/credit on wallet accounts
```

### Batch Settlement
```
Trigger: End of day (23:59 daily)
Scope: All merchant transactions, agent commissions, biller payments
Mechanism: Net position calculation → single journal entry
Execution: Automated cron job
Confirmation: Settlement report generated at 00:30 daily
```

### Agent Settlement
```
Trigger: Real-time per transaction
Float Model: Agent pre-funds float → deducts on cash-out, credits on cash-in
Commission: Accrued per transaction, settled T+1
Settlement: Net amount transferred to agent's linked wallet
```

## Settlement Flow (Agent Daily)

```
Agent Ahmed's Day:
  Starting Float: 500,000 SYP
  
  09:00 — Cash-in: Ahmad deposits 50,000 → Agent float: 550,000
  09:30 — Cash-out: Fatima withdraws 30,000 → Agent float: 520,000
  10:00 — Cash-in: Khalid deposits 100,000 → Agent float: 620,000
  11:00 — Cash-out: Omar withdraws 200,000 → Agent float: 420,000
  ...
  End of Day:
    Total Cash-in: 500,000 SYP
    Total Cash-out: 350,000 SYP
    Commissions Earned: 5,000 SYP (cash-out 1%, cash-in 0.5%)
    
  Settlement:
    Net Float Change: +150,000 SYP (500,000 → 650,000)
    Commission Settlement (T+1): 5,000 SYP credited to Agent Wallet
```

## Settlement Flow (Merchant Daily)

```
Merchant Al-Sham Supermarket:
  Total QR Sales: 250,000 SYP
  MDR: 1.5% = 3,750 SYP
  Net Settlement: 246,250 SYP
  
  Settlement Entry (EOD Batch):
    DR  Merchant Settlement Account  250,000
    CR  Merchant Wallet              246,250
    CR  Beza MDR Income                3,750
  
  Payout: Next business day to merchant's bank account
```

## Reconciliation

### Daily Reconciliation
```
1. Match all CFE postings to wallet_transactions
2. Verify total debits = total credits + fees
3. Check agent float balances against expected
4. Check merchant settlement amounts against expected
5. Generate exception report for mismatches
```

### Exception Handling
```
Mismatch < 10,000 SYP: Auto-adjust with memo
Mismatch 10,000-100,000 SYP: Flag for manual review, notify ops
Mismatch > 100,000 SYP: Halt settlement, notify finance + engineering
```
