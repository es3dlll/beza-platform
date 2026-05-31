# 31. Agent Network — Failure Scenarios

> **Purpose:** Document every failure mode for the Beza Agent Network feature in Syria — covering cash-in, cash-out, float management, agent onboarding, and agent-to-agent transfers. All scenarios use Syrian Pound (SYP) amounts, Syrian cities (Damascus, Aleppo, Homs, Latakia), Syrian mobile networks (Syriatel, MTN), and Arabic messaging. CBS (Central Bank of Syria) regulatory references are included.

---

## 1. Network Failures

### Internet cut during cash-out after customer debited but agent not credited
**System Behavior:** The agent app shows `PENDING_DISCONNECT` status. The customer's wallet balance is debited. The agent's float does not receive the funds. A ledger entry `AGENT_CASHOUT_HELD` is created in a suspense account.

**User Impact:** The customer sees "تم خصم المبلغ من محفظتك. في انتظار تأكيد الوكيل" (Amount deducted from your wallet. Awaiting agent confirmation.) The agent sees a spinner, then "فشل الاتصال" (Connection failed.)

**Recovery:** A background sync process retries the credit to the agent float every 10 seconds for up to 5 minutes. On success, the agent receives an SMS via Syriatel or MTN "تم إيداع 150,000 ل.س في رصيد الوكيل" (150,000 SYP has been deposited into your agent float.) The customer receives a push notification confirming completion.

### API timeout (>5s) during agent float balance query in Damascus
**System Behavior:** The agent app displays the last cached float balance, which was synced within the last 60 seconds. A warning banner is shown: "آخر تحديث: منذ 3 دقائق" (Last updated: 3 minutes ago.) The app continues to function with the cached value.

**User Impact:** The agent sees a potentially stale float balance. If the cached balance is higher than the actual balance, the agent could attempt a cash-out that exceeds the actual available float.

**Recovery:** The app proactively limits cash-out amounts to 80% of the cached float when the cached value is more than 2 minutes stale. The circuit breaker resets after 15 seconds and the next request fetches a fresh balance from the server.

### DNS resolution failure for agent-api.beza.sy
**System Behavior:** The agent app switches to an IP fallback mechanism — it has a hardcoded secondary IP address for the agent API. All operations continue with approximately 200ms of additional latency.

**User Impact:** The agent experiences a 1-2 second delay during the DNS timeout period. If the IP fallback works correctly, no user-facing error is shown.

**Recovery:** Route53 health check detects the primary DNS failure and fails over to the secondary region. The agent app caches the last successfully resolved IP address for 24 hours, providing resilience for future DNS failures.

### WebSocket disconnect during real-time cash-in approval in Aleppo
**System Behavior:** The agent app falls back to REST API polling mode, checking for new requests every 5 seconds. Any cash-in requests initiated while disconnected are queued locally on the device.

**User Impact:** The agent does not receive real-time sound notifications for incoming cash-in requests from customers in Aleppo. The agent must manually refresh the screen to see new requests.

**Recovery:** On WebSocket reconnection, the server sends all missed events since the last known cursor. The agent badge count is updated. Sound notifications play for any pending items. The REST polling stops once WebSocket is confirmed.

### Syriatel network congestion during peak hours — agent in Damascus Old City
**System Behavior:** Syriatel 4G network in the Damascus Old City area experiences severe congestion during business hours (10 AM - 2 PM). Mobile data throughput drops to 0.5 Mbps. The agent app detects degraded connectivity and activates offline transaction mode.

**User Impact:** The agent sees a yellow banner "شبكة سيريتيل مزدحمة. وضع العمل دون اتصال نشط" (Syriatel network congested. Offline mode active.) Customer verification is delayed as biometric data cannot be uploaded immediately.

**Recovery:** The agent app queues up to 10 transactions locally with encrypted payloads. When network conditions improve, the queue is synced automatically. Each queued transaction includes a timestamp and GPS location for audit purposes. The sync completes within 2 seconds of network restoration.

### MTN mobile data outage affecting agent in Homs
**System Behavior:** MTN Syria experiences a regional mobile data outage in Homs governorate. The agent app cannot reach the server. The app enters full offline mode with cached float display only.

**User Impact:** The agent cannot process any cash-in or cash-out transactions. Customers in Homs are turned away. The agent sees "انقطاع في شبكة إم تي إن. الخدمة ستعود تلقائياً" (MTN network outage. Service will resume automatically.)

**Recovery:** The app attempts reconnection every 30 seconds. If after 5 minutes the MTN network is still down, the agent is prompted to switch to a Wi-Fi network or Syriatel mobile data if available. A notification is sent to the regional operations team in Homs.

## 2. Transaction Failures

### Insufficient agent float — customer requests 100,000 SYP cash-out but agent has only 45,000 SYP
**System Behavior:** The agent app performs a pre-check of `agent_float >= cashout_amount`. Since 45,000 < 100,000, the transaction is rejected before any customer debit occurs.

**User Impact:** The customer sees "رصيد الوكيل غير كافٍ. المبلغ المتاح: 45,000 ل.س" (Insufficient agent float. Available amount: 45,000 SYP.)

**Recovery:** The customer can request a partial cash-out of 45,000 SYP instead. Alternatively, the agent can request an emergency float top-up from another agent in the same district via the Beza agent-to-agent float transfer feature, which is processed within 2 minutes.

### Double cash-out attempt — two customers present the same QR code within 1 second
**System Behavior:** The idempotency key is constructed as `transaction_hash(agent_id, customer_phone, amount, nonce)`. The first request succeeds. The second request, arriving within milliseconds, returns HTTP 409 Conflict.

**User Impact:** The first customer receives the cash. The second customer sees "تم استخدام رمز الاستجابة السريعة هذا بالفعل" (This QR code has already been used.)

**Recovery:** The agent generates a new QR code by refreshing the app. QR codes are configured to be single-use and valid for only 60 seconds. After a failed attempt, a new QR is generated immediately.

### Agent float limit exceeded — agent holds 2,000,000 SYP but max float is 1,500,000 SYP per CBS regulation
**System Behavior:** The float management system performs a pre-check. The agent cannot perform cash-in operations (which would increase the float) until the float drops below the maximum threshold of 1,500,000 SYP as per CBS agent network regulations.

**User Impact:** The agent attempting a cash-in sees "رصيد الوكيل تجاوز الحد المسموح به وفقاً لتعليمات مصرف سورية المركزي. قم بتحويل الفائض إلى المحفظة الرئيسية" (Agent float has exceeded the limit per Central Bank of Syria regulations. Please transfer the excess to the main wallet.)

**Recovery:** The agent must transfer the excess float (500,000 SYP) to their main Beza wallet or initiate a bank settlement to BBS or Syria International Islamic Bank. The limit check runs on every transaction and is enforced by CBS regulatory requirements.

### Cash-out reversed by customer after agent handed over cash in Latakia
**System Behavior:** Once the agent confirms the cash-out by scanning the customer's biometric and entering their own PIN, the transaction is considered final and irreversible without compliance approval.

**User Impact:** The agent receives an SMS via Syriatel "تم تسجيل معاملة 75,000 ل.س. غير قابلة للإلغاء دون موافقة الامتثال" (Transaction of 75,000 SYP recorded. Not reversible without compliance approval.)

**Recovery:** If the customer disputes the transaction, the compliance team reviews CCTV footage from the agent's location. A reversal is only processed if there is clear evidence of a system error or agent misconduct. Disputes must be filed within 48 hours per CBS consumer protection rules.

### Float top-up from agent bank account (BBS) fails
**System Behavior:** The BBS (Banque Bemo Saudi Fransi) API returns `INSUFFICIENT_BANK_BALANCE` when the agent attempts to top up their float from their linked bank account. The agent app shows a specific error message.

**User Impact:** The agent sees "فشل تعبئة الرصيد من حساب بنك بيمو السعودي الفرنسي. رصيد البنك غير كافٍ" (Float top-up from BBS bank account failed. Insufficient bank balance.)

**Recovery:** No automatic recovery is possible. The agent must deposit funds into their BBS bank account first, then retry the float top-up. The app suggests nearby bank branches in Damascus or Aleppo. An alternative top-up from Syria International Islamic Bank is offered.

### Cash-in with invalid denomination — customer tries to deposit 500 SYP notes but agent only accepts 1,000 SYP notes
**System Behavior:** The agent app prompts the agent to enter the note denomination before confirming receipt. If the denomination entered is not in the agent's accepted list, the app rejects the cash-in at the confirmation stage.

**User Impact:** The agent sees "فئة النقد غير مقبولة. الفئات المقبولة: 1,000 - 2,000 - 5,000 ل.س" (Denomination not accepted. Accepted denominations: 1,000 - 2,000 - 5,000 SYP.)

**Recovery:** The customer can provide notes in an accepted denomination. The agent can update their accepted denomination list through the app settings. The change is validated by the regional operations team.

## 3. External Dependency Failures

### Syriatel Cash API failure during agent float top-up from mobile wallet
**System Behavior:** The Syriatel Cash API for transferring funds from an agent's Syriatel Cash wallet to their Beza agent float is unavailable. The transaction is placed in `PENDING_SETTLEMENT`.

**User Impact:** The agent sees "خدمة سيريتيل كاش غير متوفرة حالياً. استخدم الحساب البنكي بدلاً من ذلك" (Syriatel Cash service is currently unavailable. Use the bank account instead.)

**Recovery:** An automatic failover routes the top-up through MTN Mobile Money if available. If both mobile money operators are down, the agent must use their linked bank account (BBS or Syria International Islamic Bank). The circuit breaker resets after 60 seconds.

### MTN Mobile Money timeout during cash-out transfer to customer
**System Behavior:** The customer chooses to receive cash-out via MTN Mobile Money instead of physical cash. The MTN API does not respond within the 30-second timeout window. The cash-out is placed in `PENDING_EXTERNAL` status.

**User Impact:** The customer sees "جاري تأكيد التحويل عبر إم تي إن. قد يستغرق ذلك بضع دقائق" (Confirming the transfer via MTN. This may take a few minutes.) The agent confirms the transaction locally.

**Recovery:** An internal poller checks the MTN transaction status via the inquiry API every 30 seconds for up to 15 minutes. On receiving confirmation, the customer's MTN Mobile Money is credited. On failure, the agent's float is reversed and the customer is notified to try again.

### CBS reporting API unavailable for daily agent transaction report
**System Behavior:** The agent network service fails to submit the daily aggregated transaction report to the Central Bank of Syria. The report is queued with a timestamp. A `CBS_REPORT_PENDING` alert is generated.

**User Impact:** No direct user impact. Agents continue to operate normally. The internal ops team sees a warning "تقرير مصرف سورية المركزي معلق" (CBS report pending).

**Recovery:** The report is retried every 30 minutes for up to 6 hours. If the CBS API remains unavailable, the report is encrypted and queued for manual submission via the CBS secure file transfer portal. CBS regulatory SLA requires submission within 24 hours.

### National ID verification API (Syrian Civil Registry) timeout during agent onboarding
**System Behavior:** The identity verification call to the Syrian Civil Registry (السجل المدني السوري) times out after 15 seconds. The onboarding flow detects the timeout and offers a manual document upload option.

**User Impact:** The applicant agent in Damascus sees "تعذر التحقق من بطاقة الهوية من السجل المدني. يمكنك تقديم المستندات يدوياً" (Unable to verify the ID card from the Civil Registry. You can submit documents manually.)

**Recovery:** An agent supervisor reviews the uploaded ID documents within 48 hours. Once approved, the agent account is activated and the new agent receives an SMS through Syriatel or MTN with their credentials and agent code.

### Bank API timeout during agent float settlement to BBS
**System Behavior:** The bank transfer for the float settlement to Banque Bemo Saudi Fransi does not receive a response within the 30-second timeout. The float is marked as `SETTLEMENT_PENDING` on the Beza ledger.

**User Impact:** The agent sees "جاري تسوية الرصيد مع بنك بيمو السعودي الفرنسي. قد يستغرق ذلك حتى 30 دقيقة" (Settling the balance with BBS bank. This may take up to 30 minutes.)

**Recovery:** A settlement poller checks the BBS bank transaction status every 60 seconds for up to 2 hours. On receiving confirmation, the float is updated. If the transaction is confirmed as failed, a reversal is initiated automatically.

### Geolocation service (Google Maps) blocked in Syria
**System Behavior:** The agent app detects that the Google Maps API is unreachable (blocked by internet restrictions in Syria). It falls back to HERE Maps or a manual address entry system combined with cellular tower triangulation via Syriatel.

**User Impact:** The agent sees "خدمة تحديد الموقع غير متوفرة. يرجى إدخال الموقع يدوياً" (Location service is unavailable. Please enter the location manually.)

**Recovery:** The manually entered address is validated against the official Syrian governorate/district list (دمشق, حلب, حمص, اللاذقية, حماة, طرطوس, etc.). A commissioning team verifies the location within 48 hours during a site visit. GPS coordinates from cellular triangulation are stored as approximate location.

## 4. Data Consistency Failures

### Agent float DB write succeeds but customer debit fails
**System Behavior:** The agent's float is increased because the cash-in write to the float DB succeeds. However, the customer's wallet debit fails. The system detects the inconsistency within 2 seconds.

**User Impact:** The agent briefly sees a phantom float increase of 50,000 SYP. The customer sees no change to their balance.

**Recovery:** A compensatory transaction is triggered automatically within 2 seconds, reducing the agent's float back to the original amount. The agent sees a reversal entry "تم عكس إيداع 50,000 ل.س" (50,000 SYP deposit has been reversed.)

### Cache inconsistency — agent float shown as 500,000 SYP but actual is 350,000 SYP
**System Behavior:** The cache-aside pattern is used with version checking. The cached value and DB version are compared. A mismatch triggers immediate cache invalidation and a fresh read from the database.

**User Impact:** The agent may attempt a cash-out based on the stale float value. The app shows a warning "قد يختلف الرصيد" (The balance may differ.)

**Recovery:** The cache entry is invalidated and the fresh value is loaded from the primary database within 50 milliseconds. A `CACHE_MISMATCH` metric is logged for monitoring purposes.

### Cash-out event lost in Kafka queue before agent confirmation is recorded in Homs
**System Behavior:** The Saga orchestrator initiates a 200,000 SYP cash-out transaction for a customer in Homs. The Kafka message carrying the agent confirmation is lost. The orchestrator times out after 30 seconds and initiates compensation.

**User Impact:** The customer sees a reversal notification "تم إلغاء معاملة السحب النقدي في حمص. تم إعادة 200,000 ل.س إلى محفظتك" (Cash-out transaction in Homs cancelled. 200,000 SYP has been returned to your wallet.)

**Recovery:** The lost event is consumed from the dead-letter queue within 5 minutes. The operations team reviews and replays the event if the agent confirmation exists in the agent app logs. The agent's float is reconciled.

### Agent commission calculation inconsistency due to CBS rate update mid-transaction
**System Behavior:** The commission is calculated using the rate table version that was active at the transaction start time. If the CBS-regulated commission rate table is updated during the transaction, a warning `RATE_CHANGE_DURING_TXN` is logged.

**User Impact:** The agent receives a commission that differs slightly from expectations (typically ±25 SYP). The commission amount is displayed on the agent's screen before confirmation.

**Recovery:** The commission amount is shown upfront and locked at transaction initiation. Any difference caused by CBS rate changes is flagged for the daily reconciliation batch, and corrections are applied in the next settlement.

### Dual-write to agent ledger and main ledger fails partially
**System Behavior:** The agent float service uses the Saga pattern to coordinate writes. If the main ledger write fails after the agent ledger write succeeds, the Saga initiates a compensatory write to reverse the agent ledger.

**User Impact:** No user impact is visible. The transaction may briefly show "مُعلق" (Pending) for up to 5 seconds before settling into the final state.

**Recovery:** The compensatory transaction is executed immediately. A retry queue processes any remaining inconsistencies every 30 seconds.

### Transaction log split-brain during agent float reconciliation — agent shows 750,000 SYP, server shows 650,000 SYP
**System Behavior:** A network partition causes the agent app and server to diverge on the correct float balance. The agent has processed 10,000 SYP in cash-outs that the server has not recorded. The next sync detects the 100,000 SYP discrepancy.

**User Impact:** The agent sees their float as 750,000 SYP while the server-side reports 650,000 SYP. The agent cannot process transactions until reconciliation completes.

**Recovery:** The server-side reconciliation engine compares the agent's local transaction log (signed and timestamped) against the server ledger. The agent's local log is accepted as authoritative if the digital signatures are valid. The discrepancy is resolved within 30 seconds. Disputed entries are flagged for manual review by the regional operations manager in Damascus.

## 5. Security Failures

### Fraud false positive — legitimate agent cash-out of 1,000,000 SYP flagged as structuring by CBS AML rules
**System Behavior:** The AML rules engine triggers when the aggregate cash-out amount for a single customer exceeds 800,000 SYP in a single day per CBS anti-money laundering thresholds. The transaction is placed in `PENDING_REVIEW`.

**User Impact:** The customer sees "المعاملة قيد المراجعة وفقاً لتعليمات مكافحة غسل الأموال في مصرف سورية المركزي. سيتم إبلاغك خلال 4 ساعات" (The transaction is under review per CBS AML regulations. You will be notified within 4 hours.)

**Recovery:** The compliance team reviews the transaction. If it is legitimate (for example, a business customer withdrawing payroll for employees), it is approved. The agent receives an SMS confirmation from Syriatel. The transaction is released within the 4-hour SLA.

### Fraud false negative — agent colludes with customer for fictitious cash-out in Aleppo
**System Behavior:** The behavioral model scores the transaction at 0.4, below the 0.7 threshold that would trigger review. The transaction is processed normally. The agent earns a 0.5% commission on the fake 500,000 SYP cash-out (2,500 SYP).

**User Impact:** The customer receives 500,000 SYP illicitly through the Beza system in Aleppo. There is no direct impact on other users.

**Recovery:** The reconciliation system detects a pattern: the same agent repeatedly performs maximum cash-outs with the same customer. A retrospective alert is generated. The agent account is suspended. The customer's account is flagged. The fraud model is retrained with the new pattern. CBS is notified per regulatory requirements.

### Agent impersonation — fraudster poses as an agent at a fake location in Damascus
**System Behavior:** The customer attempts a cash-out at a location that is not registered in the Beza agent directory near the Damascus Souq. The customer app shows the agent's registered location, QR code, and license photo for verification.

**User Impact:** The customer sees "وكيل غير مسجل. يرجى التأكد من رمز الوكيل من خلال التطبيق" (Unregistered agent. Please verify the agent code through the app.) The customer is warned not to proceed.

**Recovery:** The customer is encouraged to verify the agent through the in-app agent map. All legitimate agents have unique QR codes and registered biometric data. The fake agent location is reported and investigated by the field operations team. A security alert is sent to nearby registered agents.

### Agent PIN brute force attempt — 5 failed PIN entries
**System Behavior:** After 5 consecutive incorrect PIN entries, the agent account is locked for 30 minutes. The regional head office in Damascus receives an alert through the SIEM system.

**User Impact:** The agent sees "تم قفل حساب الوكيل. اتصل بمكتب الدعم على 1230" (Agent account locked. Contact support at 1230.) All agent operations are blocked.

**Recovery:** The regional manager can approve an unlock after verifying the agent's identity via a video call. If there have been 10 or more failed attempts, the agent account is frozen pending a physical investigation by the field team. CBS is notified of the security incident.

### Session token theft from agent mobile device in Homs
**System Behavior:** The token is stolen via malware installed on the agent's mobile device. The backend detects a change in the `device_fingerprint` and immediately challenges the new session with an MFA biometric prompt.

**User Impact:** The agent is prompted for biometric verification on any suspicious activity: "يرجى تأكيد هويتك بصمة الإصبع" (Please confirm your identity with your fingerprint.)

**Recovery:** The stolen token is immediately invalidated server-side. The agent must re-authenticate with fingerprint biometrics. All pending transactions are cancelled and must be re-initiated. The agent's device is flagged for security review.

### Customer identity spoofing using forged Syrian national ID
**System Behavior:** A customer attempts to perform a cash-out using a forged Syrian national ID (هوية شخصية). The agent app's OCR check passes, but the system detects that the NID number does not match any record in the Syrian Civil Registry database.

**User Impact:** The customer sees "تعذر التحقق من هوية المستلم. يرجى مراجعة أقرب فرع لخدمة العملاء" (Unable to verify recipient identity. Please visit the nearest customer service branch.)

**Recovery:** The forged ID is reported to the Syrian authorities. The customer's phone number and biometric data are flagged. The agent is commended for following verification procedures. A security bulletin is sent to all agents in the Damascus and Aleppo regions.

## 6. Business Logic Failures

### Agent float insufficient for cash-out — only 20,000 SYP available but customer needs 50,000 SYP
**System Behavior:** The agent app checks the float and determines it is insufficient. The app queries nearby agents and displays those with sufficient float within a 500-meter radius in the same district.

**User Impact:** The agent sees "رصيد غير كافٍ. أقرب وكيل برصيد كافٍ على بعد 200 متر" (Insufficient float. The nearest agent with sufficient float is 200 meters away.)

**Recovery:** The customer is directed to the nearby agent using an in-app map. The original agent can request an emergency float delivery via courier from the nearest Beza distribution hub in Damascus, which arrives within 1 hour.

### Agent commission rate changed mid-period per CBS regulatory update
**System Behavior:** The commission rate is calculated at settlement time (daily batch). The CBS-regulated rate change is applied to transactions made after the effective date. Transactions made before the effective date use the old rate.

**User Impact:** The agent sees the old commission rate for today's transactions and the new rate starting tomorrow. The message is "معدل العمولة المحدث حسب تعليمات مصرف سورية المركزي ساري من الغد" (The updated commission rate per CBS regulations is effective from tomorrow.)

**Recovery:** The agent is notified of the rate change 7 days in advance via SMS from Syriatel/MTN and an in-app banner. The agent can view the projected impact on earnings before the change takes effect.

### Agent location mismatch — cash-out initiated 50 km from registered location in Rural Damascus
**System Behavior:** The agent app fires a `LOCATION_MISMATCH` event. An agent registered in Damascus city is initiating a cash-out in Darayya, 15 km away. The transaction is allowed to proceed but is flagged for compliance review.

**User Impact:** Both the agent and the customer see "تم تسجيل موقع غير معتاد. قد يتم مراجعة المعاملة من قبل فريق الامتثال" (An unusual location has been recorded. This transaction may be reviewed by the compliance team.)

**Recovery:** The compliance team reviews the location deviation within 24 hours. If the pattern continues, the agent must re-verify their location through a site visit by the field operations team. The agent is asked to update their registered location if they have moved.

### Agent tier downgraded mid-shift — Gold to Silver due to low transaction volume
**System Behavior:** The agent's KPI dashboard triggers a tier downgrade from Gold to Silver when the agent fails to meet monthly transaction volume targets. The commission rate drops from 1.0% to 0.7%.

**User Impact:** The agent's next commission statement shows the reduced rate. An SMS is sent via MTN: "تم تخفيض تصنيف الوكيل إلى فضي" (Your agent tier has been downgraded to Silver.)

**Recovery:** The agent can appeal the downgrade through their regional manager in Damascus. The tier is recalculated monthly based on KPI performance. The agent receives a performance improvement plan with specific targets.

### Inactive agent — no transactions for 90 days in Latakia
**System Behavior:** The system marks the agent as `INACTIVE` after 90 consecutive days without a single transaction. The agent's float is automatically transferred back to the main Beza wallet and settled to their linked BBS bank account.

**User Impact:** The agent attempting to log in in Latakia sees "تم تعطيل حساب الوكيل بسبب عدم النشاط. اتصل بمكتب الدعم في دمشق" (Your agent account has been disabled due to inactivity. Please contact support in Damascus.)

**Recovery:** The agent can be reactivated after a phone call with the regional manager. If the agent is reactivated within 6 months, the re-commissioning fee is waived. After 6 months, a full recommissioning process with CBS registration is required.

### Daily agent cash-out limit exceeded — CBS regulation caps agent daily cash-out at 2,000,000 SYP
**System Behavior:** The agent's daily cash-out counter shows 1,950,000 SYP. A customer attempts a 100,000 SYP cash-out. The system rejects the transaction because 2,050,000 would exceed the CBS daily agent cash-out limit.

**User Impact:** The agent sees "تم تجاوز الحد اليومي للسحب النقدي وفقاً لتعليمات مصرف سورية المركزي. الحد المتبقي: 50,000 ل.س" (Daily cash-out limit per CBS regulations exceeded. Remaining limit: 50,000 SYP.)

**Recovery:** The limit resets at midnight Syria time. The agent can process cash-out requests up to the remaining 50,000 SYP. The agent can request a temporary CBS limit waiver through the regional manager for high-volume days.

## 7. Performance & Scalability Failures

### Sudden traffic spike — 5x normal agent transactions during Eid al-Fitr holiday in Damascus
**System Behavior:** The agent network API gateway auto-scales from 20 to 120 pods. The agent float DB shard handling the hot Damascus region scales read replicas from 2 to 10. Syriatel network in the Old City reaches capacity.

**User Impact:** Agents in Damascus experience 3-4 second latency instead of the normal 300ms during the first 5 minutes of the spike. Some Syriatel users experience temporary SMS delivery delays for transaction confirmations.

**Recovery:** Auto-scaling policies trigger at 70% CPU. A pre-warming process starts 2 hours before known holiday periods based on historical transaction data from previous Eid celebrations. The system stabilizes within 3 minutes. Additional SMS capacity is negotiated with Syriatel and MTN in advance.

### Agent app crash on low-end Android devices common in Syrian market
**System Behavior:** The agent app crashes on devices with less than 2GB RAM when processing large transaction histories (500+ transactions). Many Syrian agent devices are budget Android models (2GB RAM, MediaTek processors). The app restarts but loses the current transaction context.

**User Impact:** The agent in Aleppo or Homs must re-login and restart the transaction. The customer waits longer. If the cash was already handed over, the agent must manually verify the previous transaction.

**Recovery:** The app implements a progressive loading strategy — only the last 10 transactions are loaded initially. Memory usage is optimized with lazy loading of images and transaction records. A crash reporting tool captures stack traces for continuous improvement. The minimum RAM requirement is documented as 3GB for optimal performance.

### SMS gateway overload — 50,000 agent SMS notifications queued during Revolution Day holiday
**System Behavior:** The SMS queue grows to 50,000 messages during the March 8 Revolution Day peak. The SMPP connections to Syriatel and MTN can each process only 100 SMS per second. The queue backlog reaches 4 minutes.

**User Impact:** Agents and customers experience SMS delays of up to 5 minutes during peak periods on Revolution Day. Transaction confirmations sent via Syriatel arrive faster than MTN due to higher throughput capacity.

**Recovery:** SMS priority is implemented — cash-out confirmations have highest priority. Promotional messages are deprioritized. Additional SMPP connections are negotiated with both Syriatel and MTN ahead of known holiday periods. Firebase Cloud Messaging push notifications serve as the primary delivery channel during peak SMS load.

## 8. Operational Failures

### Deployment rollback — v3.1.0 causes agent cash-out failures across Syria
**System Behavior:** The canary deployment detects a 20% increase in cash-out failures within 3 minutes. The automated rollback to v3.0.9 is triggered.

**User Impact:** Approximately 50 cash-out transactions failed during the 3-minute window in Damascus, Aleppo, and Homs. These customers were refunded automatically. Affected agents had to apologize to customers who were already handed cash.

**Recovery:** The rollback completes within 2 minutes. The failed transactions are retried. Affected customers receive a 100 SYP apology credit. Agents are provided a written explanation of the outage to share with customers.

### Configuration error — agent commission rate set to 0% instead of 1% per CBS rates
**System Behavior:** A configuration change sets the default agent commission rate to 0% instead of the CBS-regulated 1%. Agents process transactions without earning commission for 15 minutes.

**User Impact:** 500 agents across Syria process 2,000 transactions with 0% commission. Each agent loses an average of 500 SYP in earnings. Agents in Aleppo notice the issue first and report it.

**Recovery:** The configuration alert fires on the anomalous value. The rate is corrected to 1% as per CBS regulations. Affected agents receive the missed commission plus a 10% goodwill credit (50 SYP). A pre-deployment validation check is added to compare commission rates against the CBS-regulated master table.

### Agent device stolen in Damascus — active session not invalidated
**System Behavior:** An agent's mobile device is stolen while logged into the agent app near the Hamidiyeh Souq in Damascus. The session remains active for 15 minutes (session TTL).

**User Impact:** The thief could perform cash-outs using the agent's account for up to 15 minutes. Customers near the theft location could lose funds if they transact with the stolen device.

**Recovery:** Agent sessions are bound to device fingerprint + biometric (fingerprint or face ID). A remote logout endpoint allows the agent to invalidate sessions by calling the support line at 1230 immediately. Session TTL is reduced to 5 minutes. All transactions initiated from the stolen device in the last 15 minutes are reversed. The incident is reported to Syrian authorities.

### Agent float settlement via BBS delayed due to bank holiday
**System Behavior:** A bank holiday is declared in Syria (e.g., Evacuation Day on April 17). BBS, Syria International Islamic Bank, and other partner banks are closed. Agent float settlements are queued until the next business day.

**User Impact:** Agents see "تأجيل تسوية الرصيد مع البنك بسبب العطلة الرسمية. سيتم المعالجة في أول يوم عمل" (Float settlement with the bank has been deferred due to the public holiday. Processing will occur on the next business day.)

**Recovery:** The settlement system is pre-configured with the Syrian official holiday calendar. Float settlement requests are automatically queued and processed on the next business day without penalty. Agents can continue operating as long as their float is sufficient. Interest is credited to agents for the settlement delay period.

## 9. Recovery Time Objectives Summary

| Failure Category | Detection Time | Recovery Time | RPO | Maximum Impact |
|-----------------|----------------|---------------|-----|----------------|
| Network (transient) | < 1 second | < 30 seconds | 0 | Single cash-out delayed |
| Network (DNS outage) | 30 seconds | < 5 minutes | 0 | All agent ops blocked |
| Network (mobile carrier) | < 5 seconds | < 2 minutes | 0 | Offline mode activated |
| Transaction failure | < 100ms | < 5 seconds | 0 | Single transaction failed |
| External dependency | < 10 seconds | < 15 minutes | 0 | Agent onboarding degraded |
| CBS report submission | < 1 minute | < 6 hours | 0 | Regulatory report delayed |
| Data inconsistency | < 5 minutes | < 1 hour | < 5 seconds | Float balance discrepancy |
| Security incident | < 1 minute | < 4 hours | 0 | Agent account frozen |
| Business logic | < 1 hour | < 24 hours | 0 | Functional limitation |
| Performance degradation | < 1 minute | < 5 minutes | 0 | Slow transaction processing |
| Operational (deployment) | < 5 minutes | < 10 minutes | < 1 minute | Transaction failures |

## Changelog

| Date | Version | Changes |
|------|---------|---------|
| 2026-05-29 | 1.0 | Initial release — Pure Syria context with SYP amounts, Syrian cities (Damascus, Aleppo, Homs, Latakia), Syrian telecoms (Syriatel, MTN), Syrian banks (BBS, Syria International Islamic Bank), CBS regulatory references, and Syrian holiday traffic patterns |

---

*Version: 1.0 | Last updated: 2026-05-29 | Owner: Agent Network Engineering Team — Syria*
