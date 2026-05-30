# 31. Settlement — Failure Scenarios

> **Purpose:** Document every failure mode for the Beza Settlement feature — inter-bank settlement, reconciliation, batch processing, nostro/vostro accounts, and end-of-day (EOD) settlement. Uses real ETB amounts and Arabic/Amharic messaging.

---

## 1. Network Failures

### Internet cut during EOD settlement file transfer to CBE
**System Behavior:** The settlement batch is generated and ready for transfer. The SFTP connection to CBE drops mid-transfer. The file is not delivered. The batch is marked as `PENDING_BANK_TRANSFER`.

**User Impact:** The settlement is delayed. The previous day's funds are not yet available in the merchant or agent bank accounts. Day+1 settlement is expected.

**Recovery:** The SFTP transfer is retried every 5 minutes. If the delay exceeds 2 hours, the operations team manually uploads the file through the CBE corporate portal.

### API timeout (>5s) during settlement balance reconciliation
**System Behavior:** The reconciliation engine makes API calls to partner banks to verify balances. The call times out. The reconciliation cannot be completed for that bank.

**User Impact:** A settlement mismatch may go undetected. "التسوية معلقة — التحقق من الأرصدة" (Settlement pending — verifying balances.)

**Recovery:** The reconciliation is retried every 30 minutes. If the API is unavailable for more than 4 hours, a manual reconciliation is performed.

### DNS failure for settlement-api.beza.et
**System Behavior:** All settlement services are inaccessible. The end-of-day processes cannot run. All Beza operations continue, but settlement is delayed.

**User Impact:** No immediate impact on end users. Backend settlement processes are blocked. "خدمة التسوية غير متوفرة" (Settlement service unavailable.)

**Recovery:** Route53 failover to the secondary region is triggered. The settlement is deferred until the service is restored.

### Network partition between settlement service and core ledger service
**System Behavior:** The settlement engine cannot read the current ledger state because the network is partitioned. The settlement cycle cannot be verified.

**User Impact:** The settlement is skipped for the current cycle. "دورة التسوية غير مكتملة" (Settlement cycle incomplete.)

**Recovery:** When connectivity is restored, a catch-up settlement runs automatically, processing all missed cycles.

### SWIFT FIN network outage
**System Behavior:** The SWIFT FIN network is unavailable. Settlement instructions to correspondent banks cannot be sent. Cross-border settlements are delayed.

**User Impact:** Cross-border settlement instructions are queued. Nostro account reconciliation is delayed.

**Recovery:** SWIFT's store-and-forward mechanism queues the messages. They are delivered when the network is restored. No messages are lost.

## 2. Transaction Failures

### Settlement batch out of balance — total debits do not equal total credits (difference: 500 ETB)
**System Behavior:** The batch validation step detects that `total_debits - total_credits = 500 ETB`. The entire batch is rejected to prevent an unbalanced settlement.

**User Impact:** No settlements are processed for that batch. All parties wait. "دفعة التسوية غير متوازنة" (Settlement batch is unbalanced.)

**Recovery:** The operations team reviews the individual transactions in the batch. A single transaction is found to have a missing 500 ETB fee. The fee is corrected and the entire batch is reprocessed.

### Duplicate settlement file sent to the bank — same batch sent twice
**System Behavior:** The bank's system receives a second copy of the settlement file. The bank detects the duplicate file reference and rejects the second submission.

**User Impact:** No double settlement occurs. The bank processes only the first file.

**Recovery:** The bank sends an acknowledgment (ACK) with the file reference number. The second submission is rejected with `DUPLICATE_FILE_REF`.

### Partner bank rejects settlement due to invalid account number
**System Behavior:** The bank returns `INVALID_ACCOUNT` for a merchant's settlement entry. The bank rejects that specific item but processes the rest of the batch.

**User Impact:** That specific merchant or agent is not settled. All others in the batch are settled normally.

**Recovery:** The operations team corrects the account number. The failed item is retried in the next settlement batch. The affected party is notified.

### Settlement amount exceeds the bank transfer limit (50,000,000 ETB per transfer)
**System Behavior:** The settlement amount of 75,000,000 ETB exceeds the single transfer limit. The settlement engine automatically splits it into two transfers: 50,000,000 ETB and 25,000,000 ETB.

**User Impact:** The receiver sees two incoming credits. No user impact.

**Recovery:** The auto-split logic is configurable and checks the limit before each submission. Both transfers are processed sequentially.

### Partial settlement — some transactions in a batch settle, others fail
**System Behavior:** Partial settlement is not permitted. The batch is all-or-nothing. If any single transaction fails, the entire batch is rejected and retried.

**User Impact:** No one gets settled. The entire batch waits for the failed transaction to be fixed. "جميع المعاملات في الدفعة معلقة بسبب فشل إحداها" (All transactions in the batch are held because one failed.)

**Recovery:** The individual failing transaction is identified and corrected. The entire batch is reprocessed.

### Settlement reversal needed — a transaction already settled needs to be reversed
**System Behavior:** A previously settled transaction is identified for reversal. The reversal is processed as a separate negative settlement item in the next batch.

**User Impact:** The original recipient's account is debited. The sender is credited. Both parties are notified. "تم عكس التسوية للمعاملة #12345" (Settlement reversed for transaction #12345.)

**Recovery:** The net settlement for the next cycle includes the reversal. The system ensures the reversal is processed before any new settlements for the affected parties.

## 3. External Dependency Failures

### CBE (Commercial Bank of Ethiopia) real-time gross settlement (RTGS) system down
**System Behavior:** All inter-bank settlements are queued. Beza holds the settlement amounts in its settlement account. No funds are moved between banks.

**User Impact:** Merchants, agents, and users waiting for settlement funds see delays. "نظام التسوية بين البنوك غير متاح حالياً" (Inter-bank settlement system is currently unavailable.)

**Recovery:** The RTGS system is typically restored within 2 hours. Queued settlements are processed in batch when the system is restored.

### NBE (National Bank of Ethiopia) settlement system API timeout
**System Behavior:** The central bank's settlement gateway is unreachable. Beza cannot complete the end-of-day settlement. All settlement is frozen.

**User Impact:** All Beza settlement is frozen. Funds are locked until the NBE system recovers.

**Recovery:** The operations team contacts the NBE directly. If the system is down for more than 4 hours, manual settlement is performed using SWIFT MT103 messages.

### Partner bank (Dashen, Abyssinia) system down for settlement confirmation
**System Behavior:** The partner bank receives the settlement file but the acknowledgment (ACK) is not sent back to Beza because the bank's system is down.

**User Impact:** Beza shows settlement as `PENDING_CONFIRMATION`. There is a risk of double settlement if the file is resent.

**Recovery:** Beza polls the bank's statement for incoming credits. When the credits appear, the settlement is confirmed. The operations team verifies manually with the bank.

### SWIFT GPI tracker unavailable
**System Behavior:** The SWIFT GPI (Global Payments Innovation) tracker cannot be reached. The real-time tracking of international settlements is not available.

**User Impact:** The cross-border settlement status shows "قيد التنفيذ" (In progress) without the detailed tracking information.

**Recovery:** The system falls back to MT103 confirmation messages. GPI tracking resumes when the service is restored.

### Automated Clearing House (ACH) Ethiopia system failure
**System Behavior:** The ACH batch for direct debits and credits cannot be processed. All ACH-based settlements are delayed.

**User Impact:** All ACH-based settlements are delayed by 1 day. "تأخير في نظام المقاصة الآلية" (ACH system delay.)

**Recovery:** The ACH batch is resubmitted in the next cycle. Any late fees incurred due to the delay are waived.

## 4. Data Consistency Failures

### Settlement file generated with wrong amounts — rounding error (total off by 0.01 ETB)
**System Behavior:** A floating-point precision error causes a 0.01 ETB discrepancy in the batch total. The batch validation rejects the file.

**User Impact:** The entire settlement batch (10,000,000 ETB) is held because of a 0.01 ETB error.

**Recovery:** The operations team manually overrides the tolerance check (configured to accept errors up to 1 ETB). The batch is processed. The precision bug is fixed in the calculation engine.

### Cache inconsistency — ledger balance cached as 5,000,000 ETB but actual is 4,999,500 ETB
**System Behavior:** The settlement engine reads the cached ledger balance (500,000 ETB) instead of the actual balance. The settlement file is generated for 5,000,000 ETB.

**User Impact:** The settlement is rejected by the bank because Beza's settlement account has insufficient funds.

**Recovery:** The cache is invalidated. The fresh balance of 4,999,500 ETB is read. The settlement file is regenerated with the correct amount.

### Settlement event lost — settlement confirmation event not published to wallet service
**System Behavior:** The settlement is completed at the bank level. The confirmation event is not published to the wallet service. The wallet service does not update merchant balances.

**User Impact:** Merchants see pending settlement status even though the funds have been settled. "تم التسوية ولكن لم يتم تحديث الرصيد بعد" (Settled but balance not yet updated.)

**Recovery:** The dead-letter queue consumer replays the settlement confirmation event. The wallet balance is updated within 5 minutes.

### Dual-write between settlement service and partner bank fails partially
**System Behavior:** Beza records the settlement as `COMPLETED` in its database. The partner bank did not receive the funds due to a communication failure.

**User Impact:** Beza shows settled. The bank says no settlement occurred. The customer is confused. "ادعاء عدم التسوية من البنك" (Bank disputes settlement.)

**Recovery:** The reconciliation job detects the discrepancy — Beza says settled but the bank says no. Beza re-sends the funds or reverses the settlement entry.

### Settlement cut-off time crossed — settlement started at 4:00 PM but takes until 5:30 PM (cut-off is 5:00 PM)
**System Behavior:** Transactions processed after 5:00 PM are moved to the next day's settlement batch. The system clearly separates pre-cut-off and post-cut-off transactions.

**User Impact:** Late transactions settle on the next business day. Users are notified. "تمت تسوية معاملات اليوم التالي" (Settled on the next business day.)

**Recovery:** The cut-off time is displayed to users. Transactions initiated after 4:00 PM show a warning "قد تتم التسوية غداً" (May settle tomorrow.)

## 5. Security Failures

### Fraud false positive — large settlement to a legitimate merchant flagged as suspicious
**System Behavior:** The AML rules engine triggers on a settlement of more than 5,000,000 ETB to a single merchant. The settlement is placed in `PENDING_REVIEW`.

**User Impact:** The merchant's settlement is delayed. "التسوية قيد المراجعة من قبل الامتثال" (Settlement under compliance review.)

**Recovery:** The compliance team reviews the merchant's transaction history and KYC documentation within 4 hours. If everything is legitimate, the settlement is released.

### Fraud false negative — settlement rerouted to an attacker's bank account
**System Behavior:** An attacker compromises the admin panel and changes the settlement bank account details for a merchant. The next settlement of 2,000,000 ETB is sent to the attacker's account.

**User Impact:** The legitimate merchant is not paid. The attacker receives 2,000,000 ETB.

**Recovery:** Settlement bank account changes require dual approval plus a 48-hour cooling period. An email and SMS notification are sent to the merchant. The SIEM system alerts on any bank account change.

### Unauthorized access to settlement admin panel
**System Behavior:** An attacker gains access to the settlement admin panel and triggers a manual settlement for fictitious transactions. 5,000,000 ETB is created from nothing and sent to the attacker's account.

**User Impact:** Beza's ledger is inflated fraudulently. 5,000,000 ETB is paid out without corresponding transactions.

**Recovery:** Manual settlements require 2-person authorization plus MFA. An audit log entry triggers an immediate SIEM alert. The attack is detected within minutes.

### Settlement file tampered in transit — SFTP file modified after generation
**System Behavior:** The settlement file is intercepted and modified after generation. The file integrity is verified using a SHA-256 hash. The hash check fails and the file is rejected.

**User Impact:** The settlement is delayed. The file must be regenerated. "تم اكتشاف تلاعب في ملف التسوية" (Settlement file tampering detected.)

**Recovery:** The file is regenerated from the immutable ledger. The hash is verified by both Beza and the bank. The security incident is investigated.

### Insider threat — settlement operator creates ghost settlements
**System Behavior:** A settlement operator creates 100 phantom settlement entries of 10,000 ETB each over a 6-month period. Total stolen: 1,000,000 ETB.

**User Impact:** No individual user is impacted. Beza loses 1,000,000 ETB cumulatively.

**Recovery:** A monthly settlement audit reconciles the total settled amount against the total transaction fees collected. An anomaly detection system flags manual settlements for review.

## 6. Business Logic Failures

### Insufficient reserve for settlement — Beza settlement account has 2,000,000 ETB but needs 2,500,000 ETB
**System Behavior:** The settlement engine detects a shortfall of 500,000 ETB. It applies a prioritization order: consumer wallets first, then agents, then merchants.

**User Impact:** Merchants are partially settled (500,000 ETB is pending). "تسوية جزئية بسبب نقص الاحتياطي" (Partial settlement due to reserve shortfall.)

**Recovery:** The reserve is topped up from the operational account. The remaining amount is settled within 2 hours. The reserve monitoring threshold is set at 120% of the average daily settlement.

### Weekend settlement — banks closed, inter-bank settlement not possible
**System Behavior:** The settlement is processed on the Beza ledger immediately. The bank transfer is deferred to the next business day.

**User Impact:** Users see the credit in their Beza wallet instantly. Bank transfers are delayed. "سيتم تحويل الأموال إلى البنك يوم الأحد" (Funds will be transferred to the bank on Sunday.)

**Recovery:** Internal settlement (Beza wallet to Beza wallet) is instant. External bank settlement is processed on the next business day.

### Currency mismatch in settlement — merchant settled in ETB but bank account is USD
**System Behavior:** The settlement engine checks the currency of the merchant's bank account against the settlement currency. The currencies do not match. The settlement is held.

**User Impact:** The merchant sees "حساب التسوية بعملة مختلفة. يرجى تحديث معلومات الحساب" (Settlement account is in a different currency. Please update your account information.)

**Recovery:** The merchant can add an ETB bank account or request Beza to convert at the current FX rate plus a conversion fee.

### Settlement frequency mismatch — merchant requests daily settlement but system processes weekly
**System Behavior:** The merchant was incorrectly categorized during onboarding. The settlement frequency is set to weekly instead of the requested daily settlement.

**User Impact:** The merchant waits 7 days for settlement instead of 1 day. "التسوية أسبوعية بدلاً من يومية" (Weekly settlement instead of daily.)

**Recovery:** The merchant contacts customer support. The settlement frequency is corrected. All pending daily amounts are settled immediately.

### Reconciliation deadlock — two settlement batches try to process the same transactions
**System Behavior:** Optimistic locking prevents the double settlement. The second batch fails with a lock exception. No double payment occurs.

**User Impact:** No user impact. The second batch is marked as `REJECTED_DUPLICATE`.

**Recovery:** The operations team reviews the two batches. They are merged into a single batch. The settlement is reprocessed.

## 7. Performance & Scalability Failures

### EOD settlement spike — 500,000 transactions settled in one batch
**System Behavior:** The end-of-day settlement engine processes 500,000 transactions in a single batch. The batch file is 500MB. File generation takes 45 minutes.

**User Impact:** Settlement is delayed by 45 minutes. Merchants and agents see funds as "جاري التسوية" (Settling) longer than usual.

**Recovery:** Settlement is split into 5 regional batches of 100,000 transactions each. Batches are processed in parallel across 5 worker pods. Total processing time is reduced to 12 minutes.

### Bank file transfer congestion — 10 settlement files queued for CBE simultaneously
**System Behavior:** 10 regional settlement files are ready for transfer to CBE simultaneously. The SFTP connection pool to CBE has 3 concurrent connections. 7 files queue.

**User Impact:** Some regions experience settlement delays of up to 15 minutes.

**Recovery:** The SFTP connection pool is increased to 10 concurrent connections. File transfers are prioritized by value (largest settlements first).

### Reconciliation job timeout — 1 million records to reconcile
**System Behavior:** The daily reconciliation job compares 1 million Beza records against bank statements. The job runs for 6 hours, exceeding the 4-hour SLA window.

**User Impact:** Settlement discrepancies are not detected until the next day. Potential mismatches go unnoticed for 24 hours.

**Recovery:** Reconciliation is partitioned by date (last 7 days) and processed incrementally. Full reconciliation runs weekly. Incremental reconciliation runs daily in 1 hour.

## 8. Operational Failures

### Deployment rollback — v2.5.0 skips fee settlement for agent transactions
**System Behavior:** The canary deployment detects a 10% decrease in settlement amounts (fees not included). The rollback is triggered.

**User Impact:** Approximately 1,000 agent transactions are settled without their commission fees. Total uncollected fees: 50,000 ETB.

**Recovery:** The rollback completes within 2 minutes. The missed fees are included in the next settlement batch. Affected agents receive the fees with an apology note.

### Configuration error — settlement frequency changed from daily to monthly
**System Behavior:** A configuration change sets the merchant settlement frequency to monthly instead of daily. 200 merchants are switched to monthly settlement.

**User Impact:** 200 merchants do not receive daily settlements for 30 days. Cash flow is severely impacted.

**Recovery:** A monitoring alert fires on the settlement frequency change. The configuration is reverted within 10 minutes. A one-time catch-up settlement is triggered for all affected merchants.

### Partner bank account closed — settlement fails for 50 merchants
**System Behavior:** The settlement bank account for 50 merchants is closed by the partner bank without notice. Settlement transfers fail with `ACCOUNT_CLOSED`.

**User Impact:** 50 merchants do not receive their settlement funds. Funds are held in Beza's settlement account.

**Recovery:** The merchants are notified to update their bank account details. A check payment is issued as a one-time alternative. A bank account validation check is added before each settlement.

## 9. Recovery Time Objectives Summary

| Failure Category | Detection Time | Recovery Time | RPO | Maximum Impact |
|-----------------|----------------|---------------|-----|----------------|
| Network (transient) | < 1 second | < 30 seconds | 0 | Single batch delayed |
| Network (DNS outage) | 30 seconds | < 5 minutes | 0 | All settlement blocked |
| Transaction failure | < 100ms | < 5 seconds | 0 | Single item failed |
| External dependency | < 10 seconds | < 4 hours | 0 | Bank/Central bank down |
| Data inconsistency | < 5 minutes | < 2 hours | < 5 seconds | Settlement mismatch |
| Security incident | < 1 minute | < 4 hours | 0 | Settlement held for review |
| Business logic | < 1 hour | < 24 hours | 0 | Insufficient reserve |
| Performance degradation | < 1 minute | < 5 minutes | 0 | Slow batch processing |
| Operational (deployment) | < 5 minutes | < 10 minutes | < 1 minute | Missing fees in settlement |

## Changelog

| Date | Version | Changes |
|------|---------|---------|
| 2026-05-29 | 1.0 | Initial release — 7 categories covering network, transactions, external dependencies, data consistency, security, business logic, and performance failures for inter-bank settlement feature |

---

*Version: 1.0 | Last updated: 2026-05-29 | Owner: Settlement Engineering Team*
