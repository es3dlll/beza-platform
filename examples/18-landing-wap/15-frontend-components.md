# مكونات الواجهة — WAP

## هيكل المكونات
```
components/
├── ui/
│   ├── Button.tsx
│   ├── Card.tsx
│   ├── Input.tsx
│   ├── Modal.tsx
│   ├── Spinner.tsx
│   └── Toast.tsx
├── layout/
│   ├── Header.tsx          ← اسم المستخدم + إشعارات
│   ├── BottomNav.tsx       ← 3-4 أزرار سفلى حسب الدور
│   └── PageShell.tsx       ← غلاف الصفحة (Header + BottomNav + محتوى)
├── pwa/
│   ├── InstallPrompt.tsx   ← زر تثبيت PWA
│   └── UpdateBanner.tsx    ← إشعار تحديث التطبيق
└── offline-queue/
    ├── OfflineIndicator.tsx  ← أيقونة الاتصال + عداد
    └── QueueModal.tsx        ← نافذة عرض Queue
```

## تدفق المكونات حسب الصفحة

### /wap/login
```
LoginPage
├── Logo
├── Input (email)
├── Input (password)
├── Button (تسجيل دخول)
└── Link (تسجيل)
```

### /wap/user
```
UserPage
├── Header (اسم + رصيد)
├── BalanceCard (SYP + USD)
├── QuickActions (تحويل, QR, إيداع)
├── RecentTransactions (آخر 5)
└── BottomNav (رصيد, تحويل, سجل, المزيد)
```

### /wap/merchant
```
MerchantPage
├── Header (اسم المتجر)
├── SummaryCard (مبيعات اليوم, الأسبوع, الشهر)
├── QRGenerator (مبلغ + إنشاء QR)
├── RecentSales
└── BottomNav (ملخص, QR, تسوية)
```

### /wap/agent
```
AgentPage
├── Header (اسم الوكيل)
├── LimitsCard (حدود الإيداع/السحب)
├── CommissionsCard (عمولة اليوم)
├── PendingList (معاملات معلقة)
└── BottomNav (الرئيسية, معلقة, طابور)
```

## حالة التحميل/الخطأ/الفارغ
كل صفحة تتبع نمط الـ 3 حالات:
```typescript
function UserPage() {
  const { data, isLoading, error } = useBalance();

  if (isLoading) return <LoadingSkeleton />;
  if (error) return <ErrorState message={error} onRetry={() => refetch()} />;
  if (!data) return <EmptyState message="لا توجد محافظ بعد" />;

  return <BalanceContent data={data} />;
}
```
