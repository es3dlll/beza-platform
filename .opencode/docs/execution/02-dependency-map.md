# Dependency Map — Beza Platform V1

## Module Inventory

Beza V1 consists of 12 engineering modules plus 3 cross-cutting infrastructure services. Each module is a self-contained Laravel directory under `Modules/` with its own models, migrations, controllers, services, tests, and routes.

---

## Dependency Matrix

| Module | Depends On | Blocks | Shared Services | Syria-Specific Notes |
|--------|-----------|--------|-----------------|---------------------|
| **Identity** | — | ALL | Cache, Queue, SMS | Phone prefix +963, OTP via Syriatel SMPP, device binding for Syrian phones |
| **Wallet** | Identity | Agent, FX, Remittance, Bills, Merchant | CFE, Fraud, Rate Limiter | Dual-currency SYP/USD, tier limits based on CBS regulation |
| **CFE (Ledger)** | Wallet, FX, Settlement | Reconciliation | — | Chart of accounts must be CBS-compatible (Central Bank of Syria classification) |
| **Agent** | Identity, Wallet | — | Fraud, Compliance, Notification | Agent registration requires Syrian business registration (سجل تجاري) |
| **FX** | Wallet | Remittance | CFE, Rate Cache | CBS daily rate feed, spread caps regulated by CBS (max 3%) |
| **Remittance** | FX, Wallet, Agent | — | Compliance, Fraud, CBS Reporting | OFAC/EU/UN sanction screening required for diaspora senders |
| **Bills** | Wallet | — | — | Syriatel and MTN prepaid top-up, Electricity Ministry SOAP API, Water Authority |
| **Merchant** | Wallet | Settlement | Fraud, Notification | T+1 settlement, MDR (Merchant Discount Rate) capped by CBS |
| **Settlement** | Wallet, Merchant, Agent | — | CFE, Notification | Batch settlement to Syrian bank accounts (IBAN format) |
| **Fraud** | ALL (consumes events) | — | Compliance, Alerting | Syria-specific rules: phone recycling detection, agent-customer collusion |
| **Compliance** | Identity, Fraud, Remittance | — | CBS Reporting, AML | Sanction screening, STR reporting to Syrian AML Commission |
| **Notification** | Identity (user prefs) | — | — | SMS via Syriatel SMPP + fallback, Arabic/English bilingual |
| **Admin** | ALL | — | — | Multi-role admin: Super Admin, Compliance Officer, Agent Manager, Finance |
| **USSD** | Identity | — | — | *123# shortcode, Arabic-first menus, Syriatel USSD gateway |
| **Auth** | Identity (local dependency) | Wallet, ALL | — | JWT + PIN + device binding, middleware on every financial route |

---

## Blocking Diagram

```
                    ┌──────────────────────────────────────────────────────────────┐
                    │                         IDENTITY                            │
                    │  (Auth, Profile, KYC, Device Binding, Phone/OTP, PIN/Session) │
                    └──────────┬───────────────────────────────────────────────────┘
                               │
                               ▼
                    ┌──────────────────────┐
                    │       WALLET         │
                    │  (Transfer, Hold,    │
                    │   Limits, Idempotency)│
                    └────┬────────┬────┬───┘
                         │        │    │
          ┌──────────────┘        │    └──────────────┐
          ▼                       ▼                   ▼
   ┌────────────┐         ┌────────────┐      ┌──────────────┐
   │   AGENT    │         │     FX     │      │   MERCHANT   │
   │(Cash-in/out│         │(FX Quote,  │      │ (QR, POS,    │
   │ Float,     │         │ Conversion,│      │  Settlement) │
   │ Commissions)│         │ Corridors) │      └──────┬───────┘
   └─────┬──────┘         └──────┬─────┘             │
         │                      │                    │
         │              ┌───────┘                    │
         │              ▼                            │
         │      ┌──────────────┐                     │
         │      │  REMITTANCE  │                     │
         │      │ (Send, AML,  │                     │
         │      │  Payout, CBS)│                     │
         │      └──────┬───────┘                     │
         │             │                             │
         ▼             ▼                             ▼
   ┌───────────────────────────────────────────────────────┐
   │                     SETTLEMENT                        │
   │  (Agent settlement, Merchant settlement, Remittance   │
   │   payout settlement, Bank transfer settlement)        │
   └───────────────────────────────────────────────────────┘
                              │
                              ▼
                     ┌────────────────┐
                     │   CFE (LEDGER) │
                     │  (Double-entry,│
                     │   Reconcile)   │
                     └────────────────┘

NON-BLOCKING (async, run in parallel from W1):
  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐
  │  COMPLIANCE  │  │    FRAUD     │  │ NOTIFICATION │  │    ADMIN     │
  │  (Sanctions, │  │  (Rules, ML, │  │  (SMS, Push, │  │  (Dashboard, │
  │   STR, CBS)  │  │   Scoring)   │  │   In-App)    │  │   Reports)   │
  └──────────────┘  └──────────────┘  └──────────────┘  └──────────────┘

ALSO PARALLEL:
  ┌──────────────┐  ┌──────────────┐
  │    USSD      │  │    BILLS     │
  │  (*123#,     │  │  (Electric,  │
  │   Arabic)    │  │  Telecom,    │
  └──────────────┘  │  Water)      │
                    └──────────────┘
```

---

## Critical Path for V1

The critical path determines the minimum time to launch V1. Every week on the critical path is non-negotiable — delays propagate to launch.

```
Step 1: IDENTITY (W1–W4, 4 weeks)
        ↓ NO DEPENDENCIES — START HERE
Step 2: WALLET (W5–W8, 4 weeks)
        ↓ WAIT for Identity
Step 3A: AGENT (W9–W12, 4 weeks)
         ↓ WAIT for Wallet
         (Agent runs on critical path because cash-in/cash-out is MVP feature)
Step 3B: FX (W13–W16, 4 weeks)
         ↓ WAIT for Wallet
         (FX runs after Agent because Remittance depends on FX — parallel not possible)
Step 4:  REMITTANCE (W17–W20, 4 weeks)
         ↓ WAIT for FX + Wallet
Step 5:  MERCHANT (W21–W24, 4 weeks)
         ↓ WAIT for Wallet
Step 6:  SETTLEMENT (W21–W24, 4 weeks, parallel with Merchant)
         ↓ WAIT for Merchant + Agent
Step 7:  OPERATIONS (W21–W24, paralel wrap)
```

**Critical Path Duration: 24 weeks**

### What Is NOT on the Critical Path
These can be shifted right without delaying V1 launch:
| Module | Slack | Rationale |
|--------|-------|-----------|
| Bills | 4 weeks (W17–W20) | Runs parallel to Remittance, same dependency (Wallet) |
| USSD | 8 weeks (W3–W10) | Started early but can slip; not core to wallet/agent flow |
| Fraud Engine | Unlimited (W5–W24) | Non-blocking — rules start simple, improve iteratively |
| Compliance | Unlimited (W1–W24) | Required for launch but runs async; basic checks ship W1 |
| Admin Panel | Unlimited (W1–W24) | Iterative; read-only dashboard ships W1, full ops W21 |
| Notification | Unlimited (W1–W24) | Async infrastructure; SMS ships W1, push later |

---

## Shared Service Dependencies

Some services are consumed by multiple modules. These must be built with stable contracts (interfaces, events, DTOs) before the first consuming module ships.

### CFE (Chart of Financial Events) — Shared Ledger
```
Suppliers: Wallet, FX, Settlement, Agent (float adjustments)
Consumers: Reconciliation, Compliance (audit trail), Admin (P&L)
Contract:  CFEInterface::postEntry(accountId, amount, currency, referenceType, referenceId, metadata)
Guarantee: All posting is idempotent (unique referenceId prevents doubles)
```

### Fraud Engine — Shared Risk Scoring
```
Suppliers: ALL modules (emit fraud events)
Consumers: Wallet (block transfer), Agent (block cash-out), Admin (case management)
Contract:  FraudEvent { userId, action, amount, deviceFingerprint, ip, geo }
Guarantee: Fraud scoring completes in <200ms (Redis-based rules), non-blocking fallback (allow on timeout)
```

### Notification — Shared Messaging
```
Suppliers: ALL modules (call NotificationService::send())
Consumers: Users, Agents, Admins (SMS, push, email, in-app)
Contract:  Notification { recipient, channel, template, params, locale (ar/en) }
Priority: SMS = high (queue), Push = default, Email = low
```

### Rate Limiter — Shared Middleware
```
Applied on: All financial routes (transfer, cash-in/out, bill pay, merchant pay)
Strategy:  Redis sliding window, per-user + per-endpoint
Tiers:     Tier 1: 10 financial txns/min, Tier 2: 30/min
```

---

## Dependency Rules (Engineering Contract)

### Rule 1: No Circular Dependencies
Modules may NOT depend on each other in a cycle. Exception: Fraud and Compliance consume events from all modules but do not block them — this is a unidirectional event stream, not a dependency.

```
BAD:  Wallet → FX → Wallet (circular)
GOOD: Wallet → FX → Remittance (acyclic)
```

### Rule 2: Interface Before Implementation
When Module A depends on Module B, Module B must provide a stable interface (PHP interface + DTO) before Module A's development begins. Implementation can follow.

```
Week 5: Wallet team defines WalletInterface (transfer(), hold(), release())
Week 5: Agent team starts coding against WalletInterface (mock)
Week 6: Wallet team implements WalletInterface
```

### Rule 3: Events Not Coupling
Cross-module communication should use Laravel events + listeners, not direct method calls, except for CFE posting (must be synchronous for consistency).

```
FraudEvent::dispatch($fraudData);     // GOOD: async, non-blocking
WalletService::transfer($data);       // GOOD: synchronous for consistency
FraudService::score($data);           // GOOD: synchronous, <200ms
Agent::create(new Agent(...));        // CONTAINS Wallet::adjustFloat() — BAD: coupling
```

### Rule 4: Database Schema Isolation
Each module uses its own MySQL schema (`beza_identity`, `beza_wallet`, etc.). Cross-schema queries are forbidden. Data joins across modules go through the application layer (service calls), not database.

```
SELECT * FROM beza_wallet.wallets WHERE user_id IN (
    SELECT id FROM beza_identity.users WHERE phone = ?   -- BAD: cross-schema
);

$user = IdentityService::findByPhone($phone);            -- GOOD: service call
$wallet = WalletService::getByUserId($user->id);
```

### Rule 5: CFE Is the Single Source of Truth for Balances
Wallet balance in Redis is a cache. Wallet balance in MySQL is a cached projection. The CFE journal entries are the single source of truth. Reconciliation runs daily.

```
Balance query path:
  CFE.journal_entries → SUM(amount) WHERE account_id = wallet.cfe_account_id
  └──→ Written to wallet.balance (MySQL, cached)
       └──→ Written to redis:wallet:{id}:balance (Redis, cached for 60s)
```

---

## Dependency Graph (ASCII)

```
                 ┌──────────┐
                 │IDENTITY  │◄─── (Notification: user prefs)
                 │(no deps) │
                 └────┬─────┘
                      │
          ┌───────────┼───────────┐
          │           │           │
          ▼           ▼           ▼
     ┌────────┐ ┌────────┐ ┌──────────┐
     │USSD    │ │WALLET  │ │ADMIN     │
     │(Auth)  │ │(Ident) │ │(Ident)   │
     └────────┘ └────┬───┘ └──────────┘
                     │
          ┌──────────┼──────────┬──────────┐
          │          │          │          │
          ▼          ▼          ▼          ▼
     ┌────────┐ ┌────────┐ ┌────────┐ ┌──────────┐
     │AGENT   │ │FX      │ │BILLS   │ │MERCHANT  │
     │(Id,Wal)│ │(Wallet)│ │(Wallet)│ │(Wallet)  │
     └────┬───┘ └────┬───┘ └────────┘ └────┬─────┘
          │          │                     │
          │          ▼                     │
          │   ┌──────────────┐            │
          │   │REMITTANCE    │            │
          │   │(FX, Wal, Ag) │            │
          │   └──────┬───────┘            │
          │          │                    │
          └──────────┼────────────────────┘
                     ▼
              ┌────────────┐
              │SETTLEMENT  │
              │(Wal,Merch, │
              │ Agent)     │
              └──────┬─────┘
                     │
                     ▼
              ┌────────────┐
              │CFE (LEDGER)│
              │(Wal,FX,Set)│
              └────────────┘

NON-BLOCKING STREAMS (separate, no blocking edges):
  Compliance ──── Consumers: Identity, Fraud, Remittance
  Fraud ───────── Consumers: ALL (event stream)
  Notification ── Consumers: ALL (event stream)
```

---

## Build Sequence by Dependency Level

| Level | Modules | Start Week | Deps Satisfied By |
|-------|---------|------------|-------------------|
| 0 | Identity | W1 | — |
| 1 | Wallet, USSD, Admin, Notification | W5 | Identity |
| 2 | Agent, FX, Bills, Merchant, Compliance | W9 | Wallet |
| 3 | Remittance, Fraud (advanced) | W13 | FX, Wallet, Agent |
| 4 | Settlement | W17 | Merchant, Agent, Wallet |
| 5 | Operations (full) | W21 | ALL |

---

## Risk Dependencies

### High Risk (single point of failure)
| Dependency | Risk | Mitigation |
|-----------|------|------------|
| Wallet → Identity | Identity delay blocks ALL financial features | Identity team is 2 engineers minimum, code review for quality |
| Agent → Wallet | Agent launch requires working wallet | Wallet API frozen by contract in W5, Agent builds against mock |
| Remittance → FX | FX delay blocks remittance | FX team starts W9, one week before Agent (buffer) |
| CFE → Wallet, FX, Settlement | CFE bugs corrupt entire ledger | CFE has its own test suite + daily reconciliation check |
| SMS → Syriatel SMPP | SMPP downtime blocks OTP/notifications | GSM modem fallback, SMS queue with retry, exponential backoff |

### Medium Risk
| Dependency | Risk | Mitigation |
|-----------|------|------------|
| Fraud → ML model | ML not ready for launch | Rule engine ships W5 without ML; ML added W13 as enhancement |
| CBS reporting → Remittance | CBS XML format changes | Adapter pattern: parse CBS format into internal DTO, swap implementation |
| Agent app → Android SDK | Android fragmentation | Min SDK 26 (Android 8), test on top 5 Syrian devices (Samsung A series, Xiaomi Redmi) |

---

## Module Isolation Strategy

Each module in Beza is designed as a self-contained Laravel package within the monorepo:

```
Modules/Wallet/
├── app/
│   ├── Models/
│   ├── Services/
│   ├── Http/
│   │   ├── Controllers/
│   │   ├── Middleware/  (idempotency, rate limit, pin verification)
│   │   └── Requests/    (form requests with authorization)
│   ├── Events/          (WalletCredited, WalletDebited, HoldExpired)
│   ├── Listeners/       (fraud scoring, notification, CFE posting)
│   └── Contracts/       (WalletInterface)
├── database/
│   └── migrations/
├── routes/
│   └── api.php
├── tests/
│   ├── Unit/
│   ├── Feature/
│   └── Integration/     (cross-module, e.g. Wallet → CFE)
└── config.php
```

Inter-module communication is via:
- **Events + Listeners** (async, non-blocking): Fraud, Notification, Compliance
- **Contracts + Service Container** (sync, blocking): Wallet → CFE, Wallet → Fraud scoring
- **Queued Jobs**: SMS sending, report generation, biller API calls
