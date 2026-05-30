# بنية النظام الخلفي — Backend Architecture

## Architecture Overview

```
                    ┌──────────────────────┐
                    │   API Gateway (Kong)  │
                    └──────┬───────────────┘
                           │
              ┌────────────┼────────────────┐
              │            │                │
       ┌──────▼────┐ ┌────▼────┐   ┌──────▼──────┐
       │ Financing  │ │ Credit  │   │  Repayment  │
       │ Application│ │ Scoring │   │  Service    │
       │ Service    │ │ Service │   │             │
       └──────┬─────┘ └────┬────┘   └──────┬──────┘
              │            │                │
              └────────────┼────────────────┘
                           │
              ┌────────────▼────────────────┐
              │      Core Financial         │
              │      Engine (CFE)           │
              └────────────┬────────────────┘
                           │
              ┌────────────┼────────────────┐
              │            │                │
       ┌──────▼────┐ ┌────▼────┐   ┌──────▼──────┐
       │  Accounts  │ │  Ledger  │   │Disbursement │
       │  Service   │ │ Service  │   │  Service    │
       └───────────┘ └─────────┘   └─────────────┘
```

## Core Services

### 1. ApplicationService
```
Responsibilities:
  - Accept new financing applications
  - Validate eligibility (KYC, wallet age, existing loans)
  - Manage application lifecycle (state machine)
  - Generate contract documents
  - Handle offer acceptance & e-signature

Key Methods:
  submitApplication(userId, productType, amount, term, purpose, documents, guarantorId)
    → validates → creates application record → triggers scoring → returns applicationId
  
  verifyApplication(applicationId, adminId)
    → manual review for high-value → approve/reject → notifies user
  
  approveApplication(applicationId, approvedAmount, approvedTerm, offeredRate)
    → generates offer → sets expiry timer → sends notification
  
  acceptOffer(applicationId)
    → generates contract → triggers disbursement process

Dependencies:
  CreditScoringService (for score calculation)
  DisbursementService (for fund transfer)
  NotificationService (for user communication)
  ContractGenerationService (for Sharia contract PDF)
```

### 2. CreditScoringService
```
Responsibilities:
  - Calculate credit score from wallet history
  - Maintain ML model (XGBoost)
  - Generate score factors and explanations
  - Monthly model retraining

Key Methods:
  calculateScore(userId, productType, amount)
    → fetches wallet data → runs feature engineering → predicts score → returns ScoreResult
  
  getScoreFactors(userId)
    → returns breakdown of contributing factors
  
  generateOfferParams(userId, score, productType, requestedAmount)
    → applies decision matrix → returns approvedAmount, profitRate, term

Architecture:
  - Async scoring for complex cases (Micro-Enterprise)
  - Synchronous scoring for simple cases (Qard Hasan)
  - Cache score for 24 hours (stale-while-revalidate)

Data Sources:
  - Wallet transaction history (last 12 months)
  - Savings account data
  - Bill payment history
  - Agent network interactions
  - Existing product usage
  - KYC level and account age
```

### 3. DisbursementService
```
Responsibilities:
  - Execute fund transfer from Beza financing pool
  - Support multiple disbursement targets: wallet, merchant, supplier
  - Verify merchant/supplier eligibility for Murabaha
  - Generate disbursement receipt

Key Methods:
  disburseToWallet(contractId, amount)
    → credits user's wallet → updates contract status → notifies user
  
  disburseToMerchant(contractId, merchantId, amount, itemDescription)
    → verifies merchant → transfers funds → confirms purchase → notifies user
  
  disburseSplit(contractId, disbursements[])
    → multiple transfers for multi-item Murabaha

Flow:
  1. Validate contract status (must be OFFER_ACCEPTED)
  2. Debit Beza financing pool
  3. Credit target (wallet or merchant)
  4. Generate transaction reference (linked to contract)
  5. Update contract status to DISBURSED
  6. Generate repayment schedule
  7. Send notification
```

### 4. RepaymentService
```
Responsibilities:
  - Manage repayment schedule generation
  - Execute auto-deduction
  - Handle manual payments
  - Track late fees and charity accounting
  - Retry logic for failed deductions

Key Methods:
  generateSchedule(contractId, principal, profit, termDays, frequency)
    → calculates installment amounts → creates installment records
  
  autoDeduct(contractId, dueDate)
    → checks wallet balance → debits installment → updates status → notifies
  
  manualPay(contractId, amount, installmentNumber?)
    → processes manual payment → allocates to principal/profit/late fees
  
  processLateFee(contractId, overdueDays)
    → calculates fee → moves to charity account → updates installment
  
  retryFailedPayment(contractId, installmentNumber)
    → 3 retries over 48 hours → escalates if all fail

Auto-Deduct Flow:
  1. Cron job runs daily at 07:00
  2. Query all installments due today with status 'pending'
  3. For each: check wallet balance
  4. If sufficient: debit and mark 'paid'
  5. If insufficient: mark 'overdue', trigger late fee, schedule retry
  6. First retry: +2 hours
  7. Second retry: +24 hours
  8. Third retry: +48 hours
  9. If all fail: trigger collection sequence
```

### 5. CollectionService
```
Responsibilities:
  - Manage overdue accounts
  - Execute escalation sequence
  - Handle restructuring requests
  - Coordinate with external collection agencies

Key Methods:
  getCollectionQueue(agentId, filters)
    → returns prioritized list of overdue accounts
  
  escalate(contractId, level)
    → moves to next escalation stage (soft → hard → legal)
  
  requestRestructure(contractId, options)
    → validates eligibility → generates new schedule → updates contract
  
  approveRestructure(contractId, adminId)
    → restructures debt → recalculates schedule → notifies user
  
  markDefault(contractId)
    → sets status to DEFAULTED → provisions bad debt → notifies credit bureau

Escalation Levels:
  Level 1 (Days 1-7):  Automated reminders (SMS, push, in-app)
  Level 2 (Days 8-14): Collection agent call (soft)
  Level 3 (Days 15-30): Collection agent call (firm) + restructuring offer
  Level 4 (Days 31-60): Field visit + guarantor contact
  Level 5 (Days 61-90): Legal notice preparation
  Level 6 (Day 90+):   Default declaration + external collection
```

### 6. ShariaComplianceLayer
```
Responsibilities:
  - Generate Sharia-compliant contracts
  - Ensure profit disclosure in Murabaha
  - Administer charity account for late fees
  - Generate Sharia audit reports
  - Maintain contract templates

Key Methods:
  generateMurabahaContract(contractId, costPrice, profitAmount, totalAmount, itemDescription)
    → generates PDF with full cost+profit disclosure
    
  generateQardHasanaContract(contractId, principal, adminFee)
    → generates PDF confirming zero-profit nature
    
  recordLateFeeCharity(contractId, amount)
    → credits charity liability account
    
  disburseCharityFunds(quarter, organizations, totalAmount)
    → executes charity disbursement with full documentation
    
  getShariaAuditReport(fromDate, toDate)
    → returns all contracts, charity movements, compliance metrics
```

## Message Queue Topics
```yaml
financing.application.submitted:
  consumers: [scoring-service, notification-service]

financing.application.approved:
  consumers: [notification-service]

financing.offer.accepted:
  consumers: [disbursement-service, contract-service]

financing.disbursement.completed:
  consumers: [repayment-service, notification-service]

financing.payment.received:
  consumers: [ledger-service, notification-service]

financing.payment.overdue:
  consumers: [collection-service, notification-service]

financing.default.declared:
  consumers: [collection-service, credit-bureau-service]
```
