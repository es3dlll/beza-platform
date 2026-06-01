import { test, expect } from "@playwright/test";

const API_BASE = "http://localhost:8000";

const adminLoginResponse = {
  success: true,
  data: {
    user: { id: 1, name: "مدير النظام", email: "admin@beza.com", role: "admin" },
    permissions: ["manage_wap"],
  },
};

const wapSummaryResponse = {
  success: true,
  data: {
    total_users: 150,
    wap_users: 120,
    total_transactions: 3450,
    pending_transactions: 12,
    total_devices: 85,
  },
};

test.describe("Admin Login + WAP Data Display", () => {
  test("shows admin login form with Arabic labels", async ({ page }) => {
    await page.goto("/login");
    await expect(page.getByText("لوحة الإدارة")).toBeVisible();
    await expect(page.getByText("Beza Platform")).toBeVisible();
    await expect(page.getByText("البريد الإلكتروني")).toBeVisible();
    await expect(page.getByText("كلمة المرور")).toBeVisible();
  });

  test("logs in and displays dashboard with WAP summary stats", async ({ page }) => {
    await page.route(`${API_BASE}/api/v1/admin/login`, async (route) => {
      await route.fulfill({
        status: 200,
        contentType: "application/json",
        headers: { "Set-Cookie": "admin_token=mock-token-123; Path=/; HttpOnly" },
        body: JSON.stringify(adminLoginResponse),
      });
    });
    await page.route(`${API_BASE}/api/v1/admin/me`, async (route) => {
      await route.fulfill({ status: 200, contentType: "application/json", body: JSON.stringify(adminLoginResponse) });
    });
    await page.route(`${API_BASE}/api/v1/admin/wap/summary`, async (route) => {
      await route.fulfill({ status: 200, contentType: "application/json", body: JSON.stringify(wapSummaryResponse) });
    });

    await page.goto("/login");
    await page.fill('input[type="email"]', "admin@beza.com");
    await page.fill('input[type="password"]', "adminpass");
    await page.click('button[type="submit"]');

    await page.waitForURL("/");
    await expect(page.getByText("لوحة التحكم")).toBeVisible();
    await expect(page.getByText("إجمالي المستخدمين")).toBeVisible();
    await expect(page.getByText("150")).toBeVisible();
    await expect(page.getByText("مستخدمي WAP")).toBeVisible();
    await expect(page.getByText("120")).toBeVisible();
    await expect(page.getByText("إجمالي المعاملات")).toBeVisible();
    await expect(page.getByText("3450")).toBeVisible();
  });

  test("navigates to WAP management panel and sees data", async ({ page }) => {
    await page.route(`${API_BASE}/api/v1/admin/login`, async (route) => {
      await route.fulfill({
        status: 200,
        contentType: "application/json",
        headers: { "Set-Cookie": "admin_token=mock-token-123; Path=/; HttpOnly" },
        body: JSON.stringify(adminLoginResponse),
      });
    });
    await page.route(`${API_BASE}/api/v1/admin/me`, async (route) => {
      await route.fulfill({ status: 200, contentType: "application/json", body: JSON.stringify(adminLoginResponse) });
    });
    await page.route(`${API_BASE}/api/v1/admin/wap/summary`, async (route) => {
      await route.fulfill({ status: 200, contentType: "application/json", body: JSON.stringify(wapSummaryResponse) });
    });
    await page.route(`${API_BASE}/api/v1/admin/wap/devices`, async (route) => {
      await route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({
          success: true,
          data: [
            { fingerprint: "abc123", user_agent: "Mozilla/5.0", request_count: 45, last_seen: "2026-05-31T12:00:00Z" },
            { fingerprint: "def456", user_agent: "Dalvik/2.1.0", request_count: 12, last_seen: "2026-05-30T08:00:00Z" },
          ],
        }),
      });
    });
    await page.route(`${API_BASE}/api/v1/admin/wap/queue`, async (route) => {
      await route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({
          success: true,
          data: {
            counts: { pending: 5, completed: 3400, failed: 45 },
            recent: [
              { id: 1, user: "أحمد", amount: 50000, currency: "SYP", status: "completed", created_at: "2026-05-31T10:00:00Z" },
            ],
          },
        }),
      });
    });
    await page.route(`${API_BASE}/api/v1/admin/wap/routes`, async (route) => {
      await route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({
          success: true,
          data: [
            { id: 1, method: "*", pattern: "/api/v1/wap/auth/login", target: "AuthController@login", roles: ["*"], priority: 0, is_active: true },
            { id: 2, method: "GET", pattern: "/api/v1/wap/merchant/*", target: "MerchantController", roles: ["merchant"], priority: 2, is_active: true },
          ],
        }),
      });
    });

    await page.goto("/login");
    await page.fill('input[type="email"]', "admin@beza.com");
    await page.fill('input[type="password"]', "adminpass");
    await page.click('button[type="submit"]');
    await page.waitForURL("/");

    await page.click('a[href="/wap"]');
    await page.waitForURL("/wap");

    await expect(page.getByRole("heading", { name: "إدارة WAP" })).toBeVisible();
    await expect(page.getByText("حالة طابور المعالجة")).toBeVisible();
    await expect(page.getByText("الأجهزة المسجلة")).toBeVisible();
    await expect(page.getByText("قواعد التوجيه / الصلاحيات")).toBeVisible();
    await expect(page.getByText("abc123")).toBeVisible();
    await expect(page.getByText("/api/v1/wap/auth/login")).toBeVisible();
    await expect(page.getByText("5", { exact: true })).toBeVisible();
    await expect(page.getByText("3400")).toBeVisible();
  });

  test("redirects unauthenticated user to login page", async ({ page }) => {
    await page.goto("/");
    await page.waitForURL("/login");
  });
});
