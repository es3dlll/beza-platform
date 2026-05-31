# Operations Command Center

> **Purpose:** Single pane of glass for the Beza operations team — monitored 24/7 from the Damascus Ops Room.
> **URL:** `https://ops.beza.sy/command-center`
> **Status Page:** `https://status.beza.sy`

---

## Layout

```
┌──────────────────────────────────────────────────────────────────────────────┐
│  🟢 ALL SYSTEMS NORMAL  │  Beza Ops  │  Damascus  │  Thu 29 May 2026 14:30 │
├──────────────────────────────────────────────────────────────────────────────┤
│ ┌──────────────┐ ┌──────────────┐ ┌──────────────┐ ┌───────────────┐        │
│ │ Total Users  │ │ Active Today │ │ Pending KYC  │ │ Open Tickets  │        │
│ │ 12,847       │ │ 3,421        │ │ 47           │ │ 23            │        │
│ └──────────────┘ └──────────────┘ └──────────────┘ └───────────────┘        │
│ ┌──────────────┐ ┌──────────────┐ ┌──────────────┐ ┌───────────────┐        │
│ │ Wallet Bal   │ │ Txn Volume   │ │ Agent Float  │ │ Fraud Alerts  │        │
│ │ 8.2B SYP     │ │ 1.4B SYP     │ │ 1.2B SYP     │ │ 3 (P0: 1)    │        │
│ └──────────────┘ └──────────────┘ └──────────────┘ └───────────────┘        │
├──────────────────────────────────────────────────────────────────────────────┤
│ ┌──────────────────────────────────────────────────────────────────────────┐│
│ │                        REAL-TIME TRANSACTION FEED                        ││
│ │ 14:30:01  P2P  500,000 SYP  Ahmed→Sara        ✅ Completed   8ms        ││
│ │ 14:29:58  CASHOUT  50,000 SYP  Agent #142      ✅ Completed  12ms        ││
│ │ 14:29:55  BILL  Syriatel  25,000 SYP  Omar     ✅ Completed  15ms        ││
│ │ 14:29:50  FX  1,000 USD→SYP  Mariam            ✅ Completed  45ms        ││
│ │ 14:29:42  REMITTANCE  200 EUR  Germany→Ali     🟡 Pending AML  1.2s     ││
│ │ 14:29:38  P2P  75,000 SYP  Layla→Hassan        ✅ Completed  11ms        ││
│ │ 14:29:30  CASHIN  200,000 SYP  Agent #89       ✅ Completed   9ms        ││
│ │ 14:29:22  BILL  Damascus Water  8,500 SYP  Rana ✅ Completed  14ms        ││
│ │ 14:29:15  FX  500 EUR→SYP  Khaled               ✅ Completed  52ms        ││
│ │ 14:29:08  P2P  1,200,000 SYP  Nour→Tarek       ✅ Completed  10ms        ││
│ └──────────────────────────────────────────────────────────────────────────┘│
├──────────────────────────────────────────────────────────────────────────────┤
│ ┌──────────────┐ ┌──────────────┐ ┌──────────────┐ ┌───────────────┐        │
│ │ FINANCIAL    │ │ SETTLEMENT   │ │ COMPLIANCE   │ │ INFRASTRUCTURE│        │
│ │ - Wallet Bal │ │ - Pending: 0 │ │ - AML Queue  │ │ - API: 45ms   │        │
│ │ - Float Dist │ │ - Batches: 3 │ │   2 items    │ │ - Queue: 0    │        │
│ │ - Liquidity  │ │ - Exceptions │ │ - Sanctions  │ │ - MySQL: 2ms  │        │
│ │ - FX Pos     │ │   1 (manual) │ │   1 name hit │ │ - Redis: 1ms  │        │
│ └──────────────┘ └──────────────┘ └──────────────┘ └───────────────┘        │
└──────────────────────────────────────────────────────────────────────────────┘
```

**Refresh:** Auto-refresh every 5 seconds. Manual refresh available via `Ctrl+R` or the refresh button in the top-right corner.

---

## Section 1: Financial Health

### 1.1 Wallet Balance Overview

| Metric                    | Value              | 24h Change | 7d Trend | Alert |
| ------------------------- | ------------------ | ---------- | -------- | ----- |
| Total SYP Wallet Balances | 8,247,329,000 SYP  | ↑ +3.2%    | ↑ +12.8% | —     |
| Total USD Wallet Balances | 428,150 USD        | ↑ +1.1%    | ↑ +4.3%  | —     |
| Total EUR Wallet Balances | 12,380 EUR         | ↓ -0.4%    | ↑ +2.1%  | —     |
| Average Wallet Balance    | 641,800 SYP        | ↑ +1.8%    | ↑ +5.5%  | —     |
| Median Wallet Balance     | 142,000 SYP        | ↑ +0.7%    | ↑ +3.2%  | —     |
| Top 10% wallets           | 65.3% of total SYP | ±0.1%      | Stable   | —     |
| Bottom 50% wallets        | 8.1% of total SYP  | ±0.3%      | Growing  | —     |
| Zero-balance wallets      | 1,204 (9.4%)       | ↓ -2.1%    | ↓ -8.7%  | —     |
| Dormant (>90d)            | 847 (6.6%)         | ±0.0%      | ↑ +1.2%  | —     |

### 1.2 Agent Float Distribution

| Region         | Float (SYP) | Daily Cash-out (SYP) | Coverage Ratio | Agents | Status |
| -------------- | ----------- | -------------------- | -------------- | ------ | ------ |
| Damascus       | 450,000,000 | 320,000,000          | 140%           | 1,420  | 🟢     |
| Aleppo         | 280,000,000 | 250,000,000          | 112%           | 980    | 🟢     |
| Latakia        | 120,000,000 | 95,000,000           | 126%           | 410    | 🟢     |
| Homs           | 95,000,000  | 88,000,000           | 108%           | 340    | 🟢     |
| Deir Ezzor     | 42,000,000  | 48,000,000           | **88%**        | 110    | 🟡     |
| Hasakeh        | 38,000,000  | 42,000,000           | **90%**        | 95     | 🟡     |
| Idlib          | 28,000,000  | 37,000,000           | **76%**        | 72     | ⚠️     |
| Rural Damascus | 55,000,000  | 68,000,000           | **81%**        | 180    | 🟡     |
| Rural Aleppo   | 35,000,000  | 44,000,000           | **80%**        | 125    | 🟡     |

**Float Alerts:**

- Idlib: Float at 76% coverage — treasury notified at 12:15. Top-up of 25,000,000 SYP scheduled for tomorrow.
- Deir Ezzor: Coverage dropped below 90% — monitor closely. Next top-up window: 48h.

### 1.3 Liquidity Position

| Bank | Account Type    | Account       | Balance (SYP) | Reserve (SYP) | Available (SYP) |
| ---- | --------------- | ------------- | ------------- | ------------- | --------------- |
| BSO  | SYP Current     | BSO-1002-AC   | 2,500,000,000 | 500,000,000   | 2,000,000,000   |
| Bemo | SYP Current     | BEMO-2004-SYP | 1,800,000,000 | 400,000,000   | 1,400,000,000   |
| SIIB | SYP Current     | SIIB-3001-OP  | 1,200,000,000 | 300,000,000   | 900,000,000     |
| CBS  | Reserve Account | CBS-RES-001   | 5,000,000,000 | 5,000,000,000 | 0               |
| BSO  | USD Current     | BSO-1002-USD  | 425,000 USD   | 100,000 USD   | 325,000 USD     |
| SIIB | USD Current     | SIIB-3001-USD | 180,000 USD   | 50,000 USD    | 130,000 USD     |

**Minimum Operating Liquidity:** 800,000,000 SYP across all current accounts.
**Current Operating Liquidity:** 4,300,000,000 SYP — **healthy**.

### 1.4 FX Position

| Pair    | Rate (Mid)      | Spread  | Volume Today (SYP) | Position           |
| ------- | --------------- | ------- | ------------------ | ------------------ |
| USD/SYP | 12,850 / 12,950 | 100 SYP | 85,000,000         | +120,000 USD long  |
| EUR/SYP | 13,850 / 13,975 | 125 SYP | 22,000,000         | +15,000 EUR long   |
| TRY/SYP | 380 / 395       | 15 SYP  | 4,500,000          | −220,000 TRY short |

**Rate Source:** CBS daily fixing at 09:00 SYT + proprietary spread engine.
**Last CBS Update:** Today 09:03 — 63 minutes ago.

### 1.5 Daily Revenue Snapshot

| Revenue Stream          | Today              | MTD                 | vs Target |
| ----------------------- | ------------------ | ------------------- | --------- |
| Transfer Fees           | 12,450,000 SYP     | 285,000,000 SYP     | 94%       |
| FX Spread               | 4,200,000 SYP      | 98,000,000 SYP      | 102%      |
| Merchant MDR            | 3,800,000 SYP      | 82,000,000 SYP      | 88%       |
| Cash-out Fees           | 2,100,000 SYP      | 48,000,000 SYP      | 91%       |
| Bill Payment Commission | 1,500,000 SYP      | 35,000,000 SYP      | 97%       |
| **Total**               | **24,050,000 SYP** | **548,000,000 SYP** | **94%**   |

---

## Section 2: Settlement Dashboard

### 2.1 Settlement Status

| Batch          | Date       | Transactions | Gross (SYP) | Fees (SYP) | Net (SYP)   | Status        | ETA   |
| -------------- | ---------- | ------------ | ----------- | ---------- | ----------- | ------------- | ----- |
| Merchant D+1   | 2026-05-28 | 1,247        | 85,000,000  | 850,000    | 84,150,000  | ✅ Settled    | —     |
| Agent D+0      | 2026-05-29 | 892          | 45,000,000  | 450,000    | 44,550,000  | 🔄 Processing | 15:00 |
| Biller D+1     | 2026-05-28 | 3,421        | 215,000,000 | 2,150,000  | 212,850,000 | ✅ Settled    | —     |
| Remittance D+2 | 2026-05-27 | 210          | 38,000,000  | 380,000    | 37,620,000  | ✅ Settled    | —     |
| Payroll D+0    | 2026-05-29 | 45           | 12,500,000  | 0          | 12,500,000  | 🔄 Processing | 16:00 |
| Merchant D+0   | 2026-05-29 | 310          | 18,200,000  | 182,000    | 18,018,000  | ⏳ Scheduled  | 23:59 |
| Agent D+1      | 2026-05-28 | 1,560        | 76,000,000  | 760,000    | 75,240,000  | ✅ Settled    | —     |

### 2.2 Settlement Exceptions

| Exception          | Batch        | Amount (SYP) | Reason                               | Status           |
| ------------------ | ------------ | ------------ | ------------------------------------ | ---------------- |
| EXC-2026-05-29-001 | Agent D+0    | 450,000      | Bank BSO returned — account mismatch | 👤 Manual review |
| EXC-2026-05-28-003 | Merchant D+1 | 1,200,000    | Beneficiary name mismatch            | ✅ Resolved      |
| EXC-2026-05-28-002 | Agent D+1    | 230,000      | Duplicate reference ID               | ✅ Resolved      |

### 2.3 Unreconciled Items

| Type              | Count | Total Amount (SYP) | Age     |
| ----------------- | ----- | ------------------ | ------- |
| Unmatched credits | 12    | 2,400,000          | 2h — 3d |
| Unmatched debits  | 8     | 1,100,000          | 1h — 2d |
| Suspense account  | 3     | 850,000            | 4h — 1d |

---

## Section 3: Fraud & Risk

### 3.1 Real-Time Fraud Dashboard

| Alert Type               | Last 24h | P0  | P1  | P2  | Auto-Resolved |
| ------------------------ | -------- | --- | --- | --- | ------------- |
| High-Risk Transaction    | 12       | 1   | 4   | 7   | 3             |
| Velocity Alert           | 5        | 0   | 2   | 3   | 2             |
| New Device Login         | 47       | 0   | 0   | 47  | 40            |
| Unusual Location         | 8        | 0   | 1   | 7   | 5             |
| Agent Float Mismatch     | 2        | 0   | 1   | 1   | 0             |
| Account Takeover Attempt | 3        | 1   | 1   | 1   | 1             |
| SMS OTP Bypass Probe     | 1        | 1   | 0   | 0   | 0             |
| Excessive Login Failures | 14       | 0   | 0   | 14  | 12            |

### 3.2 Open Fraud Cases

| Case ID           | Type                    | Amount (SYP) | User       | Created   | Status                     | Owner    |
| ----------------- | ----------------------- | ------------ | ---------- | --------- | -------------------------- | -------- |
| FR-2026-05-29-001 | Account Takeover        | 2,500,000    | User #4812 | 11:45     | 🔴 Investigating           | Ahmad R. |
| FR-2026-05-29-002 | Velocity — 15 txns/5min | 1,800,000    | User #9201 | 12:20     | 🟡 Reviewing               | Layla M. |
| FR-2026-05-28-015 | Agent Float Mismatch    | 340,000      | Agent #212 | Yesterday | 🟢 Escalated to ops        | —        |
| FR-2026-05-28-012 | Suspicious Login        | 0            | User #7345 | Yesterday | ✅ Closed — False positive | —        |

### 3.3 Fraud Rate Metrics

| Metric                 | Today  | 7d Avg | 30d Avg | Threshold  | Status |
| ---------------------- | ------ | ------ | ------- | ---------- | ------ |
| Fraud Rate (% of txns) | 0.08%  | 0.12%  | 0.10%   | > 0.50% P0 | 🟢     |
| False Positive Rate    | 4.2%   | 5.1%   | 4.8%    | > 10% P2   | 🟢     |
| Avg Detection Time     | 1.8s   | 2.1s   | 1.9s    | > 5s P1    | 🟢     |
| Manual Review Time     | 4.2min | 5.0min | 6.1min  | > 15min P2 | 🟢     |
| Chargeback Ratio       | 0.01%  | 0.02%  | 0.03%   | > 0.5% P0  | 🟢     |

---

## Section 4: Compliance

### 4.1 Queue Overview

| Queue               | Count | Oldest Item  | SLA Target | SLA Met | Actions Needed             |
| ------------------- | ----- | ------------ | ---------- | ------- | -------------------------- |
| Pending KYC         | 47    | 3h 12min ago | < 48h      | 100%    | 12 need document re-upload |
| AML Review          | 2     | 1h 05min ago | < 2h       | —       | Awaiting officer review    |
| Sanctions Name Hits | 1     | 31min ago    | < 1h       | —       | Officer assigned           |
| Pending SARs        | 0     | —            | < 24h      | —       | —                          |
| Periodic Review Due | 128   | —            | < 30d      | 96%     | 5 overdue                  |
| PEP Screening       | 4     | 45min ago    | < 4h       | —       | In progress                |

### 4.2 KYC Breakdown

| KYC Tier            | Registered | Completed | % Complete | Pending               |
| ------------------- | ---------- | --------- | ---------- | --------------------- |
| Tier 0 (Unverified) | 2,841      | —         | —          | —                     |
| Tier 1 (Basic)      | 6,210      | 5,823     | 93.8%      | 387                   |
| Tier 2 (Full)       | 3,796      | 3,612     | 95.2%      | 184                   |
| **Total**           | **12,847** | **9,435** | **73.4%**  | **47 pending review** |

### 4.3 Pending KYC Details (Oldest 5)

| User ID | Name          | Tier | Submitted | Age      | Document Type    | Issue                   |
| ------- | ------------- | ---- | --------- | -------- | ---------------- | ----------------------- |
| #11842  | Ali Hassan    | T1   | 11:18     | 3h 12min | National ID      | Blurry image            |
| #11901  | Fatima Khalil | T2   | 11:35     | 2h 55min | Passport         | Signature missing       |
| #12004  | Omar Shehadeh | T1   | 11:50     | 2h 40min | National ID      | Expired (2024)          |
| #11897  | Nour Al-Deen  | T2   | 12:05     | 2h 25min | Electricity Bill | Address mismatch        |
| #12055  | Rana Jaber    | T1   | 12:20     | 2h 10min | National ID      | Awaiting liveness check |

### 4.4 AML/Sanctions Screening

| Hit ID            | Name                | Rule                         | Score | Amount (SYP) | Status            |
| ----------------- | ------------------- | ---------------------------- | ----- | ------------ | ----------------- |
| AML-2026-05-29-01 | Mohammed Al-Ahmad   | High-Value Txn > 5M          | 72    | 5,200,000    | 🟡 Escalated      |
| AML-2026-05-29-02 | Unknown Beneficiary | Remittance — incomplete data | 55    | 2,000 EUR    | 🟡 Escalated      |
| SDN-2026-05-29-01 | Abdul Rahman M.     | Fuzzy name match (85%)       | 65    | 0            | 🟡 Pending review |

---

## Section 5: Infrastructure

### 5.1 Service Health

| Component           | Status | P99 Latency | Error Rate | Uptime (30d) | CPU | Memory |
| ------------------- | ------ | ----------- | ---------- | ------------ | --- | ------ |
| API Gateway (Nginx) | 🟢     | 45ms        | 0.02%      | 99.97%       | 22% | 34%    |
| MySQL Primary       | 🟢     | 2ms         | 0.00%      | 99.99%       | 18% | 41%    |
| MySQL Replica       | 🟢     | 3ms         | 0.00%      | 99.99%       | 12% | 38%    |
| Redis Cache         | 🟢     | 1ms         | 0.00%      | 99.99%       | 8%  | 22%    |
| RabbitMQ            | 🟢     | 5ms         | 0.01%      | 99.95%       | 15% | 28%    |
| Transfer Service    | 🟢     | 52ms        | 0.05%      | 99.98%       | 31% | 45%    |
| Auth Service        | 🟢     | 35ms        | 0.03%      | 99.99%       | 14% | 26%    |
| Agent Service       | 🟢     | 48ms        | 0.04%      | 99.95%       | 20% | 32%    |
| Wallet Service      | 🟢     | 41ms        | 0.01%      | 99.98%       | 25% | 38%    |
| Settlement Engine   | 🟢     | 120ms       | 0.10%      | 99.92%       | 35% | 52%    |
| SMS Gateway         | 🟢     | 120ms       | 0.50%      | 99.90%       | 10% | 18%    |
| USSD Gateway        | 🟢     | 80ms        | 0.15%      | 99.95%       | 8%  | 15%    |
| CBS Rate Feed       | 🟢     | 350ms       | 1.20%      | 98.50%       | 5%  | 12%    |
| Email Service       | 🟢     | 1.2s        | 0.80%      | 99.80%       | 7%  | 14%    |
| Elasticsearch       | 🟢     | 18ms        | 0.00%      | 99.97%       | 28% | 55%    |
| Grafana/Loki        | 🟢     | 22ms        | 0.01%      | 99.95%       | 16% | 30%    |

### 5.2 Queue Depth

| Queue               | Current Depth | Consumer Lag | Max Depth (24h) | Status |
| ------------------- | ------------- | ------------ | --------------- | ------ |
| transaction.process | 0             | 0ms          | 45              | 🟢     |
| settlement.execute  | 0             | 0ms          | 12              | 🟢     |
| notification.send   | 3             | 5ms          | 210             | 🟢     |
| sms.send            | 12            | 8ms          | 850             | 🟢     |
| email.send          | 5             | 3ms          | 180             | 🟢     |
| fraud.evaluate      | 0             | 0ms          | 28              | 🟢     |
| audit.log           | 0             | 0ms          | 5,200           | 🟢     |
| compliance.screen   | 2             | 12ms         | 15              | 🟢     |

### 5.3 Database Health

| Metric             | Primary | Replica 1 | Replica 2 |
| ------------------ | ------- | --------- | --------- |
| Connections        | 42/200  | 18/200    | 15/200    |
| Queries/sec        | 1,240   | 890       | 810       |
| Replica Lag        | —       | 0.4s      | 0.6s      |
| InnoDB Buffer Pool | 72%     | 65%       | 61%       |
| Slow Queries (>1s) | 0/min   | 0/min     | 0/min     |
| Deadlocks          | 0/hr    | —         | —         |

### 5.4 External Dependencies

| Dependency        | Status | Last Check | Notes               |
| ----------------- | ------ | ---------- | ------------------- |
| CBS Rate Feed     | 🟢     | 14:29:58   | 350ms, 98.5% uptime |
| Syriatel API      | 🟢     | 14:29:59   | Bill payment, 180ms |
| MTN API           | 🟢     | 14:29:59   | Bill payment, 195ms |
| BSO Banking API   | 🟢     | 14:29:57   | Settlement, 420ms   |
| Bemo Banking API  | 🟢     | 14:29:57   | Settlement, 380ms   |
| SIIB Banking API  | 🟢     | 14:29:56   | Settlement, 510ms   |
| SWIFT Gateway     | 🟢     | 14:29:55   | Remittance, 1.8s    |
| Western Union API | 🟢     | 14:29:54   | Remittance, 890ms   |

---

## Section 6: Alerts & Notifications

### 6.1 Active Alerts

| Alert                          | Severity | Time Triggered | Duration | Status             | Acknowledged By |
| ------------------------------ | -------- | -------------- | -------- | ------------------ | --------------- |
| Agent Float Low — Idlib        | P2       | 12:15:00       | 2h 15min | 🔔 Acknowledged    | Ahmad R.        |
| CBS Rate Feed 15min stale      | P2       | 12:20:00       | 2h 10min | ✅ Resolved 12:22  | —               |
| AML Queue > 5 items            | P1       | 11:30:00       | —        | 🔄 Auto-clearing   | System          |
| High-Risk Txn — User #4812     | P0       | 13:10:00       | 1h 20min | 🔴 Investigating   | Ahmad R.        |
| Settlement Exception — EXC-001 | P2       | 08:45:00       | 5h 45min | 🔔 Awaiting action | —               |
| Database Replica Lag > 10s     | P1       | 14:02:00       | —        | ✅ Resolved 14:04  | System          |

### 6.2 Alert Thresholds

| Alert Name             | Threshold               | Severity | Auto-Resolution        | Action Required                  |
| ---------------------- | ----------------------- | -------- | ---------------------- | -------------------------------- |
| CBS Feed Stale         | > 15 min without update | P2       | CBS feed restored      | Switch to manual rate entry      |
| Agent Float Low        | Float < 90% coverage    | P2       | Float replenished      | Notify treasury to top up        |
| Agent Float Critical   | Float < 75% coverage    | P1       | Immediate top-up       | Emergency treasury dispatch      |
| AML Queue Buildup      | > 5 pending reviews     | P1       | Queue drops below 5    | Surge compliance team activated  |
| AML Queue Critical     | > 15 pending reviews    | P0       | —                      | Comply incident response         |
| Fraud Rate High        | > 0.25% of txns flagged | P1       | Rate drops below 0.25% | Fraud team review                |
| Fraud Rate Critical    | > 0.50% of txns flagged | P0       | —                      | Emergency fraud meeting          |
| API P99 High           | > 2s for core services  | P1       | Latency drops below 2s | Check DB, queue, or external dep |
| API P99 Critical       | > 5s for core services  | P0       | —                      | Incident response                |
| Queue Lag              | > 1,000 messages        | P2       | Queue drains below 1K  | Scale workers                    |
| Queue Critical         | > 10,000 messages       | P1       | —                      | Auto-scale triggered             |
| MySQL Replica Lag      | > 10s behind primary    | P1       | Lag drops below 5s     | Investigate replication          |
| MySQL Replica Critical | > 60s behind primary    | P0       | —                      | Failover consideration           |
| Settlement Failure     | Any batch fails         | P1       | Manual retry succeeds  | Treasury team notified           |
| Login Error Spike      | Error rate > 10%        | P1       | Rate drops below 5%    | Auth team notified               |
| SMS Gateway Down       | 0 deliveries > 5 min    | P2       | Delivery resumes       | Switch to USSD fallback          |

### 6.3 Notification Channels

| Severity | Channel                           | Recipients                    | Response SLA |
| -------- | --------------------------------- | ----------------------------- | ------------ |
| P0       | Phone call + SMS + Slack @channel | Ops Manager, Engineer on-call | 5 min        |
| P1       | SMS + Slack @here                 | Ops Manager, Team lead        | 15 min       |
| P2       | Slack #alerts-ops                 | Ops team                      | 1 hr         |
| P3       | Email + Slack #daily-digest       | All ops                       | 24 hr        |

---

## Section 7: Operations Team

### 7.1 On-Call Roster (Week of 26 May — 1 Jun 2026)

| Role                 | Name      | Contact          | Shift       |
| -------------------- | --------- | ---------------- | ----------- |
| Ops Lead             | Ahmad R.  | +963-11-XXXX-142 | 08:00–20:00 |
| Ops Lead             | Maya K.   | +963-11-XXXX-143 | 20:00–08:00 |
| Compliance Officer   | Layla M.  | +963-11-XXXX-201 | 09:00–18:00 |
| Treasury Analyst     | Hassan N. | +963-11-XXXX-301 | 08:00–17:00 |
| Fraud Analyst        | Rami S.   | +963-11-XXXX-401 | 08:00–20:00 |
| Engineer (Primary)   | Tarek H.  | +963-11-XXXX-501 | 09:00–21:00 |
| Engineer (Secondary) | Dima A.   | +963-11-XXXX-502 | 21:00–09:00 |

### 7.2 Roles & Access

| Role               | Can View                    | Can Act                                            |
| ------------------ | --------------------------- | -------------------------------------------------- |
| Ops Viewer         | All dashboards              | Nothing                                            |
| Ops Responder      | All                         | Acknowledge alerts, escalate, comment              |
| Ops Manager        | All + financial details     | Trigger settlement, override rates, acknowledge P0 |
| Compliance Viewer  | Compliance, Fraud tabs only | Nothing                                            |
| Compliance Officer | Compliance, Fraud           | Review KYC, clear AML/Sanctions, file SARs         |
| Treasury Analyst   | Financial, Settlement       | Initiate bank transfers, manage float top-ups      |
| Fraud Analyst      | Fraud, Transaction feed     | Freeze accounts, block devices, escalate           |
| Read-Only API      | Public dashboard            | Nothing                                            |
| Admin              | ALL                         | ALL                                                |

---

## Section 8: Runbooks & Quick Actions

### 8.1 Quick Action Buttons

| Button               | Action                          | Role Required  |
| -------------------- | ------------------------------- | -------------- |
| 🔄 Refresh Rates     | Force CBS rate refresh          | Ops Manager    |
| 📊 Generate Report   | Download 24h ops summary PDF    | Ops Viewer+    |
| 📋 Settlement Report | Download pending settlement CSV | Treasury       |
| 🔔 Test Alert        | Send test P2 notification       | Ops Manager    |
| 🛑 Freeze User       | Immediately freeze user account | Fraud Analyst+ |
| 📤 Top-Up Float      | Initiate agent float top-up     | Treasury       |

### 8.2 Common Runbooks

| Runbook                       | Link              | Avg Resolution Time |
| ----------------------------- | ----------------- | ------------------- |
| Agent float top-up procedure  | `link-to-runbook` | 30 min              |
| CBS rate feed failure         | `link-to-runbook` | 15 min              |
| Settlement exception handling | `link-to-runbook` | 45 min              |
| User account freeze & review  | `link-to-runbook` | 10 min              |
| Fraud alert investigation     | `link-to-runbook` | 20 min              |
| KYC document re-request       | `link-to-runbook` | 5 min               |
| AML escalation to compliance  | `link-to-runbook` | 15 min              |
| Database replica failover     | `link-to-runbook` | 25 min              |
| Queue worker scale-up         | `link-to-runbook` | 10 min              |
| SMS gateway failover to USSD  | `link-to-runbook` | 5 min               |

---

## Section 9: Daily Operations Summary

### 9.1 Today's Highlights — 29 May 2026

| Category              | Detail                         |
| --------------------- | ------------------------------ |
| Total Transactions    | 24,108                         |
| Total Volume          | 1,415,000,000 SYP              |
| New Registrations     | 142                            |
| KYC Submissions       | 89                             |
| KYC Approved          | 76                             |
| New Agents Onboarded  | 4                              |
| Fraud Cases Opened    | 3                              |
| Fraud Cases Closed    | 1                              |
| Settlement Batches    | 7 (5 completed, 2 in progress) |
| Active Alerts         | 6 (1 P0, 2 P1, 3 P2)           |
| Top Performing Region | Damascus — 38% of volume       |
| Slowest Region        | Idlib — 2.1% of volume         |

### 9.2 Yesterday vs Today Comparison

| Metric          | Yesterday     | Today (so far) | Change |
| --------------- | ------------- | -------------- | ------ |
| Transactions    | 25,401        | 24,108         | −5.1%  |
| Volume (SYP)    | 1,382,000,000 | 1,415,000,000  | +2.4%  |
| New Users       | 128           | 142            | +10.9% |
| Peak TPS        | 48            | 52             | +8.3%  |
| Avg TPS         | 12            | 11             | −8.3%  |
| Fraud Rate      | 0.11%         | 0.08%          | −27.3% |
| API P99         | 52ms          | 45ms           | −13.5% |
| Support Tickets | 18            | 14             | −22.2% |

---

## Section 10: Export & Reporting

### 10.1 Available Reports

| Report                    | Format   | Schedule           | Retention |
| ------------------------- | -------- | ------------------ | --------- |
| Daily Ops Summary         | PDF      | Daily 23:59        | 90 days   |
| Weekly Financial Report   | PDF/XLSX | Every Sunday 23:59 | 1 year    |
| Monthly Settlement Report | XLSX     | 1st of month       | 7 years   |
| Fraud Analysis Report     | PDF      | Weekly Monday      | 1 year    |
| Compliance Quarterly      | PDF      | Quarter end        | 7 years   |
| Agent Network Health      | PDF      | Weekly Friday      | 1 year    |
| Custom Query              | CSV      | On demand          | 24h       |

### 10.2 API Endpoints

| Endpoint                              | Description               | Auth Required      |
| ------------------------------------- | ------------------------- | ------------------ |
| `GET /api/v1/ops/summary`             | Dashboard summary metrics | Ops API key        |
| `GET /api/v1/ops/alerts`              | Active alerts list        | Ops API key        |
| `GET /api/v1/ops/transactions`        | Recent transaction feed   | Ops API key        |
| `GET /api/v1/ops/settlements`         | Settlement batch status   | Treasury API key   |
| `GET /api/v1/ops/financial/liquidity` | Bank balances & liquidity | Treasury API key   |
| `GET /api/v1/ops/financial/float`     | Agent float by region     | Ops API key        |
| `GET /api/v1/ops/compliance/kyc`      | KYC queue                 | Compliance API key |
| `GET /api/v1/ops/compliance/aml`      | AML queue                 | Compliance API key |
| `GET /api/v1/ops/fraud/cases`         | Active fraud cases        | Fraud API key      |
| `GET /api/v1/ops/infrastructure`      | Service health & metrics  | Admin API key      |
| `GET /api/v1/ops/reports/{type}`      | Download report           | Role-based         |

---

## Appendix A: Glossary

| Term           | Definition                                                         |
| -------------- | ------------------------------------------------------------------ |
| Float          | Cash balance held by agents for cash-out operations                |
| Coverage Ratio | Float / Daily cash-out — ratio above 100% means sufficient float   |
| CBS            | Central Bank of Syria — provides official FX rates                 |
| BSO            | Banque Bemo Saudi Fransi — commercial bank partner                 |
| SIIB           | Syrian International Islamic Bank — commercial bank partner        |
| Bemo           | Banque Bemo — commercial bank partner                              |
| D+0 / D+1      | Settlement timeline: same day or next day                          |
| P0–P3          | Priority levels: Critical (P0) to Low (P3)                         |
| SAR            | Suspicious Activity Report — filed with CBS Financial Intelligence |
| KYC            | Know Your Customer — identity verification process                 |
| AML            | Anti-Money Laundering — transaction screening process              |
| TPS            | Transactions Per Second                                            |
| SYT            | Syria Time (UTC+3)                                                 |
| SYP            | Syrian Pound — official currency                                   |

---

## Appendix B: Change Log

| Date       | Change                             | Author          |
| ---------- | ---------------------------------- | --------------- |
| 2026-05-29 | Initial version                    | Operations Team |
| 2026-05-29 | Added agent float coverage alerts  | Treasury        |
| 2026-05-29 | Added fraud rate metrics section   | Fraud Team      |
| 2026-05-29 | Added daily ops summary comparison | Product         |
