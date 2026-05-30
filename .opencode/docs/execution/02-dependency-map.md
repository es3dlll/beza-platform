# Dependency Map — Beza Platform V1 (Corrected)

## Module Inventory

Beza V1 consists of 13 engineering modules plus 3 cross-cutting infrastructure services. Each module is a self-contained Laravel directory under `Modules/` with its own models, migrations, controllers, services, tests, and routes.

**Architectural correction:** Ledger and CFE precede Wallet. Wallet balance is a cached projection of Ledger journal entries. Building Wallet before Ledger would create unrecoverable technical debt.

---

## Dependency Matrix

| Module | Depends On | Blocks | Shared Services | Syria-Specific Notes |
|--------|-----------|--------|-----------------|---------------------|
| **Identity** | — | IAM, ALL | Cache, Queue, SMS | Phone prefix +963, OTP via Syriatel SMPP, device binding for Syrian phones |
| **IAM** | Identity | Ledger, Wallet, ALL | Cache | Spatie Laravel Permission, module-based authorization, role hierarchy (Super Admin, Compliance, Finance, Agent Manager, Support) |
| **Ledger** | IAM | CFE, Wallet, Settlement | — | Chart of accounts must be CBS-compatible (Central Bank of Syria classification). Account types: asset, liability, income, expense, suspense |
| **CFE** | Ledger | Wallet, FX, Settlement | Reconciliation | State machine (initiated → held → completed → reversed), hold engine (30-min expiry), fee engine, reversal engine, suspense handling |
| **Wallet** | Identity, IAM, CFE | Agent, FX, Remittance, Bills, Merchant | Fraud, Rate Limiter | Dual-currency SYP/USD, tier limits based on CBS regulation. Balance = cached projection of Ledger journal entries |
| **Agent** | Wallet | Settlement | Fraud, Compliance, Notification | Agent registration requires Syrian business registration (سجل تجاري). Float managed via Ledger account 1200 |
| **FX** | Wallet, CFE | Remittance | Rate Cache, CFE Posting | CBS daily rate feed, spread caps regulated by CBS (max 3%). Uses CFE suspense account for multi-currency conversion |
| **Remittance** | FX, Wallet, Agent | — | Compliance, Fraud, CBS Reporting | OFAC/EU/UN sanction screening required for diaspora senders. Payout via CFE posting to recipient wallet |
| **Bills** | Wallet | — | CFE Posting | Syriatel and MTN prepaid top-up, Electricity Ministry SOAP API, Water Authority. Settlement via daily batch |
| **Merchant** | Wallet | Settlement | Fraud, Notification | T+1 settlement, MDR (Merchant Discount Rate) capped by CBS. CFE posting on every transaction |
| **Settlement** | Wallet, Agent, Merchant, CFE | — | Notification, Bank API | Batch settlement to Syrian bank accounts (IBAN format). Nett all payables before bank transfer |
| **Fraud** | ALL (consumes events) | — | Compliance, Alerting | Syria-specific rules: phone recycling detection, agent-customer collusion. Non-blocking event consumer |
| **Compliance** | Identity, Fraud, Remittance | — | CBS Reporting, AML | Sanction screening, STR reporting to Syrian AML Commission. KYC tier enforcement |
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
                    │         IAM          │
                    │  (Roles, Permissions, │
                    │   Module Auth, Audit) │
                    └──────────┬───────────┘
                               │
                               ▼
                    ┌──────────────────────┐
                    │       LEDGER         │
                    │  (Chart of Accounts, │
                    │   Journal, Posting,  │
                    │   Trial Balance)     │
                    └──────────┬───────────┘
                               │
                               ▼
                    ┌──────────────────────┐
                    │         CFE          │
                    │  (State Machine,     │
                    │   Hold Engine, Fee,  │
                    │   Reversal, Suspense)│
                    └──────────┬───────────┘
                               │
                               ▼
                    ┌──────────────────────┐
                    │       WALLET         │
                    │  (Transfer, Hold,    │
                    │   Limits, History,   │
                    │   Balance Query)     │
                    └────┬────────┬────┬───┘
                         │        │    │
           ┌─────────────┘        │    └─────────────┐
           ▼                      ▼                   ▼
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
Step 2: IAM (W5–W6, 2 weeks)
        ↓ WAIT for Identity
Step 3: LEDGER (W7–W8, 2 weeks)
        ↓ WAIT for IAM
Step 4: CFE (W9–W12, 4 weeks)
        ↓ WAIT for Ledger
Step 5: WALLET (W13–W16, 4 weeks)
        ↓ WAIT for CFE + Identity + IAM
Step 6: AGENT (W17–W20, 4 weeks)
        ↓ WAIT for Wallet
        (Agent runs on critical path because cash-in/cash-out is MVP feature)
Step 7: FX (W21–W24, 4 weeks)
        ↓ WAIT for Wallet + CFE
        (FX runs after Agent because Remittance depends on FX)
Step 8: REMITTANCE (W25–W28, 4 weeks)
        ↓ WAIT for FX + Wallet
Step 9: SETTLEMENT (W29–W32, 4 weeks)
        ↓ WAIT for Merchant + Agent + CFE
Step 10: OPERATIONS (W29–W32, parallel wrap)
```

**Critical Path Duration: 32 weeks**

### What Is NOT on the Critical Path
These can be shifted right without delaying V1 launch:
| Module | Slack | Rationale |
|--------|-------|-----------|
| Bills | 4 weeks (W25–W28) | Runs parallel to Remittance, same dependency (Wallet) |
| Merchant | 4 weeks (W29–W32) | Settlement is final step; merchant can start later |
| USSD | 8 weeks (W3–W10) | Started early but can slip; not core to wallet/agent flow |
| Fraud Engine | Unlimited (W5–W32) | Non-blocking — rules start simple, improve iteratively |
| Compliance | Unlimited (W1–W32) | Required for launch but runs async; basic checks ship W1 |
| Admin Panel | Unlimited (W1–W32) | Iterative; read-only dashboard ships W1, full ops W29 |
| Notification | Unlimited (W1–W32) | Async infrastructure; SMS ships W1, push later |

---

## Shared Service Dependencies

Some services are consumed by multiple modules. These must be built with stable contracts (interfaces, events, DTOs) before the first consuming module ships.

### Ledger — Single Source of Truth for Balances
```
Suppliers: CFE (posts entries), Wallet (reads balance), Settlement (posts batch entries)
Consumers: Reconciliation, Compliance (audit trail), Admin (P&L)
Contract:  LedgerInterface::postEntry(accountId, debit, credit, currency, referenceType, referenceId, metadata)
Guarantee: All posting is idempotent (unique referenceId prevents doubles)
Rule:      Wallet balance = SUM(ledger.journal_entries WHERE account_id = wallet.cfe_account_id)
```

### CFE (Core Financial Engine) — Transaction Orchestrator
```
Suppliers: Wallet, FX, Agent, Bills, Merchant (initiate transactions)
Consumers: Ledger (posts journal entries), Fraud (consumes events), Notification (sends receipts)
Contract:  CfeInterface::hold(accountId, amount, currency, ttl=30min)
           CfeInterface::post(debits[], credits[], referenceType, referenceId)
           CfeInterface::reverse(transactionId, reason)
           CfeInterface::moveToSuspense(transactionId, reason)
Guarantee: All-or-nothing posting (transactional), immutable entries
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
Week 13: Wallet team defines WalletInterface (transfer(), hold(), release())
Week 13: Agent team starts coding against WalletInterface (mock)
Week 14: Wallet team implements WalletInterface
```

### Rule 3: Events Not Coupling
Cross-module communication should use Laravel events + listeners, not direct method calls, except for CFE/Ledger posting (must be synchronous for consistency).

```
FraudEvent::dispatch($fraudData);     // GOOD: async, non-blocking
CfeService::post($debits, $credits);  // GOOD: synchronous for consistency
FraudService::score($data);           // GOOD: synchronous, <200ms
Agent::create(new Agent(...));        // CONTAINS Wallet::adjustFloat() — BAD: coupling
```

### Rule 4: Database Schema Isolation
Each module uses its own MySQL schema (`beza_identity`, `beza_ledger`, `beza_cfe`, `beza_wallet`, etc.). Cross-schema queries are forbidden. Data joins across modules go through the application layer (service calls), not database.

```
SELECT * FROM beza_wallet.wallets WHERE user_id IN (
    SELECT id FROM beza_identity.users WHERE phone = ?   -- BAD: cross-schema
);

$user = IdentityService::findByPhone($phone);            -- GOOD: service call
$wallet = WalletService::getByUserId($user->id);
```

### Rule 5: Ledger Is the Single Source of Truth for Balances
Wallet balance in Redis is a cache. Wallet balance in MySQL is a cached projection. The Ledger journal entries are the single source of truth. Reconciliation runs daily.

```
Balance query path:
  Ledger.journal_entries → SUM(amount) WHERE account_id = wallet.cfe_account_id
  └──→ Written to wallet.balance (MySQL, cached)
       └──→ Written to redis:wallet:{id}:balance (Redis, cached for 60s)
```

### Rule 6: CFE Orchestrates, Ledger Records
CFE manages transaction state (hold, confirm, reverse, suspense). Ledger records the immutable double-entry journal. CFE calls Ledger; Ledger never calls CFE.

---

## Dependency Graph (ASCII)

```
                  ┌──────────┐
                  │IDENTITY  │◄─── (Notification: user prefs)
                  │(no deps) │
                  └────┬─────┘
                       │
           ┌───────────┼───────────────┐
           │           │               │
           ▼           ▼               ▼
      ┌────────┐ ┌──────────┐   ┌──────────┐
      │USSD    │ │   IAM    │   │ADMIN     │
      │(Auth)  │ │(Identity)│   │(Ident)   │
      └────────┘ └────┬─────┘   └──────────┘
                      │
                      ▼
                 ┌──────────┐
                 │  LEDGER  │
                 │  (IAM)   │
                 └────┬─────┘
                      │
                      ▼
                 ┌──────────┐
                 │   CFE    │
                 │ (Ledger) │
                 └────┬─────┘
                      │
                      ▼
                 ┌──────────┐
                 │  WALLET  │
                 │(Id,IAM,  │
                 │ CFE)     │
                 └────┬─────┘
                      │
           ┌──────────┼──────────┬──────────┐
           │          │          │          │
           ▼          ▼          ▼          ▼
      ┌────────┐ ┌────────┐ ┌────────┐ ┌──────────┐
      │AGENT   │ │FX      │ │BILLS   │ │MERCHANT  │
      │(Wallet)│ │(Wal,   │ │(Wallet)│ │(Wallet)  │
      └────┬───┘ │ CFE)   │ └────────┘ └────┬─────┘
           │      └────┬───┘                 │
           │           │                     │
           │           ▼                     │
           │   ┌──────────────┐             │
           │   │REMITTANCE    │             │
           │   │(FX, Wal, Ag) │             │
           │   └──────┬───────┘             │
           │          │                     │
           └──────────┼─────────────────────┘
                      ▼
               ┌────────────┐
               │SETTLEMENT  │
               │(Wal,Merch, │
               │ Agent,CFE) │
               └──────┬─────┘
                      │
                      ▼
               ┌────────────┐
               │  BANK API  │
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
| 1 | IAM, USSD, Admin, Notification | W5 | Identity |
| 2 | Ledger | W7 | IAM |
| 3 | CFE | W9 | Ledger |
| 4 | Wallet, Fraud (basic) | W13 | CFE, IAM, Identity |
| 5 | Agent, FX, Bills, Merchant, Compliance | W17 | Wallet, CFE |
| 6 | Remittance | W25 | FX, Wallet, Agent |
| 7 | Settlement | W29 | Merchant, Agent, Wallet, CFE |
| 8 | Operations (full) | W29 | ALL |

---

## Risk Dependencies

### High Risk (single point of failure)
| Dependency | Risk | Mitigation |
|-----------|------|------------|
| Identity → IAM | Identity delay blocks authorization | Identity team is 2 engineers minimum, code review for quality |
| IAM → Ledger | IAM delay blocks ledger posting | IAM contract frozen by W5, Ledger builds against mock |
| Ledger → CFE | Ledger bugs corrupt entire financial system | Ledger has its own test suite + daily trial balance check |
| CFE → Wallet | CFE delay blocks ALL financial features | CFE uses LedgerInterface; Wallet builds against CFE contract from W12 |
| Agent → Wallet | Agent launch requires working wallet | Wallet API frozen by contract in W13, Agent builds against mock |
| Remittance → FX | FX delay blocks remittance | FX team starts W21, one week before Remittance (buffer) |
| SMS → Syriatel SMPP | SMPP downtime blocks OTP/notifications | GSM modem fallback, SMS queue with retry, exponential backoff |

### Medium Risk
| Dependency | Risk | Mitigation |
|-----------|------|------------|
| Fraud → ML model | ML not ready for launch | Rule engine ships W5 without ML; ML added as enhancement |
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
│   └── Integration/     (cross-module, e.g. Wallet → CFE → Ledger)
└── config.php
```

Inter-module communication is via:
- **Events + Listeners** (async, non-blocking): Fraud, Notification, Compliance
- **Contracts + Service Container** (sync, blocking): Wallet → CFE, CFE → Ledger, Ledger → posting
- **Queued Jobs**: SMS sending, report generation, biller API calls
