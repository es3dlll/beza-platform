# 13 - معالجة الأخطاء (Exception Handling)

## نمط معالجة الأخطاء الموحد

```jsx
try {
  const { data } = await api.post('/transfer', form);
  setSendMsg('تم التحويل بنجاح');
  setTimeout(() => {
    setShowSend(false);
    setSendMsg('');
  }, 1500);
} catch (err) {
  setSendMsg(err.response?.data?.message || 'فشل التحويل');
}
```

## سيناريوهات الأخطاء

| # | السيناريو | السبب | المعالجة |
|---|-----------|-------|----------|
| 1 | `err.response?.data?.message` موجود | خطأ من السيرفر (رصيد غير كافٍ، PIN خاطئ) | عرض الرسالة كما هي |
| 2 | `err.response?.data?.message` غير موجود | خطأ غير معروف من السيرفر | رسالة افتراضية: "فشل التحويل" |
| 3 | `err.response` غير موجود (Network Error) | انقطاع الشبكة | رسالة افتراضية: "فشل التحويل" |
| 4 | `err.code === 'ECONNABORTED'` | Timeout | رسالة: "انتهت مهلة الطلب" |

## أخطاء الصرف (Exchange)

```jsx
setExResult({ error: err.response?.data?.message || 'فشلت العملية' });
setTimeout(() => setExResult(null), 3000);
```

- عند الخطأ: رسالة حمراء مع دائرة حمراء
- عند النجاح: رسالة خضراء مع animate-pulse
- تختفي تلقائياً بعد 3 ثوانٍ

## أخطاء الـ API العامة

```jsx
// GET /wallet/balance
api.get('/wallet/balance').then(...).catch(() => {});
// صامت — لا نعرض خطأ للمستخدم في تحميل الرصيد

// GET /auth/me
api.get('/auth/me').then(...).catch(() => {});
// صامت — يستخدم القيمة الافتراضية

// GET /wallet/rates
api.get('/wallet/rates').then(...).catch(() => {});
// صامت — يستخدم السعر الافتراضي 12500
```

## أخطاء النموذج (Form Errors)

| الحالة | المعالجة |
|--------|----------|
| حقل فارغ | الزر معطل (`disabled`) |
| PIN ناقص | الزر معطل |
| مبلغ ≤ 0 | الزر معطل |
| جاري الإرسال | الزر معطل + رسالة جاري التحميل |
