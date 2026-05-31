# 31. Cards — Failure Scenarios

> **Purpose:** Document every failure mode for the Beza Cards feature — virtual and physical card issuance, card transactions, PIN management, dispute handling, and local card network interactions. Uses real SYP amounts and Arabic messaging only. Syria context: local card scheme, BIN 639123, Syrian ATM network, CBS settlement, Syrian Telecom for SMS.

---

## 1. Network Failures

### Internet cut during card transaction — POS terminal offline but chip transaction processed
**System Behavior:** The card chip generates an offline-approved transaction (ARQC) using the offline data authentication (ODA) protocol. The transaction is stored in the POS terminal's memory.

**User Impact:** The user receives the goods. The merchant's POS stores the transaction for later settlement. The user sees "معاملة غير متصلة" in their transaction history when the batch is settled.

**Recovery:** The POS terminal sends the batch settlement when connectivity is restored. The card system matches the offline authorization. The pending transaction transitions to settled status in the user's app.

### API timeout (>5s) during card balance check at Syrian ATM
**System Behavior:** The card balance inquiry API times out. The ATM cannot retrieve the balance from the Beza system.

**User Impact:** The Syrian ATM shows "الخدمة غير متوفرة" The user cannot check their balance at the ATM.

**Recovery:** The ATM fallback displays the last known balance from the card chip (if available). The circuit breaker resets after 15 seconds.

### DNS failure for cards-api.beza.com
**System Behavior:** The card authorization service is unreachable. The system operates in fail-closed mode — all card transactions are declined by default.

**User Impact:** All card transactions are declined at POS terminals and ATMs across Syria. The user cannot use their Beza card anywhere.

**Recovery:** DNS failover is initiated. Some merchant POS terminals support voice authorization — the merchant can call the Beza voice line to get an authorization code.

### Syrian Payment Network ACK timeout — authorization request to CBS times out
**System Behavior:** The authorization request is sent to the Syrian Payment Network (CBS). The acknowledgment (ACK) is not received within the 10-second timeout. The system sends a decline to the POS terminal.

**User Impact:** The card transaction is declined at the POS. The user is embarrassed. "معاملة مرفوضة"

**Recovery:** The user can retry with a different card or payment method. The CBS network issue is typically resolved within a few minutes.

### Syrian Telecom SMS provider unavailable for card transaction alert
**System Behavior:** A card transaction of 25,000 SYP is processed. The SMS transaction alert cannot be delivered because the Syrian Telecom SMS gateway is unavailable.

**User Impact:** The user does not receive the fraud alert SMS. The push notification via Firebase is still delivered.

**Recovery:** The SMS is retried for up to 1 hour. The in-app notification is always delivered immediately.

## 2. Transaction Failures

### Insufficient balance — user tries to spend 50,000 SYP but the card limit is 35,000 SYP
**System Behavior:** The authorization request checks `available_balance >= transaction_amount`. The available balance of 35,000 SYP is insufficient for 50,000 SYP. The transaction is declined.

**User Impact:** The transaction is declined at the POS. "رصيد غير كافٍ"

**Recovery:** The user can top up the card wallet or use an alternative payment method. Partial authorization is not supported by most merchants.

### Cross-border transaction blocked — user tries an online purchase from a UAE merchant
**System Behavior:** The card is configured for domestic use only. The cross-border transaction block is triggered when the merchant's country code is not Syria.

**User Impact:** The transaction is declined. "هذه البطاقة غير مفعلة للمعاملات عبر الحدود"

**Recovery:** The user can enable cross-border usage in the app by toggling the international transactions setting and confirming with MFA. The setting takes effect immediately.

### PIN lockout — 3 incorrect PIN attempts at a Syrian ATM
**System Behavior:** The card PIN is blocked after 3 consecutive incorrect entries. The Syrian ATM retains the card per standard banking policy.

**User Impact:** The user's card is trapped in the ATM. "تم حظر البطاقة بسبب إدخال PIN خاطئ 3 مرات"

**Recovery:** The user must contact customer support to unblock the PIN. If the ATM retained the card, a card replacement is needed. The PIN can be reset in the app with MFA verification.

### Duplicate transaction — merchant submits the same transaction twice
**System Behavior:** The idempotency is enforced through the `transaction_id` from the merchant's POS. The second submission returns `DUPLICATE` and is rejected.

**User Impact:** The user sees two pending transactions on their statement. The second transaction is automatically reversed within 24 hours. "إحدى المعاملات المكررة ملغاة"

**Recovery:** The duplicate detection runs at the authorization stage. The reversal is automatically submitted to the Syrian Payment Network.

### Pre-authorization hold exceeds available — hotel blocks 25,000 SYP but wallet has only 18,000 SYP
**System Behavior:** The pre-authorization request for 25,000 SYP cannot be completed because the available balance is only 18,000 SYP. The authorization is declined.

**User Impact:** The user cannot check in to the hotel. The hotel requires an alternative payment method.

**Recovery:** The user tops up the card wallet and retries the pre-authorization. The unsuccessful hold is automatically released within 7 days if not settled.

### Refund to an expired card — merchant refunds to a card that expired last month
**System Behavior:** The refund is sent to the Syrian Payment Network. The network rejects the refund because the card has expired. The refund is stuck.

**User Impact:** The refund of 10,000 SYP does not reach the user. The merchant thinks the refund was processed.

**Recovery:** The partner bank manually processes the refund to the user's new card. Beza routes the refund to the user's default wallet as a fallback.

### Card scheme BIN validation failure — merchant terminal rejects BIN 639123
**System Behavior:** The merchant's POS terminal does not recognize the local BIN range 639123. The terminal rejects the transaction before it reaches the authorization engine.

**User Impact:** The transaction is declined at the POS terminal. "رقم البطاقة غير معروف"

**Recovery:** The merchant updates their POS terminal software to include the 639123 BIN range. Beza provides BIN lookup documentation to all partner merchants.

## 3. External Dependency Failures

### Syrian Payment Network (CBS) outage
**System Behavior:** All card authorizations are queued locally at the POS. Transactions are declined due to the fail-closed policy, unless the merchant supports offline chip transactions.

**User Impact:** All card payments are blocked across Syria. Users cannot use their Beza cards anywhere. "شبكة البطاقات غير متوفرة حالياً"

**Recovery:** Some merchants support offline chip transactions for small amounts (up to 5,000 SYP). The CBS network typically recovers within 30 minutes.

### Card issuer processor (partner bank) API timeout
**System Behavior:** The authorization request to the partner bank's card processor times out. The default action is to decline the transaction.

**User Impact:** The user's transaction is declined. The user must use an alternative card or payment method.

**Recovery:** The partner bank has a 10-second SLA. If the timeout is exceeded, the circuit breaker opens for 60 seconds. The operations team contacts the bank.

### Syrian Telecom downtime for SMS OTP for card activation
**System Behavior:** The card activation flow requires an SMS OTP to be sent to the user's phone. The Syrian Telecom network is down, so the SMS cannot be delivered.

**User Impact:** The user receives the physical card but cannot activate it. "تعذر إرسال رمز التفعيل عبر الرسائل النصية"

**Recovery:** The user can activate the card through in-app biometric verification or by requesting a voice call OTP as a fallback.

### Card printing vendor production delay
**System Behavior:** The physical card production at the vendor's facility is delayed by 5 business days due to a raw material shortage.

**User Impact:** The user waits 15 business days instead of 10 for the physical card to be delivered. "تأخير في إنتاج البطاقة"

**Recovery:** The user receives a 500 SYP credit as compensation for the delay. The shipping is upgraded to express delivery at no cost.

### 3D Secure (3DS) authentication provider down
**System Behavior:** The 3DS challenge page fails to load during an online transaction. The merchant may decline the transaction without 3DS authentication.

**User Impact:** The online purchase cannot be completed without 3DS verification. "التحقق 3D Secure غير متاح"

**Recovery:** The merchant may allow the transaction to proceed without 3DS at their own risk. Beza monitors non-3DS transactions for fraud.

### CBS settlement system down for end-of-day card settlement
**System Behavior:** The end-of-day card settlement file cannot be submitted to CBS because the CBS settlement system is unavailable. All card transactions for the day remain unsettled.

**User Impact:** Merchants are not paid for the day's card transactions. "تسوية معاملات البطاقات معلقة"

**Recovery:** The settlement file is queued and automatically submitted when CBS is restored. If the delay exceeds 4 hours, manual settlement is arranged through the CBS operations team.

## 4. Data Consistency Failures

### Card authorization recorded but settlement file not generated
**System Behavior:** The transaction is authorized online. The settlement file generated at the end of the day does not include this transaction. The merchant is not paid.

**User Impact:** The user sees the transaction as pending indefinitely. The merchant does not receive the funds.

**Recovery:** The settlement reconciliation job runs every 2 hours. Missing transactions are detected and included in the next settlement file.

### Cache inconsistency — card limit shown as 200,000 SYP but actual is 100,000 SYP
**System Behavior:** The cache TTL (5 minutes) serves a stale limit of 200,000 SYP. The user initiates a 150,000 SYP transaction based on the cached limit.

**User Impact:** The transaction is declined at the database authorization level. "الحد الفعلي: 100,000 ل.س."

**Recovery:** The card limit is always read from the primary database for authorization purposes (not from cache). The cache is used only for display, and the authorization uses the authoritative source.

### Dual-write failure — card transaction logged but wallet balance not updated
**System Behavior:** The card transaction is logged in the card service database. The wallet balance update fails. The user sees a phantom balance that is higher than the actual balance.

**User Impact:** The user believes they can spend money that is already committed to the card transaction.

**Recovery:** A compensatory debit is made in the next reconciliation cycle. The balance is corrected within 1 hour.

### Card status event lost — card reported stolen but status not updated to FROZEN
**System Behavior:** The user reports the card as stolen through the app. The Kafka event carrying the status change is lost. The card remains active.

**User Impact:** The fraudster continues to use the stolen card for 3 hours before the status change is processed. 100,000 SYP in fraudulent transactions are posted.

**Recovery:** The dead-letter queue consumer processes the status update within 5 minutes. Real-time card status is checked on every transaction authorization.

### POS batch settlement file has duplicate entries — same transaction submitted twice
**System Behavior:** The POS terminal sends the settlement file with a duplicate entry. The settlement engine detects the duplicate `transaction_id` and skips the second entry.

**User Impact:** The user is not double-charged. The merchant is not double-paid. No user impact.

**Recovery:** The duplicate is silently dropped. The event is logged for the merchant's system audit.

### CBS settlement file format mismatch — file format changed without notice
**System Behavior:** CBS updates the settlement file format specification. The new settlement file generated by Beza does not match the updated format. CBS rejects the file.

**User Impact:** Card settlement is delayed for all merchants. No funds are transferred until the format is corrected.

**Recovery:** The operations team contacts CBS to obtain the new specification. The settlement engine is updated within 4 hours. A validation test against the CBS staging environment is added to the deployment pipeline.

## 5. Security Failures

### Fraud false positive — card used in Damascus and 2 hours later used in Aleppo flagged
**System Behavior:** The velocity check detects impossible travel — the card cannot be in Damascus and Aleppo (approximately 350 km apart) within 2 hours. The transaction is blocked.

**User Impact:** The user traveling to Aleppo by car has a legitimate transaction declined. "المعاملة مرفوضة لاختلاف الموقع"

**Recovery:** The user receives an SMS "هل كنت في حلب؟ رد بـ YES للتأكيد" The user confirms. The transaction is retried.

### Fraud false negative — card cloned via skimmer, 50,000 SYP withdrawn at Syrian ATM
**System Behavior:** The fraudulent ATM withdrawal uses valid chip data + PIN copied by a skimmer. The behavioral model does not flag the transaction because the PIN and chip data are legitimate.

**User Impact:** The legitimate user loses 50,000 SYP. The skimmer attack goes undetected during the transaction.

**Recovery:** The user reports the fraud. The card is blocked. Insurance covers 80% of the loss. The chip transaction analysis identifies the compromised ATM.

### Card-not-present (CNP) fraud — stolen card details used for online shopping
**System Behavior:** The 3DS challenge is bypassed because the merchant does not support 3DS. The fraudulent transaction of 30,000 SYP is approved.

**User Impact:** The user sees a 30,000 SYP fraudulent transaction on their statement.

**Recovery:** The user disputes the transaction through the app. A chargeback is filed with the card scheme. The user receives a provisional credit within 5 business days. The chargeback is finalized within 30 days.

### Unauthorized PIN change — PIN changed via call center without proper verification
**System Behavior:** The call center agent resets the PIN after minimal identity verification. The attacker uses the new PIN at a Syrian ATM to withdraw cash.

**User Impact:** The user is locked out of their own card. 25,000 SYP is withdrawn by the attacker.

**Recovery:** PIN changes through the call center are temporary (24-hour validity). The user must set a new PIN through the app with MFA. The stolen amount is covered by the zero-liability policy.

### Lost/stolen card used before user reports — 2-hour gap between loss and report
**System Behavior:** The card is lost at 10:00 AM. The user reports it at 12:00 PM. During the 2-hour gap, the fraudster makes contactless payments (no PIN required for amounts under 5,000 SYP).

**User Impact:** 75,000 SYP in fraudulent transactions are posted. The user is liable for the first 10,000 SYP (policy deductible).

**Recovery:** The remaining 65,000 SYP is covered by the zero-liability policy. All fraudulent transactions are reversed within 30 days.

### Card BIN range spoofing — attacker generates cards with 639123 BIN
**System Behavior:** An attacker generates fake card numbers using the valid BIN range 639123. The card validation engine checks the Luhn algorithm and BIN range. The fake numbers fail the issuer-side validation.

**User Impact:** No impact. The fake card numbers are rejected at the authorization stage.

**Recovery:** BIN range abuse monitoring detects the pattern of failed attempts. The source IPs are blocked. Additional CVV and expiry validation prevents BIN-only attacks.

## 6. Business Logic Failures

### Daily transaction limit hit — user spends 100,000 SYP (limit 120,000 SYP), tries a 25,000 SYP purchase
**System Behavior:** The authorization check detects `daily_aggregate + 25,000 > 120,000 limit`. The transaction is declined.

**User Impact:** The user's card is declined at the POS. "تم تجاوز الحد اليومي للمعاملات"

**Recovery:** The limit resets at midnight (UTC+3). The user can request a temporary limit increase through the app (up to 2x the standard limit, approved instantly).

### Merchant category code (MCC) blocked — user tries to use the card at a gambling site
**System Behavior:** The authorization engine checks the merchant's MCC against the user's allowed categories. Gambling MCCs (7800-7999) are blocked by default.

**User Impact:** The transaction is declined. "هذه الفئة من التجار غير مسموح بها"

**Recovery:** The user can request the gambling MCC to be unblocked through customer support, with a responsible gambling acknowledgment.

### Card expired — user tries to use an expired card on the 15th (expired on the 1st of the month)
**System Behavior:** The authorization check detects `expiry_date < today`. The transaction is declined.

**User Impact:** The card is declined at the POS. "البطاقة منتهية الصلاحية"

**Recovery:** A replacement card is automatically issued 30 days before the expiry date. The expired card can still be used for recurring payments for up to 3 months after expiry.

### Currency conversion fee dispute — $100 USD purchase converted at 5% fee instead of 2%
**System Behavior:** The merchant offered Dynamic Currency Conversion (DCC) at a 5% fee. The user accepted the DCC rate without understanding the fee difference.

**User Impact:** The user is charged $105 USD instead of $102 USD. The difference is $3 USD (approximately 37,500 SYP at the official rate).

**Recovery:** Beza policy states no DCC fee on Beza-issued cards. The transaction is converted at the CBS official rate. The user disputes the extra 3% and is refunded.

### Contactless limit exceeded — single contactless payment of 8,000 SYP (limit 5,000 SYP)
**System Behavior:** The card chip enforces the contactless limit. When the amount exceeds 5,000 SYP, the POS prompts for PIN entry.

**User Impact:** The user must insert the card and enter the PIN. The transaction is slower. "أدخل البطاقة وأدخل الرقم السري"

**Recovery:** The user inserts the card and enters the PIN. The transaction proceeds normally. The contactless limit resets at the next transaction.

### Card scheme routing failure — BIN 639123 not routed to the correct processor
**System Behavior:** The Syrian Payment Network routes the BIN 639123 transaction to the wrong card processor. The processor rejects the transaction as unrecognized.

**User Impact:** The card transaction is declined. "خطأ في توجيه المعاملة"

**Recovery:** CBS corrects the BIN routing table. All transactions for BIN 639123 are re-routed to the correct processor. The correction is verified with test transactions.

## 7. Performance & Scalability Failures

### Card authorization spike — 1,500 TPS during holiday shopping season
**System Behavior:** The card authorization service handles 1,500 transactions per second during peak holiday shopping. The CBS gateway is configured for 400 TPS. The circuit breaker opens at 600 TPS.

**User Impact:** 900 transactions per second are queued. Authorization latency increases from 200ms to 5 seconds. Some transactions are declined due to timeout.

**Recovery:** The CBS gateway capacity is negotiated for 1,500 TPS during known peak seasons. A local authorization cache is used for repeat merchants. The circuit breaker threshold is increased to 1,200 TPS.

### ATM PIN verification bottleneck — 300 concurrent PIN verifications
**System Behavior:** The PIN verification service receives 300 concurrent requests. The HSM (Hardware Security Module) can process 80 PIN verifications per second. 220 requests queue.

**User Impact:** Syrian ATM users wait 10-15 seconds for PIN verification. Some ATMs time out and return the card.

**Recovery:** Additional HSM slots are provisioned for peak capacity. PIN verification is batched where possible. A faster PIN verification algorithm is implemented.

### Card issuing queue backlog — 5,000 card issuances queued during promotion
**System Behavior:** A promotion offers free cards for new accounts. 5,000 card issuance requests are submitted in one day. The card printing vendor can produce 800 cards per day.

**User Impact:** New customers wait 7 days instead of 3 days for their physical card. Virtual cards are issued immediately.

**Recovery:** The card printing vendor is notified of the promotion in advance. Production capacity is increased to 2,000 cards per day. Virtual cards are issued instantly for immediate use.

### Syrian ATM network congestion — 500 concurrent balance inquiries
**System Behavior:** During the public sector salary disbursement day, 500 users simultaneously check their card balance at various ATMs across Syria. The ATM switch handles 100 inquiries per second.

**User Impact:** Balance inquiries take 20-30 seconds. Some ATMs display "يرجى الانتظار"

**Recovery:** The ATM switch capacity is temporarily increased. Balance inquiry responses are cached for 30 seconds. Users are encouraged to use the app instead of ATMs for balance checks.

## 8. Operational Failures

### Deployment rollback — v9.2.0 rejects all cross-border transactions
**System Behavior:** The canary deployment detects a 100% increase in cross-border transaction declines within 2 minutes. The rollback is triggered.

**User Impact:** All cross-border card transactions are declined for 2 minutes. Users traveling abroad cannot use their cards.

**Recovery:** The rollback completes within 2 minutes. The cross-border transaction check logic is corrected and tested.

### Configuration error — daily ATM withdrawal limit set to 500 SYP instead of 50,000 SYP
**System Behavior:** A configuration change sets the daily ATM withdrawal limit to 500 SYP. Users can only withdraw 500 SYP per day from Syrian ATMs.

**User Impact:** Users attempting ATM withdrawals above 500 SYP see "تم تجاوز الحد اليومي للسحب" Support calls spike.

**Recovery:** A monitoring alert fires on the limit change exceeding 90% decrease from the mean. The configuration is reverted within 4 minutes. Users are notified of the correction.

### Card BIN (639123) not registered in CBS switch — all Beza card transactions decline
**System Behavior:** The Beza card BIN range 639123 is accidentally deregistered from the CBS card switch during a system migration. All Beza card transactions are declined across Syria.

**User Impact:** All 50,000 active Beza cards cannot make any transactions for 2 hours.

**Recovery:** The CBS operations team is contacted to re-register the BIN. The re-registration is completed within 2 hours. All affected users receive a 500 SYP apology credit.

### Syrian Telecom SMS outage — card activation OTPs not delivered for 3 hours
**System Behavior:** The Syrian Telecom SMS network experiences a country-wide outage. All SMS-based OTPs for card activation are undelivered.

**User Impact:** 2,000 users with new cards cannot activate them. Card issuance is stalled.

**Recovery:** Users are redirected to in-app biometric activation as a fallback. The SMS-based activation window is extended by 48 hours. Users are notified of the alternative activation method.

## 9. Recovery Time Objectives Summary

| Failure Category | Detection Time | Recovery Time | RPO | Maximum Impact |
|-----------------|----------------|---------------|-----|----------------|
| Network (transient) | < 1 second | < 30 seconds | 0 | Single card declined |
| Network (DNS outage) | 30 seconds | < 5 minutes | 0 | All card ops blocked |
| Transaction failure | < 100ms | < 5 seconds | 0 | Single transaction declined |
| External dependency | < 10 seconds | < 30 minutes | 0 | CBS network down |
| Data inconsistency | < 5 minutes | < 1 hour | < 5 seconds | Duplicate charge |
| Security incident | < 1 minute | < 4 hours | 0 | Card blocked for fraud |
| Business logic | < 1 hour | < 24 hours | 0 | Limit hit / expired card |
| Performance degradation | < 1 minute | < 5 minutes | 0 | Slow authorization |
| Operational (deployment) | < 5 minutes | < 10 minutes | < 1 minute | All transactions declined |

## Changelog

| Date | Version | Changes |
|------|---------|---------|
| 2026-05-29 | 1.0 | Initial release — 7 categories covering network, transactions, external dependencies, data consistency, security, business logic, and performance failures for card issuing and processing feature — Syria context only |

---

*Version: 1.0 | Last updated: 2026-05-29 | Owner: Cards Engineering Team*
