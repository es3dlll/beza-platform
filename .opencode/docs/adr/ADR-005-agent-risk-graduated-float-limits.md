# ADR-005: Agent Risk Model — Graduated Float Limits

## Status
Accepted

## Context
Beza Financial OS relies on a network of agents (typically small shop owners, petrol stations, and mobile credit vendors) who hold float cash to serve end-users. Float is the cash balance an agent holds to disburse to users (cash-out) and collects from users (cash-in). The risk: an agent could abscond with the float, leaving Beza liable.

Three risk models were evaluated:

**Model 1 — Fixed limit for all agents:** Every agent receives the same maximum float, e.g., SYP 20,000,000 (~$1,500 USD). Simple to implement but fails on two fronts: new agents get too much risk, and high-performing agents are artificially constrained.

**Model 2 — Graduated limits based on agent maturity:** Float limits increase with agent tenure, transaction history, and dispute record. A new agent starts small, and proven trust unlocks higher limits.

**Model 3 — No limit (trust-based):** Agents can request any float amount. Relies on community reputation. Impractical for compliance and catastrophic loss potential.

Syria-specific considerations:

- **Rural agent profile:** Agents in rural governorates (Idlib, Deir ez-Zor, Al-Hasakah, Rural Damascus) are often small shop owners with daily revenues of SYP 50,000-200,000 (~$4-16 USD). A float of SYP 20M is 100-400x their daily revenue — far beyond what they can personally cover if lost. A graduated model limits per-agent exposure to amounts proportional to their economic standing.

- **Informal economy:** Many agents lack formal bank accounts or credit history. Beza cannot pull credit bureau data (no centralized bureau in Syria). Agent history on the platform is the only reliable signal.

- **Dispute resolution:** Agent disputes (user claims agent stole their deposit) are adjudicated manually in V1. A graduated model limits blast radius — a new agent can only cause SYP 500K damage before being flagged.

- **Float funding:** Agents fund their float via bank transfers (slow, 1-3 business days) or via cash-in from their own users. High float limits mean agents must tie up more capital, which they may not have. Graduated limits align float requirements with actual transaction volume.

- **Bonding feasibility:** Tier 4 requires a bond (insurance or cash deposit held by Beza). Syrian insurance companies (e.g., Syrian Insurance Co., Al-Aqeelah) offer fidelity guarantees, but the market is nascent and may not support large-scale agent bonding in V1.

## Decision
Adopt **Model 2 — Graduated float limits** with four tiers:

| Tier | Duration Active | Conditions | Max Float (SYP) | Max Daily Cash-Out (SYP) |
|------|----------------|------------|-----------------|--------------------------|
| 1    | 0-30 days      | KYC completed | 2,000,000 | 500,000 |
| 2    | 31-90 days     | < 3 disputes  | 10,000,000 | 2,000,000 |
| 3    | 91+ days       | < 5 disputes  | 20,000,000 | 5,000,000 |
| 4    | 1+ year        | < 5 disputes, bonded (SYP 5M minimum) | 50,000,000 | 10,000,000 |

**Tier upgrade logic:**
- Upgrades are automatic when conditions are met (checked daily by scheduler)
- An agent can request a faster upgrade by providing a guarantor (existing Tier 3+ agent who co-signs)
- Disputes resolved in favor of the agent do not count toward the limit
- Any dispute resolved against the agent resets the dispute counter to zero (not incremental — one strike and you're back to zero)
- Tier demotion: if an agent exceeds 5 disputes after reaching Tier 3, they are demoted to Tier 2 for 30 days

**Tier downgrade logic:**
- Manual downgrade by risk team at any time (e.g., fraud suspicion)
- Automatic downgrade if agent is inactive for 90 consecutive days (reverts to Tier 1)
- Agent can appeal downgrade via the dispute resolution process

**Cash-out limits are daily (rolling 24-hour window) and cannot exceed float balance:**
```
$dailyCashOut = min($agent->maxDailyCashOut, $agent->floatBalance);
```

## Consequences
**Positive:**
- New agent exposure is limited to SYP 2M (~$160 USD) — acceptable loss if an agent disappears
- Graduated tiers incentivize good behavior: agents want to reach Tier 3 for higher limits and lower per-transaction friction
- No credit bureau dependency — limits are based entirely on observable, platform-native signals (tenure, disputes, transaction volume)
- Syrian rural reality: a new agent may only have SYP 500K of their own capital to float; Tier 1 aligns with their capacity
- Bonding requirement at Tier 4 shifts residual risk to a third party (insurance company) — essential for the high-value tier

**Negative / Trade-offs:**
- Slow ramp frustrates high-volume agents: a shop in Damascus doing SYP 5M/day in month one cannot exceed Tier 1 limits
- Dispute counter logic is blunt — one fraudulent user claim against an agent resets their progress unfairly
- Tier 4 bonding requirement assumes Syrian insurance products exist and are affordable; if not, Tier 4 may be unreachable for most agents
- Sybil attack: an agent could register multiple accounts to circumvent Tier 1 limits — requires strong KYC (national ID verification, possibly biometrics in V2)

## Compliance
Enforced via:
1. `app/Modules/Agent/Services/FloatLimitService.php` — single source of truth for tier calculation; all float queries go through this service
2. Database constraints: `agent_tiers` table with `CHECK` constraints on `max_float` and `max_daily_cashout`
3. Scheduler: `app/Console/Commands/EvaluateAgentTiers.php` runs daily at 02:00 AST
4. Monitoring: alert when > 10% of Tier 1 agents exceed 80% of their max daily cash-out (indicates limit too low)
5. DataDog/Sentry: dispute resolution events logged with agent ID, tier at time of dispute, and outcome
6. Unit tests: `tests/Unit/Agent/TierProgressionTest.php` — covers 12 scenarios (upgrade, demotion, dispute counter reset)
