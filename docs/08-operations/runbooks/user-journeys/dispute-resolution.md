# Journey 8: Dispute Resolution

## Goal

User claims a transaction was incorrect (wrong amount, wrong recipient, duplicate charge). Opens a support ticket via SMS, phone, or in-app chat. Support reviews and resolves.

## Actor

- Role: User (Tier 1/2, filing a dispute)
- Device: Mobile (in-app chat) or SMS or Phone call (USSD callback)
- Language: Arabic (default)
- Tier: Any
- Connectivity: Online preferred, SMS/phone fallback

## Preconditions

- User has completed at least one transaction
- Dispute is filed within 30 days of the transaction date
- Transaction reference number is available (from SMS or transaction history)

## Success Flow

| Step | Actor         | Action                                                                                               | System                                                                                                        | Event Emitted      | State Change       |
| ---- | ------------- | ---------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------- | ------------------ | ------------------ |
| 1    | User          | Opens app, taps "الدعم" (Support) → "الإبلاغ عن مشكلة" (Report Issue)                                | Shows recent transaction list (last 30 days)                                                                  | —                  | —                  |
| 2    | User          | Selects disputed transaction: "تحويل 5,000 ل.س إلى خالد - 28 أيار 2026"                              | Shows transaction details: "الحالة: مكتمل / المبلغ: 5,000 ل.س / الرسوم: 150 ل.س / المستلم: خالد 0933-456-789" | —                  | —                  |
| 3    | User          | Taps "الإبلاغ عن مشكلة" and selects reason from menu: "مبلغ خاطئ" (Wrong Amount)                     | Displays form: description field, optional screenshot upload                                                  | —                  | —                  |
| 4    | User          | Writes description: "أرسلت 5,000 ل.س عن طريق الخطأ كان المفروض 2,000 ل.س فقط" and uploads screenshot | Creates support ticket with priority: normal                                                                  | `DISPUTE_CREATED`  | Dispute: open      |
| 5    | System        | —                                                                                                    | Assigns ticket to support agent. Sends confirmation to user: SMS + in-app message.                            | —                  | —                  |
| 6    | User          | Receives SMS: "تم استلام بلاغك TKT-28473. سنتواصل معك خلال 24 ساعة. Beza"                            | —                                                                                                             | —                  | —                  |
| 7    | Support Agent | Reviews ticket via Beza Ops dashboard                                                                | Checks: transaction details, user history, recipient history, device fingerprint, IP logs                     | —                  | —                  |
| 8    | Support Agent | Identifies: agent error — agent entered 5,000 instead of 2,000 (check agent session logs)            | Determines root cause                                                                                         | —                  | —                  |
| 9    | Support Agent | Approves reversal of 3,000 SYP difference from agent commission account                              | System initiates reversal                                                                                     | `DISPUTE_RESOLVED` | Dispute: resolved  |
| 10   | System        | —                                                                                                    | Reverses 3,000 SYP + 50 SYP adjustment fee to user wallet                                                     | —                  | Balance: corrected |
| 11   | User          | Receives SMS: "تم حل البلاغ TKT-28473. تم إرجاع 3,050 ل.س إلى محفظتك. الرصيد: 38,050 ل.س."           | —                                                                                                             | —                  | —                  |
| 12   | User          | Opens app → support → sees ticket status "تم الحل" (Resolved)                                        | Shows resolution summary                                                                                      | —                  | —                  |
| 13   | User          | Optionally rates support experience 1-5 stars                                                        | Stores rating                                                                                                 | —                  | —                  |

## Alternative Flows

### A1: User files via SMS

User sends SMS to 1234: "نزاع {reference_number}". System responds with automated menu: "1. عرض تفاصيل النزاع 2. التحدث مع خدمة العملاء". User replies with number to navigate.

### A2: User calls phone support (call centre)

User dials 1234. IVR: "اضغط 1 للعربية / Press 2 for English. للإبلاغ عن نزاع، اضغط 3". User presses 3, connected to agent. Agent verifies identity via security questions (last transaction, balance, national ID number).

### A3: Recipient not found (wrong number)

If user sent to wrong number (e.g., 0933-456-788 instead of 0933-456-789) and recipient is not on Beza: SMS to wrong number has been sent but not claimed. Support cancels the pending SMS claim code and refunds user.

### A4: User error (legitimate transaction)

If investigation confirms user truly authorised the transaction (correct PIN, correct recipient), support explains: "عذراً، تم تأكيد العملية برقم PIN صحيح. لا يمكن الإلغاء." User is educated on double-checking before confirming.

### A5: Duplicate charge

If system error caused double deduction, agent approves immediate reversal of duplicate amount. User notified within 2 hours.

## Failure Flows

### F1: Ticket not assigned within 4 hours

Escalation triggered. Supervisor assigns manually. If no assignment within 8 hours, auto-escalate to senior support.

### F2: Dispute filed after 30 days

System auto-rejects: "عذراً، لا يمكن استقبال بلاغات بعد 30 يوماً من تاريخ العملية." User directed to visit nearest agent for in-person complaint.

### F3: User unresponsive during investigation

If user doesn't respond to 3 follow-up attempts (SMS, push, email), ticket auto-closed after 7 days with status "غير قابل للتحقيق" (Unresolvable).

### F4: Fraud detected during dispute

If investigation reveals user is attempting fraud (e.g., claiming unauthorised transaction but evidence shows user's PIN and device), ticket escalated to fraud team. Account may be frozen.

## Notifications

- SMS (ticket created): "تم استلام بلاغك {ticket_id}. سنتواصل معك خلال 24 ساعة. Beza"
- SMS (update): "تحديث البلاغ {ticket_id}: قيد المراجعة من قبل فريق الدعم."
- SMS (resolved): "تم حل البلاغ {ticket_id}. تم إرجاع {amount} ل.س. شكراً لصبرك."
- Push (new reply): "رد جديد على بلاغك {ticket_id}"
- SMS (auto-close warning): "البلاغ {ticket_id}: لم نتمكن من التواصل معك. سيغلق بعد 3 أيام."

## Ledger Impact

| Account                  | Debit     | Credit    | Currency |
| ------------------------ | --------- | --------- | -------- |
| User Wallet              | —         | 3,050 SYP | SYP      |
| Agent Commission Account | 3,000 SYP | —         | SYP      |
| Beza Adjustment Account  | 50 SYP    | —         | SYP      |

## State Changes

- Dispute: open → under review → resolved (or rejected)
- User balance: restored by 3,050 SYP
- Transaction: completed → partially reversed
- Agent commission: adjusted

## UI Screens

1. Home → 2. Support → 3. Report Issue → 4. Transaction Selection → 5. Issue Category → 6. Description + Upload → 7. Ticket Created → 8. Ticket Detail (Tracking) → 9. Resolution Summary → 10. Rating
