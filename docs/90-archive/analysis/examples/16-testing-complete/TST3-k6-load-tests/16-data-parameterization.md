# 16 - تخصيص البيانات (Data Parameterization)

## ملف CSV

```csv
# data/users.csv
phone,pin,currency
963900000001,1234,USD
963900000002,5678,USD
963900000003,9012,EUR
963900000004,3456,GBP
963900000005,7890,USD
```

## Shared Array

```javascript
// scripts/parameterized-test.js
import http from 'k6/http';
import { check, sleep } from 'k6';
import { SharedArray } from 'k6/data';
import papaparse from 'https://jslib.k6.io/papaparse/5.1.1/index.js';

const users = new SharedArray('users', function () {
  return papaparse.parse(open('data/users.csv'), { header: true }).data;
});

export let options = {
  vus: 5,
  duration: '30s',
};

const BASE_URL = 'http://localhost:8000/api/v1';

export default function () {
  const user = users[Math.floor(Math.random() * users.length)];

  const res = http.post(`${BASE_URL}/transfer`, JSON.stringify({
    to_phone: user.phone,
    amount: Math.floor(Math.random() * 100) + 1,
    currency: user.currency,
    pin: user.pin,
  }), {
    headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${__ENV.TOKEN}` },
  });

  check(res, {
    'transfer successful': (r) => r.status === 200,
  });

  sleep(1);
}
```

## مولد البيانات الديناميكي

```javascript
// scripts/data-generator.js
import http from 'k6/http';
import { check, sleep } from 'k6';

function generateTransfer() {
  const phones = [
    '963900000001', '963900000002', '963900000003',
    '963900000004', '963900000005', '963900000006',
  ];
  const currencies = ['USD', 'EUR', 'GBP', 'SYP'];

  return {
    to_phone: phones[Math.floor(Math.random() * phones.length)],
    amount: Math.round((Math.random() * 500 + 1) * 100) / 100,
    currency: currencies[Math.floor(Math.random() * currencies.length)],
    pin: '1234',
  };
}

export default function () {
  const payload = JSON.stringify(generateTransfer());
  const res = http.post('http://localhost:8000/api/v1/transfer', payload, {
    headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${__ENV.TOKEN}` },
  });
  check(res, { 'status 200': (r) => r.status === 200 });
  sleep(0.5);
}
```

## التشغيل

```bash
k6 run scripts/parameterized-test.js -e TOKEN=%K6_TOKEN%
k6 run scripts/data-generator.js -e TOKEN=%K6_TOKEN%
```
