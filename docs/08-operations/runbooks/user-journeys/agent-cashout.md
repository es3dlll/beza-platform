# Journey 5: Agent Cash-Out (Withdraw Cash)

## Goal

User withdraws cash from an agent location. Finds nearest agent on map, visits, enters amount, agent verifies via QR/user phone, gives cash, user confirms receipt.

## Actor

- Role: Tier 2 verified user (rural area)
- Device: Mobile
- Language: Arabic (default)
- Tier: Tier 2 (max cash-out 500,000 SYP/day)
- Connectivity: Online (with offline fallback)

## Preconditions

- User has sufficient wallet balance (≥ 10,000 SYP)
- User is in a rural area (e.g., Idlib countryside, Deir ez-Zor, or rural Homs)
- GPS enabled on phone
- At least one Beza agent within 20 km range
- Agent has sufficient cash float

## Success Flow

| Step | Actor  | Action                                                                                                                 | System                                                                                                                | Event Emitted        | State Change                   |
| ---- | ------ | ---------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------- | -------------------- | ------------------------------ |
| 1    | User   | Opens app, taps "سحب نقدي" (Cash Out) from home screen                                                                 | Shows map view centered on user's GPS location                                                                        | —                    | —                              |
| 2    | System | —                                                                                                                      | Loads agent markers within 20 km radius. Top result: "صيدلية الرحمة - 1.2 كم"                                         | —                    | —                              |
| 3    | User   | Views agent list sorted by distance: 1. صيدلية الرحمة (1.2 كم) 2. بقالة أبو سامر (3.5 كم) 3. محطة وقود المصري (7.8 كم) | Shows each agent's: name, distance, open/closed status, cash float indicator                                          | —                    | —                              |
| 4    | User   | Selects "صيدلية الرحمة"                                                                                                | Shows agent details: hours 8:00-22:00, "الرصيد النقدي: متوفر", verification methods: QR (recommended) or phone number | —                    | —                              |
| 5    | User   | Taps "طريق" (Directions)                                                                                               | Opens external maps with walking/driving route                                                                        | —                    | —                              |
| 6    | User   | Arrives at pharmacy, greets agent                                                                                      | —                                                                                                                     | —                    | —                              |
| 7    | Agent  | Opens agent app, selects "سحب نقدي"                                                                                    | System shows prompt to scan user QR or enter phone                                                                    | —                    | —                              |
| 8    | User   | Opens app, shows QR code from "السحب النقدي" section                                                                   | Displays QR code valid for 60 seconds                                                                                 | —                    | —                              |
| 9    | Agent  | Scans user's QR code using agent app camera                                                                            | Identifies user: "أحمد الخالد - Tier 2 - الرصيد: 575,000 ل.س"                                                         | —                    | —                              |
| 10   | Agent  | Enters withdrawal amount "50,000 SYP" in agent app                                                                     | Validates: user balance ≥ 50,000 + fee, agent float ≥ 50,000                                                          | —                    | —                              |
| 11   | User   | Sees request on phone: "سحب 50,000 ل.س من وكيل صيدلية الرحمة / الرسوم: 1,500 ل.س (3%) / الإجمالي: 51,500 ل.س"          | —                                                                                                                     | —                    | —                              |
| 12   | User   | Taps "تأكيد" (Confirm), enters PIN                                                                                     | Validates PIN                                                                                                         | `CASHOUT_INITIATED`  | Transaction: pending           |
| 13   | System | —                                                                                                                      | Debits user wallet: 51,500 SYP. Holds 50,000 SYP in agent settlement.                                                 | `CASHOUT_AUTHORIZED` | Balance: 575,000 → 523,500 SYP |
| 14   | Agent  | Sees confirmation on agent app: "تم التفويض. سلم 50,000 ل.س للعميل"                                                    | —                                                                                                                     | —                    | —                              |
| 15   | Agent  | Counts cash 50,000 SYP, hands to user                                                                                  | —                                                                                                                     | —                    | —                              |
| 16   | Agent  | Taps "تم التسليم" (Delivered) in agent app                                                                             | Confirms cash handed over                                                                                             | `CASHOUT_COMPLETED`  | Cashout: completed             |
| 17   | User   | Receives SMS: "تم سحب 50,000 ل.س من صيدلية الرحمة. الرصيد: 523,500 ل.س. Beza"                                          | —                                                                                                                     | —                    | —                              |
| 18   | User   | Optionally rates agent: stars 1-5                                                                                      | Stores rating                                                                                                         | —                    | —                              |

## Alternative Flows

### A1: Agent scan via phone number (no QR)

If user phone camera broken, agent enters user's phone number manually. Agent must verify user identity via national ID card.

### A2: Agent has insufficient cash float

Agent app shows reduced maximum. Agent can tap "طلب رصيد" to request float transfer from nearby agent.

### A3: Rural agent offline fallback

If agent has no internet, agent uses USSD code *123*51*{phone}*{amount}#. User receives USSD confirmation back.

### A4: Agent verification via national ID

If user phone is dead/no battery, agent can look up user by national ID number (الرقم الوطني). System shows user's photo for agent to verify.

## Failure Flows

### F1: Agent QR scanner failure

Retry scanning. After 3 failures, fallback to manual phone entry.

### F2: User PIN wrong

3 attempts. After 3rd, PIN blocked. User redirected to PIN reset flow.

### F3: Agent denies cashout after authorization

If agent taps "رفض" (Reject) after authorization, funds are reversed to user within 30 seconds. Reason logged: "رفض الوكيل التسليم".

### F4: User leaves without confirming receipt

If user does not tap "تم الاستلام" within 5 minutes, transaction auto-reverses. Agent must tap "إلغاء" before timeout.

## Notifications

- SMS (success): "تم سحب {amount} ل.س من {agent_name}. الرصيد: {balance} ل.س."
- SMS (reversal): "تم إلغاء عملية السحب من {agent_name}. تم إرجاع {amount} ل.س. الرصيد: {balance} ل.س."
- Push (authorized): "طلب السحب قيد التنفيذ لدى {agent_name}"
- Push (completed): "تم السحب بنجاح! {amount} ل.س نقداً"

## Ledger Impact

| Account                     | Debit      | Credit     | Currency |
| --------------------------- | ---------- | ---------- | -------- |
| User Wallet                 | 51,500 SYP | —          | SYP      |
| Agent Float (صيدلية الرحمة) | —          | 50,000 SYP | SYP      |
| Beza Fee Income             | —          | 1,500 SYP  | SYP      |

## State Changes

- User balance: 575,000 → 523,500 SYP
- Agent cash float: decreased by 50,000 SYP
- Transaction: pending → authorized → completed
- Agent cash-out counter: incremented

## UI Screens

1. Home → 2. Cash-Out Map → 3. Agent List → 4. Agent Detail → 5. Directions → 6. QR Code Display → 7. Amount Confirmation → 8. PIN Entry → 9. Processing → 10. Success → 11. Agent Rating
