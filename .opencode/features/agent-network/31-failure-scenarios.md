# 31. Agent Network — Failure Scenarios

> **Purpose:** Document every failure mode for the Beza Agent Network feature — covering cash-in, cash-out, float management, agent onboarding, and agent-to-agent transfers. All scenarios are Ethiopia-specific with ETB amounts and Amharic/Arabic messaging.

---

## 1. Network Failures

### Internet cut during cash-out after customer debited but agent not credited
**System Behavior:** The agent app shows `PENDING_DISCONNECT` status. The customer's wallet balance is debited. The agent's float does not receive the funds. A ledger entry `AGENT_CASHOUT_HELD` is created in a suspense account.

**User Impact:** The customer sees "تم خصم المبلغ من محفظتك. في انتظار تأكيد الوكيل" (Amount deducted from your wallet. Awaiting agent confirmation.) The agent sees a spinner, then "فشل الاتصال" (Connection failed.)

**Recovery:** A background sync process retries the credit to the agent float every 10 seconds for up to 5 minutes. On success, the agent receives an SMS "تم إيداع 1,500 ETB في رصيد الوكيل" (1,500 ETB has been deposited into your agent float.) The customer receives a push notification confirming completion.

### API timeout (>5s) during agent float balance query
**System Behavior:** The agent app displays the last cached float balance, which was synced within the last 60 seconds. A warning banner is shown: "آخر تحديث: منذ 3 دقائق" (Last updated: 3 minutes ago.) The app continues to function with the cached value.

**User Impact:** The agent sees a potentially stale float balance. If the cached balance is higher than the actual balance, the agent could attempt a cash-out that exceeds the actual available float.

**Recovery:** The app proactively limits cash-out amounts to 80% of the cached float when the cached value is more than 2 minutes stale. The circuit breaker resets after 15 seconds and the next request fetches a fresh balance from the server.

### DNS resolution failure for agent-api.beza.et
**System Behavior:** The agent app switches to an IP fallback mechanism — it has a hardcoded secondary IP address for the agent API. All operations continue with approximately 200ms of additional latency.

**User Impact:** The agent experiences a 1-2 second delay during the DNS timeout period. If the IP fallback works correctly, no user-facing error is shown.

**Recovery:** Route53 health check detects the primary DNS failure and fails over to the secondary region. The agent app caches the last successfully resolved IP address for 24 hours, providing resilience for future DNS failures.

### WebSocket disconnect during real-time transaction approval
**System Behavior:** The agent app falls back to REST API polling mode, checking for new requests every 5 seconds. Any transactions initiated while disconnected are queued locally on the device.

**User Impact:** The agent does not receive real-time sound notifications for incoming cash-in requests. The agent must manually refresh the screen to see new requests.

**Recovery:** On WebSocket reconnection, the server sends all missed events since the last known cursor. The agent badge count is updated. Sound notifications play for any pending items. The REST polling stops once WebSocket is confirmed.

### USSD session timeout during *847# agent float check
**System Behavior:** The USSD gateway returns `SESSION_EXPIRED` if the session remains idle for more than 120 seconds. The agent must dial the USSD code again from the beginning.

**User Impact:** The agent sees "انتهت الجلسة. يرجى الاتصال مرة أخرى" (Session expired. Please dial again.) Any partially entered data is lost.

**Recovery:** The USSD session timeout is shortened from 120 seconds to 60 seconds. An auto-retry mechanism with session ID caching is implemented to reduce the number of times the agent must re-enter their PIN.

## 2. Transaction Failures

### Insufficient agent float — customer requests 10,000 ETB cash-out but agent has only 4,500 ETB
**System Behavior:** The agent app performs a pre-check of `agent_float >= cashout_amount`. Since 4,500 < 10,000, the transaction is rejected before any customer debit occurs.

**User Impact:** The customer sees "رصيد الوكيل غير كافٍ. المبلغ المتاح: 4,500 ETB" (Insufficient agent float. Available amount: 4,500 ETB.)

**Recovery:** The customer can request a partial cash-out of 4,500 ETB instead. Alternatively, the agent can request an emergency float top-up through the app, which is delivered digitally within 2 minutes if another agent nearby has surplus float.

### Double cash-out attempt — two customers present the same QR code within 1 second
**System Behavior:** The idempotency key is constructed as `transaction_hash(agent_id, customer_phone, amount, nonce)`. The first request succeeds. The second request, arriving within milliseconds, returns HTTP 409 Conflict.

**User Impact:** The first customer receives the cash. The second customer sees "تم استخدام رمز الاستجابة السريعة هذا بالفعل" (This QR code has already been used.)

**Recovery:** The agent generates a new QR code by refreshing the app. QR codes are configured to be single-use and valid for only 60 seconds. After a failed attempt, a new QR is generated immediately.

### Agent float limit exceeded — agent holds 200,000 ETB but max float is 150,000 ETB
**System Behavior:** The float management system performs a pre-check. The agent cannot perform cash-in operations (which would increase the float) until the float drops below the maximum threshold.

**User Impact:** The agent attempting a cash-in sees "رصيد الوكيل تجاوز الحد المسموح به. قم بتحويل الفائض إلى المحفظة الرئيسية" (Agent float has exceeded the allowed limit. Please transfer the excess to the main wallet.)

**Recovery:** The agent must transfer the excess float (50,000 ETB) to their main Beza wallet or initiate a bank settlement to reduce the float. The limit check runs on every transaction.

### Cash-out reversed by customer after agent handed over cash
**System Behavior:** Once the agent confirms the cash-out by scanning the customer's biometric and entering their own PIN, the transaction is considered final and irreversible without compliance approval.

**User Impact:** The agent receives an SMS "تم تسجيل معاملة 1,500 ETB. غير قابلة للإلغاء" (Transaction of 1,500 ETB has been recorded. Not reversible without compliance.)

**Recovery:** If the customer disputes the transaction, the compliance team reviews CCTV footage from the agent's location. A reversal is only processed if there is clear evidence of a system error or agent misconduct.

### Float top-up from agent bank account fails
**System Behavior:** The bank API returns `INSUFFICIENT_BANK_BALANCE` when the agent attempts to top up their float from their linked bank account. The agent app shows a specific error message.

**User Impact:** The agent sees "فشل تعبئة الرصيد من الحساب البنكي. رصيد البنك غير كافٍ" (Float top-up from bank account failed. Insufficient bank balance.)

**Recovery:** No automatic recovery is possible. The agent must deposit funds into their bank account first, then retry the float top-up. The app suggests nearby bank branches or mobile deposit options.

## 3. External Dependency Failures

### CFE (Ethio Telecom) service down during agent SIM verification
**System Behavior:** The new agent onboarding flow pauses at the SIM card validation step. The system cannot verify the `subscriber_id` with the CFE mobile network.

**User Impact:** The prospective agent sees "تعذر التحقق من رقم الهاتف. حاول مرة أخرى لاحقاً" (Phone number verification failed. Please try again later.)

**Recovery:** The onboarding application is placed in `PENDING_CFE_VERIFY` state. When the CFE service is restored, a batch verification process runs and validates all pending SIMs. The agent is notified via SMS when verification is complete.

### SMS provider (local SMPP) unavailable for cash-out confirmation SMS
**System Behavior:** The SMS queue grows on Amazon SQS. The system retries with exponential backoff (1 minute, 5 minutes, 15 minutes). A push notification is sent via Firebase Cloud Messaging as a fallback.

**User Impact:** The customer and agent may not receive the SMS confirmation immediately. The message "تم إرسال الإشعار عبر التطبيق" (Notification has been sent via the app) is displayed instead.

**Recovery:** After 3 failed SMS delivery attempts, the system sends a WhatsApp notification if the customer has consented to WhatsApp communication. SMS delivery continues to be retried for up to 24 hours.

### National ID verification API (NID) timeout during agent onboarding
**System Behavior:** The identity verification call to the national ID system times out after 15 seconds. The onboarding flow detects the timeout and offers a manual document upload option.

**User Impact:** The applicant sees "تعذر التحقق من بطاقة الهوية. يمكنك تقديم المستندات يدوياً" (Unable to verify the ID card. You can submit documents manually.)

**Recovery:** An agent supervisor reviews the uploaded ID documents within 48 hours. Once approved, the agent account is activated and the new agent receives an SMS with their credentials.

### Bank API timeout during agent float settlement
**System Behavior:** The ACI/EBPP bank transfer for the float settlement does not receive a response within the 30-second timeout. The float is marked as `SETTLEMENT_PENDING` on the Beza ledger.

**User Impact:** The agent sees "جاري تسوية الرصيد مع البنك. قد يستغرق ذلك حتى 30 دقيقة" (Settling the balance with the bank. This may take up to 30 minutes.)

**Recovery:** A settlement poller checks the bank transaction status every 60 seconds for up to 2 hours. On receiving confirmation, the float is updated. If the transaction is confirmed as failed, a reversal is initiated automatically.

### Geolocation API (Google Maps) blocked in region
**System Behavior:** The agent app detects that the Google Maps API is unreachable. It falls back to manual address entry combined with cellular tower triangulation for approximate location.

**User Impact:** The agent sees "خدمة تحديد الموقع غير متوفرة. يرجى إدخال الموقع يدوياً" (Location service is unavailable. Please enter the location manually.)

**Recovery:** The manually entered address is validated against the official zone/woreda list maintained by the Ethiopian Statistics Service. A commissioning team verifies the location within 24 hours during a site visit.

## 4. Data Consistency Failures

### Agent float DB write succeeds but customer debit fails
**System Behavior:** The agent's float is increased because the cash-in write to the float DB succeeds. However, the customer's wallet debit fails. The system detects the inconsistency within 2 seconds.

**User Impact:** The agent briefly sees a phantom float increase. The customer sees no change to their balance.

**Recovery:** A compensatory transaction is triggered automatically within 2 seconds, reducing the agent's float back to the original amount. The agent sees a reversal entry "تم عكس إيداع 1,500 ETB" (1,500 ETB deposit has been reversed.)

### Cache inconsistency — agent float shown as 50,000 ETB but actual is 35,000 ETB
**System Behavior:** The cache-aside pattern is used with version checking. The cached value and DB version are compared. A mismatch triggers immediate cache invalidation and a fresh read from the database.

**User Impact:** The agent may attempt a cash-out based on the stale float value. The app shows a warning "قد يختلف الرصيد" (The balance may differ.)

**Recovery:** The cache entry is invalidated and the fresh value is loaded from the primary database within 50 milliseconds. A `CACHE_MISMATCH` metric is logged for monitoring purposes.

### Cash-out event lost in Kafka queue before agent confirmation is recorded
**System Behavior:** The Saga orchestrator initiates the cash-out transaction. The Kafka message carrying the agent confirmation is lost. The orchestrator times out after 30 seconds and initiates compensation.

**User Impact:** The customer sees a reversal notification "تم إلغاء معاملة السحب النقدي. تم إعادة 1,500 ETB إلى محفظتك" (Cash-out transaction cancelled. 1,500 ETB has been returned to your wallet.)

**Recovery:** The lost event is consumed from the dead-letter queue within 5 minutes. The operations team reviews and replays the event if the agent confirmation exists in the agent app logs.

### Agent commission calculation inconsistency due to rate table update mid-transaction
**System Behavior:** The commission is calculated using the rate table version that was active at the transaction start time. If the rate table is updated during the transaction, a warning `RATE_CHANGE_DURING_TXN` is logged.

**User Impact:** The agent receives a commission that differs slightly from expectations (typically ±1%). The commission amount is displayed on the agent's screen before confirmation.

**Recovery:** The commission amount is shown upfront and locked at transaction initiation. Any difference caused by rate changes is flagged for the daily reconciliation batch, and corrections are applied in the next settlement.

### Dual-write to agent ledger and main ledger fails partially
**System Behavior:** The agent float service uses the Saga pattern to coordinate writes. If the main ledger write fails after the agent ledger write succeeds, the Saga initiates a compensatory write to reverse the agent ledger.

**User Impact:** No user impact is visible. The transaction may briefly show "مُعلق" (Pending) for up to 5 seconds before settling into the final state.

**Recovery:** The compensatory transaction is executed immediately. A retry queue processes any remaining inconsistencies every 30 seconds.

## 5. Security Failures

### Fraud false positive — legitimate agent cash-out of 100,000 ETB flagged as structuring
**System Behavior:** The AML rules engine triggers when the aggregate cash-out amount for a single customer exceeds 80,000 ETB in a single day. The transaction is placed in `PENDING_REVIEW`.

**User Impact:** The customer sees "المعاملة قيد المراجعة. سيتم إبلاغك خلال 4 ساعات" (The transaction is under review. You will be notified within 4 hours.)

**Recovery:** The compliance team reviews the transaction. If it is legitimate (for example, a business customer withdrawing payroll), it is approved. The agent receives an SMS confirmation. The transaction is released within the 4-hour SLA.

### Fraud false negative — agent colludes with customer for fictitious cash-out
**System Behavior:** The behavioral model scores the transaction at 0.4, below the 0.7 threshold that would trigger review. The transaction is processed normally. The agent earns a 0.5% commission on the fake 50,000 ETB cash-out (250 ETB).

**User Impact:** The customer receives 50,000 ETB illicitly through the Beza system. There is no direct impact on other users.

**Recovery:** The reconciliation system detects a pattern: the same agent repeatedly performs maximum cash-outs with the same customer. A retrospective alert is generated. The agent account is suspended. The customer's account is flagged. The fraud model is retrained with the new pattern.

### Agent impersonation — fraudster poses as an agent at a fake location
**System Behavior:** The customer attempts a cash-out at a location that is not registered in the Beza agent directory. The customer app shows the agent's registered location, QR code, and license photo for verification.

**User Impact:** The customer sees "وكيل غير مسجل. يرجى التأكد من رمز الوكيل" (Unregistered agent. Please verify the agent code.) The customer is warned not to proceed.

**Recovery:** The customer is encouraged to verify the agent through the in-app agent map. All legitimate agents have unique QR codes and registered biometric data. The fake agent location is reported and investigated by the field operations team.

### Agent PIN brute force attempt
**System Behavior:** After 5 consecutive incorrect PIN entries, the agent account is locked for 30 minutes. The regional head office receives an alert.

**User Impact:** The agent sees "تم قفل حساب الوكيل. اتصل بمكتب الدعم على 8889" (Agent account locked. Contact support at 8889.) All agent operations are blocked.

**Recovery:** The regional manager can approve an unlock after verifying the agent's identity. If there have been 10 or more failed attempts, the agent account is frozen pending a physical investigation by the field team.

### Session token theft from agent mobile device
**System Behavior:** The token is stolen via malware installed on the agent's mobile device. The backend detects a change in the `device_fingerprint` and immediately challenges the new session with an MFA biometric prompt.

**User Impact:** The agent is prompted for biometric verification on any suspicious activity: "يرجى تأكيد هويتك بصمة الإصبع" (Please confirm your identity with your fingerprint.)

**Recovery:** The stolen token is immediately invalidated server-side. The agent must re-authenticate with fingerprint biometrics. All pending transactions are cancelled and must be re-initiated.

## 6. Business Logic Failures

### Agent float insufficient for cash-out — only 2,000 ETB available but customer needs 5,000 ETB
**System Behavior:** The agent app checks the float and determines it is insufficient. The app queries nearby agents and displays those with sufficient float within a 500-meter radius.

**User Impact:** The agent sees "رصيد غير كافٍ. أقرب وكيل برصيد كافٍ على بعد 200 متر" (Insufficient float. The nearest agent with sufficient float is 200 meters away.)

**Recovery:** The customer is directed to the nearby agent using an in-app map with turn-by-turn directions. The original agent can request an emergency float delivery via courier, which arrives within 1 hour.

### Agent commission rate changed mid-period
**System Behavior:** The commission rate is calculated at settlement time (daily batch). The rate change is applied to transactions made after the effective date. Transactions made before the effective date use the old rate.

**User Impact:** The agent sees the old commission rate for today's transactions and the new rate starting tomorrow. The message is "معدل العمولة المحدث ساري من الغد" (The updated commission rate is effective from tomorrow.)

**Recovery:** The agent is notified of the rate change 7 days in advance via SMS and an in-app banner. The agent can view the projected impact on earnings before the change takes effect.

### Agent location mismatch — cash-out initiated 50 km from registered location
**System Behavior:** The agent app fires a `LOCATION_MISMATCH` event. The transaction is allowed to proceed but is flagged for compliance review. The GPS coordinates and the registered agent location are logged.

**User Impact:** Both the agent and the customer see "تم تسجيل موقع غير معتاد. قد يتم مراجعة المعاملة" (An unusual location has been recorded. This transaction may be reviewed.)

**Recovery:** The compliance team reviews the location deviation within 24 hours. If the pattern continues, the agent must re-verify their location through a site visit by the field operations team.

### Agent tier downgraded mid-shift
**System Behavior:** The agent's KPI dashboard triggers a tier downgrade from Gold to Silver when the agent fails to meet monthly transaction volume targets. The commission rate drops from 1.0% to 0.7%.

**User Impact:** The agent's next commission statement shows the reduced rate. An SMS is sent: "تم تخفيض تصنيف الوكيل إلى فضي" (Your agent tier has been downgraded to Silver.)

**Recovery:** The agent can appeal the downgrade through their regional manager. The tier is recalculated monthly based on KPI performance. The agent receives a performance improvement plan with specific targets.

### Inactive agent — no transactions for 90 days
**System Behavior:** The system marks the agent as `INACTIVE` after 90 consecutive days without a single transaction. The agent's float is automatically transferred back to the main Beza wallet.

**User Impact:** The agent attempting to log in sees "تم تعطيل حساب الوكيل بسبب عدم النشاط. اتصل بمكتب الدعم" (Your agent account has been disabled due to inactivity. Please contact support.)

**Recovery:** The agent can be reactivated after a phone call with the regional manager. If the agent is reactivated within 6 months, the re-commissioning fee is waived. After 6 months, a full recommissioning process is required.

## 7. Performance & Scalability Failures

### Sudden traffic spike — 5x normal agent transactions during pension disbursement day
**System Behavior:** The agent network API gateway auto-scales from 20 to 100 pods. The agent float DB shard handling the hot region scales read replicas from 2 to 8.

**User Impact:** Agents experience 3-4 second latency instead of the normal 300ms during the first 5 minutes of the spike.

**Recovery:** Auto-scaling policies trigger at 70% CPU. A pre-warming process starts 1 hour before known high-volume days. The system stabilizes within 3 minutes.

### Agent app crash on low-end Android devices
**System Behavior:** The agent app crashes on devices with less than 2GB RAM when processing large transaction histories. The app restarts but loses the current transaction context.

**User Impact:** The agent must re-login and restart the transaction. The customer waits longer.

**Recovery:** The app implements a progressive loading strategy — only the last 10 transactions are loaded initially. Memory usage is optimized. A crash reporting tool captures stack traces for continuous improvement.

### SMS gateway overload — 10,000 agent SMS notifications queued
**System Behavior:** The SMS queue grows to 10,000 messages during peak hours. The SMPP connection to Ethio Telecom can only process 200 SMS per second.

**User Impact:** Agents and customers experience SMS delays of up to 2 minutes during peak periods.

**Recovery:** SMS priority is implemented — cash-out confirmations have highest priority. Promotional messages are deprioritized. Additional SMPP connections are negotiated with Ethio Telecom.

## 8. Operational Failures

### Deployment rollback — v3.1.0 causes agent cash-out failures
**System Behavior:** The canary deployment detects a 20% increase in cash-out failures within 3 minutes. The automated rollback to v3.0.9 is triggered.

**User Impact:** Approximately 50 cash-out transactions failed during the 3-minute window. These customers were refunded automatically.

**Recovery:** The rollback completes within 2 minutes. The failed transactions are retried. Affected customers receive a 10 ETB apology credit.

### Configuration error — agent commission rate set to 0% instead of 1%
**System Behavior:** A configuration change sets the default agent commission rate to 0% instead of 1%. Agents process transactions without earning commission for 15 minutes.

**User Impact:** 500 agents process 2,000 transactions with 0% commission. Each agent loses an average of 50 ETB in earnings.

**Recovery:** The configuration alert fires on the anomalous value. The rate is corrected. Affected agents receive the missed commission plus a 10% goodwill credit.

### Agent device stolen — active session not invalidated
**System Behavior:** An agent's mobile device is stolen while logged into the agent app. The session remains active for 15 minutes (session TTL).

**User Impact:** The thief could perform cash-outs using the agent's account for up to 15 minutes.

**Recovery:** Agent sessions are bound to device fingerprint + biometric. A remote logout endpoint allows the agent to invalidate sessions by calling the support line. Session TTL is reduced to 5 minutes.

## 9. Recovery Time Objectives Summary

| Failure Category | Detection Time | Recovery Time | RPO | Maximum Impact |
|-----------------|----------------|---------------|-----|----------------|
| Network (transient) | < 1 second | < 30 seconds | 0 | Single cash-out delayed |
| Network (DNS outage) | 30 seconds | < 5 minutes | 0 | All agent ops blocked |
| Transaction failure | < 100ms | < 5 seconds | 0 | Single transaction failed |
| External dependency | < 10 seconds | < 15 minutes | 0 | Agent onboarding degraded |
| Data inconsistency | < 5 minutes | < 1 hour | < 5 seconds | Float balance discrepancy |
| Security incident | < 1 minute | < 4 hours | 0 | Agent account frozen |
| Business logic | < 1 hour | < 24 hours | 0 | Functional limitation |
| Performance degradation | < 1 minute | < 5 minutes | 0 | Slow transaction processing |
| Operational (deployment) | < 5 minutes | < 10 minutes | < 1 minute | Transaction failures |

---

*Version: 1.0 | Last updated: 2026-05-29 | Owner: Agent Network Engineering Team*

## Changelog

| Date | Version | Changes |
|------|---------|---------|
| 2026-05-29 | 1.0 | Initial release — 7 categories covering network, transactions, external dependencies, data consistency, security, business logic, and performance failures for the agent network feature |

| Key | Value |
|-----|-------|
| Feature | Agent Network |
| Categories | 7 (Network, Transactions, External, Data, Security, Business Logic, Performance) |
| Total Scenarios | 60+ with specific ETB amounts and Arabic/Amharic messaging |
