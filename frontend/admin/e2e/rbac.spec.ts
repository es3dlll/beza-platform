import { test, expect } from "@playwright/test";

const API_BASE = "http://localhost:8000";

test.describe("RBAC — Access Control", () => {
  test("redirects to login when no admin_token cookie exists", async ({ page }) => {
    await page.goto("/wap");
    await page.waitForURL("/login");
  });

  test("redirects to login for protected routes without auth", async ({ page }) => {
    await page.goto("/");
    await page.waitForURL("/login");
  });

  test("shows 403 error when user lacks manage_wap permission", async ({ page }) => {
    const userResponse = {
      success: true,
      data: {
        user: { id: 2, name: "مستخدم عادي", email: "user@beza.com", role: "user" },
        permissions: [],
      },
    };

    await page.route(`${API_BASE}/api/admin/login`, async (route) => {
      await route.fulfill({
        status: 200,
        contentType: "application/json",
        headers: { "Set-Cookie": "admin_token=mock-token-123; Path=/; HttpOnly" },
        body: JSON.stringify(userResponse),
      });
    });
    await page.route(`${API_BASE}/api/admin/me`, async (route) => {
      await route.fulfill({ status: 200, contentType: "application/json", body: JSON.stringify(userResponse) });
    });

    await page.goto("/login");
    await page.fill('input[type="email"]', "user@beza.com");
    await page.fill('input[type="password"]', "userpass");
    await page.click('button[type="submit"]');

    await page.waitForURL("/");
    await page.click('a[href="/wap"]');
    await page.waitForURL("/wap");

    await expect(page.getByText("إدارة WAP")).toBeVisible();
  });

  test("shows forbidden error for non-admin API access", async ({ page }) => {
    await page.route(`${API_BASE}/api/admin/wap/summary`, async (route) => {
      await route.fulfill({
        status: 403,
        contentType: "application/json",
        body: JSON.stringify({
          success: false,
          error: { code: "FORBIDDEN", message: "غير مصرح بالوصول" },
        }),
      });
    });

    const res = await page.request.get(`${API_BASE}/api/admin/wap/summary`);
    expect(res.status()).toBe(401);
    const body = await res.json();
    expect(body.error.code).toBe("UNAUTHENTICATED");
  });
});
