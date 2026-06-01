# 14 - حدود النجاح والفشل (Thresholds)

```javascript
// scripts/thresholds.js
import http from 'k6/http';
import { check, sleep } from 'k6';

export let options = {
  vus: 50,
  duration: '1m',
  thresholds: {
    // مدة الاستجابة
    http_req_duration: ['p(95)<200', 'p(99)<500', 'avg<100', 'max<1000'],
    // معدل الفشل
    http_req_failed: ['rate<0.01'],
    // عدد الطلبات
    http_reqs: ['count>1000', 'rate>15'],
    // مدة التكرار
    iteration_duration: ['max<3000'],
    // مدة الانتظار (الخادم)
    http_req_waiting: ['p(95)<180'],
    // مدة الربط (DNS + TCP)
    http_req_connecting: ['p(95)<50'],
    // مدة TLS
    http_req_tls_handshaking: ['p(95)<100'],
  },
};

const BASE_URL = 'http://localhost:8000/api/v1';

export default function () {
  const res = http.get(`${BASE_URL}/ping`);
  check(res, { 'pong': (r) => r.json().message === 'pong' });
  sleep(1);
}
```

## شرح الحدود

| الحد | الشرح |
|------|--------|
| `p(95)<200` | 95% من الطلبات أسرع من 200ms |
| `p(99)<500` | 99% من الطلبات أسرع من 500ms |
| `rate<0.01` | أقل من 1% أخطاء |
| `count>1000` | على الأقل 1000 طلب |
| `max<1000` | أبطأ طلب أقل من 1 ثانية |

## التشغيل

```bash
k6 run scripts/thresholds.js

# تجاهل الحدود (للمراقبة فقط)
k6 run --no-thresholds scripts/thresholds.js

# حدود صارمة
k6 run --thresholds-only scripts/thresholds.js
```
