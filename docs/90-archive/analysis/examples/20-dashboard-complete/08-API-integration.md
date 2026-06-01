# 08 - نقاط الـ API المستخدمة (API Endpoints)

## قائمة النقاط

| العملية | الطريقة | النقطة | البيانات | التردد |
|---------|---------|--------|----------|--------|
| عرض الرصيد | GET | `/wallet/balance` | - | عند تحميل الصفحة |
| معلومات المستخدم | GET | `/auth/me` | - | عند تحميل الصفحة |
| سعر الصرف | GET | `/wallet/rates` | - | عند تحميل الصفحة |
| تحويل P2P | POST | `/transfer` | `to_phone`, `amount`, `currency`, `pin`, `description` | عند الإرسال |
| صرف عملات | POST | `/wallet/exchange` | `from`, `to`, `amount` | عند الصرف |

## توثيق API (Authorization)

جميع النقاط تتطلب `Bearer Token`:

```jsx
// api.js — Axios interceptor
api.interceptors.request.use(async (config) => {
  const token = tokenService.getValidToken();
  if (token) config.headers.Authorization = `Bearer ${token}`;
  return config;
});
```

## الاستجابة المتوقعة

### GET /wallet/balance

```json
{
  "success": true,
  "data": {
    "wallets": [
      { "currency": "USD", "available": 500.00, "wallet_number": "63XXXXXX" },
      { "currency": "SYP", "available": 100000.00, "wallet_number": "63XXXXXX" }
    ]
  }
}
```

### POST /transfer

```json
{
  "success": true,
  "message": "تم التحويل بنجاح",
  "data": {
    "transaction": {
      "reference_number": "BZ260527143200A1B2C3",
      "amount": 100.00,
      "currency": "USD",
      "status": "completed"
    },
    "new_balance": 400.00
  }
}
```

### POST /wallet/exchange

```json
{
  "success": true,
  "data": {
    "amount_sent": 100.00,
    "from_currency": "USD",
    "amount_received": 1250000.00,
    "to_currency": "SYP"
  }
}
```

## معالجة الأخطاء

```jsx
// نمط معالجة الأخطاء الموحد
try {
  const { data } = await api.post('/transfer', form);
  // نجاح
} catch (err) {
  const msg = err.response?.data?.message || 'فشلت العملية';
  // عرض الخطأ
}
```
