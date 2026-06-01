# TST3 - اختبارات الحمل (K6)

## الوصف
اختبار أداء API تحت الضغط.

## سيناريو 1: Ping
```javascript
import http from 'k6/http';
import { check, sleep } from 'k6';

export let options = {
  stages: [
    { duration: '30s', target: 100 },
    { duration: '1m', target: 100 },
    { duration: '30s', target: 0 },
  ],
};

export default function () {
  let res = http.get('http://localhost:8000/api/ping');
  check(res, { 'status is 200': (r) => r.status === 200 });
  sleep(1);
}
```

## سيناريو 2: معاملات متزامنة
اختبار 100 مستخدم يقومون بتحويلات في وقت واحد.
```javascript
import http from 'k6/http';
import { check } from 'k6';

export let options = {
  vus: 100,
  duration: '30s',
};

export default function () {
  let payload = JSON.stringify({
    to_phone: '0998765432',
    amount: 10,
    currency: 'USD',
    pin: '1234',
  });
  let res = http.post('http://localhost:8000/api/transfer', payload, {
    headers: { 'Authorization': 'Bearer TOKEN', 'Content-Type': 'application/json' },
  });
  check(res, { 'transfer status 200': (r) => r.status === 200 });
}
```

## أهداف الأداء
| المقياس | الهدف |
|---------|-------|
| زمن استجابة API (p95) | < 200ms |
| معاملات في الثانية | 10,000 TPS |
| وقت تحميل الصفحة | < 2 ثوانٍ |
| معدل الخطأ | < 1% |

## تشغيل
```bash
k6 run load-test.js
```
