# 19 - حالات الحافة (Edge Cases)

```javascript
// scripts/edge-cases-test.js
import http from 'k6/http';
import { check, sleep } from 'k6';

export let options = {
  vus: 10,
  duration: '1m',
  thresholds: {
    http_req_failed: ['rate<0.1'],
  },
};

const BASE_URL = 'http://localhost:8000/api/v1';

export default function () {
  const testCase = __ITER % 6;

  switch (testCase) {
    case 0:
      // Payload فارغ
      http.post(`${BASE_URL}/transfer`, '', {
        headers: { 'Content-Type': 'application/json' },
      });
      break;

    case 1:
      // مبلغ سالب
      http.post(`${BASE_URL}/transfer`, JSON.stringify({
        to_phone: '963900000001',
        amount: -50,
        currency: 'USD',
        pin: '1234',
      }), {
        headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${__ENV.TOKEN}` },
      });
      break;

    case 2:
      // مبلغ كبير جداً
      http.post(`${BASE_URL}/transfer`, JSON.stringify({
        to_phone: '963900000001',
        amount: 999999999,
        currency: 'USD',
        pin: '1234',
      }), {
        headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${__ENV.TOKEN}` },
      });
      break;

    case 3:
      // رقم هاتف غير صالح
      http.post(`${BASE_URL}/transfer`, JSON.stringify({
        to_phone: '000',
        amount: 10,
        currency: 'USD',
        pin: '1234',
      }), {
        headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${__ENV.TOKEN}` },
      });
      break;

    case 4:
      // PIN خاطئ
      http.post(`${BASE_URL}/transfer`, JSON.stringify({
        to_phone: '963900000001',
        amount: 10,
        currency: 'USD',
        pin: '0000',
      }), {
        headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${__ENV.TOKEN}` },
      });
      break;

    case 5:
      // بدون توكن مصادقة
      http.post(`${BASE_URL}/transfer`, JSON.stringify({
        to_phone: '963900000001',
        amount: 10,
        currency: 'USD',
        pin: '1234',
      }), {
        headers: { 'Content-Type': 'application/json' },
      });
      break;
  }

  sleep(0.5);
}
```

## حالات إضافية

| الحالة | المتوقع |
|--------|---------|
| رأس Content-Type خاطئ | 415 Unsupported Media Type |
| مبلغ 0 | 422 Validation Error |
| عملة غير مدعومة | 422 Validation Error |
| تحويل لنفس المستخدم | 400 Bad Request |
| رصيد غير كافٍ | 402 Payment Required |
| محاولة XSS في name | 422 Validation Error |
| SQL Injection في phone | 200 (مع عدم التأثير) |
