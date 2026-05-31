# Government Collections User Journeys

## Journey 1: First-Time Tax Payment

### Step-by-Step
```
User opens Beza → selects "الخدمات الحكومية" (Government Services)

Step 1: Home Screen
  > User sees "الخدمات الحكومية" card with shield icon
  > Taps card → enters Government Hub

Step 2: Service Selection
  > User sees grid: "الضرائب" — "المركبات" — "جوازات السفر" — "الجامعات" — "المحاكم" — "البلدية" — "المرور"
  > Taps "الضرائب" (Taxes)
  > Sub-options: "ضريبة الدخل" (Income Tax) | "الضريبة العقارية" (Property Tax) | "الاستعلام العام" (General Query)

Step 3: Tax Query
  > User selects "الاستعلام عن الضريبة"
  > Field: "الرقم الضريبي" (Tax ID) — 10-digit Syrian Tax ID
  > User enters: 1234567890
  > Taps "استعلام"

Step 4: Loading / States
  > Loading: "جاري الاستعلام عن الضرائب المستحقة..."
  > System queries Ministry of Finance API
  > Success: Shows list of outstanding obligations

Step 5: Amount Display
  ┌────────────────────────────────┐
  │ ضريبة الدخل — 2025            │
  │ المبلغ المستحق: ٢٥٠,٠٠٠ ل.س   │
  │ غرامات التأخير: ١٢,٥٠٠ ل.س   │
  │ المجموع: ٢٦٢,٥٠٠ ل.س          │
  │ تاريخ الاستحقاق: ٣١/١٢/٢٠٢٥  │
  │ الحالة: متأخر عن السداد       │
  │                                │
  │ ┌─────────────────────────┐   │
  │ │     [تسديد الآن]        │   │
  │ └─────────────────────────┘   │
  └────────────────────────────────┘

Step 6: Payment Method
  > User taps "تسديد الآن"
  > Payment method: "المحفظة" (Beza Wallet) — balance: 500,000 SYP
  > Shows fee breakdown: 262,500 + 0.5% fee (1,312) = 263,812 SYP total
  > Taps "تأكيد الدفع"

Step 7: PIN Confirmation
  > PIN pad opens (mix of numeric and pattern)
  > User enters 4-digit PIN
  > Taps "تأكيد"

Step 8: Processing
  > "جاري تأكيد الدفع مع وزارة المالية..."
  > Internal: Beza debits wallet → settles to Ministry of Finance
  > Ministry confirms receipt → generates receipt reference

Step 9: Receipt
  ┌────────────────────────────────┐
  │ ✅ تم الدفع بنجاح              │
  │                                │
  │ 📋 وصل الدفع الحكومي           │
  │ المرجع: GOV-2025-08-15-7823   │
  │ المبلغ: ٢٦٣,٨١٢ ل.س           │
  │ التاريخ: ١٥/٠٨/٢٠٢٥          │
  │ الجهة: وزارة المالية           │
  │ [🔲 QR Code]                  │
  │                                │
  │ [مشاركة] [تحميل PDF] [طباعة] │
  └────────────────────────────────┘

Step 10: Confirmation
  > SMS sent: "تم دفع 263,812 ل.س ضريبة الدخل. المرجع: GOV-2025-08-15-7823"
  > Email sent if configured
  > Push notification with celebration animation
```

### Timeline
| Step | Time | Cumulative |
|------|------|------------|
| Open app → Select tax | 5 sec | 5 sec |
| Enter tax ID | 8 sec | 13 sec |
| Query result load | 3 sec | 16 sec |
| Review and confirm | 10 sec | 26 sec |
| PIN entry | 4 sec | 30 sec |
| Payment processing | 5 sec | 35 sec |
| Receipt generation | 2 sec | 37 sec |
| **Total** | | **~37 sec** |

## Journey 2: Passport Fee Payment (Diaspora)

```
User (Samer in Berlin) opens Beza → Government Services → جوازات السفر

Step 1: Select Service
  > Selects "تجديد جواز سفر" (Passport Renewal)
  > Or "إصدار جواز سفر جديد" (New Passport)

Step 2: Enter Application Number
  > Field: "رقم الطلب" (Application Number) — 15-digit ministry reference
  > User enters: PPR-2025-7890123
  > Taps "استعلام"

Step 3: Ministry Response
  > Shows: Application status "معتمدة" (Approved)
  > Fee: 75,000 SYP standard + 15,000 SYP urgent
  > User selects normal (75,000 SYP)

Step 4: Payment
  > Payer info: Name matches application
  > Currency: SYP (user can see EUR equivalent: ~30 EUR)
  > Fee: 75,000 + 0.5% Beza fee (375) = 75,375 SYP
  > Taps "تأكيد الدفع"
  > PIN

Step 5: Receipt
  > Official receipt with QR
  > Reference: PPR-2025-7890123-GOV
  > "تم تأكيد دفع رسوم جواز السفر مع وزارة الداخلية"

Step 6: Share
  > User shares PDF receipt to embassy email
  > Embassy verifies via QR on their portal
  > Passport printing initiated
```

## Journey 3: University Tuition

```
User (Layla) opens Beza → Government → الجامعات

Step 1: University Selection
  > Search: "دمشق" (Damascus University)
  > Or recent universities list

Step 2: Student ID Entry
  > Field: "الرقم الجامعي" (Student ID)
  > User enters: 2024123456
  > Taps "استعلام"

Step 3: Tuition Display
  ┌────────────────────────────────┐
  │ جامعة دمشق — كلية الهندسة     │
  │                                │
  │ الفصل: خريفي 2025-2026        │
  │ الرسوم الدراسية: ٢٠٠,٠٠٠ ل.س  │
  │ رسوم التسجيل: ٢٥,٠٠٠ ل.س     │
  │ رسوم الكلية: ١٥,٠٠٠ ل.س      │
  │ ─────────────────             │
  │ المجموع: ٢٤٠,٠٠٠ ل.س         │
  │ آخر موعد للدفع: ١٠/٠٩/٢٠٢٥  │
  │ الحالة: غير مسدد              │
  └────────────────────────────────┘

Step 4: Pay from Parent's Wallet
  > Layla's father (Ahmed) is on a family plan
  > Option: "ادفع من محفظة الأب" (Pay from Father's Wallet)
  > Father receives notification: "تأكيد دفع 240,000 ل.س رسوم جامعة؟"
  > Father approves with PIN

Step 5: Confirmation
  > University updates immediately in their system
  > Layla gets: "تم تأكيد تسجيلك في جامعة دمشق للفصل الخريفي 🎓"
```
