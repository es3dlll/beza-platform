# Journey 3: First P2P Transfer

## Goal

User sends money to a phone contact for the first time, entering amount in SYP, seeing fee breakdown, confirming with PIN, and receiving success confirmation.

## Actor

- Role: Tier 1 verified user
- Device: Mobile (Android/iOS)
- Language: Arabic (default), English optional
- Tier: Tier 1 (daily limit 50,000 SYP)
- Connectivity: Online

## Preconditions

- User has completed KYC Tier 1
- User has wallet balance ≥ 5,000 SYP
- User has recipient's mobile number in phone contacts (Syriatel or MTN)
- Recipient is not a Beza user yet (first-time receiver)

## Success Flow

| Step | Actor     | Action                                                                                               | System                                                                                             | Event Emitted        | State Change                                         |
| ---- | --------- | ---------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------- | -------------------- | ---------------------------------------------------- |
| 1    | User      | Opens app                                                                                            | Loads home screen showing balance 25,000 SYP                                                       | —                    | —                                                    |
| 2    | User      | Taps "تحويل" (Transfer) on home screen                                                               | Shows transfer options: "إلى جهة اتصال", "إلى رقم", "إلى محفظة"                                    | —                    | —                                                    |
| 3    | User      | Taps "إلى جهة اتصال" (To Contact)                                                                    | Opens phone contacts list with search bar                                                          | —                    | —                                                    |
| 4    | User      | Selects "خالد" from contacts (number: 0933-456-789)                                                  | System checks if number is registered on Beza: no                                                  | —                    | —                                                    |
| 5    | System    | —                                                                                                    | Shows message: "المستلم غير مسجل في Beza. سيصله إشعار SMS لاستلام المبلغ."                         | —                    | —                                                    |
| 6    | User      | Taps "متابعة" (Continue)                                                                             | Opens amount entry screen with numeric keypad                                                      | —                    | —                                                    |
| 7    | User      | Enters "5000" (5,000 SYP)                                                                            | Validates: amount ≤ balance, amount ≤ daily limit                                                  | —                    | —                                                    |
| 8    | System    | —                                                                                                    | Shows fee breakdown: "التحويل: 5,000 ل.س / الرسوم: 150 ل.س / الإجمالي: 5,150 ل.س"                  | —                    | —                                                    |
| 9    | User      | Reviews fees, taps "التالي" (Next)                                                                   | Shows confirmation screen: "إلى خالد / 0933-456-789 / المبلغ: 5,000 ل.س / الرسوم: 150 ل.س"         | —                    | —                                                    |
| 10   | User      | Taps "تأكيد" (Confirm)                                                                               | Prompts for 6-digit PIN entry                                                                      | —                    | —                                                    |
| 11   | User      | Enters PIN **\*\***                                                                                  | Validates PIN hash match                                                                           | —                    | —                                                    |
| 12   | System    | —                                                                                                    | Processes transfer: debits sender 5,150 SYP, credits receiver float 5,000 SYP, records fee 150 SYP | `TRANSFER_COMPLETED` | Balance: 25,000 → 19,850 SYP, Transaction: completed |
| 13   | System    | —                                                                                                    | Shows success screen with confetti animation and large checkmark                                   | —                    | —                                                    |
| 14   | User      | Sees success screen: "تم التحويل بنجاح! 5,000 ل.س إلى خالد"                                          | —                                                                                                  | —                    | —                                                    |
| 15   | System    | —                                                                                                    | Sends SMS to sender and receiver                                                                   | —                    | —                                                    |
| 16   | User      | Receives SMS: "تم تحويل 5,000 ل.س إلى خالد 0933-456-789. الرصيد: 19,850 ل.س. Beza"                   | —                                                                                                  | —                    | —                                                    |
| 17   | Recipient | Receives SMS: "وصلتك 5,000 ل.س من أحمد via Beza. حمّل التطبيق: bez.app/dwnld لاستلامها. رمز: 987654" | —                                                                                                  | —                    | —                                                    |

## Alternative Flows

### A1: Recipient already on Beza

Skip SMS invite. Recipient gets push notification: "وصلتك 5,000 ل.س من أحمد. الرصيد: 30,000 ل.س."

### A2: Amount exceeds daily limit

If amount > 50,000 SYP (Tier 1), show: "تجاوزت الحد اليومي (50,000 ل.س). يمكنك إدخال مبلغ أقل أو ترقية حسابك."

### A3: Fee not accepted

If user cancels after seeing fee, no state change. Fee is non-refundable once confirmed.

## Failure Flows

### F1: Insufficient balance

Show error: "الرصيد غير كافٍ. الرصيد المتاح: 3,000 ل.س والمبلغ المطلوب: 5,150 ل.س (شامل الرسوم)."

### F2: Network timeout during transfer

Transaction queued. If 30s timeout: "تعذر إتمام التحويل. تم حفظ العملية. تحقق من الحالة في سجل المعاملات."

### F3: PIN blocked after 5 wrong attempts

Show: "تم حظر PIN بسبب 5 محاولات خاطئة. يمكنك إعادة تعيين PIN من الإعدادات أو الاتصال بخدمة العملاء على 1234."

## Notifications

- SMS (sender): "تم تحويل {amount} ل.س إلى {name} {number}. الرصيد: {balance} ل.س. Beza"
- SMS (non-Beza receiver): "وصلتك {amount} ل.س من {sender} via Beza. حمّل التطبيق: bez.app/dwnld لاستلامها. رمز الاستلام: {code}"
- Push (sender success): "تم التحويل بنجاح إلى {name}"
- Push (receiver on Beza): "وصلتك {amount} ل.س من {sender}. الرصيد الجديد: {balance} ل.س"
- SMS (fee): "رسوم تحويل {amount} ل.س: {fee} ل.س (3%). شكراً لاستخدام Beza."

## Ledger Impact

| Account                 | Debit     | Credit    | Currency |
| ----------------------- | --------- | --------- | -------- |
| Sender Wallet           | 5,150 SYP | —         | SYP      |
| Receiver Wallet (float) | —         | 5,000 SYP | SYP      |
| Beza Fee Income         | —         | 150 SYP   | SYP      |
| Settlement Account      | 5,000 SYP | —         | SYP      |

## State Changes

- Sender balance: 25,000 → 19,850 SYP
- Receiver balance: 0 → 5,000 SYP (pending claim if not on Beza)
- Transaction status: pending → completed
- Daily transfer counter: 0 → 5,000 SYP

## UI Screens

1. Home → 2. Transfer Type → 3. Contact List → 4. Amount Entry → 5. Fee Breakdown → 6. Confirmation → 7. PIN Entry → 8. Processing → 9. Success (with confetti)
