# Beza Platform — Sequence Diagrams

> Version: 1.0 | Last Updated: 2026-05-29

---

## Wallet Transfer Flow (Happy Path)

```
User        Flutter App      API Gateway     TransferService     CFE         Recipient
 │              │                │                │              │             │
 │── Send ─────>│                │                │              │             │
 │              │── POST /send ─>│                │              │             │
 │              │                │── Auth Check ─>│              │             │
 │              │                │                │── Hold ─────>│             │
 │              │                │                │<── Held ─────│             │
 │              │                │                │── Fraud ────>│             │
 │              │                │                │<── Allow ────│             │
 │              │                │                │── Post ─────>│             │
 │              │                │                │<── Posted ───│             │
 │              │                │                │── Release ──>│             │
 │              │                │                │── Event ────────────────>│
 │<── 200 OK ───│<── Response ───│<── Done ──────│              │             │
 │              │                │                │              │             │
```

## Wallet Transfer Flow (Failure — Fraud Block)

```
User        Flutter App      API Gateway     TransferService     CFE         Fraud
 │              │                │                │              │           │
 │── Send ─────>│                │                │              │           │
 │              │── POST /send ─>│                │              │           │
 │              │                │── Auth Check ─>│              │           │
 │              │                │                │── Hold ─────>│           │
 │              │                │                │<── Held ─────│           │
 │              │                │                │── Fraud ────>│           │
 │              │                │                │              │── Score ─>│
 │              │                │                │<── Block ────│           │
 │              │                │                │── Release ──>│           │
 │              │                │                │<── Released ─│           │
 │<── 403 Fraud─│<── Error ──────│<── Blocked ───│              │           │
 │              │                │                │              │           │
```

## Agent Cash-In (Online)

```
User (Payer)   Flutter App      Agent Device      Wallet API     Ledger      Notification
 │                │                │                │              │            │
 │                │── Scan QR ────>│                │              │            │
 │                │                │── Confirm ────>│              │            │
 │                │                │                │── Hold ─────>│            │
 │                │                │                │<── Held ─────│            │
 │                │                │                │── Journal ──>│            │
 │── PIN ────────>│                │                │              │            │
 │                │                │                │── Credit ────│            │
 │                │                │                │── Debit Agent│            │
 │                │                │                │<── Done ─────│            │
 │                │                │<── Success ────│              │            │
 │<── Receipt ────│<── Receipt ────│                │── Notify ────────────────>│
 │                │                │                │              │            │
```

## Agent Cash-In (Offline — Voucher Flow)

```
User (Payer)   Flutter App      Agent Device      Agent App (Offline)       Sync Later
 │                │                │                      │                    │
 │                │── Cash Req ───>│                      │                    │
 │                │                │── Generate Voucher ─>│                    │
 │                │                │<── Voucher Code ─────│                    │
 │── Pay Cash ────│                │                      │                    │
 │                │                │── Print/Mark Used ──>│                    │
 │<── Voucher ────│<── Voucher ────│                      │                    │
 │                │                │                      │                    │
 │                │                │                      │── (reconnect) ────>│
 │                │                │                      │── RedeemVoucher ──>│
 │                │                │                      │<── Confirmed ──────│
 │                │                │                      │                    │
```

## FX Rate Lock → Conversion

```
User        Flutter App      TransferService     FX Service       Remittance    Wallet
 │              │                │                  │                │            │
 │── Enter ────>│                │                  │                │            │
 │   Amount     │── Quote Req ──>│                  │                │            │
 │              │                │── GetQuote ─────>│                │            │
 │              │                │<── Quote ────────│                │            │
 │<── Show ─────│<── Rate ───────│                  │                │            │
 │   Rate       │                │                  │                │            │
 │              │                │                  │                │            │
 │── Accept ───>│                │                  │                │            │
 │   Rate       │── Lock Req ───>│                  │                │            │
 │              │                │── LockRate ─────>│                │            │
 │              │                │                  │── Earmark ────────────────>│
 │              │                │<── FXLocked ─────│                │            │
 │<── Locked ───│<── Confirmed ──│                  │                │            │
 │              │                │                  │                │            │
 │              │ (Transfer completes later)        │                │            │
 │              │                │── SettleFX ─────>│                │            │
 │              │                │                  │── Journal ───────────────>│
 │              │                │                  │── Post FX ───────────────>│
 │              │                │<── FXSettled ────│                │            │
 │              │                │                  │                │            │
```

## Remittance (Diaspora → Syria)

```
Sender (Diaspora)   Flutter App    RemittanceService    FX    Compliance    Recipient (Syria)
 │                     │                 │              │         │              │
 │── Send to Syria ───>│                 │              │         │              │
 │                     │── Create ──────>│              │         │              │
 │                     │                 │── Screen ────────────>│              │
 │                     │                 │<── Clear ─────────────│              │
 │                     │                 │── RequestRate ───────>│              │
 │                     │                 │<── FXLocked ──────────│              │
 │                     │                 │── Calc Total ────────│              │
 │<── Show Summary ────│<── Summary ─────│                       │              │
 │                     │                 │                       │              │
 │── Confirm Payment ──>│                 │                       │              │
 │                     │── Fund ────────>│                       │              │
 │                     │                 │── Hold Sender ────────│              │
 │                     │                 │── Queue Payout ──────│              │
 │                     │                 │── Notify Recipient ─────────────────>│
 │<── Receipt ─────────│<── Sent ────────│                       │              │
 │                     │                 │                       │              │
 │                     │                 │  (Recipient visits agent)            │
 │                     │                 │                       │              │
 │                     │                 │<── Claim Code ───────────────────────│
 │                     │                 │── Release Payout ────│              │
 │                     │                 │── Complete ─────────│              │
 │                     │                 │── Notify Sender ────│              │
 │<── Delivered ───────│<── Completed ───│                       │              │
 │                     │                 │                       │              │
```

## Merchant QR Payment

```
Customer     Flutter App      Merchant POS     PaymentService     Wallet     Ledger
 │              │                │                  │              │          │
 │              │── Scan QR ────>│                  │              │          │
 │              │                │── Amount + Ref ─>│              │          │
 │              │                │                  │── Auth ──────│          │
 │── Confirm ───>│                │                  │              │          │
 │   PIN         │                │                  │── Hold ─────>│          │
 │              │                │                  │<── Held ─────│          │
 │              │                │                  │── FraudCheck─│          │
 │              │                │                  │── Post ─────>│          │
 │              │                │                  │              │── Debit ─>│
 │              │                │                  │              │── Credit >│
 │              │                │                  │<── Posted ───│          │
 │              │                │                  │── Release ──>│          │
 │              │                │<── Success ──────│              │          │
 │<── Receipt ──│<── Receipt ────│                  │              │          │
 │              │                │                  │              │          │
```

## Savings Auto-Save

```
User        Flutter App      SavingsService     WalletService     Scheduler    Notification
 │              │                  │                  │              │             │
 │── Create ───>│                  │                  │              │             │
 │   Goal       │── CreateGoal ───>│                  │              │             │
 │              │                  │── InitWallet ──────────────>│              │
 │              │                  │<── WalletReady ─────────────│              │
 │<── Goal ─────│<── GoalCreated ──│                  │              │             │
 │   Created    │                  │                  │              │             │
 │              │                  │                  │              │             │
 │── Set ──────>│                  │                  │              │             │
 │   Auto-Save  │── SetRule ──────>│                  │              │             │
 │              │                  │── Schedule ──────────────────>│             │
 │<── Active ───│<── Rule Set ─────│                  │              │             │
 │              │                  │                  │              │             │
 │              │                  │  (Schedule triggers)           │             │
 │              │                  │                  │              │── Trigger ─>│
 │              │                  │<── AutoSave ─────│              │             │
 │              │                  │── Transfer ───────────────>│              │
 │              │                  │<── Transferred ──────────────│             │
 │              │                  │── Check Goal ───│              │             │
 │              │                  │── Notify ───────────────────────────────────>│
 │<── Updated ──│<── Progress ─────│                  │              │             │
 │              │                  │                  │              │             │
 │              │  (When goal reached)                │              │             │
 │              │                  │── GoalComplete ──│              │             │
 │              │                  │── Celebrate ────────────────────────────────>│
 │<── 🎉 ───────│<── Completed ────│                  │              │             │
 │              │                  │                  │              │             │
```

## Financing Disbursement

```
Borrower     Flutter App      LoanService       WalletService     Ledger     Repayment
 │              │                  │                  │              │           │
 │── Apply ────>│                  │                  │              │           │
 │              │── SubmitApp ────>│                  │              │           │
 │              │                  │── RiskEval ─────│              │           │
 │<── Under ────│<── Pending ──────│                  │              │           │
 │   Review     │                  │                  │              │           │
 │              │  (Risk engine approves)             │              │           │
 │              │                  │── Approve ──────│              │           │
 │<── Approved ─│<── Approved ─────│                  │              │           │
 │              │                  │                  │              │           │
 │── Accept ───>│                  │                  │              │           │
 │              │── AcceptTerms ──>│                  │              │           │
 │              │                  │── Disburse ─────>│              │           │
 │              │                  │                  │── PostLoan ──>│           │
 │              │                  │                  │── Credit ────│           │
 │              │                  │                  │── DeductFee ─│           │
 │              │                  │                  │<── Done ─────│           │
 │              │                  │                  │              │           │
 │              │                  │── CreateSchedule──────────────────────────>│
 │<── Funded ───│<── Disbursed ────│                  │              │           │
 │              │                  │                  │              │           │
 │              │  (Repayment cycle)                  │              │           │
 │              │                  │                  │              │── Due ───>│
 │── Pay ──────>│                  │                  │              │           │
 │              │── MakePayment ──>│                  │              │           │
 │              │                  │── Hold ─────────>│              │           │
 │              │                  │── Receive ──────│              │           │
 │              │                  │                  │── PostReceipt>│           │
 │<── Receipt ──│<── Paid ────────│                  │              │           │
 │              │                  │                  │              │           │
```

## Bill Payment

```
User        Flutter App      BillPaymentService    WalletService    BillerGateway   Scheduler
 │              │                    │                  │               │             │
 │── Select ───>│                    │                  │               │             │
 │   Biller     │── Get Billers ────>│                  │               │             │
 │<── List ─────│<── Billers ────────│                  │               │             │
 │              │                    │                  │               │             │
 │── Enter ────>│                    │                  │               │             │
 │   Details    │── Initiate ───────>│                  │               │             │
 │              │                    │── Hold ─────────>│               │             │
 │              │                    │<── Held ─────────│               │             │
 │              │                    │── Submit ──────────────────────────────────>│
 │── Confirm ──>│                    │                  │               │             │
 │   PIN        │── ConfirmPay ─────>│                  │               │             │
 │              │                    │                  │               │── Pay ─────>│
 │              │                    │                  │               │<── Receipt ─│
 │              │                    │── Deduct ───────>│               │             │
 │              │                    │                  │── Journal ───│             │
 │<── Receipt ──│<── Paid ──────────│                  │               │             │
 │              │                    │                  │               │             │
 │              │  (Scheduled path)                     │               │             │
 │── Schedule ─>│                    │                  │               │             │
 │              │── SchedulePay ────>│                  │               │             │
 │              │                    │── Register ──────────────────────────────────>│
 │<── Confirmed─│<── Scheduled ─────│                  │               │             │
 │              │                    │                  │               │             │
 │              │                    │  (Cron fires)    │               │             │
 │              │                    │                  │               │── Exec ────>│
 │              │                    │<── Trigger ──────│               │             │
 │              │                    │── AutoPay ────────────────────────────────>│
 │              │                    │<── Success ──────│               │             │
 │              │                    │── Deduct ───────>│               │             │
 │              │                    │── Notify ───────│               │             │
 │<── Receipt ──│<── AutoPaid ──────│                  │               │             │
 │              │                    │                  │               │             │
```

## Settlement Batch (Daily Cutoff)

```
Merchant/Agent   SettlementService     WalletService       Ledger         Notification
 │                     │                    │                │                │
 │  (Cutoff 23:59)     │                    │                │                │
 │                     │── CloseBatch ──────│                │                │
 │                     │── CollectTxs ──────│                │                │
 │                     │<── TxList ─────────│                │                │
 │                     │── NetAmount ──────│                │                │
 │                     │── VerifyGL ───────────────────────>│                │
 │                     │<── GLMatched ──────────────────────│                │
 │                     │── SettleAmount ───>│                │                │
 │                     │<── Settled ────────│                │                │
 │                     │── PostJournal ────────────────────>│                │
 │<── Settlement ──────│── Completed ──────│                │                │
 │   Report            │                  │                │                │
 │                     │── SendReport ────────────────────────────────────────>│
 │                     │                  │                │                │
 │  (Failure path)     │                  │                │                │
 │                     │── VerifyGL ───────────────────────>│                │
 │                     │<── GLMismatch ─────────────────────│                │
 │                     │── BatchFailed ───│                │                │
 │                     │── AlertOps ──────│                │                │
 │                     │                  │                │                │
 │  (Retry path)       │                  │                │                │
 │                     │── Retry(1/∞) ────│                │                │
 │                     │── SettleAmount ───────────────────>│                │
 │                     │<── Settled ────────────────────────│                │
 │                     │── Complete ──────│                │                │
 │                     │                  │                │                │
```
