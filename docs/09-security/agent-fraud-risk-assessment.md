# Agent Fraud Risk Assessment — Beza Platform
# تقييم مخاطر احتيال الوكلاء — منصة بيزا

| **Document Version** | 1.0 |
|----------------------|-----|
| **Classification** | Confidential — Internal Use Only |
| **Date** | 2026-05-31 |
| **Author** | Pentest Agent — Security Research Team |
| **Scope** | Agent Module (Cash-In, Cash-Out, Commission, Settlement) |
| **Methodology** | OWASP Testing Guide v5, STRIDE per-component, CVSS 4.0 |

---

## Table of Contents / فهرس المحتويات

1. [Executive Summary / الملخص التنفيذي](#1-executive-summary)
2. [Threat Model — Agent Fraud Vectors / نموذج التهديدات](#2-threat-model--agent-fraud-vectors)
3. [Risk Matrix / مصفوفة المخاطر](#3-risk-matrix)
4. [Mitigation Strategies / استراتيجيات التخفيف](#4-mitigation-strategies)
5. [Top 5 Security Recommendations / أهم 5 توصيات أمنية](#5-top-5-security-recommendations)
6. [Code-Level Vulnerabilities Found / الثغرات على مستوى الكود](#6-code-level-vulnerabilities-found)

---

## 1. Executive Summary / الملخص التنفيذي

This risk assessment covers the **Beza Platform Agent Module**, which enables licensed exchange offices (Agents) to perform cash-in and cash-out operations for digital wallet users. The module processes daily volume in the millions of SYP and handles commission accrual and settlement.

**Overall Risk Rating: HIGH** — The module contains several critical vulnerabilities that could lead to financial loss, unauthorized balance manipulation, and systemic fraud through agent-user collusion. The most severe issues involve race conditions in idempotency checks, PIN brute-force exposure, insufficient balance validation, and commission farming via fake transactions.

---

## 2. Threat Model — Agent Fraud Vectors / نموذج التهديدات — نواقل احتيال الوكلاء

### Vector 1: Agent-User Collusion — Fake Cash-In
**الناقل 1: التواطؤ بين الوكيل والمستخدم — إيداع وهمي**

| Attribute | Value |
|-----------|-------|
| **Attack Scenario** | Agent colludes with a user to create a fake cash-in transaction. Agent claims to have received cash from the user, creates an `AgentTransaction` of type `cash_in`, and the user's wallet is credited with `netAmount`. No real cash changes hands. Agent earns commission (~1%) and both parties share the fraud proceeds. Agent's blocked balance is decremented (but may not represent real blocked funds). The user can then withdraw the fraudulently credited funds. |
| **Likelihood** | **High** — Simple to execute, requires only two collaborating accounts. No cash inventory verification exists in the system. |
| **Impact** | **High** — Direct financial loss. A single 5,000,000 SYP transaction yields 50,000 SYP commission to the agent, and the user obtains spendable wallet balance. |
| **Affected Components** | `CashInService::cashIn()`, `AgentController::cashIn()` |
| **CVSS 4.0** | 8.6 (AV:N/AC:L/AT:N/PR:L/UI:N/VC:H/VI:H/VA:N/SC:N/SI:N/SA:N) |

---

### Vector 2: Idempotency Key Race Condition — Double-Spending
**الناقل 2: سباق التوقيت في مفتاح التكرار — إنفاق مزدوج**

| Attribute | Value |
|-----------|-------|
| **Attack Scenario** | The idempotency check in `CashInService::cashIn()` (Line 24-29) and `CashOutService::cashOut()` (Line 25-30) occurs **outside** the database transaction. Two concurrent requests with the same `idempotency_key` can both pass the check before either creates the `AgentTransaction`. Both proceed into the transaction block, resulting in double-crediting (cash-in) or double-debiting (cash-out) of the user's wallet. This is a classic TOCTOU (Time-of-Check Time-of-Use) vulnerability. |
| **Likelihood** | **Medium** — Requires precise timing and concurrent request submission. However, with automated scripts, this is reliably exploitable. |
| **Impact** | **High** — Direct financial loss. A single double-processed cash-in of 5,000,000 SYP creates 10,000,000 SYP in fraudulent credits. |
| **Affected Components** | `CashInService::cashIn()` (Lines 24-29), `CashOutService::cashOut()` (Lines 25-30) |
| **CVSS 4.0** | 8.2 (AV:N/AC:L/AT:N/PR:L/UI:N/VC:H/VI:H/VA:N/SC:N/SI:N/SA:N) |

---

### Vector 3: Agent Balance Manipulation via Direct Increment
**الناقل 3: التلاعب برصيد الوكيل عبر الزيادة المباشرة**

| Attribute | Value |
|-----------|-------|
| **Attack Scenario** | In `CashInService::cashIn()` (Line 63), the agent's wallet balance is incremented by `$amount->fils() - $netAmount` (which equals the fee). Simultaneously, `CommissionService::accrueCashInFee()` also records the fee as accrued commission (Line 81). When settlement occurs (`triggerSettlement()`, Line 155), the agent's balance is incremented **again** by `$totalCommission`. This means the agent's balance is effectively double-credited for the same fee — once immediately at transaction time and once at settlement. |
| **Likelihood** | **High** — This is a systemic logic error; every cash-in transaction triggers this double-counting. |
| **Impact** | **High** — Over time, agents accumulate ~2× the expected commission in their balance, leading to significant platform leakage. |
| **Affected Components** | `CashInService::cashIn()` (Line 63), `CommissionService::triggerSettlement()` (Line 155) |
| **CVSS 4.0** | 8.4 (AV:N/AC:L/AT:N/PR:L/UI:N/VC:H/VI:H/VA:N/SC:N/SI:N/SA:N) |

---

### Vector 4: Commission Farming via Inflated Transaction Amounts
**الناقل 4: زراعة العمولات عبر تضخيم مبالغ المعاملات**

| Attack Scenario | An agent inflates the `amount` parameter in a cash-in or cash-out request beyond the actual cash received/given. The commission is calculated as `round(amount * commission_rate)` — the agent earns more commission by reporting higher transaction values. If the agent has sufficient blocked balance (cash-in) or the user has sufficient balance (cash-out), inflated amounts pass all validation checks. |
|----------------|--------|
| **Likelihood** | **Medium** — Requires the agent to have sufficient blocked balance or a willing user with sufficient balance. |
| **Impact** | **Medium** — Commission leakage proportional to inflation amount. |
| **Affected Components** | `CashInService::calculateFee()` (Line 125), `CashOutService::calculateFee()` (Line 146) |
| **CVSS 4.0** | 6.5 (AV:N/AC:L/AT:N/PR:L/UI:N/VC:N/VI:L/VA:N/SC:N/SI:N/SA:N) |

---

### Vector 5: PIN Brute-Force on Cash-Out
**الناقل 5: هجوم تخمين رمز PIN على السحب النقدي**

| Attribute | Value |
|-----------|-------|
| **Attack Scenario** | The cash-out endpoint (`/api/v1/wap/agent/cash-out`) requires the user's PIN. However, there is **no rate limiting** on PIN validation. An agent (or attacker who compromises an agent session) can brute-force a user's PIN by repeatedly calling the cash-out endpoint with different PIN values. The `Hash::check()` call on Line 139 of `CashOutService.php` is computationally cheap (likely bcrypt but still fast enough for thousands of attempts). A 4-digit PIN space (10,000 combinations) can be exhausted rapidly. |
| **Likelihood** | **High** — No rate limiting, no account lockout, no CAPTCHA. Automated brute-force is trivial. |
| **Impact** | **High** — Full account takeover for cash-out operations. Attacker can drain user wallets. |
| **Affected Components** | `CashOutService::validatePin()` (Line 139), `AgentController::cashOut()` |
| **CVSS 4.0** | 8.8 (AV:N/AC:L/AT:N/PR:L/UI:N/VC:H/VI:H/VA:N/SC:N/SI:N/SA:N) |

---

### Vector 6: Agent Creating Fake Users for Commission Farming
**الناقل 6: إنشاء الوكيل لمستخدمين وهميين لزراعة العمولات**

| Attribute | Value |
|-----------|-------|
| **Attack Scenario** | An agent registers multiple fake user accounts (or colludes with existing users) and performs circular cash-in/cash-out transactions between them. Each transaction earns the agent commission. The agent can cycle the same funds repeatedly (e.g., User A → cash-in → Agent → User B → cash-out → Agent → ...), earning commission on each cycle. The daily limit of 5,000,000 SYP deposit / 3,000,000 SYP withdrawal per agent does not prevent this — the agent simply rotates through multiple fake users. |
| **Likelihood** | **Medium** — Requires ability to create multiple user accounts (KYC bypass needed) and sufficient initial float. |
| **Impact** | **High** — Systematic siphoning of platform commissions through circular transactions. |
| **Affected Components** | All Agent services, User registration/KYC module |
| **CVSS 4.0** | 7.5 (AV:N/AC:L/AT:N/PR:L/UI:N/VC:N/VI:H/VA:N/SC:N/SI:N/SA:N) |

---

### Vector 7: Settlement Timing Attack — Multiple Settlements for Same Period
**الناقل 7: هجوم توقيت التسوية — تسويات متعددة لنفس الفترة**

| Attribute | Value |
|-----------|-------|
| **Attack Scenario** | `CommissionService::triggerSettlement()` (Line 111) can be called multiple times. Although it uses `lockForUpdate()` on commission records, if a previous settlement partially processed and failed (or was interrupted), the next call could settle already-settled commissions. Additionally, the method selects commissions with `status = 'accrued'` and `created_at <= $periodEnd`, but `$periodEnd` is computed as `now()->subDay()->endOfDay()`. If called multiple times on the same day, only the first call succeeds because subsequent calls find no `accrued` commissions. However, the agent balance increment (`$agent->increment('balance', $totalCommission)`) happens **before** the commission status update — if the system crashes between Lines 155 and 148, the balance is incremented but commissions remain `accrued`, allowing double-claim. |
| **Likelihood** | **Medium** — Requires system crash or transaction interruption at the precise moment. |
| **Impact** | **High** — Agent balance artificially inflated by duplicate settlement amounts. |
| **Affected Components** | `CommissionService::triggerSettlement()` (Lines 111-159) |
| **CVSS 4.0** | 7.2 (AV:N/AC:L/AT:N/PR:L/UI:N/VC:N/VI:H/VA:N/SC:N/SI:N/SA:N) |

---

### Vector 8: Insufficient Blocked Balance Validation in Cash-In
**الناقل 8: عدم كفاية التحقق من الرصيد المحجوز في الإيداع النقدي**

| Attribute | Value |
|-----------|-------|
| **Attack Scenario** | In `CashInService::cashIn()` (Line 62), the agent's wallet `blocked` field is decremented by `$amount->fils()`. However, there is **no validation** that the agent's `blocked` balance is sufficient before decrementing. If the `blocked` balance is zero or insufficient, `decrement()` simply makes it negative (since no check is performed). This allows an agent to perform cash-in transactions without having the required blocked funds, effectively creating money out of thin air. |
| **Likelihood** | **High** — Trivially exploitable. Any agent with `blocked = 0` can still cash-in. |
| **Impact** | **High** — Direct creation of unbacked wallet credits. Systemic financial risk. |
| **Affected Components** | `CashInService::cashIn()` (Line 62) |
| **CVSS 4.0** | 9.1 (AV:N/AC:L/AT:N/PR:L/UI:N/VC:H/VI:H/VA:N/SC:N/SI:N/SA:N) |

---

### Vector 9: Missing Agent Authorization on User Operations
**الناقل 9: عدم التحقق من صلاحية الوكيل تجاه المستخدم**

| Attribute | Value |
|-----------|-------|
| **Attack Scenario** | Any active agent can perform cash-in or cash-out on **any** user. The only validation is that both parties are `active`. Agent A can cash-out (withdraw from) User B's wallet without any relationship or authorization link between them. There is no check that the agent is assigned to or authorized by the user. A malicious agent could target any user in the system. |
| **Likelihood** | **Medium** — Requires the agent to know the user's ID (incrementally guessable integer) or obtain it through other means. |
| **Impact** | **High** — Unauthorized access to any user's wallet for cash-out operations (with PIN brute-force). |
| **Affected Components** | `AgentController::cashIn()`, `AgentController::cashOut()`, `CashInService`, `CashOutService` |
| **CVSS 4.0** | 7.5 (AV:N/AC:L/AT:N/PR:L/UI:N/VC:H/VI:L/VA:N/SC:N/SI:N/SA:N) |

---

### Vector 10: Audit Log Spoofing via Client-Supplied Data
**الناقل 10: تزوير سجل التدقيق عبر بيانات يقدمها العميل**

| Attack Scenario | The audit log entries in both `CashInService` and `CashOutService` capture `request()->ip()`, `request()->userAgent()`, and `request()->cookie('wap_fp', '')`. All three values are client-supplied and trivially spoofable. An attacker can inject arbitrary values into audit logs, masking their true identity and making forensic investigation difficult or impossible. The `wap_fp` cookie (used as a fingerprint) is particularly problematic as it can be set to any value by the client. |
|----------------|--------|
| **Likelihood** | **High** — Any HTTP client can spoof headers and cookies. |
| **Impact** | **Medium** — Hinders incident response and forensic analysis. |
| **Affected Components** | `CashInService::cashIn()` (Lines 85-91), `CashOutService::cashOut()` (Lines 87-93) |
| **CVSS 4.0** | 5.3 (AV:N/AC:L/AT:N/PR:N/UI:N/VC:N/VI:N/VA:N/SC:N/SI:L/SA:N) |

---

### Vector 11: Commission Summary Calculation Error
**الناقل 11: خطأ في حساب ملخص العمولات**

| Attack Scenario | In `CommissionService::getCommissionSummary()` (Line 70), `pending_commission` is calculated as `$pending - $settled`. However, `$pending` queries commissions with `status = 'accrued'` and `$settled` queries with `status = 'settled'` — these are **mutually exclusive** sets. This means `$pending - $settled` actually yields `$pending` (since no settled record is included in the pending sum), but if there are any negative amounts or data inconsistencies, the result could be incorrect. More critically, the pending amount should simply be the sum of all `accrued` commissions without subtracting `settled` ones, as they are separate statuses. This logic bug provides incorrect financial data to agents, potentially causing disputes or fraudulent claims. |
|----------------|--------|
| **Likelihood** | **Medium** — The bug is always present but only causes disputes, not direct exploitation. |
| **Impact** | **Low** — Financial data integrity issue. Could lead to incorrect reporting. |
| **Affected Components** | `CommissionService::getCommissionSummary()` (Line 70) |
| **CVSS 4.0** | 3.5 (AV:N/AC:L/AT:N/PR:L/UI:N/VC:N/VI:N/VA:N/SC:N/SI:L/SA:N) |

---

### Vector 12: Wallet Balance Rounding Errors in Cash-Out Validation
**الناقل 12: أخطاء التقريب في رصيد المحفظة أثناء التحقق من السحب**

| Attack Scenario | In `CashOutService::validateUserBalance()` (Line 115), the wallet balance (stored as `decimal:4`) is converted to fils using `(int) round($wallet->balance * (10 ** $amount->currency()->decimals()))`. For SYP (2 decimals), this multiplies by 100. However, the wallet's `balance` field is `decimal:4`, meaning it can have up to 4 decimal places internally. The `round()` function may produce slightly different results than expected, and the `(int)` cast truncates rather than floors. Small discrepancies across many transactions could lead to cumulative rounding errors that either deny legitimate transactions (user appears to have insufficient funds) or allow transactions when the user's actual balance is slightly below the required amount. |
|----------------|--------|
| **Likelihood** | **Low** — Exploitation requires specific decimal values and high transaction volume. |
| **Impact** | **Low** — Minor balance discrepancies over time. |
| **Affected Components** | `CashOutService::validateUserBalance()` (Line 115) |
| **CVSS 4.0** | 2.8 (AV:N/AC:H/AT:N/PR:L/UI:N/VC:N/VI:L/VA:N/SC:N/SI:N/SA:N) |

---

## 3. Risk Matrix / مصفوفة المخاطر

| # | Vector | Likelihood | Impact | Risk Level | CVSS 4.0 |
|---|--------|-----------|--------|------------|----------|
| V1 | Agent-User Collusion — Fake Cash-In | High | High | **🔴 Critical** | 8.6 |
| V2 | Idempotency Key Race Condition — Double-Spending | Medium | High | **🔴 Critical** | 8.2 |
| V3 | Agent Balance Double-Credit Logic Error | High | High | **🔴 Critical** | 8.4 |
| V5 | PIN Brute-Force on Cash-Out | High | High | **🔴 Critical** | 8.8 |
| V8 | Insufficient Blocked Balance Validation | High | High | **🔴 Critical** | 9.1 |
| V6 | Agent Creating Fake Users for Commission Farming | Medium | High | **🟡 High** | 7.5 |
| V7 | Settlement Timing Attack — Multiple Settlements | Medium | High | **🟡 High** | 7.2 |
| V9 | Missing Agent Authorization on User Operations | Medium | High | **🟡 High** | 7.5 |
| V4 | Commission Farming via Inflated Amounts | Medium | Medium | **🟡 High** | 6.5 |
| V10 | Audit Log Spoofing via Client-Supplied Data | High | Medium | **🟡 High** | 5.3 |
| V11 | Commission Summary Calculation Error | Medium | Low | **🟢 Medium** | 3.5 |
| V12 | Wallet Balance Rounding Errors | Low | Low | **🟢 Low** | 2.8 |

### Risk Heat Map / خريطة الحرارة

```
Impact →
  High     V3 V5 V8 V1    V2 V6 V7 V9
  Medium   V10            V4
  Low      V12            V11
           Low            Medium          High
                    Likelihood →
```

---

## 4. Mitigation Strategies / استراتيجيات التخفيف

### V1: Agent-User Collusion — Fake Cash-In
**Severity: 🔴 Critical**

| # | Mitigation | Priority |
|---|-----------|----------|
| M1.1 | **Implement cash inventory tracking**: Each agent must have a `cash_inventory` table tracking physical cash on hand. Cash-in transactions reduce inventory; cash-out transactions increase it. Reconcile daily. | P0 |
| M1.2 | **Require biometric/KYC verification at transaction time**: User must provide fingerprint or face scan at the agent location for cash-in above a threshold (e.g., 100,000 SYP). | P1 |
| M1.3 | **Geofencing + IP verification**: Verify that the agent's device GPS matches their registered location(s). Flag transactions from unexpected locations. | P1 |
| M1.4 | **Machine learning anomaly detection**: Monitor for patterns like same user being served by multiple agents, or circular transaction patterns between the same set of users/agents. | P1 |
| M1.5 | **Random spot-check audits**: Physical verification of cash-in transactions for a random sample of agent transactions. | P2 |

### V2: Idempotency Key Race Condition — Double-Spending
**Severity: 🔴 Critical**

| # | Mitigation | Priority |
|---|-----------|----------|
| M2.1 | **Move idempotency check INSIDE the transaction**: Use `INSERT ... ON CONFLICT DO NOTHING` or a database-level unique constraint on `idempotency_key` in the `AgentTransaction` table. Perform the check within the `DB::transaction()` closure. | P0 |
| M2.2 | **Add database unique index**: `ALTER TABLE agent_transactions ADD UNIQUE INDEX idx_idempotency (idempotency_key);` — this guarantees that even with concurrent requests, only one insert succeeds. | P0 |
| M2.3 | **Use pessimistic locking on idempotency check**: Wrap the idempotency check in `lockForUpdate()` on a dedicated lock table or use Redis distributed locks. | P2 |

### V3: Agent Balance Double-Credit Logic Error
**Severity: 🔴 Critical**

| # | Mitigation | Priority |
|---|-----------|----------|
| M3.1 | **Remove immediate balance increment from CashInService**: The agent should NOT receive any balance increment at transaction time. The fee should only be recorded as `accrued` commission and credited at settlement time. Remove Line 63 (`$agentWallet->increment(...)`) from `CashInService`. | P0 |
| M3.2 | **Alternative**: If immediate crediting is required, subtract the credited amount from the settlement total in `triggerSettlement()` to avoid double-crediting. | P1 |

### V5: PIN Brute-Force on Cash-Out
**Severity: 🔴 Critical**

| # | Mitigation | Priority |
|---|-----------|----------|
| M5.1 | **Implement rate limiting**: Maximum 5 PIN attempts per user per 15 minutes. Use `Cache::increment()` with TTL or Redis rate limiter. Return `429 Too Many Requests` after threshold. | P0 |
| M5.2 | **Account lockout after N failed attempts**: Lock the user's account for 30 minutes after 10 consecutive failed PIN attempts. Require identity verification to unlock. | P0 |
| M5.3 | **CAPTCHA/reCAPTCHA**: Require CAPTCHA after 3 failed attempts in a session. | P1 |
| M5.4 | **Progressive delay**: Introduce incremental delay (1s, 2s, 4s, 8s, ...) between PIN validation attempts from the same IP or agent. | P1 |
| M5.5 | **Separate PIN field from password**: The current implementation checks `Hash::check($pin, $user->password)`, which means the PIN IS the user's password. Create a dedicated `pin` column using a slower hash algorithm (e.g., Argon2id with higher cost) specifically for cash-out PIN. | P0 |

### V8: Insufficient Blocked Balance Validation
**Severity: 🔴 Critical**

| # | Mitigation | Priority |
|---|-----------|----------|
| M8.1 | **Add blocked balance check before decrementing**: Validate `$agentWallet->blocked >= $amount->fils()` before Line 62. Throw `\RuntimeException('Insufficient blocked balance')` if check fails. | P0 |
| M8.2 | **Use `decrement()` with check**: Some DB engines support `UPDATE ... SET blocked = blocked - ? WHERE blocked >= ?`. Consider using raw SQL for atomic conditional decrement. | P1 |

### V6: Agent Creating Fake Users for Commission Farming
**Severity: 🟡 High**

| # | Mitigation | Priority |
|---|-----------|----------|
| M6.1 | **Enhanced KYC for user registration**: Require government-issued ID verification, selfie matching, and phone number verification (OTP) for all new user accounts. | P1 |
| M6.2 | **Detect circular transaction patterns**: Implement analytics to identify transaction cycles (e.g., A→B→C→A) and flag agents involved in suspicious cycles. | P1 |
| M6.3 | **Limit daily unique users per agent**: Restrict the number of distinct users an agent can serve per day (e.g., max 50 unique users). | P1 |
| M6.4 | **Minimum holding period**: Funds deposited via agent cash-in cannot be withdrawn for a minimum period (e.g., 24 hours) — reduces the velocity of circular farming. | P2 |

### V7: Settlement Timing Attack
**Severity: 🟡 High**

| # | Mitigation | Priority |
|---|-----------|----------|
| M7.1 | **Reorder operations in settlement**: Update commission statuses to `settled` **before** incrementing agent balance. If the transaction fails after the update, the balance increment can be safely retried. | P0 |
| M7.2 | **Use idempotency for settlements**: Generate a unique settlement ID per period. Check if settlement already exists for the period before processing. | P1 |
| M7.3 | **Add a "processing" status**: Set commissions to `processing` status before the balance increment, then to `settled` after. Handle crashed transactions by detecting `processing` entries older than N minutes and rolling them back. | P2 |

### V9: Missing Agent Authorization on User Operations
**Severity: 🟡 High**

| # | Mitigation | Priority |
|---|-----------|----------|
| M9.1 | **Agent-User relationship table**: Create an `agent_user_assignments` table linking agents to authorized users. Agents can only transact with assigned users. | P1 |
| M9.2 | **User opt-in/consent**: Require users to explicitly authorize an agent to perform cash-out operations on their behalf (e.g., via OTP or in-app approval). | P1 |
| M9.3 | **Transaction confirmation OTP**: For cash-out, send an OTP to the user's registered phone number that must be entered at the agent terminal to complete the transaction. | P1 |

### V4: Commission Farming via Inflated Amounts
**Severity: 🟡 High**

| # | Mitigation | Priority |
|---|-----------|----------|
| M4.1 | **Cap commission at maximum daily limit**: Do not allow a single transaction to earn more commission than what the daily limit would proportionally allow. | P1 |
| M4.2 | **Tiered commission rates**: Lower commission rates for very large transactions to reduce incentive for inflation. | P2 |
| M4.3 | **Random verification calls**: Implement a system where a subset of large transactions require a phone call to the user to verify the amount before finalizing. | P2 |

### V10: Audit Log Spoofing
**Severity: 🟡 High**

| # | Mitigation | Priority |
|---|-----------|----------|
| M10.1 | **Capture server-side IP only**: Use `$request->ip()` from trusted proxy headers (X-Forwarded-For only if behind a known proxy), NOT the client-supplied `User-Agent` as sole source. | P1 |
| M10.2 | **Authenticated audit fields**: Add the authenticated user/agent ID from the JWT session, which cannot be spoofed, to every audit log entry. | P0 |
| M10.3 | **Remove cookie-based fingerprint**: The `wap_fp` cookie is client-controlled. Replace with a server-generated session fingerprint that is bound to the JWT token. | P1 |

### V11: Commission Summary Calculation Error
**Severity: 🟢 Medium**

| # | Mitigation | Priority |
|---|-----------|----------|
| M11.1 | **Fix the pending calculation**: Change Line 70 to return just the sum of `accrued` commissions directly: `'pending_commission' => AgentCommission::where('agent_id', $agent->id)->where('status', 'accrued')->sum('amount')`. | P2 |

### V12: Wallet Balance Rounding Errors
**Severity: 🟢 Low**

| # | Mitigation | Priority |
|---|-----------|----------|
| M12.1 | **Store wallet balance in fils (integer)**: Store all monetary values as integers representing the smallest currency unit (fils/satoshis). This eliminates floating-point and decimal rounding issues entirely. | P2 |
| M12.2 | **Use consistent rounding**: Replace `(int) round(...)` with `intval(floor(...))` for debit operations (always round down to protect user balance) and `intval(ceil(...))` for credit operations (always round up). | P2 |

---

## 5. Top 5 Security Recommendations / أهم 5 توصيات أمنية

### 🔴 Recommendation 1: Fix Idempotency Race Condition (V2)
**Priority: IMMEDIATE — CVSS 8.2**

Move the idempotency key check **inside** the database transaction and add a **UNIQUE constraint** on `agent_transactions.idempotency_key`. This is the most likely source of a double-spending attack that could result in immediate financial loss.

```sql
ALTER TABLE agent_transactions ADD CONSTRAINT uq_idempotency UNIQUE (idempotency_key);
```

In code:
```php
return DB::transaction(function () use (...) {
    if ($idempotencyKey) {
        $existing = AgentTransaction::where('idempotency_key', $idempotencyKey)
            ->lockForUpdate()
            ->first();
        if ($existing) {
            return $existing;
        }
    }
    // ... rest of logic
});
```

---

### 🔴 Recommendation 2: Fix Agent Balance Double-Credit Logic (V3)
**Priority: IMMEDIATE — CVSS 8.4**

Remove the agent wallet balance increment in `CashInService::cashIn()` Line 63. The commission should only be paid out at settlement time via `triggerSettlement()`. Currently, the agent receives:
- Immediate credit: `$amount->fils() - $netAmount` (the fee) → agent wallet balance
- Settlement credit: `$totalCommission` (the same fee) → agent wallet balance again

**Fix:** Delete or comment out Line 62-63 in CashInService.php:
```php
// BUG: Remove these two lines — agent is double-credited
// $agentWallet->decrement('blocked', $amount->fils());
// $agentWallet->increment('balance', $amount->fils() - $netAmount);
```

---

### 🔴 Recommendation 3: Implement PIN Brute-Force Protection (V5)
**Priority: IMMEDIATE — CVSS 8.8**

Add rate limiting, account lockout, and CAPTCHA to the cash-out endpoint. Also, create a dedicated `pin` column separate from `password` to allow different security policies for each.

```php
// In CashOutService::validatePin()
private function validatePin(User $user, string $pin): void
{
    $cacheKey = 'pin_attempts_' . $user->id;
    $attempts = (int) Cache::get($cacheKey, 0);
    
    if ($attempts >= 5) {
        throw new \RuntimeException('Account temporarily locked. Try again later.');
    }
    
    if (blank($user->pin_hash)) {
        throw new \RuntimeException('User has no PIN set');
    }
    
    if (!Hash::check($pin, $user->pin_hash)) {
        Cache::increment($cacheKey);
        Cache::expire($cacheKey, 900); // 15 minutes
        throw new \RuntimeException('Invalid PIN');
    }
    
    // Reset counter on success
    Cache::forget($cacheKey);
}
```

---

### 🔴 Recommendation 4: Add Blocked Balance Validation (V8)
**Priority: IMMEDIATE — CVSS 9.1**

Validate that the agent has sufficient blocked balance **before** decrementing. Currently, an agent with zero blocked balance can perform unlimited cash-in transactions.

```php
// In CashInService::cashIn(), before Line 62:
if ($agentWallet->blocked < $amount->fils()) {
    throw new \RuntimeException('Insufficient blocked balance. Please deposit funds first.');
}
```

Additionally, consider using an atomic DB operation:
```php
$updated = DB::update(
    'UPDATE wallets SET blocked = blocked - ? WHERE id = ? AND blocked >= ?',
    [$amount->fils(), $agentWallet->id, $amount->fils()]
);
if ($updated === 0) {
    throw new \RuntimeException('Insufficient blocked balance');
}
```

---

### 🟡 Recommendation 5: Agent-User Authorization + User OTP (V9 + V1)
**Priority: 24 Hours — CVSS 7.5 / 8.6**

Implement an agent-user relationship layer and user confirmation for transactions:

1. **Create `agent_user_assignments` table** with `agent_id`, `user_id`, `status`, `approved_at`.
2. **Verify assignment** before processing any cash-in or cash-out.
3. **Send OTP** to user's registered phone for every cash-out transaction and for cash-in transactions above 100,000 SYP.
4. **Require OTP confirmation** in the API request (add `otp` field to cash-out request).

This single recommendation mitigates three major vectors: unauthorized agent operations, fake cash-in collusion, and commission farming via fake users.

---

## 6. Code-Level Vulnerabilities Found / الثغرات على مستوى الكود

### 6.1 CashInService.php — تحليل مفصل

| Line(s) | Issue | Severity | Description |
|---------|-------|----------|-------------|
| **24-29** | **Idempotency check outside transaction — TOCTOU** | 🔴 Critical | Two concurrent requests with the same `idempotency_key` can both pass the initial check (which happens before `DB::transaction()`), resulting in duplicate transactions. Both requests will enter the transaction block and create separate records. |
| **48-49** | **Fee calculation rounding** | 🟡 Medium | `(int) round($amount->fils() * $agent->commission_rate)` — using `round()` on fils amounts can produce unexpected results for large numbers. The cast `(int)` truncates rather than floors, potentially rounding up fees against the user. |
| **62** | **Missing blocked balance check** | 🔴 Critical | `$agentWallet->decrement('blocked', $amount->fils())` — no validation that `blocked >= amount`. Agents with insufficient or zero blocked balance can still process cash-in, creating unbacked wallet credits. |
| **63** | **Double-credit logic error** | 🔴 Critical | `$agentWallet->increment('balance', $amount->fils() - $netAmount)` — this immediately credits the agent's balance with the fee. But the fee is also accrued via `commissionService->accrueCashInFee()` (Line 81) and will be credited AGAIN at settlement time. The agent should only be credited once. |
| **80-82** | **Conditional commission accrual** | 🟢 Low | `if ($feeAmount > 0)` — If commission_rate is 0, no commission record is created. The agent balance is also NOT incremented (since `$amount->fils() - $netAmount` = 0 when fee is 0). This is technically correct but should be documented. |
| **85-91** | **Client-supplied audit data** | 🟡 High | `request()->ip()`, `request()->userAgent()`, and `request()->cookie('wap_fp', '')` are all client-controlled values. An attacker can inject arbitrary data into audit logs, rendering them unreliable for forensic analysis. The `fingerprint` field from a cookie is particularly dangerous. |
| **113-120** | **Timezone-dependent daily limit** | 🟢 Low | `whereDate('created_at', today())` — uses the application's configured timezone for `today()`. If the application timezone differs from the agent's operational timezone, limits could reset at unexpected times, either allowing extra transactions or blocking legitimate ones. |

### 6.2 CashOutService.php — تحليل مفصل

| Line(s) | Issue | Severity | Description |
|---------|-------|----------|-------------|
| **25-30** | **Idempotency check outside transaction — TOCTOU** | 🔴 Critical | Same issue as CashInService. Two concurrent cash-out requests with the same `idempotency_key` can both proceed, resulting in double-debit from user wallet and double-credit to agent. |
| **50** | **Fee on cash-out charged to user** | 🟢 Low | `totalDeduction = $amount->fils() + $feeAmount` — the user pays the fee for cash-out (deducted from their wallet). The agent receives the full `$amount->fils()`. This is a design choice and not a vulnerability per se, but means the user pays for both cash-in (via reduced `netAmount`) and cash-out (via `totalDeduction`). Agent pays nothing. This could be a compliance/fairness issue. |
| **114-118** | **Rounding in balance validation** | 🟢 Low | `(int) round($wallet->balance * (10 ** $amount->currency()->decimals()))` — converting decimal balance to fils using `round()` and `(int)` cast can introduce small rounding errors. For SYP with 2 decimals, `balance=1000.0050` would round to 100001 fils instead of 100000. |
| **135-141** | **PIN checked against password field** | 🔴 Critical | `Hash::check($pin, $user->password)` — this treats the user's password as the PIN. If the user has a strong login password, they must enter it at the agent terminal (losing confidentiality). If they set a simple 4-digit PIN, their login password is equally weak. These **must** be separate fields. |
| **139** | **No rate limiting on PIN validation** | 🔴 Critical | Unlimited PIN attempts. An attacker can brute-force a user's PIN by repeatedly calling the cash-out endpoint with different PIN values. No account lockout, no CAPTCHA, no progressive delay. |
| **66** | **Agent balance increment without blocked check** | 🟡 Medium | `$agentWallet->increment('balance', $amount->fils())` — the agent receives the full cash-out amount in their balance. There is no corresponding `blocked` check or movement. This could allow an agent to inflate their balance through repeated cash-outs with colluding users. |

### 6.3 CommissionService.php — تحليل مفصل

| Line(s) | Issue | Severity | Description |
|---------|-------|----------|-------------|
| **70** | **Pending commission calculation error** | 🟢 Medium | `'pending_commission' => $pending - $settled` — `$pending` queries `accrued` commissions only; `$settled` queries `settled` commissions only. Since these are mutually exclusive sets, subtracting one from the other yields incorrect results. Should be just the sum of `accrued` commissions. |
| **117-122** | **Lock scope limitation** | 🟡 Medium | `lockForUpdate()` is applied within `DB::transaction()`, but the commission records were created in separate transactions. If another process creates new commissions while `triggerSettlement()` is running, the new records might not be properly locked, potentially leading to missed or duplicated commission handling. |
| **148-155** | **Balance increment before status update — crash risk** | 🔴 Critical | The agent balance is incremented on Line 155 (`$agent->increment('balance', $totalCommission)`) **before** commission statuses are updated on Line 148-153. If the system crashes between Lines 155 and 148, the agent's balance is increased but commissions remain `accrued`. On retry, the agent gets double-paid. The status update should happen first, or both should be in the same atomic operation. |
| **155** | **Direct agent balance increment — no audit trail** | 🟡 Medium | `$agent->increment('balance', $totalCommission)` — this direct increment bypasses the wallet model and creates no audit log entry. If the settlement amount is incorrect, there is no traceability for the balance change. Should use a proper money movement method that creates a Transaction record. |
| **134** | **No validation of settlement period uniqueness** | 🟡 Medium | The method does not check if a settlement has already been processed for the given period. If somehow the same accrued commissions are fetched twice (due to the crash bug above or race condition), duplicate settlements would occur. Should add `AgentSettlement::where('agent_id', $agent->id)->where('period_end', $periodEnd)->exists()` check. |

### 6.4 AgentController.php — تحليل مفصل

| Line(s) | Issue | Severity | Description |
|---------|-------|----------|-------------|
| **161** | **Agent lookup by authenticated user** | 🟡 Medium | `Agent::where('user_id', $request->user()->id)->first()` — this assumes a 1:1 mapping between User and Agent. If the User model has multiple Agent profiles (not prevented by the schema), the wrong agent could be selected. Should use `where('user_id', $request->user()->id)->where('status', 'active')->firstOrFail()`. |
| **54-58, 93-99** | **Missing agent-user authorization check** | 🟡 High | The controller accepts any `user_id` and processes the transaction without verifying that the agent is authorized to serve that specific user. Any active agent can cash-in or cash-out any active user. |
| **67-71, 107-111** | **RuntimeException handling — information disclosure** | 🟢 Low | The `catch (\RuntimeException $e)` block returns `$e->getMessage()` directly in the JSON response. Generic error messages are fine, but if any exception contains sensitive data (e.g., SQL errors if validation is bypassed), it could be leaked. Consider whitelisting known error messages. |
| **159-168** | **No fallback for missing agent** | 🟢 Low | `getAgent()` throws a generic RuntimeException with message 'Agent profile not found'. This is a 500 error scenario but the frontend receives a 422 (validation error) with `success: false`. The HTTP status code is misleading — should return 403 or 404. |
| **Entire file** | **No CSRF protection evident** | 🟢 Low | While Laravel's `VerifyCsrfToken` middleware typically handles this, if this controller is in an API routes file with `api` middleware group (which typically excludes CSRF), there is no CSRF protection. This is acceptable for token-authenticated APIs but worth noting. |

### 6.5 Agent Model — تحليل النموذج

| Issue | Severity | Description |
|-------|----------|-------------|
| **`commission_rate` stored as `decimal:4`** | 🟢 Low | A rate of `0.0100` (1%) stored as `decimal:4` can represent values from 0.0000 to 0.9999. If the rate is set to 0.0000, the agent works for free (but commission accrual is skipped via `if ($feeAmount > 0)`). If set very high (e.g., 1.0000 = 100%), the fee equals the entire amount, making transactions impossible. Input validation should cap this at a reasonable maximum (e.g., 0.0500 = 5%). |
| **`balance` stored as `integer` on Agent but `decimal:4` on Wallet** | 🟡 Medium | The `Agent` model stores `balance` as `integer` (Line 37 of Agent.php), but the `Wallet` model stores `balance` as `decimal:4` (Line 28 of Wallet.php). These are different representations of the same concept (agent's money). The agent's `balance` in the `agents` table and the agent's wallet `balance` in the `wallets` table could drift out of sync because they use different data types and are updated in different code paths. One source of truth should be chosen. |
| **`SoftDeletes` on Agent** | 🟢 Low | Soft-deleting an agent does not invalidate their existing transactions or close their wallet. A soft-deleted agent could potentially still process transactions if the service layer doesn't check `$agent->trashed()`. The `validateAgentActive` method only checks `status`, not `deleted_at`. |

---

## Appendix A: CVSS 4.0 Scoring Reference
## الملحق أ: مرجع تسجيل CVSS 4.0

| Score | Severity |
|-------|----------|
| 9.0–10.0 | 🔴 Critical |
| 7.0–8.9 | 🟡 High |
| 4.0–6.9 | 🟢 Medium |
| 0.1–3.9 | ⚪ Low |

---

## Appendix B: Vulnerability Inventory — Quick Reference
## الملحق ب: جرد الثغرات — مرجع سريع

| ID | Component | Type | Severity | Fix Priority |
|----|-----------|------|----------|-------------|
| V2 | CashInService / CashOutService | Race Condition | 🔴 Critical | P0 — Before next deploy |
| V3 | CashInService | Logic Error | 🔴 Critical | P0 — Before next deploy |
| V5 | CashOutService | Brute Force | 🔴 Critical | P0 — Before next deploy |
| V8 | CashInService | Missing Validation | 🔴 Critical | P0 — Before next deploy |
| V7 | CommissionService | Race/Crash | 🟡 High | P1 — Within 24h |
| V9 | AgentController | Auth Bypass | 🟡 High | P1 — Within 24h |
| V1 | Whole System | Collusion | 🟡 High | P1 — Within 1 week |
| V6 | Whole System | Commission Farming | 🟡 High | P1 — Within 1 week |
| V4 | CashInService / CashOutService | Inflation | 🟡 High | P1 — Within 1 week |
| V10 | CashInService / CashOutService | Audit Integrity | 🟡 High | P1 — Within 1 week |
| V11 | CommissionService | Logic Error | 🟢 Medium | P2 — Within 2 weeks |
| V12 | CashOutService | Rounding | 🟢 Low | P3 — Next sprint |

---

## Document Sign-off / اعتماد المستند

| Role | Name | Date | Signature |
|------|------|------|-----------|
| 👑 CEO | *(pending)* | 2026-05-31 | |
| 🔬 Lead Developer | *(pending)* | 2026-05-31 | |
| 🛡️ Security Lead | *(pending)* | 2026-05-31 | |

---

---

## 7. Admin Oversight Controls / ضوابط الرقابة الإدارية

**تاريخ الإضافة:** 2026-06-01  
**المصدر:** Agent Oversight APIs (Task AD7)

تم إضافة طبقة رقابة إدارية جديدة عبر واجهات API تحت مسار `/api/v1/admin/` تسمح للمشرفين بمراقبة وإدارة الوكلاء بشكل فعال. فيما يلي أثر هذه الضوابط على مخاطر الاحتيال المحددة في التقييم:

### 7.1 أثر الضوابط على نواقل التهديد

| الناقل | النوع | الأثر | الضابط الجديد |
|--------|-------|-------|---------------|
| V1 — تواطؤ الوكيل والمستخدم | Collusion | ✅ مخفّف | `GET /agents/{id}/commissions` يسمح للمشرف بمراجعة نمط عمولات الوكيل واكتشاف الشذوذ. `GET /fraud-alerts` يُنبّه المشرف تلقائياً عند وجود سلوك مشبوه. |
| V6 — زراعة العمولات بمستخدمين وهميين | Commission Farming | ✅ مخفّف | `GET /agents` مع فلترة بالرصيد والحالة يساعد في اكتشاف الوكلاء ذوي النشاط غير الطبيعي. `GET /agents/{id}/commissions` يكشف تضخم العمولات. |
| V7 — هجوم توقيت التسوية | Settlement Timing | ✅ مخفّف | `POST /settlements/{id}/approve` يضيف طبقة موافقة إدارية على التسويات مع تسجيل قيد في دفتر الأستاذ (Ledger Entry) للامتثال WORM. |
| V11 — خطأ حساب العمولات | Logic Error | ✅ مخفّف | `POST /commissions/{id}/approve` يتيح للمشرف اعتماد العمولات يدوياً بعد المراجعة، مما يمنع الاعتماد التلقائي الخاطئ. |

### 7.2 آليات الرقابة الجديدة

1. **دفتر الأستاذ (Ledger)**: كل عملية اعتماد مالي (عمولة أو تسوية) تُسجل كقيد جديد في جدول `ledger_entries` مع توثيق:
   - الرصيد قبل وبعد العملية
   - الجهة المصادقة (المشرف)
   - الاتجاه (دائن/مدين)
   - مرجع متعدد الأشكال (Morph) للربط بالسجل الأصلي

2. **التنبيهات الأمنية**: جدول `fraud_alerts` يتيح للمشرفين:
   - تصنيف التنبيهات حسب الخطورة (حرج/عال/متوسط/منخفض)
   - تتبع حالة كل تنبيه (مفتوح/قيد التحقيق/تم الحل)
   - تسجيل قرار الإجراء المتخذ

3. **الصياحيات الدقيقة**: كل نقطة API محمية بصلاحية محددة:
   - `agents:view` — عرض بيانات الوكلاء
   - `agents:commissions` — عرض سجل العمولات
   - `commissions:approve` — اعتماد العمولات
   - `agents:finance` — عرض البيانات المالية
   - `finance:approve` — اعتماد التسويات
   - `security:view` — عرض التنبيهات الأمنية
   - `security:resolve` — حل التنبيهات الأمنية

4. **الامتثال WORM**: جميع القيود المالية في `ledger_entries` هي Write-Once Read-Many:
   - لا يوجد `update` أو `delete` على السجلات المالية
   - استخدام `DB::transaction` مع `lockForUpdate()` لمنع سباقات التوقيت
   - التغييرات المالية تُسجل كسجلات جديدة وليس تعديلات

### 7.3 توصيات إضافية

على الرغم من التحسينات الجديدة، تبقى التوصيات التالية قائمة وتتطلب معالجة منفصلة:

| المعرف | التوصية | الأولوية |
|--------|---------|----------|
| M2.1 | نقل التحقق من idempotency_key داخل المعاملة | P0 |
| M3.1 | إزالة الزيادة المزدوجة لرصيد الوكيل | P0 |
| M5.1 | تطبيق تحديد معدل PIN | P0 |
| M8.1 | إضافة التحقق من الرصيد المحجوز | P0 |
| M9.1 | إنشاء جدول علاقة الوكيل-المستخدم | P1 |

*تم تحديث المستند بواسطة فريق التطوير — 2026-06-01*
*Document updated by Development Team — 2026-06-01*

*Document generated by Pentest Agent — Security Research Team*
*تم إنشاء المستند بواسطة وكيل الاختراق — فريق البحث الأمني*
