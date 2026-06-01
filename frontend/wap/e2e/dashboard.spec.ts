import { test, expect } from "@playwright/test";

const API_BASE = "http://localhost:8000";

const userLoginResponse = {
  success: true,
  data: {
    user: { id: 1, name: "أحمد محمد", email: "ahmed@example.com", phone: "0933123456", role: "user" },
    wallets: [
      { id: 1, currency: "SYP", balance: 500000, status: "active" },
      { id: 2, currency: "USD", balance: 10000, status: "active" },
    ],
  },
};

const merchantLoginResponse = {
  success: true,
  data: {
    user: { id: 2, name: "متجر أحمد", email: "merchant@example.com", phone: "0933123456", role: "merchant" },
    wallets: [{ id: 3, currency: "SYP", balance: 2000000, status: "active" }],
  },
};

const agentLoginResponse = {
  success: true,
  data: {
    user: { id: 3, name: "وكيل محمد", email: "agent@example.com", phone: "0933123456", role: "agent" },
    wallets: [{ id: 4, currency: "SYP", balance: 500000, status: "active" }],
  },
};

async function loginAs(page: typeof userLoginResponse, role: string) {
  const responses: Record<string, typeof userLoginResponse> = { user: userLoginResponse, merchant: merchantLoginResponse, agent: agentLoginResponse };
  const resp = responses[role];
  await page.route(`${API_BASE}/api/v1/wap/auth/login`, async (route) => {
    await route.fulfill({ status: 200, contentType: "application/json", body: JSON.stringify(resp) });
  });
  await page.route(`${API_BASE}/api/v1/wap/auth/me`, async (route) => {
    await route.fulfill({ status: 200, contentType: "application/json", body: JSON.stringify(resp) });
  });
  await page.goto("/wap/login");
  await page.fill('input[type="email"]', `${role}@example.com`);
  await page.fill('input[type="password"]', "SecurePass1");
  await page.click('button[type="submit"]');
}

test.describe("User Dashboard", () => {
  test("shows user name and wallet balances", async ({ page }) => {
    await loginAs(page, "user");
    await page.waitForURL("**/wap/user");
    await expect(page.getByText("مرحباً، أحمد محمد")).toBeVisible();
    await expect(page.getByText("الرصيد")).toBeVisible();
    await expect(page.getByText("ليرة سورية")).toBeVisible();
    await expect(page.getByText("دولار أمريكي")).toBeVisible();
    await expect(page.getByText("5000.00")).toBeVisible();
    await expect(page.getByText("100.00")).toBeVisible();
  });

  test("shows quick action buttons", async ({ page }) => {
    await loginAs(page, "user");
    await page.waitForURL("**/wap/user");
    await expect(page.getByRole("paragraph").filter({ hasText: "تحويل" })).toBeVisible();
    await expect(page.getByRole("paragraph").filter({ hasText: "مسح QR" })).toBeVisible();
  });

  test("shows logout button", async ({ page }) => {
    await loginAs(page, "user");
    await page.waitForURL("**/wap/user");
    await expect(page.getByText("خروج")).toBeVisible();
  });

  test("shows BottomNav with 4 tabs", async ({ page }) => {
    await loginAs(page, "user");
    await page.waitForURL("**/wap/user");
    const nav = page.locator("nav");
    await expect(nav.getByText("الرئيسية")).toBeVisible();
    await expect(nav.getByText("تحويل")).toBeVisible();
    await expect(nav.getByText("السجل")).toBeVisible();
  });

  test("redirects unauthenticated user to login", async ({ page }) => {
    await page.goto("/wap/user");
    await page.waitForURL("**/wap/login");
  });
});

test.describe("Merchant Dashboard", () => {
  test("shows merchant name and sales summary", async ({ page }) => {
    await loginAs(page, "merchant");
    await page.waitForURL("**/wap/merchant");
    await expect(page.getByText("متجر أحمد")).toBeVisible();
    await expect(page.getByText("ملخص المبيعات")).toBeVisible();
    await expect(page.getByText("مبيعات اليوم")).toBeVisible();
    await expect(page.getByText("مبيعات الأسبوع")).toBeVisible();
    await expect(page.getByText("مبيعات الشهر")).toBeVisible();
  });

  test("shows pending settlement amount", async ({ page }) => {
    await loginAs(page, "merchant");
    await page.waitForURL("**/wap/merchant");
    await expect(page.getByText("التسوية المعلقة")).toBeVisible();
    await expect(page.locator(".text-center.text-2xl").filter({ hasText: "0 ل.س" })).toBeVisible();
  });

  test("redirects unauthenticated merchant to login", async ({ page }) => {
    await page.goto("/wap/merchant");
    await page.waitForURL("**/wap/login");
  });
});

test.describe("Agent Dashboard", () => {
  test("shows agent name and limits", async ({ page }) => {
    await loginAs(page, "agent");
    await page.waitForURL("**/wap/agent");
    await expect(page.getByText("وكيل محمد")).toBeVisible();
    await expect(page.getByText("حد الإيداع")).toBeVisible();
    await expect(page.getByText("حد السحب")).toBeVisible();
  });

  test("shows commission section", async ({ page }) => {
    await loginAs(page, "agent");
    await page.waitForURL("**/wap/agent");
    await expect(page.getByText("عمولة اليوم")).toBeVisible();
    await expect(page.getByText("٠ ل.س")).toBeVisible();
  });

  test("redirects unauthenticated agent to login", async ({ page }) => {
    await page.goto("/wap/agent");
    await page.waitForURL("**/wap/login");
  });
});

test.describe("BottomNav Navigation", () => {
  test("BottomNav renders for each role", async ({ page }) => {
    await loginAs(page, "user");
    await page.waitForURL("**/wap/user");
    const nav = page.locator("nav");
    await expect(nav.getByText("الرئيسية")).toBeVisible();
    await expect(nav.getByText("تحويل")).toBeVisible();
    await expect(nav.getByText("السجل")).toBeVisible();
  });
});
