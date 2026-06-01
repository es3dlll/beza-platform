# 05 - هيكل السكريبت (Script Structure)

## الهيكل الأساسي

```javascript
// lib/helpers.js — الدوال المساعدة
import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate, Trend } from 'k6/metrics';

// مقاييس مخصصة
const errorRate = new Rate('errors');
const transferDuration = new Trend('transfer_duration');

// الإعدادات الأساسية
export const BASE_URL = 'http://localhost:8000/api/v1';

// دالة مساعدة للتحقق
export function checkResponse(response, expectedStatus = 200) {
  const success = check(response, {
    'status is correct': (r) => r.status === expectedStatus,
    'response has success field': (r) => r.json().success === true,
  });

  errorRate.add(!success);
  return success;
}

// دالة تسجيل الدخول (مشتركة بين السكريبتات)
export function login(phone, password) {
  const payload = JSON.stringify({ phone, password });
  const params = {
    headers: { 'Content-Type': 'application/json' },
  };

  const response = http.post(`${BASE_URL}/auth/login`, payload, params);

  if (response.status === 200) {
    return response.json().data.token;
  }

  return null;
}

// دالة تحويل
export function transfer(token, toPhone, amount, pin) {
  const payload = JSON.stringify({
    to_phone: toPhone,
    amount: amount,
    currency: 'USD',
    pin: pin,
  });

  const params = {
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`,
    },
  };

  const start = Date.now();
  const response = http.post(`${BASE_URL}/transfer`, payload, params);
  const duration = Date.now() - start;

  transferDuration.add(duration);
  checkResponse(response, 201);

  return response;
}
```
