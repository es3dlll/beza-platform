# 06 - اختبار Ping (Ping Test)

```javascript
// scripts/ping-test.js
import http from 'k6/http';
import { check, sleep } from 'k6';

export let options = {
  stages: [
    { duration: '10s', target: 10 },   // ramp up
    { duration: '30s', target: 50 },   // ثابت
    { duration: '10s', target: 0 },    // ramp down
  ],
  thresholds: {
    http_req_duration: ['p(95)<200'],  // 95% من الطلبات أسرع من 200ms
    http_req_failed: ['rate<0.01'],    // < 1% أخطاء
  },
};

export default function () {
  const response = http.get('http://localhost:8000/api/ping');

  check(response, {
    'status is 200': (r) => r.status === 200,
    'response is pong': (r) => r.json().message === 'pong',
  });

  sleep(1);
}
```

## التشغيل

```bash
k6 run scripts/ping-test.js

# مع تقرير HTML
k6 run scripts/ping-test.js --out web-dashboard
```

## النتيجة المتوقعة

```
     data_received..................: 1.2 MB 28 kB/s
     data_sent......................: 84 kB  2.0 kB/s
     http_req_blocked...............: avg=8.2ms   min=0s       med=4µs
     http_req_connecting............: avg=4.1ms   min=0s       med=0s
     http_req_duration..............: avg=45.2ms  min=3.1ms    med=12.5ms
       { expected_response:true }...: avg=45.2ms  min=3.1ms    med=12.5ms
     http_req_failed................: 0.00%  ✓ 0        ✗ 150
     http_req_receiving.............: avg=2.1ms   min=1µs      med=1µs
     http_req_sending...............: avg=1.8ms   min=1µs      med=2µs
     http_req_tls_handshaking.......: avg=0s      min=0s       med=0s
     http_req_waiting...............: avg=41.2ms  min=2.9ms    med=10.2ms
     http_reqs......................: 150    3.571435/s
     iterations.....................: 150    3.571435/s
     vus............................: 1      min=1      max=50
     vus_max........................: 50     min=50     max=50
```
