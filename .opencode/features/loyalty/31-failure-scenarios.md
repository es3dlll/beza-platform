# 31. Loyalty — Failure Scenarios

> **Purpose:** Document every failure mode for the Beza Loyalty feature — points earning, redemption, tier progression, partner offers, and rewards catalog. Uses real ETB point values, partner names, and Arabic/Amharic messaging.

---

## 1. Network Failures

### Internet cut during points redemption after point debit but before reward credit
**System Behavior:** The user redeems 5,000 points (value 500 ETB). The points are debited from the user's balance. The reward issuance call fails due to the network disconnection. The transaction is marked `REDEMPTION_PENDING`.

**User Impact:** The user sees "تم خصم النقاط. في انتظار تأكيد الاستبدال" (Points deducted. Awaiting redemption confirmation.) The reward is not yet issued.

**Recovery:** A background job retries the reward issuance every 30 seconds. When the connection is restored, the reward is credited. The user receives "تم استبدال 5,000 نقطة بنجاح" (5,000 points redeemed successfully.)

### API timeout (>5s) during points balance query
**System Behavior:** The loyalty service times out while fetching the points balance. The app returns the last cached balance, which can be up to 2 minutes stale.

**User Impact:** The user sees "جاري تحميل رصيد النقاط..." followed by the cached balance. "آخر تحديث: منذ دقيقتين" (Last updated: 2 minutes ago.)

**Recovery:** The circuit breaker resets after 15 seconds and the next request fetches a fresh balance from the database.

### DNS failure for loyalty-api.beza.et
**System Behavior:** The loyalty section of the app is inaccessible because the DNS cannot resolve the API endpoint. All loyalty operations are blocked.

**User Impact:** The user sees "خدمة برنامج الولاء غير متوفرة حالياً" (Loyalty program service is currently unavailable.)

**Recovery:** Route53 failover is triggered. The app retries automatically. Core wallet functions remain available.

### Partner loyalty API timeout — partner (e.g., Ethiopian Airlines) system unreachable for points transfer
**System Behavior:** The points transfer request to the partner's loyalty program (e.g., ShebaMiles) times out. The transfer is marked `PARTNER_TRANSFER_PENDING`.

**User Impact:** The user sees "تحويل النقاط إلى شريك الولاء قيد المعالجة" (Points transfer to the loyalty partner is being processed.)

**Recovery:** Beza retries the partner API every 5 minutes for up to 2 hours. If the partner remains unreachable, the points are held in the Beza loyalty pool and the user is notified.

### SMS provider unavailable for points earning notification
**System Behavior:** The user earns 100 points from a transaction. The SMS notification cannot be delivered because the SMS provider is unavailable.

**User Impact:** The user does not receive "تم إضافة 100 نقطة ولاء" (100 loyalty points added.) via SMS. The push notification is delivered.

**Recovery:** The SMS is retried for up to 24 hours. The points history is always visible in the app.

## 2. Transaction Failures

### Insufficient points — user tries to redeem 10,000 points but has only 7,500 points
**System Behavior:** The pre-validation checks `points_balance >= redemption_cost`. The balance of 7,500 is below the 10,000 requirement. The redemption is rejected.

**User Impact:** The user sees "رصيد نقاط غير كافٍ. الرصيد المتاح: 7,500 نقطة" (Insufficient points. Available: 7,500 points.)

**Recovery:** The UI filters the rewards catalog to show only rewards that are affordable with the current balance. The user can see how many more points they need to earn.

### Double redemption — user submits the same redemption request twice
**System Behavior:** The idempotency key is constructed from `user_id + reward_id + timestamp`. The second request returns `DUPLICATE_REDEMPTION`.

**User Impact:** The user sees "تم استبدال هذه النقاط مسبقاً" (These points have already been redeemed.)

**Recovery:** The first redemption is processed normally. The second request is silently discarded. Points are not double-debited.

### Points earning missed — transaction of 2,500 ETB should earn 25 points but system did not credit
**System Behavior:** The points accrual event is lost. The transaction is processed without the points being credited to the user's loyalty balance.

**User Impact:** The user earns 0 points instead of 25 points for their 2,500 ETB transaction.

**Recovery:** A batch reconciliation job runs every 6 hours to match transactions against points earnings. Missed earnings are detected and credited. The user is notified "تمت إضافة 25 نقطة مستحقة" (25 owed points have been added.)

### Tier upgrade points threshold not recalculated — user earned enough for Gold but stuck at Silver
**System Behavior:** The user's total points balance crosses the Gold tier threshold of 5,000 points. The tier engine does not trigger the upgrade.

**User Impact:** The user stays at the Silver tier with a 1x earning rate instead of the Gold tier's 1.5x rate.

**Recovery:** The tier engine runs on every points balance change. If it does not trigger, a scheduled batch job runs hourly to recalculate tiers. The user is upgraded retroactively with the higher earning rate applied.

### Partner points transfer reversal — transferred 20,000 points to Ethiopian Airlines but partner rejects
**System Behavior:** The partner's loyalty system rejects the incoming points transfer. The partner returns a `REJECTED` status.

**User Impact:** The user sees "تم إلغاء تحويل النقاط. تم إعادة 20,000 نقطة إلى محفظة الولاء" (Points transfer cancelled. 20,000 points returned to the loyalty wallet.)

**Recovery:** The points are credited back to the user's Beza loyalty balance automatically. The user can try a different partner or redeem directly on Beza.

### Points expiry miscalculation — 15,000 points expire 30 days early
**System Behavior:** The expiry date calculation logic has a bug that causes points to expire 30 days before the intended date. 15,000 points are removed prematurely.

**User Impact:** The user loses 15,000 points (value 1,500 ETB). "تم انتهاء صلاحية 15,000 نقطة" (15,000 points have expired.)

**Recovery:** The points are restored to the user's balance. The expiry logic bug is fixed. All affected users receive 500 bonus points as compensation.

## 3. External Dependency Failures

### Partner rewards catalog API (Ethiopian Airlines ShebaMiles, Dashen Bank) down
**System Behavior:** The rewards catalog is served from a cached snapshot that was last synced 24 hours ago. No new rewards are available.

**User Impact:** The user sees a potentially outdated catalog. "كتالوج المكافآت قد لا يعرض أحدث العروض" (The rewards catalog may not show the latest offers.)

**Recovery:** The catalog is refreshed when the partner API is restored. New offers are loaded into the catalog.

### SMS provider (InfoBip) unavailable for tier upgrade notification
**System Behavior:** The user is upgraded to the Gold tier. The celebration SMS cannot be sent because the SMS provider is unavailable.

**User Impact:** The user does not receive "تهانينا! تمت ترقيتك إلى المستوى الذهبي" (Congratulations! You have been upgraded to the Gold tier.) via SMS.

**Recovery:** An in-app notification with a confetti animation is shown. The SMS is retried for up to 24 hours.

### Partner (retail store) POS system down for point earning at checkout
**System Behavior:** The retailer's POS system cannot send the transaction data to Beza for points accrual. The customer does not earn points for the purchase.

**User Impact:** The customer does not earn points for the in-store purchase. "نقاط الولاء لم تضف بسبب عطل في نظام المتجر" (Loyalty points were not added due to a store system issue.)

**Recovery:** The partner sends a daily sales file for reconciliation. Beza matches the transactions and credits points retroactively.

### Gift card provider API timeout — user redeems 50,000 points for a 500 ETB gift card
**System Behavior:** The gift card generation API times out. The points are debited. The gift card code is not received.

**User Impact:** The user has points deducted but no gift card. "بطاقة الهدايا قيد الإنشاء" (Gift card is being generated.)

**Recovery:** Beza retries the gift card generation every 30 seconds for up to 10 minutes. On success, the code is sent. On failure, the points are reversed.

### Third-party promotion engine (2x points weekend) failed to activate
**System Behavior:** The promotional multiplier for the weekend promotion is not activated. Users earn 1x points instead of 2x points for the weekend.

**User Impact:** Users earn half the expected points for the promotional period. "عروض نهاية الأسبوع: تم إضافة النقاط الإضافية" (Weekend promotion: bonus points will be added later.)

**Recovery:** The bonus points are credited retroactively within 48 hours. Users are notified "تمت إضافة نقاط المكافأة لعرض نهاية الأسبوع" (Weekend promotion bonus points have been added.)

## 4. Data Consistency Failures

### Points balance DB write fails — user earns 50 points but the write fails silently
**System Behavior:** The transaction is processed successfully. The points accrual write to the database fails. The user's points balance does not increase.

**User Impact:** The user sees the same balance. "لم تتم إضافة النقاط. اتصل بخدمة العملاء" (Points were not added. Contact customer service.)

**Recovery:** A retry queue processes the failed accrual write. The points are credited within 5 minutes. The user is notified of the correction.

### Cache inconsistency — points balance shown as 12,000 but actual is 10,000
**System Behavior:** The cache entry is stale. The user initiates a redemption of 11,000 points based on the cached balance.

**User Impact:** The redemption is rejected at the database level. "الرصيد الفعلي: 10,000 نقطة" (Actual balance: 10,000 points.)

**Recovery:** The cache is invalidated. The fresh balance is loaded from the database. Strong consistency is enforced for all point debit operations.

### Tier progression event lost — user crossed the threshold but tier upgrade was not published
**System Behavior:** The user's points balance crossed the Gold tier threshold. The Kafka event carrying the tier upgrade is lost. The user remains at the Silver tier.

**User Impact:** The user earns 1x points instead of the Gold tier's 1.5x rate. The user loses 50 points on their next 1,000 ETB spend.

**Recovery:** The hourly tier recalculation job detects the discrepancy. The user is upgraded retroactively. Any lost points from the lower earning rate are credited as bonus points.

### Dual-write between points ledger and transaction service fails partially
**System Behavior:** The transaction is recorded. The points write fails. The Saga pattern compensates by reversing the transaction.

**User Impact:** The user sees the transaction reversed. "تم إلغاء المعاملة. لم يتم خصم أي مبلغ" (Transaction cancelled. No amount was deducted.)

**Recovery:** The compensation is completed within 2 seconds. The user retries the transaction. The points write is retried 3 times before the compensation is triggered.

### Points-to-ETB conversion rate table corrupted — 100 points = 5 ETB instead of 10 ETB
**System Behavior:** The conversion rate table has a configuration error. Users get half the value when converting points to ETB.

**User Impact:** The user redeems 10,000 points for 500 ETB instead of 1,000 ETB. The user receives 50% of the expected value.

**Recovery:** The reconciliation system detects the anomalous conversion rate. The rate is corrected. The affected user is compensated with the 500 ETB difference plus a 20% goodwill bonus.

## 5. Security Failures

### Fraud false positive — user redeeming 50,000 points (value 5,000 ETB) flagged as suspicious
**System Behavior:** The AML rules engine triggers on the large redemption combined with a new device login. The redemption is placed in `PENDING_REVIEW`.

**User Impact:** The user sees "استبدال النقاط قيد المراجعة" (Points redemption under review.)

**Recovery:** An SMS OTP is sent to the user. If the user confirms with the OTP, the redemption is released within 2 minutes.

### Fraud false negative — points theft via account takeover — 25,000 points redeemed by attacker
**System Behavior:** The attacker gains access to the user's account. The attacker redeems all 25,000 points for gift cards. The behavioral model does not flag the transaction.

**User Impact:** The legitimate user loses 25,000 points (value 2,500 ETB).

**Recovery:** The account takeover is detected by a change in the device fingerprint. The points are reversed. The gift cards are cancelled. The user receives 5,000 bonus points as compensation.

### Unauthorized access to loyalty admin panel — points grant/revoke abused
**System Behavior:** An admin uses the panel to grant themselves 100,000 points. The admin redeems these points for gift cards worth 10,000 ETB.

**User Impact:** Beza loses 10,000 ETB. Actual customer points are not affected.

**Recovery:** Points grants of more than 10,000 points require dual approval. The audit log alerts the SIEM system on abnormal grant patterns. A monthly reconciliation checks total points issued against total points redeemed.

### Points manipulation via API — attacker calls the points accrual API directly with inflated amounts
**System Behavior:** The attacker sends a direct API request to add points. The API validates the HMAC signature and merchant signature. The forged request is rejected.

**User Impact:** No impact. The attacker is blocked.

**Recovery:** All API requests require HMAC-signed payloads. Rate limiting protects against rapid requests. The attempted fraud is logged.

### Promotional code abuse — user generates unlimited 500-point bonus codes
**System Behavior:** The promo code generation system is not rate-limited. The user generates and redeems 10,000 promo codes, earning 5,000,000 points (value 500,000 ETB).

**User Impact:** Beza issues 5,000,000 points fraudulently. The user redeems for 500,000 ETB in rewards.

**Recovery:** Promo codes are made single-use per user. A maximum redemption limit per user is enforced. An anomaly detection system monitors the promo code generation rate.

## 6. Business Logic Failures

### Points expired during user inactivity — 6-month inactivity policy triggers mass expiry
**System Behavior:** The inactivity policy expires points for users who have not transacted in 6 months. 100,000 points across 500 users are expired, representing 10,000 ETB in value.

**User Impact:** Users lose their accumulated points. "تم انتهاء صلاحية نقاط ولائك بسبب عدم النشاط" (Your loyalty points have expired due to inactivity.)

**Recovery:** A 30-day warning notification is sent before the expiry date. The user can perform any transaction to reset the inactivity timer. Expired points can be reinstated within 90 days through customer support.

### Tier downgrade on anniversary — user did not maintain minimum spend, drops from Gold to Silver
**System Behavior:** The annual tier review determines that the user did not maintain the minimum annual spend to keep Gold tier status. The user is downgraded to Silver.

**User Impact:** The user sees "تم تخفيض مستواك إلى الفضي" (Your tier has been downgraded to Silver.) The earning rate drops from 1.5x to 1x.

**Recovery:** A 30-day notice is provided before the downgrade takes effect. The user can spend 5,000 ETB within the notice period to maintain the Gold tier.

### Reward out of stock — user redeems points for a popular item that is unavailable
**System Behavior:** The redemption is accepted at the time of request. The fulfillment system later returns `OUT_OF_STOCK`. The points have been debited.

**User Impact:** The user's points are deducted but the reward item is not delivered. "المكافأة غير متوفرة حالياً" (Reward is currently unavailable.)

**Recovery:** The points are reversed to the user's balance. The user is offered an alternative reward plus a 10% bonus points as an apology. The inventory is synced hourly with the fulfillment system.

### Partner reward devalued — Ethiopian Airlines reduced ShebaMiles conversion rate
**System Behavior:** The partner reduces the conversion rate from 1,000 Beza points = 1,000 ShebaMiles to 1,000 Beza points = 500 ShebaMiles.

**User Impact:** Users transferring 50,000 Beza points receive 25,000 ShebaMiles instead of 50,000.

**Recovery:** A 14-day notice is provided before the rate change takes effect. Conversions initiated before the change are honored at the old rate for 7 days after the announcement.

### Maximum annual points cap hit — user earned 200,000 points (cap 200,000)
**System Behavior:** The user reaches the maximum annual points earning cap of 200,000 points. No more points are earned for the remainder of the year.

**User Impact:** The user spends 50,000 ETB more but earns 0 points. "تم الوصول إلى الحد الأقصى السنوي للنقاط" (Annual points cap reached.)

**Recovery:** The annual cap resets on January 1. The user is notified at 90%, 95%, and 100% of the cap. No workaround is available.

## 7. Performance & Scalability Failures

### Points accrual spike — 5,000 transactions per second earning points simultaneously
**System Behavior:** The points accrual service handles 5,000 transactions per second during a 2x points promotional weekend. The Kafka consumer processes 5,000 messages per second. Consumer lag grows to 50,000 messages.

**User Impact:** Points are credited 1-2 minutes after the transaction instead of instantly. "جاري إضافة النقاط..." (Points being added...) is shown.

**Recovery:** The Kafka consumer group is auto-scaled from 3 to 10 partitions. Points are credited in batch order. The accrual backlog is cleared within 2 minutes.

### Rewards catalog search — 50,000 concurrent users browsing rewards
**System Behavior:** The rewards catalog search service handles 50,000 concurrent users during a promotional campaign. The search index handles 500 queries per second.

**User Impact:** Search results take 5-8 seconds to load. "جاري تحميل كتالوج المكافآت..." (Loading rewards catalog...) takes longer than usual.

**Recovery:** The catalog is cached in Redis with 5-minute TTL. Search is moved to Elasticsearch with auto-scaling. Category pages are pre-rendered.

### Tier recalculation job — 1,000,000 accounts evaluated for tier changes
**System Behavior:** The monthly tier recalculation job evaluates 1,000,000 accounts. Each account requires checking annual spend, points balance, and activity. The job takes 6 hours.

**User Impact:** Tier changes take effect at different times during the 6-hour window. Some users see their tier updated in the morning, others in the afternoon.

**Recovery:** The recalculation is partitioned by user ID range (0-9999 per partition). 100 partitions run in parallel. Total processing time is reduced to 30 minutes. All tier changes take effect simultaneously.

## 8. Operational Failures

### Deployment rollback — v3.8.0 does not award points for mobile money transactions
**System Behavior:** The canary deployment detects a 15% decrease in points awarded (mobile money transactions excluded). The rollback is triggered.

**User Impact:** Approximately 2,000 mobile money transactions do not earn points during the 3-minute window. Total unearned points: 20,000.

**Recovery:** The rollback completes within 2 minutes. The missed points are credited retroactively. A reconciliation job identifies and corrects all affected transactions.

### Configuration error — points expiry set to 1 day instead of 365 days
**System Behavior:** A configuration change sets the points expiry period to 1 day. All users' points are expired after 24 hours. 10,000,000 points (value 1,000,000 ETB) are expired.

**User Impact:** All users see their points balance drop to zero. Panic and support calls spike to 10,000 per hour.

**Recovery:** The configuration is reverted within 3 minutes. The expired points are restored. A hard cap prevents the expiry period from being set to less than 30 days.

### Partner promotion configuration error — 10x points instead of 2x points
**System Behavior:** A partner promotion is configured as 10x points instead of the intended 2x points. The promotion runs for 2 hours before detection.

**User Impact:** Users earn 10x points on all partner transactions for 2 hours. A user spending 10,000 ETB earns 1,000 points instead of 200 points.

**Recovery:** The promotion is corrected. Excess points are not clawed back (as a goodwill gesture). The total cost is approximately 500,000 ETB in extra points value. A promotion validation check is added.

## 9. Recovery Time Objectives Summary

| Failure Category | Detection Time | Recovery Time | RPO | Maximum Impact |
|-----------------|----------------|---------------|-----|----------------|
| Network (transient) | < 1 second | < 30 seconds | 0 | Single redemption delayed |
| Network (DNS outage) | 30 seconds | < 5 minutes | 0 | All loyalty ops blocked |
| Transaction failure | < 100ms | < 5 seconds | 0 | Points accrual/redemption |
| External dependency | < 10 seconds | < 15 minutes | 0 | Partner catalog unavailable |
| Data inconsistency | < 5 minutes | < 1 hour | < 5 seconds | Points balance discrepancy |
| Security incident | < 1 minute | < 4 hours | 0 | Redemption held for review |
| Business logic | < 1 hour | < 24 hours | 0 | Points expiry / tier issue |
| Performance degradation | < 1 minute | < 5 minutes | 0 | Slow catalog loading |
| Operational (deployment) | < 5 minutes | < 10 minutes | < 1 minute | Points not awarded |

## Changelog

| Date | Version | Changes |
|------|---------|---------|
| 2026-05-29 | 1.0 | Initial release — 7 categories covering network, transactions, external dependencies, data consistency, security, business logic, and performance failures for loyalty and rewards feature |

---

*Version: 1.0 | Last updated: 2026-05-29 | Owner: Loyalty Engineering Team*
