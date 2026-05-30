# Rule-Based Pricing Engine — Beza Platform

> **Document Code:** BZ-FIN-PRD-006
> **Version:** 1.0
> **Status:** Draft
> **Domain:** Pricing & Fee Computation
> **Owner:** Financial Engineering Team

---

## 1. Overview

The Beza Pricing Engine is a deterministic, rule-based fee computation system that evaluates every financial transaction against a configurable set of pricing rules. All fees, margins, commissions, and discounts are derived from rules stored in the database and cached in Redis — no hardcoded pricing exists in application code.

---

## 2. Architecture

```
                           ┌─────────────────────────────────────────────┐
                           │             REQUEST CONTEXT                 │
                           │  txn_type, amount, currency, user_tier,     │
                           │  channel, agent_type, merchant_id, biller   │
                           └───────────────────┬─────────────────────────┘
                                                │
                                                ▼
                           ┌─────────────────────────────────────────────┐
                           │           PRICING ENGINE                    │
                           │                                             │
                           │  ┌────────────────┐                        │
                           │  │  Rule Matcher  │──Evaluates all active   │
                           │  │  (Priority)    │  rules against context  │
                           │  └───────┬────────┘  Returns matching set  │
                           │          │                                  │
                           │          ▼                                  │
                           │  ┌────────────────┐                        │
                           │  │ Fee Calculator │──Applies formula:       │
                           │  │                │  PERCENTAGE, FLAT,      │
                           │  │                │  PERCENTAGE_WITH_FCC,   │
                           │  │                │  TIERED                 │
                           │  └───────┬────────┘                        │
                           │          │                                  │
                           │          ▼                                  │
                           │  ┌────────────────┐                        │
                           │  │ FX Margin      │──If txn involves FX:   │
                           │  │ Calculator     │  applies buy/sell       │
                           │  │                │  margin based on tier   │
                           │  └───────┬────────┘                        │
                           │          │                                  │
                           │          ▼                                  │
                           │  ┌────────────────┐                        │
                           │  │ Commission     │──Splits fee between:   │
                           │  │ Calculator     │  Beza, Agent, Partner, │
                           │  │                │  Biller, Referrer      │
                           │  └───────┬────────┘                        │
                           │          │                                  │
                           │          ▼                                  │
                           │  ┌────────────────┐                        │
                           │  │ Discount       │──Applies: loyalty,     │
                           │  │ Applicator     │  promo, volume,        │
                           │  │                │  tier-based discounts   │
                           │  └───────┬────────┘                        │
                           └──────────┼──────────────────────────────────┘
                                      │
                                      ▼
                           ┌─────────────────────────────────────────────┐
                           │             RESPONSE                       │
                           │  {                                         │
                           │    fee_amount: 250,                        │
                           │    fee_currency: "SYP",                    │
                           │    fx_margin: 0,                           │
                           │    commission_breakdown: {                  │
                           │      beza: 100,                            │
                           │      agent: 150,                           │
                           │      merchant: 0                           │
                           │    },                                      │
                           │    discount_applied: "LOYALTY_50PCT",      │
                           │    total_charge: 250,                      │
                           │    breakdown: [{                           │
                           │      type: "FEE",                          │
                           │      rule_id: "FEE-CIN-001",              │
                           │      amount: 500                           │
                           │    },{                                     │
                           │      type: "DISCOUNT",                     │
                           │      rule_id: "DSC-LOY-001",              │
                           │      amount: -250                          │
                           │    }]                                      │
                           │  }                                         │
                           └─────────────────────────────────────────────┘
```

---

## 3. Rule Format

Every pricing rule is stored as a JSON document in the `pricing_rules` database table and cached in Redis under key `pricing:rules:{rule_id}`.

```json
{
  "rule_id": "FEE-TRF-001",
  "name": "P2P Transfer Standard Fee",
  "product_code": "WLT-001",
  "condition": {
    "transaction_type": "P2P_TRANSFER",
    "channel": ["MOBILE_APP", "USSD"],
    "currency": ["SYP", "USD"]
  },
  "formula": {
    "type": "PERCENTAGE_WITH_FLOOR_AND_CAP",
    "percentage": 0.5,
    "min_amount": 50,
    "max_amount": 5000,
    "currency": "SYP"
  },
  "commission_split": {
    "beza": 70,
    "agent": 0,
    "merchant": 0,
    "partner": 0,
    "biller": 0
  },
  "priority": 100,
  "active": true,
  "effective_from": "2026-01-01T00:00:00Z",
  "effective_to": null,
  "version": 2,
  "created_by": "admin@beza.finance",
  "created_at": "2026-01-01T10:00:00Z"
}
```

### Formula Types

| Type | Description | Parameters |
|------|-------------|------------|
| `FLAT` | Fixed amount per transaction | `amount` |
| `PERCENTAGE` | Percentage of transaction amount | `percentage` |
| `PERCENTAGE_WITH_FLOOR_AND_CAP` | Percentage with min/max bounds | `percentage, min_amount, max_amount` |
| `TIERED` | Different rates by amount bracket | `tiers: [{from, to, percentage, flat}]` |
| `ZERO` | Free transaction (no fee) | — |

---

## 4. Fee Rules

### 4.1 Standard Fee Rules

| Rule ID | Product | Condition | Formula | Min | Max | Commission Split | Priority |
|---------|---------|-----------|---------|-----|-----|-----------------|----------|
| FEE-TRF-001 | P2P Transfer (WLT-001) | All channels, SYP/USD | 0.5% | 50 SYP | 5,000 SYP | Beza 100% | 100 |
| FEE-CIN-001 | Agent Cash-in (AGT-001) | Mobile App, Agent POS | 0.5% | 100 SYP | 3,000 SYP | Beza 40%, Agent 60% | 100 |
| FEE-COUT-001 | Agent Cash-out (AGT-002) | Mobile App, Agent POS | 1.0% | 200 SYP | 5,000 SYP | Beza 30%, Agent 70% | 100 |
| FEE-FX-001 | FX Conversion (FX-001) | All channels | 1.5% margin | — | — | Beza 100% | 100 |
| FEE-REM-001 | Inbound Remittance (REM-001) | GCC corridor | 3.0% | 200 SYP | 10,000 SYP | Beza 50%, Partner 50% | 100 |
| FEE-REM-002 | Inbound Remittance (REM-001) | EU corridor | 4.0% | 300 SYP | 12,000 SYP | Beza 50%, Partner 50% | 110 |
| FEE-REM-003 | Inbound Remittance (REM-001) | TRY corridor | 3.5% | 250 SYP | 11,000 SYP | Beza 45%, Partner 55% | 105 |
| FEE-BIL-001 | Bill Payment — Telecom (BIL-001) | Syriatel, MTN | 0.5% | 100 SYP | 2,000 SYP | Beza 50%, Biller 50% | 100 |
| FEE-BIL-002 | Bill Payment — Electricity (BIL-002) | PEED, all governorates | 0.5% | 100 SYP | 2,000 SYP | Beza 70%, Biller 30% | 100 |
| FEE-BIL-003 | Bill Payment — Water (BIL-003) | All water authorities | 0.5% | 100 SYP | 1,000 SYP | Beza 60%, Biller 40% | 100 |
| FEE-MER-001 | Merchant QR Payment (MER-001) | All merchants, SYP | 1.0% MDR | 50 SYP | 5,000 SYP | Beza 30%, Agent 70% | 100 |
| FEE-MER-002 | Merchant QR Payment (MER-001) | Micro-merchant (< 5M/month) | 0.75% MDR | 50 SYP | 3,000 SYP | Beza 25%, Agent 75% | 110 |
| FEE-PAY-001 | Payroll Disbursement (PAY-001) | B2B, all tiers | 1.0% | 500 SYP | 20,000 SYP | Beza 100% | 100 |
| FEE-PAY-002 | Payroll Disbursement (PAY-001) | Enterprise (> 500 employees) | 0.75% | 500 SYP | 15,000 SYP | Beza 100% | 110 |
| FEE-SAV-001 | Savings Goal (SAV-001) | Mudaraba profit share | 30% of profit | — | — | Beza 100% (as Mudarib) | 100 |
| FEE-CRD-001 | Card Issuance (CRD-001) | Virtual card | 0 SYP | — | — | Beza 100% | 100 |
| FEE-CRD-002 | Card Issuance (CRD-001) | Physical card | 10,000 SYP | — | — | Beza 100% | 100 |
| FEE-CRD-003 | Card Monthly (CRD-001) | Monthly maintenance | 1,500 SYP | — | — | Beza 100% | 100 |
| FEE-CRD-004 | Card ATM Withdrawal (CRD-002) | Domestic ATM (SPN) | 2,000 SYP | — | — | Beza 60%, SPN 40% | 100 |
| FEE-CRD-005 | Card POS (CRD-002) | Domestic POS transaction | 0.5% | 100 SYP | 3,000 SYP | Beza 50%, Acquirer 50% | 100 |
| FEE-GOV-001 | Government Collection (GOV-001) | All govt entities | 0.5% | 100 SYP | 5,000 SYP | Beza 100% | 100 |
| FEE-ESC-001 | Escrow Service (ESC-001) | Marketplace escrow | 1.0% | — | 50,000 SYP | Beza 100% | 100 |
| FEE-ESC-002 | Escrow Service (ESC-001) | B2B/Real estate escrow | 0.5% | — | 200,000 SYP | Beza 100% | 110 |
| FEE-AGT-003 | Agent Float Top-up (AGT-003) | Third-party agents only | 0.2% | 500 SYP | 2,000 SYP | Beza 100% | 100 |

### 4.2 Agent Cash Transaction Details

These rules govern the split for the cash handling fee charged to the customer.

| Rule ID | Transaction | Fee Charged | Beza Share | Agent Share | Rationale |
|---------|-------------|-------------|------------|-------------|-----------|
| FEE-CIN-001 | Cash-in (0.5%) | Customer pays 0.5% (min 100, max 3,000 SYP) | 40% of fee | 60% of fee | Agent handles cash risk, storage, and CBS cash reporting |
| FEE-COUT-001 | Cash-out (1.0%) | Customer pays 1% (min 200, max 5,000 SYP) | 30% of fee | 70% of fee | Higher agent risk (cash availability, security, counterfeit detection) |

**Example:** Agent processes 100,000 SYP cash-out.
- Customer charged: 1,000 SYP (1%).
- Agent earns: 700 SYP (70%).
- Beza earns: 300 SYP (30%).

### 4.3 Merchant Commission Details

| Rule ID | Merchant Type | MDR | Beza Share | Agent Share | Notes |
|---------|--------------|-----|------------|-------------|-------|
| FEE-MER-001 | Standard merchant | 1.0% | 30% | 70% | Agent acquires and services the merchant |
| FEE-MER-002 | Micro-merchant (< 5M/month) | 0.75% | 25% | 75% | Subsidized rate to encourage adoption |

---

## 5. Discount Rules

| Rule ID | Name | Condition | Discount | Stackable | Priority |
|---------|------|-----------|----------|-----------|----------|
| DSC-LOY-001 | Tier 3 Loyalty | User tier = 3 AND > 50 transactions in last 30 days | 50% off transfer fees (FEE-TRF-001 only) | No | 100 |
| DSC-LOY-002 | Tier 2 Loyalty | User tier = 2 AND > 20 transactions in last 30 days | 25% off transfer fees (FEE-TRF-001 only) | No | 110 |
| DSC-PROMO-001 | New User First Transfers | User registration date < 30 days AND transaction count < 4 | 100% fee waiver (all transaction fees) | No | 50 |
| DSC-PROMO-002 | Agent Onboarding | Agent first 50 cash-in/cash-out transactions | 100% fee waiver for customers (Beza subsidizes agent commission) | No | 60 |
| DSC-VOL-001 | Payroll Volume Discount | Employer batch > 100 employees | 0.5% instead of 1% (applies to FEE-PAY-001) | No | 100 |
| DSC-VOL-002 | High-Volume Merchant | Merchant monthly QR volume > 50M SYP | 0.75% instead of 1% MDR | No | 100 |
| DSC-SEASONAL-001 | Ramadan Promo | Calendar month = Ramadan (Islamic calendar) | 25% off all transfer and bill payment fees | Yes (with loyalty) | 200 |
| DSC-REF-001 | Referral Bonus | Referred user completes first 3 transactions | 5,000 SYP credit to referrer (applied as discount on next fee) | No | 300 |

### Discount Evaluation Logic

```
1. Collect all matching discount rules from request context
2. Sort by priority (lower number = evaluated first)
3. If a discount is non-stackable, apply it and skip remaining
4. If stackable, accumulate discount percentage (capped at 100%)
5. Apply discount_amount = min(fee_amount * discount_pct, discount_max)
6. Ensure final fee >= 0 (no negative fees)
```

---

## 6. FX Margin Rules

| Pair | Direction | Base Margin | Tier 1 | Tier 2 | Tier 3 | CBS Max Spread |
|------|-----------|-------------|--------|--------|--------|----------------|
| SYP→USD | Buy (Sell SYP) | 2.0% | 2.0% | 1.5% | 1.0% | 3.0% (CBS Directive 2023/03) |
| USD→SYP | Sell (Buy SYP) | 1.5% | 1.5% | 1.0% | 0.75% | 2.5% (CBS Directive 2023/03) |
| SYP→EUR | Buy (Sell SYP) | 2.5% | 2.5% | 2.0% | 1.5% | 3.5% |
| EUR→SYP | Sell (Buy SYP) | 2.0% | 2.0% | 1.5% | 1.0% | 3.0% |
| SYP→TRY | Buy (Sell SYP) | 3.0% | 3.0% | 2.5% | 2.0% | 4.0% |
| TRY→SYP | Sell (Buy SYP) | 2.5% | 2.5% | 2.0% | 1.5% | 3.5% |

### FX Formula

```
customer_rate = mid_market_rate * (1 + margin)

where:
  mid_market_rate = CBS official rate (updated daily at 10:00 AM Damascus time)

Example:
  CBS rate: 1 USD = 13,000 SYP
  Tier 2 user buying USD (SYP→USD):
    margin = 1.5%
    customer_rate = 13,000 * (1 + 0.015) = 13,195 SYP/USD
  Tier 2 user selling USD (USD→SYP):
    margin = 1.0%
    customer_rate = 13,000 * (1 - 0.01) = 12,870 SYP/USD
```

### FX Fee Income Calculation

```
For 1,000 USD buy at Tier 2:
  Beza FX income = 1,000 USD * 13,000 SYP/USD * 1.5% = 195,000 SYP
```

---

## 7. Implementation Rules

### 7.1 Configuration & Storage

| Rule | Detail |
|------|--------|
| Storage | All pricing rules in `pricing_rules` table (PostgreSQL) |
| Cache | Redis key `pricing:rules:{rule_id}` — TTL 3,600 seconds (1 hour) |
| Cache warmup | On application startup, all active rules loaded into Redis |
| Invalidation | Rule CRUD operations trigger cache invalidation (pub/sub channel: `pricing:invalidate`) |
| Source of truth | Database (Redis is read-through cache) |

### 7.2 Rule Evaluation Algorithm

```
function evaluate_pricing(context):
    # 1. Load active rules for context.product_code
    rules = load_rules(context.product_code)

    # 2. Filter matching rules
    matching = [r for r in rules if all_conditions_match(r.condition, context)]

    # 3. Sort by priority (ascending — lower number = higher priority)
    matching.sort(key=lambda r: r.priority)

    # 4. First match wins (no rule stacking unless explicitly configured)
    primary_rule = matching[0] if matching else None

    # 5. Apply formula
    fee = apply_formula(primary_rule.formula, context.amount)

    # 6. Calculate FX margin if applicable
    fx_margin = 0
    if context.currency_pair:
        fx_margin = calculate_fx_margin(context.currency_pair, context.user_tier)

    # 7. Split commission
    commission = split_commission(fee, primary_rule.commission_split)

    # 8. Apply discounts
    discount = apply_discounts(context, fee)
    fee_after_discount = max(fee - discount, 0)

    # 9. Build response
    return build_response(fee_after_discount, fx_margin, commission, discount, primary_rule)
```

### 7.3 Audit & Logging

| Requirement | Implementation |
|-------------|---------------|
| Rule change audit | `pricing_rule_audit` table: stores before/after delta, changed_by, timestamp |
| Pricing history | Every fee calculation logged to `pricing_calculation_log` (transaction_id, rule_id, fee, discount, breakdown) |
| Immutable log | Pricing log entries are write-once (no UPDATE, only INSERT) |
| Retention | 7 years (CBS regulatory requirement for financial records) |
| Queryable | Logs indexed by transaction_id, user_id, date range for analytics |

### 7.4 Business Rules

- **No negative fees**: Minimum computed fee is 0 (discounts may reduce fee to zero but never negative).
- **Rounding**: All fees rounded to the nearest SYP (1 SYP = minimum currency unit). USD fees rounded to 0.01 USD.
- **Decimal precision**: Internal calculations use 6 decimal places; final amount truncated to 2 decimals.
- **Tier override**: A specific user tier may be overridden by a contractual agreement (stored in `user_pricing_overrides` table).
- **Channel override**: USSD channel may have a +100 SYP surcharge (added as separate line item in breakdown).
- **Minimum charge**: The platform minimum charge for any paid transaction is 50 SYP (unless a zero-fee promo is active).
- **Currency conversion fees**: When a transaction involves different currencies than the user's primary wallet, FX conversion is applied before fee calculation.
- **Agent incentive floor**: Agent commission share for cash-in/cash-out will never be less than 60% (per Agent Network Agreement clause 4.2).

### 7.5 CBS Regulatory Constraints

| Constraint | Limit | Source |
|------------|-------|--------|
| Max cash-in fee | 0.5% | CBS Agent Banking Framework 2023/45 |
| Max cash-out fee | 1.0% | CBS Agent Banking Framework 2023/45 |
| Max FX spread | 3.0% (SYP↔USD) | CBS FX Directive 2023/03 |
| Max remittance fee | 4.0% | CBS Cross-Border Remittance Directive 2022/28 |
| Max government collection fee | 0.5% | CBS Government Collection Framework 2024/17 |
| Max MDR (micro-merchant) | 1.0% | CBS Merchant Payment Framework 2024/01 |
| Fee transparency | All fees must be displayed before transaction confirmation | CBS Consumer Protection Directive 2024/05 |

---

## 8. Database Schema

```sql
-- Core pricing rules table
CREATE TABLE pricing_rules (
    rule_id         VARCHAR(20) PRIMARY KEY,
    name            VARCHAR(255) NOT NULL,
    product_code    VARCHAR(10) NOT NULL REFERENCES products(product_code),
    condition       JSONB NOT NULL,
    formula         JSONB NOT NULL,
    commission_split JSONB NOT NULL,
    priority        INTEGER NOT NULL DEFAULT 100,
    active          BOOLEAN NOT NULL DEFAULT true,
    effective_from  TIMESTAMP WITH TIME ZONE NOT NULL,
    effective_to    TIMESTAMP WITH TIME ZONE,
    version         INTEGER NOT NULL DEFAULT 1,
    created_by      VARCHAR(100) NOT NULL,
    created_at      TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    updated_by      VARCHAR(100),
    updated_at      TIMESTAMP WITH TIME ZONE
);

-- Discount rules table
CREATE TABLE discount_rules (
    rule_id         VARCHAR(20) PRIMARY KEY,
    name            VARCHAR(255) NOT NULL,
    condition       JSONB NOT NULL,
    discount_type   VARCHAR(50) NOT NULL, -- PERCENTAGE, FLAT, WAIVER
    discount_value  DECIMAL(15,4) NOT NULL,
    max_discount    DECIMAL(15,2),
    stackable       BOOLEAN NOT NULL DEFAULT false,
    priority        INTEGER NOT NULL DEFAULT 100,
    active          BOOLEAN NOT NULL DEFAULT true,
    effective_from  TIMESTAMP WITH TIME ZONE NOT NULL,
    effective_to    TIMESTAMP WITH TIME ZONE
);

-- Audit log for rule changes
CREATE TABLE pricing_rule_audit (
    audit_id        BIGSERIAL PRIMARY KEY,
    rule_id         VARCHAR(20) NOT NULL,
    action          VARCHAR(20) NOT NULL, -- CREATE, UPDATE, DELETE, TOGGLE
    before_state    JSONB,
    after_state     JSONB,
    changed_by      VARCHAR(100) NOT NULL,
    changed_at      TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
);

-- Pricing calculation log
CREATE TABLE pricing_calculation_log (
    log_id          BIGSERIAL PRIMARY KEY,
    transaction_id  VARCHAR(64) NOT NULL,
    rule_id         VARCHAR(20),
    context         JSONB NOT NULL,
    fee_amount      DECIMAL(15,2) NOT NULL,
    fee_currency    VARCHAR(3) NOT NULL,
    fx_margin       DECIMAL(15,2) DEFAULT 0,
    commission_breakdown JSONB,
    discount_applied JSONB,
    discount_amount  DECIMAL(15,2) DEFAULT 0,
    total_charge    DECIMAL(15,2) NOT NULL,
    breakdown       JSONB NOT NULL,
    calculated_at   TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
);

-- User-level pricing overrides (contractual)
CREATE TABLE user_pricing_overrides (
    override_id     BIGSERIAL PRIMARY KEY,
    user_id         VARCHAR(64) NOT NULL,
    product_code    VARCHAR(10) NOT NULL REFERENCES products(product_code),
    override_type   VARCHAR(50) NOT NULL, -- DISCOUNT_PERCENTAGE, FLAT_RATE, CUSTOM_RULE
    override_value  JSONB NOT NULL,
    approved_by     VARCHAR(100) NOT NULL,
    effective_from  TIMESTAMP WITH TIME ZONE NOT NULL,
    effective_to    TIMESTAMP WITH TIME ZONE,
    created_at      TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
);
```

---

## 9. API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/pricing/evaluate` | Evaluate pricing for a single transaction (real-time) |
| POST | `/api/v1/pricing/batch-evaluate` | Evaluate pricing for a batch (e.g., payroll) |
| GET | `/api/v1/pricing/rules` | List all active pricing rules |
| GET | `/api/v1/pricing/rules/{rule_id}` | Get rule details |
| POST | `/api/v1/pricing/rules` | Create new pricing rule (admin) |
| PUT | `/api/v1/pricing/rules/{rule_id}` | Update pricing rule (audit trail auto-logged) |
| DELETE | `/api/v1/pricing/rules/{rule_id}` | Soft-delete (set active=false) |
| GET | `/api/v1/pricing/rules/{rule_id}/audit` | View audit trail for a rule |
| GET | `/api/v1/pricing/calculate-history` | Query pricing calculation logs (with filters) |
| POST | `/api/v1/pricing/simulate` | Simulate pricing without creating a transaction |
| POST | `/api/v1/admin/pricing/cache/refresh` | Force refresh Redis cache from DB |

---

## 10. Sample Calculation Walkthrough

### Scenario: Agent Cash-out of 50,000 SYP by a Tier 2 User

**Request context:**
```json
{
  "transaction_type": "AGENT_CASH_OUT",
  "amount": 50000,
  "currency": "SYP",
  "user_tier": 2,
  "channel": "MOBILE_APP",
  "agent_type": "CERTIFIED",
  "product_code": "AGT-002"
}
```

**Engine evaluation:**

| Step | Rule | Calculation | Result |
|------|------|------------|--------|
| 1 | Match rule | FEE-COUT-001 (Agent Cash-out, 1%, min 200, max 5,000) | Matched (priority 100) |
| 2 | Fee formula | 50,000 × 1% = 500 SYP | 500 SYP (within min/max bounds) |
| 3 | Check discount | User Tier 2, > 20 transactions last 30 days? → Yes | DSC-LOY-002 applies: 25% off transfer fees |
| 4 | Apply discount | 500 × 25% = 125 SYP | 500 − 125 = 375 SYP |
| 5 | Check promo | Registration < 30 days? → No | No promo discount |
| 6 | Commission split | Fee 375 SYP: Beza 30% (112.5), Agent 70% (262.5) | Beza: 113 SYP, Agent: 263 SYP (rounded) |
| 7 | FX check | SYP only, no FX | margin = 0 |

**Response:**
```json
{
  "fee_amount": 375.00,
  "fee_currency": "SYP",
  "fx_margin": 0,
  "commission_breakdown": {
    "beza": 112.50,
    "agent": 262.50
  },
  "discount_applied": "DSC-LOY-002",
  "discount_amount": 125.00,
  "total_charge": 375.00,
  "breakdown": [
    { "type": "FEE", "rule_id": "FEE-COUT-001", "amount": 500.00 },
    { "type": "DISCOUNT", "rule_id": "DSC-LOY-002", "amount": -125.00 }
  ]
}
```

---

*This pricing engine design is proprietary to Beza Platform. All fee rates, margins, and commission splits are based on Syrian market benchmarks and CBS regulatory limits as of May 2026.*
