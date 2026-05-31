# Offline Behavior — Fraud Management

## Offline Strategy Overview

Fraud management is primarily a real-time, server-side function. However, Syria's network infrastructure (variable connectivity in rural areas, IDP camps, conflict-affected zones) requires thoughtful offline handling.

## Offline Scenarios

| Scenario                     | Frequency               | Duration         | Impact                      |
| ---------------------------- | ----------------------- | ---------------- | --------------------------- |
| Mobile device offline (user) | High (daily)            | Minutes to hours | Transactions queued locally |
| Agent POS offline            | Medium                  | Minutes to hours | Local rule engine fallback  |
| Backend service disruption   | Low                     | Minutes          | Degraded fraud screening    |
| Network outage (region)      | Medium (Syria-specific) | Hours to days    | Manual agent reconciliation |
| Syriatel/MTN network issue   | Medium                  | 30min–4h         | Delayed real-time screening |

## Client-Side (Mobile) Offline Behavior

### Fraud Rules Cached Locally

```
┌─────────────────────────────────────────────────────────────┐
│  MOBILE DEVICE — LOCAL RULE CACHE                           │
│                                                             │
│  Cache Contents:                                            │
│  • Top 30 most-frequently-triggered rules (compressed JSON) │
│  • Device fingerprint hash                                  │
│  • Last 10 transaction records (for velocity checks)        │
│  • User risk tier (cached from last server response)        │
│                                                             │
│  Cache Size: < 50KB per user                                │
│  Update Frequency: Every 24h or on rule change              │
│  Stale After: 7 days (fall back to server-only)            │
│                                                             │
│  When offline:                                              │
│  1. Transaction is screened against local rules              │
│  2. If local rules → PASS: transaction queued for server    │
│  3. If local rules → FLAG: transaction blocked locally       │
│  4. User sees: "Transaction held for security check"        │
│     (not "blocked" — it may pass server-side review)       │
└─────────────────────────────────────────────────────────────┘
```

### Queued Transaction Processing

```
┌─────────────────────────────────────────────────────────────┐
│  OFFLINE QUEUE (Mobile Device)                              │
│                                                             │
│  ┌────────────┐                                             │
│  │ LOCAL QUEUE│  ← Transactions stored in SQLite            │
│  └──────┬─────┘    Encrypted at rest                         │
│         │                                                    │
│         ▼                                                    │
│  ┌────────────────┐                                          │
│  │ WHEN ONLINE:   │                                          │
│  │ 1. Send batch   │                                          │
│  │ 2. Server       │                                          │
│  │    re-screens   │                                          │
│  │ 3. Returns      │                                          │
│  │    decision     │                                          │
│  └────────┬───────┘                                          │
│           ▼                                                   │
│  ┌──────────────────┐                                        │
│  │ Decision:        │                                        │
│  │ • Approved → OK  │   ← Transaction visible in history    │
│  │ • Blocked → Notif│   ← Push notification: "Transaction   │
│  │                  │      blocked for security reasons"    │
│  │ • Review → Pending│  ← Held until manual review          │
│  └──────────────────┘                                        │
│                                                             │
│  Queue Security:                                            │
│  • Max 10 transactions queued (anti-fraud: limit exposure)  │
│  • Max total value: 200,000 SYP (configurable)              │
│  • Expiry: 24h (unprocessed queue items expire)             │
│  • User notified of queue status in app                     │
└─────────────────────────────────────────────────────────────┘
```

### Mobile Offline Risk Mitigation

| Risk                                                           | Mitigation                                                            |
| -------------------------------------------------------------- | --------------------------------------------------------------------- |
| Fraudster goes offline to bypass real-time screening           | Local rules still apply; max queue value limit; max queue items limit |
| Stale rules on device                                          | Rules expire after 7 days; force online check for high-value txns     |
| Queued transaction approved locally but flagged server-side    | Transaction reversed if blocked on server reconciliation              |
| Device compromised, local rules tampered                       | Rules stored in tamper-proof area; device integrity check             |
| User creates multiple offline transactions to exploit velocity | Local velocity rules cached                                           |

## Agent POS Offline Behavior

### Local Rule Engine for Basic Fraud Checks

```
┌─────────────────────────────────────────────────────────────┐
│  AGENT POS DEVICE — OFFLINE MODE                            │
│                                                             │
│  POS devices (Android-based, Nabi/PAX terminals)            │
│  have a LOCAL rule engine for basic fraud checks:           │
│                                                             │
│  Local Rules (always active, even online):                  │
│  ┌────────────────────────────────────────────────────┐    │
│  │ Rule              │ Check                         │    │
│  │───────────────────────────────────────────────────│    │
│  │ POS-001: Max Cash │ Customer txn > 500,000 SYP   │    │
│  │ Out Per Txn       │ → require supervisor approval │    │
│  │───────────────────────────────────────────────────│    │
│  │ POS-002: Max Daily│ Customer total > 2M SYP/day  │    │
│  │ Customer Limit    │ → block                       │    │
│  │───────────────────────────────────────────────────│    │
│  │ POS-003: Agent    │ Float < 50,000 SYP → warn    │    │
│  │ Float Threshold   │                               │    │
│  │───────────────────────────────────────────────────│    │
│  │ POS-004: Velocity │ > 5 cash-outs in 10 min      │    │
│  │                   │ → require ID verification    │    │
│  │───────────────────────────────────────────────────│    │
│  │ POS-005: New      │ Customer registered < 24h ago│    │
│  │ Customer          │ → limit to 50,000 SYP        │    │
│  └────────────────────────────────────────────────────┘    │
│                                                             │
│  When offline:                                              │
│  • ALL local rules enforced (no server fallback)            │
│  • Transactions stored in local queue                       │
│  • Agent sees: "Offline mode" indicator                     │
│  • Max 50 offline transactions before forced online sync    │
│  • Daily offline limit: 500,000 SYP per agent               │
│  • Ops team alerted when agent in offline > 4 hours         │
└─────────────────────────────────────────────────────────────┘
```

### Agent POS Sync Protocol

```
┌─────────────────────────────────────────────────────────────┐
│  AGENT POS — ONLINE SYNC                                    │
│                                                             │
│  On reconnect:                                              │
│  1. Upload queued transactions (batch, encrypted)           │
│  2. Server re-screens each transaction with FULL fraud      │
│     engine (ML + all rules, not just local subset)          │
│  3. Server returns verdicts:                                │
│     • APPROVED: visible in transaction history              │
│     • BLOCKED: transaction reversed, agent AND customer     │
│       notified via SMS                                      │
│     • FLAGGED: held for manual review                       │
│  4. Server sends updated local rule set                     │
│  5. POS confirms receipt and clears processed queue         │
│                                                             │
│  Sync Priority:                                             │
│  1. High-value transactions (> 100,000 SYP)                 │
│  2. Oldest transactions first                               │
│  3. Agent-initiated reversal requests                       │
└─────────────────────────────────────────────────────────────┘
```

## Backend Service Degradation

### Fraud Engine Partial Outage

```
┌─────────────────────────────────────────────────────────────┐
│  BACKEND — DEGRADED MODE                                    │
│                                                             │
│  Component Down         │ Action                            │
│─────────────────────────┼───────────────────────────────────│
│ ML Scoring Service      │ Fall back to rules-only scoring   │
│ Rules Engine            │ Fall back to ML-only (if avail)   │
│ Feature Store           │ Use minimal features (amount,     │
│                         │ device, location only)           │
│ Database (read)         │ Use Redis cache for recent data   │
│ Database (write)        │ Queue decisions for batch write   │
│                         │ ⚠️ Risk: lost decision data       │
│                         │ → must replay from logs           │
│ Full outage             │ Fail open (approve all) OR        │
│                         │ fail closed (block all)           │
│                         │ → Policy decision: fail open with │
│                         │   amount cap (max 50,000 SYP)    │
└─────────────────────────────────────────────────────────────┘
```

### Fail-Open Policy

```
┌─────────────────────────────────────────────────────────────┐
│  FAIL-OPEN THRESHOLDS                                       │
│                                                             │
│  When fraud engine unavailable > 30 seconds:                │
│                                                             │
│  Transaction Value    │ Action                              │
│───────────────────────┼─────────────────────────────────────│
│ < 50,000 SYP         │ Approve (fail-open, low risk)        │
│ 50,000 - 250,000 SYP │ Hold and retry for 5 seconds         │
│                      │ → if still down, block               │
│ > 250,000 SYP        │ Block automatically                  │
│───────────────────────┼─────────────────────────────────────│
│                                                             │
│  All approved transactions during outage logged for         │
│  post-recovery batch screening. Any flagged post-hoc       │
│  will result in account freeze.                            │
└─────────────────────────────────────────────────────────────┘
```

## Data Consistency

| Scenario                                            | Approach                                                                | Rationale                                                     |
| --------------------------------------------------- | ----------------------------------------------------------------------- | ------------------------------------------------------------- |
| Queued txn approved locally, blocked server-side    | Transaction reversed; SMS notification sent                             | Server is source of truth                                     |
| Server-side fraud rule updated while device offline | Rules expire after 7 days; force online check for new rules             | Stale rules favor approval, which is acceptable for low value |
| Device lost/stolen with queued transactions         | Queue encrypted at rest; device integrity check; remote wipe capability | Security                                                      |
| POS device stolen with offline txns                 | Agent PIN required for each txn; device-level authentication            | Limit fraud window                                            |

## User Experience During Offline

### Mobile App Offline Indicators

```
┌─────────────────────────────────────────────────────────────┐
│  OFFLINE FRAUD UX — MOBILE APP                              │
│                                                             │
│  When offline transaction queued:                           │
│                                                             │
│  ┌────────────────────────────────────────────────┐        │
│  │ 📶 Offline — Transaction Queued                 │        │
│  │                                                 │        │
│  │ Your transaction is saved and will be processed │        │
│  │ automatically when you're back online.          │        │
│  │                                                 │        │
│  │ Amount: 25,000 SYP to Ahmed M.                  │        │
│  │ Status: ⏳ Queued (pending network)             │        │
│  │                                                 │        │
│  │ [View Queue (3 items)]  [Got it]               │        │
│  └────────────────────────────────────────────────┘        │
│                                                             │
│  When coming back online:                                   │
│  • Toast notification: "Processing queued transactions..."  │
│  • Brief loading indicator                                  │
│  • Success: "Your queued transactions have been processed"  │
│  • Block: "A transaction was blocked for security"         │
└─────────────────────────────────────────────────────────────┘
```

### Agent POS Offline Indicators

```
When offline:
┌────────────────────────────────────────┐
│  📶 OFFLINE MODE                        │
│  Transactions are stored locally        │
│  and will sync when network returns.    │
│                                         │
│  Items in queue: 12                     │
│  Total value: 340,000 SYP               │
│  Queue remaining: 38 / 50               │
└────────────────────────────────────────┘
```

## Regional Offline Considerations (Syria)

| Region                | Connectivity                      | Offline Risk Level | Strategy                                                           |
| --------------------- | --------------------------------- | ------------------ | ------------------------------------------------------------------ |
| Damascus              | Good (4G, fiber)                  | Low                | Standard online only                                               |
| Aleppo                | Moderate (4G, some outages)       | Medium             | Local cache + queue                                                |
| Rural areas           | Poor (2G/3G, intermittent)        | High               | POS local rules + extended queue                                   |
| IDP camps             | Variable (limited infrastructure) | Very High          | Max offline queue increased; SMS-based verification                |
| Opposition-held areas | Blocked/reduced                   | Very High          | Extended offline mode; manual reconciliation; paper-based fallback |
