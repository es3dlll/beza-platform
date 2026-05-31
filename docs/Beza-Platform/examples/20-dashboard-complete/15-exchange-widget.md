# 15 - واجهة الصرف الفوري (Exchange Widget)

## الفكرة

واجهة صرف مبسطة:
1. المستخدم يدخل المبلغ
2. يختار العملة المصدر (USD أو SYP)
3. النتيجة تظهر تلقائياً
4. زر واحد للصرف

## المكونات

```
Exchange Widget
├── Header
│   ├── أيقونة ArrowsRightLeftIcon (ذهبية)
│   └── سعر الصرف الحالي (قابل للنقر للتبديل)
│
├── Input
│   ├── حقل إدخال المبلغ (text + inputMode=decimal)
│   └── أزرار العملة (USD / SYP) — تبديل اتجاه
│
├── Result
│   └── المبلغ المحول + العملة
│
├── Feedback (اختياري)
│   └── رسالة نجاح/فشل (تظهر 3 ثوانٍ)
│
└── CTA Button
    └── "صرف X USD/SYP" (ذهبي متدرج)
```

## تدفق البيانات

```
1. المستخدم يدخل الرقم
   → setExAmt(value.replace(/[^0-9.]/g, ''))
   → converted = exAmt * rate (أو exAmt / rate)

2. المستخدم يضغط زر العملة
   → swapDir() تعكس اتجاه الصرف
   → converted يعاد حسابه تلقائياً

3. المستخدم يضغط "صرف"
   → handleExchange()
   → POST /wallet/exchange {from, to, amount}
   → عرض رسالة نجاح/فشل
```

## سعر الصرف

```jsx
// جلب السعر من API عند تحميل الصفحة
useEffect(() => {
  api.get('/wallet/rates').then(({ data }) => {
    if (data?.data?.USD_SYP) setExRate(data.data.USD_SYP);
  }).catch(() => {});
}, []);

// عرض السعر في header
{exDir === 'toSYP'
  ? exRate.toLocaleString('ar-SA')
  : (1 / exRate).toFixed(6)}
```

## حالات الـ Edge

| السيناريو | السلوك |
|-----------|--------|
| حقل فارغ | النتيجة "٠"، الزر معطل |
| مبلغ 0 | الزر معطل |
| مبلغ سالب | مستحيل (regex يمنع إلا الأرقام والنقطة) |
| قيد التحميل | الزر معطل + spinner |
| نجاح | رسالة خضراء + اختفاء بعد 3 ثوانٍ |
| فشل | رسالة حمراء + اختفاء بعد 3 ثوانٍ |
