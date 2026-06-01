# 12 - اختبار التحمل (Soak Test)

```javascript
// scripts/soak-test.js
import http from 'k6/http';
import { check, sleep } from 'k6';

export let options = {
  stages: [
    { duration: '5m', target: 100 },    // ramp up
    { duration: '60m', target: 100 },   // تحمل لمدة ساعة
    { duration: '5m', target: 0 },      // ramp down
  ],
  thresholds: {
    http_req_duration: ['p(95)<300'],
    http_req_failed: ['rate<0.01'],
    iteration_duration: ['avg<2000'],
  },
};

const BASE_URL = 'http://localhost:8000/api/v1';

export default function () {
  // محاكاة سلوك مستخدم حقيقي
  http.get(`${BASE_URL}/ping`);
  sleep(2);

  http.get(`${BASE_URL}/wallet`, {
    headers: { 'Authorization': `Bearer ${__ENV.TOKEN}` },
  });
  sleep(3);

  http.post(`${BASE_URL}/transfer`, JSON.stringify({
    to_phone: '963900000002',
    amount: 10,
    currency: 'USD',
    pin: '1234',
  }), {
    headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${__ENV.TOKEN}` },
  });
  sleep(5);

  http.get(`${BASE_URL}/transactions?page=1`, {
    headers: { 'Authorization': `Bearer ${__ENV.TOKEN}` },
  });
  sleep(2);
}
```

## التشغيل

```bash
k6 run scripts/soak-test.js -e TOKEN=%K6_TOKEN% --out csv=reports/soak.csv
```

## مراقبة التسريبات

- راقب استخدام الذاكرة (RAM) بمرور الوقت
- تحقق من اتصالات قاعدة البيانات المفتوحة
- تأكد من عدم زيادة زمن الاستجابة مع الوقت
