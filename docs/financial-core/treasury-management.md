# Treasury Operations — Cash & Liquidity Management

## Functions

### 1. Cash Forecasting

Predict daily and weekly cash position across all Beza wallets (customer, agent, merchant, settlement, reserve).

**Model Architecture:**

```
Input: Historical txn data (90 days), Agent cash volumes, Bill payments, Payroll cycles
Model: Time-series (Prophet/ARIMA)
Output: 7-day cash position forecast ± 15% accuracy
Update: Daily at 06:00
```

**Input Data Sources:**

- `ledger.transactions` — 90-day history of all wallet transactions
- `agent.daily_float_usage` — aggregate float consumption per agent
- `biller.payment_forecasts` — expected bill payments (Sawa, SyriaTel, MTN, Damascus Water, Public Electricity)
- `payroll.schedules` — known payroll dates for enterprise clients (e.g., Ministry of Finance pays 25th of each month)
- `merchant.settlement_projections` — expected merchant settlement volumes
- `fx.forward_positions` — outstanding FX forward contracts

**Outputs:**

```sql
-- Forecast table
CREATE TABLE treasury.cash_forecast (
    forecast_date DATE NOT NULL,
    wallet_type VARCHAR(50) NOT NULL,
    opening_balance NUMERIC(18,3) NOT NULL,
    projected_inflows NUMERIC(18,3),
    projected_outflows NUMERIC(18,3),
    projected_closing NUMERIC(18,3),
    confidence_lower NUMERIC(18,3),
    confidence_upper NUMERIC(18,3),
    model_version VARCHAR(50),
    generated_at TIMESTAMPTZ DEFAULT NOW(),
    PRIMARY KEY (forecast_date, wallet_type)
);
```

### 2. Vault Management

Manage Beza's cash reserves across Syrian banks.

| Bank                                         | Account Type     | Current Balance (SYP) | Purpose                |
| -------------------------------------------- | ---------------- | --------------------- | ---------------------- |
| **Bank of Syria and Overseas (BSO)**         | Current (SYP)    | 850,000,000           | Agent float funding    |
| **Bemo Saudi Fransi**                        | Current (SYP)    | 1,200,000,000         | Merchant settlements   |
| **Banque Bemo Saudi Fransi**                 | USD Current      | $2,500,000            | FX reserves            |
| **Syrian International Islamic Bank (SIIB)** | Current (SYP)    | 600,000,000           | Payroll disbursements  |
| **Commercial Bank of Syria (CBS)**           | Settlement (SYP) | 3,200,000,000         | CBS settlement account |
| **Commercial Bank of Syria (CBS)**           | Reserve (SYP)    | 5,000,000,000         | Statutory reserve      |

**Cash Movement Protocol:**

- Transfers > 50M SYP require dual authorization (Treasury Head + CFO)
- Transfers > 500M SYP require CEO approval and 24h notice to CBS
- All interbank transfers executed via CBS RTGS between 09:00–14:00 (CBS settlement window)
- Physical cash logistics coordinated with Cash Transport Syria (licensed security firm)

### 3. Agent Float Optimization

Ensure 3,500+ agents across 14 Syrian governorates maintain optimal float.

**Float Calculation:**

```
Optimal Float = (Historical daily avg cash-out × 3 days) + (Buffer 20%) + (Distance factor)
```

**Agent Float Tiers:**

| Tier                      | Daily Volume  | Float Range   | Top-Up Frequency | Monitoring      |
| ------------------------- | ------------- | ------------- | ---------------- | --------------- |
| Premium (100+ txns/day)   | > 500K SYP    | 2M–5M SYP     | Every 2 days     | Daily check     |
| Standard (30–99 txns/day) | 100K–500K SYP | 500K–2M SYP   | Every 3 days     | Every other day |
| Basic (< 30 txns/day)     | < 100K SYP    | 100K–500K SYP | Weekly           | Weekly check    |

**Float Replenishment Flow:**

```
Agent requests top-up → Beza treasury validates →
Bank transfer to agent account → Agent mobile wallet credited →
Physical cash delivered via Cash Transport Syria (if needed)
```

### 4. FX Position Management

Manage SYP/USD exposure in compliance with CBS regulations.

**Position Limits:**

| Limit Type        | Threshold                      | Approval      |
| ----------------- | ------------------------------ | ------------- |
| Intraday SYP/USD  | Long 500K USD / Short 200K USD | Treasury Head |
| Overnight SYP/USD | Long 100K USD / Short 50K USD  | CFO           |
| Monthly Net Open  | Max 2M USD                     | Board + CBS   |

**FX Rate Sources:**

- Primary: CBS official daily fixing rate (published 10:00)
- Secondary: Bloomberg terminal (SYP NDF implied rate)
- Fallback: Bemo Saudi Fransi quote desk

**Hedging Instruments:**

- CBS forward contracts (max 90-day tenor)
- NDFs via Bemo Saudi Fransi for USD/SYP

### 5. Liquidity Coverage

Meet CBS reserve requirements.

**CBS Statutory Reserve Requirements (2024):**

| Metric                | Requirement                               | Current Status    |
| --------------------- | ----------------------------------------- | ----------------- |
| Reserve Ratio         | 15% of customer deposits                  | 16.2% (compliant) |
| Minimum Reserve       | 5B SYP in CBS reserve account             | 5B SYP            |
| Calculation Base      | Average daily deposits over 14-day period | —                 |
| Reporting             | Daily to CBS via SRM portal               | —                 |
| Penalty for Shortfall | 2× CBS discount rate on deficit amount    | —                 |

**Reserve Monitoring Query:**

```sql
SELECT
    deposit_date,
    SUM(balance) as total_deposits,
    SUM(balance) * 0.15 as required_reserve,
    (SELECT SUM(balance) FROM treasury.bank_accounts WHERE account_type = 'RESERVE') as current_reserve,
    CASE
        WHEN (SELECT SUM(balance) FROM treasury.bank_accounts WHERE account_type = 'RESERVE') >= SUM(balance) * 0.15
        THEN 'COMPLIANT' ELSE 'NON-COMPLIANT'
    END as compliance_status
FROM treasury.customer_deposits_daily
WHERE deposit_date >= CURRENT_DATE - INTERVAL '14 days'
GROUP BY deposit_date;
```

### 6. Bank Relationship Management

Maintain accounts and relationships with Syrian banks.

| Bank              | Relationship Manager  | Meeting Cadence | Credit Facilities                 |
| ----------------- | --------------------- | --------------- | --------------------------------- |
| BSO               | Omar Al-Khatib        | Weekly          | 500M SYP overdraft                |
| Bemo Saudi Fransi | Sami Daoud            | Weekly          | 1B SYP overdraft + 3M USD FX line |
| SIIB              | Hassan Mousa          | Bi-weekly       | 300M SYP overdraft                |
| CBS               | CBS Corporate Banking | Monthly         | N/A (regulatory only)             |

## Liquidity Tiers

| Tier             | Amount            | Purpose                                           | Access Time                | Location            |
| ---------------- | ----------------- | ------------------------------------------------- | -------------------------- | ------------------- |
| Tier 1 (Cash)    | 500,000,000 SYP   | Daily settlement, agent float top-ups             | Instant                    | CBS current account |
| Tier 2 (Bank)    | 2,000,000,000 SYP | Agent funding, bulk payouts, merchant settlements | Same day (by 14:00)        | BSO, Bemo, SIIB     |
| Tier 3 (Reserve) | 5,000,000,000 SYP | CBS statutory reserve requirement                 | 24h notice (CBS clearance) | CBS reserve account |
| FX Buffer        | $2,500,000 USD    | FX settlement, import payments, agent USD         | Same day (by 12:00)        | Bemo USD account    |

## Liquidity Stress Scenarios

| Scenario                                     | Impact                       | Mitigation                                                   |
| -------------------------------------------- | ---------------------------- | ------------------------------------------------------------ |
| 20% daily withdrawal spike                   | Tier 1 depleted in 2h        | Draw from Tier 2 (same-day)                                  |
| Tier 2+ major bank outage                    | Only CBS account accessible  | Activate backup accounts at remaining banks                  |
| CBS RTGS system down                         | All interbank transfers halt | Use mobile wallet peer-to-peer float sharing                 |
| 30%+ deposit outflow (bank run)              | Tier 3 insufficient          | Emergency CBS liquidity facility (pre-arranged 10B SYP line) |
| FX rate crash (parallel rate > 20% official) | USD buffer under water       | Hedge with CBS forwards, suspend USD services                |

## Reporting

### Daily Reports (07:00)

| Report               | Contents                                                             | Recipients         |
| -------------------- | -------------------------------------------------------------------- | ------------------ |
| Cash Position        | Opening balance, projected inflows/outflows, closing, by wallet type | Treasury Team      |
| FX Exposure          | Long/short USD positions, MTM, hedging status                        | CFO, Treasury Head |
| Agent Float Coverage | % agents with > 3 days float remaining, top 10 agents needing top-up | Agent Ops Manager  |
| CBS Reserve Status   | Current reserve ratio, compliance check                              | Compliance Officer |

### Weekly Reports (Sunday)

| Report                   | Contents                                                      | Recipients         |
| ------------------------ | ------------------------------------------------------------- | ------------------ |
| Forecast vs Actual       | 7-day forecast accuracy, variance analysis                    | CFO, Treasury      |
| Bank Balances            | All bank account balances, outstanding transfers              | Treasury, Finance  |
| CBS Compliance           | Reserve compliance report, any penalty exposure               | Compliance, CEO    |
| Agent Float Optimization | Float turnover ratios, cost analysis, recommended adjustments | Agent Network Head |

### Monthly Reports (1st of Month)

| Report                | Contents                                                               | Recipients            |
| --------------------- | ---------------------------------------------------------------------- | --------------------- |
| Board Treasury Report | Liquidity position, FX exposure, bank relationships, forecast accuracy | Board of Directors    |
| CBS Statutory Report  | Reserve compliance, deposit base, settlement volumes                   | CBS Supervision Dept  |
| Bank Fee Analysis     | Bank charges, interest income/expense                                  | CFO                   |
| Annual Audit          | Full treasury operations audit by CBS-approved external auditor        | Board Audit Committee |

## Systems & Tools

| System                  | Purpose                             | URL                                      |
| ----------------------- | ----------------------------------- | ---------------------------------------- |
| Cash Forecasting Engine | Automated Prophet/ARIMA forecasts   | `https://treasury.beza-sy.com/forecast`  |
| Bank Portal (BSO)       | Balance inquiry, transfers          | `https://ebanking.bso-sy.com`            |
| Bank Portal (Bemo)      | Balance inquiry, transfers, FX      | `https://corporate.bemo-sy.com`          |
| Bank Portal (SIIB)      | Balance inquiry, transfers          | `https://ebanking.siib-sy.com`           |
| CBS SRM Portal          | Reserve reporting, RTGS, settlement | `https://srm.cbs.gov.sy`                 |
| Bloomberg Terminal      | FX rates, market data               | `//BLP/` (Bemo office)                   |
| Treasury Dashboard      | Real-time cash position, alerts     | `https://grafana.beza-sy.com/d/treasury` |

## Key Contacts

| Role                      | Name               | Phone            | Email                    |
| ------------------------- | ------------------ | ---------------- | ------------------------ |
| Head of Treasury          | Khaled Al-Jundi    | +963 11 234 5678 | khaled.jundi@beza-sy.com |
| Treasury Analyst          | Lina Haddad        | +963 11 234 5679 | lina.haddad@beza-sy.com  |
| CFO                       | Rami Sukkar        | +963 11 234 5680 | rami.sukkar@beza-sy.com  |
| CBS Relationship Manager  | CBS Corporate Desk | +963 11 245 8900 | corporate@cbs.gov.sy     |
| BSO Relationship Manager  | Omar Al-Khatib     | +963 11 235 1122 | omar.khatib@bso-sy.com   |
| Bemo Treasury Desk        | Sami Daoud         | +963 11 236 3344 | sami.daoud@bemo-sy.com   |
| SIIB Relationship Manager | Hassan Mousa       | +963 11 237 5566 | hassan.mousa@siib-sy.com |
