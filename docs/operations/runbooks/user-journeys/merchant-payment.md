# Journey 7: Merchant Payment (Shop QR)

## Goal

Customer at a small shop scans the merchant's static QR code, enters payment amount (or merchant enters it), confirms with PIN, and the merchant receives funds and notification.

## Actor

- Role: Customer (Tier 1+ verified user)
- Device: Mobile
- Language: Arabic (default)
- Tier: Tier 1 (sufficient for payments up to 50,000 SYP)
- Connectivity: Online (intermittent in some areas)

## Preconditions

- Customer has Beza wallet with sufficient balance (≥ 15,000 SYP)
- Merchant is registered as a Beza Merchant (بقالة أبو سامر)
- Merchant has a printed QR code sticker placed at the counter
- Shop is a small neighbourhood grocery store (بقالة) — no POS terminal, no computer
- Customer has data/Wi-Fi connection

## Success Flow

| Step | Actor    | Action                                                                                  | System                                                                                  | Event Emitted                | State Change                                           |
| ---- | -------- | --------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------- | ---------------------------- | ------------------------------------------------------ |
| 1    | Customer | Opens app, taps "دفع" (Pay) at a shop                                                   | Shows payment options: "مسح QR" and "إدخال رقم التاجر"                                  | —                            | —                                                      |
| 2    | Customer | Taps "مسح QR" (Scan QR)                                                                 | Opens camera with QR scanner overlay                                                    | —                            | —                                                      |
| 3    | Customer | Points camera at the QR sticker on the counter labeled "Beza Pay - بقالة أبو سامر"      | Decodes QR containing merchant ID "M-12345-دمشق"                                        | —                            | —                                                      |
| 4    | System   | —                                                                                       | Recognises merchant: "بقالة أبو سامر / دمشق، شارع بغداد / منذر السيد"                   | —                            | —                                                      |
| 5    | Customer | Sees merchant name on screen: "الدفع إلى: بقالة أبو سامر"                               | Prompts to enter amount                                                                 | —                            | —                                                      |
| 6    | Customer | Enters "15000" (15,000 SYP) for groceries                                               | Validates: balance ≥ 15,000 + fee, daily limit check                                    | —                            | —                                                      |
| 7    | System   | —                                                                                       | Shows breakdown: "المبلغ: 15,000 ل.س / الرسوم: 0 ل.س (QR مجاني) / الإجمالي: 15,000 ل.س" | —                            | —                                                      |
| 8    | Customer | Taps "تأكيد" (Confirm)                                                                  | Prompts PIN entry                                                                       | —                            | —                                                      |
| 9    | Customer | Enters PIN                                                                              | Validates PIN                                                                           | —                            | —                                                      |
| 10   | System   | —                                                                                       | Debits customer 15,000 SYP, credits merchant wallet 15,000 SYP                          | `MERCHANT_PAYMENT_COMPLETED` | Customer balance: reduced, Merchant balance: increased |
| 11   | Customer | Sees success screen: "تم الدفع! 15,000 ل.س إلى بقالة أبو سامر"                          | —                                                                                       | —                            | —                                                      |
| 12   | Merchant | Receives SMS: "وصلتك 15,000 ل.س من أحمد عبر Beza. الرصيد: 385,000 ل.س."                 | —                                                                                       | —                            | —                                                      |
| 13   | Merchant | Optionally hears voice notification from merchant app (if installed): "وصلت 15,000 ل.س" | —                                                                                       | —                            | —                                                      |
| 14   | Customer | Receives SMS: "تم الدفع 15,000 ل.س إلى بقالة أبو سامر. الرصيد: 35,000 ل.س."             | —                                                                                       | —                            | —                                                      |

## Alternative Flows

### A1: Merchant enters amount (merchant-presented mode)

Merchant has a smartphone with merchant app. Merchant enters amount 15,000 SYP in app. Customer sees incoming payment request on their phone: "يطلب بقالة أبو سامر 15,000 ل.س". Customer approves with PIN.

### A2: Manual merchant ID entry (no QR)

If QR is damaged/unreadable, customer enters merchant ID "M-12345" manually. System shows merchant name for confirmation.

### A3: Intermittent connectivity (offline mode)

Customer scans QR while offline. Transaction queued. When connectivity resumes, transaction processes. Merchant receives delayed notification.

### A4: Customer has insufficient balance

Show: "الرصيد غير كافٍ. الرصيد: 10,000 ل.س والمبلغ المطلوب: 15,000 ل.س." Customer can choose smaller amount or add funds.

## Failure Flows

### F1: QR code invalid/unreadable

Show: "رمز QR غير صالح. جرب الإدخال اليدوي لرقم التاجر." If manual entry also fails, contact support.

### F2: Merchant account frozen

If merchant account is blocked/frozen, payment rejected with: "لا يمكن إتمام الدفع. حساب التاجر غير نشط. يرجى استخدام وسيلة دفع أخرى."

### F3: Duplicate payment detected

If customer accidentally pays twice within 60 seconds, second payment blocked. "تم الدفع مسبقاً. يرجى التحقق من سجل المعاملات."

### F4: Network failure during confirmation

Transaction retried 3 times. If all fail, funds not debited. Show: "تعذر إتمام الدفع. لم يتم خصم أي مبلغ. حاول مرة أخرى."

## Notifications

- SMS (customer): "تم الدفع {amount} ل.س إلى {merchant}. الرصيد: {balance} ل.س."
- SMS (merchant): "وصلتك {amount} ل.س من {customer} عبر Beza. الرصيد: {balance} ل.س."
- Push (customer): "تم الدفع بنجاح في {merchant}"
- Push (merchant): "إشعار دفع: {amount} ل.س من {customer}"
- Voice (merchant app): "وصلت {amount} ل.س"

## Ledger Impact

| Account                          | Debit      | Credit     | Currency |
| -------------------------------- | ---------- | ---------- | -------- |
| Customer Wallet                  | 15,000 SYP | —          | SYP      |
| Merchant Wallet (بقالة أبو سامر) | —          | 15,000 SYP | SYP      |
| Beza Fee Income                  | —          | 0 SYP      | SYP      |

## State Changes

- Customer balance: reduced by 15,000 SYP
- Merchant balance: increased by 15,000 SYP
- Transaction: completed
- Merchant daily turnover: +15,000 SYP
- Customer daily payment limit consumed: +15,000 SYP

## UI Screens

1. Home → 2. Pay → 3. QR Scanner → 4. Merchant Confirmation → 5. Amount Entry → 6. Fee Breakdown → 7. PIN Entry → 8. Processing → 9. Success (with merchant name and amount) → 10. Optional Receipt Screen
