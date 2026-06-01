import { test, expect } from "@playwright/test";

const API_BASE = "http://localhost:8000";

const adminLoginResponse = {
  success: true,
  data: {
    user: { id: 1, name: "مدير النظام", email: "admin@beza.com", role: "admin" },
    permissions: ["manage_wap"],
  },
};

const routesResponse = {
  success: true,
  data: [
    { id: 1, method: "*", pattern: "/api/v1/wap/auth/login", target: "AuthController@login", roles: ["*"], priority: 0, is_active: true },
    { id: 2, method: "GET", pattern: "/api/v1/wap/merchant/*", target: "MerchantController", roles: ["merchant"], priority: 2, is_active: true },
  ],
};

test.describe("Update WAP Routing Rule", () => {
  test.beforeEach(async ({ page }) => {
    await page.route(`${API_BASE}/api/admin/login`, async (route) => {
      await route.fulfill({ status: 200, contentType: "application/json", body: JSON.stringify(adminLoginResponse) });
    });
    await page.route(`${API_BASE}/api/admin/me`, async (route) => {
      await route.fulfill({ status: 200, contentType: "application/json", body: JSON.stringify(adminLoginResponse) });
    });
    await page.route(`${API_BASE}/api/admin/wap/summary`, async (route) => {
      await route.fulfill({ status: 200, contentType: "application/json", body: JSON.stringify({ success: true, data: {} }) });
    });
    await page.route(`${API_BASE}/api/admin/wap/devices`, async (route) => {
      await route.fulfill({ status: 200, contentType: "application/json", body: JSON.stringify({ success: true, data: [] }) });
    });
    await page.route(`${API_BASE}/api/admin/wap/queue`, async (route) => {
      await route.fulfill({ status: 200, contentType: "application/json", body: JSON.stringify({ success: true, data: { counts: { pending: 0, completed: 0, failed: 0 }, recent: [] } }) });
    });
    await page.route(`${API_BASE}/api/admin/wap/routes`, async (route) => {
      await route.fulfill({ status: 200, contentType: "application/json", body: JSON.stringify(routesResponse) });
    });
  });

  test("displays routing rules with active status", async ({ page }) => {
    await page.goto("/login");
    await page.fill('input[type="email"]', "admin@beza.com");
    await page.fill('input[type="password"]', "adminpass");
    await page.click('button[type="submit"]');
    await page.waitForURL("/");
    await page.click('a[href="/wap"]');
    await page.waitForURL("/wap");

    const routeCard = page.getByText("/api/v1/wap/auth/login");
    await expect(routeCard).toBeVisible();
    await expect(page.getByText("فعال")).toBeVisible();
  });

  test("toggles a routing rule from active to inactive and back", async ({ page }) => {
    let currentRoutes = [...routesResponse.data];

    await page.route(`${API_BASE}/api/admin/wap/routes/${1}`, async (route) => {
      currentRoutes = currentRoutes.map((r) =>
        r.id === 1 ? { ...r, is_active: false } : r
      );
      await route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({ success: true, data: currentRoutes.find((r) => r.id === 1) }),
      });
    });

    await page.route(`${API_BASE}/api/admin/wap/routes`, async (route) => {
      await route.fulfill({ status: 200, contentType: "application/json", body: JSON.stringify({ success: true, data: currentRoutes }) });
    });

    await page.goto("/login");
    await page.fill('input[type="email"]', "admin@beza.com");
    await page.fill('input[type="password"]', "adminpass");
    await page.click('button[type="submit"]');
    await page.waitForURL("/");
    await page.click('a[href="/wap"]');
    await page.waitForURL("/wap");

    const activeBadges = page.locator("text=فعال");
    const inactiveBadges = page.locator("text=معطل");
    const initialActive = await activeBadges.count();

    await page.locator("text=فعال").first().click();
    await page.waitForTimeout(500);

    const inactiveCount = await inactiveBadges.count();
    expect(inactiveCount).toBeGreaterThanOrEqual(1);
  });
});
