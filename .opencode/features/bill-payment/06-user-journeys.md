# Bill Payment User Journeys

## Journey 1: Pay Electricity Bill (PEED — Real-time API)

```
Step 1: User opens app → taps "Pay Bills"
Step 2: Selects "كهرباء" (Electricity) from category grid
Step 3: Choose biller: "الشركة العامة للكهرباء — PEED"
Step 4: Enters customer ID (24-digit smart meter number: 1234-5678-9012-3456-7890)
Step 5: App validates digit format (4 groups of 6 digits)
Step 6: Taps "استعلام" (Fetch Bill)
Step 7: Loading state with spinner + "جارٍ الاستعلام عن الفاتورة..."
Step 8: Bill details displayed:
    - Customer name: أحمد خالد (Ahmad Khaled)
    - Address: دمشق, المزة, شارع النصر
    - Invoice #: 2026-06-789012
    - Billing period: May 2026
    - Consumption: 850 kWh
    - Amount: 42,500 SYP
    - Late fees: 2,125 SYP (5% — 5 days overdue)
    - Total due: 44,625 SYP
    - Due date: 2026-06-15
Step 9: User confirms with PIN
Step 10: Payment processing animation
Step 11: Success screen:
    - "تم الدفع بنجاح"
    - Reference: BILL-PEED-202606-XXXXX
    - Biller Reference: PE1234567890
    - Amount: 44,625 SYP
    - Timestamp: 2026-06-10T09:30:00Z
    - "تم إرسال الإيصال عبر SMS + البريد الإلكتروني"
    - Options: [مشاركة الإيصال] [دفع فاتورة أخرى] [العودة للرئيسية]

Edge Cases:
  - Invalid customer ID format: show "رقم المشترك غير صحيح — يجب أن يكون 24 رقم"
  - Customer ID not found: "لم يتم العثور على مشترك بهذا الرقم"
  - Bill already paid: "هذه الفاتورة مدفوعة مسبقاً — المرجع: PE1234567890"
  - PEED API down: queue payment → retry 3x → notify if failed
  - Insufficient wallet balance: "الرصيد غير كافٍ — المبلغ المطلوب: 44,625 ل.س — رصيدك: 30,000 ل.س"
  - Amount mismatch: biller returns different amount than fetched → block with "عدم تطابق — اتصل بالدعم الفني"
```

## Journey 2: Pay Water Bill (Damascus Water Authority)

```
Step 1: User taps "Pay Bills" → "مياه" → "مؤسسة مياه الشرب والصرف الصحي بدمشق"
Step 2: Enters customer ID: 10-digit subscription number (e.g., 1234567890)
Step 3: Fetches bill → shows:
    - Customer: محمد علي
    - Address: دمشق, العدوي
    - Meter reading: Current 4520, Previous 4380 = 140 m³
    - Water consumption fee: 6,500 SYP
    - Wastewater fee: 1,200 SYP
    - Fixed subscription: 800 SYP
    - Total: 8,500 SYP
    - Due date: 2026-06-20
Step 4: User confirms → pay → success with reference

Edge Cases:
  - No bill for this period: "لا توجد فاتورة جديدة للاشتراك رقم XXXXX"
  - Estimated reading (no access): displayed with "(تقديري)" tag
  - Delinquent account: shows outstanding balance + reconnect fee
```

## Journey 3: Pay Syriatel Mobile Postpaid

```
Step 1: "Pay Bills" → "اتصالات" → "سيريتل"
Step 2: Enters mobile number: 0933-123456 (10 digits)
Step 3: Fetches bill → shows:
    - Number: 0933-123456 (Ahmad Khaled)
    - Plan: Syriatel Liberty Postpaid 25K
    - Monthly subscription: 25,000 SYP
    - Extra usage (data overage): 3,200 SYP
    - International calls: 1,800 SYP
    - VAT (10%): 3,000 SYP
    - Total: 33,000 SYP
    - Due date: 2026-06-12
    - Service suspension date: 2026-06-20
Step 4: Pay → immediate credit to account → confirmation from Syriatel
Step 5: Receipt includes Syriatel confirmation reference

Edge Cases:
  - Number belongs to different network: "الرقم 0933-XXXXXX غير تابع لسيريتل — هل تقصد MTN?"
  - Prepaid number: "هذا رقم بطاقة مسبقة الدفع — استخدم خدمة تعبئة رصيد"
  - Suspended number: "الرقم موقوف — الرسوم المستحقة: 38,000 SYP (شامل رسوم إعادة التوصيل)"
```

## Journey 4: Set Bill Reminder + Auto-pay

```
Step 1: User selects a biller from recent bills or biller list
Step 2: Enters customer ID → fetches bill
Step 3: Taps "تذكير" (Set Reminder)
Step 4: Options:
    - Before due date: 1 day / 3 days / 7 days
    - Notification method: Push / SMS / Both
    - Reminder frequency: One-time / Monthly recurring
Step 5: Also offers: "تفعيل الدفع التلقائي" (Enable Auto-pay)
    - Confirm: "سيتم دفع الفاتورة تلقائياً في تاريخ الاستحقاق من محفظتك"
    - Requires: sufficient balance on due date
    - Cancel: anytime from scheduled bills list
Step 6: Confirmation: "تم تعيين التذكير لفاتورة الكهرباء — سيتم إشعارك قبل الموعد بـ 3 أيام"

Edge Cases:
  - User sets reminder but biller not in system → check if biller supports reminders
  - Auto-pay fails due to insufficient balance → retry 3x over 24h → notify
  - Multiple reminders on same day → consolidate into single notification
```

## Journey 5: CSV Batch Bill — Government Fees

```
Step 1: User enters Civil Affairs customer ID (national number: 16 digits)
Step 2: System queries internal database (populated from CSV batch)
Step 3: Shows pending fees:
    - Civil status record extract: 5,000 SYP
    - Family registry certificate: 3,000 SYP
    - Total: 8,000 SYP
    - Reference: CSV-BATCH-2026-06-01-123
Step 4: Pay → system marks as paid in internal DB
Step 5: User receives receipt + instruction to collect documents from office

Edge Cases:
  - CSV not yet processed for current month: "بيانات غير متوفرة حالياً — يرجى المحاولة بعد 48 ساعة"
  - Fee already paid: "تم دفع هذه الرسوم مسبقاً — التاريخ: 2026-05-28"
  - CSV record not found: "لم يتم العثور على معاملة بهذا الرقم الوطني"
```
