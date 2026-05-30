# Failure Scenarios — Fraud Management

## Resilience Philosophy

Fraud prevention is a **safety-critical** system. Failures must be detected quickly, contained safely, and resolved systematically. Every component must have a defined failure mode that does not cascade.

## Failure Scenario Catalog

### 1. ML Model Drift

**Description:** ML model's prediction accuracy degrades over time as fraud patterns shift. Fraudsters adapt, new transaction types emerge, or economic conditions change.

**Detection:**
- AUC-ROC drops below 0.85 (alert triggered)
- Precision-recall curve shifts
- Feature importance distribution changes significantly
- False positive rate increases > 5% in 24h
- Fraud rate increases > 0.3% in 24h

**Impact:**
- Increased false positives (frustrated users)
- OR decreased fraud detection (increased losses)
- Typically one worsens while the other improves (precision-recall tradeoff)

**Root Causes in Syria:**
| Cause | Example |
|-------|---------|
| New fraud pattern | Fraudsters discover a new attack vector (e.g., fake agent receipts) |
| Economic shock | SYP devaluation changes transaction patterns (amount-based rules break) |
| Seasonal shift | Ramadan transactions differ significantly (higher volumes, charity) |
| Population movement | Displacement from one region to another changes location baselines |
| Regulatory change | New KYC requirements change user onboarding patterns |

**Mitigation:**
```
1. AUTOMATED: Daily drift detection on model metrics
2. AUTOMATED: Daily retraining with last 90 days data
3. MANUAL: Data scientist reviews feature importance weekly
4. PROCEDURE: If AUC < 0.85, fall back to rules-only scoring
5. PROCEDURE: If AUC < 0.80, rollback to previous model version
6. PROCEDURE: New model shadow-deployed for 24h before active
```

**Recovery:**
- Rollback to last known good model (within 5 minutes)
- Rules engine operates independently during model retraining
- Manual review queue increased until new model deployed

### 2. Rule Engine Cold Start

**Description:** New rules deployed without adequate testing, causing unexpected behavior (too aggressive or too permissive).

**Detection:**
- Rule hit rate significantly different from expected (e.g., > 5% or < 0.1%)
- False positive rate spike on specific rule
- Dashboard alert: "Rule [X] hit rate anomaly"

**Prevention:**
```
All new rules go through:
┌──────────┐    ┌──────────┐    ┌──────────┐    ┌──────────┐
│ SHADOW   │───▶│ MONITOR  │───▶│ LIMITED  │───▶│ FULL     │
│ MODE     │    │ (24h)    │    │ ROLLOUT  │    │ DEPLOY   │
│ (log     │    │          │    │ (10% txns)│    │ (100%)   │
│  only)   │    │          │    │          │    │          │
└──────────┘    └──────────┘    └──────────┘    └──────────┘
Duration: 24h    24h            48h             Permanent
```

**Mitigation:**
- Immediate rule deactivation (ops team dashboard: 1-click disable)
- Affected users notified and transactions released
- Rule returned to shadow mode for refinement

### 3. False Positive Storm

**Description:** A sudden spike in false positives affecting a large percentage of legitimate users.

**Triggers:**
- New rule deployed too aggressively
- ML model update misclassifies a legitimate pattern
- Feature data source changes (e.g., location service down)
- Time-based rule misaligned with Syrian holidays/work patterns
- Bulk user behavior change (e.g., pension payment day)

**Detection:**
- Automated alert: false positive rate > 10% (3σ from baseline)
- Alert within 5 minutes of onset
- Dashboard shows FP rate spike in real-time

**Impact:**
- Thousands of legitimate users frustrated
- Support team overwhelmed with appeals
- Reputational damage
- User churn

**Response Plan:**
```
┌─────────────┬──────────────────────────────────────┐
│ Time        │ Action                               │
├─────────────┼──────────────────────────────────────┤
│ T+0         │ Automated alert: FP rate > 3σ        │
│ T+2 min     │ Ops lead acknowledges alert           │
│ T+5 min     │ Identify root cause (rule/ML/feature) │
│ T+10 min    │ Disable/correct offending component   │
│ T+15 min    │ FP rate returns to normal            │
│ T+30 min    │ Affected users notified (apology SMS) │
│ T+60 min    │ Post-mortem begins                   │
│ T+24h       │ Root cause fixed + new safeguards    │
└─────────────┴──────────────────────────────────────┘
```

**Safeguards:**
- New rules: 24h shadow mode → 48h limited → full rollout
- Model updates: A/B test against 10% of traffic first
- Feature dependency monitoring (if location service down, disable location rules)
- "Kill switch" for immediate rule/model rollback

### 4. Adversarial Attack (Fraudsters Learn Rules)

**Description:** Fraudsters systematically probe the system to understand rules and evade detection.

**Attack Vectors in Syria:**
| Attack | Method | Evasion |
|--------|--------|---------|
| Low-value probing | Small transactions to map thresholds | Stay below radar (e.g., 49,999 SYP if threshold is 50K) |
| Device rotation | Use multiple devices | New device rule only catches first use |
| Time spreading | Transact over days, not hours | Velocity rules miss slow-moving fraud |
| Agent collusion | Insider helps fraudsters | Agent pattern rules required |
| Mule networks | Distributed transactions | Single-account velocity rules miss this |

**Detection:**
- Fraud rate increases despite stable ML metrics (adversarial adapting)
- "Near-miss" transactions cluster just below thresholds
- Same IP/device across multiple accounts (mule network)
- Unusual patterns of legitimate-to-flagged ratio

**Mitigation:**
```
LAYERED DEFENSE:
1. Feature obfuscation: Don't expose which features triggered
2. Model rotation: Deploy new model versions every 24-48h
3. Ensemble methods: Multiple models, voting system
4. Threshold randomization: Small random variation around fixed thresholds ±5%
5. Honeypot rules: Decoy rules that look exploitable but are monitored
6. Rate limiting: Limit API calls per IP/device/account
7. Behavioral features: Features fraudsters can't easily fake
   (typing patterns, scroll speed, app usage patterns)
```

**Recovery:**
- If adversarial attack detected → rotate all active models
- Increase manual review percentage temporarily
- Deploy honeypot rules to confirm attack pattern
- Update rule engine with adversarial-specific rules

### 5. Data Pipeline Delay

**Description:** Feature data arrives late or is missing, causing incorrect scoring.

**Scenarios:**
- Device fingerprint service timeout
- Location geocoding delay
- User profile data stale (replicated with lag)
- Transaction history not yet indexed for velocity calculation
- KYC verification data incomplete

**Impact:**
- Transactions scored with incomplete features → incorrect decisions
- Sicker: missing data may fall back to default values → systematic bias

**Mitigation:**
```
Feature availability requirements:
┌──────────────────────┬──────────┬─────────────────────┐
│ Feature              │ Required │ Fallback            │
├──────────────────────┼──────────┼─────────────────────┤
│ Amount               │ YES      │ N/A (always avail)  │
│ Sender ID            │ YES      │ N/A                 │
│ Recipient ID         │ YES      │ N/A                 │
│ Device fingerprint   │ OPTIONAL │ 0 score (no penalty)│
│ Location             │ OPTIONAL │ Derive from IP      │
│ User avg amount      │ OPTIONAL │ Global average      │
│ Velocity (30min)     │ OPTIONAL │ Velocity (24h) only │
───────────────────────┴──────────┴─────────────────────┘

Timeout handling:
- Feature extraction: 50ms timeout per feature group
- If timeout → use fallback value
- Log timeout for monitoring
- Alert if > 1% of requests experience timeout
```

### 6. Database / Cache Failure

**Description:** Core data stores become unavailable.

| Failure | Impact | Mitigation |
|---------|--------|------------|
| Redis (cache) down | Velocity checks fail, session data lost | Fall back to database queries (slower) |
| PostgreSQL (main) down | Cannot read historical data | Redis fallback for recent data; fail-open for new txns |
| Feature Store down | Cannot compute ML features | Rules-only mode (no ML) |
| Queue (RabbitMQ/Redis) down | Async alerts delayed | Direct alert dispatch as fallback |

### 7. Third-Party Service Failure

**Description:** External services used in fraud detection are unavailable.

| Service | Impact | Fallback |
|---------|--------|----------|
| Device fingerprint (e.g., FingerprintJS) | Cannot detect new/known devices | Skip new-device scoring. Flag for manual review all txns without fd |
| SMS gateway | Cannot send fraud alerts via SMS | Push notification + email only |
| Telecom API (SIM swap check) | Cannot detect recent SIM swaps | Assume no SIM swap. Flag high-value remittances for manual review |
| Geolocation API | Cannot verify location | Use IP-based country only. Flag for manual review |
| ML model server | Cannot compute ML score | Rules-only scoring |

### 8. Cascade Failure

**Description:** One failure triggers another, creating a domino effect.

**Example Cascade:**
```
Feature Store latency spikes → ML scoring times out → Fall back to rules-only
→ Rules engine overloaded (handles 2x traffic) → Rules engine latency spikes
→ All transaction screening delayed → Transactions queued → Queue fills
→ New transactions blocked → Users cannot transact → Reputational crisis
```

**Prevention:**
- Bulkheads: Each component runs in isolated process/container
- Circuit breakers: If ML scoring > 100ms, trip circuit → rules-only
- Backpressure: If queue > 10K items, reject new requests
- Graceful degradation: Components fail independently, not cascade
- Independent fallbacks: Rules fail → ML only. ML fails → rules only. Both fail → fail-open with limits.

### 9. False Sense of Security

**Description:** System appears to work (low fraud rate) but misses significant fraud.

**Detection:**
- Low fraud rate + low false positive rate can mean "rules are too permissive"
- Compare fraud rate to industry benchmarks
- Conduct periodic "red team" exercises — ethical hackers attempt fraud
- Analyze declined transactions (are we blocking the right things?)
- User-reported fraud that slipped through is a key metric

**Mitigation:**
- Independent validation team reviews fraud detection effectiveness quarterly
- Red team exercises every 6 months
- External fraud audit annually
- Benchmark against Syria market fraud rates

### 10. Syria-Specific Failure Scenarios

| Scenario | Risk | Mitigation |
|----------|------|------------|
| Syriatel/MTN network blackout | No connectivity → all txns offline | Extended offline queue; fail-open for small amounts |
| CBS system outage | Cannot file SARs | Queue SARs, file when system restores |
| SYP hyperinflation event | Amount-based rules break | Dynamic thresholds based on market rate |
| Public holiday (Eid, etc.) | Transaction patterns change drastically | Holiday mode: relaxed rules, manual review |
| Conflict escalation | Population displacement, network disruption | Regional degradation, extended offline mode |
| Electricity outage | Server/comms infrastructure affected | Multi-region hosting, backup power |
