# FX Engine UX/UI Specification

## Screen 1: Exchange Home / Rate Dashboard

### Layout
```
┌────────────────────────────────┐
│ <  أسعار الصرف              🔔 │ Header
├────────────────────────────────┤
│ ┌──────────────────────────┐   │ SYP/USD Card
│ │ USD/SYP              ⭐  │   │ Pair + favorite toggle
│ │ 14,850                     │   │ Beza Rate (large)
│ │ شراء: 14,700 | بيع: 15,000│   │ Bid / Ask
│ │ آخر تحديث: قبل 5 ثوان     │   │ Last updated
│ │ 📊 24h chart (sparkline)  │   │ Mini chart
│ │ 🔽 3 مصادر │ معدل السوق   │   │ Sources count + tag
│ └──────────────────────────┘   │
│ ┌──────────────────────────┐   │ SYP/EUR Card
│ │ EUR/SYP              ⭐  │   │
│ │ 16,250                     │   │
│ │ شراء: 16,100 | بيع: 16,400│   │
│ │ آخر تحديث: قبل 10 ثوان    │   │
│ └──────────────────────────┘   │
│ ┌──────────────────────────┐   │ USD/EUR Card
│ │ EUR/USD               ⭐  │   │
│ │ 1.094                     │   │
│ │ آخر تحديث: قبل 8 ثوان     │   │
│ └──────────────────────────┘   │
├────────────────────────────────┤
│ [Convert Now — Primary CTA]   │ Fixed bottom button
└────────────────────────────────┘
```

### Expanded Rate Detail (Tap card)
```
┌────────────────────────────────┐
│ <  USD/SYP                     │ Header
├────────────────────────────────┤
│ سعر Beza                       │
│ 14,850 SYP/USD                 │
│                                   │
│ تفاصيل السعر                    │
│ سعر السوق: 14,550               │
│ هامش Beza: +300 (2.1%)         │
│                                   │
│ المصادر (3)                     │
│ 🟢 CBS الرسمي: 13,100          │ Official
│ 🟢 السوق الموازي: 14,550       │ Parallel
│ 🟢 السوق السوداء: 15,200        │ Black
│                                   │
│ [مخطط 24 ساعة]                  │ Chart
│ [GBP, SAR, AED rates...]        │ Other currencies
└────────────────────────────────┘
```

### States
| State | Behavior |
|-------|----------|
| Loading | 3x skeleton rate cards with shimmer |
| Empty (no providers) | "لم يتم تكوين أي مصدر سعر" with admin CTA |
| All providers down | "جميع مصادر الأسعار غير متاحة" + "إعادة المحاولة" |
| Partial data | Show available rates, gray out missing ones with "غير متاح" |
| Stale rates (>30s) | Amber banner "قد تكون الأسعار قديمة" |
| Anomaly detected | Red banner "تم الكشف عن تقلب غير طبيعي" |

## Screen 2: Convert Currency

### Layout
```
┌────────────────────────────────┐
│ <  تحويل عملة                  │ Header
├────────────────────────────────┤
│ من                              │ Source
│ ┌──────────────────────────┐   │
│ │ 💰 المحفظة بالليرة       │   │ Wallet picker
│ │ الرصيد: 10,000,000 ل.س   │   │ Balance display
│ └──────────────────────────┘   │
│                                   │
│ إلى                             │ Target
│ ┌──────────────────────────┐   │
│ │ 💵 المحفظة بالدولار      │   │
│ │ الرصيد: $250             │   │
│ └──────────────────────────┘   │
│                                   │
│ المبلغ                          │
│ ┌──────────────────────────┐   │
│ │    5,000,000   ل.س       │   │ Amount (large, centered)
│ └──────────────────────────┘   │
│  (quick amounts: 100K, 500K, 1M, 5M) │
│                                   │
│ ┌──────────────────────────┐   │ Rate Preview
│ │ السعر: 1 USD = 14,935   │   │
│ │ سترسل: 5,000,000 ل.س     │   │
│ │ ستستلم: $334.78          │   │
│ │ الفارق: 150,000 ل.س      │   │ Spread amount
│ └──────────────────────────┘   │
│                                   │
│ [🔒 تثبيت السعر لمدة 30 ثانية]  │ Lock & convert CTA
└────────────────────────────────┘
```

### States
| State | Behavior |
|-------|----------|
| Loading wallets | Skeleton for wallet pickers |
| Rate fetching | Pulsing rate preview "جارِ جلب السعر..." |
| Rate locked | Green glow on rate card + countdown timer |
| Confirming | "تأكيد...", PIN input |
| Successful | "تم التحويل!" + receipt details |
| Failed | "فشل التحويل" + reason + retry |
| Rate expired | "انتهت صلاحية السعر" + "الحصول على سعر جديد" button |

## Screen 3: Conversion Detail / Receipt

### Layout
```
┌────────────────────────────────┐
│ <  تفاصيل التحويل              │ Header
├────────────────────────────────┤
│        🟢                       │ Status icon
│        تم بنجاح                 │ Status
├────────────────────────────────┤
│          $334.78                │ Received amount
│                   أو            │
│          5,000,000 ل.س         │ Sent amount
├────────────────────────────────┤
│ المعلومات                      │
│ المرجع: FX-CONV-ABC123         │
│ التاريخ: 1 يونيو 2026          │
│ الوقت: 10:30 صباحاً            │
│ الحالة: مكتملة                 │
├────────────────────────────────┤
│ تفاصيل السعر                    │
│ السعر المثبت: 14,935 SYP/USD   │
│ السعر الأصلي: 14,550           │
│ هامش Beza: 385 (2.6%)          │
│ المصدر: السوق الموازي          │
│ معرف تثبيت السعر: lock_abc123 │
├────────────────────────────────┤
│ [مشاركة الإيصال] [دعم]          │ Actions
└────────────────────────────────┘
```

### States
| State | Behavior |
|-------|----------|
| Pending conversion | Spinner + "جاري تنفيذ التحويل..." |
| Completed | Green checkmark + full receipt |
| Failed | Red X + error detail + "إعادة المحاولة" |
| Expired | Amber + "تم إلغاء التحويل" |
