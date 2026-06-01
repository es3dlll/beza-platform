# 13 - مقاييس مخصصة (Custom Metrics)

```javascript
// scripts/custom-metrics.js
import http from 'k6/http';
import { check, sleep } from 'k6';
import { Trend, Rate, Counter, Gauge } from 'k6/metrics';

// مقاييس مخصصة
const transferDuration = new Trend('transfer_duration');
const walletDuration = new Trend('wallet_duration');
const errorRate = new Rate('custom_error_rate');
const totalTransfers = new Counter('total_transfers');
const activeUsers = new Gauge('active_users');

export let options = {
  stages: [
    { duration: '30s', target: 100 },
    { duration: '1m', target: 100 },
    { duration: '30s', target: 0 },
  ],
};

const BASE_URL = 'http://localhost:8000/api/v1';

export default function () {
  const txnType = __ENV.TXN_TYPE || 'transfer';
  let res;

  if (txnType === 'transfer') {
    res = http.post(`${BASE_URL}/transfer`, JSON.stringify({
      to_phone: '963900000002',
      amount: 10,
      currency: 'USD',
      pin: '1234',
    }), {
      headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${__ENV.TOKEN}` },
    });
    transferDuration.add(res.timings.duration);
    totalTransfers.add(1);
  } else {
    res = http.get(`${BASE_URL}/wallet`, {
      headers: { 'Authorization': `Bearer ${__ENV.TOKEN}` },
    });
    walletDuration.add(res.timings.duration);
  }

  const failed = res.status >= 400;
  errorRate.add(failed);
  activeUsers.add(__VU);

  check(res, {
    'status is 2xx': (r) => r.status >= 200 && r.status < 300,
  });

  sleep(1);
}
```

## التقارير المخصصة

```bash
# تشغيل مع مقاييس transfer
k6 run scripts/custom-metrics.js -e TXN_TYPE=transfer -e TOKEN=%K6_TOKEN%

# تشغيل مع مقاييس wallet
k6 run scripts/custom-metrics.js -e TXN_TYPE=wallet -e TOKEN=%K6_TOKEN%

# تصدير كـ JSON لتحليل لاحق
k6 run scripts/custom-metrics.js --out json=reports/custom-metrics.json
```
