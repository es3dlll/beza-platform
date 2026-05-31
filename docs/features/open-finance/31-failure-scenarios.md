# 31. Open Finance — Failure Scenarios

> **Purpose:** Document every failure mode for the Beza Open Finance feature — open banking APIs, third-party provider (TPP) integrations, consent management, data sharing, and account aggregation. Uses Arabic messaging only. Syria context: Syrian banks (Bank of Syria and Overseas, Real Estate Bank, Banque BEMO Saudi Fransi, Industrial Bank), Syrian fintech partners, Syrian National ID (الرقم الوطني), beza.com/api endpoints.

---

## 1. Network Failures

### Internet cut during account data sharing — user authorized TPP but data transfer interrupted
**System Behavior:** The consent is granted. The data package is partially transmitted. The third-party provider (TPP) receives incomplete financial data.

**User Impact:** The user believes the data sharing is complete. The TPP has partial data. "مشاركة البيانات قيد التقدم"

**Recovery:** The data transfer resumes from the point of interruption when the connection is restored. The full data package is sent. The TPP receives a completion notification.

### API timeout (>5s) for TPP authentication request
**System Behavior:** The TPP's authentication request hits the Beza Open Finance API. The request times out after 5 seconds. The TPP receives an HTTP 504 Gateway Timeout.

**User Impact:** The user is redirected to the TPP's error page. "خدمة المصادقة غير متوفرة"

**Recovery:** The TPP retries the authentication request with exponential backoff. The circuit breaker opens for 15 seconds to protect the Beza authentication service.

### DNS failure for open-api.beza.com
**System Behavior:** All Open Finance API endpoints are unreachable. Third-party providers cannot integrate with Beza. All third-party apps show errors.

**User Impact:** Users see errors in their third-party financial apps. "بيانات Beza غير متوفرة"

**Recovery:** DNS failover to the secondary region is triggered. TPPs automatically retry with exponential backoff.

### WebSocket disconnect during real-time account balance streaming to TPP
**System Behavior:** The TPP's WebSocket connection drops. The TPP receives stale balance data until the WebSocket reconnects.

**User Impact:** The user's budget app shows an old balance. "آخر تحديث: منذ 5 دقائق"

**Recovery:** The WebSocket reconnects automatically. The server sends the latest balance snapshot. No balance data is lost.

### Network partition between Beza Open Finance gateway and core banking system
**System Behavior:** The Open Finance gateway cannot fetch account data from the core banking system because the network is partitioned. TPP requests for account data fail.

**User Impact:** The TPP receives errors. "خدمة الحسابات غير متوفرة"

**Recovery:** The gateway serves cached account data (with valid consent) for up to 5 minutes. After the cache TTL expires, errors are returned to the TPP.

### Syrian Telecom network outage affects TPP callback URLs
**System Behavior:** The Syrian Telecom network experiences a country-wide outage. TPP callback URLs that use Syrian Telecom infrastructure are unreachable. Beza cannot send webhook notifications to TPPs.

**User Impact:** TPPs do not receive real-time updates. Data synchronization is delayed.

**Recovery:** TPPs fall back to polling the Beza API every 15 minutes. Webhook notifications are queued and replayed when the network is restored.

## 2. Transaction Failures

### Consent expired — TPP tries to access data using an expired consent token
**System Behavior:** The token validation fails because the consent `expires_at` timestamp has passed. The API returns `CONSENT_EXPIRED`.

**User Impact:** The TPP shows "انتهت صلاحية الإذن. يرجى إعادة التفويض"

**Recovery:** The user re-authorizes the consent through the Beza app. A new token is issued with a fresh expiry date.

### TPP rate limit exceeded — TPP makes 200 requests per minute (limit is 150)
**System Behavior:** The API gateway enforces the rate limit. The 151st request in the sliding 60-second window returns HTTP 429 `TOO_MANY_REQUESTS`.

**User Impact:** The TPP's service is degraded. The user's app shows "مؤقتاً غير متاح"

**Recovery:** The TPP must back off based on the `Retry-After` header, which specifies 60 seconds. The rate limit resets every minute.

### Duplicate consent request — user grants the same access twice
**System Behavior:** The idempotency key on `consent_request_id` detects the duplicate. The second request returns the existing consent reference.

**User Impact:** The user sees the second consent in the list. "تم منح هذا الإذن مسبقاً"

**Recovery:** The duplicate is silently deduplicated. The user sees a single consent entry in the consent management screen.

### Consent revocation mid-data-stream — user revokes while TPP is reading data
**System Behavior:** The data access token is invalidated mid-session. The TPP receives `CONSENT_REVOKED` on the next API call.

**User Impact:** The TPP receives partial data. The user's intent to revoke is honored immediately.

**Recovery:** The TPP must stop using the data immediately. Already-accessed data can be retained for up to 48 hours for processing per Beza's data retention policy.

### Scope escalation — TPP requests more permissions than originally displayed
**System Behavior:** The consent screen compares the requested scope against the initially displayed scope. If the TPP added scopes, the user is shown a warning.

**User Impact:** The user sees "يطلب التطبيق أذونات إضافية: التحويل من الحساب"

**Recovery:** The user can accept or reject the expanded scope. If rejected, the consent is not granted. If accepted, the full scope is recorded.

### Data format incompatibility — Beza returns ISO 20022 but TPP expects OFX
**System Behavior:** The content negotiation via the `Accept` header determines that the TPP cannot accept any format Beza supports. The API returns HTTP 406 `NOT_ACCEPTABLE`.

**User Impact:** The TPP cannot parse the response. The integration is broken. "تنسيق البيانات غير متوافق"

**Recovery:** The TPP updates the client to use a supported format (JSON or ISO 20022 XML). Beza provides a format migration guide.

### Invalid redirect URI — TPP callback URL does not match registered URI
**System Behavior:** The TPP's callback URL at runtime differs from the one registered during onboarding. The OAuth authorization flow is rejected.

**User Impact:** The user is redirected to an error page. "عنوان إعادة التوجيه غير متطابق مع العنوان المسجل"

**Recovery:** The TPP must register all valid callback URIs. A wildcard is not allowed. The TPP support team assists with updating the registered URIs.

## 3. External Dependency Failures

### Partner bank (Bank of Syria and Overseas) open banking API down
**System Behavior:** Beza cannot aggregate account data from the partner bank because the bank's open banking API is down.

**User Impact:** The user cannot see their Bank of Syria and Overseas account balance in the Beza Open Finance dashboard. "بيانات مصرف سورية والمهجر غير متوفرة حالياً"

**Recovery:** Beza shows cached data (up to 24 hours old) with a disclaimer. The partner bank API status is monitored, and the service resumes when the API is restored.

### Syrian National ID verification API timeout during TPP onboarding
**System Behavior:** The TPP's identity verification during registration fails because the National ID API from the Ministry of Interior times out. The TPP cannot be registered as a provider.

**User Impact:** The TPP's development is blocked. "التحقق من هوية مزود الخدمة غير متاح"

**Recovery:** A manual verification process is triggered. The TPP submits documents for manual review. The review is completed within the 48-hour SLA.

### Certificate authority (CA) revocation list unavailable
**System Behavior:** The Open Finance gateway cannot verify the TPP's QWAC (Qualified Website Authentication Certificate) because the CA's certificate revocation list (CRL) is unavailable.

**User Impact:** The TPP's connection is rejected because the certificate status cannot be verified. "تعذر التحقق من شهادة الأمان لمزود الخدمة"

**Recovery:** The CRL check result is cached for 1 hour. If the CA is still unavailable after 1 hour, a manual override is available with security team approval.

### Syrian Telecom SMS gateway unavailable for consent confirmation SMS
**System Behavior:** The consent is granted. The confirmation SMS cannot be sent because the Syrian Telecom SMS gateway is unavailable.

**User Impact:** The user does not receive "تم منح الإذن لتطبيق XYZ" via SMS.

**Recovery:** An in-app notification is sent immediately. The SMS is retried for up to 24 hours. The consent is still valid without the SMS confirmation.

### CBS Open Finance registry API down
**System Behavior:** The CBS Open Finance registry, which lists all registered TPPs, is unavailable. Beza cannot verify a new TPP's registration status.

**User Impact:** New TPP onboarding is blocked. "سجل مزودي الخدمة المالية غير متاح"

**Recovery:** The registry check result is cached for 24 hours. The TPP is verified manually until the registry is restored.

### Real Estate Bank open banking API deprecated without notice
**System Behavior:** The Real Estate Bank deprecates their v1 open banking API and migrates to v2. Beza's integration still targets the v1 endpoints. All API calls fail.

**User Impact:** Users with Real Estate Bank accounts cannot see those accounts in the Open Finance dashboard.

**Recovery:** Beza updates to the v2 API endpoints. A monitoring system is added to detect API deprecation headers. A grace period of 30 days is negotiated with the bank.

### Banque BEMO Saudi Fransi open banking API certificate rotation
**System Behavior:** Banque BEMO Saudi Fransi rotates their API TLS certificate without prior notice. Beza's certificate pinning fails. All API calls to the bank are rejected.

**User Impact:** Users with BEMO accounts cannot access their account data through Open Finance.

**Recovery:** The new certificate is obtained and updated in Beza's trust store. Certificate pinning is replaced with regular CA-based validation. A certificate change notification mechanism is established with the bank.

## 4. Data Consistency Failures

### Consent state inconsistency — user revoked consent but TPP still has a valid token
**System Behavior:** The user revokes consent in the Beza app. The Redis cache is updated immediately. The TPP's cached token is still valid for up to 15 minutes (the token TTL).

**User Impact:** The TPP can still access data for up to 15 minutes after revocation. This is an authorized security gap.

**Recovery:** The token TTL is reduced to 15 minutes to limit the exposure window. Token revocation is broadcast via WebSocket to connected TPPs immediately.

### Cache inconsistency — user's account list cached without a new account opened yesterday
**System Behavior:** The user opened a new Bank of Syria and Overseas account yesterday. The account aggregation cache (24-hour TTL) does not include the new account.

**User Impact:** The user cannot see the new account in the Open Finance dashboard. "قد لا تعكس الحسابات أحدث الإضافات"

**Recovery:** A force refresh button triggers an immediate fresh aggregation. The cache is also invalidated when a new account is opened event is received.

### Account data event lost — transaction posted but not pushed to TPP
**System Behavior:** A new transaction is posted to the user's account. The push event to the TPP is lost. The TPP has stale transaction data.

**User Impact:** The user's budget app misses a transaction. The budget categorization is off by 10,000 SYP.

**Recovery:** The TPP polls for changes every 30 minutes. The missed event is captured on the next poll. The dead-letter queue replays the event within 5 minutes.

### Dual-write between consent DB and token cache fails partially
**System Behavior:** The consent is recorded in the database. The token cache write fails. The user sees the consent as active but the TPP cannot use it because the token is not in the cache.

**User Impact:** The user thinks the data sharing is active. The TPP gets `TOKEN_NOT_FOUND`.

**Recovery:** A consistency check runs every 5 minutes. Missing tokens are regenerated from the consent database.

### Data aggregation deduplication failure — same transaction appears twice
**System Behavior:** Two data sources (the bank API and an SMS parser) both report the same transaction. The aggregation engine does not deduplicate.

**User Impact:** The user sees a duplicate transaction in their aggregated dashboard. The balance is off by 10,000 SYP.

**Recovery:** A fuzzy matching algorithm detects the duplicate based on amount + date + reference. The duplicate is flagged and hidden. A manual correction option is available.

### TPP webhook delivery failure — Beza sends webhook but TPP endpoint is down
**System Behavior:** Beza sends a webhook notification for a data change event. The TPP's webhook endpoint returns HTTP 500. Beza retries 3 times, then marks the delivery as failed.

**User Impact:** The TPP does not receive the real-time update. The data change is only picked up on the next TPP poll cycle.

**Recovery:** The webhook is retried with exponential backoff (5min, 15min, 1hr). After all retries fail, the TPP is notified through the dashboard. The TPP can manually trigger a data refresh.

## 5. Security Failures

### Fraud false positive — user connecting a legitimate TPP financial advisor flagged
**System Behavior:** The AML rules engine triggers on a new TPP connection combined with a large account balance. The TPP connection is placed in review.

**User Impact:** The user sees "اتصال مزود الخدمة قيد المراجعة"

**Recovery:** The compliance team reviews the TPP's credentials within 4 hours. If legitimate, the TPP is approved and the user is notified.

### Fraud false negative — malicious TPP approved with fake credentials
**System Behavior:** The TPP registration passes the automated verification. The TPP is actually a data scraper with fraudulent credentials.

**User Impact:** 5,000 users' financial data is accessed by the malicious TPP. Personal financial information is exposed.

**Recovery:** The TPP is monitored post-registration. Abnormal data access patterns (downloading full transaction histories for all users) are detected within 7 days. The TPP is suspended. All affected users are notified.

### Unauthorized data access through API — attacker uses stolen TPP credentials
**System Behavior:** A TPP's `client_id` and `client_secret` are stolen. The attacker impersonates the TPP and accesses user data.

**User Impact:** 20,000 users' account balances are exposed through the compromised TPP.

**Recovery:** The compromised TPP credentials are revoked. The TPP is notified. All user consents for that TPP are suspended. New credentials are issued.

### Consent screen phishing — fake consent page mimics Beza OAuth screen
**System Behavior:** A phishing site shows a fake Beza OAuth consent screen. The user enters their Beza credentials on the phishing site.

**User Impact:** The user's Beza account is compromised. Funds could be transferred.

**Recovery:** The phishing site is reported and taken down. The user's account is frozen. A mandatory password reset is enforced. An education campaign is initiated.

### Redirect URI manipulation — TPP uses a different redirect URI than registered
**System Behavior:** The TPP registers the redirect URI `https://myapp.com/callback` but uses `https://attacker.com/callback` at authentication time.

**User Impact:** The redirect URI mismatch is detected. The authorization is rejected. "عنوان إعادة التوجيه غير متطابق"

**Recovery:** All valid redirect URIs must be pre-registered. Wildcard URIs are not allowed. Strict validation is enforced at the authorization endpoint.

### TPP API key leakage — TPP accidentally exposes their API key in client-side code
**System Behavior:** A TPP developer embeds the Beza API key in client-side JavaScript. The key is visible in the browser's developer tools. An attacker uses the key to make unauthorized API calls.

**User Impact:** Beza sees API calls from unauthorized origins. User data accessed through the leaked key is potentially exposed.

**Recovery:** The leaked API key is immediately revoked. A new key is issued to the TPP. The TPP is educated on secure key storage practices. Rate limiting and IP whitelisting are enforced.

## 6. Business Logic Failures

### Consent scope mismatch — user granted "read balance" but TPP also requests "read transactions"
**System Behavior:** The consent screen displays all requested scopes. The user can see and deselect individual scopes. If the user misses deselecting "read transactions," that scope is granted.

**User Impact:** The TPP can read transactions even though the user only intended to share the balance.

**Recovery:** Granular scope toggles are provided with clear explanations. The user can revoke specific scopes later through the consent management screen.

### Data refresh frequency limit — TPP wants real-time but the tier only allows daily
**System Behavior:** The TPP is subscribed to the Basic tier (daily updates). The TPP requests real-time data. The API returns `TIER_LIMIT_EXCEEDED`.

**User Impact:** The TPP receives an error. "ترقية الاشتراك للحصول على بيانات فورية"

**Recovery:** The TPP upgrades to the Premium tier. A real-time WebSocket connection is established.

### Account aggregation fails for a specific account type (fixed deposit)
**System Behavior:** The aggregation engine does not support fixed deposit accounts. The FD balance is not shown in the dashboard.

**User Impact:** The user's fixed deposit balance is not visible in the Open Finance dashboard. "بعض أنواع الحسابات غير مدعومة حالياً"

**Recovery:** Fixed deposit support is added to the aggregation engine. Historical data for existing FDs is backfilled.

### Multiple TPPs accessing the same data simultaneously — user has 5 budget apps
**System Behavior:** All 5 TPPs poll for changes at the same time (top of the hour). The API gateway handles the concurrent requests.

**User Impact:** No user impact. All TPPs receive their data. The latency may increase by up to 200ms.

**Recovery:** The API gateway uses request collapsing — one upstream request serves all 5 TPPs that poll within the same second.

### Deceased user data access — family member tries to access the deceased's financial data
**System Behavior:** The consent verification fails because the user account is not active (deceased status). The data access is denied.

**User Impact:** The family member cannot see the deceased's accounts. "الحساب غير نشط"

**Recovery:** The family member contacts customer support with a death certificate and legal authorization. The accounts are transferred to the executor of the estate.

### TPP data retention policy violation — TPP stores data beyond permitted period
**System Behavior:** The TPP continues to store user financial data beyond the 90-day retention period specified in the consent agreement. Beza's compliance audit detects the violation.

**User Impact:** The user's financial data is retained longer than permitted. This is a privacy policy violation.

**Recovery:** Beza notifies the TPP to delete the data immediately. If the TPP does not comply within 48 hours, the TPP is suspended from the platform. All affected users are notified.

### Syrian bank account number format mismatch — TPP expects IBAN but Syrian banks use local format
**System Behavior:** The TPP requests account data in IBAN format. Syrian bank accounts use a local format (account number without IBAN prefix). Beza cannot map the account to an IBAN.

**User Impact:** The TPP receives a format error. The account data cannot be shared in the requested format.

**Recovery:** Beza provides account data in both local and IBAN formats where available. The TPP updates its integration to accept the local account number format.

## 7. Performance & Scalability Failures

### TPP API spike — 30,000 API requests per minute from a popular fintech app
**System Behavior:** A popular fintech app integrated with Beza Open Finance sends 30,000 requests per minute during a promotional campaign. The API gateway auto-scales to handle the load.

**User Impact:** Other TPPs experience 1-2 second latency during the spike. The aggressive TPP is rate-limited.

**Recovery:** Per-TPP rate limits are enforced (4,000 requests per minute per TPP). The API gateway prioritizes requests from existing TPPs over new ones. The promotional TPP's rate limit is temporarily increased with a support ticket.

### Consent management dashboard — 500,000 consents to display
**System Behavior:** A user with 500,000 connected TPPs (theoretical max) tries to load their consent management dashboard. The query scans 500,000 rows.

**User Impact:** The dashboard takes 30 seconds to load. "جاري تحميل الأذونات..." is shown.

**Recovery:** The consent list is paginated (20 per page). A search/filter feature allows finding specific TPPs. Archive consents older than 1 year.

### Data aggregation from 10 partner Syrian banks — full refresh takes 3 hours
**System Behavior:** The daily full data aggregation job pulls account data from 10 partner Syrian banks. Each bank API has different latency. The total job takes 3 hours.

**User Impact:** User account data can be up to 3 hours stale. New transactions are not reflected immediately.

**Recovery:** Incremental aggregation runs every 15 minutes for recent data (last 24 hours). Full refresh runs once daily. The aggregation window is reduced to 20 minutes on a normal day.

### Bank API throttling during peak aggregation — 10 banks rate-limited simultaneously
**System Behavior:** During the daily aggregation peak at 9:00 AM, all 10 partner banks experience high load. Five of them (Bank of Syria and Overseas, Real Estate Bank, BEMO, Industrial Bank, Agricultural Bank) throttle Beza's aggregation requests.

**User Impact:** Aggregation is delayed for users with accounts at those 5 banks. Account data remains stale for up to 2 extra hours.

**Recovery:** Aggregation schedules are staggered across banks (Bank of Syria at 8:00 AM, Real Estate at 8:15 AM, BEMO at 8:30 AM, etc.). Retry with exponential backoff is implemented for throttled requests.

## 8. Operational Failures

### Deployment rollback — v5.6.0 exposes transaction details in TPP response
**System Behavior:** The canary deployment detects that transaction descriptions (merchant name, location) are being sent to TPPs without explicit consent scope. The rollback is triggered.

**User Impact:** User transaction details are exposed to TPPs that were only authorized for balance and transaction amounts. Privacy breach for 500 users during the 2-minute window.

**Recovery:** The rollback completes within 2 minutes. An API audit identifies which TPPs received the additional data. Those TPPs are required to delete the data. The consent scope filter is corrected.

### Configuration error — CORS policy set to allow all origins (*)
**System Behavior:** A configuration change sets the CORS policy for the Open Finance API to allow all origins. Any website can make API calls to Beza's Open Finance endpoints.

**User Impact:** Potential for cross-origin data theft from authenticated users. No immediate user impact detected.

**Recovery:** The CORS policy is corrected to allow only registered TPP origins within 5 minutes. A security audit checks for any data exfiltration during the window.

### TPP certificate revocation — 30 TPPs lose connectivity due to expired certificates
**System Behavior:** The QWAC certificates for 30 TPPs expire on the same day. The TPPs are automatically disconnected until new certificates are provided.

**User Impact:** Users of 30 fintech apps cannot access their Beza financial data through those apps.

**Recovery:** TPPs are notified 30 days before certificate expiry. A grace period of 7 days after expiry allows certificate renewal without service interruption.

### Syrian fintech regulatory change — CBS updates Open Finance regulation
**System Behavior:** CBS issues new Open Finance regulations requiring additional consent screens and data retention limits. Beza's existing implementation does not comply with the new regulation.

**User Impact:** TPP integrations may be non-compliant. New TPP onboarding is paused until compliance is achieved.

**Recovery:** Beza's compliance team analyzes the new regulation. The consent flow and data retention policies are updated within 30 days. Existing TPPs are notified of the changes and given 60 days to comply.

### TPP developer portal outage — onboarding blocked for 6 hours
**System Behavior:** The TPP developer portal (developer.beza.com) experiences a database failure. New TPPs cannot register, existing TPPs cannot update their applications.

**User Impact:** Syrian fintech developers cannot integrate with Beza. New TPP onboarding is completely blocked.

**Recovery:** The database is restored from the latest snapshot within 6 hours. The developer portal recovery SLA is 4 hours. A read-replica is added for failover.

### Third-party API monitoring service outage
**System Behavior:** Beza's external API monitoring service (used to track partner bank API uptime) is down. Beza cannot automatically detect bank API outages.

**User Impact:** Partner bank API outages go undetected for longer periods. Users experience stale data without proactive notification.

**Recovery:** Beza switches to a secondary monitoring service. A fallback in-house monitoring system polls each bank API every 5 minutes. The primary monitoring service is restored within 2 hours.

## 9. Recovery Time Objectives Summary

| Failure Category | Detection Time | Recovery Time | RPO | Maximum Impact |
|-----------------|----------------|---------------|-----|----------------|
| Network (transient) | < 1 second | < 30 seconds | 0 | Single API request failed |
| Network (DNS outage) | 30 seconds | < 5 minutes | 0 | All Open Finance blocked |
| Transaction failure | < 100ms | < 5 seconds | 0 | Single consent/request |
| External dependency | < 10 seconds | < 15 minutes | 0 | Partner bank API down |
| Data inconsistency | < 5 minutes | < 1 hour | < 5 seconds | Stale account data |
| Security incident | < 1 minute | < 4 hours | 0 | Consent revoked / TPP blocked |
| Business logic | < 1 hour | < 24 hours | 0 | Consent scope issue |
| Performance degradation | < 1 minute | < 5 minutes | 0 | Slow API response |
| Operational (deployment) | < 5 minutes | < 10 minutes | < 1 minute | Data exposure |

## Changelog

| Date | Version | Changes |
|------|---------|---------|
| 2026-05-29 | 1.0 | Initial release — 7 categories covering network, transactions, external dependencies, data consistency, security, business logic, and performance failures for open banking / open finance feature — Syria context only |

---

*Version: 1.0 | Last updated: 2026-05-29 | Owner: Open Finance Engineering Team*
