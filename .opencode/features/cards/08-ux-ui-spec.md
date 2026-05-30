# Cards UX/UI Specification

## Screen 1: Cards Home / Carousel

### Layout
```
┌────────────────────────────────┐
│ <  البطاقات              +     │ Header
├────────────────────────────────┤
│                                │
│  ┌────────────────────────┐    │ Card Carousel
│  │ █████████████████████  │    │ Card visual (SYP)
│  │ Mastercard             │    │ Network logo
│  │ ██ ████ ████ █1234    │    │ Last 4 digits
│  │                      │    │
│  │ Layla's Card           │    │ Card nickname
│  │ VALID THRU 12/28       │    │ Expiry
│  │ ●●●● ●●●● ●●●● 1234 │    │ Masked PAN
│  │                        │    │
│  │ [نشطة]  الرصيد: 500,000│    │ Balance
│  └────────────────────────┘    │
│                                │
│  ○ ● ○                         │ Page dots (3 cards)
│                                │
│  ┌──────────┐ ┌──────────┐     │ Quick actions
│  │ تجميد     │ │ إظهار     │    │ Freeze / Show details
│  │ الرقم     │ │           │    │
│  └──────────┘ └──────────┘     │
│  ┌──────────┐ ┌──────────┐     │
│  │الحدود     │ │ المعاملات│    │ Limits / Transactions
│  └──────────┘ └──────────┘     │
│                                │
│  [بطاقة لمرة واحدة]           │ One-time card FAB
│                                │
├────────────────────────────────┤
│ [Bottom Tab Bar]               │
│  الرئيسية | الدفع | البطاقات | المزيد
└────────────────────────────────┘
```

### States
| State | Behavior |
|-------|----------|
| Loading | Skeleton: card shape with shimmer, 3x action button placeholders |
| Empty | "مرحباً! أنشئ بطاقتك الأولى" with illustration + CTA "إنشاء بطاقة" |
| No physical card ordered | "اطلب بطاقة بلاستيكية" with preview + CTA |
| Error | "تعذر تحميل البطاقات" + "إعادة المحاولة" |
| Card frozen | Card visual greyed out with "مجمدة" badge overlay |

## Screen 2: Create Card

### Layout
```
┌────────────────────────────────┐
│ <  إنشاء بطاقة جديدة           │ Header
├────────────────────────────────┤
│                                │
│  نوع البطاقة                   │
│  ┌────────────────────────┐    │
│  │ ● افتراضية  │ ○ بلاستيكية│  │ Card type toggle
│  │   500 ل.س   │  15,000 ل.س│  │ Fee shown
│  └────────────────────────┘    │
│                                │
│  العملة                        │
│  ┌────────────────────────┐    │
│  │ ○ ل.س (SYP)  │ ● دولار │    │ Currency
│  └────────────────────────┘    │
│                                │
│  اسم البطاقة                   │
│  ┌────────────────────────┐    │
│  │  بطاقة التسوق           │    │ Nickname input
│  └────────────────────────┘    │
│                                │
│  حدود الصرف                    │
│  أونلاين:  500,000 ل.س  [تعديل]│
│  نقاط بيع: 200,000 ل.س [تعديل]│
│  صراف:     غير مفعل     [تعديل]│
│  دولي:     غير مفعل     [تعديل]│
│                                │
│  الرسوم:                       │
│  رسوم الإصدار: 5,000 ل.س      │
│  رسوم شهرية: مجاناً            │
│  الإجمالي: 5,000 ل.س          │
│                                │
│  [إنشاء البطاقة]               │ Primary CTA
└────────────────────────────────┘
```

### States
| State | Behavior |
|-------|----------|
| Type selection | Fee updates dynamically based on type |
| Currency change | Limits adjust (USD limits = SYP/12500) |
| KYC insufficient | "تحتاج إلى توثيق الحساب (المستوى 2)" with upgrade link |
| Create in progress | Loading spinner + "جارٍ إنشاء بطاقتك..." |
| Success | Card reveal animation (flip from loading to card face) |
| Error | "فشل إنشاء البطاقة" + reason + retry |

## Screen 3: Transaction Detail

### Layout
```
┌────────────────────────────────┐
│ <  تفاصيل المعاملة             │ Header
├────────────────────────────────┤
│        🟢                       │ Status icon
│        تمت التسوية              │ Status
├────────────────────────────────┤
│         125,000 ل.س             │ Amount
├────────────────────────────────┤
│ التاجر                         │
│ ┌──────────────────────────┐   │
│ │ AliExpress               │   │ Merchant name
│ │ تسوق إلكتروني            │   │ Category
│ │ الصين                     │   │ Country
│ └──────────────────────────┘   │
├────────────────────────────────┤
│ التفاصيل                       │
│ البطاقة: *** 1234             │
│ التاريخ: 1 يونيو 2026          │
│ الوقت: 15:30                   │
│ النوع: أونلاين                 │
│ رمز التفويض: AUTH-ABC123       │
│ رقم المرجع: RRN-987654         │
│ STAN: 123456                   │
├────────────────────────────────┤
│ [الإبلاغ عن معاملة]            │ Report
└────────────────────────────────┘
```
