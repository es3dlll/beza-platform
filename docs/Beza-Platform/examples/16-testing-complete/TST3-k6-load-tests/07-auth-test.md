# 07 - اختبار المصادقة (Auth Test)

```javascript
// scripts/auth-test.js
import http from 'k6/http';
import { check, sleep } from 'k6';

export let options = {
  stages: [
    { duration: '10s', target: 20 },
    { duration: '30s', target: 100 },
    { duration: '10s', target: 0 },
  ],
  thresholds: {
    http_req_duration: ['p(95)<300'],  // Auth أبطأ قليلاً (hashing)
    http_req_failed: ['rate<0.02'],
  },
};

const BASE_URL = 'http://localhost:8000/api/v1';

export default function () {
  // محاكاة تسجيل الدخول
  const phone = `9639${String(Math.floor(Math.random() * 10000000)).padStart(8, '0')}`;
  const password = 'StrongPass123';

  // Register
  const registerPayload = JSON.stringify({
    name: `User_${__VU}_${__ITER}`,
    phone: phone,
    password: password,
    password_confirmation: password,
    pin: '1234',
  });

  const regRes = http.post(`${BASE_URL}/auth/register`, registerPayload, {
    headers: { 'Content-Type': 'application/json' },
  });

  check(regRes, {
    'register status 200': (r) => r.status === 200,
  });

  sleep(1);

  // Login
  const loginPayload = JSON.stringify({ phone, password });
  const loginRes = http.post(`${BASE_URL}/auth/login`, loginPayload, {
    headers: { 'Content-Type': 'application/json' },
  });

  check(loginRes, {
    'login status 200': (r) => r.status === 200,
    'has token': (r) => r.json().data.token !== undefined,
  });

  sleep(2);
}
```
