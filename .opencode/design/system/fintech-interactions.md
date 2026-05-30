# Fintech Interaction Patterns — Beza (بزة)

## Money Transfer Flow

### Step 1: Select Contact
- **Search by**: Phone number, name from contacts, or recent recipients
- **Recent recipients**: Last 10 shown with avatar, name, phone, last transfer date
- **Manual entry**: Full Syrian phone number (09XX XXX XXX) with country code +963
- **Validation**: Number must be a valid Syrian mobile (093-099 prefixes)
- **RTL note**: Phone number digits are Latin (0-9), not Arabic-Indic

### Step 2: Enter Amount
- Numeric keypad with SYP symbol (ل.س)
- Quick-amount buttons: 10,000 | 25,000 | 50,000 | 100,000 | 500,000
- Amount in words displayed below: "خمسة وعشرون ألف ليرة سورية فقط"
- Current balance shown: "الرصيد: ١٬٢٠٠٬٠٠٠ ل.س"
- Daily limit remaining: "الحد المتبقي: ٥٠٠٬٠٠٠ ل.س"
- Error if amount > balance or > daily limit

### Step 3: Review
- **From**: Beza Wallet (محفظة بزة)
- **To**: Recipient name + phone
- **Amount**: 25,000 ل.س
- **Fee**: 500 ل.س (مجاني first transfer of day)
- **Total**: 25,500 ل.س
- **Note**: Optional, 50 chars max, Arabic or English
- **Edit button**: Pencil icon on each row

### Step 4: Authenticate
- PIN entry (6 digits, shuffled keypad)
- OR biometric (fingerprint/face) for amounts ≤ 500,000 SYP
- Amounts > 500,000 SYP always require PIN
- "Use fingerprint instead" toggle below PIN pad

### Step 5: Confirmation
- Success animation: green check + particle burst
- Auto-dismiss after 2 seconds
- "View Receipt" button visible during animation

### Step 6: Receipt
- Digital receipt screen with:
  - Beza logo + "تمت المعاملة بنجاح" (Transaction Successful)
  - Reference number: BZ-20260529-XXXXXX
  - Date/time: 29 مايو 2026, 10:30 ص
  - From/To details
  - Amount + fee
  - QR code containing all receipt data (for merchant verification)
- **Share**: Share as image (PNG) via system share sheet
- **Copy reference**: One-tap copy reference number

---

## Agent Cash-In Flow (الصراف)

> Correct flow for Syrian context (user gives cash → agent confirms → wallet credited):

### Step 1: Locate Agent
- Agent list shows nearest agents sorted by distance
- Each agent card: name, distance, rating, operating hours, "is open now" indicator
- Map view with agent markers (green = open, gray = closed)

### Step 2: Show Code to Agent
- User taps "Cash-In at Agent" on app
- App generates a one-time QR code or 6-digit code (valid 5 minutes)
- User shows phone screen to agent
- **Agent scans QR** with their POS app OR **enters the 6-digit code** manually

### Step 3: Agent Enters Amount
- Agent terminal shows: user phone number, user name (masked: محمد أ.)
- Agent enters cash amount received
- Agent terminal shows fee (if any) and total to credit
- Agent confirms on their device

### Step 4: User Confirms
- User phone shows: amount, fee, total credit amount
- User taps "Confirm" (تأكيد)
- PIN/biometric authentication required

### Step 5: Success
- User phone: "تمت الإيداع بنجاح. الرصيد الجديد: ١٬٢٥٠٬٠٠٠ ل.س"
- Agent terminal: "تم الإيداع. المبلغ: ١٠٠٬٠٠٠ ل.س"
- Receipt generated on both devices
- SMS confirmation sent to user

### Error Scenarios
- **Agent cancels**: User sees "تم إلغاء العملية" — no effect on balance
- **Timeout (5 min)**: Code expires, user must generate new code
- **Wrong amount dispute**: Agent and user must go to branch with receipt

---

## Bill Payment Flow

### Step 1: Search Biller
- Search bar with biller name (Arabic or English)
- Categorized grid: Electricity (كهرباء), Water (ماء), Telecom (اتصالات), Internet (إنترنت), Gas (غاز), Education (تعليم), Government (حكومي)
- Recent billers at top
- Supported billers:
  - **Electricity**: Damascus, Aleppo, Homs, Hama, Latakia, Tartus, Deir ez-Zor, Hasakeh, Raqqa, Idlib, Daraa, Quneitra, Suweida
  - **Telecom**: Syriatel, MTN
  - **Internet**: SCS, ANET, Aya, Alfa, CityNet, Smart Network, Al-Mada, WATAN, Al-Aseel, TerraNet, SyriaNet, Sawa, Al-Jazeera, Eagle

### Step 2: Enter Account Info
- Electricity: meter number (12-15 digits)
- Water: subscription number
- Telecom: phone number (09XX XXX XXX)
- Internet: account number or phone number
- Validation: correct digit count per biller type

### Step 3: Fetch Bill
- Loading skeleton while fetching (1-3s typical)
- Bill details shown:
  - **Biller**: "كهرباء دمشق"
  - **Account**: **** 4521
  - **Period**: "مايو 2026"
  - **Amount**: 45,250 ل.س
  - **Late fees**: 2,500 ل.س (if applicable)
  - **Due date**: 15 يونيو 2026
  - **Status**: "قابل للدفع" (Payable) or "مدفوع" (Already Paid)

### Step 4: Pay
- Total: bill amount + late fees
- Payment method: Beza Wallet (محفظة بزة) — only option
- PIN/biometric confirmation
- Success animation + receipt

### Step 5: Receipt
- Biller name + account number
- Amount paid
- Payment reference
- Date/time
- Share option

---

## FX Conversion Flow (تحويل عملة)

### Step 1: Select Currency Pair
- **From**: SYP (always) — user holds SYP
- **To**: One of supported currencies
  - USD (دولار أمريكي)
  - EUR (يورو)
  - TRY (ليرة تركية)
  - AED (درهم إماراتي)
  - SAR (ريال سعودي)
- Rate is displayed: "١ USD = ١٣٬٢٥٠ SYP"

### Step 2: Enter Amount
- User enters amount in SOURCE currency (SYP)
- Target amount calculated in real-time
- "You will receive: ٣٧٧٫٣٦ USD"
- Fee shown: 0.5% of amount (min 5,000 SYP, max 50,000 SYP)

### Step 3: Rate Lock Timer
- Rate is locked for 30 seconds
- Countdown timer displayed: "ينتهي السعر خلال ٢٥ ثانية"
- If timer expires: rate refreshes, user must re-confirm
- Flash warning at 10 seconds (pulse animation)

### Step 4: Confirm
- Amount in SYP, amount in foreign currency
- Exchange rate
- Fee
- Total SYP debited
- PIN/biometric required for all amounts
- Confirm button disabled if rate expired

### Step 5: Success
- "تم تحويل ٥٬٠٠٠٬٠٠٠ ل.س ← ٣٧٧٫٣٦ USD"
- Funds held in Beza multi-currency wallet
- Receipt with rate, time, reference
- Option to initiate international transfer (via SWIFT/Western Union partner)

---

## USSD Fallback (*123#)

When smartphone app is unavailable (no data, no smartphone, network congestion):

| Code | Function | Response Example |
|------|----------|-----------------|
| `*123#` | Main menu | "1. الرصيد 2. كشف حساب 3. وكلاء 4. فواتير 5. تغيير الرمز" |
| `*123*1#` | Balance | "رصيدك: ١٬٢٠٠٬٠٠٠ ل.س. آخر تحديث: 29 مايو 2026" |
| `*123*2#` | Mini-statement | Last 5 transactions with amounts and dates |
| `*123*3#` | Agent locator | "أقرب وكيل: متجر الرحمة, دمشق, 500م. الرقم: 0934567890" |
| `*123*4#` | Bill inquiry | "أدخل رقم العداد" → shows bill amount |
| `*123*5#` | PIN change | "أدخل الرمز الحالي" → "أدخل الرمز الجديد" → "تم التغيير" |

USSD menus are in Arabic only. Response time: 2-10s depending on network tier.
