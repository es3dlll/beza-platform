# 31. Loyalty — Failure Scenarios

> **Purpose:** Document every failure mode for the Beza Loyalty feature — points earning, redemption, tier progression, partner offers, and rewards catalog. Uses real SYP point values, Syrian partner names (Syriatel, MTN Syria), Arabic tier names (برونز، فضي، ذهبي، بلاتيني), and Arabic messaging only.

---

## 1. Network Failures

### Internet cut during points redemption after point debit but before reward credit
**System Behavior:** The user redeems 5,000 points (value 500 SYP). The points are debited from the user's balance. The reward issuance call fails due to the network disconnection. The transaction is marked `REDEMPTION_PENDING`.

**User Impact:** The user sees "تم خصم النقاط. في انتظار تأكيد الاستبدال" The reward is not yet issued.

**Recovery:** A background job retries the reward issuance every 30 seconds. When the connection is restored, the reward is credited. The user receives "تم استبدال 5,000 نقطة بنجاح"

### API timeout (>5s) during points balance query
**System Behavior:** The loyalty service times out while fetching the points balance. The app returns the last cached balance, which can be up to 2 minutes stale.

**User Impact:** The user sees "جاري تحميل رصيد النقاط..." followed by the cached balance. "آخر تحديث: منذ دقيقتين"

**Recovery:** The circuit breaker resets after 15 seconds and the next request fetches a fresh balance from the database.

### DNS failure for loyalty-api.beza.com
**System Behavior:** The loyalty section of the app is inaccessible because the DNS cannot resolve the API endpoint. All loyalty operations are blocked.

**User Impact:** The user sees "خدمة برنامج الولاء غير متوفرة حالياً"

**Recovery:** DNS failover is triggered. The app retries automatically. Core wallet functions remain available.

### Partner loyalty API timeout — Syriatel system unreachable for points transfer
**System Behavior:** The points transfer request to Syriatel's loyalty program times out. The transfer is marked `PARTNER_TRANSFER_PENDING`.

**User Impact:** The user sees "تحويل النقاط إلى سيريتل قيد المعالجة"

**Recovery:** Beza retries the partner API every 5 minutes for up to 2 hours. If the partner remains unreachable, the points are held in the Beza loyalty pool and the user is notified.

### Syrian Telecom SMS provider unavailable for points earning notification
**System Behavior:** The user earns 200 points from a transaction. The SMS notification cannot be delivered because the Syrian Telecom SMS gateway is unavailable.

**User Impact:** The user does not receive "تم إضافة 200 نقطة ولاء" via SMS. The push notification is delivered.

**Recovery:** The SMS is retried for up to 24 hours. The points history is always visible in the app.

## 2. Transaction Failures

### Insufficient points — user tries to redeem 20,000 points but has only 15,000 points
**System Behavior:** The pre-validation checks `points_balance >= redemption_cost`. The balance of 15,000 is below the 20,000 requirement. The redemption is rejected.

**User Impact:** The user sees "رصيد نقاط غير كافٍ. الرصيد المتاح: 15,000 نقطة"

**Recovery:** The UI filters the rewards catalog to show only rewards that are affordable with the current balance. The user can see how many more points they need to earn.

### Double redemption — user submits the same redemption request twice
**System Behavior:** The idempotency key is constructed from `user_id + reward_id + timestamp`. The second request returns `DUPLICATE_REDEMPTION`.

**User Impact:** The user sees "تم استبدال هذه النقاط مسبقاً"

**Recovery:** The first redemption is processed normally. The second request is silently discarded. Points are not double-debited.

### Points earning missed — transaction of 5,000 SYP should earn 50 points but system did not credit
**System Behavior:** The points accrual event is lost. The transaction is processed without the points being credited to the user's loyalty balance.

**User Impact:** The user earns 0 points instead of 50 points for their 5,000 SYP transaction.

**Recovery:** A batch reconciliation job runs every 6 hours to match transactions against points earnings. Missed earnings are detected and credited. The user is notified "تمت إضافة 50 نقطة مستحقة"

### Tier upgrade points threshold not recalculated — user earned enough for الفضي (Silver) but stuck at برونز (Bronze)
**System Behavior:** The user's total points balance crosses the Silver tier threshold of 3,000 points. The tier engine does not trigger the upgrade.

**User Impact:** The user stays at the Bronze tier with a 1x earning rate instead of the Silver tier's 1.2x rate.

**Recovery:** The tier engine runs on every points balance change. If it does not trigger, a scheduled batch job runs hourly to recalculate tiers. The user is upgraded retroactively with the higher earning rate applied.

### Partner points transfer reversal — transferred 10,000 points to Syriatel but partner rejects
**System Behavior:** Syriatel's loyalty system rejects the incoming points transfer. Syriatel returns a `REJECTED` status.

**User Impact:** The user sees "تم إلغاء تحويل النقاط. تم إعادة 10,000 نقطة إلى محفظة الولاء"

**Recovery:** The points are credited back to the user's Beza loyalty balance automatically. The user can try a different partner (MTN) or redeem directly on Beza.

### Points expiry miscalculation — 20,000 points expire 30 days early
**System Behavior:** The expiry date calculation logic has a bug that causes points to expire 30 days before the intended date. 20,000 points are removed prematurely.

**User Impact:** The user loses 20,000 points (value 2,000 SYP). "تم انتهاء صلاحية 20,000 نقطة"

**Recovery:** The points are restored to the user's balance. The expiry logic bug is fixed. All affected users receive 1,000 bonus points as compensation.

### Tier downgrade due to inactivity — user drops from ذهبي (Gold) to برونز (Bronze)
**System Behavior:** The inactivity policy triggers a tier downgrade for users who have not transacted in 12 months. The user drops three tiers from Gold to Bronze.

**User Impact:** The user's earning rate drops from 2x to 1x. Previously earned tier benefits (birthday bonus, priority support) are lost.

**Recovery:** A 30-day warning notification is sent before the downgrade. The user can make one transaction to reset the inactivity timer. The tier can be regained by meeting the points threshold again.

## 3. External Dependency Failures

### Partner rewards catalog API (Syriatel, MTN Syria) down
**System Behavior:** The rewards catalog is served from a cached snapshot that was last synced 24 hours ago. No new rewards are available.

**User Impact:** The user sees a potentially outdated catalog. "كتالوج المكافآت قد لا يعرض أحدث العروض"

**Recovery:** The catalog is refreshed when the partner API is restored. New offers are loaded into the catalog.

### Syrian Telecom SMS gateway unavailable for tier upgrade notification
**System Behavior:** The user is upgraded to the الفضي (Silver) tier. The celebration SMS cannot be sent because the Syrian Telecom SMS gateway is unavailable.

**User Impact:** The user does not receive "تهانينا! تمت ترقيتك إلى المستوى الفضي" via SMS.

**Recovery:** An in-app notification with a confetti animation is shown. The SMS is retried for up to 24 hours.

### Partner (retail store) POS system down for point earning at checkout
**System Behavior:** The retailer's POS system cannot send the transaction data to Beza for points accrual. The customer does not earn points for the purchase.

**User Impact:** The customer does not earn points for the in-store purchase. "نقاط الولاء لم تضف بسبب عطل في نظام المتجر"

**Recovery:** The partner sends a daily sales file for reconciliation. Beza matches the transactions and credits points retroactively.

### Syrialtel recharge voucher API timeout — user redeems 50,000 points for a 5,000 SYP recharge
**System Behavior:** The Syriatel recharge voucher generation API times out. The points are debited. The recharge code is not received.

**User Impact:** The user has points deducted but no recharge code. "رمز التعبئة قيد الإنشاء"

**Recovery:** Beza retries the voucher generation every 30 seconds for up to 10 minutes. On success, the code is sent. On failure, the points are reversed.

### Third-party promotion engine (2x points weekend) failed to activate
**System Behavior:** The promotional multiplier for the weekend promotion is not activated. Users earn 1x points instead of 2x points for the weekend.

**User Impact:** Users earn half the expected points for the promotional period. "عروض نهاية الأسبوع: تم إضافة النقاط الإضافية"

**Recovery:** The bonus points are credited retroactively within 48 hours. Users are notified "تمت إضافة نقاط المكافأة لعرض نهاية الأسبوع"

### Partner MTN Syria points conversion rate not updated
**System Behavior:** MTN Syria changes the points conversion rate from 100 Beza points = 50 MB data to 100 Beza points = 25 MB data. Beza's system uses the old rate for 3 days.

**User Impact:** Users redeem points expecting 50 MB but receive 25 MB. Users are disappointed.

**Recovery:** The conversion rate is updated. Affected users are compensated with bonus data. An automated rate sync with MTN is implemented.

## 4. Data Consistency Failures

### Points balance DB write fails — user earns 100 points but the write fails silently
**System Behavior:** The transaction is processed successfully. The points accrual write to the database fails. The user's points balance does not increase.

**User Impact:** The user sees the same balance. "لم تتم إضافة النقاط. اتصل بخدمة العملاء"

**Recovery:** A retry queue processes the failed accrual write. The points are credited within 5 minutes. The user is notified of the correction.

### Cache inconsistency — points balance shown as 25,000 but actual is 20,000
**System Behavior:** The cache entry is stale. The user initiates a redemption of 22,000 points based on the cached balance.

**User Impact:** The redemption is rejected at the database level. "الرصيد الفعلي: 20,000 نقطة"

**Recovery:** The cache is invalidated. The fresh balance is loaded from the database. Strong consistency is enforced for all point debit operations.

### Tier progression event lost — user crossed the threshold but tier upgrade was not published
**System Behavior:** The user's points balance crossed the Gold (الذهبي) tier threshold of 10,000 points. The Kafka event carrying the tier upgrade is lost. The user remains at the Silver (الفضي) tier.

**User Impact:** The user earns 1.2x points instead of the Gold tier's 2x rate. The user loses 40 points on their next 2,000 SYP spend.

**Recovery:** The hourly tier recalculation job detects the discrepancy. The user is upgraded retroactively. Any lost points from the lower earning rate are credited as bonus points.

### Dual-write between points ledger and transaction service fails partially
**System Behavior:** The transaction is recorded. The points write fails. The Saga pattern compensates by reversing the transaction.

**User Impact:** The user sees the transaction reversed. "تم إلغاء المعاملة. لم يتم خصم أي مبلغ"

**Recovery:** The compensation is completed within 2 seconds. The user retries the transaction. The points write is retried 3 times before the compensation is triggered.

### Points-to-SYP conversion rate table corrupted — 100 points = 5 SYP instead of 10 SYP
**System Behavior:** The conversion rate table has a configuration error. Users get half the value when converting points to SYP.

**User Impact:** The user redeems 20,000 points for 1,000 SYP instead of 2,000 SYP. The user receives 50% of the expected value.

**Recovery:** The reconciliation system detects the anomalous conversion rate. The rate is corrected. The affected user is compensated with the 1,000 SYP difference plus a 20% goodwill bonus.

### Arabic tier name corruption — Unicode encoding issue with tier names
**System Behavior:** The database encoding fails to store Arabic tier names properly. The tier name "بلاتيني" (Platinum) is displayed as garbled text.

**User Impact:** The user sees corrupted Arabic text for their tier name. "اسم المستوى غير مقروء"

**Recovery:** The encoding is corrected to UTF-8. The tier name is restored from the application configuration. A validation check prevents non-UTF-8 data from being stored.

## 5. Security Failures

### Fraud false positive — user redeeming 100,000 points (value 10,000 SYP) flagged as suspicious
**System Behavior:** The AML rules engine triggers on the large redemption combined with a new device login. The redemption is placed in `PENDING_REVIEW`.

**User Impact:** The user sees "استبدال النقاط قيد المراجعة"

**Recovery:** An SMS OTP is sent to the user. If the user confirms with the OTP, the redemption is released within 2 minutes.

### Fraud false negative — points theft via account takeover — 50,000 points redeemed by attacker
**System Behavior:** The attacker gains access to the user's account. The attacker redeems all 50,000 points for Syriatel recharge vouchers. The behavioral model does not flag the transaction.

**User Impact:** The legitimate user loses 50,000 points (value 5,000 SYP).

**Recovery:** The account takeover is detected by a change in the device fingerprint. The points are reversed. The recharge vouchers are cancelled. The user receives 10,000 bonus points as compensation.

### Unauthorized access to loyalty admin panel — points grant/revoke abused
**System Behavior:** An admin uses the panel to grant themselves 200,000 points. The admin redeems these points for Syriatel vouchers worth 20,000 SYP.

**User Impact:** Beza loses 20,000 SYP. Actual customer points are not affected.

**Recovery:** Points grants of more than 20,000 points require dual approval. The audit log alerts the SIEM system on abnormal grant patterns. A monthly reconciliation checks total points issued against total points redeemed.

### Points manipulation via API — attacker calls the points accrual API directly with inflated amounts
**System Behavior:** The attacker sends a direct API request to add points. The API validates the HMAC signature and merchant signature. The forged request is rejected.

**User Impact:** No impact. The attacker is blocked.

**Recovery:** All API requests require HMAC-signed payloads. Rate limiting protects against rapid requests. The attempted fraud is logged.

### Promotional code abuse — user generates unlimited 1,000-point bonus codes
**System Behavior:** The promo code generation system is not rate-limited. The user generates and redeems 5,000 promo codes, earning 5,000,000 points (value 500,000 SYP).

**User Impact:** Beza issues 5,000,000 points fraudulently. The user redeems for 500,000 SYP in Syriatel vouchers.

**Recovery:** Promo codes are made single-use per user. A maximum redemption limit per user is enforced. An anomaly detection system monitors the promo code generation rate.

### Tier status fraud — attacker spoofs Platinum (بلاتيني) tier for enhanced benefits
**System Behavior:** An attacker manipulates API requests to claim Platinum tier status. The tier validation engine checks the server-side tier assignment and rejects the spoofed claim.

**User Impact:** No impact. The attacker is blocked from receiving Platinum benefits.

**Recovery:** All tier calculations are performed server-side. Client-side tier claims are validated against the authoritative source. The attempted fraud is logged and the account is flagged.

## 6. Business Logic Failures

### Points expired during user inactivity — 6-month inactivity policy triggers mass expiry
**System Behavior:** The inactivity policy expires points for users who have not transacted in 6 months. 200,000 points across 500 users are expired, representing 20,000 SYP in value.

**User Impact:** Users lose their accumulated points. "تم انتهاء صلاحية نقاط ولائك بسبب عدم النشاط"

**Recovery:** A 30-day warning notification is sent before the expiry date. The user can perform any transaction to reset the inactivity timer. Expired points can be reinstated within 90 days through customer support.

### Tier downgrade on anniversary — user did not maintain minimum spend, drops from ذهبي (Gold) to فضي (Silver)
**System Behavior:** The annual tier review determines that the user did not maintain the minimum annual spend to keep Gold tier status. The user is downgraded to Silver.

**User Impact:** The user sees "تم تخفيض مستواك إلى الفضي" The earning rate drops from 2x to 1.2x.

**Recovery:** A 30-day notice is provided before the downgrade takes effect. The user can spend 10,000 SYP within the notice period to maintain the Gold tier.

### Reward out of stock — user redeems points for a popular item that is unavailable
**System Behavior:** The redemption is accepted at the time of request. The fulfillment system later returns `OUT_OF_STOCK`. The points have been debited.

**User Impact:** The user's points are deducted but the reward item is not delivered. "المكافأة غير متوفرة حالياً"

**Recovery:** The points are reversed to the user's balance. The user is offered an alternative reward plus a 10% bonus points as an apology. The inventory is synced hourly with the fulfillment system.

### Partner reward devalued — Syriatel reduced data bundle conversion rate
**System Behavior:** Syriatel reduces the conversion rate from 1,000 Beza points = 1 GB to 1,000 Beza points = 500 MB.

**User Impact:** Users redeeming 10,000 Beza points receive 5 GB instead of 10 GB.

**Recovery:** A 14-day notice is provided before the rate change takes effect. Conversions initiated before the change are honored at the old rate for 7 days after the announcement.

### Maximum annual points cap hit — user earned 300,000 points (cap 300,000)
**System Behavior:** The user reaches the maximum annual points earning cap of 300,000 points. No more points are earned for the remainder of the year.

**User Impact:** The user spends 100,000 SYP more but earns 0 points. "تم الوصول إلى الحد الأقصى السنوي للنقاط"

**Recovery:** The annual cap resets on January 1. The user is notified at 90%, 95%, and 100% of the cap. No workaround is available.

### Points multiplier not applied for partner-specific promotions
**System Behavior:** An MTN Syria promotion offers 3x points on all MTN-related transactions. The promotion is not applied because the merchant category code mapping is incomplete.

**User Impact:** Users earn 1x points instead of 3x points on their MTN transactions during the promotion period.

**Recovery:** The MCC mapping is corrected. Bonus points are credited retroactively within 48 hours. All affected users receive a notification of the correction.

## 7. Performance & Scalability Failures

### Points accrual spike — 8,000 transactions per second earning points simultaneously
**System Behavior:** The points accrual service handles 8,000 transactions per second during a 3x points promotional weekend. The Kafka consumer processes 8,000 messages per second. Consumer lag grows to 80,000 messages.

**User Impact:** Points are credited 2-3 minutes after the transaction instead of instantly. "جاري إضافة النقاط..." is shown.

**Recovery:** The Kafka consumer group is auto-scaled from 3 to 12 partitions. Points are credited in batch order. The accrual backlog is cleared within 3 minutes.

### Rewards catalog search — 30,000 concurrent users browsing rewards
**System Behavior:** The rewards catalog search service handles 30,000 concurrent users during a promotional campaign. The search index handles 400 queries per second.

**User Impact:** Search results take 5-8 seconds to load. "جاري تحميل كتالوج المكافآت..." takes longer than usual.

**Recovery:** The catalog is cached in Redis with 5-minute TTL. Search is moved to Elasticsearch with auto-scaling. Category pages are pre-rendered.

### Tier recalculation job — 500,000 accounts evaluated for tier changes
**System Behavior:** The monthly tier recalculation job evaluates 500,000 accounts. Each account requires checking annual spend, points balance, and activity. The job takes 5 hours.

**User Impact:** Tier changes take effect at different times during the 5-hour window. Some users see their tier updated in the morning, others in the afternoon.

**Recovery:** The recalculation is partitioned by user ID range (0-9999 per partition). 50 partitions run in parallel. Total processing time is reduced to 30 minutes. All tier changes take effect simultaneously.

### Syriatel voucher generation bottleneck — 10,000 concurrent voucher requests
**System Behavior:** During a popular promotion, 10,000 users simultaneously request Syriatel recharge vouchers. The Syriatel API can generate 200 vouchers per second.

**User Impact:** Users wait 30-60 seconds for their voucher code. "جاري إنشاء رمز التعبئة..." is shown.

**Recovery:** Voucher generation requests are queued and processed at the Syriatel API rate. Users are shown a progress indicator with an estimated wait time. The queue clears within 60 seconds.

## 8. Operational Failures

### Deployment rollback — v3.8.0 does not award points for mobile wallet transactions
**System Behavior:** The canary deployment detects a 15% decrease in points awarded (mobile wallet transactions excluded). The rollback is triggered.

**User Impact:** Approximately 3,000 mobile wallet transactions do not earn points during the 3-minute window. Total unearned points: 30,000.

**Recovery:** The rollback completes within 2 minutes. The missed points are credited retroactively. A reconciliation job identifies and corrects all affected transactions.

### Configuration error — points expiry set to 1 day instead of 365 days
**System Behavior:** A configuration change sets the points expiry period to 1 day. All users' points are expired after 24 hours. 50,000,000 points (value 5,000,000 SYP) are expired.

**User Impact:** All users see their points balance drop to zero. Panic and support calls spike to 15,000 per hour.

**Recovery:** The configuration is reverted within 3 minutes. The expired points are restored. A hard cap prevents the expiry period from being set to less than 30 days.

### Partner promotion configuration error — 10x points instead of 2x points
**System Behavior:** A Syriatel promotion is configured as 10x points instead of the intended 2x points. The promotion runs for 2 hours before detection.

**User Impact:** Users earn 10x points on all Syriatel transactions for 2 hours. A user spending 5,000 SYP on Syriatel recharge earns 500 points instead of 100 points.

**Recovery:** The promotion is corrected. Excess points are not clawed back (as a goodwill gesture). The total cost is approximately 1,000,000 SYP in extra points value. A promotion validation check is added.

### Tier name display issue — Arabic tier names reversed in UI
**System Behavior:** A UI deployment reverses the Arabic tier names display. Instead of برونز (Bronze), فضي (Silver), ذهبي (Gold), بلاتيني (Platinum), the names appear in reverse order or incorrectly mapped.

**User Impact:** Users see incorrect tier names. A Silver user sees "برونز" instead of "فضي" causing confusion.

**Recovery:** The UI is corrected within 30 minutes. The tier name mapping is validated in the deployment pipeline with a visual regression test.

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
| 2026-05-29 | 1.0 | Initial release — 7 categories covering network, transactions, external dependencies, data consistency, security, business logic, and performance failures for loyalty and rewards feature — Syria context only |

---

*Version: 1.0 | Last updated: 2026-05-29 | Owner: Loyalty Engineering Team*
