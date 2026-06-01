# 04 - علاقات المكونات وشجرة الـ DOM (Component Tree)

## شجرة المكونات الكاملة

```
<Dashboard>                                       # صفحة كاملة، خلفية #080c1a
│
├── <div className="max-w-7xl mx-auto mb-6">      # AppBar
│   │
│   ├── تحية + اسم مستخدم                         # flex items-center
│   ├── جرس الإشعارات + ترس                        # flex items-center
│   │   └── Notification Dropdown                  # absolute positioned
│   │       ├── Header (عدد الإشعارات)
│   │       ├── 4 Notifications (color-coded)
│   │       │   ├── وارد    → أخضر (#22c55e)
│   │       │   ├── صادر    → أحمر (#ef4444)
│   │       │   ├── سعر     → ذهبي (#F5A623)
│   │       │   └── توثيق   → أخضر (#22c55e)
│   │       └── Footer (عرض جميع)
│   │
│   ├── التاريخ العربي
│   └── Gold Divider                              # linear-gradient
│
├── KYC Alert                                      # قابل للإخفاء (dismissKyc)
│
├── <BentoGrid>                                    # grid-cols-1 sm:2 lg:3 xl:4
│   │
│   ├── Wallet Card (col-span-2)                   # linear-gradient خلفية
│   │   ├── Brand + Eye toggle
│   │   ├── QR Code (qrcode library)
│   │   ├── Balance + Currency pills
│   │   └── Account number + Copy button
│   │
│   ├── Exchange Widget (col-span-2)               # glassmorphism
│   │   ├── Header (ArrowsRightLeftIcon + rate)
│   │   ├── Input (amount + currency toggle)
│   │   ├── Result display
│   │   ├── Feedback message (نجاح/فشل)
│   │   └── CTA Button (gold gradient)
│   │
│   ├── Action Grid (col-span-4)                   # grid-cols-2 sm:4
│   │   ├── إرسال    → showSend=true
│   │   ├── بطاقات   → navigate(/cards)
│   │   ├── استقبال  → showReceive=true
│   │   └── ذهب وفضة → navigate(/gold-silver)
│   │
│   └── Transactions (col-span-4)                  # قائمة آخر 6
│       ├── Header (عرض الكل → /transactions)
│       └── 6x TransactionRow
│           ├── Icon (in/out)
│           ├── Name + Time
│           └── Amount
│
├── Send Bottom Sheet                               # fixed bottom-0
│   ├── Header (عنوان + زر إغلاق)
│   ├── to_phone input
│   ├── amount + currency
│   ├── pin input
│   ├── description textarea
│   ├── رسالة نجاح/فشل
│   └── زر تأكيد (مع loading)
│
└── Receive Bottom Sheet                            # fixed bottom-0
    ├── Header (عنوان + زر إغلاق)
    ├── QR Code أيقونة
    ├── Account number + Copy
    └── زر عرض QR → /qr-generate
```

## تبعيات المكونات الخارجية

| المكتبة | الاستخدام | الإصدار |
|---------|-----------|---------|
| `react` | UI Framework | 19 |
| `react-router-dom` | التوجيه (navigate) | 7 |
| `qrcode` | إنشاء QR code | ^1.5 |
| `@heroicons/react` | الأيقونات | ^2 |
| Tailwind CSS v4 | التصميم | 4 |
