# Merchant User Journeys

## Journey 1: Merchant Registration (First-time)
```
Step 1: Merchant downloads "Beza Merchant" app from Google Play
Step 2: App opens in Arabic — "تسجيل تاجر جديد" (Register New Merchant)
Step 3: Enters phone number → receives SMS OTP
Step 4: Creates 6-digit PIN (merchant PIN for operations)
Step 5: Enters business info:
    - Store name: "الشمام" (الاسم التجاري)
    - Business type: "متجر بقالة" (اختر من القائمة)
    - Address: Description + optional map pin
    - Phone number for customers (optional)
Step 6: Uploads business license (or takes photo) — or skips if informal
Step 7: Takes 2 photos of shop (storefront + interior)
Step 8: Verification status: "قيد المراجعة" (Under Review) — usually 1-2 hours
Step 9: Notification: "تم تفعيل حسابك التجاري!" — merchant gets QR code
Step 10: Downloads QR code as PNG or requests laminated copy (courier)

Edge Cases:
  - Illiterate merchant: Son/relative fills form, merchant only sets PIN
  - No business license: Flagged as "informal" — lower transaction limits
  - Photos rejected: "الصور غير واضحة، يرجى إعادة التصوير"
  - Duplicate phone: Merchant already registered under another number
  - Slow verification: Manual review escalates if > 4 hours
```

## Journey 2: QR Payment at Store
```
Step 1: Customer finishes shopping at Al-Sham Supermarket
Step 2: Merchant shows QR code (laminated, at checkout counter)
Step 3: Customer opens Beza app → taps "Scan" (icon in bottom bar)
Step 4: Customer scans QR code → merchant name + logo appears
Step 5: Customer enters amount: 45,000 SYP (or sees fixed amount if dynamic QR)
Step 6: Customer confirms: "دفع 45,000 ل.س لمتجر الشمّام؟"
Step 7: Customer enters PIN → transaction processing
Step 8: Both customer and merchant see success screen
Step 9: Merchant app: voice says "تم استلام 45,000 ل.س" + vibration
Step 10: Customer gets SMS receipt: "تم الدفع 45,000 ل.س لمتجر الشمّام"

Edge Cases:
  - Customer has insufficient balance: "الرصيد غير كافٍ — الرجاء اختيار طريقة دفع أخرى"
  - Network failure: Transaction queued → processed when online
  - Wrong amount entered: Merchant sees amount → can cancel on their app
  - QR code damaged: Merchant logs in → downloads QR again
  - Duplicate scan: Idempotency prevents double charge
  - Customer app not open: SMS link to download Beza
```

## Journey 3: Payment Link via WhatsApp
```
Step 1: Merchant (Damascus Bazar) has a customer on WhatsApp ordering
Step 2: Customer: "كم سعر شنطة الظهر؟" (How much is the backpack?)
Step 3: Merchant: "45,000 ل.س" → opens Beza Merchant app → "إنشاء رابط دفع"
Step 4: Merchant enters: Amount (45,000 SYP), Description (شنطة ظهر جلدية), Optional expiry
Step 5: App generates link: https://pay.beza.com/pay/link_abc123
Step 6: Merchant taps "مشاركة" → WhatsApp opens with pre-filled message:
    "مرحباً، رابط الدفع لمنتج شنطة ظهر جلدية بقيمة 45,000 ل.س:
    https://pay.beza.com/pay/link_abc123
    شكراً لتسوقك مع دمشق بازار"
Step 7: Customer opens link → Beza payment page (mobile web or app)
Step 8: Customer sees: product description, amount, merchant name
Step 9: Customer pays with Beza balance (or enters card details — future)
Step 10: Merchant gets push notification: "تم الدفع — 45,000 ل.س — شنطة ظهر جلدية"
Step 11: Merchant ships the product

Edge Cases:
  - Link expired: Customer sees "انتهت صلاحية رابط الدفع" — contact merchant
  - Customer pays partial amount: Blocked — must pay exact amount
  - Duplicate payment: Idempotency prevents — second click shows "مدفوعة مسبقاً"
  - Customer doesn't have Beza: Link shows "حمّل تطبيق Beza للدفع" with download button
  - Payment link shared publicly: Stranger pays — merchant fulfills (first-come)
```

## Journey 4: Daily Settlement
```
Step 1: Time: 23:59 — SettlementService triggers batch process
Step 2: System calculates all of merchant's completed transactions today:
    - Al-Sham Supermarket: 12 transactions, Total: 850,000 SYP
    - MDR: 1.5% × 850,000 = 12,750 SYP
    - Net settlement: 837,250 SYP
Step 3: System debits merchant settlement clearing account
Step 4: System credits merchant Beza wallet: +837,250 SYP
Step 5: System credits Beza MDR income: +12,750 SYP
Step 6: Merchant gets notification: "تم تسوية اليوم — 837,250 ل.س"
Step 7: Merchant opens app → sees settlement report:
    - Date, gross sales, MDR rate, MDR amount, net amount
    - Breakdown by QR vs POS vs Link
Step 8: Merchant can withdraw from wallet to bank or cash-out at agent
Step 9: Settlement report available as PDF: "تقرير التسوية — 2026-06-01.pdf"

Edge Cases:
  - Zero transactions: No settlement — notification: "لا توجد معاملات اليوم"
  - Settlement failed: Retry 3x → if still failed → alert ops team
  - Disputed transaction: Amount held in suspense, excluded from settlement
  - Partial settlement: If merchant wallet is frozen (compliance), settlement held
  - Negative settlement: Not applicable — MDR never exceeds transaction amount
```
