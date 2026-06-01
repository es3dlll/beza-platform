import { test, expect } from "@playwright/test";

const API_BASE = "http://localhost:8000";
const loginResponse = {
  success: true,
  data: {
    user: { id: 1, name: "أحمد محمد", email: "ahmed@example.com", phone: "0933123456", role: "user" },
    wallets: [
      { id: 1, currency: "SYP", balance: 500000, status: "active" },
      { id: 2, currency: "USD", balance: 10000, status: "active" },
    ],
  },
};

test.describe("WAP Login", () => {
  test("shows login form with Arabic labels", async ({ page }) => {
    await page.goto("/wap/login");
    await expect(page.getByText("بيزا")).toBeVisible();
    await expect(page.getByText("محفظتك الرقمية")).toBeVisible();
    await expect(page.getByText("البريد الإلكتروني")).toBeVisible();
    await expect(page.getByText("كلمة المرور")).toBeVisible();
    await expect(page.getByRole("button", { name: "تسجيل الدخول" })).toBeVisible();
  });

  test("redirects user to /wap/user on successful login", async ({ page }) => {
    await page.route(`${API_BASE}/api/v1/wap/auth/login`, async (route) => {
      await route.fulfill({ status: 200, contentType: "application/json", body: JSON.stringify(loginResponse) });
    });
    await page.route(`${API_BASE}/api/v1/wap/auth/me`, async (route) => {
      await route.fulfill({ status: 200, contentType: "application/json", body: JSON.stringify(loginResponse) });
    });

    await page.goto("/wap/login");
    await page.fill('input[type="email"]', "ahmed@example.com");
    await page.fill('input[type="password"]', "SecurePass1");
    await page.click('button[type="submit"]');
    await page.waitForURL("**/wap/user");
    await expect(page.getByText("مرحباً، أحمد محمد")).toBeVisible();
  });

  test("shows error on invalid credentials", async ({ page }) => {
    await page.route(`${API_BASE}/api/v1/wap/auth/login`, async (route) => {
      await route.fulfill({
        status: 401,
        contentType: "application/json",
        body: JSON.stringify({ success: false, error: { code: "INVALID_CREDENTIALS", message: "بيانات الدخول غير صحيحة" } }),
      });
    });

    await page.goto("/wap/login");
    await page.fill('input[type="email"]', "wrong@example.com");
    await page.fill('input[type="password"]', "wrong");
    await page.click('button[type="submit"]');
    await expect(page.getByText("بيانات الدخول غير صحيحة")).toBeVisible();
  });

  test("redirects merchant to /wap/merchant", async ({ page }) => {
    const merchantResponse = { ...loginResponse, data: { ...loginResponse.data, user: { ...loginResponse.data.user, role: "merchant", name: "متجر أحمد" } } };
    await page.route(`${API_BASE}/api/v1/wap/auth/login`, async (route) => {
      await route.fulfill({ status: 200, contentType: "application/json", body: JSON.stringify(merchantResponse) });
    });
    await page.route(`${API_BASE}/api/v1/wap/auth/me`, async (route) => {
      await route.fulfill({ status: 200, contentType: "application/json", body: JSON.stringify(merchantResponse) });
    });

    await page.goto("/wap/login");
    await page.fill('input[type="email"]', "merchant@example.com");
    await page.fill('input[type="password"]', "SecurePass1");
    await page.click('button[type="submit"]');
    await page.waitForURL("**/wap/merchant");
    await expect(page.getByText("متجر أحمد")).toBeVisible();
  });

  test("redirects agent to /wap/agent", async ({ page }) => {
    const agentResponse = { ...loginResponse, data: { ...loginResponse.data, user: { ...loginResponse.data.user, role: "agent", name: "وكيل محمد" } } };
    await page.route(`${API_BASE}/api/v1/wap/auth/login`, async (route) => {
      await route.fulfill({ status: 200, contentType: "application/json", body: JSON.stringify(agentResponse) });
    });
    await page.route(`${API_BASE}/api/v1/wap/auth/me`, async (route) => {
      await route.fulfill({ status: 200, contentType: "application/json", body: JSON.stringify(agentResponse) });
    });

    await page.goto("/wap/login");
    await page.fill('input[type="email"]', "agent@example.com");
    await page.fill('input[type="password"]', "SecurePass1");
    await page.click('button[type="submit"]');
    await page.waitForURL("**/wap/agent");
    await expect(page.getByText("وكيل محمد")).toBeVisible();
  });

  test("shows loading state during submission", async ({ page }) => {
    await page.route(`${API_BASE}/api/v1/wap/auth/login`, async () => {
      await new Promise((r) => setTimeout(r, 1000));
    });

    await page.goto("/wap/login");
    await page.fill('input[type="email"]', "ahmed@example.com");
    await page.fill('input[type="password"]', "SecurePass1");
    await page.click('button[type="submit"]');
    await expect(page.getByText("جاري تسجيل الدخول...")).toBeVisible();
  });
});
