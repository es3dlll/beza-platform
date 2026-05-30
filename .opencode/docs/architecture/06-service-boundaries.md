# Service Boundaries — Beza Platform V1

> **Status:** Approved  
> **Last updated:** 2025-12-01  
> **Owner:** Platform Architecture

---

## Principle

**V1 = Monolith with MODULES (NOT services).**  
Boundaries are logical/namespace, not network. Extraction to microservices only when **2 of 3** criteria are met:

1. Different scaling needs
2. Different data isolation requirements
3. Independent deploy frequency

This constraint prevents premature distribution. Syria's internet infrastructure (latency spikes, routing instability) makes network calls expensive — every cross-process boundary must earn its keep.

### Why Monolith First for Syria

| Factor | Reality |
|--------|---------|
| Internet reliability | SY median latency >150ms to global cloud, packet loss >2% |
| Team maturity | Starting with 2–3 squads, microservices overhead would halve throughput |
| Regulatory timeline | CBS expects V1 certification within 12 months; monolith accelerates audit |
| Capital efficiency | Fewer infra costs = more runway for organic growth |

---

## Module Map (V1 Monolith)

```
app/Modules/
├── Identity/          # User registration, KYC, auth, device binding
│   └── owns: User, KYC, Device, Session
├── Wallet/            # Wallets, transactions, holds
│   └── owns: Wallet, Transaction, Balance, Limit
├── Ledger/            # Double-entry accounting (CFE)
│   └── owns: Account, Entry, Journal, TrialBalance
├── FX/                # Rates, quotes, conversions
│   └── owns: Rate, Quote, Conversion, Corridor
├── Agent/             # Agent network, float, commissions
│   └── owns: Agent, Float, CashIn, CashOut, Commission
├── Merchant/          # Merchant onboarding, payments, MDR
│   └── owns: Merchant, Terminal, Payment, MDR
├── Remittance/        # Diaspora remittances (Europe → Syria corridors)
│   └── owns: Order, Beneficiary, Corridor
├── Bills/             # Biller management, payments
│   └── owns: Biller, Bill, Payment
├── Compliance/        # AML, sanctions, KYC, SAR
│   └── owns: Case, Screening, Rule, SAR
├── Settlement/        # End-of-day settlement, reconciliation
│   └── owns: Batch, NetPosition, Reconciliation
├── Notification/      # SMS, email, push, in-app
│   └── owns: Template, Notification, Outbox
└── Payroll/           # Bulk salary disbursement
    └── owns: PayrollBatch, Disbursement
```

### Syria-Specific Module Notes

| Module | Syria Context |
|--------|--------------|
| Identity | National ID (رقم وطني) as primary identifier; no civil registry API available → manual KYC verification |
| Wallet | SYP is sole settlement currency; SANA exchange rate from CBS daily |
| Ledger | Chart of Accounts must align with Central Bank of Syria (CBS) classification |
| FX | No free-floating market; rates tied to CBS official rate + approved premium corridors |
| Agent | Agent network is primary distribution channel (80%+ unbanked adults in Syria) |
| Compliance | OFAC/Syria sanctions screening is critical; US/EU sanctions prohibit certain transactions |
| Remittance | Primary use case: Syrian diaspora in EU sending EUR → SYP to families |
| Bills | Public sector billers (electricity, water, telecom) all state-owned |
| Settlement | Banks use SY centralized clearing; no RTGS available for real-time interbank |

---

## Module Interaction Rules

1. **Modules communicate ONLY through SERVICE INTERFACES** (not models directly)
2. Cross-module calls = **synchronous for reads, event-driven for writes**
3. **No direct DB access across modules** — each module owns its schema
4. Events are the integration backbone (see Event Catalog)

### Enforcement

- PHPStan level 6 with custom rules bans cross-module Eloquent model imports
- Deployment pipeline runs `arbitrary/prevent-cross-module-db-queries` checker
- Only `ModuleServiceProvider` registers routes; controllers live inside module

---

## Extraction Candidates (Post-V1)

| Module | Extraction Trigger | Target | Syria Consideration |
|--------|-------------------|--------|---------------------|
| FX Engine | When 3+ rate providers, sub-millisecond needed | Go or Rust service | If CBS allows premium FX corridors, rate computation becomes latency-sensitive |
| Settlement | When volume > 500K txns/day, need isolated processing | Standalone service | CBS may require separate audit trail for settlement |
| Notification | When 5+ channels, need independent scaling | Queue-driven worker | SMS aggregators in MENA region have unpredictable latency |
| Compliance | When regulatory requires isolated audit trail | Standalone service | Likely first extraction — regulatory audits need air-gapped data |

---

## Module Dependency Graph (V1)

```
Identity ──> ALL (auth check)
     │
     ▼
Wallet ──> Ledger (postings)
  │         │
  ▼         ▼
FX ──> Ledger (FX postings)
  │
  ├──> Remittance ──> FX ──> Ledger
  ├──> Agent ──> Wallet ──> Ledger
  ├──> Merchant ──> Wallet ──> Settlement
  ├──> Bills ──> Wallet ──> Ledger
  └──> Payroll ──> Wallet ──> Ledger

Compliance ──> ALL (async screening via events)
Settlement ──> Ledger ──> Bank External
Notification <── ALL (async via outbox)
```

### Dependency Rules

- **No circular dependencies** — enforced by PHPStan + CI pipeline
- Ledger is the **root dependency** (no module may depend on something that depends on Ledger)
- Identity is infrastructure — every module depends on it for auth, but NOT at the domain level
- Compliance is **fire-and-forget** — it consumes events but never blocks a transaction

---

## Event Contracts (Cross-Module)

| Event | Publisher | Subscribers | Delivery |
|-------|-----------|-------------|----------|
| `user.registered` | Identity | Compliance (screen), Notification (welcome) | Async |
| `wallet.transaction.created` | Wallet | Ledger (post), Compliance (screen), Notification | Async |
| `fx.quote.completed` | FX | Remittance, Wallet | Async |
| `agent.cash_in.completed` | Agent | Wallet, Ledger | Sync then Async |
| `compliance.sar.filed` | Compliance | Notification (alert compliance officer) | Async |
| `settlement.batch.closed` | Settlement | Ledger, Wallet (reconcile) | Async |

---

## Boundaries Decision Log

| Date | Decision | Rationale |
|------|----------|-----------|
| 2025-11-01 | Keep FX inside monolith V1 | Single CBS rate source; <100ms acceptable in-process |
| 2025-11-15 | Notification stays in monolith | Only 2 channels (SMS, email); SMS aggregator is single provider (Naratel) |
| 2025-11-20 | Compliance remains coupled V1 | Regulatory needs evolve fast; monolith gives flexibility for CBS exam cycles |
| 2025-12-01 | Ledger is NOT extracted V1 | Double-entry must be transactional with Wallet; network splits would cause irreconcilable differences |
