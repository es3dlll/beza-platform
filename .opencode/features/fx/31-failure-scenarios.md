# 31. FX — Failure Scenarios

> **Purpose:** Document every failure mode for the Beza FX (Foreign Exchange) feature — rate locking, conversion, liquidity management, and corridor-specific FX operations. Uses ETB, USD, EUR, GBP amounts and Arabic/Amharic messaging.

---

## 1. Network Failures

### Internet cut during FX rate lock — user submits conversion but network drops before lock persistence
**System Behavior:** The rate lock is initiated on the client side but the HTTP request never reaches the server. The client times out after 10 seconds. No rate lock record is created in the database.

**User Impact:** The user sees a spinner, then "فشل تأمين سعر الصرف. حاول مرة أخرى" (Failed to lock the exchange rate. Please try again.) No rate is reserved and no funds are held.

**Recovery:** The user retries the rate lock. A fresh rate is fetched from the CBX feed. Since no lock was persisted, no rate is burned or wasted.

### API timeout (>5s) during rate query for 10,000 USD
**System Behavior:** The FX service returns a cached rate from Redis that is up to 30 seconds old. If no cache is available because the TTL has expired, the service returns HTTP 503 Service Unavailable.

**User Impact:** The user sees "أسعار الصرف غير متوفرة حالياً" (Exchange rates are currently unavailable.) If a cached rate is returned, the user sees a warning "قد يختلف السعر" (The rate may differ.)

**Recovery:** The circuit breaker trips after the 5-second timeout with a 15-second cooldown. The client retries with exponential backoff. A fresh rate is fetched on the next successful attempt.

### DNS failure for fx-api.beza.et during CBX rate feed callback
**System Behavior:** The CBX rate feed server cannot push rate updates because the FX API DNS is unreachable. The rate feed goes stale. No new rates are ingested.

**User Impact:** The displayed rates freeze at the last pushed value. Users see non-moving rates with a warning "آخر تحديث: منذ 5 دقائق" (Last updated: 5 minutes ago.)

**Recovery:** Route53 health check detects the DNS failure and initiates failover to the secondary region. The CBX maintains a queue of undelivered rate updates (up to 1000 messages) that are delivered when connectivity is restored.

### WebSocket disconnect during live rate streaming
**System Behavior:** The client detects the WebSocket disconnection and initiates reconnection with incremental backoff (1s, 2s, 4s). Missed rate updates are replayed as a snapshot plus deltas.

**User Impact:** The user sees a frozen rate ticker on the screen. The indicator "جاري تحديث الأسعار..." (Updating rates...) is shown during the reconnection period.

**Recovery:** On successful reconnection, the server sends a full rate table snapshot covering all instruments. No rates are lost because Kafka retains the rate topic for 24 hours and the client receives all missed deltas.

### Network partition between FX service and liquidity pool service
**System Behavior:** The FX service quotes a rate without being able to verify that sufficient liquidity is available in the pool. The rate is quoted optimistically.

**User Impact:** The user locks a rate. At conversion time, the system discovers there is insufficient liquidity and the conversion fails. The user sees "عذراً، لا توجد سيولة كافية للتحويل" (Sorry, insufficient liquidity for conversion.)

**Recovery:** The FX service is updated to pre-reserve liquidity before quoting a rate. If the liquidity service is partitioned, the circuit breaker opens and prevents any rate quotes from being issued.

## 2. Transaction Failures

### Rate lock expired before conversion — 57.50 ETB/USD locked at 10:00:00, valid 30s, conversion at 10:00:45
**System Behavior:** The lock's `expires_at` field is checked against the current database time. The expired lock is rejected. A new rate of 57.80 is fetched from the CBX feed.

**User Impact:** The user sees "انتهت صلاحية السعر. السعر الجديد: 57.80 ETB/USD" (The rate has expired. The new rate is 57.80 ETB/USD.)

**Recovery:** The user can accept the new rate or cancel the transaction. If cancelled, no funds are moved. If accepted, a new rate lock is created at 57.80 with a fresh 30-second validity window.

### Double conversion attempt with the same lock ID
**System Behavior:** The lock ID is used as an idempotency key. When the second conversion request arrives with the same lock ID, the system returns HTTP 409 Conflict.

**User Impact:** The user sees "تم استخدام سعر الصرف هذا مسبقاً" (This exchange rate has already been used.)

**Recovery:** The first conversion is processed normally. The second request is silently discarded. The rate lock is marked as consumed in the database to prevent any future reuse.

### Duplicate idempotency key for FX conversion
**System Behavior:** The idempotency key is stored in Redis with a 48-hour TTL. A second request with the same key returns HTTP 409 Conflict.

**User Impact:** The user sees "تمت معالجة طلب التحويل مسبقاً" (This conversion request has already been processed.)

**Recovery:** The client SDK must generate a unique UUID per conversion request. The SDK's `FxClient.createConversion()` method auto-generates a UUIDv4 for each call.

### Minimum conversion amount not met — user attempts 50 USD conversion (minimum 100 USD)
**System Behavior:** The pre-validation engine checks the amount against the configured minimum (100 USD equivalent). The transaction is rejected with error code `MIN_AMOUNT`.

**User Impact:** The user sees "الحد الأدنى للتحويل هو $100 USD أو ما يعادله" (The minimum conversion amount is $100 USD or equivalent.)

**Recovery:** The UI pre-validates the amount and grays out the submit button when the amount is below the minimum. A tooltip explains the minimum requirement.

### Maximum conversion amount exceeded — user attempts 100,000 USD (maximum 25,000 USD per transaction)
**System Behavior:** The pre-validation engine checks the amount against the maximum per-transaction limit. The transaction is rejected with `MAX_AMOUNT_EXCEEDED`.

**User Impact:** The user sees "الحد الأقصى للتحويل هو $25,000 USD للمعاملة الواحدة" (The maximum conversion amount is $25,000 USD per transaction.)

**Recovery:** The user can split the conversion into multiple transactions of up to $25,000 each, or use the wholesale FX desk for larger amounts.

### Liquidity insufficient at lock execution — 50,000 USD sold but pool has only 30,000 USD
**System Behavior:** The FX service checks the liquidity pool balance before confirming the conversion. If the pool balance is insufficient, the conversion is rejected.

**User Impact:** The user sees "عذراً، لا توجد سيولة كافية لتنفيذ التحويل. تم إلغاء الحجز" (Sorry, there is insufficient liquidity to execute the conversion. The lock has been cancelled.)

**Recovery:** The rate lock is reversed. No funds are debited from the user's account. The user can try a smaller amount or wait for the liquidity pool to be replenished.

## 3. External Dependency Failures

### CBX (National Bank of Ethiopia) rate feed down
**System Behavior:** The FX service switches to `LAST_KNOWN_RATE` mode. It serves rates that are up to 30 minutes old. A `RATE_STALE` warning is broadcast on the status endpoint.

**User Impact:** Users see a banner "أسعار الصرف قد لا تكون محدثة" (Exchange rates may not be current.) New FX locks use the stale rate plus a 0.5% risk margin.

**Recovery:** The operations team contacts the CBX. When the feed is restored, the stale flag is cleared. The 30-minute window of stale rates is compensated by the 0.5% margin.

### CBX rate feed delayed by more than 30 minutes
**System Behavior:** All new FX conversions are blocked. Only wallet-to-wallet ETB transfers are allowed. The FX service returns a 503 for all conversion requests.

**User Impact:** Users attempting FX conversions see "خدمة تحويل العملات غير متوفرة حالياً" (Currency conversion service is currently unavailable.)

**Recovery:** The operations team manually uploads rates received from the CBX via phone or email. Once manually uploaded, the system resumes processing. Auto-resume happens when the feed is restored.

### Liquidity provider (correspondent bank) API timeout
**System Behavior:** The liquidity reservation call to the correspondent bank hangs. The FX service falls back to the available pool balance cached in Redis (updated every 60 seconds).

**User Impact:** The rate quote is based on the cached pool balance. If the cached balance shows insufficient liquidity, the quote is rejected. "السيولة غير متوفرة" (Liquidity is unavailable.)

**Recovery:** A consistent hashing algorithm routes to an alternate liquidity provider. The circuit breaker for the primary provider trips after 3 consecutive timeouts and resets after 60 seconds.

### SWIFT gateway unavailable for cross-currency settlement
**System Behavior:** The FX settlement messages are queued in the SWIFT store-and-forward system. The settlement batch is delayed.

**User Impact:** The conversion completes on the Beza ledger. The external settlement is pending. "تسوية خارجية قيد الانتظار" (External settlement pending.)

**Recovery:** The SWIFT queue is monitored. If the delay exceeds 4 hours, the operations team is alerted to contact the SWIFT service desk. The settlement is processed when the gateway is restored.

### Market data provider (Bloomberg/Reuters) feed outage
**System Behavior:** The FX service falls back to the CBX official rate, which is less frequently updated and has a wider spread. The bid-ask spread is increased by 0.5% as a risk buffer.

**User Impact:** Users see wider bid-ask spreads. "فروق أسعار أوسع بسبب ظروف السوق" (Wider spreads due to market conditions.)

**Recovery:** The auto-fallback to the CBX official rate requires no manual intervention. When the primary market data feed is restored, the system automatically switches back to the tighter spreads.

## 4. Data Consistency Failures

### FX rate lock created in DB but not reflected in cache
**System Behavior:** The rate lock is persisted to the database. The cache entry for that rate is not updated. When the conversion service reads from the cache, it does not see the lock.

**User Impact:** The user might accidentally override their own lock by fetching a fresh rate before the conversion. The original lock is still valid and causes a lock conflict.

**Recovery:** A `CACHE_MISMATCH` alert triggers automatic cache invalidation. A fresh read from the database confirms the lock. The client always uses the `lock_id` returned by the lock creation endpoint.

### Conversion DB write succeeds but ledger update fails
**System Behavior:** The USD debit is recorded in the FX database. The ETB credit write to the user's wallet fails. The Saga pattern detects the inconsistency and triggers compensation.

**User Impact:** The user sees a reversal notification "تم إلغاء التحويل وإعادة $500 USD" (The conversion has been cancelled and $500 USD has been returned.)

**Recovery:** The compensatory transaction reverses the USD debit. A retry queue attempts the ledger update 3 times (5s, 30s, 120s) before the compensation is finalized.

### FX rate event lost in Kafka — rate update published but not consumed
**System Behavior:** The rate update message is published to the Kafka topic. One or more consumers (pricing engine, wallet, remittance) fail to consume the message due to a consumer group rebalance.

**User Impact:** Users of the affected consumer see a slightly stale rate for that currency pair. The price discrepancy can be up to 1%.

**Recovery:** The Kafka dead-letter queue consumer replays missed events. Consumer lag is monitored via LinkedIn Burrow. An alert is triggered if consumer lag exceeds 100 messages.

### Dual-write inconsistency — rate lock DB write + Kafka event publish partially fails
**System Behavior:** The rate lock event is published to Kafka successfully. The database write fails (rare race condition). The system believes no lock exists.

**User Impact:** The user's lock is not persisted. When the user attempts the conversion, the system returns "lock_not_found" error. The pre-authorized amount appears held but no valid lock exists.

**Recovery:** A compensating event consumes the pre-authorized hold. The user sees "فشل التحويل. لم يتم خصم أي مبلغ" (Conversion failed. No amount has been deducted.)

### Liquidity pool counter corrupted after concurrent conversions
**System Behavior:** Two concurrent conversions both read the liquidity pool as 50,000 USD. Both deduct 10,000 USD from their local copy. The pool counter in the database ends up at 40,000 USD instead of 30,000 USD.

**User Impact:** The pool shows phantom liquidity. The next conversion attempt fails because the actual pool is exhausted earlier than expected.

**Recovery:** Optimistic locking with a `version` column prevents this race condition. The second transaction fails on the write with `OPTIMISTIC_LOCK_EXCEPTION` and is retried with the updated counter.

## 5. Security Failures

### Fraud false positive — recurring conversion pattern flagged as wash trading
**System Behavior:** The AML rules engine detects 3 same-day USD to ETB to USD round-trip conversions. The pattern is flagged as potential wash trading.

**User Impact:** The user sees "تم تعليق التحويل للمراجعة" (The conversion has been suspended for review.) The user's FX access is temporarily restricted.

**Recovery:** The compliance team reviews the trading pattern within 4 hours. If the activity is legitimate hedging, the pattern is whitelisted. The user's FX access is restored.

### Fraud false negative — front-running via rate feed latency
**System Behavior:** An insider who can see the rate change before it is broadcast to the market executes a conversion at the old rate. The rate feed consumer has a 100ms advantage.

**User Impact:** The insider successfully executes a 50,000 USD conversion at the pre-change rate. The market receives the updated rate 100ms later.

**Recovery:** The internal rate feed is broadcast via Kafka with strict message ordering. No consumer is allowed to read ahead of the committed offset. A full audit trail tracks all rate views and conversions.

### Unauthorized access to FX admin rate override panel
**System Behavior:** An attacker modifies the USD/ETB rate to 60.00 (market rate is 57.50) through the admin panel. The attacker then executes an internal conversion at the manipulated rate.

**User Impact:** The market rate is artificially manipulated. Beza loses 2.50 ETB per USD on the trade. For a 100,000 USD trade, the loss is 250,000 ETB.

**Recovery:** Rate overrides require dual approval from two authorized users plus MFA. An audit log entry triggers a SIEM alert on any `RATE_OVERRIDE` event. The override requires supervisor authorization.

### Rate manipulation via multiple accounts — user creates 10 accounts to bypass the daily FX limit
**System Behavior:** The user creates 10 different accounts, each with a 25,000 USD daily FX limit. The user converts 25,000 USD from each account, totaling 250,000 USD.

**User Impact:** The user successfully bypasses the per-account limit and moves 250,000 USD out of ETB in a single day.

**Recovery:** The AML system detects that all 10 accounts share the same device fingerprint and IP address. The accounts are linked. Future FX operations are blocked until the user completes a compliance review.

### Timing attack on rate lock expiry — attacker submits conversion at the exact expiry time
**System Behavior:** The attacker monitors the rate lock expiry with sub-second precision. The attacker submits the conversion request at the exact moment the lock is about to expire.

**User Impact:** If the race condition is won by the attacker, the lock is accepted 10ms after expiry. The user gets the old, more favorable rate.

**Recovery:** The expiry check uses the database-level `expires_at` field with an atomic compare-and-set operation. This prevents the race condition by using a single database transaction for validation.

## 6. Business Logic Failures

### Rate lock expired before conversion — user locks 57.50 at 10:00:00, tries conversion at 10:00:45
**System Behavior:** The server checks `NOW() >= expires_at` and finds the lock expired. A new rate of 57.80 is fetched. The difference (0.52%) is within the 2% auto-approval threshold.

**User Impact:** The user sees "انتهت صلاحية سعر الصرف. السعر الجديد: 57.80. هل توافق؟" (The exchange rate has expired. The new rate is 57.80. Do you agree?)

**Recovery:** The user accepts the new rate. The system proactively refreshes the rate preview during the conversion flow to minimize the risk of expiry.

### Spread too wide during volatile market — normal spread 0.5%, current spread 3.0%
**System Behavior:** The risk engine checks the current spread against the maximum allowed spread threshold (2.0%). The spread of 3.0% exceeds the threshold and the conversion is rejected.

**User Impact:** The user sees "فروق الأسعار حالياً واسعة جداً. حاول مرة أخرى بعد 30 دقيقة" (The spreads are currently too wide. Please try again in 30 minutes.)

**Recovery:** The system re-checks the spread every 5 minutes. When market volatility subsides and the spread narrows below the threshold, conversions resume automatically.

### Directional limit hit — more USD bought than sold (net position exceeds risk limit)
**System Behavior:** The FX risk engine monitors the net position in each currency. When the net USD position exceeds the configured risk limit, further USD purchases are blocked.

**User Impact:** The user attempting to buy USD sees "تم الوصول إلى الحد الأقصى لصافي المركز. حاول شراء عملة أخرى" (The net position limit has been reached. Try buying a different currency pair.)

**Recovery:** The FX dealer manually hedges with the CBX to rebalance the position. The limit is reset after the hedge is executed.

### Weekend/holiday rate — no CBX rates available (Saturday in Ethiopia)
**System Behavior:** The FX service checks the CBX operating hours. Outside of business days (Saturday and Sunday in Ethiopia), the service returns an error indicating rates are unavailable.

**User Impact:** The user sees "أسعار الصرف متاحة فقط خلال أيام العمل الرسمية" (Exchange rates are available only during official business days.)

**Recovery:** The user can schedule the conversion for the next business day. The rate is locked at the opening rate on Monday morning with a guaranteed 30-second lock window.

### Tier limit for FX conversion exceeded — Tier 1 user tries 50,000 USD/month (limit 10,000 USD)
**System Behavior:** The `user_tier.monthly_fx_limit` check against the monthly aggregate of 50,000 USD exceeds the Tier 1 limit of 10,000 USD. The transaction is rejected.

**User Impact:** The user sees "الحد الشهري للتحويل هو $10,000 USD. قم بترقية حسابك" (The monthly FX limit is $10,000 USD. Please upgrade your account.)

**Recovery:** The user can complete an in-app KYC upgrade to Tier 2 (monthly limit of $100,000 USD) by uploading identity and source of funds documentation. The upgrade is processed within 24 hours.

## 7. Performance & Scalability Failures

### Sudden rate feed spike — 100 rate updates per second during market volatility
**System Behavior:** The CBX rate feed sends 100 updates per second during a volatile market period. The Kafka consumer processes 6,000 messages per minute. Consumer lag increases to 5,000 messages.

**User Impact:** Users see rates that are 5-10 seconds stale during high volatility. Rate lock requests may use slightly outdated rates.

**Recovery:** Consumer group is auto-scaled from 3 to 10 partitions. Lag is cleared within 30 seconds. A rate throttling mechanism batches updates to 10 per second for the display tier.

### High concurrency on rate locks — 1,000 concurrent lock requests
**System Behavior:** The rate lock service receives 1,000 concurrent requests. The database row-level locking on the `rate_locks` table causes contention. Lock acquisition time increases from 10ms to 500ms.

**User Impact:** Users experience 2-3 second delays when locking rates during peak periods.

**Recovery:** Rate locking is moved to Redis for faster atomic operations. The database is used only for persistence. Concurrent lock capacity increases to 5,000 requests per second.

### Memory leak in rate calculation engine — OOM after 4 hours of high load
**System Behavior:** The rate calculation service has a memory leak in the spread calculation algorithm. After 4 hours under high load, memory usage reaches 90% and the pod is OOM-killed.

**User Impact:** Rate calculations fail for 30 seconds while the pod restarts. Users see "خدمة أسعار الصرف غير متوفرة" (Exchange rate service unavailable.)

**Recovery:** Kubernetes auto-restarts the pod within 30 seconds. A hot standby pod serves requests during the restart. The memory leak is fixed in the next release.

## 8. Operational Failures

### Deployment rollback — v4.1.0 uses wrong spread formula, overcharging customers
**System Behavior:** The canary deployment detects a 25% increase in FX revenue within 3 minutes. The automated rollback is triggered.

**User Impact:** Approximately 200 customers were overcharged by an average of 0.5% on their conversions. Total overcharge: approximately 50,000 ETB.

**Recovery:** The rollback completes within 2 minutes. Overcharged customers are refunded automatically. The spread formula is corrected and tested.

### Configuration error — overnight margin set to 10% instead of 1%
**System Behavior:** A configuration change sets the overnight FX margin to 10% instead of the standard 1%. Customers are charged 10× the normal spread.

**User Impact:** 50 customers process conversions with a 10% spread during the 10-minute window. A 100,000 ETB conversion costs 10,000 ETB extra.

**Recovery:** A monitoring alert fires on the margin exceeding the 5% threshold. The configuration is reverted. Affected customers receive full refunds plus 20% apology credit.

### CBX rate feed disconnected — manual intervention delayed
**System Behavior:** The CBX rate feed disconnects at 2:00 AM EAT. The on-call engineer does not respond for 45 minutes. FX rates are frozen for 45 minutes.

**User Impact:** Users see stale rates. New conversions use rates that may not reflect market movements during the 45-minute window.

**Recovery:** The alerting severity is increased for CBX feed disconnections. An auto-reconnect mechanism is implemented with 30-second retry intervals. Escalation to secondary on-call after 15 minutes.

## 9. Recovery Time Objectives Summary

| Failure Category | Detection Time | Recovery Time | RPO | Maximum Impact |
|-----------------|----------------|---------------|-----|----------------|
| Network (transient) | < 1 second | < 30 seconds | 0 | Single rate lock delayed |
| Network (DNS outage) | 30 seconds | < 5 minutes | 0 | All FX operations blocked |
| Transaction failure | < 100ms | < 5 seconds | 0 | Single conversion failed |
| External dependency | < 10 seconds | < 30 minutes | 30 min stale rates | FX unavailable |
| Data inconsistency | < 5 minutes | < 1 hour | < 5 seconds | Rate lock discrepancy |
| Security incident | < 1 minute | < 4 hours | 0 | Conversion held |
| Business logic | < 1 hour | < 24 hours | 0 | Rate expired / limit hit |
| Performance degradation | < 1 minute | < 5 minutes | 0 | Slow rate locks |
| Operational (deployment) | < 5 minutes | < 10 minutes | < 1 minute | Incorrect spread applied |

## Changelog

| Date | Version | Changes |
|------|---------|---------|
| 2026-05-29 | 1.0 | Initial release — 7 categories covering network, transactions, external dependencies, data consistency, security, business logic, and performance failures for FX rate locking and conversion feature |

---

*Version: 1.0 | Last updated: 2026-05-29 | Owner: FX Engineering Team*
