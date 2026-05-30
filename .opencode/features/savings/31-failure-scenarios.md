# 31. Savings — Failure Scenarios

> **Purpose:** Document every failure mode for the Beza Savings feature — savings accounts, goal-based savings, auto-save, interest calculation, early withdrawal, and fixed deposits. Uses real ETB amounts and Arabic/Amharic messaging.

---

## 1. Network Failures

### Internet cut during savings deposit after wallet debited but savings not credited
**System Behavior:** The user's wallet is debited 10,000 ETB. The savings account is not credited. The transaction is marked `SAVINGS_DEPOSIT_PENDING`.

**User Impact:** The user sees "تم خصم 10,000 ETB. جاري إيداعها في حساب التوفير" (10,000 ETB deducted. Depositing to savings.)

**Recovery:** A background job checks for pending deposits every 30 seconds. When the connection is restored, the savings account is credited. The user receives a notification confirming the deposit.

### API timeout (>5s) during savings balance query
**System Behavior:** The savings service times out. The app returns the cached savings balance, which can be up to 5 minutes stale.

**User Impact:** The user sees "جاري التحميل..." followed by the cached balance with a warning "آخر تحديث: منذ 3 دقائق" (Last updated: 3 minutes ago.)

**Recovery:** The circuit breaker resets after 15 seconds. The next request fetches a fresh balance from the database.

### DNS failure for savings-api.beza.et
**System Behavior:** The savings section of the app is inaccessible because the DNS cannot resolve the API endpoint. The rest of the app functions normally.

**User Impact:** The user sees "خدمة التوفير غير متوفرة حالياً" (Savings service is currently unavailable.) Other wallet functions continue working.

**Recovery:** Route53 failover to the secondary region is triggered within 5 minutes. The app retries the connection every 30 seconds.

### WebSocket disconnect during real-time savings goal progress tracking
**System Behavior:** The goal progress bar on the dashboard freezes at the last synced value. The server stores the progress updates for replay.

**User Impact:** The user sees "جاري التحديث..." on the goal progress indicator. Contributions are still recorded even though the progress bar is frozen.

**Recovery:** When the WebSocket reconnects, the progress is recalculated from the actual savings balance. The user sees the updated progress bar.

### SMS provider unavailable for savings milestone notification
**System Behavior:** The user reaches 50% of their savings goal (50,000 ETB). The milestone SMS cannot be delivered because the SMS provider is unavailable.

**User Impact:** The user does not receive the celebration SMS. The in-app notification "أكملت 50% من هدف التوفير!" (You have completed 50% of your savings goal!) is still delivered.

**Recovery:** The SMS is retried for up to 24 hours. The push notification serves as the primary celebration channel.

## 2. Transaction Failures

### Insufficient wallet balance for auto-save rule — 2,000 ETB auto-save fails
**System Behavior:** The auto-save engine checks the wallet balance on the scheduled day. The balance is below 2,000 ETB. The auto-save is skipped for that day.

**User Impact:** The user sees a notification "فشل التوفير التلقائي. رصيد غير كافٍ" (Auto-save failed. Insufficient balance.)

**Recovery:** The auto-save retries the next day. If it fails 3 consecutive times, the auto-save rule is automatically paused and the user is notified.

### Early withdrawal penalty miscalculated — user withdraws 50,000 ETB after 3 months (penalty 2% but charged 5%)
**System Behavior:** The penalty engine applies a 5% fee instead of the correct 2% fee. The user is charged 2,500 ETB instead of 1,000 ETB.

**User Impact:** The user sees "رسوم السحب المبكر: 2,500 ETB" (Early withdrawal fee: 2,500 ETB.) The user is overcharged by 1,500 ETB.

**Recovery:** The user contacts support to report the discrepancy. The penalty is corrected. 1,500 ETB plus compensatory interest is refunded. The bug is fixed in the penalty calculation logic.

### Duplicate withdrawal request — user submits withdrawal twice
**System Behavior:** The idempotency key prevents the second withdrawal from being processed. The second request returns HTTP 409 Conflict.

**User Impact:** The user sees "تمت معالجة طلب السحب مسبقاً" (The withdrawal request has already been processed.)

**Recovery:** The first withdrawal is processed normally. The second request is silently discarded.

### Minimum balance violation — user withdraws all but 100 ETB (minimum 500 ETB required)
**System Behavior:** The pre-validation checks that the remaining balance after withdrawal is at least 500 ETB. The remaining 100 ETB violates this requirement. The withdrawal is rejected.

**User Impact:** The user sees "يجب أن يتبقى 500 ETB على الأقل في حساب التوفير" (At least 500 ETB must remain in the savings account.)

**Recovery:** The user adjusts the withdrawal amount. The UI shows the maximum withdrawable amount (current balance minus 500 ETB).

### Fixed deposit maturity — auto-renewal fails due to insufficient main wallet for tax deduction
**System Behavior:** The fixed deposit of 100,000 ETB matures. The system needs to deduct 500 ETB in tax from the main wallet. The main wallet has only 200 ETB.

**User Impact:** The FD renews automatically. The tax deduction is deferred. "خصم ضريبة الوديعة مؤجل" (Fixed deposit tax deduction deferred.)

**Recovery:** The system retries the tax deduction daily for 7 days. If the main wallet remains insufficient, the system partially liquidates the FD to cover the tax.

### Savings goal target already met but system continues auto-save
**System Behavior:** The savings goal target of 100,000 ETB is reached. The auto-save rule is still active. An additional 5,000 ETB is saved beyond the target.

**User Impact:** The user saves 105,000 ETB instead of the planned 100,000 ETB. "لقد تجاوزت هدف التوفير!" (You have exceeded your savings goal!)

**Recovery:** The auto-save rule is automatically paused when the goal reaches 100%. The user can set a new goal or withdraw the excess amount.

## 3. External Dependency Failures

### NBE (National Bank of Ethiopia) interest rate benchmark API down
**System Behavior:** The savings interest rate calculation uses the last known benchmark rate, which is cached for up to 24 hours.

**User Impact:** Interest accrues at the previous rate. "معدل الفائدة: 4.5% (قد لا يعكس السعر الحالي)" (Interest rate: 4.5% (may not reflect the current rate).)

**Recovery:** When the benchmark feed is restored, the rate is updated. Interest is recalculated for the affected period.

### SMS provider (InfoBip) unavailable for savings confirmation
**System Behavior:** The SMS delivery is queued on SQS. A push notification is sent via Firebase Cloud Messaging.

**User Impact:** The user may not receive the SMS "تم إيداع 10,000 ETB في حساب التوفير" (10,000 ETB deposited to savings.) The in-app notification is delivered.

**Recovery:** The SMS is retried for up to 24 hours. The transaction appears in the in-app history immediately.

### Bank API timeout for fixed deposit funding from bank account
**System Behavior:** The bank transfer for funding the fixed deposit times out. The FD is marked as `FUNDING_PENDING`.

**User Impact:** The user sees "في انتظار تحويل الأموال من الحساب البنكي" (Awaiting the bank transfer for FD funding.)

**Recovery:** A poller checks the bank transaction status every 30 seconds for up to 2 hours. On confirmation, the FD is activated. If the transfer fails, the FD is cancelled and the user is notified.

### Credit reference bureau API down during savings account upgrade
**System Behavior:** The credit check for a premium savings account upgrade cannot be completed because the bureau API is down. The upgrade is blocked.

**User Impact:** The user sees "التحقق من الائتمان غير متوفر. حاول مرة أخرى لاحقاً" (Credit check unavailable. Please try again later.)

**Recovery:** The upgrade is deferred. The system retries every hour for 24 hours. If the bureau remains unavailable, a manual override is available with manager approval.

### Central bank deposit insurance API unavailable
**System Behavior:** The system cannot verify the deposit insurance limit (200,000 ETB per depositor) because the central bank API is down.

**User Impact:** No immediate user impact. The insurance verification is delayed.

**Recovery:** The insurance limit is cached and served from the last successful response. When the API is restored, the cache is refreshed.

## 4. Data Consistency Failures

### Interest accrual DB write fails mid-month — 15 days accrued but write fails on day 16
**System Behavior:** The daily interest accrual job successfully writes entries for 15 days. On day 16, the database write fails. The interest for that day is not recorded.

**User Impact:** The user earns slightly less interest for the month. "تم احتساب الفائدة لمدة 29 يوماً بدلاً من 30" (Interest was calculated for 29 days instead of 30.)

**Recovery:** The monthly interest reconciliation detects the missing day. The missing interest (approximately 0.50 ETB on a 100,000 ETB balance at 4.5% APR) is compensated. The user is credited the correct amount.

### Cache inconsistency — savings balance shown as 150,000 ETB but actual is 145,000 ETB
**System Behavior:** The cache-aside pattern with version checking detects the TTL mismatch. The cache entry is invalidated and a fresh value is loaded from the database.

**User Impact:** The user initiates a withdrawal of 148,000 ETB based on the cached balance. The withdrawal is rejected at the database level. "الرصيد الفعلي: 145,000 ETB" (Actual balance: 145,000 ETB.)

**Recovery:** The cache is invalidated and the fresh balance is loaded. Strong consistency is enforced for all balance-affecting writes.

### Savings goal progress event lost — 10,000 ETB deposit not reflected in goal tracking
**System Behavior:** The deposit is credited to the savings account. The Kafka event carrying the goal progress update is lost. The goal progress is stuck at 40% instead of 50%.

**User Impact:** The user sees the goal progress as "هدف التوفير: 40% مكتمل" (Savings goal: 40% complete.) even though they have deposited more.

**Recovery:** The goal progress is recalculated from the actual savings balance every 6 hours. The progress corrects itself automatically within the next recalculation cycle.

### Dual-write to savings ledger and main wallet fails partially
**System Behavior:** The main wallet is debited. The savings ledger write fails. The Saga pattern detects the inconsistency and reverses the main wallet debit.

**User Impact:** The user sees "تم إلغاء الإيداع. لم يتم خصم أي مبلغ" (Deposit cancelled. No amount was deducted.)

**Recovery:** The compensation transaction is completed within 2 seconds. The user retries the deposit.

### Fixed deposit interest rate table corrupted — wrong rate applied to 1,000,000 ETB FD
**System Behavior:** The FD is booked at 3% interest instead of the correct 5% due to a rate table corruption. The user loses 20,000 ETB in interest over a 12-month term.

**User Impact:** The user earns 30,000 ETB in interest instead of 50,000 ETB. This is a 20,000 ETB loss.

**Recovery:** The monthly reconciliation detects the rate discrepancy. The rate is corrected retroactively. The user receives the 20,000 ETB difference plus compensatory interest.

## 5. Security Failures

### Fraud false positive — user withdrawing 200,000 ETB savings to buy property flagged
**System Behavior:** The AML rules engine triggers on the large withdrawal amount (greater than 100,000 ETB) combined with an irregular pattern. The withdrawal is placed in `PENDING_REVIEW`.

**User Impact:** The user sees "السحب قيد المراجعة. سيتم إعلامك خلال 24 ساعة" (Withdrawal under review. You will be notified within 24 hours.)

**Recovery:** The compliance team reviews the documentation (property sale agreement). If legitimate, the withdrawal is released within the 4-hour SLA.

### Fraud false negative — unauthorized early withdrawal via stolen phone
**System Behavior:** The attacker uses a stolen phone to withdraw 80,000 ETB from savings to the main wallet, then transfers it out. The behavioral model does not flag the transaction.

**User Impact:** The legitimate user loses 80,000 ETB in savings.

**Recovery:** Insurance covers 80% of the verified loss. The device fingerprint and geolocation are used for retrospective fraud model training. The stolen device is blacklisted.

### Unauthorized access to savings admin — interest rate override
**System Behavior:** An attacker gains access to the admin panel and changes the savings interest rate from 4.5% to 8%. All savings accounts earn inflated interest.

**User Impact:** Beza overpays 3.5% extra interest across 100,000 accounts. The monthly loss is approximately 3.5 million ETB.

**Recovery:** Interest rate changes require MFA plus dual approval from Product and Finance teams. An audit log entry triggers an immediate SIEM alert on any `RATE_OVERRIDE` event.

### Savings goal privacy breach — user's "حج" (Hajj savings) goal visible to other users
**System Behavior:** The sharing setting was defaulted to public when the goal was created. The goal name and progress are visible to social contacts.

**User Impact:** The user's religious savings goal details are exposed to other users. This is a privacy violation.

**Recovery:** The default sharing setting is changed to private for all new goals. Existing public goals are retroactively set to private. An audit determines who viewed the goal.

### Round-up auto-save data leak — round-up amounts reveal spending patterns
**System Behavior:** The round-up from each transaction is saved. The detailed round-up history reveals the user's complete spending pattern when analyzed.

**User Impact:** If an account analyst views the round-up history, they can reconstruct the user's entire spending behavior.

**Recovery:** Round-up amounts are aggregated and not itemized in analytics views. Personal transaction data is masked in all reporting interfaces.

## 6. Business Logic Failures

### Early withdrawal penalty exception — user provides medical emergency proof but system applies penalty
**System Behavior:** The medical emergency exception is not implemented in the penalty logic. The system applies the standard 2% early withdrawal fee (1,000 ETB on 50,000 ETB).

**User Impact:** The user is charged 1,000 ETB despite having a valid medical emergency. "تم خصم رسوم السحب المبكر: 1,000 ETB" (Early withdrawal fee: 1,000 ETB.)

**Recovery:** Customer support reviews the medical documentation. The penalty is waived. The 1,000 ETB is refunded. The medical emergency exception is added to the penalty logic.

### Tier-based interest rate not applied — Tier 2 user (>100,000 ETB) gets 4.5% instead of 5.5%
**System Behavior:** The user's tier was upgraded to Tier 2, but the interest rate bucket was not updated. The user earns the standard rate of 4.5%.

**User Impact:** The user earns 4.5% instead of 5.5% on a 150,000 ETB balance. The annual loss is 1,500 ETB.

**Recovery:** The monthly interest reconciliation detects the rate mismatch. The correct rate is applied retroactively. The user receives the difference plus compensation.

### Fixed deposit lapses — user forgot FD maturity date, auto-renewal at a lower rate
**System Behavior:** The FD of 100,000 ETB auto-renews at the current rate of 4.0% instead of the original rate of 5.0%. The user is locked into a lower rate for another term.

**User Impact:** The user misses the opportunity to withdraw and reinvest at a better rate. The loss is 1,000 ETB in interest over the next term.

**Recovery:** A 7-day grace period is provided after maturity during which the user can withdraw without penalty. Notifications are sent at 30, 14, 7, and 1 day before maturity.

### Savings goal deadline missed — 12-month goal of 120,000 ETB, user saved only 80,000 ETB
**System Behavior:** The goal expires at the deadline. The status is set to "غير مكتمل" (Incomplete.) The 80,000 ETB remains in the savings account.

**User Impact:** The user did not reach the goal. The 80,000 ETB is still available. "لم تحقق هدف التوفير. تم تمديد المهلة 30 يوماً" (You did not achieve your savings goal. The deadline has been extended by 30 days.)

**Recovery:** A 30-day grace period is provided. The user can extend the goal, reduce the target, or withdraw the funds.

### Joint savings account — one holder withdraws without consent of the other
**System Behavior:** The joint account requires both holders to approve any withdrawal. A single holder's withdrawal request is denied.

**User Impact:** The first holder sees "يتطلب سحب الأموال موافقة جميع المستفيدين" (Withdrawal requires approval from all beneficiaries.)

**Recovery:** The second holder receives an approval request through the app. The second holder can approve or reject. The withdrawal is only processed after both approvals are received.

## 7. Performance & Scalability Failures

### End-of-month interest calculation — 500,000 accounts processed
**System Behavior:** The monthly interest calculation job processes 500,000 savings accounts. Each account requires a database read and write. The job takes 4 hours to complete.

**User Impact:** Interest is credited at different times depending on account processing order. Users checking their balance see "جاري احتساب الفائدة الشهرية" (Monthly interest being calculated.)

**Recovery:** Interest calculation is parallelized across 20 worker pods, each handling 25,000 accounts. Processing time is reduced to 30 minutes. Interest is credited in batches with notification.

### Auto-save engine overload — 200,000 auto-save rules execute on payday
**System Behavior:** On the 30th of each month (typical payday), 200,000 auto-save rules trigger simultaneously. The engine processes 5,000 rules per second.

**User Impact:** Auto-save deductions are delayed by up to 10 minutes. Users see "التوفير التلقائي قيد المعالجة" (Auto-save processing) instead of immediate deduction.

**Recovery:** Auto-save rules are executed in a staggered schedule based on user ID hash (0-999). The processing window is spread over 2 hours. Priority is given to rules expiring that day.

### Savings goal progress recalculation — 100,000 goals recalculated after bulk interest credit
**System Behavior:** After the monthly interest is credited, 100,000 savings goals need progress recalculation. The recalculation job competes with regular transaction processing for database resources.

**User Impact:** Users may see stale goal progress for up to 1 hour after interest is credited. Goal progress shows 42% instead of 45%.

**Recovery:** Goal progress is recalculated asynchronously with low database priority. The recalculation is completed within 30 minutes. Users are notified when the progress is updated.

## 8. Operational Failures

### Deployment rollback — v8.3.0 double-counts interest for one day
**System Behavior:** The canary deployment detects a 0.5% increase in total interest credited (double-counting for some accounts). The rollback is triggered.

**User Impact:** Approximately 200 accounts receive double interest for one day (average 5 ETB extra per account). Total overpayment: 1,000 ETB.

**Recovery:** The rollback completes within 2 minutes. The double-counted interest is reversed in the next calculation cycle. Affected users are notified.

### Configuration error — minimum balance set to 50,000 ETB instead of 500 ETB
**System Behavior:** A configuration change sets the minimum savings balance to 50,000 ETB. 80% of users' accounts show "minimum balance not met" status.

**User Impact:** Users with less than 50,000 ETB in savings see warning "الرصيد أقل من الحد الأدنى" (Balance below minimum.) Panic and support calls spike.

**Recovery:** A monitoring alert fires on the minimum balance change exceeding 10x the previous value. The configuration is reverted within 5 minutes. A communication is sent to all users explaining the error.

### Fixed deposit maturity notification failure — 5,000 FDs matured without notification
**System Behavior:** The scheduled notification job for FD maturities fails silently. 5,000 users with maturing FDs do not receive their maturity notification.

**User Impact:** Users miss the 7-day grace period for withdrawal without penalty. FDs auto-renew at the current lower rate.

**Recovery:** The notification failure is detected within 1 hour. Maturity notifications are sent retroactively. The grace period is extended by 7 days for all affected users.

## 9. Recovery Time Objectives Summary

| Failure Category | Detection Time | Recovery Time | RPO | Maximum Impact |
|-----------------|----------------|---------------|-----|----------------|
| Network (transient) | < 1 second | < 30 seconds | 0 | Single deposit delayed |
| Network (DNS outage) | 30 seconds | < 5 minutes | 0 | All savings ops blocked |
| Transaction failure | < 100ms | < 5 seconds | 0 | Single withdrawal/ deposit |
| External dependency | < 10 seconds | < 15 minutes | 0 | Interest benchmark down |
| Data inconsistency | < 5 minutes | < 1 hour | < 5 seconds | Balance discrepancy |
| Security incident | < 1 minute | < 4 hours | 0 | Withdrawal held for review |
| Business logic | < 1 hour | < 24 hours | 0 | Early withdrawal penalty |
| Performance degradation | < 1 minute | < 5 minutes | 0 | Slow interest calculation |
| Operational (deployment) | < 5 minutes | < 10 minutes | < 1 minute | Double-counted interest |

## Changelog

| Date | Version | Changes |
|------|---------|---------|
| 2026-05-29 | 1.0 | Initial release — 7 categories covering network, transactions, external dependencies, data consistency, security, business logic, and performance failures for savings and fixed deposit feature |

---

*Version: 1.0 | Last updated: 2026-05-29 | Owner: Savings Engineering Team*
