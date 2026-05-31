# 11 - الأحداث والمؤثرات (Events & Effects)

## قائمة الـ useEffect

| الترتيب | المحفز | الوظيفة | الاعتماديات |
|---------|--------|---------|-------------|
| 1 | تحميل الصفحة | جلب رصيد المحفظة | `[]` |
| 2 | تحميل الصفحة | جلب معلومات المستخدم | `[setUser]` |
| 3 | تحميل الصفحة | إنشاء QR code | `[]` |
| 4 | تحميل الصفحة | جلب سعر الصرف | `[]` |
| 5 | `showNotifs === true` | مراقبة النقر الخارجي لإخفاء القائمة | `[showNotifs]` |

## الأحداث التفاعلية (Event Handlers)

| الحدث | الوظيفة |
|-------|---------|
| `handleSend()` | إرسال طلب POST /transfer |
| `handleExchange()` | إرسال طلب POST /wallet/exchange |
| `copyAccount()` | نسخ رقم الحساب إلى clipboard |
| `swapDir()` | تبديل اتجاه الصرف (USD→SYP / SYP→USD) |
| `handleAction(action)` | معالجة أزرار الإجراءات (إرسال/بطاقات/استقبال/ذهب) |
| `onClick => setShowNotifs(!showNotifs)` | فتح/غلق قائمة الإشعارات |
| `onClick => navigate(path)` | التنقل إلى صفحة أخرى |

## نمط الـ Outside Click

```jsx
const notifRef = useRef(null);

useEffect(() => {
  if (!showNotifs) return;
  const handler = (e) => {
    if (notifRef.current && !notifRef.current.contains(e.target))
      setShowNotifs(false);
  };
  document.addEventListener('mousedown', handler);
  return () => document.removeEventListener('mousedown', handler);
}, [showNotifs]);
```

## المؤقتات (Timers)

| المؤقت | المدة | الوظيفة |
|--------|-------|---------|
| `setTimeout` → 1500ms | 1.5 ثانية | إخفاء رسالة النجاح وإعادة تعيين النموذج |
| `setTimeout` → 2000ms | 2 ثانية | إعادة حالة "تم النسخ" |
| `setTimeout` → 3000ms | 3 ثانية | إخفاء رسالة نتيجة الصرف |
