# 10 - اختبار الإجهاد (Stress Test)

```javascript
// scripts/stress-test.js
import http from 'k6/http';
import { check, sleep } from 'k6';

export let options = {
  stages: [
    { duration: '30s', target: 100 },
    { duration: '30s', target: 300 },
    { duration: '30s', target: 500 },    // الذروة
    { duration: '30s', target: 500 },
    { duration: '30s', target: 0 },
  ],
  thresholds: {
    http_req_duration: ['p(95)<500'],    // يسمح بارتفاع تحت الضغط
    http_req_failed: ['rate<0.05'],       // 5% أخطاء مسموحة تحت الضغط
  },
};

const BASE_URL = 'http://localhost:8000/api/v1';

export default function () {
  // خليط من الطلبات
  const requestType = Math.random();

  if (requestType < 0.3) {
    // Ping
    http.get(`${BASE_URL}/ping`);
  } else if (requestType < 0.6) {
    // Wallet
    http.get(`${BASE_URL}/wallet`, {
      headers: { 'Authorization': `Bearer ${__ENV.TOKEN}` },
    });
  } else {
    // Transfer
    http.post(`${BASE_URL}/transfer`, JSON.stringify({
      to_phone: '963900000002',
      amount: 1,
      currency: 'USD',
      pin: '1234',
    }), {
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${__ENV.TOKEN}`,
      },
    });
  }

  sleep(0.5);
}
```

## التشغيل

```bash
# تعيين التوكن
set K6_TOKEN=1|your-test-token

k6 run scripts/stress-test.js -e TOKEN=%K6_TOKEN%

# أو من ملف
k6 run scripts/stress-test.js -e TOKEN=$(cat token.txt)
```
