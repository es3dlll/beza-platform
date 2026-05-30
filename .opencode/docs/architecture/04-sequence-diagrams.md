# Sequence Diagrams

Text sequence diagrams for all critical platform flows. Syria context throughout.

Actors: User, App, API, Service, CFE, External, Queue

---

## 1. Wallet Transfer

### Happy Path
```
User         App          API          Service       CFE          Queue         Recipient
 |            |            |            |            |            |              |
 |--amount--> |            |            |            |            |              |
 |            |--POST----->|            |            |            |              |
 |            |            |--validate-->|            |            |              |
 |            |            |            |--auth------>|            |              |
 |            |            |            |<--approved--|            |              |
 |            |            |            |--hold------>|            |              |
 |            |            |            |<--held------|--event---->|              |
 |            |            |            |--post------>|            |              |
 |            |            |            |<--posted----|--event---->|              |
 |<--success--|<---200-----|<-----------|            |            |--notify----->|
```

### Insufficient Balance
```
User         App          API          Service       CFE
 |            |            |            |            |
 |--amount--> |            |            |            |
 |            |--POST----->|            |            |
 |            |            |--validate-->|            |
 |            |            |            |--auth------>|
 |            |            |            |<--insuff----|
 |            |            |            |            |
 |<--error----|<---402-----|<-----------|            |
 | "insufficient"           |            |            |
```

### Fraud Block
```
User         App          API          Service     FraudEng     CFE
 |            |            |            |            |            |
 |--amount--> |--POST----->|--validate-->|            |            |
 |            |            |            |--riskcheck->|            |
 |            |            |            |<--block-----|            |
 |            |            |            |            |            |
 |            |            |            |--flag------|            |
 |<--blocked--|<---403-----|<-----------|            |            |
```

### Hold Timeout
```
User         App          API          Service     Scheduler    CFE
 |            |            |            |            |            |
 |--amount--> |--POST----->|--validate-->|            |            |
 |            |            |            |--hold----->|            |
 |            |            |            |<--held-----|            |
 |            |            |            |            |            |
 |            |            |            |            |--tick      |
 |            |            |            |<--timeout---|            |
 |            |            |            |--release--->|            |
 |            |            |            |<--released--|            |
 |<--failed---|<---408-----|<-----------|            |            |
```

---

## 2. Agent Cash-in

### Online Happy Path
```
Customer     AgentPOS     API          Service      CFE          Queue
 |            |            |            |            |            |
 |--cash----->|            |            |            |            |
 |            |--POST----->|            |            |            |
 |            |            |--validate-->|            |            |
 |            |            |            |--credit---->|            |
 |            |            |            |<--credited--|--event---->|
 |            |            |            |--commission>|            |
 |<--receipt--|<---200-----|<-----------|            |            |
```

### Offline Queue (Agent offline → sync later)
```
Customer     AgentPOS   LocalDB      SyncSvc      Service      CFE
 |            |            |            |            |            |
 |--cash----->|            |            |            |            |
 |            |--enqueue-->|            |            |            |
 |<--receipt--|            |            |            |            |
 |            |   ... network restored ...           |            |
 |            |            |--flush----->|            |            |
 |            |            |            |--POST----->|            |
 |            |            |            |            |--credit--->|
 |            |            |            |            |<--credited-|
 |            |            |<--confirmed-|<-----------|            |
 |            |--dequeue-->|            |            |            |
```

### Reconciliation Match/Mismatch
```
Servic     EODBatch    ReconEng    Ledger      BankFile     Ops
 |            |            |            |            |            |
 |--close---->|            |            |            |            |
 |            |--collect-->|            |            |            |
 |            |            |--pull------|            |            |
 |            |            |--pull-------------------->|            |
 |            |            |--compare--->|            |            |
 |            |            |            |            |            |
 |            |            |--MATCH--->|            |            |
 |            |            |            |            |            |
 |            |            |   OR                          |      |
 |            |            |--MISMATCH-------------------->|      |
 |            |            |            |            |--alert-->|
```

---

## 3. FX Rate Lock + Conversion

### Normal Flow
```
User         App          FXEngine     RateProv     RemitSvc     CFE
 |            |            |            |            |            |
 |--quote---->|            |            |            |            |
 |            |--fetch---->|            |            |            |
 |            |            |--request--------------->|            |
 |            |            |<--rate+expiry------------|            |
 |<--quote----|<--rate-----|            |            |            |
 |            |            |            |            |            |
 |--accept--->|            |            |            |            |
 |            |--lock----->|            |            |            |
 |            |            |--convert-->|            |            |
 |            |            |--hold------|            |--post----->|
 |            |            |<--complete-|            |            |
 |<--confirmed|<-----------|            |            |            |
```

### Rate Expired
```
User         App          FXEngine     Scheduler    RemitSvc
 |            |            |            |            |
 |--quote---->|--fetch---->|            |            |
 |            |<--rate-----|            |            |
 |            |            |            |            |
 |            |            |<--tick-----|            |
 |            |            |--expire--->|            |
 |            |<--rate_expired           |            |
 |--requote-->|            |            |            |
 |            |--fetch---->|            |            |
 |            |<--new_rate-|            |            |
```

### Provider Failover
```
FXEngine     PrimaryProv  HealthChk    SecondaryProv
 |            |            |            |
 |--request--------------->|            |
 |            |            |            |
 |            |--timeout-->|            |
 |            |<--no_resp--|            |
 |            |            |            |
 |--mark_down------------->|            |
 |            |            |            |
 |--request--------------------------->|
 |            |            |<--healthy--|
 |<--rate-----|            |            |
```

---

## 4. Remittance (Diaspora → Syria)

### Normal
```
Sender       App          API          RemitSvc     AML/FX       CFE    Recipient_SYR
 |            |            |            |            |            |          |
 |--EUR100--->|            |            |            |            |          |
 |            |--POST----->|            |            |            |          |
 |            |            |--validate-->|            |            |          |
 |            |            |            |--rate_lock->|            |          |
 |            |            |            |<-rate_locked|            |          |
 |            |            |            |--aml_check->|            |          |
 |            |            |            |<--pass------|            |          |
 |            |            |            |--convert--->|            |          |
 |            |            |            |--hold------>|            |          |
 |            |            |            |--disburse-------------->|          |
 |            |            |            |            |            |--transfer|
 |<--sent-----|<---200-----|<-----------|            |            |          |
```

### Sanctions Hit
```
Sender       App          API          RemitSvc     Screening    AML      Compliance
 |            |            |            |            |            |          |
 |--EUR100--->|--POST----->|--validate-->|            |            |          |
 |            |            |            |--screen--->|            |          |
 |            |            |            |<--MATCH-----|            |          |
 |            |            |            |            |--"SyriaSanctions"      |
 |            |            |            |--BLOCK---->|            |          |
 |            |            |            |--alert-------------->|            |
 |<--blocked--|<---451-----|<-----------|            |            |--review->|
```

### AML Review
```
RemitSvc     AML          Compliance     Queue       Scheduler
 |            |            |              |            |
 |--flag----->|            |              |            |
 |            |--enqueue-->|              |            |
 |            |            |--pull------->|            |
 |            |            |              |            |
 |            |            |   ... 4 hours later ...  |
 |            |            |              |            |
 |            |            |              |<--tick-----|
 |            |            |--escalate--->|            |
 |<--approved-|<--clear----|              |            |
```

---

## 5. Merchant QR Payment

### Normal
```
Customer     POS          API          MerchantSvc  CustomerW    CFE
 |            |            |            |            |            |
 |--scan----->|            |            |            |            |
 |            |--POST----->|            |            |            |
 |            |            |--validate-->|            |            |
 |            |            |            |--debit---->|            |
 |            |            |            |--credit----|            |
 |            |            |            |--fee-------|            |
 |<--success--|<---200-----|<-----------|            |            |
```

### Network Loss
```
Customer     POS        LocalQueue   NetworkBck   API          Service
 |            |            |            |            |            |
 |--scan----->|            |            |            |            |
 |            |--enqueue-->|            |            |            |
 |<--pending--|            |            |            |            |
 |            |            |            |            |            |
 |            |            |--flush---->|            |            |
 |            |            |            |--POST----->|            |
 |            |            |            |            |--process-->|
 |            |            |            |<--complete-|            |
 |            |<--confirmed-<------------|            |            |
```

### Refund
```
Customer     API          MerchantSvc  CFE
 |            |            |            |
 |--refund--->|            |            |
 |            |--validate-->|            |
 |            |            |--reverse-->|
 |            |            |<--reversed-|
 |<--refunded-|<-----------|            |
```

---

## 6. Savings Auto-save

### Scheduled Execution
```
Scheduler    SavingsSvc   WalletSvc    CFE          Queue
 |            |            |            |            |
 |--tick----->|            |            |            |
 |            |--fetch_due->|            |            |
 |            |<--goals----|            |            |
 |            |            |            |            |
 |            |--debit---->|            |            |
 |            |            |--hold----->|            |
 |            |            |<--held-----|            |
 |            |            |--post----->|            |
 |            |            |<--posted---|--event---->|
 |            |<--done-----|            |            |
```

### Insufficient Balance
```
Scheduler    SavingsSvc   WalletSvc
 |            |            |
 |--tick----->|            |
 |            |--debit---->|
 |            |<--insuff---|
 |            |            |
 |            |--retry(2)-->|  (3 attempts total)
 |            |<--insuff---|
 |            |            |
 |            |--pause---->|  (auto-pause goal)
```

### Manual Pause/Resume
```
User         App          API          SavingsSvc
 |            |            |            |
 |--pause---->|            |            |
 |            |--PUT------>|            |
 |            |            |--set_paused>|
 |<--paused---|<---200-----|<-----------|
 |            |            |            |
 | ... next month ...      |            |
 |            |            |            |
 |--resume--->|            |            |
 |            |--PUT------>|            |
 |            |            |--set_active>|
 |<--resumed--|<---200-----|<-----------|
```

---

## 7. Financing Disbursement

### Approval → Disbursement → First Repayment
```
User         App          LoanSvc      Underwriter  CFE          Collection
 |            |            |            |            |            |
 |--apply---->|            |            |            |            |
 |            |--submit--->|            |            |            |
 |            |            |--credit----|            |            |
 |            |            |--risk------|            |            |
 |            |            |            |            |            |
 |            |            |--pending-->|            |            |
 |            |            |<--approve--|            |            |
 |<--offer----|<--approve--|            |            |            |
 |            |            |            |            |            |
 |--accept--->|            |            |            |            |
 |            |            |--disburse------------->|            |
 |            |            |<--credited--------------|            |
 |<--funded---|<--done-----|            |            |            |
 |            |            |            |            |            |
 | ... 30 days later ...   |            |            |            |
 |            |            |            |            |            |
 |            |            |--schedule-------------->|            |
 |            |            |<--deducted--------------|            |
 |            |            |--apply_penalty          |            |
```

---

## 8. Bill Payment

### Fetch → Pay → Confirm
```
User         App          BillPaySvc   BillerAPI    CFE
 |            |            |            |            |
 |--select--->|            |            |            |
 |            |--fetch---->|            |            |
 |            |            |--query---->|            |
 |            |            |<--amount---|            |
 |<--due------|<--bill-----|            |            |
 |            |            |            |            |
 |--confirm-->|            |            |            |
 |            |--pay------>|            |            |
 |            |            |--hold----->|            |
 |            |            |--post----->|            |
 |            |            |--notify--->|            |
 |            |            |<--confirm--|            |
 |<--paid-----|<--done-----|            |            |
```

### Biller Timeout + Partial Payment
```
BillPaySvc   BillerAPI    RetryQueue   Ops
 |            |            |            |
 |--pay------>|            |            |
 |            |--timeout-->|            |
 |            |            |            |
 |--retry(1)-------->|    |            |
 |<--timeout---|            |            |
 |--retry(2)-------->|    |            |
 |<--timeout---|            |            |
 |--retry(3)-------->|    |            |
 |<--timeout---|            |            |
 |            |            |            |
 |--PARTIAL--->|            |            |
 |<--partial--|            |            |
 |            |            |            |
 |--enqueue-------------->|            |
 |            |            |--alert--->|
```

---

## 9. Settlement Batch

### EOD Collect → Net → Reconcile → Settle
```
Scheduler    SettleEng    Ledger       BankAPI      ReconEng     Ops
 |            |            |            |            |            |
 |--trigger-->|            |            |            |            |
 |            |--collect-->|            |            |            |
 |            |            |--txns----->|            |            |
 |            |<--all_txns-|            |            |            |
 |            |            |            |            |            |
 |            |--net------>|            |            |            |
 |            |<--positions|            |            |            |
 |            |            |            |            |            |
 |            |--reconcile------------->|            |            |
 |            |<--match----|            |            |            |
 |            |            |            |            |            |
 |            |--settle--------------->|            |            |
 |            |<--done-----------------|            |            |
 |<--complete-|            |            |            |            |
```

### Mismatch → Manual Review → Force Settle
```
SettleEng    ReconEng     Ops          Finance
 |            |            |            |
 |--reconcile->|            |            |
 |<--MISMATCH-|            |            |
 |            |            |            |
 |--alert--------------->|            |
 |            |            |            |
 |            |            |--review--->|
 |            |            |--match?    |
 |            |            |            |
 |            |            |--force----|            |
 |            |<--settle---|            |--approve->|
 |            |            |            |<--yes-----|
 |--complete->|            |            |
```

---

## 10. Card Transaction

### Auth → Clear → Settle
```
Cardholder   POS/Terminal  CardSvc     Processor    Issuer       CFE
 |            |            |            |            |            |
 |--tap------>|            |            |            |            |
 |            |--auth----->|            |            |            |
 |            |            |--auth----->|            |            |
 |            |            |            |--auth----->|            |
 |            |            |<--approved-|<--approved-|            |
 |<--approved-|<--approved-|            |            |            |
 |            |            |            |            |            |
 |            |  --- clearing batched ---           |            |
 |            |--clear---->|            |            |            |
 |            |            |--clear---->|            |            |
 |            |            |            |--clear---->|            |
 |            |            |            |<--posted---|--hold----->|
 |            |            |            |            |            |
 |            |  --- settlement next day ---         |            |
 |            |            |--settle--->|            |            |
 |            |            |            |--settle--->|            |
 |            |            |            |<--settled--|--release-->|
```

### Decline + Chargeback
```
Cardholder   POS          CardSvc      Processor    FraudEng
 |            |            |            |            |
 |--tap------>|            |            |            |
 |            |--auth----->|            |            |
 |            |            |--auth----->|            |
 |            |            |            |--auth----->|  -- OK
 |            |<--decline---<--decline--|<--decline--|
 |<--declined-|            |            |            |
 |            |            |            |            |
 | ... 2 weeks later ...   |            |            |
 |            |            |            |            |
 |--dispute-->|            |            |            |
 |            |--chargeback->|            |            |
 |            |            |--chargeback>|            |
 |            |            |            |--reversal->|
 |            |            |<--reversed-|            |
 |<--refunded-|<-----------|            |            |
```

---

## 11. User Registration

### Phone → OTP → PIN → KYC → Wallet
```
User         App          AuthSvc      KYCService   WalletSvc    CFE
 |            |            |            |            |            |
 |--phone---->|            |            |            |            |
 |            |--send_otp->|            |            |            |
 |<--otp_sent-|            |            |            |            |
 |            |            |            |            |            |
 |--otp------>|            |            |            |            |
 |            |--verify--->|            |            |            |
 |<--verified-|<--ok-------|            |            |            |
 |            |            |            |            |            |
 |--pin------>|            |            |            |            |
 |            |--set_pin-->|            |            |            |
 |            |            |            |            |            |
 |--name+id-->|            |            |            |            |
 |            |--submit--->|            |--verify--->|            |
 |            |            |            |            |            |
 |            |            |            |<--approved-|            |
 |            |            |            |--create--->|            |
 |            |            |            |            |--open---->|
 |<--welcome--|<--done-----|<-----------|<-----------|<--ready---|
```

### Duplicate Registration
```
User         App          AuthSvc      KYCService
 |            |            |            |
 |--phone---->|            |            |
 |            |--send_otp->|            |
 |<--otp_sent-|            |            |
 |            |            |            |
 |--otp------>|            |            |
 |            |--verify--->|            |
 |            |            |            |
 |            |--check_dup->|            |
 |            |<--DUPLICATE|            |
 |<--error----|<--409------|            |
 | "already registered"    |            |
```

---

## 12. Compliance Screening

### Txn → Sanctions Check → AML Rules → Decision
```
Transaction  Screening    SanctionsDB  AMLEngine    Compliance   Queue
 |            |            |            |            |            |
 |--txn------>|            |            |            |            |
 |            |            |            |            |            |
 |       --- Sanctions Screening ---    |            |            |
 |            |--query---------------->|            |            |
 |            |<--hit/no_hit------------|            |            |
 |            |            |            |            |            |
 |       --- AML Rule Evaluation ---    |            |            |
 |            |--evaluate-------------->|            |            |
 |            |<--score+rules-----------|            |            |
 |            |            |            |            |            |
 |       --- Decision ---               |            |            |
 |            |            |            |            |            |
 |   CLEAR ──>|--allow---->|            |            |            |
 |            |            |            |            |            |
 |   HOLD ───>|--enqueue--------------------------->|            |
 |            |            |            |            |--review--->|
```

### False Positive Lifecycle
```
Screening    Compliance   Queue        Transaction
 |            |            |            |
 |--HIT------>|--enqueue-->|            |
 |            |            |--dequeue-->|
 |            |            |            |
 |            |--investigate           |
 |            |--confirm_FP            |
 |            |            |            |
 |            |--override-->|            |
 |            |            |--approve-->|
 |            |            |            |
 |<--cleared--|            |            |
```

---

> **12 flow diagrams** covering happy path, failures, and resilience patterns.
