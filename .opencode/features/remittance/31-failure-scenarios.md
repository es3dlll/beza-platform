# 31. Remittance — Failure Scenarios

> **Purpose:** Document every failure mode for the Beza Remittance feature — cross-border money transfers from the diaspora to Ethiopia. Covers sender debit, FX conversion, recipient credit, and corridor-specific failures. Uses real ETB/USD/EUR amounts and Arabic/Amharic messaging.

---

## 1. Network Failures

### Internet cut after sender payment confirmed but before FX lock completes
**System Behavior:** The sender's USD is debited from the Stripe/payment gateway wallet. The FX rate lock call to the CBX rate feed times out. The remittance is stuck in `FX_LOCK_PENDING` status. The sender's funds are held in a suspense account.

**User Impact:** The sender sees "تم خصم المبلغ. في انتظار تأكيد سعر الصرف" (Amount deducted. Awaiting exchange rate confirmation.) The recipient in Ethiopia sees nothing.

**Recovery:** The rate lock is retried with exponential backoff (5s, 15s, 45s). If the CBX feed is unreachable for more than 2 minutes, the system holds the sender's funds and continues retrying for 15 minutes. On success, the rate is locked at the next available rate within a ±0.5% tolerance of the original quote.

### API timeout (>5s) during recipient wallet validation in Ethiopia
**System Behavior:** The remittance service times out while checking if the recipient's wallet is active. The system uses a fail-open strategy with a risk flag — it assumes the wallet is valid but marks the transaction for additional verification.

**User Impact:** The sender proceeds with a warning "قد تتأكد صحة حساب المستلم عند المعالجة" (The recipient account will be verified during processing.)

**Recovery:** If the recipient wallet is found to be invalid during processing, the remittance is held in `PENDING_RECIPIENT_CHECK` status. The sender is notified "يرجى تأكيد معلومات المستلم" (Please confirm the recipient's information.) The sender can correct the details or cancel for a full refund.

### DNS failure for remittance-api.beza.et during corridor partner callback
**System Behavior:** The corridor partner (e.g., a payout partner in Ethiopia) cannot send the status callback because the remittance API DNS is unreachable. The remittance is marked as `CALLBACK_PENDING`.

**User Impact:** No immediate user impact is visible. However, status updates are delayed until the callback is successfully processed.

**Recovery:** The partner retries the callback every 5 minutes for up to 1 hour. If the callback still fails after 1 hour, the operations team manually reconciles the remittance status by contacting the partner directly.

### WebSocket disconnect during real-time remittance status tracking
**System Behavior:** The sender's app detects the WebSocket disconnection and falls back to REST API polling, checking for status updates every 10 seconds.

**User Impact:** The sender sees "جاري التحديث..." (Updating...) on the status tracking page instead of a real-time progress bar. The status may appear to be delayed by up to 10 seconds.

**Recovery:** The WebSocket client reconnects automatically with exponential backoff. Once reconnected, all missed status events are replayed from the server. The status display is updated to the latest state immediately.

### Intermittent packet loss on USD to ETB corridor API (Stripe to Beza)
**System Behavior:** TCP retransmission causes a latency spike from 5 seconds to 15 seconds. The Kafka producer is configured with `max.in.flight.requests=1` to preserve message ordering during retransmission.

**User Impact:** The remittance processing is delayed by 10-15 seconds. The sender sees the processing spinner for longer than usual.

**Recovery:** The producer retries with exponential backoff. If delivery fails after 3 retries, the message is sent to a dead-letter queue on SQS for operations review.

## 2. Transaction Failures

### Insufficient sender balance — sender tries to send $500 USD but wallet has $420 USD
**System Behavior:** The pre-validation checks `usd_balance >= amount + fee`. The balance of $420 USD is less than $500 + $15 fee. The transaction is rejected at the gateway level.

**User Impact:** The sender sees "رصيد غير كافٍ. الرصيد المتاح: $420 USD" (Insufficient balance. Available balance: $420 USD.)

**Recovery:** The UI shows the maximum sendable amount including the fee calculation. The sender can top up their USD wallet using a linked bank card or bank transfer before retrying.

### Double send attempt — sender submits the same remittance twice in rapid succession
**System Behavior:** The idempotency key is constructed as `sender_remittance_hash(sender_id, recipient_phone, amount, currency)`. The second request returns HTTP 409 `DUPLICATE_REMITTANCE`.

**User Impact:** The sender sees "تم إرسال هذه الحوالة مسبقاً. تحقق من سجل الحوالات" (This remittance has already been sent. Please check your remittance history.)

**Recovery:** The first transaction processes normally. The second request is silently discarded. No duplicate charge is made to the sender's payment method.

### Duplicate idempotency key reused across different remittances (race condition)
**System Behavior:** The idempotency key is checked in Redis with a 48-hour TTL. If the same key is reused with different remittance parameters, the system returns HTTP 422 `IDEMPOTENCY_MISMATCH`.

**User Impact:** The sender sees "خطأ في إعادة المحاولة. يرجى بدء حوالة جديدة" (Retry error. Please start a new remittance.)

**Recovery:** The client SDK must generate a new UUIDv4 idempotency key for each unique remittance. The SDK enforces strict no-reuse policy through a monotonically increasing counter.

### FX rate expires during remittance — rate locked at 57.50 ETB/USD but lock validity (30s) expires
**System Behavior:** The orchestrator detects that the rate lock has expired. It requests a fresh rate from the CBX rate feed. If the new rate differs by more than 2% from the original rate, the system requires the sender to re-approve.

**User Impact:** The sender sees "انتهت صلاحية سعر الصرف. السعر الجديد: 57.80 ETB/USD. هل ترغب في المتابعة؟" (The exchange rate has expired. The new rate is 57.80 ETB/USD. Would you like to continue?)

**Recovery:** The sender must re-confirm the new rate. If the rate change is less than 2%, the remittance proceeds automatically. If greater than 2%, explicit consent is required through an in-app confirmation dialog.

### Partial credit — recipient wallet receives 8,500 ETB instead of 10,000 ETB (fee calculation error)
**System Behavior:** The fee calculation engine double-counts a processing fee. The recipient receives 8,500 ETB instead of the expected 10,000 ETB. The discrepancy is detected by the reconciliation system.

**User Impact:** The recipient sees 8,500 ETB credited. Later, a correction entry appears "تم تعديل المبلغ: +1,500 ETB" (Adjustment made: +1,500 ETB.)

**Recovery:** The operations team manually triggers the correction within 2 hours of detection. The root cause — a fee double-count in the fee calculation service — is identified and fixed. The sender and recipient are both notified.

### Remittance declined by compliance (OFAC/UN sanctions screening)
**System Behavior:** The sanctions screening engine returns a name similarity hit above the threshold. The transaction is set to `REJECTED_COMPLIANCE`. No funds are moved.

**User Impact:** The sender sees "تم رفض الحوالة بسبب متطلبات الامتثال. اتصل بخدمة العملاء" (The remittance has been rejected due to compliance requirements. Please contact customer service.)

**Recovery:** The sender can submit additional documentation to prove their identity and the legitimacy of the transaction. The compliance team reviews the documentation within 24 hours. If cleared, the remittance is released.

## 3. External Dependency Failures

### CBX (National Bank of Ethiopia) rate feed unavailable
**System Behavior:** The remittance service uses the last cached rate from Redis, which is valid for 30 minutes. If no cached rate is available because the TTL has expired, all new remittances are blocked.

**User Impact:** The sender sees "أسعار الصرف غير متوفرة حالياً. حاول مرة أخرى بعد 30 دقيقة" (Exchange rates are currently unavailable. Please try again in 30 minutes.)

**Recovery:** The operations team contacts the CBX directly by phone. A manual rate upload is performed through the admin panel with CBX telephone authorization. The service resumes automatically when the feed is restored.

### Stripe/payment gateway API timeout for USD collection
**System Behavior:** The Stripe `payment_intent.confirm` call hangs without a response. The remittance remains in `FUNDING_PENDING` status. The Stripe webhook endpoint waits for confirmation.

**User Impact:** The sender sees "جاري تأكيد الدفع. قد يستغرق حتى دقيقتين" (Payment is being confirmed. This may take up to 2 minutes.)

**Recovery:** The Stripe webhook retries the confirmation. If the timeout exceeds 2 minutes, the transaction is marked as `FUNDING_FAILED`. The sender can retry with a different card or payment method. Rate lock is extended for 15 minutes.

### SMS provider (InfoBip) unavailable for remittance confirmation
**System Behavior:** The SMS delivery is queued on SQS. The system falls back to the local Ethio Telecom SMPP connection. A push notification is sent via Firebase Cloud Messaging as the primary delivery channel.

**User Impact:** The sender and recipient may not receive the SMS confirmation immediately. The message "تم إرسال الإشعار عبر التطبيق" (The notification has been sent via the app) is shown.

**Recovery:** The SMS is queued and retried for up to 24 hours. If SMS continues to fail after 24 hours, a voice call fallback via Twilio is initiated for critical remittance confirmations.

### Partner bank in Ethiopia (Dashen, CBE) API timeout for direct bank deposit
**System Behavior:** The partner bank API for direct deposit into the recipient's bank account does not respond within the 30-second timeout. The remittance is marked as `DEPOSIT_PENDING`.

**User Impact:** The recipient sees "في انتظار تأكيد البنك" (Awaiting bank confirmation.) The funds are not yet available in their bank account.

**Recovery:** A poller checks the bank statement via the inquiry API every 60 seconds for up to 2 hours. On receiving confirmation from the bank, the status is updated. If the bank ultimately rejects the deposit, the funds are disbursed to the recipient's Beza wallet instead.

### SWIFT network delay for corridor partner settlement
**System Behavior:** The SWIFT gpi tracker shows that the payment is in progress but has not been confirmed by the receiving bank for more than 4 hours. An ops alert is triggered.

**User Impact:** The sender sees the status "قيد التحويل الدولي" (International transfer in progress) for longer than the expected 2-hour window.

**Recovery:** The operations team monitors the SWIFT gpi tracker. If the transfer exceeds 24 hours, a tracer is raised with the correspondent bank. The sender is provided with the SWIFT reference number for direct follow-up.

## 4. Data Consistency Failures

### DB write failure after USD debit but before ETB credit log
**System Behavior:** The ledger records the USD debit from the sender. The ETB credit write to the recipient's wallet fails. The Saga pattern detects the inconsistency within 2 seconds and triggers a compensatory debit reversal.

**User Impact:** The sender sees a reversal notification "تم إلغاء الحوالة وإعادة $500 USD" (The remittance has been cancelled and $500 USD has been returned.)

**Recovery:** The retry queue attempts the failed credit write 3 times (5s, 30s, 120s). If all retries fail, the compensation is finalized. The operations team is notified and performs a manual fix.

### Cache inconsistency — FX rate shown as 57.50 but actual rate is 57.80 at confirmation
**System Behavior:** The rate preview on the remittance initiation screen is read from the cache (TTL 30 seconds). The actual rate lock at confirmation time uses the current market rate from the CBX feed.

**User Impact:** The sender sees "تم تأكيد سعر الصرف: 57.80 ETB/USD" on the confirmation screen, which may differ from the previewed rate of 57.50.

**Recovery:** The rate preview explicitly shows a disclaimer "السعر قد يتغير" (The rate is subject to change.) The actual rate is locked at the time of confirmation. If the difference exceeds 0.5%, a warning is displayed before the sender confirms.

### Remittance event lost in Kafka — credit event never published to wallet service
**System Behavior:** The remittance status shows "مكتملة" (Completed) in the remittance service, but the credit event was never published to Kafka. The wallet service never receives the instruction to credit the recipient.

**User Impact:** The sender thinks the remittance was successful. The recipient has no funds. This is a silent data loss scenario.

**Recovery:** A dead-letter queue consumer checks for unprocessed events every 5 minutes. A reconciliation job between the remittance and wallet services runs every 1 hour. Any missing credits are detected and replayed. The recipient is credited retroactively.

### Dual-write to remittance DB and wallet DB fails partially
**System Behavior:** The remittance record is created in the remittance database. The wallet credit write fails silently due to a database connection pool exhaustion. No compensatory action is taken.

**User Impact:** The recipient is not credited. The sender is not notified. This is a potential data loss of $500 USD (approximately 28,750 ETB).

**Recovery:** A reconciliation batch detects orphan remittance records (those marked as completed but with no corresponding wallet credit) every 15 minutes. An automatic credit is triggered. An incident is raised for the engineering team.

### Transaction log corrupted due to disk I/O error (10,000 ETB remittance)
**System Behavior:** A database page corruption occurs on the `remittance_transactions` table due to a disk I/O error. The specific row containing the 10,000 ETB remittance becomes unreadable.

**User Impact:** The user may see the remittance missing from their transaction history. The error message "حدث خطأ في تحميل سجل الحوالات" (An error occurred while loading the remittance history) may appear.

**Recovery:** The DBA restores the corrupted page from the PostgreSQL Write-Ahead Log (WAL) within 5 minutes. The corrupted row is reconstructed from the immutable event log. The recovery point objective (RPO) is less than 1 second.

## 5. Security Failures

### Fraud false positive — diaspora sender sending $2,000 to uncle in Ethiopia flagged
**System Behavior:** The AML rules engine triggers on the combination of amount > $1,000, a newly added beneficiary, and a country risk score of 3. The transaction is placed in `PENDING_REVIEW`.

**User Impact:** The sender sees "الحوالة قيد المراجعة. سيتم إعلامك خلال 24 ساعة" (The remittance is under review. You will be notified within 24 hours.)

**Recovery:** The compliance team reviews the relationship proof (family connection documentation). Typically cleared within 4 hours. The sender is notified when the hold is lifted.

### Fraud false negative — compromised sender account used to send $5,000 to a mule
**System Behavior:** The behavioral model scores the transaction at 0.5, which is below the 0.7 threshold that would trigger an MFA challenge. The recipient is a known mule account but is not yet in the shared fraud database.

**User Impact:** The legitimate sender loses $5,000 USD. The recipient cashes out through the agent network within minutes of receiving the funds.

**Recovery:** The sender reports the fraud through the call center. Insurance covers 80% of the loss (up to $3,000). The mule's wallet is added to the shared fraud database. The fraud model is retrained with the new behavioral pattern.

### Unauthorized access to remittance admin panel
**System Behavior:** An attacker gains access to the operations dashboard through compromised credentials. The attacker initiates a manual remittance of 50,000 ETB to an accomplice's wallet without a corresponding sender consent.

**User Impact:** A fictitious remittance is created. The accomplice receives 50,000 ETB fraudulently. No legitimate user is directly impacted, but Beza's ledger shows an unbacked liability.

**Recovery:** The SIEM system alerts on any `ADMIN_MANUAL_REMITTANCE` event that does not have a corresponding support ticket. The admin panel requires MFA plus dual-approval for any manual financial operation. The attacker's access is revoked.

### Recipient identity theft — someone impersonates the recipient to claim the remittance
**System Behavior:** The recipient verification process detects a mismatch between the registered phone number and the NID database. The system escalates to enhanced verification.

**User Impact:** The legitimate recipient cannot claim the remittance. The funds are stuck in `PENDING_VERIFICATION` status.

**Recovery:** The recipient must visit a Beza branch in person with their original NID card for biometric verification. Once verified, the remittance is released. False claimants are reported to the authorities.

### Man-in-the-middle on sender's email — phishing to redirect remittance
**System Behavior:** An attacker intercepts the remittance confirmation email and modifies the recipient's bank details. The attacker changes the bank account number to their own account.

**User Impact:** The sender believes the money is being sent to the correct recipient. In reality, the funds are routed to the attacker's account.

**Recovery:** Beza sends a dual confirmation: an in-app notification plus an SMS to the sender's registered phone number, both containing the full recipient details. The sender must verify both channels before the remittance is finalized.

## 6. Business Logic Failures

### Rate lock expired before conversion — 57.50 ETB/USD locked at 10:00:00, valid 30s, conversion at 10:00:45
**System Behavior:** The system detects that the rate lock has expired (current time > expires_at). A fresh rate of 57.80 is fetched from the CBX feed. The change (0.52%) is within the 2% auto-approval threshold.

**User Impact:** The sender sees "تم تحديث سعر الصرف. السعر الجديد: 57.80 ETB/USD. هل توافق؟" (The exchange rate has been updated. The new rate is 57.80 ETB/USD. Do you agree?)

**Recovery:** The UI shows the old rate versus the new rate side by side. The sender can accept or cancel. If cancelled, the full $500 USD is refunded within 5 minutes.

### Recipient wallet frozen at the time of credit
**System Behavior:** The pre-credit check detects that the recipient's wallet status is `FROZEN`. The credit is held in `PENDING_RECIPIENT_CHECK` status. The funds are not lost but are not accessible to the recipient.

**User Impact:** The recipient sees that there is an incoming remittance but cannot access the funds. The message is "المبلغ متاح بعد إزالة تجميد الحساب" (The amount will be available after the account is unfrozen.)

**Recovery:** The system sends a notification to the recipient "قم بإزالة تجميد محفظتك لاستلام 10,000 ETB" (Unfreeze your wallet to receive 10,000 ETB.) Once the wallet is unfrozen, the credit is released automatically.

### Sender's bank card declined after remittance initiated
**System Behavior:** The Stripe `payment_intent.confirm` returns `card_declined`. The remittance status changes to `FUNDING_FAILED`. The rate lock is released.

**User Impact:** The sender sees "تم رفض البطاقة. يرجى استخدام بطاقة أخرى" (Card declined. Please use another card.)

**Recovery:** The sender can retry with a different card within the 15-minute rate lock extension window. If no successful payment is received within 15 minutes, the remittance is cancelled and the rate lock is fully released.

### Daily remittance limit exceeded for corridor (USD to ETB)
**System Behavior:** The aggregate daily volume for the USD to ETB corridor reaches the 500,000 USD daily cap. New remittance requests are placed in `QUEUED_FOR_NEXT_DAY` status.

**User Impact:** The sender sees "تم تجاوز الحد اليومي للتحويلات. ستتم المعالجة غداً" (The daily transfer limit has been reached. Your remittance will be processed tomorrow.)

**Recovery:** The queued remittances are processed at midnight East Africa Time (UTC+3). The sender receives a notification "تمت معالجة حوالاتك" (Your remittance has been processed.)

### Beneficiary information mismatch — name on NID vs recipient wallet name
**System Behavior:** The compliance check calculates `beneficiary_name_match` at 65%, which is below the 80% threshold. The transaction is flagged for manual compliance review.

**User Impact:** The recipient sees "يرجى تأكيد اسم المستلم في المحفظة ليتطابق مع الاسم في بطاقة الهوية" (Please confirm that the recipient name in the wallet matches the name on the national ID card.)

**Recovery:** The recipient updates their wallet name through the in-app KYC flow by submitting a new NID photo. The compliance team reviews and approves the name change within 2 hours. Once approved, the remittance is released.

## 7. Performance & Scalability Failures

### Sudden traffic spike — 8x remittance volume during Ethiopian Christmas diaspora peak
**System Behavior:** The remittance service auto-scales from 15 to 80 pods. The FX rate lock service must handle 500 concurrent rate lock requests. The CBX rate feed is polled every second instead of every 5 seconds.

**User Impact:** Diaspora senders experience 4-5 second latency instead of the normal 1 second. Rate lock approvals may take up to 10 seconds.

**Recovery:** Pre-scaling is triggered 2 hours before known peak periods based on historical data. The rate lock service batch-processes requests. The system stabilizes within 5 minutes.

### Large file processing — 50,000 bulk remittance file from institutional sender
**System Behavior:** The bulk remittance file processing service receives a 50,000 record file. Processing takes 15 minutes due to individual FX rate locks for each transaction.

**User Impact:** The institutional sender's file is queued. Processing is delayed behind individual user transactions.

**Recovery:** Bulk files are processed on a dedicated worker pool with lower priority than individual transactions. The sender is given an estimated completion time. Progress is tracked per batch.

### WebSocket fan-out overload — 100,000 simultaneous observers tracking one remittance
**System Behavior:** A high-value remittance (1,000,000 ETB) is tracked by 100,000 users who received a referral link. The WebSocket server handles 100,000 concurrent connections.

**User Impact:** The WebSocket server CPU spikes to 95%. Some users experience dropped connections and must refresh.

**Recovery:** WebSocket connections are rate-limited per IP. The fan-out is moved to a dedicated WebSocket cluster. Cached status updates are served for non-critical observers.

## 8. Operational Failures

### Deployment rollback — v5.2.0 applies wrong FX rate margin to diaspora remittances
**System Behavior:** The canary deployment detects a 30% increase in FX revenue (incorrectly charging 2% margin instead of 1%). The rollback is triggered automatically.

**User Impact:** Approximately 500 remittances were processed with a 1% higher margin. Senders were overcharged an average of $5 USD each.

**Recovery:** The rollback completes within 2 minutes. The overcharged amount is refunded to each affected sender with a 10% apology credit.

### Configuration error — recipient wallet validation disabled in production
**System Behavior:** A configuration change accidentally disables the recipient wallet validation check. Remittances are sent to invalid or closed wallets.

**User Impact:** 50 remittances are sent to invalid wallets. The funds are stuck in `PENDING_RECIPIENT_CHECK`.

**Recovery:** The configuration error is detected within 5 minutes by a monitoring alert. The validation is re-enabled. Affected remittances are reconciled manually.

### SFTP key rotation failure — bank file transfer fails after key expiry
**System Behavior:** The SFTP key used to send salary batch files to CBE expires. The file transfer fails. The key rotation was not completed on schedule.

**User Impact:** 10,000 employees' salary files are not delivered to CBE. Salaries are delayed by 1 day.

**Recovery:** The key is manually rotated within 30 minutes. The file transfer is retried. Automated key rotation with 30-day advance warning is implemented.

## 9. Recovery Time Objectives Summary

| Failure Category | Detection Time | Recovery Time | RPO | Maximum Impact |
|-----------------|----------------|---------------|-----|----------------|
| Network (transient) | < 1 second | < 30 seconds | 0 | Single remittance delayed |
| Network (DNS outage) | 30 seconds | < 5 minutes | 0 | All remittance ops blocked |
| Transaction failure | < 100ms | < 5 seconds | 0 | Single remittance failed |
| External dependency | < 10 seconds | < 15 minutes | 0 | FX rate unavailable |
| Data inconsistency | < 5 minutes | < 1 hour | < 5 seconds | Remittance status discrepancy |
| Security incident | < 1 minute | < 4 hours | 0 | Remittance held for review |
| Business logic | < 1 hour | < 24 hours | 0 | Rate expired / limit hit |
| Performance degradation | < 1 minute | < 5 minutes | 0 | Slow remittance processing |
| Operational (deployment) | < 5 minutes | < 10 minutes | < 1 minute | Incorrect fee applied |

## Changelog

| Date | Version | Changes |
|------|---------|---------|
| 2026-05-29 | 1.0 | Initial release — 7 categories covering network, transactions, external dependencies, data consistency, security, business logic, and performance failures for diaspora remittance feature with real USD/ETB amounts |

---

*Version: 1.0 | Last updated: 2026-05-29 | Owner: Remittance Engineering Team*
