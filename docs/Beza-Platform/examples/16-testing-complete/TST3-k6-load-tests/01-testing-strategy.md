# 01 - استراتيجية اختبارات الحمل (K6 Load Testing Strategy)

## نظرة عامة

نستخدم [K6](https://k6.io) لاختبارات الحمل للتأكد من قدرة النظام على تحمل الضغط المتوقع والإضافي.

## أهداف الأداء

| المقياس | الهدف | الحد الأقصى |
|---------|-------|-------------|
| **p95 API latency** | < 200ms | 300ms |
| **p99 API latency** | < 500ms | 800ms |
| **Error rate** | < 1% | 2% |
| **Throughput** | 10,000 transaction/min | 15,000 |
| **Concurrent users** | 500 peak | 1,000 |

## أنواع اختبارات الحمل

| النوع | الوصف | المستخدمون | المدة | الهدف |
|-------|-------|-----------|-------|-------|
| Smoke | تحقق أساسي من تشغيل API | 1–5 | 30 ثانية | التأكد أن API لا يعيد 500 |
| Load (Normal) | عبء عمل طبيعي متوقع | 200 | 5 دقائق | قياس زمن الاستجابة الطبيعي |
| Stress | أقصى طاقة متوقعة | 500–1,000 | 3 دقائق | تحديد نقطة الانهيار |
| Spike | ذروة مفاجئة | 100 → 500 فجأة | دقيقة | اختبار auto-scaling |
| Soak | تحمل طويل الأمد | 200 | 30–60 دقيقة | كشف تسرب الذاكرة |

## سيناريوهات الاختبار

### 1. اختبار Endpoint التحويلات (Transfer) — 1000 مستخدم متزامن

```javascript
import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
    stages: [
        { duration: '30s', target: 300 },   // صعود تدريجي
        { duration: '1m',  target: 1000 },  // ذروة
        { duration: '30s', target: 0 },     // هبوط
    ],
    thresholds: {
        http_req_duration: ['p(95)<200', 'p(99)<500'],
        http_req_failed:   ['rate<0.01'],
    },
};

export default function () {
    const payload = JSON.stringify({
        from_wallet_id: 'wallet_' + (__VU * 100 + __ITER % 100),
        to_wallet_id:   'wallet_target_' + __VU,
        amount:         1000 + (__VU % 100) * 500,
        currency:       'SYP',
    });

    const params = {
        headers: {
            'Content-Type':  'application/json',
            'Authorization': `Bearer ${__ENV.AUTH_TOKEN}`,
        },
    };

    const res = http.post(`${__ENV.BASE_URL}/api/v1/transfers`, payload, params);

    check(res, {
        'status is 201 or 200': (r) => r.status === 201 || r.status === 200,
        'response time < 300ms': (r) => r.timings.duration < 300,
    });

    sleep(1);
}
```

### 2. اختبار Wallet Balance — قراءة مكثفة

```javascript
export default function () {
    const walletId = 'wallet_' + (__VU % 500);
    const res = http.get(
        `${__ENV.BASE_URL}/api/v1/wallets/${walletId}/balance`,
        { headers: { Authorization: `Bearer ${__ENV.AUTH_TOKEN}` } }
    );

    check(res, {
        'status is 200':       (r) => r.status === 200,
        'returns balance':     (r) => JSON.parse(r.body).balance !== undefined,
        'response time < 50ms': (r) => r.timings.duration < 50,
    });
}
```

### 3. اختبار Login — المصادقة

```javascript
export default function () {
    const payload = JSON.stringify({
        phone:    '09' + String(10000000 + __VU).slice(0, 7),
        password: 'TestPass123!',
    });

    const res = http.post(`${__ENV.BASE_URL}/api/v1/auth/login`, payload, {
        headers: { 'Content-Type': 'application/json' },
    });

    check(res, {
        'login successful':       (r) => r.status === 200,
        'token returned':         (r) => JSON.parse(r.body).token !== undefined,
        'response time < 500ms':  (r) => r.timings.duration < 500,
    });
}
```

## تحضير بيانات الاختبار (Test Data Preparation)

قبل تشغيل اختبارات الحمل، يجب تهيئة البيانات مسبقاً:

```bash
# إنشاء 1000 مستخدم وهمي مع محافظ مسبقة التمويل
php artisan test:seed-load-test-data --users=1000 --balance=1000000

# تصدير التوكن للمستخدمين إلى ملف JSON لاستخدامه في K6
php artisan test:export-auth-tokens --count=1000 --output=tokens.json
```

## المقاييس المطلوب جمعها (Metrics)

| المقياس في K6 | الوصف | العتبة (Threshold) |
|---------------|-------|-------------------|
| `http_req_duration` | زمن استجابة الطلب كاملاً | p95 < 200ms |
| `http_req_failed` | نسبة الطلبات الفاشلة | < 1% |
| `iterations` | عدد مرات تنفيذ السكريبت | مراقبة |
| `vus` | عدد المستخدمين المتزامنين | مراقبة |
| `http_req_waiting` | وقت انتظار أول بايت (TTFB) | p95 < 100ms |
| `data_received` | حجم البيانات المستقبلة | مراقبة |

## التكامل مع CI (GitHub Actions)

```yaml
# .github/workflows/load-test.yml
name: Load Test
on:
  schedule:
    - cron: '0 6 * * 1'   # كل يوم اثنين 6 صباحاً
  workflow_dispatch:       # يدوياً عند الحاجة

jobs:
  k6-load-test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: تشغيل اختبار الحمل
        uses: grafana/k6-action@v0.3.0
        with:
          filename: tests/k6/transfers-test.js
          flags: |
            --out json=results.json
            --env BASE_URL=https://staging.bezaplatform.com
            --env AUTH_TOKEN=${{ secrets.LOAD_TEST_AUTH_TOKEN }}
      - name: رفع النتائج
        uses: actions/upload-artifact@v4
        with:
          name: k6-results
          path: results.json
      - name: التحقق من العتبات
        run: |
          if grep -q '"http_req_failed".*"rate">=0.01' results.json; then
            echo "❌ فشل: نسبة الأخطاء تجاوزت 1%"
            exit 1
          fi
          echo "✅ جميع العتبات ضمن الحدود المسموحة"
```

## تنبيهات الفشل

عند تجاوز أي عتبة (threshold)، يرسل النظام تنبيهاً:

```javascript
// thresholds في الكود تضمن فشل الاختبار تلقائياً عند التجاوز
export const options = {
    thresholds: {
        http_req_duration: ['p(95)<200', 'p(99)<500'],
        http_req_failed:   ['rate<0.01'],
        checks:            ['rate>0.95'],
    },
};
```

إذا فشل الاختبار، يتم إشعار الفريق عبر Slack/Email مع رابط إلى تقرير K6.
