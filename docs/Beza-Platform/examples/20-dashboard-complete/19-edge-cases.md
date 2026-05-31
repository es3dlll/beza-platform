# 19 - حالات الحافة (Edge Cases)

## 1. مستخدم بدون رصيد (First-time User)

| السيناريو | السلوك المتوقع |
|-----------|---------------|
| `balance = null` | Skeleton loader (مستطيلات رمادية) |
| `wallets` فارغ | "63 **** ****" يظهر كرقم افتراضي |
| `user?.name` غير موجود | "مستخدم" يظهر كاسم افتراضي |
| `user?.name`.charAt(0) غير موجود | "U" يظهر في avatar |

## 2. فشل في تحميل QR Code

```jsx
// إذا فشل QR، يظهر QrCodeIcon كـ placeholder
{qrDataUrl ? (
  <img src={qrDataUrl} alt="QR" />
) : (
  <QrCodeIcon className="w-14 h-14" />
)}
```

## 3. خطأ في الـ API صامت

```jsx
// كل أخطاء API الصامتة تسمح للصفحة بالعمل بشكل طبيعي
api.get('/wallet/balance').then(...).catch(() => {});
// → balance يبقى null → Skeleton يظهر
```

## 4. تبديل العملة السريع (Rapid Toggle)

```jsx
// المستخدم يضغط أزرار العملة بسرعة
// → converted يعاد حسابه في كل مرة
// → لا توجد race condition (useState مستقر)
```

## 5. إرسال مكرر (Double Submit)

```jsx
// الزر معطل أثناء التحميل يمنع الإرسال المكرر
disabled={sendLoading || ...}
// و
disabled={exSending || ...}
```

## 6. إخفاء KYC (Dismissible)

```jsx
// عند الإخفاء، لا يظهر مجدداً حتى إعادة تحميل الصفحة
// لا يوجد localStorage persistence (متعمد — يظهر في كل جلسة)
```

## 7. قائمة الإشعارات الفارغة

حالياً هناك 4 إشعارات hardcoded. إذا كانت القائمة فارغة، يظهر:
- Header مع "0 جديد"
- Footer "عرض جميع الإشعارات"
- القائمة الفارغة

## 8. النقر خارج الـ Bottom Sheet

```jsx
// قناع خلفي يغلق الـ sheet عند النقر
<div className="fixed inset-0 z-40" onClick={() => setShowSend(false)} />
```

## 9. توافق RTL مع الأرقام

```jsx
// المبالغ تستخدم المتصفح لتنسيق الأرقام العربية
Number(converted).toLocaleString('ar-SA')
// مثال: 12500 → "١٢٬٥٠٠"

// أرقام الهواتف dir="ltr" لمنع انعكاس الترتيب
<input type="tel" dir="ltr" placeholder="رقم المستلم" />
```

## 10. شاشة اللمس (Touch Devices)

```css
/* منع التحديد والتكبير */
-webkit-tap-highlight-color: transparent;
user-select: none;

/* أزرار بحجم مناسب للمس */
py-3.5 px-4  /* على الأقل 44px ارتفاع */
```
