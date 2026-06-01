# 09 - عبء عمل مختلط (Mixed Workload)

```javascript
// scripts/mixed-workload.js
import http from 'k6/http';
import { check, sleep } from 'k6';
import { randomIntBetween } from 'https://jslib.k6.io/k6-utils/1.2.0/index.js';

export let options = {
  stages: [
    { duration: '30s', target: 50 },
    { duration: '1m', target: 200 },
    { duration: '30s', target: 200 },
    { duration: '30s', target: 0 },
  ],
  thresholds: {
    http_req_duration: ['p(95)<250'],
    http_req_failed: ['rate<0.02'],
  },
};

const BASE_URL = 'http://localhost:8000/api/v1';

// توزيع العبء
const WORKLOAD = [
  { name: 'ping', weight: 10 },
  { name: 'wallet', weight: 30 },
  { name: 'transfer', weight: 40 },
  { name: 'transactions', weight: 20 },
];

function selectEndpoint() {
  const totalWeight = WORKLOAD.reduce((sum, w) => sum + w.weight, 0);
  let random = Math.random() * totalWeight;
  for (const endpoint of WORKLOAD) {
    random -= endpoint.weight;
    if (random <= 0) return endpoint.name;
  }
  return 'ping';
}

export default function () {
  const endpoint = selectEndpoint();

  switch (endpoint) {
    case 'ping':
      http.get(`${BASE_URL}/ping`);
      break;
    case 'wallet':
      http.get(`${BASE_URL}/wallet`, {
        headers: { 'Authorization': `Bearer ${__ENV.TOKEN}` },
      });
      break;
    case 'transfer':
      // استخدام توكن من متغير البيئة
      http.post(`${BASE_URL}/transfer`, JSON.stringify({
        to_phone: '963900000002',
        amount: randomIntBetween(1, 50),
        currency: 'USD',
        pin: '1234',
      }), {
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${__ENV.TOKEN}`,
        },
      });
      break;
    case 'transactions':
      http.get(`${BASE_URL}/transactions`, {
        headers: { 'Authorization': `Bearer ${__ENV.TOKEN}` },
      });
      break;
  }

  sleep(randomIntBetween(0.5, 2));
}
```
