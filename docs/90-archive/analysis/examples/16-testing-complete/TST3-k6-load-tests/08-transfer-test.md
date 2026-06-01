# 08 - اختبار التحويلات (Transfer Test)

```javascript
// scripts/transfer-test.js
import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate, Trend } from 'k6/metrics';

const errorRate = new Rate('errors');
const transferLatency = new Trend('transfer_latency');

export let options = {
  stages: [
    { duration: '10s', target: 10 },
    { duration: '30s', target: 100 },
    { duration: '30s', target: 100 },
    { duration: '10s', target: 0 },
  ],
  thresholds: {
    http_req_duration: ['p(95)<200'],
    http_req_failed: ['rate<0.01'],
    transfer_latency: ['p(95)<200'],
  },
};

const BASE_URL = 'http://localhost:8000/api/v1';
const SENDER_PHONE = '963900000001';
const SENDER_PASSWORD = 'password';
const RECEIVER_PHONES = [
  '963900000002', '963900000003', '963900000004',
  '963900000005', '963900000006', '963900000007',
];

// تسجيل الدخول مرة واحدة (setup)
export function setup() {
  const res = http.post(`${BASE_URL}/auth/login`, JSON.stringify({
    phone: SENDER_PHONE,
    password: SENDER_PASSWORD,
  }), { headers: { 'Content-Type': 'application/json' } });

  return { token: res.json().data.token };
}

export default function (data) {
  const token = data.token;
  const toPhone = RECEIVER_PHONES[Math.floor(Math.random() * RECEIVER_PHONES.length)];
  const amount = Math.floor(Math.random() * 100) + 1; // 1-100 USD

  const payload = JSON.stringify({
    to_phone: toPhone,
    amount: amount,
    currency: 'USD',
    pin: '1234',
  });

  const start = Date.now();
  const response = http.post(`${BASE_URL}/transfer`, payload, {
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`,
    },
  });
  const latency = Date.now() - start;

  transferLatency.add(latency);

  const success = check(response, {
    'transfer status 201': (r) => r.status === 201,
    'transfer success': (r) => r.json().success === true,
  });

  errorRate.add(!success);

  sleep(1);
}
```
