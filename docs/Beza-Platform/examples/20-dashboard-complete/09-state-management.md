# 09 - إدارة الحالة (State Management)

## قائمة المتغيرات (State Variables)

| المتغير | النوع | الافتراضي | الوصف |
|---------|-------|-----------|-------|
| `balance` | `object|null` | `null` | بيانات الرصيد من API |
| `showBalance` | `boolean` | `true` | إظهار/إخفاء الرصيد |
| `showSend` | `boolean` | `false` | فتح/غلق Bottom Sheet الإرسال |
| `showReceive` | `boolean` | `false` | فتح/غلق Bottom Sheet الاستقبال |
| `sendForm` | `object` | `{to_phone, amount, currency, pin, description}` | بيانات نموذج الإرسال |
| `sendMsg` | `string` | `''` | رسالة نجاح/فشل الإرسال |
| `sendLoading` | `boolean` | `false` | حالة تحميل الإرسال |
| `copied` | `boolean` | `false` | تم نسخ رقم الحساب |
| `qrDataUrl` | `string` | `''` | صورة QR (base64) |
| `activeCurr` | `number` | `0` | العملة النشطة في الرصيد |
| `exDir` | `string` | `'toSYP'` | اتجاه الصرف (toSYP/toUSD) |
| `exRate` | `number` | `12500` | سعر الصرف الحالي |
| `exAmt` | `string` | `''` | مبلغ الصرف المدخل |
| `exResult` | `object|null` | `null` | نتيجة الصرف |
| `exSending` | `boolean` | `false` | حالة تحميل الصرف |
| `dismissKyc` | `boolean` | `false` | إخفاء تنبيه KYC |
| `showNotifs` | `boolean` | `false` | فتح/غلق قائمة الإشعارات |

## القيم المحسوبة (Computed Values)

```jsx
// التحية حسب الوقت
const hr = new Date().getHours();
const greeting = hr >= 5 && hr < 12 ? 'صباح الخير'
  : hr >= 12 && hr < 18 ? 'مساء الخير' : 'مساء الخير';

// التاريخ العربي
const days = ['الأحد','الإثنين','الثلاثاء','الأربعاء','الخميس','الجمعة','السبت'];
const months = ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'];
const dateStr = `${days[now.getDay()]}، ${now.getDate()} ${months[now.getMonth()]}`;

// نتيجة الصرف التلقائية
const converted = exAmt
  ? exDir === 'toSYP'
    ? (Number(exAmt) * exRate).toFixed(2)
    : (Number(exAmt) / exRate).toFixed(2)
  : '0.00';
```

## تدفق الحالة (State Flow)

```
useEffect[1] → GET /wallet/balance → setBalance(data)
useEffect[2] → GET /auth/me → setUser(data) (من AuthContext)
useEffect[3] → QRCode.toDataURL() → setQrDataUrl(url)
useEffect[4] → GET /wallet/rates → setExRate(data)
useEffect[5] → mousedown listener → setShowNotifs(false) [outside click]
```

## React 19 Strict Mode

تستخدم `useEffect` مع `StrictMode` — ضمان عدم وجود side effects مكررة:

```jsx
useEffect(() => {
  api.get('/wallet/balance').then(({ data }) => {
    setBalance(data.data);
  }).catch(() => {});
}, []); // [] → يشتغل مرة واحدة فقط في الإنتاج
```
