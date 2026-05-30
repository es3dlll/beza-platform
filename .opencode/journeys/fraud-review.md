# Journey 9: Fraud Review (Operations Team)

## Goal

Ops team member receives a fraud alert (risk score 850/1000), reviews transaction details, identifies suspicious patterns (device mismatch, new location, abnormal amount), and decides to block transaction, freeze account, and escalate for investigation.

## Actor

- Role: Fraud Operations Analyst (موظف مكافحة الاحتيال)
- Device: Web (Beza Ops Dashboard)
- Language: Arabic (interface default), English optional
- Tier: Internal (Ops Team)
- Connectivity: Online (VPN-secured internal network)

## Preconditions

- Fraud detection engine is active with rule set v2.3
- Transaction triggered risk score ≥ 800 (high risk)
- Analyst is logged into Beza Ops Dashboard with fraud_review permission
- Case is auto-assigned by the fraud engine

## Success Flow

| Step | Actor        | Action                                                                                                                              | System                                                                                                                                                                                                                  | Event Emitted                              | State Change                                        |
| ---- | ------------ | ----------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------ | --------------------------------------------------- |
| 1    | Fraud Engine | —                                                                                                                                   | Real-time scoring on incoming transaction: Send 750,000 SYP from user 0933-111-222 to 0933-999-888. Score: 850/1000. Rules triggered: R04 (device change < 24h), R12 (location mismatch), R07 (amount 3× daily average) | `FRAUD_ALERT_TRIGGERED`                    | Alert: created                                      |
| 2    | System       | —                                                                                                                                   | Transaction placed on hold (pending review). PUSH sent to on-call analyst. Dashboard notification badge +1                                                                                                              | `TRANSACTION_HELD`                         | Transaction: on-hold                                |
| 3    | Analyst      | Opens Ops Dashboard, sees fraud queue (1 case pending)                                                                              | Shows case list with priority: HIGH, score 850, timestamp, user name "أحمد الخالد"                                                                                                                                      | —                                          | —                                                   |
| 4    | Analyst      | Clicks on case TKT-FR-20260529-001                                                                                                  | Opens case detail view: transaction info, user profile, risk factors                                                                                                                                                    | —                                          | —                                                   |
| 5    | System       | —                                                                                                                                   | Displays risk factors panel: 1. الجهاز: جهاز جديد (iPhone 14 - first login before 6 ساعات) / 2. الموقع: حلب (آخر معاملة من دمشق) / 3. المبلغ: 750,000 ل.س (المعدل اليومي: 250,000 ل.س) / 4. الوقت: 02:30 صباحاً         | —                                          | —                                                   |
| 6    | Analyst      | Reviews user profile: created 14 days ago, Tier 2, KYC approved, 8 legitimate transactions, total volume 1,200,000 SYP              | Checks: KYC documents (national ID matches selfie), previous transaction pattern (small amounts, daytime, same device, Damascus location)                                                                               | —                                          | —                                                   |
| 7    | Analyst      | Clicks "سجل أجهزة المستخدم" (Device History)                                                                                        | Shows: previous device "Samsung A54 - دمشق - IP 78.95.x.x", current device "iPhone 14 - حلب - IP 5.155.x.x"                                                                                                             | —                                          | —                                                   |
| 8    | Analyst      | Notes SIM swap flag: user changed SIM 3 days ago at Syriatel shop (SIM swap risk in Syria — common fraud vector)                    | Cross-references: SIM swap date matches device change date                                                                                                                                                              | —                                          | —                                                   |
| 9    | Analyst      | Clicks "عرض معاملات الوكيل" (Agent Transactions)                                                                                    | Shows: 750,000 SYP is going to agent account "مكتب الشام للصرافة" — agent activated 2 days ago, no prior transactions                                                                                                   | —                                          | —                                                   |
| 10   | Analyst      | Makes decision: "حظر المعاملة وتجميد الحسابين وتحويل للتحقيق" (Block transaction, freeze both accounts, escalate to investigations) | Selects actions in decision panel                                                                                                                                                                                       | —                                          | —                                                   |
| 11   | System       | —                                                                                                                                   | Blocks transaction (never settles). Freezes sender wallet. Freezes agent wallet. Triggers investigation case.                                                                                                           | `FRAUD_ACTION_BLOCK` `FRAUD_ACTION_FREEZE` | Transaction: blocked, Sender: frozen, Agent: frozen |
| 12   | Analyst      | Writes notes: "SIM swap قبل 3 أيام. جهاز جديد. موقع جديد. مبلغ غير طبيعي. وكيل مشبوه. احتمالية احتيال عالية."                       | Saves case notes                                                                                                                                                                                                        | —                                          | —                                                   |
| 13   | System       | —                                                                                                                                   | Sends SMS to user: "عذراً، تم تعليق حسابك مؤقتاً لأسباب أمنية. يرجى الاتصال بخدمة العملاء على 1234."                                                                                                                    | `CUSTOMER_NOTIFIED`                        | —                                                   |
| 14   | System       | —                                                                                                                                   | Escalates case to Senior Fraud Investigator. Adds case to queue.                                                                                                                                                        | `FRAUD_ESCALATED`                          | Case: escalated                                     |
| 15   | Analyst      | Returns to fraud queue, ready for next alert                                                                                        | —                                                                                                                                                                                                                       | —                                          | —                                                   |

## Alternative Flows

### A1: False positive (transaction is legitimate)

Analyst reviews and determines user is travelling (moved from Damascus to Aleppo). Calls user to verify identity via security questions. User confirms. Analyst clicks "تأكيد العملية" (Release Transaction). Transaction proceeds. Risk rule updated for user's travel pattern.

### A2: Agent fraud pattern detected

If agent is complicit (e.g., agent "مكتب الشام للصرافة" processes multiple large amounts to different users from new SIM accounts), analyst escalates to agent compliance team for agent contract termination.

### A3: Low confidence (score 600-799 — medium risk)

Auto-rule: transaction held, SMS verification sent to user: "إذا كنت تقصد هذه العملية، رد بكلمة 'نعم' خلال 5 دقائق". If user confirms via SMS, transaction released. If no response, held for manual review.

### A4: Known victim of SIM swap

If user calls support saying "رقمي مسروق" (my number is stolen), support immediately freezes account and escalates as urgent. Analyst bypasses review and blocks all pending transactions.

## Failure Flows

### F1: Analyst does not act within SLA (15 minutes)

After 15 minutes of alert without action, auto-escalate to senior analyst. After 30 minutes, escalate to fraud team lead and send SMS to user: "نشاط غير عادي على حسابك. إذا لم تكن أنت، اتصل على 1234 فوراً."

### F2: Action taken on wrong case (human error)

System requires confirmation dialog: "هل أنت متأكد من حظر هذا الحساب؟". All actions are logged with audit trail. Reversible within 5 minutes by supervisor.

### F3: Analytics engine offline

If scoring engine is down, transactions bypass fraud check and process normally. Queue stored for retrospective review. Alert sent to engineering team.

### F4: User falsely frozen (complaint escalates)

If user complains and investigation later proves legitimate, account unfrozen within 1 hour. Compensation of 5,000 SYP goodwill credit applied. Agent reported to agent compliance for incorrect information.

## Notifications

- SMS (user - freeze): "عذراً، تم تعليق حسابك مؤقتاً لأسباب أمنية. يرجى الاتصال بخدمة العملاء على 1234."
- SMS (user - release): "تم إلغاء تعليق حسابك. يمكنك استخدام Beza كالمعتاد. نأسف للإزعاج."
- Internal alert (Ops dashboard): "🔴 تنبيه احتيال - المعاملة {tx_id} - النقاط: 850 - {user_name}"
- Email (escalation): "FR-{case_id} تم رفع البلاغ إلى فريق التحقيق. الإجراءات: حظر + تجميد."
- SMS (agent freeze): "عذراً، تم تجميد حساب التاجر الخاص بك لأسباب أمنية. سيتم التواصل معك قريباً."

## Ledger Impact

| Account                 | Debit              | Credit | Currency              |
| ----------------------- | ------------------ | ------ | --------------------- |
| Sender Wallet           | 0 (hold released)  | —      | SYP                   |
| Agent Wallet            | 0 (never credited) | —      | SYP                   |
| Fraud Provision Account | —                  | —      | No movement (blocked) |

## State Changes

- Transaction: on-hold → blocked (never settles)
- Sender account: active → frozen
- Agent account: active → frozen
- Fraud case: open → under review → escalated
- Risk score: 850 → N/A (case resolved)

## UI Screens

1. Ops Dashboard (Fraud Queue) → 2. Case Detail (Transaction + Profile + Risk Factors) → 3. Device History → 4. Agent History → 5. Decision Panel → 6. Action Confirmation → 7. Case Closed (Return to Queue)
