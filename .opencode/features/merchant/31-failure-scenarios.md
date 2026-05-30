# 31. Merchant — Failure Scenarios

> **Purpose:** Document every failure mode for the Beza Merchant feature — in-store payments, POS, QR code, e-commerce gateway, settlement, and merchant onboarding. Uses real ETB amounts and Arabic/Amharic messaging.

---

## 1. Network Failures

### Internet cut during POS payment after customer debited but merchant not credited
**System Behavior:** The transaction is marked `PENDING_SETTLEMENT`. The customer's wallet is debited. The merchant's POS terminal shows "قيد المعالجة" (Processing.) The merchant credit is not applied.

**User Impact:** The customer sees "تم خصم المبلغ من المحفظة. في انتظار تأكيد التاجر" (Amount has been deducted from your wallet. Awaiting merchant confirmation.) The merchant sees a spinning icon on the POS screen.

**Recovery:** The POS terminal retries the credit to the merchant wallet every 10 seconds for up to 5 minutes. On success, a receipt is printed automatically. After 5 minutes of failure, a reversal is initiated and the customer is refunded.

### API timeout (>5s) during QR code generation
**System Behavior:** The merchant app cannot generate a new QR code because the API call times out. The app shows the last cached QR code, which is valid for 60 seconds from its original generation time.

**User Impact:** The customer attempting to scan the QR code uses the cached version. If the cached code has expired, the scan fails and the customer sees "رمز الاستجابة السريعة منتهي الصلاحية" (QR code expired.)

**Recovery:** The merchant refreshes the app to generate a new QR code. The circuit breaker resets after 15 seconds and the API call succeeds on retry.

### DNS failure for merchant-api.beza.et
**System Behavior:** The e-commerce checkout iframe hosted by Beza fails to load because the merchant API DNS is unreachable. The payment gateway JavaScript detects the failure and falls back to a redirect-based payment flow.

**User Impact:** The customer sees "بوابة الدفع غير متوفرة حالياً. استخدم طريقة دفع بديلة" (Payment gateway is currently unavailable. Please use an alternative payment method.)

**Recovery:** The e-commerce plugin automatically redirects the customer to `pay.beza.et` which is served from a different DNS zone. If the redirect also fails, the customer is offered a bank transfer as a backup.

### WebSocket disconnect during real-time payment notification
**System Behavior:** The merchant dashboard detects the WebSocket disconnection and falls back to REST API polling every 5 seconds to check for new transactions.

**User Impact:** The merchant sees "آخر تحديث: منذ 3 دقائق" (Last updated: 3 minutes ago.) on the dashboard. Payments are still being processed but are not reflected in real-time.

**Recovery:** The WebSocket client reconnects automatically. Once reconnected, the server replays all missed transaction events. The dashboard is refreshed with the complete transaction list.

### SSL handshake failure on merchant POS terminal
**System Behavior:** The POS terminal cannot establish a TLS connection with the Beza server. The terminal switches to offline mode, which supports transactions up to 500 ETB that are stored locally on the device.

**User Impact:** The customer can still pay up to 500 ETB. The merchant sees "وضع غير متصل — المبلغ محدود بـ 500 ETB" (Offline mode — amount limited to 500 ETB.)

**Recovery:** The POS terminal stores encrypted transactions in local memory. When the network connection is restored, the stored transactions are automatically synced and sent for batch settlement.

## 2. Transaction Failures

### Insufficient customer balance — customer tries to pay 3,500 ETB but has 2,000 ETB
**System Behavior:** The payment pre-validation checks `wallet_balance >= transaction_amount`. The balance of 2,000 ETB is below the 3,500 ETB purchase amount. The transaction is rejected.

**User Impact:** The customer sees "رصيد غير كافٍ. الرصيد المتاح: 2,000 ETB" (Insufficient balance. Available balance: 2,000 ETB.)

**Recovery:** The customer can split the payment — 2,000 ETB from the Beza wallet and 1,500 ETB from a linked bank card. The merchant POS system supports split-tender transactions.

### Duplicate QR scan — two customers scan the same QR code simultaneously
**System Behavior:** The QR code is configured for single use. After the first successful payment, the QR code is marked as consumed. The second scan returns `QR_ALREADY_USED`.

**User Impact:** The second customer sees "رمز الاستجابة السريعة منتهي الصلاحية" (QR code expired.) The first customer's payment proceeds normally.

**Recovery:** The merchant generates a new QR code by refreshing the app. QR codes have a 60-second lifetime and are automatically refreshed every 30 seconds.

### Refund initiated for an already-refunded transaction
**System Behavior:** The idempotency key prevents double refunds. The second refund request returns HTTP 409 Conflict with `DUPLICATE_REFUND`.

**User Impact:** The merchant sees "تم إرجاع المبلغ مسبقاً" (The amount has already been refunded.)

**Recovery:** The merchant checks the transaction history to confirm the refund status before initiating a new refund request.

### Partial refund — merchant refunds 500 ETB from a 2,000 ETB purchase
**System Behavior:** The system supports partial refunds. A new refund transaction is created for 500 ETB. The original transaction remains valid for the remaining 1,500 ETB.

**User Impact:** The customer receives "تم إرجاع 500 ETB من مشترياتك لدى متجر ABC" (500 ETB has been refunded from your purchase at ABC store.)

**Recovery:** The remaining 1,500 ETB is still available for future refunds if needed. The merchant can issue additional partial refunds up to the original transaction amount.

### Settlement batch fails — daily merchant settlement to bank account fails
**System Behavior:** The settlement engine processes the batch at 2:00 AM EAT. The bank API returns an error for the batch transfer. The entire batch is held.

**User Impact:** The merchant sees "تسوية اليوم متأخرة" (Today's settlement is delayed.) The previous day's funds are not yet available in the merchant's bank account.

**Recovery:** The settlement is retried every 30 minutes for up to 6 hours. If all retries fail, the operations team triggers a manual settlement through the bank's corporate portal.

### Merchant account deactivated mid-transaction
**System Behavior:** The merchant's account status is changed to `INACTIVE` by a compliance action. The payment was initiated before the status change propagated. The transaction is blocked at the authorization stage.

**User Impact:** The customer sees the transaction was rejected. The merchant sees "حساب التاجر غير نشط" (Merchant account is inactive.)

**Recovery:** The merchant contacts customer support to resolve the compliance issue. Once the account is reactivated, payments resume normally.

## 3. External Dependency Failures

### CFE (Ethio Telecom) service down during merchant SIM verification
**System Behavior:** The new merchant onboarding flow pauses at the SIM card verification step. The CFE API cannot confirm the mobile number ownership.

**User Impact:** The prospective merchant sees "تعذر التحقق من رقم الهاتف" (Phone number verification failed.)

**Recovery:** The onboarding flow continues with an alternative verification method — a video call with a Beza agent who verifies the merchant's identity and SIM card ownership.

### SMS provider (InfoBip) unavailable for merchant receipt
**System Behavior:** The SMS delivery is queued on SQS. A push notification is sent via Firebase Cloud Messaging as the primary receipt channel.

**User Impact:** The customer may not receive the SMS receipt. The message "تم إرسال الإيصال عبر التطبيق" (The receipt has been sent via the app) is shown.

**Recovery:** The SMS is retried for up to 24 hours. If the customer has consented to WhatsApp communication, a WhatsApp receipt is sent as a backup.

### Bank API timeout during merchant settlement
**System Behavior:** The ACI/EBPP bank transfer for the merchant settlement does not respond within the 30-second timeout. The funds are held in the Beza settlement account.

**User Impact:** The merchant sees "التسوية معلقة — سيتم إيداع المبلغ خلال 24 ساعة" (Settlement is pending — the amount will be deposited within 24 hours.)

**Recovery:** An internal transfer is made to the merchant's Beza wallet instantly (available immediately). The bank transfer is processed asynchronously when the bank API is available.

### Tax authority (MoF) API failure for e-receipt integration
**System Behavior:** The receipt is generated locally but the submission to the MoF tax authority fails. The receipt is queued for retry with the transaction data stored locally.

**User Impact:** No user impact is visible. The merchant prints the receipt with a valid tax ID. The receipt shows "الإيصال مسجل في النظام الضريبي" (Receipt registered with the tax authority.)

**Recovery:** The e-receipt is retried every 5 minutes for up to 2 hours. If the MoF API remains unavailable, the operations team manually uploads the batch to the tax authority portal.

### POS terminal manufacturer API down for firmware update
**System Behavior:** The POS terminal continues operating on the current firmware version. The security patch or feature update is delayed.

**User Impact:** No operational impact on payment processing. The merchant sees "يتوفر تحديث للنظام. يرجى التحديث لاحقاً" (A system update is available. Please update later.)

**Recovery:** The firmware update is deferred to the next maintenance window. The POS terminal continues operating normally.

## 4. Data Consistency Failures

### Payment success DB write succeeds but merchant ledger write fails
**System Behavior:** The customer is debited successfully. The merchant ledger write fails due to a database connection issue. The Saga pattern detects the inconsistency and triggers a compensation.

**User Impact:** The customer sees a refund notification "تم إعادة 1,500 ETB إلى محفظتك" (1,500 ETB has been returned to your wallet.) The merchant never sees the transaction.

**Recovery:** The compensation is triggered automatically within 2 seconds. The merchant transaction is not recorded. The customer is refunded in full.

### Cache inconsistency — merchant daily sales total shown as 85,000 ETB but actual is 72,000 ETB
**System Behavior:** The cache-aside pattern with version checking detects the TTL mismatch. The stale cache entry is invalidated and a fresh value is computed from the database.

**User Impact:** The merchant sees an inflated sales figure on the dashboard. A warning "قد تختلف الأرقام عن الإجمالي الفعلي" (Figures may differ from the actual total.) is shown.

**Recovery:** The cache is invalidated and the fresh total is computed from the database. An `INCONSISTENCY_DETECTED` metric is logged for monitoring.

### Settlement event lost in Kafka — merchant settlement instruction not published
**System Behavior:** The settlement engine prepares the merchant's settlement but the Kafka message is lost before reaching the bank transfer service. The merchant is not settled.

**User Impact:** The merchant's daily settlement is missing from their dashboard. The status shows "لم يتم تسوية اليوم" (Not settled today.)

**Recovery:** The dead-letter queue consumer detects the missing settlement event within 5 minutes and replays it. A manual trigger is available through the ops panel for immediate settlement.

### Dual-write between payment service and inventory system fails
**System Behavior:** The payment is processed successfully. The inventory decrement write fails. The system detects the inconsistency within 5 seconds.

**User Impact:** The customer pays for an item successfully. The merchant's inventory is not updated, creating a risk of overselling the same item.

**Recovery:** The payment is reversed if the inventory write fails within 5 seconds. The customer sees "تم إلغاء الطلب بسبب خطأ في المخزون" (The order has been cancelled due to an inventory error.)

### Merchant commission rate table updated mid-day
**System Behavior:** The commission rate table uses versioned entries. Transactions initiated before the rate change use the old rate. Transactions after the change use the new rate.

**User Impact:** The merchant sees some transactions at the old commission rate and some at the new rate within the same day. The message is "معدل العمولة: 1.5% للمعاملات السابقة، 1.8% للجديدة" (Commission rate: 1.5% for previous transactions, 1.8% for new transactions.)

**Recovery:** The rate table is versioned per transaction. No retroactive application occurs. The merchant is notified of the rate change 7 days in advance.

## 5. Security Failures

### Fraud false positive — legitimate customer making a 15,000 ETB purchase flagged
**System Behavior:** The AML rules engine triggers on the combination of amount > 10,000 ETB and a first-time transaction with this merchant. The payment is blocked and placed in review.

**User Impact:** The customer sees "المعاملة قيد المراجعة" (Transaction under review.) at the checkout counter. This may cause embarrassment in a physical store setting.

**Recovery:** An SMS OTP is sent to the customer's registered phone. If the customer enters the OTP correctly, the transaction is approved within 30 seconds.

### Fraud false negative — stolen phone used to make an 8,000 ETB payment at a merchant
**System Behavior:** The behavioral model scores the transaction at 0.3, which is well below the 0.7 threshold for MFA. The payment is approved without additional verification.

**User Impact:** The legitimate phone owner loses 8,000 ETB. The transaction appears as a normal purchase at a merchant store.

**Recovery:** The user reports the fraud through the call center. Insurance covers 80% of the verified loss. The device fingerprint and geolocation are used for retrospective model training.

### Unauthorized access to merchant settlement dashboard
**System Behavior:** An attacker changes the merchant's settlement bank account details through the compromised dashboard. The change takes effect on the next settlement cycle.

**User Impact:** The merchant's daily settlement of 50,000 ETB is sent to the attacker's bank account instead of the merchant's account.

**Recovery:** Settlement bank account changes require MFA plus a 24-hour cooling period. An email and SMS notification are sent to the merchant's registered contact. The audit log tracks all changes.

### Refund fraud — customer claims non-receipt after receiving goods
**System Behavior:** The customer requests a refund through customer support, claiming they never received the goods. The merchant disputes the claim.

**User Impact:** The merchant loses 3,500 ETB (refunded to the customer) plus the value of the goods.

**Recovery:** Beza requires photo evidence of delivery from the merchant. The payment is held in escrow for 24 hours after delivery confirmation. A resolution team investigates the dispute.

### POS tampering — merchant modifies POS to report lower sales
**System Behavior:** The merchant modifies their POS terminal to report 10,000 ETB in daily sales instead of the actual 25,000 ETB. The settlement fee is calculated on the lower amount.

**User Impact:** Beza loses commission on 15,000 ETB. The tax authority loses VAT on the unreported amount.

**Recovery:** Random shadow audits compare merchant-reported sales against customer receipt data from the Beza app. Anomaly detection on average ticket size versus similar merchants triggers an investigation.

## 6. Business Logic Failures

### Merchant daily settlement limit exceeded — 150,000 ETB settled but bank account has 200,000 ETB limit
**System Behavior:** The settlement engine checks the bank account's incoming transfer limit. It splits the settlement into two parts: 150,000 ETB today and 50,000 ETB tomorrow.

**User Impact:** The merchant sees "سيتم تسوية 50,000 ETB غداً" (50,000 ETB will be settled tomorrow.)

**Recovery:** The remaining balance is automatically queued for the next settlement cycle. No manual action is needed.

### Merchant category code (MCC) mismatch — merchant registered as retail but processes high-risk transactions
**System Behavior:** The risk engine detects that the transaction patterns do not match the registered MCC. Additional risk fees are applied to the transactions.

**User Impact:** The merchant sees "تم تطبيق رسوم مخاطر إضافية بنسبة 0.5%" (An additional 0.5% risk fee has been applied.)

**Recovery:** The merchant can request an MCC code update through customer support. If the MCC change is approved, the difference in risk fees is refunded.

### Customer disputes a chargeback — claims they did not authorize a 5,000 ETB payment
**System Behavior:** The chargeback is raised by the customer. The merchant account is debited 5,000 ETB pending the investigation outcome.

**User Impact:** The merchant sees "تم خصم 5,000 ETB كطرف مقابل للنزاع" (5,000 ETB has been debited as a counter-entry for the dispute.)

**Recovery:** The merchant provides the signed receipt or delivery proof. If the documentation is valid, the chargeback is reversed and the funds are returned to the merchant. If the documentation is insufficient, the merchant bears the loss.

### Settlement delayed due to a public holiday (Ethiopian banks closed)
**System Behavior:** The settlement engine checks the holiday calendar and skips the settlement for the public holiday. The settlement is queued for the next business day.

**User Impact:** The merchant sees "التسوية مؤجلة بسبب العطلة الرسمية" (Settlement is deferred due to a public holiday.)

**Recovery:** No action is needed. The funds are held in the Beza settlement account (earning no interest) and settled on the next business day.

### Tier 1 merchant daily transaction limit hit (50,000 ETB)
**System Behavior:** The merchant's daily transaction acceptance limit of 50,000 ETB is reached. The merchant cannot accept any more payments for the day.

**User Impact:** Customers attempting to pay see "التاجر غير قادر على قبول الدفع حالياً" (The merchant is currently unable to accept payments.)

**Recovery:** The merchant can upgrade to Tier 2 (daily limit of 500,000 ETB) by completing an in-app KYC upgrade with business license upload. The upgrade is instant upon document submission.

## 7. Performance & Scalability Failures

### Sudden POS traffic spike — 10x during Meskel Festival holiday shopping
**System Behavior:** The merchant payment API auto-scales from 30 to 200 pods. The QR code generation service handles 5,000 requests per second. Database write connections peak at 300.

**User Impact:** Merchants and customers experience 2-3 second latency during the first 5 minutes of the spike. QR code generation may take up to 8 seconds.

**Recovery:** Auto-scaling policies trigger at 60% CPU. Pre-warming is scheduled for known holiday periods. QR codes are pre-generated and cached on the merchant device.

### POS terminal batch settlement timeout — 500 terminals settle simultaneously at midnight
**System Behavior:** The settlement engine receives 500 batch files at the same time (midnight auto-settlement). Processing queue grows to 30 minutes.

**User Impact:** Merchants see settlement as "قيد المعالجة" (Processing) for up to 30 minutes. Funds are not available until settlement completes.

**Recovery:** Settlement processing is distributed across 10 worker pods. Terminal settlement times are randomized within a 1-hour window (11:30 PM to 12:30 AM).

### E-commerce checkout API overwhelmed — 500 concurrent checkout requests
**System Behavior:** The e-commerce payment gateway handles 500 concurrent checkout requests. The 3DS verification service becomes a bottleneck, processing only 100 requests per second.

**User Impact:** Customers see the checkout spinner for 10-15 seconds. 20% of checkout requests time out and must be retried.

**Recovery:** The 3DS service is auto-scaled. A checkout queue with position tracking is displayed to the customer. "موقعك في قائمة الانتظار: 45" (Your position in queue: 45.)

## 8. Operational Failures

### Deployment rollback — v6.3.0 breaks QR code scanning for Android devices
**System Behavior:** The canary deployment detects a 40% increase in QR scan failures within 2 minutes. The automated rollback to v6.2.9 is triggered.

**User Impact:** Approximately 300 customers could not scan QR codes for 2 minutes. They either used alternative payment or waited.

**Recovery:** The rollback completes within 2 minutes. The QR scanning library version is tested on all device types before the next release.

### Configuration error — merchant settlement fee set to 10% instead of 2%
**System Behavior:** A configuration change sets the merchant settlement fee to 10%. 200 merchant settlements are processed with the incorrect fee.

**User Impact:** 200 merchants are charged 8% extra on their settlement. A merchant settling 100,000 ETB loses 8,000 ETB in extra fees.

**Recovery:** A monitoring alert fires on the fee exceeding 3%. The configuration is reverted. Affected merchants receive the excess fee refunded plus a 5% apology credit.

### POS terminal certificate expiry — 1,000 terminals lose connectivity
**System Behavior:** The client certificate on 1,000 POS terminals expires simultaneously. The terminals cannot establish a TLS connection with the Beza server.

**User Impact:** 1,000 merchants cannot accept payments for 2 hours until the certificates are renewed.

**Recovery:** Certificate renewal is automated with 30-day advance warning. A remote certificate push mechanism is implemented. Terminals fall back to offline mode for transactions under 500 ETB.

## 9. Recovery Time Objectives Summary

| Failure Category | Detection Time | Recovery Time | RPO | Maximum Impact |
|-----------------|----------------|---------------|-----|----------------|
| Network (transient) | < 1 second | < 30 seconds | 0 | Single payment delayed |
| Network (DNS outage) | 30 seconds | < 5 minutes | 0 | All merchant ops blocked |
| Transaction failure | < 100ms | < 5 seconds | 0 | Single payment failed |
| External dependency | < 10 seconds | < 15 minutes | 0 | Biller unavailable |
| Data inconsistency | < 5 minutes | < 1 hour | < 5 seconds | Settlement discrepancy |
| Security incident | < 1 minute | < 4 hours | 0 | Merchant account frozen |
| Business logic | < 1 hour | < 24 hours | 0 | Limit hit / fee error |
| Performance degradation | < 1 minute | < 5 minutes | 0 | Slow checkout |
| Operational (deployment) | < 5 minutes | < 10 minutes | < 1 minute | Payment processing bug |

## Changelog

| Date | Version | Changes |
|------|---------|---------|
| 2026-05-29 | 1.0 | Initial release — 7 categories covering network, transactions, external dependencies, data consistency, security, business logic, and performance failures for merchant payments feature |

---

*Version: 1.0 | Last updated: 2026-05-29 | Owner: Merchant Engineering Team*
