# 02 - مكان الصفحة في تطبيق React (Architecture Position)

## موقع Dashboard ضمن نظام الصفحات

```
src/
├── main.jsx                        # نقطة الدخول (Token قبل createRoot)
├── App.jsx                         # Router + AuthProvider
├── layouts/
│   └── AppLayout.jsx               # Layout الرئيسي مع Auth Guard
├── pages/
│   ├── Dashboard.jsx               # ← الصفحة الرئيسية (نحن هنا)
│   ├── Transfer.jsx                # تحويل يدوي
│   ├── QrScan.jsx                  # مسح QR
│   ├── QrGenerate.jsx              # إنشاء QR
│   ├── RequestMoney.jsx            # طلب مال
│   ├── Transactions.jsx            # كل المعاملات
│   ├── Notifications.jsx           # الإشعارات
│   ├── NotificationDetail.jsx      # تفاصيل الإشعار
│   └── ... (باقي الصفحات)
├── components/
│   ├── PageShell.jsx               # القالب المشترك للصفحات
│   └── ... (مكونات أخرى)
├── services/
│   ├── api.js                      # Axios instance
│   └── tokenService.js             # إدارة التوكن
├── contexts/
│   └── AuthContext.jsx              # حالة المصادقة
└── index.css                       # Tailwind v4 + @keyframes
```

## هيكل Dashboard.jsx (طبقات المكونات)

```
Dashboard
├── AppBar (مدمج)
│   ├── Avatar (أحرف أولى)
│   ├── تحية + اسم مستخدم
│   ├── تاريخ عربي
│   ├── جرس الإشعارات ← Dropdown
│   └── ترس الإعدادات
│
├── KYC Alert (قابل للإخفاء)
│
├── Bento Grid (max-w-7xl)
│   ├── Wallet Card (col-span-2)
│   │   ├── QR Code (حقيقي)
│   │   ├── الرصيد + أزرار العملة
│   │   └── رقم الحساب + نسخ
│   │
│   ├── Exchange Widget (col-span-2)
│   │   ├── إدخال المبلغ
│   │   ├── اختيار العملة (USD/SYP)
│   │   ├── النتيجة التلقائية
│   │   └── زر الصرف
│   │
│   ├── Action Grid 2×2
│   │   ├── إرسال ← Bottom Sheet
│   │   ├── بطاقات → /cards
│   │   ├── استقبال ← Bottom Sheet
│   │   └── ذهب وفضة → /gold-silver
│   │
│   └── Transactions List
│       ├── آخر 6 معاملات
│       └── عرض الكل ← /transactions
│
├── Send Bottom Sheet (Modal)
└── Receive Bottom Sheet (Modal)
```
