# 31. Bill Payment — Failure Scenarios

> **Purpose:** Document every failure mode for the Beza Bill Payment feature — utility bills (electricity, water, telecom), government fees, and private billers. Uses real ETB amounts, biller names (EEU, Ethio Telecom, Addis Ababa Water), and Arabic/Amharic messaging.

---

## 1. Network Failures

### Internet cut during bill payment after debit but before biller confirmation
**System Behavior:** The customer is debited from their wallet. The payment is marked `PENDING_BILLER_ACK`. The biller (e.g., EEU) has not received the payment confirmation.

**User Impact:** The customer sees "تم خصم المبلغ. في انتظار تأكيد الفاتورة" (Amount deducted. Awaiting bill confirmation.) The biller may later send a disconnection notice if the payment is not recorded.

**Recovery:** A biller reconciliation job runs every 2 hours. If the biller confirms receipt, the payment status is updated. If the biller does not confirm within 4 hours, a reversal is initiated and the customer is refunded.

### API timeout (>5s) during EEU bill inquiry
**System Behavior:** The bill inquiry service times out while fetching the bill amount from the EEU API. The service cannot return the bill amount.

**User Impact:** The customer sees "تعذر الحصول على قيمة الفاتورة. حاول مرة أخرى" (Could not obtain the bill amount. Please try again.) The bill amount field remains blank.

**Recovery:** The system retries with backoff (2s, 5s, 15s). If all retries fail, the customer can enter the amount manually by uploading a photo of their bill. The manual entry is verified against the biller's records before processing.

### DNS failure for bills-api.beza.et
**System Behavior:** The bill payment gateway is inaccessible because the DNS cannot resolve the API endpoint. All bill payment operations are blocked.

**User Impact:** The user sees "خدمة دفع الفواتير غير متوفرة حالياً" (Bill payment service is currently unavailable.) The request is automatically redirected to `pay.beza.et`.

**Recovery:** Route53 failover to the secondary region completes within 5 minutes. The customer retries after a 2-minute wait.

### Biller API (EEU) connection timeout during payment submission
**System Behavior:** The payment submission to the EEU server does not receive a response within the 20-second timeout. The payment is marked as `PENDING_BILLER_SUBMIT`.

**User Impact:** The customer sees "جاري تأكيد الدفع مع هيئة الكهرباء" (Confirming payment with the Electric Utility.)

**Recovery:** The system retries the submission 3 times (30s, 2min, 5min intervals). If the EEU server remains unreachable, the payment is queued and processed when the EEU system recovers.

### Network partition between Beza and Ethio Telecom SMSC
**System Behavior:** The bill payment is processed successfully. The SMS receipt cannot be delivered because the SMSC connection is down.

**User Impact:** The customer sees "تم الدفع بنجاح" (Payment successful) in the app. The SMS receipt is not received.

**Recovery:** The SMS is retried for up to 24 hours. An in-app receipt is always available in the transaction history. A push notification is sent as an immediate backup.

## 2. Transaction Failures

### Insufficient balance — customer tries to pay a 3,500 ETB water bill but has 2,100 ETB
**System Behavior:** The pre-validation checks `wallet_balance >= bill_amount`. The transaction is rejected before any debit occurs.

**User Impact:** The customer sees "رصيد غير كافٍ. الرصيد المتاح: 2,100 ETB" (Insufficient balance. Available: 2,100 ETB.)

**Recovery:** The UI shows the minimum top-up amount needed. The customer can add funds to their wallet or check if the biller supports partial payment. Most utility billers in Ethiopia require full payment.

### Duplicate bill payment — customer pays the same EEU bill twice
**System Behavior:** The idempotency key is constructed from `biller_code + customer_id + bill_reference + amount`. The second payment request returns `DUPLICATE` and is rejected.

**User Impact:** The customer sees "تم دفع هذه الفاتورة مسبقاً" (This bill has already been paid.)

**Recovery:** The second payment is rejected before any debit occurs. If a duplicate payment somehow occurs (race condition), the customer can request a refund from Beza support, which is processed within 48 hours.

### Wrong bill reference number — customer enters a 10-digit number instead of 13 digits
**System Behavior:** The reference validation checks the length and format against the biller's specification. The format does not match and the payment is blocked.

**User Impact:** The customer sees "رقم الفاتورة غير صحيح. يجب أن يتكون من 13 رقماً" (Invalid bill number. Must be 13 digits.)

**Recovery:** The UI validates the reference number format as the customer types. The format mask adjusts based on the selected biller type.

### Bill already paid at the biller — customer tries to pay an already-cleared EEU bill
**System Behavior:** The biller returns `BILL_ALREADY_PAID` when Beza submits the payment. The payment is not processed and the customer is not debited.

**User Impact:** The customer sees "هذه الفاتورة مدفوعة مسبقاً" (This bill has already been paid.)

**Recovery:** No refund is needed since no funds were moved. The transaction history shows "مدفوعة مسبقاً" (Already paid) for reference.

### Payment amount exceeds bill amount — customer enters 2,000 ETB for a 1,750 ETB bill
**System Behavior:** The system checks with the biller whether overpayments are accepted. If accepted, the excess is credited as prepaid balance. If not accepted, the payment is rejected.

**User Impact:** The customer sees "المبلغ المدخل (2,000 ETB) يتجاوز قيمة الفاتورة (1,750 ETB). سيتم إيداع 250 ETB كرصيد مسبق" (The entered amount (2,000 ETB) exceeds the bill amount (1,750 ETB). 250 ETB will be credited as prepaid balance.)

**Recovery:** The prepaid balance is tracked by the biller and can be used for the next billing cycle.

### Payment made to the wrong biller — customer selects Ethio Telecom but enters an EEU reference
**System Behavior:** The system validates the reference number format against the selected biller's pattern. The format does not match and the payment is blocked.

**User Impact:** The customer sees "رقم الفاتورة لا يتطابق مع مزود الخدمة المحدد" (The bill number does not match the selected service provider.)

**Recovery:** The payment is rejected before any funds are moved. The customer corrects the biller selection and retries.

## 3. External Dependency Failures

### EEU (Ethiopian Electric Utility) system down for maintenance
**System Behavior:** All EEU bill inquiries and payments are blocked. A status banner is shown on the bill payment page.

**User Impact:** The customer sees "خدمة هيئة الكهرباء غير متوفرة حالياً. حاول مرة أخرى بعد ساعة" (EEU service is currently unavailable. Please try again in 1 hour.)

**Recovery:** The operations team monitors the EEU status page. When EEU confirms availability, the service resumes automatically. For urgent payments, a queue is available that processes when EEU is back online.

### Ethio Telecom biller API returning incorrect balance
**System Behavior:** The Ethio Telecom API returns 0 ETB due for all customers due to a billing system glitch. Beza displays the API response.

**User Impact:** The customer sees "لا توجد فاتورة مستحقة" (No bill due.) even though they know they have an outstanding balance.

**Recovery:** Beza displays a disclaimer "قد لا تكون قيمة الفاتورة دقيقة. يُرجى التأكيد مع Ethio Telecom" (The bill amount may not be accurate. Please confirm with Ethio Telecom.) The customer can manually enter the amount with a screenshot of their bill.

### Water and Sewerage Authority API timeout
**System Behavior:** The inquiry API hangs for more than 15 seconds. The system falls back to the cached bill amount from the last 24 hours.

**User Impact:** The customer sees a cached amount with the disclaimer "قيمة الفاتورة من آخر تحديث" (Bill amount from the last update.)

**Recovery:** If no cached amount is available, the system shows "غير متاح" (Unavailable.) and the customer can manually enter the amount by taking a photo of their water bill.

### SMS provider (InfoBip) unavailable for bill payment receipt
**System Behavior:** The SMS is queued on SQS. A push notification is sent via Firebase Cloud Messaging as the primary channel.

**User Impact:** The customer may not receive the SMS receipt. The receipt is available in the app transaction history. "تم إرسال الإيصال عبر التطبيق" (Receipt sent via app.)

**Recovery:** The SMS is retried every hour for up to 24 hours. If the customer has consented, a WhatsApp receipt is also sent.

### Tax authority (MoF) API for government fee payment down
**System Behavior:** Government fee payments (e.g., passport fees, business licenses) are blocked. Private billers (EEU, Ethio Telecom) are unaffected.

**User Impact:** The customer sees "خدمة دفع الرسوم الحكومية غير متوفرة" (Government fee payment service is unavailable.)

**Recovery:** Queued payments are processed when the MoF API is restored. Any late payment penalties incurred due to the outage are waived.

## 4. Data Consistency Failures

### Payment success in Beza DB but biller rejects (EEU system error)
**System Behavior:** The customer is debited in the Beza wallet. The EEU system returns a `REJECTED` status. Beza detects the mismatch and initiates an automatic reversal.

**User Impact:** The customer sees the reversal "تم إعادة 1,750 ETB إلى محفظتك. فشل دفع الفاتورة" (1,750 ETB has been returned to your wallet. Bill payment failed.)

**Recovery:** The reversal is completed within 30 seconds. The customer can retry the payment. The EEU error is logged for follow-up by the operations team.

### Cache inconsistency — bill amount cached as 0 but actual is 1,500 ETB
**System Behavior:** The cache TTL (5 minutes) serves a stale value of 0 ETB. The customer proceeds thinking no bill is due.

**User Impact:** The customer does not pay the bill. The bill goes overdue and a late fee is incurred.

**Recovery:** The next cache refresh shows the correct amount of 1,500 ETB. The customer is notified "لديك فاتورة مستحقة بقيمة 1,500 ETB" (You have a due bill of 1,500 ETB.)

### Bill payment event lost in Kafka — credit to biller never sent
**System Behavior:** The payment is marked as `COMPLETED` in the Beza database. The Kafka message carrying the payment confirmation to the biller is lost. The biller is never notified.

**User Impact:** The customer thinks the bill is paid. The biller sends a disconnection notice because they have not received the payment.

**Recovery:** A reconciliation job between Beza and the biller runs daily. Mismatched payments are detected and the credit information is resent. The customer is notified of the correction.

### Dual-write inconsistency — payment recorded but biller balance decrement fails
**System Behavior:** The customer is debited. The biller's prepaid balance is not updated because the write to the biller's system fails.

**User Impact:** The customer sees the payment in their history. The biller shows no credit on their account.

**Recovery:** A compensatory transaction refunds the customer. The customer sees "تم إلغاء الدفع. أعيد 1,750 ETB" (Payment cancelled. 1,750 ETB returned.)

### Bill reference number collision — two customers use the same reference number
**System Behavior:** The database enforces a unique constraint on `(biller_code, bill_reference)`. The second customer's payment is rejected.

**User Impact:** The second customer sees "رقم الفاتورة مستخدم مسبقاً. يرجى التحقق من الرقم" (Bill number already in use. Please verify the number.)

**Recovery:** The customer contacts the biller to confirm the correct reference number. Beza support helps resolve the collision.

## 5. Security Failures

### Fraud false positive — customer paying a 25,000 ETB business electricity bill flagged
**System Behavior:** The AML rules engine triggers on the amount exceeding the 20,000 ETB threshold. The payment is placed in `PENDING_REVIEW`.

**User Impact:** The customer sees "المعاملة قيد المراجعة" (Transaction under review.) The business may face a late payment penalty if the delay exceeds the due date.

**Recovery:** The compliance team reviews the transaction within 2 hours. If the customer has a history of business-scale payments, their profile is whitelisted. The payment is released.

### Fraud false negative — stolen phone used to pay a 500 ETB bill (testing), then 15,000 ETB
**System Behavior:** The small test payment of 500 ETB passes with a low risk score. The larger payment of 15,000 ETB also passes because the device has been used successfully.

**User Impact:** The legitimate owner loses 15,500 ETB total. The biller is credited for the fraudster's payments.

**Recovery:** The device fingerprint and geolocation anomaly triggers a retrospective alert. Insurance covers 80% of the verified loss. The stolen device is blacklisted.

### Unauthorized access to biller configuration panel
**System Behavior:** An attacker modifies the EEU biller API endpoint URL to point to the attacker's own server. All EEU bill payments are redirected.

**User Impact:** Thousands of customers pay their EEU bills to the attacker. At 1,750 ETB per customer times 10,000 customers, the total exposure is 17.5 million ETB.

**Recovery:** API endpoint changes require MFA plus dual approval from two authorized engineers. An audit log entry triggers an immediate SIEM alert on any `BILLER_ENDPOINT_CHANGE`. Weekly endpoint verification confirms correct configuration.

### Refund fraud — customer claims bill payment failed, requests refund after biller confirms
**System Behavior:** The customer calls support claiming the payment failed. The support agent issues a refund without verifying with the biller.

**User Impact:** Beza loses 1,750 ETB. The customer receives both the bill payment and the refund.

**Recovery:** The support workflow requires direct verification with the biller before issuing any refund. The refund requires the biller's confirmation code as evidence.

### Fake biller injected — attacker registers as a biller and sends fake bills
**System Behavior:** The attacker registers a fake biller through the biller onboarding process, which was not thoroughly vetted. The attacker sends fake bills to Beza users.

**User Impact:** 500 customers pay 1,200 ETB each to the fake biller. Total loss is 600,000 ETB.

**Recovery:** All new billers undergo physical verification and bank account matching before activation. Suspicious billers are flagged for manual review by the merchant onboarding team.

## 6. Business Logic Failures

### Late payment fee incurred due to Beza processing delay
**System Behavior:** The customer pays on the due date. The biller receives the payment the next day due to a processing delay. The biller charges a late fee.

**User Impact:** The customer sees a late fee from the biller. Beza covers the cost. "تم دفع غرامة التأخير نيابة عنك" (The late fee has been paid on your behalf.)

**Recovery:** Beza absorbs the late fee (maximum 50 ETB per bill). The root cause — the biller's batch processing runs only at 6 PM — is documented and communicated to the customer.

### Biller rejects payment because the customer account number format changed
**System Behavior:** The EEU migrated to a new account numbering system. The old format is no longer recognized. The payment is rejected.

**User Impact:** The customer sees "رقم الحساب غير معروف لدى هيئة الكهرباء" (Account number unknown to EEU.)

**Recovery:** Beza updates the reference format mapping within 4 hours. The old format is automatically mapped to the new format. The customer retries the payment.

### Partial payment not supported by the biller
**System Behavior:** The customer tries to pay 50% of a 3,000 ETB bill. The biller returns `PARTIAL_NOT_SUPPORTED`.

**User Impact:** The customer sees "هذا المزود لا يدعم الدفع الجزئي. يرجى دفع 3,000 ETB كاملاً" (This provider does not support partial payment. Please pay the full 3,000 ETB.)

**Recovery:** The UI hides the partial payment option for billers that do not support it. The customer must pay the full amount.

### Recurring payment fails due to insufficient balance on the scheduled date
**System Behavior:** The auto-payment engine checks the wallet balance. The balance is insufficient. The payment is skipped. A retry is scheduled for 3 days later.

**User Impact:** The customer is notified "فشل الدفع التلقائي للفاتورة. يرجى التأكد من وجود رصيد كافٍ" (Auto-payment failed. Please ensure you have sufficient balance.)

**Recovery:** The system retries up to 3 times (3, 7, and 14 days after the original due date). If all retries fail, the auto-payment rule is suspended and the customer must pay manually.

### Bill amount changed between inquiry and payment
**System Behavior:** The EEU updated the bill from 1,750 ETB to 1,850 ETB between the customer's inquiry and the payment confirmation. The system re-verifies the amount at payment time.

**User Impact:** The customer sees "تم تحديث قيمة الفاتورة. المبلغ الجديد: 1,850 ETB" (The bill amount has been updated. The new amount is 1,850 ETB.)

**Recovery:** The customer can accept the new amount or cancel the payment. If cancelled, no charge is incurred. The original inquiry amount is invalidated.

## 7. Performance & Scalability Failures

### Sudden bill payment spike — 20x volume on EEU bill due date
**System Behavior:** The bill payment API auto-scales from 20 to 150 pods. The EEU API receives 8,000 payment submissions per minute. EEU's system throttles to 100 requests per second.

**User Impact:** Customers see "الخدمة مشغولة حالياً. يرجى الانتظار" (The service is busy. Please wait.) Payment confirmations are delayed by up to 5 minutes.

**Recovery:** Bill payment requests are queued and processed at the EEU throttle rate. A position indicator shows "مكانك في قائمة الانتظار: 234" (Your position in queue: 234.) Pre-scheduled payments are processed ahead of the due date.

### Biller inquiry API rate limiting — 200 inquiries per second per biller
**System Behavior:** The EEU inquiry API enforces a rate limit of 200 requests per second. Beza sends 500 inquiries per second during peak hours. 300 inquiries are throttled.

**User Impact:** Customers see a 5-second delay when looking up their EEU bill. "جاري تحميل بيانات الفاتورة..." (Loading bill data...) takes longer than usual.

**Recovery:** Inquiry results are cached in Redis for 5 minutes. The rate is smoothed to exactly 200 requests per second. Batch inquiry is used to fetch multiple bills in a single call.

### Database query slowdown — bill history query takes 15 seconds for power users
**System Behavior:** Users with 1,000+ bill payments experience slow history queries. The query scans all rows for that user. Query time grows linearly with transaction count.

**User Impact:** Power users see "جاري تحميل سجل الفواتير..." (Loading bill history...) for 10-15 seconds.

**Recovery:** A composite index on `(user_id, created_at)` reduces query time to 200ms. Pagination is limited to 20 items per page with cursor-based pagination.

## 8. Operational Failures

### Deployment rollback — v3.4.0 sends duplicate bill payments to EEU
**System Behavior:** The canary deployment detects a 50% increase in payment submissions within 3 minutes. The automated rollback is triggered.

**User Impact:** Approximately 100 customers had their bills paid twice. The EEU shows double payment on their accounts. Total overpayment: 175,000 ETB.

**Recovery:** The rollback completes within 2 minutes. The duplicate payments are reconciled with EEU. Customers receive refunds for the overpayment within 48 hours.

### Configuration error — wrong EEU API endpoint configured
**System Behavior:** A configuration change points the EEU integration to the wrong API endpoint (production pointed to staging). All EEU payments fail.

**User Impact:** All EEU bill payments fail for 8 minutes. 500 customers cannot pay their electricity bills.

**Recovery:** A monitoring alert fires on 100% failure rate for EEU payments. The endpoint is corrected within 8 minutes. Affected customers are notified and can retry.

### Biller credential rotation failure — EEU API key expires
**System Behavior:** The EEU API key expires. The credential rotation was not completed on schedule. All EEU API calls fail with authentication errors.

**User Impact:** EEU bill inquiries and payments are blocked for 25 minutes. 2,000 customers are affected.

**Recovery:** The API key is manually rotated within 25 minutes. Automated credential rotation with 14-day advance warning is implemented. A backup API key is maintained.

## 9. Recovery Time Objectives Summary

| Failure Category | Detection Time | Recovery Time | RPO | Maximum Impact |
|-----------------|----------------|---------------|-----|----------------|
| Network (transient) | < 1 second | < 30 seconds | 0 | Single payment delayed |
| Network (DNS outage) | 30 seconds | < 5 minutes | 0 | All bill payments blocked |
| Transaction failure | < 100ms | < 5 seconds | 0 | Single payment failed |
| External dependency | < 10 seconds | < 15 minutes | 0 | Biller API unavailable |
| Data inconsistency | < 5 minutes | < 1 hour | < 5 seconds | Payment status mismatch |
| Security incident | < 1 minute | < 4 hours | 0 | Payment held for review |
| Business logic | < 1 hour | < 24 hours | 0 | Partial pay not supported |
| Performance degradation | < 1 minute | < 5 minutes | 0 | Slow bill inquiry |
| Operational (deployment) | < 5 minutes | < 10 minutes | < 1 minute | Duplicate payments |

## Changelog

| Date | Version | Changes |
|------|---------|---------|
| 2026-05-29 | 1.0 | Initial release — 7 categories covering network, transactions, external dependencies, data consistency, security, business logic, and performance failures for bill payment feature |

---

*Version: 1.0 | Last updated: 2026-05-29 | Owner: Bill Payment Engineering Team*
