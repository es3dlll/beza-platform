# Beza Platform — Event Storming

> Version: 1.0 | Last Updated: 2026-05-29

---

## Wallet Transfer

```
Trigger: User taps "Send" → Form submitted → PIN validated
Command: InitiateTransfer(senderWalletId, recipientId, amount, note, pin)
Event:   MoneyHeld(sender, amount, holdId)
Consumer: Fraud Detection (score transaction in real-time)
Event:   TransferSent(sender, recipient, amount, fee, transferId)
Consumer: Notification (push + SMS to sender and recipient)
Event:   MoneyPosted(sender, recipient, fee, postingId)
Consumer: Ledger (update sender debit, recipient credit)
Read Model: TransactionHistory, BalanceSummary, DailyLimitTracker
UI: TransferConfirmationScreen, ReceiptScreen, TransactionDetailScreen

Failure Path:
Event:   FraudDetected(transferId, riskScore)
Consumer: Auth (block transfer, release hold)
Event:   MoneyReleased(sender, holdId, reason="fraud_block")
Consumer: Wallet (unfreeze balance)
UI: TransferFailedScreen, FraudAlertBottomSheet
```

## Wallet Top-Up (Cash-In)

```
Trigger: Agent scans user QR → User confirms amount → Agent accepts
Command: ConfirmCashIn(agentId, userId, amount, method)
Event:   CashInInitiated(agentId, userId, amount, sessionId)
Consumer: Agent (update float reservation)
Consumer: Ledger (create pending journal entry)
Event:   CashInCompleted(agentId, userId, amount, fee, receiptId)
Consumer: Wallet (credit user balance)
Consumer: Agent (update float — decrease cash held, increase digital)
Consumer: Notification (push receipt to user)
Read Model: AgentFloatSummary, UserBalance, CashInHistory
UI: CashInConfirmationScreen, ReceiptScreen, AgentFloatDashboard

Offline Fallback:
Command: GenerateOfflineVoucher(agentId, amount, expiry)
Event:   OfflineVoucherCreated(voucherId, code, amount)
Consumer: Agent (print or save voucher)
Event:   OfflineVoucherRedeemed(voucherId, userId)
Consumer: Wallet (credit user, sync when online)
```

## FX Rate Lock → Conversion

```
Trigger: User enters send amount in foreign currency → Rate displayed
Command: RequestFxQuote(fromCurrency, toCurrency, amount)
Event:   FxQuoted(quoteId, rate, expiresAt, fromAmount, toAmount)
Consumer: Remittance (display rate to user)
UI: RateQuoteCard with countdown timer

Trigger: User accepts rate quote
Command: LockFxRate(quoteId)
Event:   FXLocked(lockId, quoteId, rate, fromAmount, toAmount)
Consumer: Remittance (proceed to funding)
Consumer: Wallet (earmark amount at locked rate)
Read Model: ActiveFxLock, FxRateHistory
UI: RateConfirmedBadge, LockStatusIndicator

Trigger: Transfer completed → FX settles
Command: SettleFxContract(lockId)
Event:   FXSettled(lockId, finalRate, netSettlementAmount)
Consumer: Ledger (post FX gain/loss entries)
Consumer: Treasury (update FX position)
```

## Remittance (Diaspora → Syria)

```
Trigger: Diaspora user selects "Send to Syria" → Fills recipient details
Trigger: Transfer hits remittance corridor
Command: CreateRemittance(senderId, recipientPhone, recipientName, sendAmount, receiveCurrency, purpose)
Event:   RemittanceCreated(remittanceId, sender, recipient, amount, corridor)
Consumer: Compliance (sanctions screening, AML checks)
Consumer: Notification (SMS to recipient: "You have incoming funds")
Consumer: FX (request rate lock)
Event:   FXLocked(lockId, rate, fromCurrency, toCurrency)
Consumer: Remittance (update remittance with rate, show to user)
Event:   RemittanceFunded(remittanceId, sendAmount, fee)
Consumer: Settlement (queue for corridor payout)
Event:   RemittanceCompleted(remittanceId, payoutRef, receivedAmount)
Consumer: Notification (push to sender + SMS to recipient with collection code)
Consumer: Ledger (post FX gain, fee income)
Read Model: RemittanceStatus, CorridorRates, DailyRemittanceVolume
UI: RemittanceTrackerScreen, RecipientCollectionScreen, RateComparisonScreen

Failure Path:
Event:   ComplianceHold(remittanceId, reason)
Consumer: Notification (sender asked to provide more info)
Event:   RemittanceFailed(remittanceId, reason)
Consumer: Wallet (refund sender)
```

## Agent Onboarding

```
Trigger: Prospective agent downloads app → Selects "Become an Agent"
Command: SubmitAgentApplication(userId, businessName, location, documents)
Event:   AgentApplicationSubmitted(applicationId, userId)
Consumer: Compliance (document verification, background check)
Event:   AgentApproved(agentId, userId, businessName, location)
Consumer: Onboarding (assign agent ID, generate dashboard credentials)
Consumer: Notification (welcome message, training materials)
Consumer: Wallet (initialize agent float wallet)
Read Model: AgentApplicationStatus, AgentNetworkMap
UI: ApplicationStatusScreen, AgentDashboardHome, FloatManagementScreen
```

## Merchant QR Payment

```
Trigger: Customer scans merchant QR → Enters amount → Confirms
Command: InitiateMerchantPayment(customerWalletId, merchantId, amount, tip, note)
Event:   MerchantPaymentInitiated(paymentId, customerId, merchantId, amount)
Consumer: Fraud (velocity check on merchant)
Consumer: Wallet (hold customer funds)
Event:   MerchantPaymentCompleted(paymentId, settlementAmount, fee, timestamp)
Consumer: Merchant (real-time payment notification)
Consumer: Ledger (credit merchant settlement account)
Consumer: Notification (receipt to customer)
Read Model: MerchantDailySales, CustomerPaymentHistory
UI: PaymentSuccessScreen, MerchantSalesDashboard, ReceiptScreen

Tip Flow:
Command: AddTip(paymentId, tipAmount)
Event:   TipAdded(paymentId, tipAmount)
Consumer: Merchant (update settlement total)
Consumer: Wallet (additional hold on customer)
```

## Savings Auto-Save

```
Trigger: Savings goal created → Auto-save rule configured
Command: CreateSavingsGoal(userId, name, targetAmount, targetDate)
Event:   SavingsGoalCreated(goalId, name, targetAmount)
Consumer: Wallet (create sub-wallet for goal)
Read Model: GoalProgress, AutoSaveConfiguration
UI: GoalCreationScreen, GoalDetailScreen

Trigger: Scheduled auto-save trigger (daily/weekly/monthly)
Command: ExecuteAutoSave(goalId, amount, sourceWalletId)
Event:   SavingsAutoSaved(goalId, amount, newBalance)
Consumer: Wallet (transfer from main wallet to goal sub-wallet)
Consumer: SavingsGoal (update progress percentage)
Event:   SavingsGoalCompleted(goalId, finalAmount)
Consumer: Notification (congratulations message)
Consumer: Wallet (release funds or keep in goal)
Read Model: AutoSaveHistory, GoalProgressTimeline
UI: GoalProgressScreen, AutoSaveLogScreen, CelebrationOverlay
```

## Financing Disbursement

```
Trigger: Loan application approved → Disbursement scheduled
Command: DisburseLoan(applicationId, approvedAmount, sourceAccountId)
Event:   FinancingDisbursed(disbursementId, applicationId, amount, fee)
Consumer: Wallet (credit borrower's wallet minus fee)
Consumer: Ledger (create loan receivable asset, record fee income)
Consumer: Repayment (generate repayment schedule)
Event:   RepaymentScheduleCreated(loanId, installments[], dueDates[], totalPayable)
Consumer: Notification (inform borrower of first due date)
Read Model: ActiveLoans, RepaymentCalendar, OutstandingBalance
UI: LoanConfirmationScreen, RepaymentScheduleScreen, LoanDetailScreen

Repayment Path:
Trigger: Scheduled repayment due or manual payment
Command: MakeRepayment(loanId, amount, sourceWalletId)
Event:   RepaymentReceived(loanId, amount, remainingBalance)
Consumer: Loan (update outstanding, advance to next installment)
Consumer: Ledger (debit loan receivable, credit income)
```

## Bill Payment

```
Trigger: User selects biller → Enters account number → Confirms amount
Command: InitiateBillPayment(userId, billerId, accountNumber, amount, schedule)
Event:   BillPaymentInitiated(paymentId, billerId, amount, reference)
Consumer: Wallet (hold funds)
Consumer: Biller Gateway (submit payment to biller API)
Event:   BillPaymentPaid(paymentId, receiptNumber, paidAmount)
Consumer: Wallet (deduct funds)
Consumer: Notification (receipt to user)
Consumer: Ledger (record expense or transfer)
Read Model: ScheduledPayments, PaymentHistory, BillerList
UI: BillerSelectionScreen, PaymentConfirmationScreen, ScheduledPaymentsScreen

Schedule Path:
Command: ScheduleBillPayment(userId, billerId, accountNumber, amount, cronExpression)
Event:   BillPaymentScheduled(scheduleId, nextRunDate)
Consumer: Scheduler (queue cron job)
Event:   BillPaymentPaid(paymentId) (on each execution)
Consumer: Notification (reminder before due date, receipt after payment)
```

## Settlement Batch

```
Trigger: Settlement cutoff time reached (e.g., 23:59 daily)
Command: CloseSettlementBatch(entityId, type)
Event:   SettlementBatchOpened(batchId, entityId, type, cutoffTime)
Consumer: Settlement (collect all pending transactions for entity)
Command: ProcessSettlementBatch(batchId)
Event:   SettlementBatchClosing(batchId, transactionCount, netAmount)
Consumer: Ledger (verify GL totals)
Event:   SettlementBatchFailed(batchId, reason, discrepancyAmount)
Consumer: Alert (operations team notified)
Command: RetrySettlementBatch(batchId)
Event:   SettlementBatchSettled(batchId, netAmount, settledAt)
Consumer: Wallet (credit merchant/agent settlement account)
Consumer: Ledger (post settlement journal entries)
Event:   SettlementCompleted(batchId, entityId, amount)
Consumer: Notification (merchant/agent daily settlement report SMS)
Consumer: Merchant (update daily sales status to "settled")
Read Model: SettlementHistory, PendingSettlements, DailySettlementReport
UI: SettlementDashboard, MerchantSettlementScreen, SettlementDetailScreen
```

## User Onboarding

```
Trigger: User opens app → Phone number entry → OTP verification
Command: RegisterUser(phone, deviceInfo, referralCode)
Event:   UserRegistered(userId, phone, deviceId, referralCode)
Consumer: Analytics (attribution, cohort assignment)
Consumer: Onboarding (create default wallet, assign tier-1 limits)
Consumer: Notification (welcome SMS, onboarding flow trigger)
Command: AssignKycLevel(userId, level=1)
Event:   KYCLevelAssigned(userId, level, limits[])
Consumer: Wallet (set daily/weekly transaction limits)
Read Model: UserProfile, KycStatus, TransactionLimits
UI: OnboardingFlow (WelcomeScreen → PhoneVerify → ProfileSetup → KycPrompt)

Trigger: User upgrades KYC (tier 2 or 3)
Command: SubmitKycDocuments(userId, documentType, documentImages, selfie)
Event:   KYCDocumentsSubmitted(userId, documentRefs)
Consumer: Compliance (queue for verification)
Event:   KYCApproved(userId, newLevel, verificationMethod)
Consumer: Auth (update user claims, generate new token)
Consumer: Wallet (lift limits to new tier)
Consumer: Notification ("Your account is now upgraded!")
Read Model: KycVerificationStatus, CurrentLimits
UI: KycUploadScreen, KycStatusScreen, LimitsUpgradeScreen
```
