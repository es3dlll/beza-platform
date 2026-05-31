# 31. Wallet — Failure Scenarios

> **Purpose:** Document every failure mode for the Beza Wallet feature in Syria — network, transaction, external dependency, data consistency, security, and business logic failures. All scenarios use Syrian Pound (SYP) amounts, Syrian telecom operators (Syriatel, MTN), Syrian banks (BBS, Bemo, Syria International Islamic Bank), and Arabic error messages.

---

## 1. Network Failures

### Internet cut during P2P transfer after debit but before credit
**System Behavior:** Transaction marked `PENDING_DISCONNECT` in the ledger. Sender's balance is debited but recipient is not credited. A ledger entry `LEDGER_DEBIT_PENDING` is created with status `HELD`. The orchestration layer marks the transfer as requiring reconciliation.

**User Impact:** Sender sees a spinning loader, then the error "تعذر الاتصال بالخادم. يرجى التحقق من اتصالك بالإنترنت" (Could not connect to the server. Please check your internet connection.) The recipient sees nothing.

**Recovery:** A reconciliation cron job runs every 2 minutes to match orphaned debit records against pending credit entries. The credit is retried via Kafka with exponential backoff (3s, 9s, 27s). On success, the sender receives a push notification "تم استلام تحويلك" (Your transfer has been received.)

### API gateway timeout (>5s) during balance query
**System Behavior:** The API gateway returns HTTP 504 after 5 seconds. The circuit breaker opens for the upstream wallet service, blocking all requests for a 15-second cooldown period. Subsequent requests are immediately rejected with 503.

**User Impact:** The user sees "الخدمة غير متوفرة حالياً. حاول مرة أخرى لاحقاً" (Service is currently unavailable. Please try again later.) The balance may not display on the home screen and the cached value (last synced) is shown with a warning.

**Recovery:** The circuit breaker half-opens after 15 seconds, probing with a single request. On success, full traffic resumes. The client library retries with exponential backoff (2s, 4s, 8s). The stale cache is invalidated and refreshed.

### DNS resolution failure for wallet-api.beza.sy
**System Behavior:** CloudFront returns 502 Bad Gateway because no backend IP is reachable. No requests reach the application servers. All wallet operations are blocked.

**User Impact:** The app shows "فشل الاتصال بالخادم. يرجى التحقق من اتصالك بالإنترنت" (Connection to server failed. Please check your internet connection.) All wallet operations — transfers, balance checks, history — are unavailable.

**Recovery:** Route53 health check detects the outage within 30 seconds and initiates failover to the secondary region (5-minute TTL propagation). DNS TTL is set to 60 seconds for fast propagation. Client-side retry automatically succeeds after failover.

### WebSocket disconnect during real-time balance streaming
**System Behavior:** The client initiates reconnection with exponential backoff (1s, 2s, 4s, max 30s). The server stores the last 5 events per connection and replays them on successful reconnect.

**User Impact:** The balance temporarily shows a stale cached value from the last sync. An indicator "جاري التحديث..." (Updating...) appears at the top of the balance display.

**Recovery:** On successful reconnection, the server sends a `BALANCE_SYNC` event containing the current ledger balance. The client invalidates the local cache and renders the new balance. The entire recovery completes within 2 seconds typically.

### Syriatel 3G/4G network congestion during peak hours (Damascus central zone)
**System Behavior:** Mobile data latency increases from 50ms to 2,000ms+. TCP connections experience frequent timeouts. The app detects increased packet loss via the connection quality monitor and switches to lightweight API calls with reduced payload.

**User Impact:** Users in central Damascus experience slow transaction confirmations — transfers take 15-20 seconds instead of the normal 2 seconds. The app shows "جاري إرسال المبلغ..." (Sending amount...) for an extended period.

**Recovery:** The connection quality monitor degrades gracefully by reducing the API polling frequency from every 1 second to every 10 seconds. The app caches responses locally. Once Syriatel network congestion subsides, the connection monitor resets to normal polling within 30 seconds.

### MTN Syria SMS gateway throttling during mass notifications
**System Behavior:** The MTN SMPP connection reaches its 50 SMS/second throughput limit. Messages are queued on SQS with a 30-second delay between batch sends. The SMS priority queue is reordered to ensure OTP messages are sent before promotional messages.

**User Impact:** Users may experience SMS delivery delays of 2-5 minutes for transaction confirmations during high-traffic periods. Push notifications via FCM are sent immediately as a fallback.

**Recovery:** The system automatically load-balances between Syriatel and MTN SMPP connections based on real-time queue depth. If both operators are congested, a Twilio voice call fallback is triggered for OTP delivery.

## 2. Transaction Failures

### Insufficient balance — user tries to send 25,000 SYP but wallet has 12,000 SYP available
**System Behavior:** The ledger check enforces `available_balance >= transaction_amount` at the pre-validation stage. The transaction is rejected before any debit occurs. No funds are moved.

**User Impact:** The user sees "رصيد غير كافٍ. الرصيد المتاح: 12,000 ل.س" (Insufficient balance. Available balance: 12,000 SYP.)

**Recovery:** No automatic recovery is possible. The user must deposit additional funds or reduce the transfer amount. The UI proactively suggests the maximum sendable amount (12,000 SYP) with a single tap.

### Double spend attempt — user sends 50,000 SYP to two recipients in parallel (<50ms apart)
**System Behavior:** The idempotency key is constructed from the sender's device ID + UUID nonce. The second request carries a different key and is processed independently. Both pre-validation checks pass simultaneously because neither has committed yet. The database enforces a ledger-level `CHECK` constraint that prevents `SUM(debits) > balance`.

**User Impact:** The first transaction to complete database commit succeeds. The second fails at the constraint check level. One recipient receives 50,000 SYP. The other sees nothing.

**Recovery:** The failed transaction rolls back atomically. No funds are lost. The user sees a success for the first transfer and an error for the second: "تمت معالجة هذا الطلب مسبقًا" (This request has already been processed.) The user can retry with a new idempotency key.

### Duplicate idempotency key reused within 24 hours
**System Behavior:** The idempotency key is stored in Redis with a 24-hour TTL. When the same key is submitted again, Redis returns the stored response. The service returns HTTP 409 Conflict with the original transaction ID.

**User Impact:** The user is shown "تم تأكيد هذه المعاملة بالفعل. تحقق من سجل المعاملات" (This transaction has already been confirmed. Check your transaction history.)

**Recovery:** The client SDK must generate a unique idempotency key for each request. The SDK auto-generates a UUIDv4 per request. No server-side recovery is needed since the transaction already succeeded.

### Transaction amount exceeds daily limit (>500,000 SYP in a single transfer)
**System Behavior:** The pre-validation engine checks `SUM(transactions_today) + amount <= daily_limit`. The limit is 500,000 SYP for Tier 2 users. The transaction is rejected with code `DAILY_LIMIT_EXCEEDED`.

**User Impact:** The user sees "تجاوز الحد اليومي للمعاملات. الحد المتبقي: 423,000 ل.س" (Daily transaction limit exceeded. Remaining limit: 423,000 SYP.)

**Recovery:** The daily limit resets at midnight Syria time (UTC+3). The user can request a temporary limit increase through customer support with enhanced KYC verification per CBS guidelines. Alternatively, the user can wait until the next day when the limit resets.

### Account frozen — transaction blocked by CBS compliance hold
**System Behavior:** An AML trigger (e.g., suspicious pattern detection per Central Bank of Syria regulations) sets `wallet_status = FROZEN`. All outgoing transactions are blocked at the server level before any ledger operation. Incoming transactions are also blocked.

**User Impact:** The user sees "الحساب مجمد. يرجى الاتصال بخدمة العملاء على 1230" (Account frozen. Please contact customer service at 1230.) The user cannot send, receive, or withdraw funds.

**Recovery:** The compliance team reviews the account within 24 hours in coordination with CBS reporting requirements. If the account is cleared, the status is changed to `ACTIVE` and the user receives a push notification "تم إلغاء تجميد حسابك" (Your account has been unfrozen.)

### Partial transfer success — debit succeeded but credit to recipient failed (recipient wallet deleted)
**System Behavior:** The sender's wallet is debited in the ledger. The credit to the recipient fails because the recipient's wallet was deleted between the validation and commit steps. The orchestrator retries the credit 3 times with increasing delays (5s, 30s, 120s).

**User Impact:** The sender sees "قيد المعالجة" (Processing...) for up to 2 minutes. The recipient receives nothing. The money is held in a suspense ledger.

**Recovery:** After 3 failed retries, the orchestrator creates a `SETTLEMENT_NEEDED` event for the operations team. An ops engineer manually reverses the hold via the admin panel. The sender is refunded within 1 hour. A bug fix prevents wallet deletion while pending transactions exist.

### Invalid recipient phone number — MTN number entered as Syriatel format
**System Behavior:** The pre-validation checks the recipient's phone number prefix against telecom operator ranges (Syriatel: 093, 094; MTN: 095, 096). A mismatch is detected. The system returns `INVALID_RECIPIENT_OPERATOR`.

**User Impact:** The sender sees "رقم المستلم غير صحيح. تأكد من اختيار شبكة الاتصال الصحيحة (سيريتيل أو إم تي إن)" (Invalid recipient number. Make sure to select the correct network operator — Syriatel or MTN.)

**Recovery:** The sender corrects the recipient's phone number. The app auto-detects the operator based on the entered prefix and suggests corrections. No funds are at risk since no debit occurred.

## 3. External Dependency Failures

### Syriatel mobile money API service down during balance check
**System Behavior:** The wallet service calls the Syriatel Cash API to verify mobile wallet balance. The call times out after 10 seconds. The service falls back to wallet-only transfer mode, disabling Syriatel Cash top-up and withdrawal.

**User Impact:** Users attempting to top up via Syriatel Cash see "خدمة سيريتيل كاش غير متوفرة حالياً. استخدم بطاقة البنك بدلاً من ذلك" (Syriatel Cash service is currently unavailable. Please use a bank card instead.)

**Recovery:** The Syriatel Cash status is monitored via a health endpoint `/health/syriatel-cash` polled every 30 seconds. When the Syriatel service is restored, any queued top-up requests are processed. Users receive a notification "خدمة سيريتيل كاش متاحة الآن" (Syriatel Cash service is now available.)

### MTN Syria API timeout during mobile money transfer
**System Behavior:** The MTN Mobile Money API does not respond within the 30-second timeout window. The transaction is placed in `PENDING_EXTERNAL` status. The circuit breaker opens for the MTN downstream service.

**User Impact:** The user sees "جاري تأكيد التحويل عبر إم تي إن. قد يستغرق ذلك بضع دقائق" (Confirming the transfer via MTN. This may take a few minutes.) The wallet balance does not update immediately.

**Recovery:** An internal poller checks the MTN transaction status via the inquiry API every 30 seconds for up to 15 minutes. On receiving confirmation, the wallet is credited. The circuit breaker resets after 60 seconds with a single probing request.

### BBS (Banque Bemo Saudi Fransi) API timeout during wallet top-up from bank account
**System Behavior:** The integration sends a credit transfer request to BBS. The bank API does not respond within the 30-second timeout. The transaction is placed in `PENDING_EXTERNAL` status.

**User Impact:** The user sees "جاري تأكيد التحويل من مصرف بيمو السعودي الفرنسي. قد يستغرق ذلك بضع دقائق" (Confirming the transfer from BBS. This may take a few minutes.) The wallet balance does not update immediately.

**Recovery:** An internal poller checks the BBS transaction status via the inquiry API every 30 seconds for up to 15 minutes. On receiving confirmation, the wallet is credited. On receiving a failure notification, the transaction is marked as `FAILED` and the user is notified.

### CBS (Central Bank of Syria) exchange rate feed unavailable
**System Behavior:** The rate cache in Redis serves the last known SYP/USD rate for up to 15 minutes. A warning `RATE_STALE` is logged. After the cache TTL expires (30 minutes), no new FX conversions are allowed.

**User Impact:** Users initiating foreign currency transfers see "أسعار الصرف غير متوفرة حالياً من مصرف سورية المركزي. حاول مرة أخرى بعد 30 دقيقة" (Exchange rates from the Central Bank of Syria are currently unavailable. Please try again in 30 minutes.)

**Recovery:** The operations team is alerted. If the CBS feed is down for more than 1 hour, a manual rate upload is performed via the admin panel with CBS telephone approval. When the feed is restored, the stale flag is cleared automatically.

### Syria International Islamic Bank KYC verification API failure
**System Behavior:** The system operates in `FAIL_OPEN` mode for KYC — if the identity verification provider is unavailable, basic transactions under 50,000 SYP daily are allowed without full KYC verification.

**User Impact:** New users registering via referral see "التحقق من الهوية قيد المراجعة عبر المصرف الدولي الإسلامي. يمكنك استخدام الخدمات الأساسية في الوقت الحالي" (Identity verification is under review with Syria International Islamic Bank. You can use basic services for now.)

**Recovery:** KYC verification requests are backed up on an SQS queue. When the identity provider recovers, the queue is drained and batch verification runs. Users are notified when their identity is fully verified.

### QR payment acquirer (Syrian Payment Network) timeout
**System Behavior:** The Syrian Payment Network (SPN) acquirer API for QR code merchant payments returns HTTP 502. The circuit breaker opens. The QR payment flow falls back to manual merchant ID entry.

**User Impact:** Users scanning merchant QR codes see "خدمة الدفع عبر رمز الاستجابة السريعة غير متوفرة. أدخل رمز التاجر يدوياً" (QR payment service is unavailable. Please enter the merchant code manually.)

**Recovery:** The SPN health endpoint is polled every 30 seconds. On restoration, the QR payment flow resumes automatically. Any QR payments that were initiated before the outage are retried through the manual entry fallback.

## 4. Data Consistency Failures

### Database write failure during transfer (primary DB crash mid-transaction)
**System Behavior:** The transaction is executed within a try-catch block with automatic rollback on `SQLException`. The ledger uses atomic debit-credit operations within a single database transaction. If the DB crashes mid-write, the in-flight transaction is rolled back.

**User Impact:** The transaction fails atomically. The user sees "فشلت المعاملة. لم يتم خصم أي مبلغ من حسابك" (Transaction failed. No amount has been deducted from your account.)

**Recovery:** The application retries the transaction 3 times with a 1-second delay between attempts. On the 3rd failure, the request is sent to a dead-letter queue on SQS for operations review. A manual fix is performed within the 4-hour SLA.

### Redis cache inconsistency — balance cached as 250,000 SYP but ledger says 175,000 SYP
**System Behavior:** The cache-aside pattern is used: the application reads from cache and verifies the `cache_version` against the database version. A version mismatch triggers immediate cache invalidation and a fresh read from the primary database.

**User Impact:** The user may briefly see a stale balance during the version mismatch window (approximately 200ms). The "جاري التحديث..." (Updating...) indicator appears during the refresh.

**Recovery:** The cache entry is invalidated and a fresh value is loaded from the ledger database. A `CACHE_MISMATCH` metric is incremented for monitoring. The inconsistency is automatically fixed within 300ms.

### Event lost in Kafka queue during P2P transfer
**System Behavior:** Exactly-once semantics are enforced using Kafka transactional producers. If the broker fails before committing the transaction, the consumer group rebalances and the message is re-delivered. The consumer is idempotent.

**User Impact:** No user impact is visible for transient Kafka failures. If a message is truly lost after exhausting all retries, it lands in a dead-letter queue.

**Recovery:** The dead-letter queue is processed by an hourly cron job. Operations replays failed events through the admin panel endpoint `POST /admin/replay/{eventId}`. The recovery point objective (RPO) is less than 5 seconds.

### Dual-write failure — ledger updated but audit log write failed
**System Behavior:** The wallet service writes to the primary ledger database (PostgreSQL) first. If the write to the audit log (MongoDB) fails, the Saga pattern triggers a compensatory transaction that reverses the ledger entry.

**User Impact:** The user initially sees a success screen, then a reversal entry appears in the transaction history within 2 seconds. The net effect is zero. The user is not financially impacted.

**Recovery:** The compensatory transaction is logged as `AUDIT_REVERSAL` for audit purposes. The operations team is alerted. An auto-recovery queue retries the audit log write 3 times before escalating.

### Secondary index corruption on wallet_transactions table
**System Behavior:** The query planner falls back to a full table scan because the corrupted index is no longer usable. Query latency increases dramatically from 50ms to 8 seconds for transaction history queries.

**User Impact:** Users experience very slow transaction history loading — "جاري تحميل سجل المعاملات..." (Loading transaction history...) for more than 5 seconds.

**Recovery:** The DBA team is alerted. The index is rebuilt during the off-peak window (2:00 AM Syria time). While the index is being rebuilt, the pagination is temporarily degraded to 10 items per page to reduce the query surface area.

### Duplicate transaction entry due to consumer rebalancing in Kafka group
**System Behavior:** A Kafka consumer rebalancing event causes a message to be processed twice. The consumer's idempotent handler checks `transaction_id` uniqueness in the database before inserting. The second attempt finds the duplicate key and skips the insert.

**User Impact:** No user impact. The idempotent handler prevents any double credit or double debit from appearing. The duplicate is silently discarded and logged.

**Recovery:** The duplicate is logged as `DUPLICATE_MESSAGE_SUPPRESSED` for monitoring. No corrective action is needed. The consumer group stabilizes after rebalancing completes within 2 seconds.

## 5. Security Failures

### Fraud false positive — legitimate user sending 500,000 SYP to family in Aleppo
**System Behavior:** The AML rules engine triggers on the combination of (a) amount > 300,000 SYP and (b) a newly added beneficiary. The transaction is placed in `PENDING_REVIEW` status per CBS AML regulations. No funds are moved.

**User Impact:** The sender sees "المعاملة قيد المراجعة من قبل فريق الامتثال وفقاً لتعليمات مصرف سورية المركزي. سيتم إعلامك خلال 24 ساعة" (The transaction is under review by the compliance team per Central Bank of Syria regulations. You will be notified within 24 hours.)

**Recovery:** The compliance team reviews the transaction within the 4-hour SLA. If the transaction is legitimate, it is approved. The user receives a push notification "تمت الموافقة على معاملتك" (Your transaction has been approved.)

### Fraud false negative — stolen phone used to transfer 150,000 SYP to an unknown account
**System Behavior:** The behavioral model scores the transaction at 0.35, which is below the 0.7 threshold that would trigger additional MFA challenges. No additional verification is requested. The transaction proceeds normally.

**User Impact:** The legitimate user loses 150,000 SYP. The transaction appears as a normal outgoing transfer in the history. The user only discovers the loss when checking their balance.

**Recovery:** The user reports the fraud through the call center. Beza's insurance policy covers the first 100,000 SYP of verified fraud losses. The fraud model is retrained with a new feature: `device_fingerprint_change`. The stolen device is blacklisted.

### Unauthorized access attempt — 5 failed PIN entries on login
**System Behavior:** After 5 consecutive incorrect PIN entries, the account is locked for 30 minutes. The security team receives an alert through the SIEM system. The failed attempts are logged with IP, device fingerprint, and geolocation.

**User Impact:** The user sees "تم قفل الحساب بسبب محاولات متكررة خاطئة. حاول مرة أخرى بعد 30 دقيقة" (The account has been locked due to repeated incorrect attempts. Please try again in 30 minutes.)

**Recovery:** After 30 minutes, the lock is automatically cleared. If there have been 10 or more failed attempts, the account is frozen until the user completes identity re-verification via a video call with a customer service agent.

### Session token theft via XSS attack
**System Behavior:** The token is stolen from localStorage. The attacker uses the token to initiate a transfer. The backend validates the `request_ip` against the `token_issued_ip`. An IP mismatch triggers an MFA challenge.

**User Impact:** The legitimate user is prompted for MFA on their next operation with the message "تم تسجيل الدخول من جهاز جديد. يرجى تأكيد هويتك" (A login from a new device has been detected. Please confirm your identity.)

**Recovery:** The stolen token is invalidated server-side. The user must re-authenticate with MFA. All existing sessions are revoked and new tokens are issued for all active sessions.

### Man-in-the-middle attack on public WiFi in Damascus café
**System Behavior:** TLS 1.3 with certificate pinning prevents decryption of the traffic. The mobile app refuses to establish a connection if the certificate pinning validation fails.

**User Impact:** The user sees "اتصال غير آمن. يرجى استخدام شبكة إنترنت موثوقة" (Unsecure connection detected. Please use a trusted internet network.) All financial operations are blocked.

**Recovery:** The app blocks all financial operations until a secure connection is restored. No data exposure occurs because the TLS handshake fails before any application data is transmitted.

### SIM swap attack on Syriatel mobile number
**System Behavior:** An attacker performs a SIM swap with a fraudulent ID at a Syriatel branch. The attacker requests an OTP to reset the wallet PIN. The system detects the SIM change because the IMSI differs from the last registered IMSI.

**User Impact:** The legitimate user loses mobile service. The attacker attempts to access the wallet. The system blocks the PIN reset and alerts the security team. The user sees no direct impact but cannot log in.

**Recovery:** The user visits a Syriatel branch to report the SIM swap and reclaim their number. Once the original SIM is restored, the user completes identity re-verification. The wallet status is set to `SIM_SWAP_LOCKED` until the user contacts customer support. All pending transactions are cancelled.

## 6. Business Logic Failures

### Wallet-to-wallet transfer to a frozen recipient wallet
**System Behavior:** The pre-validation engine checks the recipient's `wallet_status` before initiating the transfer. If the status is `FROZEN`, the transfer is rejected with a specific error code `RECIPIENT_FROZEN`.

**User Impact:** The sender sees "لا يمكن إرسال الأموال إلى هذا الحساب في الوقت الحالي" (Cannot send money to this account at this time.) No funds are deducted.

**Recovery:** The recipient must contact customer support to initiate the unfreeze process. After the wallet status changes to `ACTIVE`, the sender can retry the transfer. The sender receives a notification if the recipient's wallet becomes active within 7 days.

### Daily aggregate limit hit at 11:55 PM — user tries one more transfer
**System Behavior:** The daily aggregate counter shows 498,000 SYP against a 500,000 SYP limit. The user attempts a 5,000 SYP transfer. The pre-validation rejects the transaction because 498,000 + 5,000 = 503,000 > 500,000.

**User Impact:** The user sees "تم تجاوز الحد اليومي. يتبقى 5 دقائق حتى منتصف الليل لإعادة التعيين" (Daily limit exceeded. 5 minutes remaining until midnight reset.)

**Recovery:** The user can wait 5 minutes for the daily limit to reset at midnight Syria time (UTC+3). Alternatively, the user can request a temporary limit increase through customer support. The limit increase is granted instantly up to 2x the standard limit per CBS approval.

### Beneficiary wallet deleted between scheduling and execution
**System Behavior:** A scheduled transfer was created for a future date (e.g., monthly rent payment on the 1st). Between scheduling and execution, the beneficiary deleted their wallet. At execution time, the system checks the recipient's wallet status and finds it deleted.

**User Impact:** The sender sees "فشل التحويل المجدول: حساب المستلم غير موجود" (Scheduled transfer failed: recipient account not found.)

**Recovery:** The system reverses any hold that was placed on the sender's funds at scheduling time. The sender is notified with "تم إلغاء التحويل المجدول وإعادة المبلغ إلى محفظتك" (The scheduled transfer has been cancelled and the amount has been returned to your wallet.)

### Tier 1 user (daily limit 100,000 SYP) tries to send 120,000 SYP in one transaction
**System Behavior:** The pre-validation checks `user_tier.max_transaction = 100,000 SYP`. The transaction is rejected at the UI level before any server call is made.

**User Impact:** The user sees "الحد الأقصى للمعاملة الواحدة هو 100,000 ل.س. قم بترقية حسابك لإرسال مبالغ أكبر" (The maximum single transaction is 100,000 SYP. Upgrade your account to send larger amounts.)

**Recovery:** The user can complete a KYC upgrade to Tier 2 (daily limit 500,000 SYP) through the in-app document upload flow. The upgrade is instant when all required documents (Syrian national ID or passport) are provided. Once upgraded, the user can retry the transfer.

### Cash-out request but no agent within 10km in Homs has sufficient float
**System Behavior:** The agent float query returns zero agents within a 10km radius with adequate cash to fulfill the 200,000 SYP request. The system returns an availability message to the user.

**User Impact:** The user sees "لا يوجد وكيل قريب في حمص لديه رصيد كافٍ للسحب النقدي حالياً. حاول مرة أخرى لاحقاً" (No nearby agent in Homs has sufficient cash balance for withdrawal at this time. Please try again later.)

**Recovery:** The system sends an alert to nearby agents suggesting they top up their float. The user is encouraged to retry after 30 minutes. An alternative option is offered: withdrawing cash from a BBS ATM using the Beza card instead.

### Transfer to a sanctioned entity (OFAC/Syria sanctions list match)
**System Behavior:** The sanctions screening engine checks all beneficiary names against the OFAC SDN list, EU sanctions list, and the Syrian sanctions list maintained by CBS. A name similarity hit above the threshold blocks the transaction.

**User Impact:** The sender sees "تم رفض المعاملة بسبب متطلبات الامتثال للعقوبات. يرجى الاتصال بخدمة العملاء" (Transaction rejected due to sanctions compliance requirements. Please contact customer service.) The sender cannot retry the same transfer.

**Recovery:** The compliance team reviews the match within 24 hours. If it is a false positive (e.g., common name match), the sender is contacted to provide additional identification. Approved transactions are released. True matches are reported to CBS as required by law.

## 7. Performance & Scalability Failures

### Sudden traffic spike — 10x normal load during Eid al-Adha holiday (2,000 TPS vs normal 200 TPS)
**System Behavior:** The API gateway auto-scales from 10 to 80 pods within 2 minutes. The database connection pool scales from 50 to 300 connections. Some requests may queue briefly during the scale-up.

**User Impact:** Users experience 2-3 second latency instead of the normal 200ms during the scale-up window. Requests are queued but not dropped.

**Recovery:** Auto-scaling policies trigger at 70% CPU utilization. The system handles the full load within 3 minutes of the spike starting. A load test is scheduled ahead of known holiday periods (Eid al-Fitr, Eid al-Adha, Revolution Day).

### Database connection pool exhaustion — all 300 connections in use
**System Behavior:** New requests cannot acquire a database connection. They are queued in the application layer with a 5-second timeout. If the timeout expires, requests fail with `CONNECTION_POOL_EXHAUSTED`.

**User Impact:** Users see "الخدمة مشغولة حالياً بسبب كثافة الاستخدام. حاول مرة أخرى" (The service is busy due to high usage. Please try again.) during peak load.

**Recovery:** The connection pool auto-scales based on the `active_connections` metric. Pending requests are retried. The root cause — an inefficient query — is identified and optimized.

### Hot partition on wallet_transactions table — 50% of transactions go to the same DB shard (Damascus users)
**System Behavior:** The sharding key (user_id modulo 10) causes uneven distribution because 50% of users are in the Damascus region. One shard handles 50% of all transactions. That shard's latency increases from 50ms to 500ms.

**User Impact:** Damascus-based users experience slow transaction history and delayed transfers. Users in other governorates (Aleppo, Homs, Latakia) are unaffected.

**Recovery:** The sharding key is rebalanced to a hash of `user_id + governorate_code` for more even distribution. The hot shard is scaled with more read replicas.

## 8. Operational Failures

### Deployment rollback — v2.4.0 introduces a bug that deducts fees twice on P2P transfers
**System Behavior:** The canary deployment detects a 15% increase in fee revenue within 5 minutes. The automated rollback is triggered. v2.4.0 is rolled back to v2.3.9.

**User Impact:** Approximately 200 users are double-charged fees (average 250 SYP each). These users see two fee entries in their history.

**Recovery:** The rollback completes within 2 minutes. A corrective batch job reverses the duplicate fees. Affected users receive a notification and an apology credit of 500 SYP.

### Configuration change error — incorrect daily limit value deployed (5,000 SYP instead of 500,000 SYP)
**System Behavior:** A configuration change sets the daily transfer limit to 5,000 SYP instead of 500,000 SYP. All users are limited to 5,000 SYP per day.

**User Impact:** Users attempting transfers above 5,000 SYP see "تم تجاوز الحد اليومي للمعاملات" (Daily transaction limit exceeded.) Customer complaints spike. Social media reports mention "Beza wallet limit reduced to 5,000 SYP."

**Recovery:** The configuration change triggers an alert because the value deviates by more than 2 standard deviations from the mean. The change is reverted within 3 minutes. A pre-deployment validation check is added.

### Certificate expiry — internal service certificate expires, breaking service-to-service communication
**System Behavior:** The mutual TLS certificate between the wallet service and the ledger service expires. All inter-service communication fails. The wallet service returns 500 errors.

**User Impact:** All wallet operations fail. Users see "حدث خطأ في النظام. يرجى المحاولة لاحقاً" (A system error occurred. Please try later.)

**Recovery:** A PagerDuty alert fires 30 days before the certificate expiry for renewal. The expired certificate is replaced within 5 minutes. Automated certificate renewal with 90-day validity is implemented.

### Outage during Syria internet shutdown (government-mandated blackout)
**System Behavior:** The Syrian Telecommunications Establishment (STE) cuts internet access nationwide. The app detects total loss of connectivity to all servers. All operations are blocked. The app enters offline mode with last known balance display.

**User Impact:** Users cannot perform any transactions. The app shows "انقطاع خدمة الإنترنت في سورية. الخدمة ستعود تلقائياً عند توفر الاتصال" (Internet service is interrupted in Syria. Service will resume automatically when connectivity is restored.) Cached balance is displayed with a warning.

**Recovery:** The app stores pending transactions locally in an encrypted queue. When internet service is restored, the queue is processed in order. Users receive notifications for each completed transaction. No transactions are lost as they are replayed idempotently.

## 9. Recovery Time Objectives Summary

| Failure Category | Detection Time | Recovery Time | RPO | Maximum Impact |
|-----------------|----------------|---------------|-----|----------------|
| Network (transient) | < 1 second | < 30 seconds | 0 | Single transaction delayed |
| Network (DNS outage) | 30 seconds | < 5 minutes | 0 | All wallet operations blocked |
| Transaction failure | < 100ms | < 5 seconds | 0 | Single transaction failed |
| External dependency | < 10 seconds | < 15 minutes | 0 | Feature degradation |
| Data inconsistency | < 5 minutes | < 1 hour | < 5 seconds | Transaction history discrepancy |
| Security incident | < 1 minute | < 4 hours | 0 | Account frozen / transaction held |
| Business logic | < 1 hour | < 24 hours | 0 | Functional limitation |
| Performance degradation | < 1 minute | < 5 minutes | 0 | Slow response times |
| Operational (deployment) | < 5 minutes | < 10 minutes | < 1 minute | Feature regression |

## Changelog

| Date | Version | Changes |
|------|---------|---------|
| 2026-05-29 | 1.0 | Initial release — Pure Syria context with SYP amounts, Syrian telecoms (Syriatel, MTN), Syrian banks (BBS, Bemo, SIIB), CBS references, and Syrian holiday traffic patterns |

---

*Version: 1.0 | Last updated: 2026-05-29 | Owner: Wallet Engineering Team — Syria*
