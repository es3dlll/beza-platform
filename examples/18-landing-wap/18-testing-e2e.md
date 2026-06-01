# اختبارات E2E — Playwright

## النطاق
اختبار التدفق الكامل: دخول ← عرض الرصيد ← تحويل (أونلاين) ← تحويل (أوفلاين مع مزامنة لاحقة)

## الإعداد
```bash
cd frontend/wap
npx playwright install
npm install @playwright/test
```

## Playwright Config
```typescript
// playwright.config.ts
import { defineConfig } from '@playwright/test';

export default defineConfig({
  testDir: './e2e',
  webServer: {
    command: 'npm run dev',
    port: 3002,
    reuseExistingServer: true,
  },
  use: {
    baseURL: 'http://localhost:3002',
    locale: 'ar',
  },
});
```

## هيكل الاختبارات
```
frontend/wap/e2e/
├── 01-auth.spec.ts          ← تسجيل دخول، رفض، تحديث
├── 02-balance.spec.ts       ← عرض الرصيد (full + minimal)
├── 03-transfer.spec.ts      ← تحويل P2P + idempotency
├── 04-merchant.spec.ts      ← ملخص التاجر + QR
├── 05-agent.spec.ts         ← حدود الوكيل + العمولة
├── 06-offline-queue.spec.ts ← تحويل بدون إنترنت + مزامنة
└── 07-routing.spec.ts       ← الحماية: /user ← غير مصادق → /login
```

## اختبار Offline Queue
```typescript
test('offline transfer syncs when online', async ({ page, context }) => {
  await page.goto('/wap/login');
  await page.fill('[name="email"]', 'test@example.com');
  await page.fill('[name="password"]', 'password123');
  await page.click('button[type="submit"]');
  await page.waitForURL('/wap/dashboard');

  // محاكاة قطع الاتصال
  await context.setOffline(true);

  await page.goto('/wap/user/transfer');
  await page.fill('[name="amount"]', '500');
  await page.fill('[name="phone"]', '0999999999');
  await page.click('button:has-text("تحويل")');

  // التأكد من ظهور إشعار Offline Queue
  await expect(page.locator('text=تم حفظ التحويل')).toBeVisible();

  // إعادة الاتصال
  await context.setOffline(false);

  // الانتظار للمزامنة
  await page.waitForTimeout(2000);

  // التحقق من اختفاء العنصر من Queue
  await expect(page.locator('[data-testid="queue-count"]')).toHaveText('0');
});
```

## تقارير الأداء — k6
```javascript
import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
  stages: [
    { duration: '30s', target: 20 },
    { duration: '1m', target: 50 },
    { duration: '30s', target: 0 },
  ],
};

export default function () {
  const res = http.get('http://localhost:8000/api/v1/wap/wallet/balance?format=minimal', {
    headers: { 'Cookie': `token=${__ENV.JWT_TOKEN}` },
  });
  check(res, { 'status 200': (r) => r.status === 200 });
  sleep(1);
}
```
