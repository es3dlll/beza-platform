# Journey 1: First-Time User Onboarding

## Goal

Download the Beza app for the first time, register with a Syrian phone number, set a PIN, grant permissions, find an agent, deposit cash, and see the first wallet balance.

## Actor

- Role: New unregistered user (low digital literacy)
- Device: Mobile (Android/iOS)
- Language: Arabic (default), English optional
- Tier: Tier 0 → Tier 1 after cash-in
- Connectivity: Online (Wi-Fi or mobile data)

## Preconditions

- User has a Syriatel or MTN SIM card with SMS capability
- User has national ID (رقم وطني)
- User has cash (5000–50000 SYP) for first deposit
- Smartphone with minimum Android 6.0 or iOS 12.0
- App not yet installed OR freshly installed but not registered

## Success Flow

| Step | Actor  | Action                                                                   | System                                                                    | Event Emitted       | State Change                       |
| ---- | ------ | ------------------------------------------------------------------------ | ------------------------------------------------------------------------- | ------------------- | ---------------------------------- |
| 1    | User   | Opens Play Store / App Store and searches "Beza"                         | Shows app listing                                                         | —                   | —                                  |
| 2    | User   | Taps "تثبيت" (Install)                                                   | Downloads and installs app                                                | —                   | —                                  |
| 3    | User   | Taps "فتح" (Open)                                                        | Shows splash screen with Beza logo                                        | —                   | —                                  |
| 4    | User   | Sees welcome screen with "إنشاء حساب" button                             | Prompts to enter mobile number                                            | —                   | —                                  |
| 5    | User   | Selects Syria flag (+963) and enters mobile number                       | Validates number format (09XX-XXX-XXX)                                    | —                   | —                                  |
| 6    | User   | Taps "إرسال رمز التحقق"                                                  | Sends OTP via SMS through Syriatel/MTN gateway                            | `OTP_SENT`          | OTP: pending                       |
| 7    | User   | Receives SMS: "رمز تحقق Beza: 123456. صالح لمدة 5 دقائق"                 | —                                                                         | —                   | —                                  |
| 8    | User   | Enters 6-digit OTP                                                       | Verifies OTP matches stored code                                          | `OTP_VERIFIED`      | OTP: verified                      |
| 9    | System | —                                                                        | Prompts to create 6-digit PIN                                             | —                   | —                                  |
| 10   | User   | Enters PIN 123456                                                        | Stores hashed PIN                                                         | `PIN_SET`           | PIN: created                       |
| 11   | User   | Re-enters PIN 123456                                                     | Confirms match                                                            | —                   | —                                  |
| 12   | System | —                                                                        | Requests permissions: SMS (read OTP), Contacts, Location, Camera, Storage | —                   | —                                  |
| 13   | User   | Grants all permissions                                                   | Saves permissions in OS settings                                          | —                   | Permissions: granted               |
| 14   | System | —                                                                        | Displays empty home screen with 0 SYP balance and "إيداع أول مرة" prompt  | —                   | Wallet: created (balance = 0)      |
| 15   | System | —                                                                        | Shows nearby agents list (top 3 closest) based on GPS                     | —                   | —                                  |
| 16   | User   | Taps "العثور على وكيل" (Find Agent)                                      | Opens agent map view with markers                                         | —                   | —                                  |
| 17   | User   | Selects agent "مكتب الحلبي للصرافة" from list                            | Shows agent details: name, distance, working hours                        | —                   | —                                  |
| 18   | User   | Taps "عرض الاتجاهات" (Show Directions)                                   | Opens Google Maps with walking/driving route                              | —                   | —                                  |
| 19   | User   | Visits agent, hands over 5000 SYP cash                                   | —                                                                         | —                   | —                                  |
| 20   | Agent  | Enters user's phone number in agent app, selects deposit amount 5000 SYP | —                                                                         | —                   | —                                  |
| 21   | System | —                                                                        | Sends push notification: "إيداع 5,000 ل.س قيد التنفيذ"                    | `CASH_IN_INITIATED` | Transaction: pending               |
| 22   | User   | Opens app, sees confirmation prompt                                      | Shows "تأكيد الإيداع 5,000 ل.س لدى الوكيل الحلبي؟"                        | —                   | —                                  |
| 23   | User   | Taps "تأكيد" (Confirm)                                                   | Credits wallet with 5000 SYP                                              | `CASH_IN_COMPLETED` | Balance: 0 → 5000 SYP, Tier: 0 → 1 |
| 24   | System | —                                                                        | Shows success screen with new balance 5000 SYP and confetti animation     | —                   | —                                  |
| 25   | User   | Receives SMS: "تم إيداع 5,000 ل.س في محفظة Beza. رصيدك: 5,000 ل.س"       | —                                                                         | —                   | —                                  |

## Alternative Flows

### A1: OTP not received

User taps "إعادة إرسال" (Resend). System resends OTP after 60s cooldown. Max 5 attempts per hour, then 24-hour lockout.

### A2: Number already registered

System shows "هذا الرقم مسجل مسبقاً. هل تريد تسجيل الدخول؟" and redirects to login.

### A3: Agent not found in area

App shows message "لا يوجد وكلاء قريبون. جرّب البحث لاحقاً" and suggests USSD cash-in code *123*50#.

### A4: Low phone storage

System checks 100MB free space before install. Shows warning "المساحة غير كافية. حرّر مساحة على جهازك."

## Failure Flows

### F1: Network timeout during OTP

Retry 3 times. After failure, show "تعذّر الاتصال. تحقق من اتصالك بالإنترنت وحاول مرة أخرى."

### F2: SMS gateway down (Syriatel/MTN outage)

Fallback: voice call OTP ("رمز تحقق Beza الخاص بك هو: 1 2 3 4 5 6").

### F3: SIM swap detected

If SIM changed within last 24 hours, block registration. Show "يرجى الاتصال بخدمة العملاء على 1234."

## Notifications

- SMS (OTP): "رمز تحقق Beza: {code}. صالح لمدة 5 دقائق. لا تشاركه مع أحد."
- SMS (welcome): "أهلاً بك في Beza! رصيدك: 5,000 ل.س. يمكنك التحويل والدفع الآن."
- Push: "نوّن أول إيداع لك! اذهب إلى أقرب وكيل."

## Ledger Impact

| Account                | Debit     | Credit    | Currency |
| ---------------------- | --------- | --------- | -------- |
| User Wallet (Customer) | —         | 5,000 SYP | SYP      |
| Agent Float (الحلبي)   | 5,000 SYP | —         | SYP      |
| Cash-in Fee Income     | —         | 0 SYP     | SYP      |

## State Changes

- User: unregistered → tier 1 active
- Wallet: non-existent → created (balance = 5000 SYP)
- Transaction: pending → completed (cash-in)
- Permissions: not granted → all granted
- PIN: not set → set

## UI Screens

1. Splash → 2. Welcome ("إنشاء حساب") → 3. Phone Entry → 4. OTP Entry → 5. PIN Setup (enter + confirm) → 6. Permissions → 7. Empty Home (0 SYP) → 8. Agent Map → 9. Agent Detail → 10. Cash-in Confirmation → 11. Success (balance 5000 SYP)
