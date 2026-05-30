# 31. Government — Failure Scenarios

> **Purpose:** Document every failure mode for the Beza Government feature — tax payments, government fees, social welfare disbursements, public sector payroll, and citizen-government financial interactions. Uses real ETB amounts and Arabic/Amharic messaging.

---

## 1. Network Failures

### Internet cut during tax payment after debit but before MoF receipt
**System Behavior:** The taxpayer's wallet is debited 15,000 ETB. The submission to the Ministry of Finance (MoF) API fails due to the network disconnection. The transaction is marked `TAX_PENDING_SUBMISSION`.

**User Impact:** The taxpayer sees "تم خصم 15,000 ETB كضريبة. في انتظار تأكيد وزارة المالية" (15,000 ETB deducted as tax. Awaiting Ministry of Finance confirmation.)

**Recovery:** An automatic retry mechanism submits the payment to the MoF every 5 minutes for up to 4 hours. On success, a tax receipt is generated electronically. On failure after 4 hours, the payment is refunded to the taxpayer's wallet.

### API timeout (>5s) during tax liability inquiry
**System Behavior:** The tax liability inquiry API times out while fetching the taxpayer's outstanding balance from the MoF system. The service cannot return the amount due.

**User Impact:** The citizen sees "تعذر الحصول على قيمة الضريبة المستحقة" (Could not obtain the tax amount due.)

**Recovery:** The system retries with backoff (2s, 5s, 15s). If all retries fail, the user can manually enter the amount due by uploading their tax assessment document.

### DNS failure for gov-payments.beza.et
**System Behavior:** The government payment gateway is inaccessible because the DNS cannot resolve the API endpoint. All government fee and tax payments are blocked.

**User Impact:** The citizen sees "خدمة الدفع الحكومي غير متوفرة" (Government payment service is unavailable.)

**Recovery:** Route53 failover is triggered. An alternative payment method is offered — the citizen can pay at a bank teller using a Beza payment reference.

### MoF API connection timeout during payment submission
**System Behavior:** The payment submission to the MoF server does not receive a response within the 30-second timeout. The payment is marked `PENDING_MOF_ACK`.

**User Impact:** The citizen sees "جاري تأكيد الدفع مع وزارة المالية" (Confirming payment with the Ministry of Finance.)

**Recovery:** The payment is queued locally. The system retries every 10 minutes for up to 6 hours. If the MoF remains unreachable, the operations team uploads the payment batch manually.

### WebSocket disconnect during real-time welfare disbursement tracking
**System Behavior:** The beneficiary's dashboard detects the WebSocket disconnection and falls back to REST API polling every 30 seconds.

**User Impact:** The beneficiary sees "آخر تحديث: منذ دقيقة" (Last updated: 1 minute ago.) on the disbursement status. The actual disbursement continues processing.

**Recovery:** The WebSocket reconnects automatically. Missed status events are replayed and the dashboard is updated.

## 2. Transaction Failures

### Insufficient balance — citizen tries to pay 25,000 ETB income tax but has 18,000 ETB
**System Behavior:** The pre-validation checks `wallet_balance >= tax_amount`. The balance of 18,000 ETB is insufficient. The transaction is rejected.

**User Impact:** The citizen sees "رصيد غير كافٍ. الرصيد المتاح: 18,000 ETB. يرجى دفع المبلغ قبل 30 يونيو" (Insufficient balance. Available: 18,000 ETB. Please pay before June 30.)

**Recovery:** The citizen can top up their wallet. Partial tax payment is not supported — the full amount must be paid in a single transaction.

### Duplicate tax payment — citizen pays the same tax assessment twice
**System Behavior:** The idempotency key is constructed from `tax_year + tax_type + TIN + assessment_number`. The second payment returns `DUPLICATE` and is rejected.

**User Impact:** The citizen sees "تم دفع هذه الضريبة مسبقاً" (This tax has already been paid.)

**Recovery:** The second payment is rejected before any debit occurs. If a duplicate payment somehow processes, an automatic refund is initiated within 48 hours.

### Wrong TIN (Taxpayer Identification Number) entered — 9-digit number instead of 10 digits
**System Behavior:** The TIN validation check verifies the format and length. A 9-digit number does not match the 10-digit TIN format. The payment is blocked.

**User Impact:** The citizen sees "رقم TIN غير صحيح. يجب أن يتكون من 10 أرقام" (Invalid TIN. Must be 10 digits.)

**Recovery:** The UI pre-validates the TIN format by tax type and provides format hints as the user types.

### Welfare disbursement to a deceased beneficiary
**System Behavior:** The disbursement engine detects that the beneficiary's account has had no recent activity for 6 months. The transaction is flagged as `BENEFICIARY_INACTIVE`.

**User Impact:** The disbursement is held. The funds are not sent. "المستفيد غير نشط. التحقق مطلوب" (Beneficiary is inactive. Verification required.)

**Recovery:** The social welfare office verifies the beneficiary's status. If deceased, the funds are returned to the government. If alive, the beneficiary must complete a KYC re-verification.

### Partial disbursement — government sends 3,000 ETB welfare to the wrong phone number
**System Behavior:** The government's file contains a typo in the beneficiary's phone number. The disbursement is sent to the wrong Beza wallet.

**User Impact:** The intended beneficiary does not receive the funds. The wrong number is credited.

**Recovery:** The reconciliation system detects unclaimed disbursements within 7 days. The funds are clawed back from the wrong recipient. The correct disbursement is reissued.

### Tax overpayment — citizen pays 50,000 ETB but owes only 42,000 ETB
**System Behavior:** The system accepts the full payment. The excess 8,000 ETB is recorded as a tax credit on the citizen's account.

**User Impact:** The citizen sees "فائض دفع: 8,000 ETB. يمكن استخدامه للدفعة القادمة أو استرداده" (Overpayment: 8,000 ETB. Can be used for the next payment or refunded.)

**Recovery:** The citizen can apply the excess to the next tax period or request a refund. Refunds are processed within 30 days.

## 3. External Dependency Failures

### MoF (Ministry of Finance) tax system API unavailable during filing season
**System Behavior:** All tax payments are blocked. A status page shows the outage. The system queues payment requests for later processing.

**User Impact:** Citizens cannot pay their taxes. Late payment penalties may apply. "نظام وزارة المالية غير متاح حالياً" (Ministry of Finance system is currently unavailable.)

**Recovery:** The MoF typically extends the filing deadline if the system is down for more than 4 hours. Beza provides proof of queued payment for penalty waiver requests.

### Ethiopian Revenue and Customs Authority (ERCA) API timeout
**System Behavior:** The tax filing and payment API times out. Business taxpayers cannot file or pay their VAT returns.

**User Impact:** The business taxpayer sees "خدمة مصلحة الضرائب غير متوفرة" (Tax authority service is unavailable.)

**Recovery:** The ERCA accepts paper filings during system outages. Beza assists with preparing the digital filing for later submission.

### NID (National ID) verification API down during welfare registration
**System Behavior:** The identity verification step for new welfare beneficiaries fails because the NID API is unavailable. The registration cannot proceed.

**User Impact:** The citizen seeking welfare benefits sees "التحقق من الهوية الوطنية غير متاح" (National ID verification is unavailable.)

**Recovery:** The registration continues with manual ID verification. The welfare office completes the verification within 48 hours.

### CFE (Ethio Telecom) SMS downtime for government payment confirmations
**System Behavior:** The payment confirmation SMS cannot be sent because the CFE network is down. The payment is processed successfully on Beza.

**User Impact:** The citizen pays the tax but does not receive the SMS confirmation. The in-app status shows success. "تم الدفع بنجاح" (Payment successful.)

**Recovery:** The SMS is retried for up to 24 hours. The in-app receipt is always available with a downloadable PDF for official purposes.

### Pension system (Public Servants Social Security) API unavailable
**System Behavior:** The pension contribution reporting to the social security authority is delayed. The contributions are still collected and held in Beza.

**User Impact:** No immediate impact on employees. "تقديم تقرير التأمينات الاجتماعية مؤجل" (Social insurance report is deferred.)

**Recovery:** The report is generated and queued. It is submitted when the API is restored. Any late submission fee is waived.

## 4. Data Consistency Failures

### Tax payment recorded in Beza but not in MoF system — 25,000 ETB discrepancy
**System Behavior:** The payment is marked as completed in the Beza database. The MoF system does not show the payment due to a communication failure. The taxpayer's record at MoF shows an outstanding balance.

**User Impact:** The citizen may receive a late payment notice from the MoF despite having paid. "تم الدفع ولكن لم يتم تحديث سجل وزارة المالية" (Payment made but MoF record not updated.)

**Recovery:** A weekly reconciliation between Beza and the MoF detects the missing record. The payment is re-submitted to the MoF. The citizen is provided with proof of payment for any penalty disputes.

### Cache inconsistency — tax due shown as 30,000 ETB but actual is 25,000 ETB
**System Behavior:** The cached tax assessment is stale. The citizen initiates a payment of 30,000 ETB based on the cached amount.

**User Impact:** The citizen pays 30,000 ETB instead of 25,000 ETB. The overpayment is 5,000 ETB.

**Recovery:** The overpayment is recorded as a tax credit on the citizen's account. The citizen can request a refund or apply it to the next tax period.

### Welfare disbursement event lost — 3,000 ETB credit event not consumed by wallet service
**System Behavior:** The government's disbursement instruction is processed by Beza. The Kafka event carrying the individual credit is lost. The beneficiary is not credited.

**User Impact:** The beneficiary does not receive the 3,000 ETB welfare payment. They assume the disbursement is late.

**Recovery:** The dead-letter queue consumer replays the event within 15 minutes. The beneficiary is credited automatically.

### Dual-write between Beza and MoF fails partially
**System Behavior:** The payment is recorded in Beza's database. The write to the MoF system fails. The Saga pattern initiates a compensation — the Beza record is reversed.

**User Impact:** The citizen sees the payment in their history, followed by a reversal. "تم إلغاء الدفع بسبب خطأ في النظام الحكومي" (Payment cancelled due to a government system error.)

**Recovery:** The compensation is completed within 30 seconds. The citizen is asked to retry when the MoF system is available.

### Tax credit balance corrupted — citizen shows 15,000 ETB credit but actual is 10,000 ETB
**System Behavior:** A database corruption error inflates the citizen's accumulated tax credit from 10,000 ETB to 15,000 ETB. The citizen applies the incorrect credit.

**User Impact:** The citizen underpays their tax by 5,000 ETB by using the inflated credit.

**Recovery:** The monthly reconciliation detects the credit balance mismatch. The correction is applied. The citizen is billed for the 5,000 ETB underpayment, but interest is waived.

## 5. Security Failures

### Fraud false positive — business paying 500,000 ETB quarterly VAT flagged
**System Behavior:** The AML rules engine triggers on the large payment amount combined with an early payment pattern (the business usually pays late). The payment is placed in `PENDING_REVIEW`.

**User Impact:** The business sees "دفع الضريبة قيد المراجعة" (Tax payment under review.) There is a risk of late payment penalties.

**Recovery:** The compliance team reviews the business's payment history within 4 hours. The early payment pattern is recognized as legitimate. The payment is released.

### Fraud false negative — tax refund fraudster claims 100,000 ETB refund with fake documents
**System Behavior:** The refund is processed without thorough document verification. The fraudster submits fake tax assessment documents and the refund is approved.

**User Impact:** Beza and the MoF lose 100,000 ETB. Legitimate taxpayers are not directly affected.

**Recovery:** An AI document verification system detects the fake MoF stamp in a retrospective audit. The recovery rate is approximately 60% through law enforcement channels.

### Unauthorized access to tax payment records
**System Behavior:** A competitor gains access to a business taxpayer's payment history through compromised credentials.

**User Impact:** The business's tax payment patterns are exposed. This is a competitive intelligence loss.

**Recovery:** Tax record access requires MFA plus a designated business role. An audit log tracks all views of tax records. The affected business is notified of the suspicious access.

### Welfare beneficiary impersonation — fraudster claims 3,000 ETB monthly welfare using a fake NID
**System Behavior:** The fraudster's fake NID passes the initial automated verification. The fraudster receives 3,000 ETB per month for 12 months. Total stolen: 36,000 ETB.

**User Impact:** The legitimate beneficiary with the same name may be flagged as a duplicate. The real beneficiary's benefits are disrupted.

**Recovery:** In-person biometric verification at the welfare office is implemented. Facial recognition matching against the NID database is introduced. The fraudster is identified and reported.

### Fake government fee portal — phishing site mimicking Beza government payment page
**System Behavior:** A phishing website mimics the Beza government payment page. The site collects card details and personal information from unsuspecting users.

**User Impact:** 500 citizens pay fake "government fees" to the attacker. At 5,000 ETB per victim, the total loss is 2.5 million ETB.

**Recovery:** Beza sends an SMS alert to all users: "Beza لا يطلب معلومات البطاقة عبر البريد الإلكتروني" (Beza does not ask for card information via email.) Domain monitoring services are engaged for takedown.

## 6. Business Logic Failures

### Tax deadline missed due to Beza processing delay
**System Behavior:** The citizen pays on April 30 at 11:55 PM. The MoF receives the payment on May 1 due to a processing delay. A late penalty is assessed.

**User Impact:** The citizen sees "غرامة تأخير: 500 ETB" (Late penalty: 500 ETB.) through the MoF system.

**Recovery:** Beza's policy states that the payment timestamp at the Beza server determines timeliness, not the MoF receipt timestamp. Beza covers the late penalty.

### Incorrect tax rate applied — VAT calculated at 18% instead of 15%
**System Behavior:** The tax engine applies the wrong VAT rate for the goods category. The citizen is charged 18,000 ETB VAT on a 100,000 ETB purchase instead of 15,000 ETB.

**User Impact:** The citizen overpays 3,000 ETB in VAT. "الضريبة المحتسبة: 18%" (Tax calculated: 18%.)

**Recovery:** The refund is initiated automatically when the error is detected. The tax rate table is corrected. All affected transactions are audited and corrected.

### Welfare means-testing miscalculation — beneficiary's income incorrectly calculated
**System Behavior:** The income aggregation engine misses a deduction. The calculated income is above the threshold. The beneficiary is denied benefits.

**User Impact:** The beneficiary is denied 3,000 ETB per month in welfare. "غير مؤهل للدعم بناءً على الدخل المحتسب" (Not eligible based on calculated income.)

**Recovery:** The beneficiary appeals with corrected income documents. The review is completed within 7 days. Retroactive payments are made if the appeal is approved.

### Government fee for passport application — fee paid but application not linked
**System Behavior:** The passport fee of 5,000 ETB is paid through Beza. The payment reference is not transmitted to the immigration system due to a data mapping error.

**User Impact:** The citizen's passport application is not processed. The citizen thinks the application is in progress.

**Recovery:** The immigration office runs a daily reconciliation that links payments to applications. The application is processed without delay. The citizen is notified of the correction.

### Tiered tax relief not applied — new business (first 2 years) eligible for 50% reduction
**System Behavior:** The tax engine does not have the `new_business_flag` set for this taxpayer. The full tax amount is charged instead of the 50% reduction.

**User Impact:** The new business pays 100,000 ETB instead of 50,000 ETB. "لم يتم تطبيق الإعفاء الضريبي للشركات الجديدة" (New business tax relief was not applied.)

**Recovery:** The 50,000 ETB overpayment is refunded within 30 days. The tax rule is updated in the engine to include the new business flag.

## 7. Performance & Scalability Failures

### Tax payment spike — 100,000 concurrent payments on deadline day
**System Behavior:** On the tax filing deadline (June 30), 100,000 taxpayers submit payments simultaneously. The MoF API throttles to 500 requests per second. The payment queue grows to 50,000 pending submissions.

**User Impact:** Taxpayers see "قائمة الانتظار: 25,000" (Queue: 25,000) with an estimated 10-minute wait. Payment confirmations are delayed.

**Recovery:** Tax payments are accepted and confirmed on the Beza side immediately. Submission to MoF is queued. The MoF queue is processed at the throttle rate. Taxpayers receive Beza confirmation as proof of timely payment.

### Welfare disbursement batch — 500,000 beneficiaries processed
**System Behavior:** The monthly welfare disbursement batch processes 500,000 beneficiaries. Each requires a wallet credit. The wallet service handles 100 credits per second. The batch takes 1.5 hours.

**User Impact:** Beneficiaries at the end of the batch receive funds 1.5 hours after the start. "جاري صرف الدعم" (Disbursement in progress) is shown.

**Recovery:** The disbursement is parallelized across 50 worker threads (10,000 beneficiaries each). Processing time is reduced to 15 minutes. Beneficiaries are notified in batches of 10,000 as each group is processed.

### Government fee inquiry — 50,000 concurrent inquiries for passport fees
**System Behavior:** The government fee inquiry API handles 50,000 concurrent requests. The MoF legacy system can process only 200 requests per second.

**User Impact:** Citizens experience 30-second delays when inquiring about passport or license fees. "جاري الاستعلام..." (Inquiring...) takes longer than expected.

**Recovery:** Fee schedules are cached in Redis with 1-hour TTL. The MoF legacy system is polled once per hour. 99% of inquiries are served from cache instantly.

## 8. Operational Failures

### Deployment rollback — v4.7.0 incorrectly calculates tax credit for businesses
**System Behavior:** The canary deployment detects a 20% decrease in tax revenue (over-crediting). The automated rollback is triggered.

**User Impact:** Approximately 500 businesses receive incorrect tax credits averaging 2,000 ETB extra. Total over-credit: 1,000,000 ETB.

**Recovery:** The rollback completes within 2 minutes. The tax credit calculation is corrected. The excess credits are adjusted in the next tax period.

### Configuration error — welfare amount set to 30,000 ETB instead of 3,000 ETB
**System Behavior:** A configuration change sets the monthly welfare disbursement amount to 30,000 ETB instead of 3,000 ETB. The error persists for 1 hour before detection.

**User Impact:** 5,000 beneficiaries receive 30,000 ETB each instead of 3,000 ETB. Total overpayment: 135,000,000 ETB.

**Recovery:** The configuration is reverted within 1 hour. Beneficiaries are notified of the error. A repayment plan is offered with 12-month installments for amounts above 10,000 ETB. A hard cap validation is added.

### MoF tax rate table update missed — new VAT rate not applied for 2 weeks
**System Behavior:** The MoF changes the VAT rate for digital services from 15% to 18%. Beza's tax engine is not updated for 2 weeks.

**User Impact:** Digital service providers are undercharged by 3% VAT for 2 weeks. The government loses tax revenue.

**Recovery:** The tax rate table is updated. A retrospective adjustment is applied. The undercharged amount is collected in the next tax period. An automated rate table sync with MoF is implemented.

## 9. Recovery Time Objectives Summary

| Failure Category | Detection Time | Recovery Time | RPO | Maximum Impact |
|-----------------|----------------|---------------|-----|----------------|
| Network (transient) | < 1 second | < 30 seconds | 0 | Single payment delayed |
| Network (DNS outage) | 30 seconds | < 5 minutes | 0 | All gov payments blocked |
| Transaction failure | < 100ms | < 5 seconds | 0 | Single payment failed |
| External dependency | < 10 seconds | < 4 hours | 0 | MoF/Central system down |
| Data inconsistency | < 5 minutes | < 2 hours | < 5 seconds | Tax payment discrepancy |
| Security incident | < 1 minute | < 4 hours | 0 | Payment held for review |
| Business logic | < 1 hour | < 24 hours | 0 | Wrong tax rate applied |
| Performance degradation | < 1 minute | < 5 minutes | 0 | Slow inquiry/ payment |
| Operational (deployment) | < 5 minutes | < 10 minutes | < 1 minute | Wrong welfare amount |

## Changelog

| Date | Version | Changes |
|------|---------|---------|
| 2026-05-29 | 1.0 | Initial release — 7 categories covering network, transactions, external dependencies, data consistency, security, business logic, and performance failures for government payments feature |

---

*Version: 1.0 | Last updated: 2026-05-29 | Owner: Government Engineering Team*
