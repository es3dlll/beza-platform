# Accounting Book — Beza Platform (IFRS-Compliant)

**Scope:** Every financial operation processed by the Beza platform, with full double-entry accounting, CFE event types, settlement impact, reconciliation rules, and risk handling. All entries comply with IFRS 9 (Financial Instruments) and IFRS 15 (Revenue from Contracts with Customers). Syrian context: all operations available in SYP and USD; CBS reporting requirements embedded.

---

## Operation 1: P2P Transfer (SYP)

### Trigger
User sends funds from their Beza wallet to another Beza user's wallet in SYP.

### Double-Entry
| Step | Account Type | Account Name | Debit | Credit | Currency |
|------|-------------|-------------|-------|--------|----------|
| 1 | Liability | Sender Wallet (User A) | — | amount | SYP |
| 2 | Liability | Receiver Wallet (User B) | amount | — | SYP |
| 3 | Income | Transaction Fee Income | — | fee (0.5%) | SYP |

### CFE Event Types
- `MoneyHeld` (sender balance reserved at step 1 pending confirmation)
- `MoneyPosted` (all steps committed on confirmation)
- `FeePosted` (fee recognised at posting)

### Settlement Impact
- Net: 0 (pure internal transfer; no movement of physical cash or bank balance)
- No bank settlement required

### Reconciliation
- Daily: `SUM(beza_cfe.journal_entries WHERE type = 'P2P')` = `SUM(beza_wallet.wallet_balance_changes)`
- Match: sender debit amount = receiver credit amount + fee
- Fee income recognised net of tax (if applicable)

### Risk
- Failed posting after hold → release hold (reversal of step 1, no loss)
- Dispute → freeze both wallets, create suspense liability entry
- Double-spend prevented by idempotency key

---

## Operation 2: P2P Transfer (USD)

### Trigger
User sends USD from their Beza USD wallet to another user's USD wallet.

### Double-Entry
| Step | Account Type | Account Name | Debit | Credit | Currency |
|------|-------------|-------------|-------|--------|----------|
| 1 | Liability | Sender USD Wallet | — | amount | USD |
| 2 | Liability | Receiver USD Wallet | amount | — | USD |
| 3 | Income | Transaction Fee Income | — | fee (0.5%) | USD |
| 4 | Liability | FX Suspense | — | 0 | USD |

### CFE Event Types
- `MoneyHeld`, `MoneyPosted`, `FeePosted` (same as SYP)
- `FXConversionPosted` (if sender pays in SYP but sends USD — see Operation 4)

### Settlement Impact
- Net: 0 (internal transfer, USD-denominated)
- No bank settlement needed unless USD float requires replenishment

### Reconciliation
- Daily: sum of USD journal entries = sum of USD wallet balance changes
- USD fee income recognised at CBs reference rate for SYP reporting

### Risk
- Same as SYP P2P
- USD balance must exist before transfer; no overdraft permitted

---

## Operation 3: Agent Cash-in

### Trigger
User visits a Beza agent, hands over cash (SYP), agent confirms receipt. User's wallet is credited.

### Double-Entry
| Step | Account Type | Account Name | Debit | Credit | Currency |
|------|-------------|-------------|-------|--------|----------|
| 1 | Asset | Agent Float Account | — | cash_amount | SYP |
| 2 | Liability | User Wallet | cash_amount | — | SYP |
| 3 | Income | Agent Cash-in Commission Income | — | commission | SYP |
| 4 | Expense | Agent Commission Expense | commission | — | SYP |

### CFE Event Types
- `CashInInitiated` (agent scan, amount entry)
- `MoneyHeld` (float reduction held pending user PIN)
- `CashInCompleted` (all entries posted)
- `CommissionPosted` (agent commission credited to agent's commission wallet)

### Settlement Impact
- Agent float decreases by `cash_amount`
- User wallet increases by `cash_amount`
- Net Beza position: 0 (asset decrease = liability increase)
- Commission creates expense and income; net P&L impact: 0 (assuming commission = income)

### Reconciliation
- Daily: `SUM(agent.cash_in) = SUM(cfe.journal_entries WHERE type = 'cash_in')`
- Agent float balance = opening float − SUM(cash_in) + SUM(cash_out) + top-ups
- Match: agent float reduction = user wallet credit

### Risk
- Agent processes cash-in without having sufficient float → blocked by system
- Dispute (user claims cash given but not credited) → agent investigation, suspense entry if unresolved
- Collusion (agent + user fake cash-in) → detected via velocity/pattern rules

---

## Operation 4: Agent Cash-out

### Trigger
User requests cash from agent; user's wallet is debited; agent gives physical cash; agent float increases.

### Double-Entry
| Step | Account Type | Account Name | Debit | Credit | Currency |
|------|-------------|-------------|-------|--------|----------|
| 1 | Liability | User Wallet | amount | — | SYP |
| 2 | Asset | Agent Float Account | — | amount | SYP |
| 3 | Income | Agent Cash-out Fee Income | — | fee | SYP |
| 4 | Expense | Agent Commission Expense | fee | — | SYP |

### CFE Event Types
- `CashOutInitiated`, `MoneyHeld`, `CashOutCompleted`, `CommissionPosted`

### Settlement Impact
- Agent float increases; user wallet decreases
- Beza net position: 0

### Reconciliation
- Same as cash-in (inverse direction)
- Validate: agent float net change = SUM(cash_out) − SUM(cash_in)

### Risk
- Agent gives cash but user PIN fails → hold released, no loss
- Agent shortchanges user → dispute resolution via transaction logs
- User wallet must have sufficient balance; no overdraft

---

## Operation 5: FX Conversion (SYP ↔ USD)

### Trigger
User converts SYP to USD (or USD to SYP) at a locked exchange rate within the app.

### Double-Entry (SYP → USD)
| Step | Account Type | Account Name | Debit | Credit | Currency |
|------|-------------|-------------|-------|--------|----------|
| 1 | Liability | User SYP Wallet | syp_amount | — | SYP |
| 2 | Liability | FX Suspense Account | — | syp_amount | SYP |
| 3 | Liability | FX Suspense Account | usd_amount | — | USD |
| 4 | Liability | User USD Wallet | — | usd_amount | USD |
| 5 | Income | FX Spread Income | — | spread | SYP |

**Rate = syp_amount / usd_amount. Spread = (market_rate − locked_rate) × usd_amount.**

### CFE Event Types
- `FXQuoteCreated` (rate lock, 60s validity)
- `FXConversionStarted` (SYP debited, funds to suspense)
- `FXConversionCompleted` (USD credited from suspense)
- `FXSpreadPosted` (spread recognised as income)

### Settlement Impact
- SYP wallet debited; USD wallet credited
- FX suspense (multi-currency) is a temporary holding account; net balance should be zero after each completed conversion
- No bank movement until net settlement with CBS or partner bank

### Reconciliation
- Daily: FX suspense account balance = 0 (all open conversions resolved)
- FX spread income = SUM(spread per conversion)
- Rate used must match locked rate in quote; audit trail for each conversion

### Risk
- Rate lock expired → user must re-quote (no loss)
- Partial execution (SYP debited but USD not credited) → suspense holds funds; auto-retry or manual resolution
- CBS rate change between quote and execution → Beza bears the difference if rate not locked properly

---

## Operation 6: Remittance Payout

### Trigger
A diaspora sender sends money via a corridor partner; funds arrive at Beza's settlement account; Beza credits the Syrian recipient's wallet.

### Double-Entry
| Step | Account Type | Account Name | Debit | Credit | Currency |
|------|-------------|-------------|-------|--------|----------|
| 1 | Liability | Remittance Settlement Account | payout_amount | — | SYP |
| 2 | Liability | Recipient Wallet | — | payout_amount | SYP |
| 3 | Income | Beza Remittance Fee Income | — | beza_fee | SYP |
| 4 | Liability | Corridor Partner Payable | — | partner_fee | SYP |
| 5 | Asset | Bank Account (Settlement) | — | payout_amount + fees | SYP |

### CFE Event Types
- `RemittanceReceived` (funds arrive from corridor partner)
- `RemittancePaidOut` (recipient credited)
- `FeeSplitPosted` (Beza fee + partner fee recognised)

### Settlement Impact
- Bank account decreases (settlement to partner)
- Recipient wallet increases
- Net: Beza holds fee income; partner payable is settled per contract (weekly/monthly)

### Reconciliation
- Daily: remittance settlement account = SUM(incoming) − SUM(payouts) − SUM(fees owed to partners)
- Match: CBS daily remittance report = internal remittance journal entries
- Partner fee payable reconciled monthly

### Risk
- Recipient wallet not found or frozen → funds held in suspense; ops team resolves within 24h
- AML block → funds returned to sender minus processing fee
- Duplicate payout → reversal and investigation

---

## Operation 7: Bill Payment

### Trigger
User pays a bill (electricity, telecom, water, internet) from their Beza wallet.

### Double-Entry
| Step | Account Type | Account Name | Debit | Credit | Currency |
|------|-------------|-------------|-------|--------|----------|
| 1 | Liability | User Wallet | bill_amount | — | SYP |
| 2 | Liability | Biller Payable Account | — | bill_amount | SYP |
| 3 | Income | Bill Payment Fee Income | — | fee | SYP |

### CFE Event Types
- `BillPaymentInitiated`
- `BillPaymentCompleted`
- `FeePosted`

### Settlement Impact
- User wallet decreases; biller payable increases
- No immediate bank movement; settlement occurs in daily batch (Operation 9)

### Reconciliation
- Daily: `SUM(bill_payments) = SUM(biller_payable_changes)`
- Match each bill payment to biller confirmation (API response)
- Unconfirmed payments flagged for follow-up

### Risk
- Biller API timeout → retry 3 times; if all fail, refund wallet
- Wrong account number → user responsibility; no reversal unless biller confirms mismatch
- Bill already paid → biller API returns error; wallet not debited

---

## Operation 8: Merchant QR Payment

### Trigger
User scans a merchant's static QR code at a point of sale, enters amount, confirms with PIN. Merchant wallet is credited.

### Double-Entry
| Step | Account Type | Account Name | Debit | Credit | Currency |
|------|-------------|-------------|-------|--------|----------|
| 1 | Liability | User Wallet | amount | — | SYP |
| 2 | Liability | Merchant Payable Account | — | amount − MDR | SYP |
| 3 | Income | Merchant Discount Rate (MDR) Income | — | MDR | SYP |

**MDR = Merchant Discount Rate (configurable, typically 1–2.5%)**

### CFE Event Types
- `MerchantPaymentInitiated`
- `MerchantPaymentCompleted`
- `MDRPosted`

### Settlement Impact
- User wallet debited; merchant payable credited at net amount (after MDR)
- Settlement batch T+1: merchant payable → merchant bank account (Operation 9)

### Reconciliation
- Daily: `SUM(merchant_payments) = SUM(merchant_payable) + SUM(MDR_income)`
- D+1 settlement: net position between merchant payable and actual bank transfer must equal 0

### Risk
- Merchant QR spoofing → merchant identity verification at registration
- Double scan → idempotency key prevents duplicate
- Refund scenario: merchant initiates refund via dashboard (reverse entry, fee usually not returned)

---

## Operation 9: Payroll Disbursement

### Trigger
Employer uploads bulk payroll file; Beza debits the employer's account and credits multiple employee wallets.

### Double-Entry
| Step | Account Type | Account Name | Debit | Credit | Currency |
|------|-------------|-------------|-------|--------|----------|
| 1 | Asset | Employer Settlement Account | total_payroll | — | SYP |
| 2 | Liability | Employee 1 Wallet | — | salary_1 | SYP |
| 3 | Liability | Employee 2 Wallet | — | salary_2 | SYP |
| … | … | … | — | … | SYP |
| N | Liability | Employee N Wallet | — | salary_N | SYP |
| N+1 | Income | Payroll Processing Fee Income | — | fee | SYP |

### CFE Event Types
- `PayrollFileUploaded`
- `PayrollBatchStarted` (transaction batch UUID)
- `PayrollBatchCompleted` (all employees credited)
- `PayrollFeePosted`

### Settlement Impact
- Employer settlement account debited (funds must be pre-funded)
- Employee wallets credited in bulk
- Net: 0 (asset decrease = total liability increase + fee income)

### Reconciliation
- Match: `total_payroll = SUM(all_salaries) + fee`
- Each employee must have an active wallet; failed rows logged for manual processing
- Daily payroll batch report for employer

### Risk
- Employer insufficient balance → batch rejected entirely (all-or-nothing)
- Incorrect employee phone/wallet → salary held in suspense; ops team resolves
- Duplicate batch → idempotency on batch UUID prevents double-posting

---

## Operation 10: Settlement Batch (EOD)

### Trigger
End-of-day batch process that nets merchant payables, biller payables, agent commissions, and partner fees, then initiates a single net bank transfer for each external counterparty.

### Double-Entry (Merchant Settlement Example)
| Step | Account Type | Account Name | Debit | Credit | Currency |
|------|-------------|-------------|-------|--------|----------|
| 1 | Liability | Merchant Payable (Merchant A) | net_amount_A | — | SYP |
| 2 | Asset | Bank Account (Settlement) | — | net_amount_A | SYP |
| 3 | Expense | Bank Transfer Fee | bank_fee | — | SYP |
| 4 | Asset | Bank Account (Settlement) | — | bank_fee | SYP |

### Double-Entry (Biller Settlement)
| Step | Account Type | Account Name | Debit | Credit | Currency |
|------|-------------|-------------|-------|--------|----------|
| 1 | Liability | Biller Payable (Biller X) | biller_total | — | SYP |
| 2 | Asset | Bank Account (Settlement) | — | biller_total | SYP |

### CFE Event Types
- `SettlementBatchStarted`
- `SettlementBatchCompleted`
- `BankTransferInitiated`
- `BankTransferConfirmed`

### Settlement Impact
- All payable accounts are zeroed (netted to zero)
- Bank account reflects aggregate net outflow to external counterparties
- FX settlement (USD payables) handled via separate USD bank account

### Reconciliation
- Post-settlement: ALL merchant/biller/agent/partner payable accounts = 0
- Bank account net change = SUM(all settlement outflows) − SUM(all settlement inflows)
- Every unsettled item is a reconciliation exception

### Risk
- Bank transfer fails → payable remains on books; retry next batch
- Partial settlement → payable shows residual balance; flagged automatically
- Bank account insufficient funds → settlement postponed; treasury alert triggered

---

## Operation 11: Reversal

### Trigger
Operations team (with 2-person approval) reverses a completed transaction due to error, dispute, or fraud.

### Double-Entry (Reversal of P2P Transfer)
| Step | Account Type | Account Name | Debit | Credit | Currency |
|------|-------------|-------------|-------|--------|----------|
| 1 | Liability | Receiver Wallet (was credited) | original_amount | — | SYP |
| 2 | Liability | Sender Wallet (was debited) | — | original_amount | SYP |
| 3 | Income | Reversal Fee Income | — | reversal_fee | SYP |
| 4 | Expense | Reversed Transaction Fee Expense (original fee) | — | original_fee | SYP |

**Note:** The reversal fee is charged to the party at fault (sender, receiver, or Beza). The original fee is expensed to undo the income recognition.

### CFE Event Types
- `ReversalInitiated`
- `ReversalCompleted`
- `ReversalFeePosted`
- `OriginalTransactionReversed` (links to original transaction ID)

### Settlement Impact
- All original entries are reversed with opposite direction
- Net: 0 (reversal of the original + new reversal fee)
- Original fee income is reversed; reversal fee income is recognised

### Reconciliation
- Check: `SUM(original_tx.journal_entries)` = −`SUM(reversal.journal_entries)` (excluding reversal fee)
- Reversal must reference the original transaction ID; audit trail preserved

### Risk
- Double reversal (same transaction reversed twice) → system prevents by marking original as `reversed`
- Reversal after settlement batch → must reverse through bank (not internal); creates suspense
- Fraudster abuses reversal → reversal fee deters; limits on reversal frequency

---

## Operation 12: Chargeback

### Trigger
Customer disputes a merchant payment; after investigation, the merchant is deemed liable. Merchant account is debited; customer wallet is credited. Merchant funded (no Beza loss).

### Double-Entry
| Step | Account Type | Account Name | Debit | Credit | Currency |
|------|-------------|-------------|-------|--------|----------|
| 1 | Liability | Merchant Payable (or Merchant Wallet) | chargeback_amount | — | SYP |
| 2 | Liability | Customer Wallet | — | chargeback_amount | SYP |
| 3 | Expense | Chargeback Processing Fee | fee | — | SYP |
| 4 | Income | Chargeback Fee Income | — | fee | SYP |

### CFE Event Types
- `ChargebackInitiated`
- `ChargebackCompleted`
- `ChargebackFeePosted`

### Settlement Impact
- Merchant account debited; customer credited
- If merchant has already been settled (D+1), chargeback is deducted from next settlement batch
- Net Beza impact: 0 (merchant-funded; processing fee is pass-through)

### Reconciliation
- Chargeback amount = original transaction amount (full reversal)
- Merchant settlement batch adjusted for pending chargebacks
- Monthly chargeback ratio calculated (chargebacks / total transactions) for CBS reporting

### Risk
- Merchant disputes chargeback → arbitration process; funds held in suspense
- Merchant account insufficient → chargeback queued until merchant prefunds
- Excessive chargebacks → merchant terminated

---

## Operation 13: Float Top-up (Agent)

### Trigger
Agent requests float top-up; agent transfers cash or bank transfer to Beza's bank account; ops team credits the agent's float account.

### Double-Entry
| Step | Account Type | Account Name | Debit | Credit | Currency |
|------|-------------|-------------|-------|--------|----------|
| 1 | Asset | Bank Account (Settlement) | topup_amount | — | SYP |
| 2 | Asset | Agent Float Account | — | topup_amount | SYP |

### CFE Event Types
- `FloatTopUpInitiated`
- `FloatTopUpCompleted`

### Settlement Impact
- Bank account increases (cash received); agent float increases
- Net: 0 (asset-to-asset transfer)

### Reconciliation
- Agent float = opening float + top-ups − cash_in + cash_out
- Match: bank deposit reference matches top-up amount
- Automated check: `SUM(agent_float) + SUM(customer_wallets) = bank_account_balance` (whole-platform balance sheet check)

### Risk
- Agent claims top-up not received → bank statement verification
- Top-up credited before bank confirmation → ops policy: only after bank statement matches
- Fraudulent top-up → reversal and agent suspension

---

## Operation 14: Fee Accrual

### Trigger
Monthly accrual for fees earned but not yet recognised (e.g., fees on transactions settled in the next accounting period, or subscription-style fees).

### Double-Entry
| Step | Account Type | Account Name | Debit | Credit | Currency |
|------|-------------|-------------|-------|--------|----------|
| 1 | Asset | Accrued Fee Receivable | accrued_amount | — | SYP |
| 2 | Income | Accrued Fee Income | — | accrued_amount | SYP |

**Accrual basis: fees earned in period M recognised in period M, regardless of settlement date.**

### CFE Event Types
- `FeeAccrualCalculated`
- `FeeAccrualPosted`

### Settlement Impact
- No cash impact; purely accounting adjustment
- Reversed in the next period when the fee is actually settled (or recognised as realised)

### Reconciliation
- Monthly: total accrued fees = `SUM(fee_eligible_transactions in month) − SUM(already recognised fees)`
- Accrual entries reversed on 1st of following month (reversal entry or subsequent recognition)

### Risk
- Over-accrual → reversed on realisation; no material impact
- Under-accrual → missed revenue in P&L; adjusted in subsequent month
- IFRS 15 requires accrual for performance obligations satisfied

---

## Operation 15: Provision for Fraud Losses

### Trigger
Monthly provision (IFRS 9 expected credit loss model) for estimated fraud losses based on historical chargeback rates and open investigations.

### Double-Entry
| Step | Account Type | Account Name | Debit | Credit | Currency |
|------|-------------|-------------|-------|--------|----------|
| 1 | Expense | Fraud Loss Expense (P&L) | provision_amount | — | SYP |
| 2 | Liability | Provision for Fraud Losses | — | provision_amount | SYP |

**Provision = ECL = Σ(transaction_value × PD × LGD) per portfolio segment.**

### CFE Event Types
- `FraudProvisionCalculated`
- `FraudProvisionPosted`

### Settlement Impact
- No cash impact; P&L expense reduces retained earnings
- When actual fraud loss occurs:
  - Debit: Provision for Fraud Losses
  - Credit: User Wallet (or other affected account)

### Reconciliation
- Monthly: actual losses vs provision; variance analysed
- Provision balance = YTD provisions − YTD actual losses
- IFRS 9 requires model validation annually

### Risk
- Provision too low → unexpected P&L hit when fraud crystallises
- Provision too high → unnecessary reduction in reported profit
- Regulatory: CBS may require minimum provision ratio

---

## Operation 16: Suspense Account Operations

### Trigger
A transaction cannot be completed due to a system error, missing counterparty, or compliance hold. Funds are moved to a suspense account pending resolution.

### Double-Entry (P2P transfer — receiver wallet not found)
| Step | Account Type | Account Name | Debit | Credit | Currency |
|------|-------------|-------------|-------|--------|----------|
| 1 | Liability | Sender Wallet | amount | — | SYP |
| 2 | Liability | Suspense Account (Unresolved Transactions) | — | amount | SYP |
| 3 | Income | Transaction Fee Income | — | fee | SYP |

### Double-Entry (Resolution — receiver found)
| Step | Account Type | Account Name | Debit | Credit | Currency |
|------|-------------|-------------|-------|--------|----------|
| 1 | Liability | Suspense Account (Unresolved Transactions) | amount | — | SYP |
| 2 | Liability | Receiver Wallet | — | amount | SYP |

### Double-Entry (Resolution — refund to sender)
| Step | Account Type | Account Name | Debit | Credit | Currency |
|------|-------------|-------------|-------|--------|----------|
| 1 | Liability | Suspense Account (Unresolved Transactions) | amount | — | SYP |
| 2 | Liability | Sender Wallet | — | amount | SYP |
| 3 | Expense | Reversed Fee Expense | fee | — | SYP |
| 4 | Income | Transaction Fee Income | — | fee | SYP |

### CFE Event Types
- `SuspenseHeld` (funds moved to suspense)
- `SuspenseReleased` (funds released to destination)
- `SuspenseRefunded` (funds returned to origin)

### Settlement Impact
- No bank movement; funds remain within Beza's ledger
- Suspense balance must be reviewed daily by operations

### Reconciliation
- Daily: suspense account balance = SUM(all unresolved transaction amounts)
- Aging report: transactions in suspense > 7 days escalated to management
- Zero tolerance for unreconciled suspense at month-end

### Risk
- Suspense balance grows unchecked → manual review required; ageing triggers escalate
- Resolution takes too long → user complaint; regulatory risk
- Wrong resolution path (receiver gets funds twice) → investigative audit
- CBS regulation: suspense accounts > 30 days must be reported

---

## Chart of Accounts (Summary)

| Account Code | Type | Name | Description |
|-------------|------|------|-------------|
| 1000–1999 | Asset | Cash & Bank | Bank accounts, cash on hand |
| 1100 | Asset | Bank Account (Settlement) | Main settlement bank account |
| 1200 | Asset | Agent Float Account | Agent cash float (asset, cash held by agent) |
| 1300 | Asset | Accrued Fee Receivable | Unrecognised earned fees |
| 2000–2999 | Liability | Customer Wallets & Payables | |
| 2100 | Liability | User Wallet (SYP) | Individual user wallet, SYP-denominated |
| 2101 | Liability | User Wallet (USD) | Individual user wallet, USD-denominated |
| 2200 | Liability | Agent Float Payable | Agent float balance (mirror of asset side for reconciliation) |
| 2300 | Liability | Merchant Payable | Merchant settlement pending transfer |
| 2400 | Liability | Biller Payable | Biller settlement pending transfer |
| 2500 | Liability | Remittance Settlement Account | Remittance funds pending payout |
| 2600 | Liability | FX Suspense Account | Multi-currency holding during FX conversion |
| 2700 | Liability | Suspense Account (Unresolved Transactions) | Unresolved transaction holding |
| 2800 | Liability | Provision for Fraud Losses | IFRS 9 expected credit loss provision |
| 2900 | Liability | Corridor Partner Payable | Partner fee payable |
| 3000–3999 | Income | Revenue Accounts | |
| 3100 | Income | Transaction Fee Income | P2P transfer fees |
| 3200 | Income | FX Spread Income | FX conversion spread |
| 3300 | Income | Agent Cash-in Commission Income | Agent cash-in fees |
| 3400 | Income | Bill Payment Fee Income | Bill payment fees |
| 3500 | Income | Merchant Discount Rate (MDR) Income | Merchant QR fees |
| 3600 | Income | Remittance Fee Income | Remittance fees |
| 3700 | Income | Reversal Fee Income | Reversal fees |
| 3800 | Income | Accrued Fee Income | Accrued but unrealised fees |
| 4000–4999 | Expense | Operating Expenses | |
| 4100 | Expense | Agent Commission Expense | Agent commissions paid |
| 4200 | Expense | Bank Transfer Fee | Bank transfer charges |
| 4300 | Expense | Fraud Loss Expense (P&L) | Fraud provision expense |
| 4400 | Expense | Chargeback Processing Fee | Chargeback handling costs |
| 4500 | Expense | SMS/Notification Expense | Outbound communication costs |
| 4600 | Expense | CBS Reporting Expense | Regulatory reporting costs |

---

## Period-End Procedures

### Daily
- Reconcile all cash-in/cash-out totals against agent float changes
- Reconcile all P2P totals (debits = credits + fees)
- Reconcile merchant/biller payable balances
- Reconcile FX suspense — must be zero
- Reconcile suspense account — review all open items

### Weekly
- Corridor partner fee payable reconciliation
- Agent commission payable reconciliation
- Fraud provision adequacy check

### Monthly
- Full IFRS trial balance
- Accrual entries (fee accrual, fraud provision)
- Bank statement reconciliation
- CBS regulatory report (aggregate transaction data)
- Profit & loss statement by product line

### Quarterly
- IFRS 9 ECL model update
- Impairment assessment
- External audit preparation
- CBS compliance report (quarterly filing)

---

*Last updated: 2026-05-29 | IFRS compliant | Beza Financial Control*
