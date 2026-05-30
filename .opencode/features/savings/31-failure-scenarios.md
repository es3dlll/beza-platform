# 31. Savings — Failure Scenarios

> **Purpose:** Document every failure mode for the Beza Savings feature — savings accounts, goal-based savings, auto-save, profit distribution, early withdrawal, and fixed deposits. Uses real SYP amounts and Arabic messaging only. Syria context: Islamic profit sharing (المشاركة في الربح), CBS benchmark, Syrian Telecom for SMS.

---

## 1. Network Failures

### Internet cut during savings deposit after wallet debited but savings not credited
**System Behavior:** The user's wallet is debited 10,000 SYP. The savings account is not credited. The transaction is marked `SAVINGS_DEPOSIT_PENDING`.

**User Impact:** The user sees "تم خصم 10,000 ل.س. جاري إيداعها في حساب التوفير"

**Recovery:** A background job checks for pending deposits every 30 seconds. When the connection is restored, the savings account is credited. The user receives a notification confirming the deposit.

### API timeout (>5s) during savings balance query
**System Behavior:** The savings service times out. The app returns the cached savings balance, which can be up to 5 minutes stale.

**User Impact:** The user sees "جاري التحميل..." followed by the cached balance with a warning "آخر تحديث: منذ 3 دقائق"

**Recovery:** The circuit breaker resets after 15 seconds. The next request fetches a fresh balance from the database.

### DNS failure for savings-api.beza.com
**System Behavior:** The savings section of the app is inaccessible because the DNS cannot resolve the API endpoint. The rest of the app functions normally.

**User Impact:** The user sees "خدمة التوفير غير متوفرة حالياً" Other wallet functions continue working.

**Recovery:** DNS failover to the secondary region is triggered within 5 minutes. The app retries the connection every 30 seconds.

### WebSocket disconnect during real-time savings goal progress tracking
**System Behavior:** The goal progress bar on the dashboard freezes at the last synced value. The server stores the progress updates for replay.

**User Impact:** The user sees "جاري التحديث..." on the goal progress indicator. Contributions are still recorded even though the progress bar is frozen.

**Recovery:** When the WebSocket reconnects, the progress is recalculated from the actual savings balance. The user sees the updated progress bar.

### Syrian Telecom SMS provider unavailable for savings milestone notification
**System Behavior:** The user reaches 50% of their savings goal (250,000 SYP). The milestone SMS cannot be delivered because the Syrian Telecom SMS gateway is unavailable.

**User Impact:** The user does not receive the celebration SMS. The in-app notification "أكملت 50% من هدف التوفير!" is still delivered.

**Recovery:** The SMS is retried for up to 24 hours. The push notification serves as the primary celebration channel.

## 2. Transaction Failures

### Insufficient wallet balance for auto-save rule — 5,000 SYP auto-save fails
**System Behavior:** The auto-save engine checks the wallet balance on the scheduled day. The balance is below 5,000 SYP. The auto-save is skipped for that day.

**User Impact:** The user sees a notification "فشل التوفير التلقائي. رصيد غير كافٍ"

**Recovery:** The auto-save retries the next day. If it fails 3 consecutive times, the auto-save rule is automatically paused and the user is notified.

### Early withdrawal penalty miscalculated — user withdraws 100,000 SYP after 3 months (penalty 2% but charged 5%)
**System Behavior:** The penalty engine applies a 5% fee instead of the correct 2% fee. The user is charged 5,000 SYP instead of 2,000 SYP.

**User Impact:** The user sees "رسوم السحب المبكر: 5,000 ل.س." The user is overcharged by 3,000 SYP.

**Recovery:** The user contacts support to report the discrepancy. The penalty is corrected. 3,000 SYP plus compensatory profit is refunded. The bug is fixed in the penalty calculation logic.

### Duplicate withdrawal request — user submits withdrawal twice
**System Behavior:** The idempotency key prevents the second withdrawal from being processed. The second request returns HTTP 409 Conflict.

**User Impact:** The user sees "تمت معالجة طلب السحب مسبقاً"

**Recovery:** The first withdrawal is processed normally. The second request is silently discarded.

### Minimum balance violation — user withdraws all but 200 SYP (minimum 1,000 SYP required)
**System Behavior:** The pre-validation checks that the remaining balance after withdrawal is at least 1,000 SYP. The remaining 200 SYP violates this requirement. The withdrawal is rejected.

**User Impact:** The user sees "يجب أن يتبقى 1,000 ل.س. على الأقل في حساب التوفير"

**Recovery:** The user adjusts the withdrawal amount. The UI shows the maximum withdrawable amount (current balance minus 1,000 SYP).

### Fixed deposit maturity — auto-renewal fails due to insufficient main wallet for zakat deduction
**System Behavior:** The fixed deposit of 500,000 SYP matures. The system needs to deduct 2,500 SYP in zakat from the main wallet. The main wallet has only 300 SYP.

**User Impact:** The FD renews automatically. The zakat deduction is deferred. "خصم الزكاة مؤجل"

**Recovery:** The system retries the zakat deduction daily for 7 days. If the main wallet remains insufficient, the system partially liquidates the FD to cover the zakat.

### Savings goal target already met but system continues auto-save
**System Behavior:** The savings goal target of 500,000 SYP is reached. The auto-save rule is still active. An additional 25,000 SYP is saved beyond the target.

**User Impact:** The user saves 525,000 SYP instead of the planned 500,000 SYP. "لقد تجاوزت هدف التوفير!"

**Recovery:** The auto-save rule is automatically paused when the goal reaches 100%. The user can set a new goal or withdraw the excess amount.

### Hajj savings goal — withdrawal restriction violated (user withdraws before season)
**System Behavior:** The Hajj savings goal (2,000,000 SYP) has a seasonal withdrawal restriction. The user tries to withdraw 500,000 SYP outside the Hajj registration period. The withdrawal is blocked.

**User Impact:** The user sees "لا يمكن السحب من هدف الحج خارج موسم التسجيل"

**Recovery:** The user can withdraw only during the designated Hajj registration window announced by the Ministry of Awqaf. Emergency withdrawals are allowed with documented proof.

## 3. External Dependency Failures

### CBS (Central Bank of Syria) profit rate benchmark API down
**System Behavior:** The savings profit rate calculation uses the last known benchmark rate, which is cached for up to 24 hours.

**User Impact:** Profit accrues at the previous rate. "معدل الربح: 4.5% (قد لا يعكس السعر الحالي)"

**Recovery:** When the benchmark feed is restored, the rate is updated. Profit is recalculated for the affected period.

### Syrian Telecom SMS gateway unavailable for savings confirmation
**System Behavior:** The SMS delivery is queued on SQS. A push notification is sent via Firebase Cloud Messaging.

**User Impact:** The user may not receive the SMS "تم إيداع 10,000 ل.س. في حساب التوفير" The in-app notification is delivered.

**Recovery:** The SMS is retried for up to 24 hours. The transaction appears in the in-app history immediately.

### Partner bank API timeout for fixed deposit funding from bank account
**System Behavior:** The bank transfer for funding the fixed deposit times out. The FD is marked as `FUNDING_PENDING`.

**User Impact:** The user sees "في انتظار تحويل الأموال من الحساب المصرفي"

**Recovery:** A poller checks the bank transaction status every 30 seconds for up to 2 hours. On confirmation, the FD is activated. If the transfer fails, the FD is cancelled and the user is notified.

### Credit reference bureau API down during savings account upgrade
**System Behavior:** The credit check for a premium savings account upgrade cannot be completed because the Syrian Credit Bureau (شركة ضمان الائتمان) API is down. The upgrade is blocked.

**User Impact:** The user sees "التحقق من الائتمان غير متوفر. حاول مرة أخرى لاحقاً"

**Recovery:** The upgrade is deferred. The system retries every hour for 24 hours. If the bureau remains unavailable, a manual override is available with manager approval.

### CBS deposit insurance system API unavailable
**System Behavior:** The system cannot verify the deposit insurance limit (500,000 SYP per depositor) because the CBS deposit insurance API is down.

**User Impact:** No immediate user impact. The insurance verification is delayed.

**Recovery:** The insurance limit is cached and served from the last successful response. When the API is restored, the cache is refreshed.

## 4. Data Consistency Failures

### Profit accrual DB write fails mid-month — 15 days accrued but write fails on day 16
**System Behavior:** The daily profit accrual job successfully writes entries for 15 days. On day 16, the database write fails. The profit for that day is not recorded.

**User Impact:** The user earns slightly less profit for the month. "تم احتساب الربح لمدة 29 يوماً بدلاً من 30"

**Recovery:** The monthly profit reconciliation detects the missing day. The missing profit (approximately 6 SYP on a 200,000 SYP balance at 4.5% annual rate) is compensated. The user is credited the correct amount.

### Cache inconsistency — savings balance shown as 300,000 SYP but actual is 290,000 SYP
**System Behavior:** The cache-aside pattern with version checking detects the TTL mismatch. The cache entry is invalidated and a fresh value is loaded from the database.

**User Impact:** The user initiates a withdrawal of 295,000 SYP based on the cached balance. The withdrawal is rejected at the database level. "الرصيد الفعلي: 290,000 ل.س."

**Recovery:** The cache is invalidated and the fresh balance is loaded. Strong consistency is enforced for all balance-affecting writes.

### Savings goal progress event lost — 25,000 SYP deposit not reflected in goal tracking
**System Behavior:** The deposit is credited to the savings account. The Kafka event carrying the goal progress update is lost. The goal progress is stuck at 40% instead of 50%.

**User Impact:** The user sees the goal progress as "هدف التوفير: 40% مكتمل" even though they have deposited more.

**Recovery:** The goal progress is recalculated from the actual savings balance every 6 hours. The progress corrects itself automatically within the next recalculation cycle.

### Dual-write to savings ledger and main wallet fails partially
**System Behavior:** The main wallet is debited. The savings ledger write fails. The Saga pattern detects the inconsistency and reverses the main wallet debit.

**User Impact:** The user sees "تم إلغاء الإيداع. لم يتم خصم أي مبلغ"

**Recovery:** The compensation transaction is completed within 2 seconds. The user retries the deposit.

### Fixed deposit profit rate table corrupted — wrong rate applied to 2,000,000 SYP FD
**System Behavior:** The FD is booked at 3% profit instead of the correct 5% due to a rate table corruption. The user loses 40,000 SYP in profit over a 12-month term.

**User Impact:** The user earns 60,000 SYP in profit instead of 100,000 SYP. This is a 40,000 SYP loss.

**Recovery:** The monthly reconciliation detects the rate discrepancy. The rate is corrected retroactively. The user receives the 40,000 SYP difference plus compensatory profit.

### Savings goal name corruption — Unicode encoding issue with Arabic goal names
**System Behavior:** The database encoding fails to store Arabic characters properly. The goal name "هدف الحج" is stored as garbled text "??? ??"

**User Impact:** The user sees corrupted Arabic text for the goal name. "???? ???? — اسم الهدف غير مقروء"

**Recovery:** The encoding is corrected to UTF-8. The goal name is restored from the application log. A validation check prevents non-UTF-8 data from being stored.

## 5. Security Failures

### Fraud false positive — user withdrawing 500,000 SYP savings to purchase property flagged
**System Behavior:** The AML rules engine triggers on the large withdrawal amount (greater than 250,000 SYP) combined with an irregular pattern. The withdrawal is placed in `PENDING_REVIEW`.

**User Impact:** The user sees "السحب قيد المراجعة. سيتم إعلامك خلال 24 ساعة"

**Recovery:** The compliance team reviews the documentation (property sale agreement). If legitimate, the withdrawal is released within the 4-hour SLA.

### Fraud false negative — unauthorized early withdrawal via stolen phone
**System Behavior:** The attacker uses a stolen phone to withdraw 150,000 SYP from savings to the main wallet, then transfers it out. The behavioral model does not flag the transaction.

**User Impact:** The legitimate user loses 150,000 SYP in savings.

**Recovery:** Insurance covers 80% of the verified loss. The device fingerprint and geolocation are used for retrospective fraud model training. The stolen device is blacklisted.

### Unauthorized access to savings admin — profit rate override
**System Behavior:** An attacker gains access to the admin panel and changes the savings profit rate from 4.5% to 8%. All savings accounts earn inflated profit.

**User Impact:** Beza overpays 3.5% extra profit across 50,000 accounts. The monthly loss is approximately 1.75 million SYP.

**Recovery:** Profit rate changes require MFA plus dual approval from Product and Finance teams. An audit log entry triggers an immediate SIEM alert on any `RATE_OVERRIDE` event.

### Savings goal privacy breach — user's "هدف الحج" (Hajj savings) goal visible to other users
**System Behavior:** The sharing setting was defaulted to public when the goal was created. The goal name and progress are visible to social contacts.

**User Impact:** The user's religious savings goal details are exposed to other users. This is a privacy violation.

**Recovery:** The default sharing setting is changed to private for all new goals. Existing public goals are retroactively set to private. An audit determines who viewed the goal.

### Round-up auto-save data leak — round-up amounts reveal spending patterns
**System Behavior:** The round-up from each transaction is saved. The detailed round-up history reveals the user's complete spending pattern when analyzed.

**User Impact:** If an account analyst views the round-up history, they can reconstruct the user's entire spending behavior.

**Recovery:** Round-up amounts are aggregated and not itemized in analytics views. Personal transaction data is masked in all reporting interfaces.

### Social engineering — attacker impersonates support to reset savings PIN
**System Behavior:** An attacker calls customer support claiming to be the account holder and requests a PIN reset for the savings account. The agent processes the reset without proper verification.

**User Impact:** The attacker gains access to the savings account and withdraws 200,000 SYP.

**Recovery:** PIN resets require video call verification with matching national ID. A 24-hour cooling period is enforced before the new PIN is active. The stolen amount is covered by insurance.

## 6. Business Logic Failures

### Early withdrawal penalty exception — user provides medical emergency proof but system applies penalty
**System Behavior:** The medical emergency exception is not implemented in the penalty logic. The system applies the standard 2% early withdrawal fee (2,000 SYP on 100,000 SYP).

**User Impact:** The user is charged 2,000 SYP despite having a valid medical emergency. "تم خصم رسوم السحب المبكر: 2,000 ل.س."

**Recovery:** Customer support reviews the medical documentation. The penalty is waived. The 2,000 SYP is refunded. The medical emergency exception is added to the penalty logic.

### Tier-based profit rate not applied — Tier 2 user (>500,000 SYP) gets 4.5% instead of 5.5%
**System Behavior:** The user's tier was upgraded to Tier 2, but the profit rate bucket was not updated. The user earns the standard rate of 4.5%.

**User Impact:** The user earns 4.5% instead of 5.5% on a 600,000 SYP balance. The annual loss is 6,000 SYP.

**Recovery:** The monthly profit reconciliation detects the rate mismatch. The correct rate is applied retroactively. The user receives the difference plus compensation.

### Fixed deposit lapses — user forgot FD maturity date, auto-renewal at a lower rate
**System Behavior:** The FD of 500,000 SYP auto-renews at the current rate of 4.0% instead of the original rate of 5.0%. The user is locked into a lower rate for another term.

**User Impact:** The user misses the opportunity to withdraw and reinvest at a better rate. The loss is 5,000 SYP in profit over the next term.

**Recovery:** A 7-day grace period is provided after maturity during which the user can withdraw without penalty. Notifications are sent at 30, 14, 7, and 1 day before maturity.

### Savings goal deadline missed — 12-month goal of 600,000 SYP, user saved only 400,000 SYP
**System Behavior:** The goal expires at the deadline. The status is set to "غير مكتمل" The 400,000 SYP remains in the savings account.

**User Impact:** The user did not reach the goal. The 400,000 SYP is still available. "لم تحقق هدف التوفير. تم تمديد المهلة 30 يوماً"

**Recovery:** A 30-day grace period is provided. The user can extend the goal, reduce the target, or withdraw the funds.

### Joint savings account — one holder withdraws without consent of the other
**System Behavior:** The joint account requires both holders to approve any withdrawal. A single holder's withdrawal request is denied.

**User Impact:** The first holder sees "يتطلب سحب الأموال موافقة جميع المستفيدين"

**Recovery:** The second holder receives an approval request through the app. The second holder can approve or reject. The withdrawal is only processed after both approvals are received.

### Islamic profit distribution timing error — profit calculated on Gregorian calendar instead of Hijri
**System Behavior:** The profit distribution engine calculates monthly profit based on the Gregorian calendar (30/31 days) instead of the Hijri calendar (29/30 days). The profit amounts are slightly misaligned with Islamic banking standards.

**User Impact:** Users receive marginally different profit amounts. Profit may be 0.5% higher or lower than the Islamic calculation method.

**Recovery:** The profit calculation is corrected to use the Hijri calendar. The difference is reconciled in the next distribution cycle. A sharia compliance audit is triggered.

### Zakat deduction on savings — wrong calculation for amounts below nisab threshold
**System Behavior:** The automated zakat deduction engine deducts 2.5% from all savings balances including those below the nisab threshold (approximately 200,000 SYP). The deduction should only apply to balances above nisab.

**User Impact:** Users with balances below nisab have 2.5% incorrectly deducted. A user with 50,000 SYP loses 1,250 SYP.

**Recovery:** The zakat logic is corrected to check the nisab threshold before deduction. Incorrect deductions are refunded with a goodwill credit of 500 SYP.

## 7. Performance & Scalability Failures

### End-of-month profit calculation — 250,000 accounts processed
**System Behavior:** The monthly profit calculation job processes 250,000 savings accounts. Each account requires a database read and write. The job takes 3 hours to complete.

**User Impact:** Profit is credited at different times depending on account processing order. Users checking their balance see "جاري احتساب الأرباح الشهرية"

**Recovery:** Profit calculation is parallelized across 20 worker pods, each handling 12,500 accounts. Processing time is reduced to 25 minutes. Profit is credited in batches with notification.

### Auto-save engine overload — 100,000 auto-save rules execute on payday
**System Behavior:** On the last day of the month (typical public sector payday in Syria), 100,000 auto-save rules trigger simultaneously. The engine processes 3,000 rules per second.

**User Impact:** Auto-save deductions are delayed by up to 10 minutes. Users see "التوفير التلقائي قيد المعالجة" instead of immediate deduction.

**Recovery:** Auto-save rules are executed in a staggered schedule based on user ID hash (0-999). The processing window is spread over 2 hours. Priority is given to rules expiring that day.

### Savings goal progress recalculation — 50,000 goals recalculated after bulk profit credit
**System Behavior:** After the monthly profit is credited, 50,000 savings goals need progress recalculation. The recalculation job competes with regular transaction processing for database resources.

**User Impact:** Users may see stale goal progress for up to 1 hour after profit is credited. Goal progress shows 42% instead of 45%.

**Recovery:** Goal progress is recalculated asynchronously with low database priority. The recalculation is completed within 30 minutes. Users are notified when the progress is updated.

## 8. Operational Failures

### Deployment rollback — v8.3.0 double-counts profit for one day
**System Behavior:** The canary deployment detects a 0.5% increase in total profit credited (double-counting for some accounts). The rollback is triggered.

**User Impact:** Approximately 200 accounts receive double profit for one day (average 25 SYP extra per account). Total overpayment: 5,000 SYP.

**Recovery:** The rollback completes within 2 minutes. The double-counted profit is reversed in the next calculation cycle. Affected users are notified.

### Configuration error — minimum balance set to 100,000 SYP instead of 1,000 SYP
**System Behavior:** A configuration change sets the minimum savings balance to 100,000 SYP. 80% of users' accounts show "minimum balance not met" status.

**User Impact:** Users with less than 100,000 SYP in savings see warning "الرصيد أقل من الحد الأدنى" Panic and support calls spike.

**Recovery:** A monitoring alert fires on the minimum balance change exceeding 10x the previous value. The configuration is reverted within 5 minutes. A communication is sent to all users explaining the error.

### Fixed deposit maturity notification failure — 2,000 FDs matured without notification
**System Behavior:** The scheduled notification job for FD maturities fails silently. 2,000 users with maturing FDs do not receive their maturity notification.

**User Impact:** Users miss the 7-day grace period for withdrawal without penalty. FDs auto-renew at the current lower rate.

**Recovery:** The notification failure is detected within 1 hour. Maturity notifications are sent retroactively. The grace period is extended by 7 days for all affected users.

### CBS reporting deadline missed — daily savings aggregate report not submitted
**System Behavior:** The automated report generation job fails. The daily aggregate savings report to CBS is not submitted by the 10:00 AM deadline.

**User Impact:** No direct user impact. Beza faces a regulatory compliance issue with CBS.

**Recovery:** The job is retried automatically every 30 minutes. If the report is more than 4 hours late, the compliance team submits a manual report and notifies CBS of the delay.

## 9. Recovery Time Objectives Summary

| Failure Category | Detection Time | Recovery Time | RPO | Maximum Impact |
|-----------------|----------------|---------------|-----|----------------|
| Network (transient) | < 1 second | < 30 seconds | 0 | Single deposit delayed |
| Network (DNS outage) | 30 seconds | < 5 minutes | 0 | All savings ops blocked |
| Transaction failure | < 100ms | < 5 seconds | 0 | Single withdrawal/deposit |
| External dependency | < 10 seconds | < 15 minutes | 0 | CBS benchmark down |
| Data inconsistency | < 5 minutes | < 1 hour | < 5 seconds | Balance discrepancy |
| Security incident | < 1 minute | < 4 hours | 0 | Withdrawal held for review |
| Business logic | < 1 hour | < 24 hours | 0 | Early withdrawal penalty |
| Performance degradation | < 1 minute | < 5 minutes | 0 | Slow profit calculation |
| Operational (deployment) | < 5 minutes | < 10 minutes | < 1 minute | Double-counted profit |

## Changelog

| Date | Version | Changes |
|------|---------|---------|
| 2026-05-29 | 1.0 | Initial release — 7 categories covering network, transactions, external dependencies, data consistency, security, business logic, and performance failures for savings and fixed deposit feature — Syria context only |

---

*Version: 1.0 | Last updated: 2026-05-29 | Owner: Savings Engineering Team*
