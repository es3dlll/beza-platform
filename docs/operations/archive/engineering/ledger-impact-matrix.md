# Ledger Impact Matrix — Beza Platform

> **Last Updated:** 2026-05-29
> **Owner:** Finance & Core Financial Engineering
> **Scope:** All financial operations with double-entry ledger impact

---

## 1. Account Types (Chart of Accounts)

| Category        | Account Code Range | Examples                                                      |
| --------------- | ------------------ | ------------------------------------------------------------- |
| **Assets**      | 1xxxx              | User Wallets, Agent Float, Bank Accounts, Settlement Accounts |
| **Liabilities** | 2xxxx              | Biller Payable, Merchant Payable, Remittance Payable          |
| **Equity**      | 3xxxx              | Paid-in Capital, Retained Earnings                            |
| **Income**      | 4xxxx              | Fee Income, FX Income, Commission Income, Interest Income     |
| **Expenses**    | 5xxxx              | SMS Costs, API Costs, Settlement Fees, Bank Charges           |
| **Suspense**    | 6xxxx              | FX Suspense, Settlement Suspense, Unidentified Credits        |
| **Contra**      | 7xxxx              | Float Top-up (Contra), Fee Waiver (Contra)                    |

---

## 2. Operation Ledger Impact Matrix

### 2.1 P2P Transfers

| #   | Operation                   | Debit Account                   | Credit Account                  | Currency | Amount               | Timing      | CFE Event               | Txn Type                |
| --- | --------------------------- | ------------------------------- | ------------------------------- | -------- | -------------------- | ----------- | ----------------------- | ----------------------- |
| 1   | P2P Transfer (Principal)    | 10001 — Sender Wallet (Asset)   | 10002 — Receiver Wallet (Asset) | SYP      | transfer_amount      | Instant     | MoneyHeld → MoneyPosted | send_money              |
| 2   | P2P Transfer Fee            | 10001 — Sender Wallet (Asset)   | 40001 — Fee Income (Income)     | SYP      | fee (0.5% of amount) | Instant     | FeePosted               | send_money_fee          |
| 3   | P2P Transfer — Reversal     | 10002 — Receiver Wallet (Asset) | 10001 — Sender Wallet (Asset)   | SYP      | transfer_amount      | On reversal | ReversalPosted          | send_money_reversal     |
| 4   | P2P Transfer Fee — Reversal | 40001 — Fee Income (Income)     | 10001 — Sender Wallet (Asset)   | SYP      | fee                  | On reversal | ReversalFeePosted       | send_money_fee_reversal |

### 2.2 Agent Cash-In

| #   | Operation                  | Debit Account                      | Credit Account                     | Currency | Amount                   | Timing  | CFE Event             |
| --- | -------------------------- | ---------------------------------- | ---------------------------------- | -------- | ------------------------ | ------- | --------------------- |
| 5   | Cash-In (Principal)        | 10003 — Agent Float (Asset)        | 10001 — User Wallet (Asset)        | SYP      | cash_in_amount           | Instant | CashInCompleted       |
| 6   | Cash-In Fee (User)         | 10001 — User Wallet (Asset)        | 40002 — Commission Income (Income) | SYP      | fee (0.5%)               | Instant | FeePosted             |
| 7   | Cash-In Commission (Agent) | 40002 — Commission Income (Income) | 10003 — Agent Float (Asset)        | SYP      | agent_commission (0.25%) | Instant | AgentCommissionPosted |

### 2.3 Agent Cash-Out

| #   | Operation                   | Debit Account                      | Credit Account                     | Currency | Amount                  | Timing  | CFE Event             |
| --- | --------------------------- | ---------------------------------- | ---------------------------------- | -------- | ----------------------- | ------- | --------------------- |
| 8   | Cash-Out (Principal)        | 10001 — User Wallet (Asset)        | 10003 — Agent Float (Asset)        | SYP      | cash_out_amount         | Instant | CashOutCompleted      |
| 9   | Cash-Out Fee (User)         | 10001 — User Wallet (Asset)        | 40002 — Commission Income (Income) | SYP      | fee (1%)                | Instant | FeePosted             |
| 10  | Cash-Out Commission (Agent) | 40002 — Commission Income (Income) | 10003 — Agent Float (Asset)        | SYP      | agent_commission (0.5%) | Instant | AgentCommissionPosted |

### 2.4 FX Conversion

| #   | Operation          | Debit Account                   | Credit Account                  | Currency | Amount            | Timing              | CFE Event           |
| --- | ------------------ | ------------------------------- | ------------------------------- | -------- | ----------------- | ------------------- | ------------------- |
| 11  | FX — Debit Source  | 10001 — User SYP Wallet (Asset) | 60001 — FX Suspense (Suspense)  | SYP      | syp_amount        | Instant (rate lock) | RateLocked          |
| 12  | FX — Credit Target | 60001 — FX Suspense (Suspense)  | 10004 — User USD Wallet (Asset) | USD      | usd_equivalent    | Instant             | ConversionCompleted |
| 13  | FX Fee (Spread)    | 10001 — User SYP Wallet (Asset) | 40003 — FX Income (Income)      | SYP      | spread_fee (1.5%) | Instant             | FeePosted           |
| 14  | FX — Reversal      | 60001 — FX Suspense (Suspense)  | 10001 — User SYP Wallet (Asset) | SYP      | syp_amount        | On reversal         | ReversalPosted      |

### 2.5 Remittance (Outbound)

| #   | Operation                  | Debit Account                          | Credit Account                         | Currency | Amount         | Timing           | CFE Event           |
| --- | -------------------------- | -------------------------------------- | -------------------------------------- | -------- | -------------- | ---------------- | ------------------- |
| 15  | Remittance — Debit Sender  | 10001 — Sender Wallet (Asset)          | 20003 — Remittance Payable (Liability) | SYP      | sender_amount  | Instant          | RemittanceInitiated |
| 16  | Remittance — FX Conversion | 20003 — Remittance Payable (Liability) | 60001 — FX Suspense (Suspense)         | SYP      | syp_amount     | Instant          | RateLocked          |
| 17  | Remittance — FX Settlement | 60001 — FX Suspense (Suspense)         | 20003 — Remittance Payable (Liability) | USD      | usd_equivalent | Instant          | ConversionCompleted |
| 18  | Remittance — Payout to MTO | 20003 — Remittance Payable (Liability) | 10005 — Settlement Account (Asset)     | USD      | usd_amount     | Instant (or T+1) | RemittancePaidOut   |
| 19  | Remittance Fee             | 10001 — Sender Wallet (Asset)          | 40004 — Remittance Income (Income)     | SYP      | fee (2%)       | Instant          | FeePosted           |
| 20  | Remittance — Reversal      | 20003 — Remittance Payable (Liability) | 10001 — Sender Wallet (Asset)          | SYP      | sender_amount  | On failure       | RemittanceReversed  |

### 2.6 Bill Payments

| #   | Operation                   | Debit Account                      | Credit Account                                | Currency | Amount            | Timing    | CFE Event     |
| --- | --------------------------- | ---------------------------------- | --------------------------------------------- | -------- | ----------------- | --------- | ------------- |
| 21  | Bill Payment — Syriatel     | 10001 — User Wallet (Asset)        | 20001 — Biller Payable — Syriatel (Liability) | SYP      | bill_amount       | Instant   | BillPaid      |
| 22  | Bill Payment — PEED         | 10001 — User Wallet (Asset)        | 20002 — Biller Payable — PEED (Liability)     | SYP      | bill_amount       | Instant   | BillPaid      |
| 23  | Bill Payment — MTN          | 10001 — User Wallet (Asset)        | 20004 — Biller Payable — MTN (Liability)      | SYP      | bill_amount       | Instant   | BillPaid      |
| 24  | Bill Payment — Water        | 10001 — User Wallet (Asset)        | 20005 — Biller Payable — Water (Liability)    | SYP      | bill_amount       | Instant   | BillPaid      |
| 25  | Bill Payment Fee            | 10001 — User Wallet (Asset)        | 40005 — Bill Payment Income (Income)          | SYP      | fee (0.75%)       | Instant   | FeePosted     |
| 26  | Bill Settlement to Provider | 20001 — Biller Payable (Liability) | 10006 — Bank Account — Settlement (Asset)     | SYP      | accumulated_bills | Daily EOD | BillerSettled |

### 2.7 Merchant Payments

| #   | Operation            | Debit Account                        | Credit Account                            | Currency | Amount         | Timing           | CFE Event                |
| --- | -------------------- | ------------------------------------ | ----------------------------------------- | -------- | -------------- | ---------------- | ------------------------ |
| 27  | Merchant Payment     | 10001 — User Wallet (Asset)          | 20006 — Merchant Payable (Liability)      | SYP      | payment_amount | Instant          | MerchantPaymentCompleted |
| 28  | Merchant Payment Fee | 10001 — User Wallet (Asset)          | 40006 — Merchant Income (Income)          | SYP      | fee (0.5%)     | Instant          | FeePosted                |
| 29  | Merchant Settlement  | 20006 — Merchant Payable (Liability) | 10006 — Bank Account — Settlement (Asset) | SYP      | net_amount     | Daily EOD or T+1 | MerchantSettled          |

### 2.8 Payroll Disbursement

| #   | Operation                  | Debit Account                          | Credit Account                         | Currency | Amount                   | Timing            | CFE Event             |
| --- | -------------------------- | -------------------------------------- | -------------------------------------- | -------- | ------------------------ | ----------------- | --------------------- |
| 30  | Payroll — Debit Employer   | 10007 — Employer Account (Asset)       | 60002 — Settlement Suspense (Suspense) | SYP      | total_payroll            | Batch (scheduled) | PayrollBatchInitiated |
| 31  | Payroll — Credit Employees | 60002 — Settlement Suspense (Suspense) | 10001 — Employee Wallets (Asset)       | SYP      | individual_salaries      | Batch             | PayrollBatchCompleted |
| 32  | Payroll Fee                | 10007 — Employer Account (Asset)       | 40007 — Payroll Income (Income)        | SYP      | fee (0.25% per employee) | Batch             | FeePosted             |

### 2.9 Agent Float Management

| #   | Operation                    | Debit Account                   | Credit Account                            | Currency | Amount          | Timing   | CFE Event        |
| --- | ---------------------------- | ------------------------------- | ----------------------------------------- | -------- | --------------- | -------- | ---------------- |
| 33  | Float Top-Up — from Bank     | 10003 — Agent Float (Asset)     | 10006 — Bank Account — Settlement (Asset) | SYP      | topup_amount    | Same day | FloatToppedUp    |
| 34  | Float Transfer — Inter-Agent | 10003_A — Agent A Float (Asset) | 10003_B — Agent B Float (Asset)           | SYP      | transfer_amount | Instant  | FloatTransferred |

### 2.10 Settlement Operations

| #   | Operation             | Debit Account                             | Credit Account                            | Currency | Amount         | Timing         | CFE Event                |
| --- | --------------------- | ----------------------------------------- | ----------------------------------------- | -------- | -------------- | -------------- | ------------------------ |
| 35  | Settlement — Agent    | 20007 — Agent Payable (Liability)         | 10006 — Bank Account — Settlement (Asset) | SYP      | net_settlement | Daily EOD      | SettlementBatchCompleted |
| 36  | Settlement — Merchant | 20006 — Merchant Payable (Liability)      | 10006 — Bank Account — Settlement (Asset) | SYP      | net_settlement | Daily EOD      | SettlementBatchCompleted |
| 37  | Settlement — Biller   | 20001-20005 — Biller Payables (Liability) | 10006 — Bank Account — Settlement (Asset) | SYP      | accumulated    | Daily EOD      | BillerSettled            |
| 38  | Settlement — Bank Fee | 50001 — Bank Fee Expense (Expense)        | 10006 — Bank Account — Settlement (Asset) | SYP      | bank_fee       | Per settlement | BankFeePosted            |

### 2.11 System / Corrections

| #   | Operation                   | Debit Account                              | Credit Account                             | Currency | Amount            | Timing | CFE Event        |
| --- | --------------------------- | ------------------------------------------ | ------------------------------------------ | -------- | ----------------- | ------ | ---------------- |
| 39  | Balance Correction (Credit) | 60003 — Suspense — Unidentified (Suspense) | 10001 — User Wallet (Asset)                | SYP      | correction_amount | Manual | ManualCorrection |
| 40  | Balance Correction (Debit)  | 10001 — User Wallet (Asset)                | 60003 — Suspense — Unidentified (Suspense) | SYP      | correction_amount | Manual | ManualCorrection |
| 41  | Fee Waiver                  | 40001-40007 — Fee Income (Income)          | 10001 — User Wallet (Asset)                | SYP      | waived_fee        | Manual | FeeWaived        |
| 42  | Journal Entry (Arbitrary)   | Any account                                | Any account                                | SYP      | amount            | Manual | ManualJournal    |

---

## 3. Fund Flow State Machine

```
                           ┌─────────────┐
                           │  Available   │
                           │  (Source)    │
                           └──────┬──────┘
                                  │ Hold
                                  ▼
                           ┌─────────────┐
                     ┌─────│   Held in   │─────┐
                     │     │  Suspense   │     │
                     │     └─────────────┘     │
                     │ Post                    │ Release
                     ▼                         ▼
              ┌─────────────┐          ┌─────────────┐
              │   Credited   │          │   Released   │
              │  (Target)    │          │   (Source)   │
              └─────────────┘          └─────────────┘
```

---

## 4. Fee Schedule Summary

| Fee Type                    | Rate      | Cap (SYP)    | Payor    | Recipient | Frequency      |
| --------------------------- | --------- | ------------ | -------- | --------- | -------------- |
| P2P Transfer                | 0.5%      | 5,000        | Sender   | Platform  | Per txn        |
| Agent Cash-In               | 0.5%      | 2,500        | User     | Platform  | Per txn        |
| Agent Cash-Out              | 1.0%      | 5,000        | User     | Platform  | Per txn        |
| FX Spread                   | 1.5%      | —            | User     | Platform  | Per conversion |
| FX Conversion               | 0.5%      | 3,000        | User     | Platform  | Per txn        |
| Remittance                  | 2.0%      | 10,000       | Sender   | Platform  | Per remittance |
| Bill Payment                | 0.75%     | 2,000        | User     | Platform  | Per bill       |
| Merchant Payment            | 0.5%      | 3,000        | User     | Platform  | Per txn        |
| Payroll                     | 0.25%/emp | 50,000 total | Employer | Platform  | Per batch      |
| Agent Commission (Cash-In)  | 0.25%     | —            | Platform | Agent     | Per txn        |
| Agent Commission (Cash-Out) | 0.5%      | —            | Platform | Agent     | Per txn        |

---

## 5. Daily Settlement Summary

### 5.1 End-of-Day Process Flow

```
23:30 — Cut-off: No new transactions accepted (grace period)
23:35 — Batch calculation begins
        → Aggregate all biller payables (Syriatel, MTN, PEED, Water)
        → Aggregate all merchant payables
        → Calculate net agent settlement positions
        → Calculate net platform fee income
23:45 — CFE settlement batch posted to ledger
23:50 — Settlement files generated (CSV for each bank)
23:55 — SFTP transfer to BSO, Bemo, SIIB
00:30 — Reconciliation with bank statements (next day)
```

### 5.2 Daily Settlement Accounts

| Settlement Account              | Bank      | Currency | Purpose               | Sweep Frequency |
| ------------------------------- | --------- | -------- | --------------------- | --------------- |
| 10006-001 — BSO Settlement      | BSO Bank  | SYP      | Main settlement       | Daily EOD       |
| 10006-002 — Bemo Settlement     | Bemo Bank | SYP      | Secondary settlement  | Daily EOD       |
| 10006-003 — SIIB USD Settlement | SIIB      | USD      | Remittance settlement | Daily EOD       |
| 10006-004 — SIIB SYP Settlement | SIIB      | SYP      | Biller settlement     | Daily EOD       |
| 10006-005 — BSO Fee Account     | BSO Bank  | SYP      | Fee collection        | Weekly          |

---

## 6. Reconciliation Rules

| Rule ID | Check                                                               | Frequency        | Tolerance  | Action if Breached                |
| ------- | ------------------------------------------------------------------- | ---------------- | ---------- | --------------------------------- |
| REC-001 | CFE ledger balance = SUM(user wallets)                              | Every 15min      | ±0 SYP     | Alert ops team, halt transactions |
| REC-002 | SUM(agent floats) = CFE agent float account                         | Hourly           | ±0 SYP     | Flag for investigation            |
| REC-003 | Daily biller payable = SUM(bill payments) — SUM(biller settlements) | Daily            | ±100 SYP   | Hold settlement until resolved    |
| REC-004 | FX suspense balance = 0 at EOD                                      | Daily            | ±500 SYP   | Flag for FX reconciliation        |
| REC-005 | Settlement account transactions match bank statement                | Daily            | ±1,000 SYP | Manual reconciliation required    |
| REC-006 | Fee income = SUM(fees charged) — SUM(fees waived)                   | Daily            | ±10 SYP    | Investigate fee discrepancy       |
| REC-007 | Daily txn count matches CFE journal entries                         | Daily            | 0          | System integrity check            |
| REC-008 | Agent float + sum(cash-in) — sum(cash-out) = expected float         | Per agent, daily | ±2,000 SYP | Flag agent for float mismatch     |
| REC-009 | Total assets = total liabilities + equity                           | Daily            | ±500 SYP   | Balance sheet integrity           |

---

## 7. Accounting Periods & Closing

| Period          | Frequency       | Description                                                             |
| --------------- | --------------- | ----------------------------------------------------------------------- |
| Daily Close     | Every day 00:15 | Lock daily transactions, post settlement, generate daily GL summary     |
| Weekly Close    | Every Sunday    | Generate weekly P&L, MTD revenue run-rate                               |
| Monthly Close   | 1st of month    | Full accounting close: accruals, deferrals, month-end GL, CBS reporting |
| Quarterly Close | Apr/Jul/Oct/Jan | Quarterly financial statements, board report, CBS quarterly return      |
| Annual Close    | Jan 31          | Full audit, annual financial statements, tax filing, CBS annual return  |

---

## 8. GL Account Balance Monitoring

| Account                    | Expected Direction | Alert Threshold             | Action on Breach                         |
| -------------------------- | ------------------ | --------------------------- | ---------------------------------------- |
| User Wallets (Asset)       | Always positive    | Any negative                | Immediate investigation, halt operations |
| Agent Float (Asset)        | Always positive    | Any negative                | Freeze agent, investigate                |
| Biller Payable (Liability) | ≥ 0                | > 50M SYP                   | Review settlement capacity               |
| FX Suspense (Suspense)     | ~0 at EOD          | > 1M SYP                    | Manual FX reconciliation                 |
| Fee Income (Income)        | Increasing MoM     | Negative growth             | Revenue analysis                         |
| Bank Account (Asset)       | Positive           | < monthly settlement amount | Fund bank account                        |

---

_End of Ledger Impact Matrix. 42 operations, 40+ GL account types, 9 reconciliation rules documented._
