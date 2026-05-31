# 11 - اختبار الذروة (Spike Test)

```javascript
// scripts/spike-test.js
import http from 'k6/http';
import { check, sleep } from 'k6';

export let options = {
  stages: [
    { duration: '2m', target: 50 },     // حالة طبيعية
    { duration: '10s', target: 500 },    // ذروة مفاجئة
    { duration: '30s', target: 500 },    // ثبات عند الذروة
    { duration: '30s', target: 50 },     // عودة للطبيعي
    { duration: '1m', target: 50 },      // تعافي
  ],
  thresholds: {
    http_req_duration: ['p(90)<300', 'p(95)<800'],
    http_req_failed: ['rate<0.05'],
  },
};

const BASE_URL = 'http://localhost:8000/api/v1';

export default function () {
  const requests = {
    'ping': () => http.get(`${BASE_URL}/ping`),
    'wallet': () => http.get(`${BASE_URL}/wallet`, {
      headers: { 'Authorization': `Bearer ${__ENV.TOKEN}` },
    }),
    'transfer': () => http.post(`${BASE_URL}/transfer`, JSON.stringify({
      to_phone: '963900000002',
      amount: 1,
      currency: 'USD',
      pin: '1234',
    }), {
      headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${__ENV.TOKEN}` },
    }),
  };

  const keys = Object.keys(requests);
  const key = keys[Math.floor(Math.random() * keys.length)];
  const res = requests[key]();
  check(res, { [`${key} status 2xx`]: (r) => r.status >= 200 && r.status < 300 });
  sleep(0.5);
}
```

## التشغيل

```bash
k6 run scripts/spike-test.js -e TOKEN=%K6_TOKEN% --out json=reports/spike.json
```

## النتيجة المتوقعة

```
http_req_duration..............: avg=120ms  p(90)=280ms  p(95)=650ms
http_req_failed................: 2.1%
```
