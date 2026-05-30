# 31. Remittance — Failure Scenarios

> **Purpose:** Document every failure mode for the Beza Remittance feature serving the Syrian diaspora — cross-border money transfers from the EU and UAE to Syria. Covers sender debit (EUR/USD), FX conversion to SYP, recipient credit in Syria, and corridor-specific failures. Uses real SYP/EUR/USD amounts, Syrian banks (BBS, Bemo, Commercial Bank of Syria), diaspora corridors (Germany→Syria, UAE→Syria, Sweden→Syria), and Arabic messaging. Includes hawala comparison and CBS (Central Bank of Syria) regulatory references.

---

## 1. Network Failures

### Internet cut after sender payment confirmed in Berlin but before FX lock completes
**System Behavior:** The sender's EUR is debited from the payment gateway (Stripe or local EU payment method). The FX rate lock call to the CBS rate feed (Central Bank of Syria official rate) times out. The remittance is stuck in `FX_LOCK_PENDING` status. The sender's funds are held in a suspense account.

**User Impact:** The sender in Berlin sees "تم خصم المبلغ. في انتظار تأكيد سعر الصرف من مصرف سورية المركزي" (Amount deducted. Awaiting exchange rate confirmation from the Central Bank of Syria.) The recipient in Damascus sees nothing.

**Recovery:** The rate lock is retried with exponential backoff (5s, 15s, 45s). If the CBS feed is unreachable for more than 2 minutes, the system holds the sender's funds and continues retrying for 15 minutes. On success, the rate is locked at the next available CBS rate within a tolerance of the original quote.

### API timeout (>5s) during recipient wallet validation in Syria
**System Behavior:** The remittance service times out while checking if the recipient's wallet in Syria is active. The system uses a fail-open strategy with a risk flag — it assumes the wallet is valid but marks the transaction for additional verification.

**User Impact:** The sender in Stockholm proceeds with a warning "قد تتأكد صحة حساب المستلم في سورية عند المعالجة" (The recipient account in Syria will be verified during processing.)

**Recovery:** If the recipient wallet is found to be invalid during processing, the remittance is held in `PENDING_RECIPIENT_CHECK` status. The sender is notified "يرجى تأكيد معلومات المستلم في سورية" (Please confirm the recipient's information in Syria.) The sender can correct the details or cancel for a full refund.

### DNS failure for remittance-api.beza.sy during corridor partner callback
**System Behavior:** The corridor partner (e.g., a payout partner in Syria) cannot send the status callback because the remittance API DNS is unreachable. The remittance is marked as `CALLBACK_PENDING`.

**User Impact:** No immediate user impact is visible. However, status updates are delayed until the callback is successfully processed. The sender in Dubai sees no update for the UAE→Syria remittance.

**Recovery:** The partner retries the callback every 5 minutes for up to 1 hour. If the callback still fails after 1 hour, the operations team in Damascus manually reconciles the remittance status by contacting the partner directly.

### WebSocket disconnect during real-time remittance status tracking from Stockholm
**System Behavior:** The sender's app in Stockholm detects the WebSocket disconnection and falls back to REST API polling, checking for status updates every 10 seconds.

**User Impact:** The sender sees "جاري التحديث..." (Updating...) on the status tracking page instead of a real-time progress bar for the Sweden→Syria remittance. The status may appear to be delayed by up to 10 seconds.

**Recovery:** The WebSocket client reconnects automatically with exponential backoff. Once reconnected, all missed status events are replayed from the server. The status display is updated to the latest state immediately.

### Intermittent packet loss on EUR to SYP corridor API (Stripe EU to Beza Syria)
**System Behavior:** TCP retransmission between the EU-based payment processor and the Syria-region servers causes a latency spike from 5 seconds to 15 seconds. The Kafka producer is configured with `max.in.flight.requests=1` to preserve message ordering during retransmission.

**User Impact:** The remittance processing from Germany to Syria is delayed by 10-15 seconds. The sender in Berlin sees the processing spinner for longer than usual.

**Recovery:** The producer retries with exponential backoff. If delivery fails after 3 retries, the message is sent to a dead-letter queue on SQS for operations review. The ops team in Damascus follows up with the sender.

### Syrian internet disruption affecting remittance payout confirmation
**System Behavior:** The Syrian Telecommunications Establishment (STE) experiences a regional outage affecting the Damascus data center. The remittance payout confirmation from the Syrian payout partner cannot be received. The remittance is stuck in `PAYOUT_PENDING`.

**User Impact:** The sender in Dubai sees the status "في انتظار تأكيد الاستلام في سورية" (Awaiting receipt confirmation in Syria.) The recipient in Syria does not receive their SMS notification.

**Recovery:** The system retries the payout confirmation callback every 60 seconds. The Syrian partner queues confirmations locally. When the STE internet is restored, all queued confirmations are sent within 2 minutes. The sender receives a push notification when the recipient confirms receipt.

## 2. Transaction Failures

### Insufficient sender balance — sender in Berlin tries to send €500 EUR but wallet has €420 EUR
**System Behavior:** The pre-validation checks `eur_balance >= amount + fee`. The balance of €420 EUR is less than €500 + €15 fee. The transaction is rejected at the gateway level.

**User Impact:** The sender sees "رصيد غير كافٍ. الرصيد المتاح: €420 يورو" (Insufficient balance. Available balance: €420 EUR.)

**Recovery:** The UI shows the maximum sendable amount including the fee calculation. The sender can top up their EUR wallet using a linked bank card (SEPA transfer) or bank transfer before retrying the remittance to Syria.

### Double send attempt — sender in Stockholm submits the same remittance twice
**System Behavior:** The idempotency key is constructed as `sender_remittance_hash(sender_id, recipient_phone_syria, amount, currency)`. The second request returns HTTP 409 `DUPLICATE_REMITTANCE`.

**User Impact:** The sender in Stockholm sees "تم إرسال هذه الحوالة إلى سورية مسبقاً. تحقق من سجل الحوالات" (This remittance to Syria has already been sent. Please check your remittance history.)

**Recovery:** The first transaction processes normally. The second request is silently discarded. No duplicate charge is made to the sender's payment method.

### Duplicate idempotency key reused across different remittances (race condition) from Dubai to Damascus
**System Behavior:** The idempotency key is checked in Redis with a 48-hour TTL. If the same key is reused with different remittance parameters, the system returns HTTP 422 `IDEMPOTENCY_MISMATCH`.

**User Impact:** The sender in Dubai sees "خطأ في إعادة المحاولة. يرجى بدء حوالة جديدة من دبي إلى دمشق" (Retry error. Please start a new remittance from Dubai to Damascus.)

**Recovery:** The client SDK must generate a new UUIDv4 idempotency key for each unique remittance. The SDK enforces strict no-reuse policy through a monotonically increasing counter.

### CBS FX rate expires during remittance — rate locked at 13,500 SYP/EUR but lock validity (30s) expires
**System Behavior:** The orchestrator detects that the rate lock from the Central Bank of Syria has expired. It requests a fresh rate from the CBS rate feed. If the new rate differs by more than 2% from the original rate, the system requires the sender to re-approve.

**User Impact:** The sender in Berlin sees "انتهت صلاحية سعر الصرف من مصرف سورية المركزي. السعر الجديد: 13,800 ل.س/يورو. هل ترغب في المتابعة؟" (The CBS exchange rate has expired. The new rate is 13,800 SYP/EUR. Would you like to continue?)

**Recovery:** The sender must re-confirm the new CBS rate. If the rate change is less than 2%, the remittance proceeds automatically. If greater than 2%, explicit consent is required through an in-app confirmation dialog.

### Partial credit — recipient wallet in Aleppo receives 850,000 SYP instead of 1,000,000 SYP (fee calculation error)
**System Behavior:** The fee calculation engine double-counts a processing fee. The recipient in Aleppo receives 850,000 SYP instead of the expected 1,000,000 SYP. The discrepancy is detected by the reconciliation system.

**User Impact:** The recipient in Aleppo sees 850,000 SYP credited. Later, a correction entry appears "تم تعديل المبلغ: +150,000 ل.س" (Adjustment made: +150,000 SYP.)

**Recovery:** The operations team in Damascus manually triggers the correction within 2 hours of detection. The root cause — a fee double-count in the fee calculation service — is identified and fixed. The sender in Berlin and recipient in Aleppo are both notified via push notification and SMS.

### Remittance declined by sanctions compliance (EU/OFAC sanctions screening for Syria)
**System Behavior:** The sanctions screening engine returns a name similarity hit above the threshold against the EU Syria sanctions list or OFAC SDN list. The transaction is set to `REJECTED_COMPLIANCE`. No funds are moved.

**User Impact:** The sender in Berlin sees "تم رفض الحوالة بسبب متطلبات الامتثال للعقوبات. يرجى الاتصال بخدمة العملاء" (The remittance has been rejected due to sanctions compliance requirements. Please contact customer service.)

**Recovery:** The sender can submit additional documentation to prove their identity and the legitimacy of the transaction (e.g., family relationship proof). The compliance team reviews the documentation within 24 hours per CBS and EU regulatory requirements. If cleared, the remittance is released.

## 3. External Dependency Failures

### CBS (Central Bank of Syria) rate feed unavailable for SYP conversion
**System Behavior:** The remittance service uses the last cached CBS rate from Redis, which is valid for 30 minutes. If no cached rate is available because the TTL has expired, all new remittances to Syria are blocked.

**User Impact:** The sender in Stockholm sees "أسعار الصرف من مصرف سورية المركزي غير متوفرة حالياً. حاول مرة أخرى بعد 30 دقيقة" (Exchange rates from the Central Bank of Syria are currently unavailable. Please try again in 30 minutes.)

**Recovery:** The operations team in Damascus contacts the CBS directly by phone. A manual rate upload is performed through the admin panel with CBS telephone authorization. The service resumes automatically when the feed is restored.

### Stripe/payment gateway API timeout for EUR collection from Germany
**System Behavior:** The Stripe `payment_intent.confirm` call from the sender in Berlin hangs without a response. The remittance remains in `FUNDING_PENDING` status. The Stripe webhook endpoint waits for confirmation.

**User Impact:** The sender in Berlin sees "جاري تأكيد الدفع من ألمانيا. قد يستغرق حتى دقيقتين" (Payment confirmation from Germany in progress. This may take up to 2 minutes.)

**Recovery:** The Stripe webhook retries the confirmation. If the timeout exceeds 2 minutes, the transaction is marked as `FUNDING_FAILED`. The sender can retry with a different card or SEPA bank transfer. The CBS rate lock is extended for 15 minutes.

### SMS provider (Syriatel/MTN) unavailable for remittance confirmation to recipient in Syria
**System Behavior:** The SMS delivery to the recipient's phone in Syria is queued on SQS. The system falls back to the alternative Syrian telecom operator (Syriatel if MTN fails, or vice versa). A push notification via FCM is sent as the primary delivery channel.

**User Impact:** The sender in Dubai and the recipient in Damascus may not receive the SMS confirmation immediately. The in-app message "تم إرسال الإشعار عبر التطبيق" (The notification has been sent via the app) is shown.

**Recovery:** The SMS is queued and retried for up to 24 hours through both Syriatel and MTN SMPP connections. If SMS continues to fail after 24 hours, a voice call fallback via Twilio is initiated for critical remittance confirmations. The recipient's Beza in-app notification is the primary confirmation channel.

### Partner bank in Syria (Commercial Bank of Syria) API timeout for direct bank deposit
**System Behavior:** The Commercial Bank of Syria API for direct deposit into the recipient's bank account does not respond within the 30-second timeout. The remittance is marked as `DEPOSIT_PENDING`.

**User Impact:** The recipient in Syria sees "في انتظار تأكيد المصرف التجاري السوري" (Awaiting confirmation from the Commercial Bank of Syria.) The funds are not yet available in their bank account.

**Recovery:** A poller checks the bank statement via the inquiry API every 60 seconds for up to 2 hours. On receiving confirmation from the bank, the status is updated. If the bank ultimately rejects the deposit, the funds are disbursed to the recipient's Beza wallet instead via the BBS network.

### SWIFT network delay for corridor partner settlement (Dubai to Damascus)
**System Behavior:** The SWIFT gpi tracker shows that the UAE dirham to SYP settlement payment is in progress but has not been confirmed by the corresponding Syrian bank for more than 4 hours. An ops alert is triggered.

**User Impact:** The sender in Dubai sees the status "قيد التحويل الدولي من دبي إلى دمشق" (International transfer from Dubai to Damascus in progress) for longer than the expected 2-hour window.

**Recovery:** The operations team monitors the SWIFT gpi tracker. If the transfer exceeds 24 hours, a tracer is raised with the correspondent bank in Dubai and the receiving bank in Syria. The sender is provided with the SWIFT reference number for direct follow-up.

### Hawala comparison — traditional hawala network in Syria faster than digital remittance
**System Behavior:** A sender in Berlin compares the Beza digital remittance (2-hour processing, 13,500 SYP/EUR rate, €5 fee) against the traditional hawala network operating between Berlin and Damascus (30-minute processing, 13,200 SYP/EUR rate, €3 fee). The Beza system logs the comparison but cannot match the hawala speed.

**User Impact:** The sender sees a notification "تستغرق الحوالة الرقمية وقتاً أطول من الحوالة التقليدية. لكنها تخضع لإشراف مصرف سورية المركزي وتوفر حماية أكبر" (Digital remittance takes longer than traditional hawala. However, it is regulated by the Central Bank of Syria and offers greater protection.)

**Recovery:** The product team analyzes the speed gap vs hawala and optimizes the processing pipeline. A "speed boost" option with a premium fee is introduced to reduce processing time to 30 minutes by prioritizing the transaction in the processing queue.

## 4. Data Consistency Failures

### DB write failure after EUR debit but before SYP credit log for Syria remittance
**System Behavior:** The ledger records the EUR debit from the sender in Berlin. The SYP credit write to the recipient's wallet in Damascus fails. The Saga pattern detects the inconsistency within 2 seconds and triggers a compensatory debit reversal.

**User Impact:** The sender in Berlin sees a reversal notification "تم إلغاء الحوالة إلى سورية وإعادة €500 يورو" (The remittance to Syria has been cancelled and €500 EUR has been returned.)

**Recovery:** The retry queue attempts the failed credit write 3 times (5s, 30s, 120s). If all retries fail, the compensation is finalized. The operations team in Damascus is notified and performs a manual fix.

### Cache inconsistency — FX rate shown as 13,500 SYP/EUR but actual CBS rate is 13,800 at confirmation
**System Behavior:** The rate preview on the remittance initiation screen is read from the cache (TTL 30 seconds). The actual rate lock at confirmation time uses the current market rate from the CBS feed.

**User Impact:** The sender in Stockholm sees "تم تأكيد سعر الصرف من مصرف سورية المركزي: 13,800 ل.س/يورو" on the confirmation screen, which may differ from the previewed rate of 13,500.

**Recovery:** The rate preview explicitly shows a disclaimer "السعر يصدر عن مصرف سورية المركزي وقد يتغير" (The rate is issued by the Central Bank of Syria and is subject to change.) The actual CBS rate is locked at the time of confirmation. If the difference exceeds 0.5%, a warning is displayed before the sender confirms.

### Remittance event lost in Kafka — SYP credit event never published to wallet service in Syria
**System Behavior:** The remittance status shows "مكتملة" (Completed) in the remittance service, but the credit event was never published to Kafka. The wallet service in Syria never receives the instruction to credit the recipient in SYP.

**User Impact:** The sender in Dubai thinks the remittance was successful. The recipient in Damascus has no funds. This is a silent data loss scenario.

**Recovery:** A dead-letter queue consumer checks for unprocessed events every 5 minutes. A reconciliation job between the remittance and wallet services runs every 1 hour. Any missing credits are detected and replayed. The recipient is credited retroactively in SYP at the original CBS rate.

### Dual-write to remittance DB and Syria wallet DB fails partially
**System Behavior:** The remittance record is created in the remittance database (EU region). The SYP credit write to the Syria wallet database fails silently due to a database connection pool exhaustion. No compensatory action is taken.

**User Impact:** The recipient in Syria is not credited. The sender in Berlin is not notified. This is a potential data loss of €500 EUR (approximately 6,750,000 SYP at CBS rate).

**Recovery:** A reconciliation batch detects orphan remittance records (those marked as completed but with no corresponding SYP wallet credit) every 15 minutes. An automatic credit is triggered. An incident is raised for the engineering team in Damascus.

### Transaction log corrupted due to disk I/O error on Syria region database
**System Behavior:** A database page corruption occurs on the `remittance_transactions` table in the Syria region due to a disk I/O error. The specific row containing a 1,000,000 SYP remittance from Stockholm to Aleppo becomes unreadable.

**User Impact:** The sender in Stockholm may see the remittance missing from their transaction history. The error message "حدث خطأ في تحميل سجل الحوالات إلى سورية" (An error occurred while loading the remittance history to Syria) may appear.

**Recovery:** The DBA restores the corrupted page from the PostgreSQL Write-Ahead Log (WAL) within 5 minutes. The corrupted row is reconstructed from the immutable event log. The recovery point objective (RPO) is less than 1 second.

### Corridor rate mismatch — CBS rate for EUR/SYP differs from the agreed corridor rate for Germany→Syria
**System Behavior:** The CBS official rate for EUR/SYP is 13,500. However, the negotiated corridor rate for Germany→Syria remittances includes a special diaspora incentive of +50 SYP/EUR (effective rate 13,550). The system incorrectly applies the standard CBS rate instead of the corridor-specific rate.

**User Impact:** The recipient in Syria receives 6,750,000 SYP instead of 6,775,000 SYP for a €500 remittance. The sender in Berlin is overcharged by 25,000 SYP equivalent.

**Recovery:** The discrepancy is detected by the corridor rate audit batch (runs every 30 minutes). A correction of 25,000 SYP is credited to the recipient's wallet. The rate engine is fixed to apply corridor-specific rates for Germany→Syria. The sender receives a notification explaining the correction.

## 5. Security Failures

### Fraud false positive — diaspora sender in Berlin sending €2,000 to uncle in Damascus flagged by CBS AML rules
**System Behavior:** The AML rules engine triggers on the combination of amount > €1,000, a newly added beneficiary, and a country risk score for Syria. The transaction is placed in `PENDING_REVIEW` per CBS and EU AML regulations.

**User Impact:** The sender in Berlin sees "الحوالة قيد المراجعة وفقاً لتعليمات مكافحة غسل الأموال. سيتم إعلامك خلال 24 ساعة" (The remittance is under review per AML regulations. You will be notified within 24 hours.)

**Recovery:** The compliance team reviews the relationship proof (family connection documentation such as Syrian national ID matching). Typically cleared within 4 hours. The sender in Berlin is notified when the hold is lifted.

### Fraud false negative — compromised sender account in Stockholm used to send €5,000 to a mule in Syria
**System Behavior:** The behavioral model scores the transaction at 0.5, which is below the 0.7 threshold that would trigger an MFA challenge. The recipient in Syria is a known mule account but is not yet in the shared fraud database.

**User Impact:** The legitimate sender in Stockholm loses €5,000 EUR. The recipient in Syria cashes out through the agent network in Damascus within minutes of receiving the funds in SYP.

**Recovery:** The sender reports the fraud through the call center. Insurance covers 80% of the loss (up to €3,000). The mule's wallet in Syria is added to the shared fraud database. The fraud model is retrained with the new behavioral pattern. CBS is notified of the fraudulent account.

### Unauthorized access to remittance admin panel in Damascus
**System Behavior:** An attacker gains access to the operations dashboard in Damascus through compromised credentials. The attacker initiates a manual remittance of 5,000,000 SYP to an accomplice's wallet without a corresponding sender consent from the diaspora.

**User Impact:** A fictitious remittance is created from a fake sender profile. The accomplice in Damascus receives 5,000,000 SYP fraudulently. No legitimate diaspora user is directly impacted, but Beza's ledger shows an unbacked liability.

**Recovery:** The SIEM system alerts on any `ADMIN_MANUAL_REMITTANCE` event that does not have a corresponding support ticket. The admin panel requires MFA plus dual-approval from two authorized ops team members in Damascus for any manual financial operation. The attacker's access is revoked.

### Recipient identity theft — someone impersonates the recipient in Aleppo to claim the remittance
**System Behavior:** The recipient verification process detects a mismatch between the registered phone number (MTN Aleppo) and the Syrian Civil Registry database. The system escalates to enhanced verification.

**User Impact:** The legitimate recipient in Aleppo cannot claim the remittance from their brother in Berlin. The funds are stuck in `PENDING_VERIFICATION` status.

**Recovery:** The recipient must visit a Beza branch in Damascus or Aleppo in person with their original Syrian national ID (هوية شخصية) for biometric verification. Once verified, the remittance is released. False claimants are reported to the Syrian authorities.

### Man-in-the-middle on sender's email — phishing to redirect remittance from Dubai to Syria
**System Behavior:** An attacker intercepts the remittance confirmation email to the sender in Dubai and modifies the recipient's bank details. The attacker changes the Commercial Bank of Syria account number to their own account.

**User Impact:** The sender in Dubai believes the money is being sent to the correct recipient in Damascus. In reality, the funds are routed to the attacker's account in Syria.

**Recovery:** Beza sends a dual confirmation: an in-app notification plus an SMS to the sender's registered phone number (UAE mobile), both containing the full recipient details and the last 4 digits of the Syrian bank account. The sender must verify both channels before the remittance is finalized.

### Sender identity verification failure — passport from Syrian diaspora in Sweden cannot be validated
**System Behavior:** A sender in Stockholm provides their Syrian passport (expired) as identity verification. The KYC system cannot validate the passport against the Syrian Civil Registry due to restricted access to Syrian government databases.

**User Impact:** The sender in Stockholm sees "تعذر التحقق من جواز السفر السوري. يرجى تقديم وثيقة هوية سارية المفعول" (Unable to verify Syrian passport. Please provide a valid identification document.)

**Recovery:** The sender can provide a valid Swedish residence permit or EU passport as alternative identification. If only a Syrian passport is available, a manual verification process with a video call is initiated. The Beza compliance team in Damascus reviews the document within 48 hours.

## 6. Business Logic Failures

### CBS rate lock expired before conversion — 13,500 SYP/EUR locked at 10:00:00, valid 30s, conversion at 10:00:45
**System Behavior:** The system detects that the CBS rate lock has expired (current time > expires_at). A fresh rate of 13,800 is fetched from the CBS feed. The change (2.22%) exceeds the 2% auto-approval threshold.

**User Impact:** The sender in Berlin sees "تم تحديث سعر الصرف من مصرف سورية المركزي. السعر الجديد: 13,800 ل.س/يورو. هل توافق؟" (The CBS exchange rate has been updated. The new rate is 13,800 SYP/EUR. Do you agree?)

**Recovery:** The UI shows the old CBS rate versus the new rate side by side. The sender can accept or cancel. If cancelled, the full €500 EUR is refunded within 5 minutes to their original payment method.

### Recipient wallet in Syria frozen at the time of credit (CBS compliance hold)
**System Behavior:** The pre-credit check detects that the recipient's wallet status in Syria is `FROZEN` due to a CBS compliance hold. The SYP credit is held in `PENDING_RECIPIENT_CHECK` status. The funds are not lost but are not accessible to the recipient.

**User Impact:** The recipient in Homs sees that there is an incoming remittance from their family in Sweden but cannot access the funds. The message is "المبلغ متاح بعد إزالة تجميد الحساب من قبل مصرف سورية المركزي" (The amount will be available after the account is unfrozen by the Central Bank of Syria.)

**Recovery:** The system sends a notification to the recipient "قم بإزالة تجميد محفظتك لاستلام 500,000 ل.س من السويد" (Unfreeze your wallet to receive 500,000 SYP from Sweden.) Once the wallet is unfrozen, the credit is released automatically.

### Sender's bank card declined in UAE after remittance initiated to Syria
**System Behavior:** The Stripe `payment_intent.confirm` returns `card_declined` for the sender in Dubai. The remittance status changes to `FUNDING_FAILED`. The CBS rate lock is released.

**User Impact:** The sender in Dubai sees "تم رفض البطاقة من البنك في الإمارات. يرجى استخدام بطاقة أخرى" (Card declined by the UAE bank. Please use another card.)

**Recovery:** The sender can retry with a different card within the 15-minute CBS rate lock extension window. If no successful payment is received within 15 minutes, the remittance is cancelled and the CBS rate lock is fully released. Alternative payment methods (UAE bank transfer, Apple Pay) are suggested.

### Daily remittance limit exceeded for Germany→Syria corridor (€500,000 EUR daily cap)
**System Behavior:** The aggregate daily volume for the Germany→Syria corridor reaches the €500,000 EUR daily cap set by CBS regulations. New remittance requests from Berlin are placed in `QUEUED_FOR_NEXT_DAY` status.

**User Impact:** The sender in Berlin sees "تم تجاوز الحد اليومي للحوالات من ألمانيا إلى سورية. ستتم المعالجة غداً" (The daily remittance limit from Germany to Syria has been reached. Your remittance will be processed tomorrow.)

**Recovery:** The queued remittances are processed at midnight Syria time (UTC+3). The sender receives a notification "تمت معالجة حوالاتك إلى سورية" (Your remittance to Syria has been processed.)

### Beneficiary information mismatch — name on Syrian national ID vs recipient wallet name
**System Behavior:** The compliance check calculates `beneficiary_name_match` at 65%, which is below the 80% threshold. The sender in Berlin entered the recipient's name slightly differently from the registered name on the Syrian national ID.

**User Impact:** The recipient in Damascus sees "يرجى تأكيد اسم المستلم في المحفظة ليتطابق مع الاسم في الهوية الشخصية السورية" (Please confirm that the recipient name in the wallet matches the name on the Syrian national ID card.)

**Recovery:** The recipient updates their wallet name through the in-app KYC flow by submitting a new photo of their Syrian national ID. The compliance team in Damascus reviews and approves the name change within 2 hours. Once approved, the remittance is released.

### CBS daily aggregate limit for Syria inbound remittances reached
**System Behavior:** The aggregate daily inbound remittance volume to Syria reaches the CBS-regulated cap for the day. All new inbound remittances are queued. The system logs `CBS_DAILY_AGGREGATE_LIMIT_REACHED`.

**User Impact:** The sender in Stockholm sees "تم الوصول إلى الحد الأقصى للحوالات الواردة إلى سورية لهذا اليوم وفقاً لمصرف سورية المركزي. ستتم المعالجة غداً" (The maximum inbound remittance limit to Syria for today per CBS has been reached. Processing will resume tomorrow.)

**Recovery:** Queued remittances are processed at midnight Syria time. The CBS limit is automatically reset. Senders receive a notification when their queued remittance is processed.

## 7. Performance & Scalability Failures

### Sudden traffic spike — 10x remittance volume during Eid al-Adha diaspora peak from Germany, Sweden, and UAE
**System Behavior:** The remittance service auto-scales from 20 to 150 pods across all corridors (Germany→Syria, Sweden→Syria, UAE→Syria). The CBS FX rate lock service must handle 800 concurrent rate lock requests. The CBS feed is polled every second instead of every 5 seconds.

**User Impact:** Diaspora senders in Berlin, Stockholm, and Dubai experience 5-6 second latency instead of the normal 1 second. CBS rate lock approvals may take up to 12 seconds. Recipients in Syria experience SMS delivery delays.

**Recovery:** Pre-scaling is triggered 2 hours before known peak periods based on historical diaspora remittance data from previous Eid seasons. The rate lock service batch-processes requests. The system stabilizes within 5 minutes. Additional SMS capacity is provisioned with Syriatel and MTN in Syria.

### Large file processing — 10,000 bulk remittance file from Syrian diaspora organization in Germany
**System Behavior:** The bulk remittance file processing service receives a 10,000 record file from a Syrian community organization in Germany sending collective remittances to Damascus and Aleppo. Processing takes 20 minutes due to individual CBS FX rate locks for each transaction.

**User Impact:** The institutional sender's file is queued. Processing is delayed behind individual user transactions from Sweden and UAE. Individual senders from Berlin experience slightly slower processing during the batch window.

**Recovery:** Bulk files are processed on a dedicated worker pool with lower priority than individual transactions. The sender is given an estimated completion time. Progress is tracked per batch with a downloadable CSV report showing each recipient's status in Syria.

### CBS rate feed overload during high-volume diaspora remittance period
**System Behavior:** The Central Bank of Syria rate feed API is queried at 500 requests/second during the peak diaspora remittance period. The CBS API response time degrades from 200ms to 5 seconds. Rate lock requests start timing out.

**User Impact:** Diaspora senders in Berlin, Stockholm, and Dubai see "مصرف سورية المركزي يعاني من ضغط عالٍ. يتم تأمين سعر الصرف..." (The Central Bank of Syria is under high load. Securing the exchange rate...) for up to 8 seconds.

**Recovery:** The rate cache TTL is temporarily extended from 30 seconds to 60 seconds during high load, reducing CBS API queries by 50%. The rate lock service batches FX rate requests for the same corridor. A dedicated CBS API connection pool is allocated for rate lock requests with priority queuing.

## 8. Operational Failures

### Deployment rollback — v5.2.0 applies wrong FX rate margin to Germany→Syria corridor
**System Behavior:** The canary deployment detects a 30% increase in FX revenue from the Germany→Syria corridor (incorrectly charging 2% margin instead of 1% per the CBS-regulated diaspora rate). The rollback is triggered automatically.

**User Impact:** Approximately 500 remittances from Germany to Syria were processed with a 1% higher margin. Senders in Berlin were overcharged an average of €5 EUR each.

**Recovery:** The rollback completes within 2 minutes. The overcharged amount (€5 per sender) is refunded to each affected sender with a 10% apology credit (€0.50). The CBS rate margin configuration is validated against the master rate table before deployment.

### Configuration error — recipient wallet validation for Syria disabled in production
**System Behavior:** A configuration change accidentally disables the recipient wallet validation check for Syria. Remittances are sent to invalid or closed wallets in Damascus and Aleppo.

**User Impact:** 50 remittances from the Sweden→Syria corridor are sent to invalid or closed wallets in Syria. The funds are stuck in `PENDING_RECIPIENT_CHECK` status. Recipients who were expecting money are not credited.

**Recovery:** The configuration error is detected within 5 minutes by a monitoring alert. The validation is re-enabled. Affected remittances are reconciled manually. The operations team contacts each sender in Sweden individually via phone to verify recipient details.

### SFTP key rotation failure — Commercial Bank of Syria file transfer fails after key expiry
**System Behavior:** The SFTP key used to send remittance batch files to the Commercial Bank of Syria expires. The file transfer fails for the daily batch. The key rotation was not completed on schedule.

**User Impact:** 5,000 diaspora remittance recipients in Syria whose payouts were scheduled through the Commercial Bank of Syria experience a 1-day delay. Recipients in Damascus and Aleppo do not receive their SMS notifications.

**Recovery:** The key is manually rotated within 30 minutes by the operations team in coordination with the Commercial Bank of Syria. The file transfer is retried. Automated key rotation with 30-day advance warning is implemented.

### Currency conversion error — AED to SYP rate miscalculation for UAE→Syria corridor
**System Behavior:** The currency conversion engine misapplies the AED→SYP rate (uses 1 AED = 500 SYP instead of the CBS rate of 1 AED = 520 SYP). Recipients in Syria are underpaid.

**User Impact:** A sender in Dubai sending 1,000 AED to Syria receives a conversion of 500,000 SYP instead of 520,000 SYP. The recipient in Damascus is short by 20,000 SYP.

**Recovery:** The discrepancy is detected within 10 minutes by the corridor audit batch. The missing 20,000 SYP is credited to the recipient automatically. A rate validation check is added before each conversion. The sender receives a notification explaining the correction and apology.

### Syria internet blackout during remittance payout processing
**System Behavior:** A government-mandated internet blackout affects the entire Damascus region during a scheduled remittance payout batch. The payout confirmations cannot be sent from the Syrian payout partner to the Beza core system.

**User Impact:** Remittances from all corridors (Germany, Sweden, UAE) are stuck in `PAYOUT_IN_PROGRESS` status. Recipients in Syria have not received their funds. Senders see "في انتظار تأكيد الاستلام في سورية — انقطاع الإنترنت" (Awaiting receipt confirmation in Syria — internet outage.)

**Recovery:** The payout partner queues all confirmations locally with digital signatures. When the internet is restored (typically within 2-4 hours based on historical patterns), all queued confirmations are sent. The Beza system processes them in order. Recipients automatically receive their funds with the original CBS rate lock. Senders are notified when the batch completes.

## 9. Recovery Time Objectives Summary

| Failure Category | Detection Time | Recovery Time | RPO | Maximum Impact |
|-----------------|----------------|---------------|-----|----------------|
| Network (transient) | < 1 second | < 30 seconds | 0 | Single remittance delayed |
| Network (DNS outage) | 30 seconds | < 5 minutes | 0 | All remittance ops blocked |
| Network (Syria internet) | < 1 minute | < 4 hours | 0 | Payout confirmations queued |
| Transaction failure | < 100ms | < 5 seconds | 0 | Single remittance failed |
| External dependency | < 10 seconds | < 15 minutes | 0 | CBS FX rate unavailable |
| Data inconsistency | < 5 minutes | < 1 hour | < 5 seconds | Remittance status discrepancy |
| Security incident | < 1 minute | < 4 hours | 0 | Remittance held for review |
| Business logic | < 1 hour | < 24 hours | 0 | CBS rate expired / limit hit |
| Performance degradation | < 1 minute | < 5 minutes | 0 | Slow remittance processing |
| Operational (deployment) | < 5 minutes | < 10 minutes | < 1 minute | Incorrect fee applied |

## Changelog

| Date | Version | Changes |
|------|---------|---------|
| 2026-05-29 | 1.0 | Initial release — Pure Syria context with SYP amounts, diaspora corridors (Germany→Syria, UAE→Syria, Sweden→Syria), Syrian banks (BBS, Bemo, Commercial Bank of Syria), CBS rate references, hawala comparison, Syrian cities (Damascus, Aleppo, Homs, Latakia), diaspora cities (Berlin, Stockholm, Dubai), and Arabic-only messaging |

---

*Version: 1.0 | Last updated: 2026-05-29 | Owner: Remittance Engineering Team — Syria*
