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

test.describe("WAP Transfer", () => {
  test.beforeEach(async ({ page }) => {
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
  });

  test("renders transfer form with all fields", async ({ page }) => {
    await page.goto("/wap/user/transfer");
    await expect(page.getByText("تحويل سريع")).toBeVisible();
    await expect(page.getByText("رقم المستلم")).toBeVisible();
    await expect(page.getByText("المبلغ (ل.س)")).toBeVisible();
    await expect(page.getByText("ملاحظة (اختياري)")).toBeVisible();
    await expect(page.getByRole("button", { name: "تحويل" })).toBeVisible();
  });

  test("shows success message on completed transfer", async ({ page }) => {
    await page.route(`${API_BASE}/api/v1/wap/wallet/transfer`, async (route) => {
      const body = JSON.parse(route.request().postData() || "{}");
      await route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({
          success: true,
          data: { id: 1, amount: body.amount, currency: "SYP", status: "completed", created_at: new Date().toISOString() },
        }),
      });
    });

    await page.goto("/wap/user/transfer");
    await page.fill('input[placeholder="09xxxxxxxx"]', "0933987654");
    await page.fill('input[type="number"]', "500");
    await page.click('button[type="submit"]');
    await expect(page.getByText("✅ تم التحويل بنجاح")).toBeVisible();
  });

  test("queues transfer offline when API is unreachable", async ({ page }) => {
    await page.route(`${API_BASE}/api/v1/wap/wallet/transfer`, (route) => route.abort("connectionrefused"));

    await page.goto("/wap/user/transfer");
    await page.fill('input[placeholder="09xxxxxxxx"]', "0933987654");
    await page.fill('input[type="number"]', "500");
    await page.click('button[type="submit"]');
    await expect(page.getByText("📤 تم حفظ التحويل")).toBeVisible();
  });

  test("shows error message on failed transfer", async ({ page }) => {
    await page.route(`${API_BASE}/api/v1/wap/wallet/transfer`, async (route) => {
      await route.fulfill({
        status: 422,
        contentType: "application/json",
        body: JSON.stringify({ success: false, error: { code: "INSUFFICIENT_BALANCE", message: "الرصيد غير كافٍ" } }),
      });
    });

    await page.goto("/wap/user/transfer");
    await page.fill('input[placeholder="09xxxxxxxx"]', "0933987654");
    await page.fill('input[type="number"]', "99999999");
    await page.click('button[type="submit"]');
    await expect(page.getByText("الرصيد غير كافٍ")).toBeVisible();
  });

  test("disables submit button while loading", async ({ page }) => {
    await page.route(`${API_BASE}/api/v1/wap/wallet/transfer`, async () => {
      await new Promise((r) => setTimeout(r, 1000));
    });

    await page.goto("/wap/user/transfer");
    await page.fill('input[placeholder="09xxxxxxxx"]', "0933987654");
    await page.fill('input[type="number"]', "500");
    await page.click('button[type="submit"]');
    await expect(page.getByRole("button", { name: "جاري..." })).toBeDisabled();
  });
});
