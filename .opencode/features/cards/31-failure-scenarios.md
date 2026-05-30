# 31. Cards — Failure Scenarios

> **Purpose:** Document every failure mode for the Beza Cards feature — virtual and physical card issuance, card transactions, PIN management, dispute handling, and card network (Visa/Mastercard) interactions. Uses real ETB/USD amounts and Arabic/Amharic messaging.

---

## 1. Network Failures

### Internet cut during card transaction — POS terminal offline but EMV chip transaction processed
**System Behavior:** The card chip generates an offline-approved transaction (ARQC) using the offline data authentication (ODA) protocol. The transaction is stored in the POS terminal's memory.

**User Impact:** The user receives the goods. The merchant's POS stores the transaction for later settlement. The user sees "معاملة غير متصلة" (Offline transaction) in their transaction history when the batch is settled.

**Recovery:** The POS terminal sends the batch settlement when connectivity is restored. The card system matches the offline authorization. The pending transaction transitions to settled status in the user's app.

### API timeout (>5s) during card balance check at ATM
**System Behavior:** The card balance inquiry API times out. The ATM cannot retrieve the balance from the Beza system.

**User Impact:** The ATM shows "الخدمة غير متوفرة" (Service unavailable.) The user cannot check their balance at the ATM.

**Recovery:** The ATM fallback displays the last known balance from the card chip (if available). The circuit breaker resets after 15 seconds.

### DNS failure for cards-api.beza.et
**System Behavior:** The card authorization service is unreachable. The system operates in fail-closed mode — all card transactions are declined by default.

**User Impact:** All card transactions are declined at POS terminals and ATMs. The user cannot use their Beza card anywhere.

**Recovery:** Route53 failover is initiated. Some merchant POS terminals support voice authorization — the merchant can call the Beza voice line to get an authorization code.

### Visa network ACK timeout — authorization request to VisaNet times out
**System Behavior:** The authorization request is sent to VisaNet. The acknowledgment (ACK) is not received within the 10-second timeout. The system sends a decline to the POS terminal.

**User Impact:** The card transaction is declined at the POS. The user is embarrassed. "معاملة مرفوضة" (Transaction declined.)

**Recovery:** The user can retry with a different card or payment method. The Visa network issue is typically resolved within a few minutes.

### SMS provider unavailable for card transaction alert
**System Behavior:** A card transaction of 15,000 ETB is processed. The SMS transaction alert cannot be delivered because the SMS provider is unavailable.

**User Impact:** The user does not receive the fraud alert SMS. The push notification via Firebase is still delivered.

**Recovery:** The SMS is retried for up to 1 hour. The in-app notification is always delivered immediately.

## 2. Transaction Failures

### Insufficient balance — user tries to spend 20,000 ETB but the card limit is 15,000 ETB
**System Behavior:** The authorization request checks `available_balance >= transaction_amount`. The available balance of 15,000 ETB is insufficient for 20,000 ETB. The transaction is declined.

**User Impact:** The transaction is declined at the POS. "رصيد غير كافٍ" (Insufficient balance.)

**Recovery:** The user can top up the card wallet or use an alternative payment method. Partial authorization is not supported by most merchants.

### International transaction blocked — user tries a $500 USD online purchase from a US merchant
**System Behavior:** The card is configured for domestic use only. The international transaction block is triggered when the merchant's country code is not Ethiopia.

**User Impact:** The transaction is declined. "هذه البطاقة غير مفعلة للمعاملات الدولية" (This card is not enabled for international transactions.)

**Recovery:** The user can enable international usage in the app by toggling the international transactions setting and confirming with MFA. The setting takes effect immediately.

### PIN lockout — 3 incorrect PIN attempts at an ATM
**System Behavior:** The card PIN is blocked after 3 consecutive incorrect entries. The ATM retains the card per standard banking policy.

**User Impact:** The user's card is trapped in the ATM. "تم حظر البطاقة بسبب إدخال PIN خاطئ 3 مرات" (Card blocked after 3 incorrect PIN attempts.)

**Recovery:** The user must contact customer support to unblock the PIN. If the ATM retained the card, a card replacement is needed. The PIN can be reset in the app with MFA verification.

### Duplicate transaction — merchant submits the same transaction twice
**System Behavior:** The idempotency is enforced through the `transaction_id` from the merchant's POS. The second submission returns `DUPLICATE` and is rejected.

**User Impact:** The user sees two pending transactions on their statement. The second transaction is automatically reversed within 24 hours. "إحدى المعاملات المكررة ملغاة" (One of the duplicate transactions has been cancelled.)

**Recovery:** The duplicate detection runs at the authorization stage. The reversal is automatically submitted to the Visa network.

### Pre-authorization hold exceeds available — hotel blocks 10,000 ETB but wallet has only 8,000 ETB
**System Behavior:** The pre-authorization request for 10,000 ETB cannot be completed because the available balance is only 8,000 ETB. The authorization is declined.

**User Impact:** The user cannot check in to the hotel. The hotel requires an alternative payment method.

**Recovery:** The user tops up the card wallet and retries the pre-authorization. The unsuccessful hold is automatically released within 7 days if not settled.

### Refund to an expired card — merchant refunds to a card that expired last month
**System Behavior:** The refund is sent to the Visa network. The network rejects the refund because the card has expired. The refund is stuck.

**User Impact:** The refund of 5,000 ETB does not reach the user. The merchant thinks the refund was processed.

**Recovery:** The partner bank manually processes the refund to the user's new card. Beza routes the refund to the user's default wallet as a fallback.

## 3. External Dependency Failures

### Visa/Mastercard network outage
**System Behavior:** All card authorizations are queued locally at the POS. Transactions are declined due to the fail-closed policy, unless the merchant supports offline EMC transactions.

**User Impact:** All card payments are blocked. Users cannot use their Beza cards anywhere. "شبكة البطاقات غير متوفرة حالياً" (Card network is currently unavailable.)

**Recovery:** Some merchants support offline EMV chip transactions for small amounts. The Visa network typically recovers within 30 minutes.

### Card issuer processor (partner bank) API timeout
**System Behavior:** The authorization request to the partner bank's card processor times out. The default action is to decline the transaction.

**User Impact:** The user's transaction is declined. The user must use an alternative card or payment method.

**Recovery:** The partner bank has a 10-second SLA. If the timeout is exceeded, the circuit breaker opens for 60 seconds. The operations team contacts the bank.

### CFE (Ethio Telecom) downtime for SMS OTP for card activation
**System Behavior:** The card activation flow requires an SMS OTP to be sent to the user's phone. The CFE network is down, so the SMS cannot be delivered.

**User Impact:** The user receives the physical card but cannot activate it. "تعذر إرسال رمز التفعيل عبر الرسائل النصية" (Activation code via SMS could not be sent.)

**Recovery:** The user can activate the card through in-app biometric verification or by requesting a voice call OTP via Twilio as a fallback.

### Card printing vendor (IDEMIA/Giesecke) production delay
**System Behavior:** The physical card production at the vendor's facility is delayed by 5 business days due to a raw material shortage.

**User Impact:** The user waits 15 business days instead of 10 for the physical card to be delivered. "تأخير في إنتاج البطاقة" (Card production delay.)

**Recovery:** The user receives a 100 ETB credit as compensation for the delay. The shipping is upgraded to express delivery at no cost.

### 3D Secure (3DS) authentication provider down
**System Behavior:** The 3DS challenge page fails to load during an online transaction. The merchant may decline the transaction without 3DS authentication.

**User Impact:** The online purchase cannot be completed without 3DS verification. "التحقق 3D Secure غير متاح" (3D Secure verification is unavailable.)

**Recovery:** The merchant may allow the transaction to proceed without 3DS at their own risk. Beza monitors non-3DS transactions for fraud.

## 4. Data Consistency Failures

### Card authorization recorded but settlement file not generated
**System Behavior:** The transaction is authorized online. The settlement file generated at the end of the day does not include this transaction. The merchant is not paid.

**User Impact:** The user sees the transaction as pending indefinitely. The merchant does not receive the funds.

**Recovery:** The settlement reconciliation job runs every 2 hours. Missing transactions are detected and included in the next settlement file.

### Cache inconsistency — card limit shown as 100,000 ETB but actual is 50,000 ETB
**System Behavior:** The cache TTL (5 minutes) serves a stale limit of 100,000 ETB. The user initiates a 75,000 ETB transaction based on the cached limit.

**User Impact:** The transaction is declined at the database authorization level. "الحد الفعلي: 50,000 ETB" (The actual limit is 50,000 ETB.)

**Recovery:** The card limit is always read from the primary database for authorization purposes (not from cache). The cache is used only for display, and the authorization uses the authoritative source.

### Dual-write failure — card transaction logged but wallet balance not updated
**System Behavior:** The card transaction is logged in the card service database. The wallet balance update fails. The user sees a phantom balance that is higher than the actual balance.

**User Impact:** The user believes they can spend money that is already committed to the card transaction.

**Recovery:** A compensatory debit is made in the next reconciliation cycle. The balance is corrected within 1 hour.

### Card status event lost — card reported stolen but status not updated to FROZEN
**System Behavior:** The user reports the card as stolen through the app. The Kafka event carrying the status change is lost. The card remains active.

**User Impact:** The fraudster continues to use the stolen card for 3 hours before the status change is processed. 50,000 ETB in fraudulent transactions are posted.

**Recovery:** The dead-letter queue consumer processes the status update within 5 minutes. Real-time card status is checked on every transaction authorization.

### POS batch settlement file has duplicate entries — same transaction submitted twice
**System Behavior:** The POS terminal sends the settlement file with a duplicate entry. The settlement engine detects the duplicate `transaction_id` and skips the second entry.

**User Impact:** The user is not double-charged. The merchant is not double-paid. No user impact.

**Recovery:** The duplicate is silently dropped. The event is logged for the merchant's system audit.

## 5. Security Failures

### Fraud false positive — card used in Addis Ababa and 2 hours later used in Bahir Dar flagged
**System Behavior:** The velocity check detects impossible travel — the card cannot be in Addis Ababa and Bahir Dar (approximately 600 km apart) within 2 hours. The transaction is blocked.

**User Impact:** The user traveling to Bahir Dar by plane has a legitimate transaction declined. "المعاملة مرفوضة لاختلاف الموقع" (Transaction declined due to location discrepancy.)

**Recovery:** The user receives an SMS "هل كنت في بهر دار؟ رد بـ YES للتأكيد" (Were you in Bahir Dar? Reply YES to confirm.) The user confirms. The transaction is retried.

### Fraud false negative — card cloned via skimmer, 25,000 ETB withdrawn at ATM
**System Behavior:** The fraudulent ATM withdrawal uses valid chip data + PIN copied by a skimmer. The behavioral model does not flag the transaction because the PIN and chip data are legitimate.

**User Impact:** The legitimate user loses 25,000 ETB. The skimmer attack goes undetected during the transaction.

**Recovery:** The user reports the fraud. The card is blocked. Insurance covers 80% of the loss. The chip transaction analysis identifies the compromised ATM.

### Card-not-present (CNP) fraud — stolen card details used for online shopping
**System Behavior:** The 3DS challenge is bypassed because the merchant does not support 3DS. The fraudulent transaction of 15,000 ETB is approved.

**User Impact:** The user sees a 15,000 ETB fraudulent transaction on their statement.

**Recovery:** The user disputes the transaction through the app. A chargeback is filed with Visa. The user receives a provisional credit within 5 business days. The chargeback is finalized within 30 days.

### Unauthorized PIN change — PIN changed via call center without proper verification
**System Behavior:** The call center agent resets the PIN after minimal identity verification. The attacker uses the new PIN at an ATM to withdraw cash.

**User Impact:** The user is locked out of their own card. 10,000 ETB is withdrawn by the attacker.

**Recovery:** PIN changes through the call center are temporary (24-hour validity). The user must set a new PIN through the app with MFA. The stolen amount is covered by the zero-liability policy.

### Lost/stolen card used before user reports — 2-hour gap between loss and report
**System Behavior:** The card is lost at 10:00 AM. The user reports it at 12:00 PM. During the 2-hour gap, the fraudster makes contactless payments (no PIN required for amounts under 2,000 ETB).

**User Impact:** 35,000 ETB in fraudulent transactions are posted. The user is liable for the first 5,000 ETB (policy deductible).

**Recovery:** The remaining 30,000 ETB is covered by the Visa zero-liability policy. All fraudulent transactions are reversed within 30 days.

## 6. Business Logic Failures

### Daily transaction limit hit — user spends 45,000 ETB (limit 50,000 ETB), tries a 10,000 ETB purchase
**System Behavior:** The authorization check detects `daily_aggregate + 10,000 > 50,000 limit`. The transaction is declined.

**User Impact:** The user's card is declined at the POS. "تم تجاوز الحد اليومي للمعاملات" (Daily transaction limit exceeded.)

**Recovery:** The limit resets at midnight EAT (UTC+3). The user can request a temporary limit increase through the app (up to 2× the standard limit, approved instantly).

### Merchant category code (MCC) blocked — user tries to use the card at a gambling site
**System Behavior:** The authorization engine checks the merchant's MCC against the user's allowed categories. Gambling MCCs (7800-7999) are blocked by default.

**User Impact:** The transaction is declined. "هذه الفئة من التجار غير مسموح بها" (This merchant category is not permitted.)

**Recovery:** The user can request the gambling MCC to be unblocked through customer support, with a responsible gambling acknowledgment.

### Card expired — user tries to use an expired card on the 15th (expired on the 1st of the month)
**System Behavior:** The authorization check detects `expiry_date < today`. The transaction is declined.

**User Impact:** The card is declined at the POS. "البطاقة منتهية الصلاحية" (Card has expired.)

**Recovery:** A replacement card is automatically issued 30 days before the expiry date. The expired card can still be used for recurring payments per Visa rules for up to 3 months after expiry.

### Currency conversion fee dispute — $100 USD purchase converted at 5% fee instead of 2%
**System Behavior:** The merchant offered Dynamic Currency Conversion (DCC) at a 5% fee. The user accepted the DCC rate without understanding the fee difference.

**User Impact:** The user is charged $105 USD instead of $102 USD. The difference is $3 USD (approximately 345 ETB).

**Recovery:** Beza policy states no DCC fee on Beza-issued cards. The transaction is converted at the Visa network rate. The user disputes the extra 3% and is refunded.

### Contactless limit exceeded — single contactless payment of 3,500 ETB (limit 2,000 ETB)
**System Behavior:** The card chip enforces the contactless limit. When the amount exceeds 2,000 ETB, the POS prompts for PIN entry.

**User Impact:** The user must insert the card and enter the PIN. The transaction is slower. "أدخل البطاقة وأدخل الرقم السري" (Insert card and enter PIN.)

**Recovery:** The user inserts the card and enters the PIN. The transaction proceeds normally. The contactless limit resets at the next transaction.

## 7. Performance & Scalability Failures

### Card authorization spike — 2,000 TPS during holiday shopping season
**System Behavior:** The card authorization service handles 2,000 transactions per second during peak holiday shopping. The Visa network gateway is configured for 500 TPS. The circuit breaker opens at 800 TPS.

**User Impact:** 1,200 transactions per second are queued. Authorization latency increases from 200ms to 5 seconds. Some transactions are declined due to timeout.

**Recovery:** The Visa gateway capacity is negotiated for 2,000 TPS during known peak seasons. A local authorization cache is used for repeat merchants. The circuit breaker threshold is increased to 1,500 TPS.

### ATM PIN verification bottleneck — 500 concurrent PIN verifications
**System Behavior:** The PIN verification service receives 500 concurrent requests. The HSM (Hardware Security Module) can process 100 PIN verifications per second. 400 requests queue.

**User Impact:** ATM users wait 10-15 seconds for PIN verification. Some ATMs time out and return the card.

**Recovery:** Additional HSM slots are provisioned for peak capacity. PIN verification is batched where possible. A faster PIN verification algorithm is implemented.

### Card issuing queue backlog — 10,000 card issuances queued during promotion
**System Behavior:** A promotion offers free cards for new accounts. 10,000 card issuance requests are submitted in one day. The card printing vendor can produce 1,000 cards per day.

**User Impact:** New customers wait 10 days instead of 3 days for their physical card. Virtual cards are issued immediately.

**Recovery:** The card printing vendor is notified of the promotion in advance. Production capacity is increased to 3,000 cards per day. Virtual cards are issued instantly for immediate use.

## 8. Operational Failures

### Deployment rollback — v9.2.0 rejects all international transactions
**System Behavior:** The canary deployment detects a 100% increase in international transaction declines within 2 minutes. The rollback is triggered.

**User Impact:** All international card transactions are declined for 2 minutes. Users traveling abroad cannot use their cards.

**Recovery:** The rollback completes within 2 minutes. The international transaction check logic is corrected and tested.

### Configuration error — daily ATM withdrawal limit set to 200 ETB instead of 20,000 ETB
**System Behavior:** A configuration change sets the daily ATM withdrawal limit to 200 ETB. Users can only withdraw 200 ETB per day from ATMs.

**User Impact:** Users attempting ATM withdrawals above 200 ETB see "تم تجاوز الحد اليومي للسحب" (Daily withdrawal limit exceeded.) Support calls spike.

**Recovery:** A monitoring alert fires on the limit change exceeding 90% decrease from the mean. The configuration is reverted within 4 minutes. Users are notified of the correction.

### Visa BIN (Bank Identification Number) not registered — all Beza card transactions decline
**System Behavior:** The Beza card BIN range is accidentally deregistered from the Visa network during a system migration. All Beza card transactions are declined globally.

**User Impact:** All 100,000 active Beza cards cannot make any transactions for 2 hours.

**Recovery:** The Visa network operations team is contacted to re-register the BIN. The re-registration is completed within 2 hours. All affected users receive a 100 ETB apology credit.

## 9. Recovery Time Objectives Summary

| Failure Category | Detection Time | Recovery Time | RPO | Maximum Impact |
|-----------------|----------------|---------------|-----|----------------|
| Network (transient) | < 1 second | < 30 seconds | 0 | Single card declined |
| Network (DNS outage) | 30 seconds | < 5 minutes | 0 | All card ops blocked |
| Transaction failure | < 100ms | < 5 seconds | 0 | Single transaction declined |
| External dependency | < 10 seconds | < 30 minutes | 0 | Visa network down |
| Data inconsistency | < 5 minutes | < 1 hour | < 5 seconds | Duplicate charge |
| Security incident | < 1 minute | < 4 hours | 0 | Card blocked for fraud |
| Business logic | < 1 hour | < 24 hours | 0 | Limit hit / expired card |
| Performance degradation | < 1 minute | < 5 minutes | 0 | Slow authorization |
| Operational (deployment) | < 5 minutes | < 10 minutes | < 1 minute | All transactions declined |

## Changelog

| Date | Version | Changes |
|------|---------|---------|
| 2026-05-29 | 1.0 | Initial release — 7 categories covering network, transactions, external dependencies, data consistency, security, business logic, and performance failures for card issuing and processing feature |

---

*Version: 1.0 | Last updated: 2026-05-29 | Owner: Cards Engineering Team*
