# Agent Network UX/UI Specification

## Screen 1: Agent POS Home / Dashboard

### Layout
```
┌────────────────────────────────┐
│ 📶  وكيل Beza    🔔  ⚙️       │ Status bar
├────────────────────────────────┤
│ ┌──────────────────────────┐   │ Float Card (32pt padding)
│ │ رصيد الصندوق              │   │
│ │    1,250,000             │   │ Amount (huge, 48px font)
│ │    ل.س                    │   │ Currency label
│ │                          │   │
│ │ [تعبئة] [تحويل من وكيل]  │   │ Quick float actions
│ └──────────────────────────┘   │
├────────────────────────────────┤
│ معاملات اليوم                  │ Section header
│ الإيداع: 1,500,000    سحب: 850,000
│ العمولة المقدرة: 12,500 ل.س    │ Ticker
├────────────────────────────────┤
│ ┌────────────────────────┐    │
│ │        🟢               │    │ Big green button
│ │     إيداع نقدي          │    │
│ │     (Cash-in)          │    │
│ │  حجم 80 × 64px         │    │
│ └────────────────────────┘    │
│ ┌────────────────────────┐    │
│ │        🔴               │    │ Big red button
│ │      سحب نقدي           │    │
│ │     (Cash-out)         │    │
│ └────────────────────────┘    │
├────────────────────────────────┤
│ آخر المعاملات                  │
│ │ 🟢 إيداع 100,000 09:30    │   │
│ │ 🔴 سحب 50,000  09:15     │   │
│ │ 🟢 إيداع 25,000  08:45   │   │
│ └── عرض الكل ──────────────┘   │
├────────────────────────────────┤
│ [الإيداع] [السحب] [الصندوق] [المعاملات] │ Bottom tabs
└────────────────────────────────┘
```

### States
| State | Behavior |
|-------|----------|
| Loading | Skeleton: float card shimmer + 2 action button placeholders + 3 transaction row skeletons |
| Empty (new agent) | "مرحباً بك في Beza!" banner + "قم بأول عملية إيداع لبدء العمل" + prominent green CTA |
| Error | "حدث خطأ في تحميل البيانات — تحقق من اتصالك" + "إعادة المحاولة" button |
| Offline | Banner: "🔵 أنت غير متصل — المعاملات ستصف في قائمة الانتظار" + cached float + offline queue badge showing count |
| Slow (3s+) | Float: last known shown with "جارِ التحديث..." spinner beside it |
| Float Low (<100K) | Float card turns red + alert icon + "⚠️ رصيد الصندوق منخفض — قم بالتعبئة" |
| Float Critical (<50K) | Same as low but card pulses + "🚨 رصيد الصندوق حرج — لا يمكن إتمام السحوبات" + cash-out button disabled |
| Float High (>5M) | Float card turns gold + "🌟 رصيد ممتاز" badge |
| New notification | Red badge on bell icon + toast: "📢 إعلان جديد من Beza" |

## Screen 2: Cash-in Screen

### Layout
```
┌────────────────────────────────┐
│ <  إيداع نقدي                  │ Header with back
├────────────────────────────────┤
│ الخطوة 1 من 4                  │ Step indicator
│ [🟩][  ][  ][  ]              │ Progress dots
├────────────────────────────────┤
│ رقم الزبون                     │ Label
│ ┌──────────────────────────┐   │
│ │  096 123 4567           │   │ Phone input
│ └──────────────────────────┘   │
│ [1] [2] [3]                    │ Keypad (large buttons 56px)
│ [4] [5] [6]                    │
│ [7] [8] [9]                    │
│ [ ] [0] [⌫]                   │
├────────────────────────────────┤
│ ...أو امسح رمز QR               │ QR scan fallback
│ [📷 مسح QR]                    │
└────────────────────────────────┘

--- بعد إدخال الرقم ---

│ رمز التحقق                      │ Label
│ أرسلنا رمزاً إلى رقم الزبون      │ Instruction
│ ┌──────────────────────────┐   │
│ │  _  _  _  _             │   │ Code input (4 digits, large)
│ └──────────────────────────┘   │
│ [إعادة إرسال] بعد 30 ثانية      │
│ *الرمز صالح لمدة 5 دقائق*       │

--- بعد التحقق ---

│ المبلغ                          │
│ ┌──────────────────────────┐   │
│ │     100,000             │   │ Amount (36px, monospace)
│ │         ل.س              │   │
│ └──────────────────────────┘   │
│ المبلغ الأدنى: 5,000 ل.س        │
│ أقصى مبلغ: 3,000,000 ل.س       │
│ [1] [2] [3]                    │
│ [4] [5] [6]                    │
│ [7] [8] [9]                    │
│ [00] [0] [⌫]                  │

--- بعد التأكيد ---

│ تأكيد الإيداع                    │
│ الزبون: أم خالد                 │
│ الرقم: 0961234567               │
│ المبلغ: 100,000 ل.س             │
│ عمولتك: 500 ل.س (0.5%)         │
│ ┌──────────────────────────┐   │
│ │       [تأكيد الإيداع]      │   │ Green CTA
│ └──────────────────────────┘   │
│ [إلغاء]                        │

--- بعد النجاح ---

│ 🟢                             │ Large checkmark
│ تم الإيداع بنجاح!               │
│ 100,000 ل.س                     │
│ ┌──────────────────────────┐   │
│ │ [طباعة الإيصال]           │   │
│ └──────────────────────────┘   │
│ [معاملة جديدة]                  │
```

### States
| State | Behavior |
|-------|----------|
| Step 1 — Enter Phone | Keypad visible, phone mask +963 XX XXX XXXX, "التالي" disabled until 9 digits |
| Step 1 — Invalid Number | Red border + "رقم هاتف غير صحيح — أدخل 9 أرقام" |
| Step 1 — QR Scan | Camera opens, scans QR code, auto-fills phone number |
| Step 2 — Sending Code | "جارٍ إرسال رمز التحقق..." spinner |
| Step 2 — Code Sent | 4 input boxes, auto-advance on each digit, "إعادة إرسال" after 30s |
| Step 2 — Code Expired | "انتهت صلاحية الرمز — اضغط على إعادة إرسال" |
| Step 2 — Wrong Code | "رمز غير صحيح — المحاولة 1 من 3" → after 3 fails: "تم حظر الرمز — أعد إرسال رمز جديد" |
| Step 3 — Enter Amount | Keypad with commas, real-time "عمولتك المقدرة: X ل.س" below |
| Step 3 — Below Minimum | "المبلغ الأدنى 5,000 ل.س" + confirm disabled |
| Step 3 — Above Maximum | "تم تجاوز الحد الأقصى للإيداع" + limit shown |
| Step 3 — Float Insufficient | "⚠️ رصيد الصندوق غير كافٍ — أقصى مبلغ: 200,000 ل.س" |
| Step 4 — Confirming | Spinner "جاري تنفيذ المعاملة..." (target <2s) |
| Step 4 — Success | Checkmark animation (Lottie) + amount + CTA to print |
| Step 4 — Failed | Red X + reason + "إعادة المحاولة" button |
| Step 4 — Offline Queued | Blue cloud icon + "ستتم المعاملة تلقائياً عند الاتصال" |
| Step 4 — Slow Network | "جارِ الاتصال... تأكد من بقاء التطبيق مفتوحاً" |

## Screen 3: Cash-out Screen

### Layout
```
┌────────────────────────────────┐
│ <  سحب نقدي                    │ Header
├────────────────────────────────┤
│ الخطوة 1 من 5                  │
│ [🟥][  ][  ][  ][  ]          │
├────────────────────────────────┤
│ (Same phone input + verification as Cash-in)
├────────────────────────────────┤
│ المبلغ                         │
│ ┌──────────────────────────┐   │
│ │     50,000              │   │
│ │         ل.س              │   │
│ └──────────────────────────┘   │
│ المبلغ الأدنى: 5,000 ل.س        │
│ أقصى مبلغ: 2,000,000 ل.س       │
├────────────────────────────────┤
│ تفاصيل المعاملة                 │
│ المبلغ المطلوب: 50,000 ل.س     │
│ رسوم السحب 1.5%: 750 ل.س      │
│ إجمالي الخصم: 50,750 ل.س       │
│ رصيد الزبون بعد: 199,250 ل.س   │
│ عمولتك المقدرة: 375 ل.س        │
├────────────────────────────────┤
│ [تأكيد السحب]                  │ Red CTA
├────────────────────────────────┤
│ --- بعد التأكيد (فوق 500K) ---  │
│ التحقق من البصمة                │
│ ┌──────────────────────────┐   │
│ │      👆                   │   │
│ │    ضع إصبعك هنا          │   │ Biometric prompt
│ └──────────────────────────┘   │
│ *أو أدخل الرقم السري*          │ Fallback
└────────────────────────────────┘
```

### States
| State | Behavior |
|-------|----------|
| Step 1-2 — Phone + Code | Same as Cash-in |
| Step 3 — Amount Input | Shows fee breakdown in real-time |
| Step 3 — Insufficient Customer Balance | "⚠️ رصيد الزبون غير كافٍ — الرصيد: 30,000 ل.س — أقصى مبلغ للسحب: 29,250 ل.س" |
| Step 3 — Exceeds Agent Float | "⚠️ رصيدك النقدي غير كافٍ — أقصى مبلغ للسحب الآن: 200,000 ل.س" |
| Step 3 — Exceeds Agent Daily Cash-out Limit | "تم تجاوز حد السحب اليومي — باقي: 1,500,000 ل.س" |
| Step 4 — PIN Verification (Customer) | "أدخل الرقم السري للمحفظة" + keypad (hidden digits) + 3 attempts |
| Step 4 — PIN Blocked | "تم حظر الرقم السري — الرجاء مراجعة الدعم" (30 min) |
| Step 5 — Biometric (if >500K) | Fingerprint icon + "ضع إصبع الزبون على القارئ" |
| Step 5 — Biometric Fail | "فشل التحقق — أدخل الرقم السري بدلاً من ذلك" |
| Step 6 — Success | "تم السحب بنجاح! سلم 50,000 ل.س للزبون" + print receipt |
| Step 6 — Failed | Red X + reason |
| Step 6 — Cash Handover Confirmation | "هل سلمت النقود للزبون؟" [نعم] [لا] → if "نعم": complete. If "لا": timer 120s, then auto-complete |
