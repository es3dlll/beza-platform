import http from "k6/http";
import { check, sleep, group } from "k6";
import { Rate, Trend } from "k6/metrics";

const BASE_URL = __ENV.API_URL || "http://localhost:8000";

const loginFailureRate = new Rate("login_failures");
const transferFailureRate = new Rate("transfer_failures");
const loginDuration = new Trend("login_duration");
const transferDuration = new Trend("transfer_duration");
const balanceDuration = new Trend("balance_duration");

export const options = {
  stages: [
    { duration: "30s", target: 10 },
    { duration: "30s", target: 25 },
    { duration: "30s", target: 50 },
    { duration: "30s", target: 100 },
    { duration: "30s", target: 0 },
  ],
  thresholds: {
    login_failures: ["rate<0.05"],
    transfer_failures: ["rate<0.05"],
    http_req_duration: ["p(95)<2000"],
    login_duration: ["p(95)<1500"],
    transfer_duration: ["p(95)<3000"],
    balance_duration: ["p(95)<1000"],
  },
};

export default function () {
  const credentials = {
    email: "performance@test.com",
    password: "PerformancePass1",
  };

  group("WAP Authentication", () => {
    const loginRes = http.post(`${BASE_URL}/api/v1/wap/auth/login`, JSON.stringify(credentials), {
      headers: { "Content-Type": "application/json", Accept: "application/json" },
    });

    loginDuration.add(loginRes.timings.duration);
    loginFailureRate.add(loginRes.status !== 200);

    check(loginRes, {
      "login status is 200": (r) => r.status === 200,
      "login response has user": (r) => r.json("data.user") !== undefined,
      "login response has wallets": (r) => r.json("data.wallets") !== undefined,
    });

    if (loginRes.status === 200) {
      const cookies = loginRes.cookies;
      const tokenCookie = cookies["wap_token"] ? cookies["wap_token"][0].value : null;

      if (tokenCookie) {
        const cookie jar = http.cookieJar();
        cookieJar.set(BASE_URL, "wap_token", tokenCookie);

        group("WAP Wallet", () => {
          const balanceRes = http.get(`${BASE_URL}/api/v1/wap/wallet/balance`, {
            headers: { Accept: "application/json" },
          });

          balanceDuration.add(balanceRes.timings.duration);

          check(balanceRes, {
            "balance status is 200": (r) => r.status === 200,
            "balance has wallets": (r) => r.json("data") !== undefined,
          });

          sleep(1);

          const transferRes = http.post(
            `${BASE_URL}/api/v1/wap/wallet/transfer`,
            JSON.stringify({
              receiver_phone: "0933987654",
              amount: 1000,
              currency: "SYP",
              idempotency_key: `${__VU}-${__ITER}-${Date.now()}`,
              note: "k6 test transfer",
            }),
            { headers: { "Content-Type": "application/json", Accept: "application/json" } }
          );

          transferDuration.add(transferRes.timings.duration);
          transferFailureRate.add(transferRes.status !== 200 && transferRes.status !== 422);

          check(transferRes, {
            "transfer is accepted or rejected": (r) => r.status === 200 || r.status === 422,
            "transfer has success field": (r) => r.json("success") !== undefined,
          });
        });

        group("WAP Auth Me", () => {
          const meRes = http.get(`${BASE_URL}/api/v1/wap/auth/me`, {
            headers: { Accept: "application/json" },
          });

          check(meRes, {
            "me status is 200": (r) => r.status === 200,
            "me returns user data": (r) => r.json("data.user") !== undefined,
          });
        });

        group("WAP Logout", () => {
          const logoutRes = http.post(`${BASE_URL}/api/v1/wap/auth/logout`, null, {
            headers: { Accept: "application/json" },
          });

          check(logoutRes, {
            "logout status is 200": (r) => r.status === 200,
          });
        });
      }
    }
  });

  sleep(1);
}
