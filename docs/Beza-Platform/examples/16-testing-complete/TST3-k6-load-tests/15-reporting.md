# 15 - تقارير النتائج (Reporting)

## تقرير نصي

```bash
# تقرير ملخص (يظهر تلقائياً بعد التشغيل)
k6 run scripts/mixed-workload.js

# تصدير CSV
k6 run scripts/mixed-workload.js --out csv=reports/results.csv

# تصدير JSON
k6 run scripts/mixed-workload.js --out json=reports/results.json
```

## تقرير HTML (Dashboard)

```bash
# تشغيل وفتح dashboard في المتصفح
k6 run scripts/mixed-workload.js --out web-dashboard

# حفظ التقرير
k6 run scripts/mixed-workload.js --out web-dashboard=reports/dashboard.html
```

## تحليل JSON

```javascript
// scripts/analyze-results.js
const fs = require('fs');
const results = JSON.parse(fs.readFileSync('reports/results.json', 'utf8'));

// حساب الإحصائيات
const durations = results.metrics.http_req_duration.values;
console.log('=== ملخص النتائج ===');
console.log(`avg: ${durations.avg}ms`);
console.log(`min: ${durations.min}ms`);
console.log(`med: ${durations.med}ms`);
console.log(`max: ${durations.max}ms`);
console.log(`p(90): ${durations['p(90)']}ms`);
console.log(`p(95): ${durations['p(95)']}ms`);
console.log('');
console.log(`الطلبات الكلية: ${results.metrics.http_reqs.values.count}`);
console.log(`معدل الأخطاء: ${(results.metrics.http_req_failed.values.rate * 100).toFixed(2)}%`);
```

## تقارير متقدمة (اختياري)

للمراقبة المتقدمة، يمكن تصدير النتائج إلى CSV أو JSON وتحليلها بأدوات خارجية مثل Excel أو Grafana Cloud.
