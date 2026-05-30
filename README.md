# Beza Platform — Syria's Financial Operating System

[![CI](https://github.com/es3dlll/beza-platform/actions/workflows/ci.yml/badge.svg)](https://github.com/es3dlll/beza-platform/actions/workflows/ci.yml)
![PHP](https://img.shields.io/badge/PHP-8.5-777BB4?logo=php)
![Laravel](https://img.shields.io/badge/Laravel-11-FF2D20?logo=laravel)
![Flutter](https://img.shields.io/badge/Flutter-3.41-02569B?logo=flutter)
![Tests](https://img.shields.io/badge/Tests-361%20passing-brightgreen)
![Modules](https://img.shields.io/badge/Modules-31-blue)
![Docs](https://img.shields.io/badge/Docs-120%2B%20files-lightgrey)

**Beza (بزة)** is a full-stack financial operating system purpose-built for Syria — replacing cash (85%+ economy) with programmable money. It serves 22M citizens and 6M diaspora through a **Laravel modular monolith** backend, **Flutter** mobile app, and **React** admin panel, bringing financial inclusion to a <25% banked population.

---

## Table of Contents

1. [Platform Vision](#1-platform-vision)
2. [Architecture Overview](#2-architecture-overview)
3. [System Context](#3-system-context)
4. [Module Catalog](#4-module-catalog)
5. [Project Structure](#5-project-structure)
6. [State Machines](#6-state-machines)
7. [Events Catalog](#7-events-catalog)
8. [Sequence Diagrams & Flows](#8-sequence-diagrams--flows)
9. [API Standards](#9-api-standards)
10. [Error Catalog](#10-error-catalog)
11. [Domain Model](#11-domain-model)
12. [Data Dictionary](#12-data-dictionary)
13. [Financial Core (CFE)](#13-financial-core-cfe)
14. [Design Language 2026](#14-design-language-2026)
15. [Security Model](#15-security-model)
16. [Plans & Roadmaps](#16-plans--roadmaps)
17. [Build Order & Dependencies](#17-build-order--dependencies)
18. [User Journeys](#18-user-journeys)
19. [API Matrix](#19-api-matrix)
20. [Ledger Impact Matrix](#20-ledger-impact-matrix)
21. [Production Readiness](#21-production-readiness)
22. [Testing](#22-testing)
23. [KPI Catalog](#23-kpi-catalog)
24. [Operations & Observability](#24-operations--observability)
25. [i18n Translations](#25-i18n-translations)
26. [Quick Start](#26-quick-start)
27. [Documentation Index](#27-documentation-index)

---

## 1. Platform Vision

Beza is Syria's first Financial Operating System — a unified digital infrastructure replacing cash with programmable money, connecting 18M citizens and 6M diaspora through a single platform.

### Core Identity

| Attribute | Answer |
|-----------|--------|
| What is Beza? | Financial Operating System |
| What does it replace? | Cash (85%+ economy), informal hawala, fragmented billers |
| Who does it serve? | Citizens, diaspora, merchants, agents, government, NGOs, enterprises |
| What currency? | SYP + USD (multi-currency ready) |
| What channels? | Mobile App (Flutter), USSD (*123#), SMS, Web, Agent POS |
| Business model | Transaction fees, FX spread, MDR, SaaS, lending income |
| Scale target | 5M+ users, 10K+ agents, $2B+ annual TP Year 5 |

### Market Context (Syria 2026)

| Metric | Value |
|--------|-------|
| Population | ~22M (including 6M diaspora) |
| Cash economy | 85%+ of transactions |
| Banked population | <25% |
| Smartphone penetration | ~60% (growing) |
| Internet coverage | ~55% (concentrated urban) |
| Diaspora remittances | $2-3B/year through informal channels |
| Unbanked SMEs | 95%+ have no access to credit |
| Youth (under 25) | 50%+ of population — mobile-first generation |

### 10 Platform Pillars

| # | Pillar | Description |
|---|--------|-------------|
| 1 | **Wallet Infrastructure** | Multi-currency (SYP/USD) programmable wallets with tiered limits |
| 2 | **FX Infrastructure** | Automated rate engine with CBS daily fixing, 15s quote locking |
| 3 | **Agent Banking Network** | National cash-in/cash-out network across 14 governorates |
| 4 | **Merchant Acquiring** | QR codes, payment links, POS for all business sizes |
| 5 | **Payroll Infrastructure** | Digital salary distribution for enterprises |
| 6 | **Government Collections** | Tax, fees, utilities, social payments |
| 7 | **Remittance Corridors** | Inbound diaspora money transfer (5 corridors) |
| 8 | **Savings & Financing** | Goal-based savings, Sharia-compliant lending |
| 9 | **Cards Infrastructure** | Virtual + physical prepaid cards |
| 10 | **Open Finance Layer** | API ecosystem for third-party innovation |

---

## 2. Architecture Overview

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                              PRESENTATION LAYER                             │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────────┐ │
│  │ Flutter  │  │  React   │  │  Admin   │  │   USSD   │  │  Agent POS   │ │
│  │   App    │  │   Web    │  │  Panel   │  │  *123#   │  │  Terminal    │ │
│  └────┬─────┘  └────┬─────┘  └────┬─────┘  └────┬─────┘  └──────┬───────┘ │
│       └──────────────┴─────────────┴─────────────┴──────────────┘          │
├─────────────────────────────────────────────────────────────────────────────┤
│                           API GATEWAY (Nginx)                                │
│  Auth Proxy | Rate Limit | Route Match | Cache Layer | TLS Termination      │
├─────────────────────────────────────────────────────────────────────────────┤
│                     APPLICATION LAYER (Laravel Modular Monolith)             │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────────────┐ │
│  │  Auth    │ │  Wallet  │ │   CFE    │ │  Ledger  │ │  31 Modules      │ │
│  │  Module  │ │  Module  │ │  Module  │ │  Module  │ │  (self-contained) │ │
│  └──────────┘ └──────────┘ └──────────┘ └──────────┘ └──────────────────┘ │
├─────────────────────────────────────────────────────────────────────────────┤
│                           EVENT PLATFORM                                     │
│  RabbitMQ Cluster | Exchange Types: direct, topic, fanout, delayed           │
│  Schemas: CloudEvents 1.0 + Avro serialization | Dead Letter Strategy        │
├─────────────────────────────────────────────────────────────────────────────┤
│                              DATA PLATFORM                                   │
│  MySQL 8.0 | Redis 7 | ClickHouse (analytics) | Elasticsearch (search+logs) │
├─────────────────────────────────────────────────────────────────────────────┤
│                           AI PLATFORM                                        │
│  Fraud Detection (ONNX) | Risk Scoring | AML Screening | NLP Chatbot | OCR  │
└─────────────────────────────────────────────────────────────────────────────┘
```

### V1 Architecture Rules (NON-NEGOTIABLE)

1. **Modular Monolith ONLY** — No microservices, no separate service deployments
2. **Single Laravel codebase** — `app/Modules/*`, all modules in one process
3. **Single database** — MySQL with schemas per domain
4. **CFE owns ALL financial state** — No module writes balances directly
5. **Ledger = Single Source of Truth** — Every financial event passes through CFE
6. **ULID for ALL primary keys** — No auto-increment IDs
7. **Money = bigint minor units** — No float, `App\Domain\ValueObjects\Money`
8. **Cross-module via Events only** — No direct service calls across modules
9. **Zero Trust** — RBAC + ABAC + JWT rotation + device binding
10. **Arabic-first, RTL-native** — Syria-specific (SYP, CBS, Syriatel, MTN)

### Technology Stack

| Component | Technology | Version |
|-----------|-----------|---------|
| Framework | Laravel | 11.54.0 |
| Language | PHP | 8.5.6 |
| Database (prod) | MySQL | 8.0+ |
| Database (dev) | SQLite | in-memory |
| Cache/Queue | Redis | 7+ |
| Event Bus | RabbitMQ | 3.x |
| Web Server | Nginx | latest |
| Mobile | Flutter | 3.41.9 |
| Admin | React | 18+ |

### Standard Financial Transaction Flow

```
User Action → Mobile App / USSD / Web
  → API Gateway → JWT Verify → Rate Check → Route Match
    → Module Controller → Validate Input → Authorize
      → Domain Service → Business Rules → Compute
        → CFE → Hold → Post → Ledger → Fee → FX
          → Event Bus → Emit TransactionEvent
            → Listeners → Notification → Analytics → Compliance
              → Response → User Confirmation → Receipt
```

---

## 3. System Context

### Internal Systems

```
┌──────────────────────────────────────────────────────────────────────────────┐
│                             BEZA FINANCIAL OS                                │
│                                                                              │
│  ┌────────────┐  ┌──────────────┐  ┌──────────────┐  ┌────────────────┐    │
│  │  Wallet    │  │   FX Engine  │  │  Agent       │  │  CFE (Core     │    │
│  │  Module    │  │              │  │  Network     │  │  Fin. Engine)  │    │
│  └─────┬──────┘  └──────┬───────┘  └──────┬───────┘  └────────┬───────┘    │
│  ┌─────▼──────┐  ┌──────▼───────┐  ┌──────▼───────┐  ┌────────▼────────┐  │
│  │ Remittance │  │  Merchant    │  │  Bills       │  │  Settlement     │  │
│  │ Module     │  │  Payments    │  │  Payment     │  │  Engine         │  │
│  └────────────┘  └──────────────┘  └──────────────┘  └────────────────┘  │
│  ┌────────────┐  ┌──────────────┐  ┌──────────────┐  ┌────────────────┐  │
│  │ Compliance │  │  Treasury    │  │  Notification│  │  Identity &    │  │
│  │ Engine     │  │  Management  │  │  Dispatcher  │  │  KYC Module    │  │
│  └────────────┘  └──────────────┘  └──────────────┘  └────────────────┘  │
└──────────────────────────────────────────────────────────────────────────────┘
         │                    │                    │                    │
         ▼                    ▼                    ▼                    ▼
┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐  ┌────────────────────┐
│  SYRIAN USER    │  │   AGENT         │  │   MERCHANT      │  │  GOVERNMENT /      │
│  [Person]       │  │   [Person]      │  │   [Person]      │  │  REGULATOR [Sys]   │
└─────────────────┘  └─────────────────┘  └─────────────────┘  └────────────────────┘
```

### External Systems

| System | Role | Protocol |
|--------|------|----------|
| **CBS** (Central Bank of Syria) | Rate fixing, RTGS settlement, regulatory | SWIFT MT/MX, SOAP/XML, SFTP |
| **BSO** (Bank of Syria and Overseas) | Primary settlement bank, agent float accounts | SFTP, REST, SWIFT MT940 |
| **Bemo Saudi Fransi** | Merchant acquiring, POS terminals | ISO 8583, SFTP |
| **SIIB** (Syrian Intl. Islamic Bank) | Sharia-compliant reserves | SFTP, REST |
| **World-Check** (Refinitiv/LSEG) | Sanctions screening, PEP, adverse media | REST API |
| **Syriatel / MTN** | SMS delivery (OTP, alerts) | SMPP 3.4 |
| **STE** (Syrian Telecom) | Landline/internet bills | SFTP CSV |
| **OFAC SDN / UNSCR** | Sanctions lists download | HTTPS file |

### Diaspora Remittance Corridors

| Corridor | Volume Share | Avg. Transaction | Fee Structure |
|----------|-------------|-----------------|--------------|
| Europe→Turkey→Syria | ~45% | €200-500 | 3% (<€500), 2.5% (€500-2000), 2% (>€2000) |
| UAE→Syria | ~25% | 500-2000 AED | Flat 15 AED + 2% FX spread |
| Saudi Arabia→Syria | ~15% | 500-3000 SAR | 10 SAR + 2% FX spread |
| US→Syria | ~10% | $500-5000 | $5 + 2.5% FX spread + $2 compliance surcharge |
| Jordan→Syria | ~5% | 50-300 JOD | 1 JOD + 1.5% FX spread (T+0 via RTGS) |

---

## 4. Module Catalog

### Tier A — V1: Core Financial Infrastructure (12 modules)

| Module | Description | Files | Tests | Key Features |
|--------|-------------|-------|-------|-------------|
| **Identity** | User registration, phone/OTP, KYC, device binding, PIN | 35+ | 71 | Syrian National ID, 3 KYC tiers, device fingerprint |
| **IAM** | RBAC + ABAC: Super Admin, Compliance, Finance, Agent Mgr, Support | 25+ | 4 | Spatie roles/permissions, policy-based access |
| **Ledger** | Double-entry accounting, chart of accounts, journal, trial balance | 30+ | 5 | 13 seed accounts, WORM journal, holds |
| **CFE** | Core Financial Engine — 5 engines: Posting, Fee, Hold, Reversal, Settlement | 15+ | 5 | State machine (initiated→held→completed/failed/reversed) |
| **Wallet** | Multi-currency (SYP/USD), limits T1/T2/T3, P2P transfer | 25+ | 4 | Tier limits, daily/monthly caps, balance cache |
| **Agent** | Agent banking network — registration, KYC, cash-in/out, commissions | 20+ | 6 | Geo-location, 14 governorates, float management |
| **Settlement** | Merchant D+1, agent settlement, batch processing, reconciliation | 15+ | 3 | State machine, cut-off processing |
| **FX** | CBS rate feed, quote/lock 15s, SYP↔USD via suspense, 1.5% fee | 15+ | 3 | Corridor rates, provider failover |
| **Remittance** | 5 diaspora corridors, inquire/receive, compliance hold >$1K, sanctions | 25+ | 3 | LB/AE/JO/DE corridors, OFAC screening |
| **Bills** | Syriatel/MTN/PEED/Water/Landline adapters, inquire→pay→receipt | 20+ | 4 | Provider adapters, biller timeout, partial payment |
| **Merchant** | Registration, static QR, D+1 settlement, refund | 20+ | 4 | MDR tiers, Bemo POS integration |
| **Fraud** | 20+ rules, scoring 0-1000, blacklist, device fingerprinting, cases | 25+ | 3 | 100+ input signals, velocity checks |

### Tier B — V1.5: Growth (3 modules)

| Module | Description | Files | Tests | Key Features |
|--------|-------------|-------|-------|-------------|
| **Payroll** | Bulk disbursement, employer management, CSV import, salary certificates | 25+ | 3 | Batch processing, fee calculation, salary certificate PDF |
| **Savings** | Goal-based savings, profit distribution, auto-sweep, withdrawal rules | 20+ | 3 | Auto-save, round-up, profit sharing |
| **Settlement (enhanced)** | Cut-off processing, reconciliation, D+1 settlement for merchants | 3+ | 3 | Batch netting, exception handling |

### Tier C — V2: Expansion (3 modules)

| Module | Description | Files | Tests | Key Features |
|--------|-------------|-------|-------|-------------|
| **Cards** | Virtual/physical card management, card schemes, disputes | 15+ | 3 | Card states: issued→active→suspended→frozen→cancelled |
| **Loyalty** | Points, tiers, cashback, rewards engine | 25+ | 3 | Points per transaction, tier upgrades, cashback triggers |
| **GovCollections** | Inquire→pay pattern, CBS/BSO/tax/utility providers | 20+ | 3 | 30-min inquiry expiry, admin summary |

### Tier D — V3: Advanced Financial Services (4 modules)

| Module | Description | Files | Tests | Key Features |
|--------|-------------|-------|-------|-------------|
| **Financing** | Murabaha, Qard Hasan, Micro-Enterprise, BNPL | 30+ | 4 | Credit scoring (0-1000), installment schedule, late penalties |
| **Education** | Institution→student→fee hierarchy, CSV bulk import | 20+ | 6 | Partial payment, QR receipt, overdue tracking, institution portal |
| **Humanitarian** | Organization→program→disbursement, batch processing, OFAC | 25+ | 7 | Voucher/cash dual path, agent pickup, donor reports |
| **OpenFinance** | OAuth2 app/consent/token, Payment Initiation, Account Info, Webhooks | 25+ | 6 | HMAC webhooks, sandbox environment, rate limiting |

### Tier E — V4: Super App Ecosystem (8 modules)

| Module | Description | Files | Tests | Key Features |
|--------|-------------|-------|-------|-------------|
| **Escrow** | CFE hold/release/refund, milestone tracking, dispute resolution | 15+ | 6 | 1% fee capped 50K SYP, 3-stage milestone model |
| **Marketplace M1** | Digital products catalog, order state machine, vendor onboarding | 21+ | 6 | Invite-only vendors, 8-15% commission |
| **Marketplace M2** | Gift cards, promo codes, loyalty points | 11+ | 8 | SMS/WhatsApp delivery, QR redeem, 1pt/1000SYP |
| **Marketplace M3** | Physical products, shipping zones, COD, tracking | 8+ | 4 | 14 governorates, 3 shipping zones, agent COD |
| **Marketplace M4** | Open marketplace API, rate limiting 60/min, webhook fulfillment | 1+ | 4 | Partner catalog/order API |
| **Takaful** | Islamic insurance — health, device, travel | 15+ | 6 | Tabarru' pool, simplified underwriting, loss ratio |
| **Investments** | Sharia-compliant funds, subscribe/redeem, daily NAV | 16+ | 6 | Zakat calculator, T+2 settlement, AUM dashboard |
| **Admin V3+V4** | 8 admin controllers — dashboards, approval queues, reports | 16+ | 8 | All 8 backend modules managed |

### Cross-Cutting (4 modules)

| Module | Description | Key Features |
|--------|-------------|-------------|
| **USSD** | *123# menu engine for feature phones | Arabic menu, tree navigation, session management |
| **Notification** | Multi-channel (in-app, SMS, email, push/FCM) | Template system, outbox pattern, retry |
| **Auth** | JWT authentication, FCM token management | 15min access token, 7d refresh, device token |
| **Float** | Agent float management | Float accounts, top-up, reconciliation |

---

## 5. Project Structure

```
beza-platform/
├── backend/
│   ├── app/
│   │   ├── Console/Commands/          ← Artisan commands (30+)
│   │   ├── Domain/ValueObjects/       ← Money, Currency, Rate, Percentage
│   │   ├── Exceptions/               ← Global exception handlers
│   │   ├── Http/Controllers/         ← API controllers
│   │   ├── Listeners/               ← Event listeners
│   │   └── Modules/                 ← **31 self-contained modules**
│   │       ├── Admin/               ← Admin backend (8 controllers + services)
│   │       ├── Agent/               ← Agent banking network
│   │       ├── Auth/                ← Authentication
│   │       ├── Bills/               ← Bill payment providers
│   │       ├── CFE/                 ← Core Financial Engine
│   │       ├── Cards/               ← Card management
│   │       ├── Education/           ← Tuition payments
│   │       ├── Escrow/              ← Escrow agreements
│   │       ├── Financing/           ← Loans & BNPL
│   │       ├── Float/               ← Agent float
│   │       ├── Fraud/               ← Fraud detection
│   │       ├── FX/                  ← Currency exchange
│   │       ├── GovCollections/      ← Government fees
│   │       ├── Humanitarian/        ← Aid distribution
│   │       ├── IAM/                 ← Identity & access
│   │       ├── Identity/            ← KYC & registration
│   │       ├── Investments/         ← Fund investments
│   │       ├── Ledger/              ← Double-entry accounting
│   │       ├── Loyalty/             ← Loyalty rewards
│   │       ├── Marketplace/         ← E-commerce platform
│   │       ├── Merchant/            ← QR payments
│   │       ├── Notification/        ← Multi-channel dispatch
│   │       ├── OpenFinance/         ← Open API platform
│   │       ├── Payroll/             ← Bulk payroll
│   │       ├── Remittance/          ← Cross-border transfers
│   │       ├── Savings/             ← Savings goals
│   │       ├── Settlement/          ← Settlement engine
│   │       ├── Takaful/             ← Islamic insurance
│   │       ├── USSD/                ← *123# menu
│   │       └── Wallet/              ← Digital wallets
│   ├── bootstrap/                    ← Laravel bootstrap
│   ├── config/                       ← Config files (app, auth, beza, cache, database, features, ...)
│   ├── database/
│   │   ├── migrations/              ← 100+ migration files
│   │   └── seeders/                 ← Demo data seeders
│   ├── docker/                       ← Docker PHP/Nginx config
│   ├── docs/                         ← OpenAPI spec (openapi.yaml)
│   ├── postman/                      ← Postman collection + environment
│   ├── public/                       ← Web server root (index.php)
│   ├── resources/views/              ← Blade templates (Blade)
│   ├── routes/                       ← API, web, console routes (184+ endpoints)
│   ├── storage/                      ← Logs, cache, sessions
│   ├── tests/                        ← Backend tests (361 tests, 842 assertions)
│   ├── vendor/                       ← Composer dependencies
│   ├── artisan                       ← CLI entry point
│   ├── composer.json
│   └── phpunit.xml
├── frontend/
│   ├── admin/                        ← React admin panel
│   │   └── src/
│   │       ├── pages/               ← KYC review, agents, fraud, FX, roles, users, transactions
│   │       └── services/api.ts      ← API client
│   └── mobile/                       ← Flutter mobile app
│       └── lib/
│           ├── core/                ← Theme, API client, config, routing
│           ├── features/            ← Feature modules mirroring backend
│           └── l10n/                ← Arabic + English localization
├── .github/workflows/                ← CI pipeline
├── .gitignore
└── .opencode/                        ← AI-augmented documentation (120+ files)
    ├── docs/                         ← Architecture, ADRs, domain, engineering, operations...
    │   ├── adr/                     ← 7 Architecture Decision Records
    │   ├── api/                     ← API standards + 126 error codes
    │   ├── architecture/            ← System context, events, state machines, sequences
    │   ├── design/                  ← Design language 2026
    │   ├── domain/                  ← Domain model, data dictionary
    │   ├── engineering/             ← Coding standards, conventions, guardrails
    │   ├── execution/               ← Build order, dependency map, matrices
    │   ├── financial-core/          ← CFE, reconciliation, treasury, accounting
    │   ├── journeys/                ← 9 user journey walkthroughs
    │   ├── operations/              ← Observability, KPI catalog, command center
    │   ├── product/                 ← Vision 2026
    │   ├── roadmap/                 ← V1-V5 scope docs
    │   └── security/                ← Zero Trust model
    ├── plans/                        ← Build plans (V0 through V5)
    ├── features/                     ← Feature specs (Wallet, Settlement)
    ├── tasks/                        ← Task breakdowns (backend, Flutter, QA, DevOps, AI)
    ├── implementation/               ← Implementation guides
    ├── shared/                       ← Shared standards (security, design, testing, etc.)
    └── operations/runbooks/          ← Incident runbooks
```

### Module Internal Structure

Every module follows this strict pattern:

```
Module/
├── Controllers/       ← HTTP endpoint handlers (final classes)
├── Services/          ← Business logic (final classes)
├── Models/            ← Eloquent models (ULID PKs, $incrementing=false)
├── DTOs/              ← Data Transfer Objects
├── Enums/             ← PHP 8.1+ backed enums
├── Events/            ← Dispatchable events (final, SerializesModels)
├── Exceptions/        ← Domain-specific exceptions
├── Database/
│   ├── Migrations/    ← Table definitions (timestamped)
│   └── Factories/     ← Test factories
├── Http/Requests/     ← Form request validation
├── Routes/api.php     ← Module routes (prefix v1/{module})
├── Providers/         ← Service providers (bindings as singletons)
├── Tests/             ← Feature tests
└── Resources/lang/    ← Translations {en, ar, ku, hy}/messages.php
```

---

## 6. State Machines

### WalletTransaction (7 states, 9 transitions)

```
pending → processing → completed
                   → failed
                   → disputed → resolved
                   → expired
```

| From | To | Guard | Trigger | Allowed |
|------|----|-------|---------|---------|
| pending | processing | auth_check_passed AND limit_not_exceeded | User submits | System |
| processing | completed | cfe_posting_success | CFE confirms | System |
| processing | failed | cfe_posting_failure OR fraud_block | CFE error | System |
| processing | expired | hold_timeout > 30min | Scheduler | System |
| completed | disputed | within_24h AND user_initiated | User disputes | User |
| disputed | resolved | investigation_complete | Compliance resolves | Compliance |

**Timeout Rules:** Hold expires 30min, dispute window 24h, reversal window 7d, failed cleanup 90d

### Remittance (11 states, 15 transitions)

```
draft → rate_locked → aml_screening → fx_conversion → disbursing → completed
 |  → expired          → aml_hold      → failed
 |                     → sanctions_block
 |                     → failed
 → cancelled
```

**Timeout Rules:** Rate lock TTL 5min, AML screening 2min, disbursement 24h, AML hold 72h, sanctions hold 7d

### Agent Application (8 states, 9 transitions)

```
submitted → document_review → background_check → approved → active
          → rejected         → rejected          → rejected    → suspended → active
                                                                → terminated
```

**Timeout Rules:** Document review 48h, background check 5 business days, KYC re-verification 12 months

### Loan Application (12 states, 15 transitions)

```
draft → submitted → credit_check → risk_assessment → underwriting → approved → disbursing → active
 → cancelled      → declined      → declined        → declined                  → failed       → completed
                                                                                             → defaulted → collections
```

**Timeout Rules:** Credit check 30s, underwriting 4 business hours, grace period 15 days, default 90 days

### Settlement Batch (9 states, 11 transitions)

```
collecting → netting → reconciling → settling → completed
                        → exception → manual_review → retry → reconciling
                                                     → force_settle → settling
```

**Timeout Rules:** Collection 30min, auto-retry 3 attempts, manual review SLA 4h, batch cut-off 23:59

### Card (6 states, 9 transitions)

```
issued → active → suspended → active
                → frozen → active
                → cancelled
                → reported_stolen → cancelled
```

**Timeout Rules:** Activation 90 days, auto-freeze lift 72h, stolen confirmation 24h

### Savings Goal (4 states, 6 transitions)

```
active → paused → active
       → completed
       → cancelled
```

**Timeout Rules:** Auto-save retry 3 attempts, goal expiry 6 months past target

### Merchant (5 states, 8 transitions)

```
registered → verified → active → suspended → active
          → rejected           → terminated
```

**Timeout Rules:** Verification SLA 3 business days, chargeback ratio threshold >1.5%

### Bill Payment (6 states, 10 transitions)

```
initiated → pending_confirmation → confirmed → completed
          → failed                            → failed
          → expired
```

**Timeout Rules:** Initiation expiry 10min, confirmation window 5min, processing 30s per retry × 3

### User KYC (6 states, 10 transitions)

```
not_started → documents_uploaded → verification_pending → approved
                                   → manual_review → approved
                                                    → rejected → expired
```

**Timeout Rules:** Auto-verify 30s, manual review 24h, KYC expiry T1(never)/T2(5yr)/T3(3yr)

### User Account (5 states, 12 transitions)

```
pending_phone → active → suspended → active
                       → frozen → active
                       → closed
```

**Timeout Rules:** Phone verification 10min, suspension auto-escalate 30d, inactive 90d→dormant→365d→closure

---

## 7. Events Catalog

All **36 events** across **10 domains** follow CloudEvents 1.0 spec:

```json
{
  "specversion": "1.0",
  "id": "ulid",
  "source": "/beza/{domain}/{version}",
  "type": "com.beza.{domain}.{action}",
  "time": "ISO8601",
  "tenant_id": "string",
  "data": {}
}
```

### Wallet Domain

| Event | Producer | Trigger | Priority |
|-------|----------|---------|----------|
| `MoneyHeld` | CFE | Transfer initiated — hold placed | High |
| `MoneyReleased` | CFE | Transfer completed or hold expired | High |
| `MoneyPosted` | CFE | Funds credited to recipient | High |
| `BalanceUpdated` | Wallet | Any balance-affecting event | Medium |

### FX Domain

| Event | Producer | Trigger | Priority |
|-------|----------|---------|----------|
| `RateLocked` | FX Engine | Quote created with firm rate | High |
| `RateExpired` | FX Engine | Quote TTL reached | Medium |
| `ConversionCompleted` | FX Engine | FX conversion executed | High |
| `RateAnomalyDetected` | FX Engine | Rate deviation >5% from CBS | Critical |

### Remittance Domain

| Event | Producer | Trigger |
|-------|----------|---------|
| `RemittanceInitiated` | Remittance | Order created by sender |
| `RemittanceCompleted` | Remittance | Funds disbursed to recipient |
| `RemittanceFailed` | Remittance | Processing error |
| `CorridorRateApplied` | Remittance | Rate locked for corridor |

### Agent Domain

| Event | Producer | Trigger |
|-------|----------|---------|
| `AgentCashIn` | Agent | Cash deposit completed |
| `AgentCashOut` | Agent | Cash withdrawal completed |
| `AgentFloatLow` | Agent | Float below threshold |
| `AgentSuspended` | Compliance | Agent suspended |

### Merchant Domain

| Event | Producer | Trigger |
|-------|----------|---------|
| `MerchantPayment` | Merchant | Payment completed |
| `MerchantSettled` | Settlement | Batch settlement paid |

### Settlement Domain

| Event | Producer | Trigger |
|-------|----------|---------|
| `SettlementBatchStarted` | Settlement | EOD batch initiated |
| `SettlementBatchCompleted` | Settlement | All positions settled |
| `SettlementBatchFailed` | Settlement | Batch processing error |
| `ReconciliationMatched` | Settlement | Internal matches external |
| `ReconciliationException` | Settlement | Variance detected |

### Savings Domain

| Event | Producer | Trigger |
|-------|----------|---------|
| `SavingsGoalCreated` | Savings | New goal created |
| `SavingsGoalCompleted` | Savings | Target reached |
| `AutoSaveExecuted` | Savings | Scheduled auto-save |
| `RoundUpExecuted` | Savings | Round-up contribution |

### Cards Domain

| Event | Producer | Trigger |
|-------|----------|---------|
| `CardCreated` | Cards | New card issued |
| `CardTransaction` | Cards | Card used at POS/ATM |
| `CardFrozen` | Cards | Card frozen by user/system |
| `CardFraudAlert` | Fraud | Suspicious card activity |

### Compliance Domain

| Event | Producer | Trigger |
|-------|----------|---------|
| `KYCPending` | Identity | Documents submitted |
| `KYCApproved` | Identity | Verification passed |
| `KYCRejected` | Identity | Verification failed |
| `AMLRuleTriggered` | Compliance | Transaction monitoring alert |
| `SanctionsHit` | Compliance | Name match on sanctions list |

### System Domain

| Event | Producer | Trigger |
|-------|----------|---------|
| `ServiceHealthChanged` | Monitoring | Service status change |
| `QueueDepthAlert` | Monitoring | Queue exceeds threshold |
| `DatabaseReplicaLag` | Monitoring | Replica lag > threshold |
| `BackupCompleted` | System | Daily backup done |

---

## 8. Sequence Diagrams & Flows

All **12 critical flows** documented with text sequence diagrams covering happy path, failure modes, and resilience patterns:

### 1. Wallet Transfer
```
User → App → API → Service → CFE → Queue → Recipient
  |      |      |       |       |       |        |
  |--$-->|      |       |       |       |        |
  |      |--POST>|       |       |       |        |
  |      |      |--val-->|       |       |        |
  |      |      |       |--hold->|       |        |
  |      |      |       |<--held-|--evt->|        |
  |      |      |       |--post->|       |        |
  |      |      |       |<--post-|--evt->|        |
  |<--ok-|<--200<-------|       |       |--not-->|
```

**Failure modes:** Insufficient balance (402), fraud block (403), hold timeout (408)

### 2. Agent Cash-in
- **Happy path:** Customer → POS → API → Service → CFE (credit + commission)
- **Offline queue:** Agent POS stores locally → syncs when online
- **Reconciliation:** EOD batch matches internal vs bank transactions

### 3. FX Rate Lock + Conversion
- **Normal:** Quote → Rate fetch → Lock (15s TTL) → Convert → Hold → Post
- **Rate expired:** Auto-detected by scheduler → user re-quotes
- **Provider failover:** Primary timeout → health check → secondary provider

### 4. Remittance (Diaspora → Syria)
- **Normal:** EUR100 → Rate lock → AML check → Convert → Disburse to wallet
- **Sanctions hit:** World-Check match → BLOCK → Alert Compliance
- **AML review:** Flagged → Queue → 4h SLA → Escalate if missed

### 5. Merchant QR Payment
- **Normal:** Scan QR → Debit customer → Credit merchant → Fee → Receipt
- **Network loss:** Local queue → Flush when online → Confirm
- **Refund:** Refund → Validate → Reverse CFE → Notify

### 6. Savings Auto-save
- **Scheduled:** Scheduler → Fetch due goals → Debit wallet → Hold → Post
- **Insufficient:** 3 retry attempts → Auto-pause goal
- **Manual:** User pause/resume via PUT endpoint

### 7. Financing Disbursement
- Apply → Credit check → Risk assessment → Underwriter approve → Accept → CFE disburse → Installment schedule
- Repayment → Auto-deduct → Late penalty if overdue

### 8. Bill Payment
- **Fetch → Pay → Confirm:** Select biller → Query amount → Hold → Post → Notify biller → Confirm
- **Biller timeout:** 3 retries → Partial payment → Enqueue for ops

### 9. Settlement Batch
- **EOD collect → Net → Reconcile → Settle:** All transactions → Net positions → Match bank → RTGS submit
- **Mismatch:** Variance detected → Alert ops → Manual review → Force settle or retry

### 10. Card Transaction
- **Auth → Clear → Settle:** Tap → Auth request → Processor → Issuer → Hold
- **Decline + Chargeback:** Fraud decline → 2-week dispute → Chargeback → Reversal

### 11. User Registration
- **Phone → OTP → PIN → KYC → Wallet:** 5-step registration flow
- **Duplicate:** Phone already registered → 409 error

### 12. Compliance Screening
- **Sanctions → AML → Decision:** Transaction → World-Check query → AML rules → Allow/Hold/Block
- **False positive:** Compliance investigates → Confirms FP → Override → Clear

---

## 9. API Standards

### Base URL

```
https://api.beza.app/v1/{module}/{resource}
```

### Authentication — JWT Structure

```json
{
  "sub": "user_ulid",
  "role": "admin|user|agent",
  "permissions": ["wallet:read", "wallet:transfer"],
  "device_id": "device_fingerprint",
  "session_id": "session_ulid",
  "iat": 1516239022,
  "exp": 1516239922
}
```

**Token Lifecycle:** Access Token (15min) → Refresh Token (7d) → Device Token (permanent)

### Rate Limiting Tiers

| Tier | Limit | Applied To |
|------|-------|-----------|
| Anonymous | 10/min | Unauthenticated endpoints |
| Authenticated | 60/min | Standard user API |
| Authenticated (high) | 300/min | Admin API |
| Agent POS | 200/min | Agent terminal |
| Webhook | 500/min | Partner callbacks |
| Internal Service | 5000/min | Module→module |

### Response Envelope

```json
{
  "success": true,
  "data": {},
  "message": "string",
  "request_id": "ulid",
  "timestamp": "ISO8601"
}
```

### Pagination

| Type | Used For | Parameters |
|------|----------|-----------|
| Cursor-based | Real-time feeds | `cursor`, `limit` (default 20, max 100) |
| Offset-based | Admin/search | `page`, `per_page` (default 15, max 100) |

### Idempotency

- Header: `Idempotency-Key: uuid-v4`
- Cache duration: 24 hours
- Response cached for duplicate keys within window
- Return original response `200 OK` (not 201) for retries

### Naming Conventions

| Element | Convention | Example |
|---------|-----------|---------|
| Endpoints | kebab-case | `/v1/wallet/transfer-history` |
| Query params | snake_case | `?page=1&per_page=20` |
| Request body | camelCase | `{ "amount": 1000 }` |
| Response fields | camelCase | `{ "transactionId": "..." }` |

### Versioning Lifecycle

| Phase | Duration | Behavior |
|-------|----------|----------|
| Active | 12 months from release | Full support |
| Deprecated | 6 months warning | Header `Sunset: ...` |
| Removed | After 18 months | 410 Gone |

### Standard Headers

| Header | Purpose | Required |
|--------|---------|----------|
| `Authorization` | Bearer JWT | Auth endpoints |
| `X-Request-Id` | Trace ID | Yes |
| `Idempotency-Key` | Idempotency | Write endpoints |
| `Accept-Language` | ar/en | Yes |
| `User-Agent` | Client identification | Yes |
| `X-Device-Fingerprint` | Device binding | Auth endpoints |
| `X-Session-Id` | Session tracking | Auth endpoints |
| `X-Idempotency-Key` | Idempotency replay | Write endpoints |
| `Sunset` | Deprecation header | Deprecated endpoints |

---

## 10. Error Catalog

**126 error codes** across **14 domains** — all with Arabic and English messages:

| Domain | Error Count | Prefix | Examples |
|--------|------------|--------|----------|
| Wallet | 19 | WLT_ | WLT_INSUFFICIENT_BALANCE, WLT_DAILY_LIMIT_EXCEEDED |
| Agent | 12 | AGT_ | AGT_FLOAT_INSUFFICIENT, AGT_SUSPENDED |
| FX | 9 | FX_ | FX_RATE_EXPIRED, FX_CORRIDOR_UNAVAILABLE |
| Remittance | 8 | REM_ | REM_SANCTIONS_HIT, REM_LIMIT_EXCEEDED |
| Merchant | 7 | MCH_ | MCH_QR_EXPIRED, MCH_SUSPENDED |
| Bill Payment | 8 | BLL_ | BLL_INQUIRY_EXPIRED, BLL_PROVIDER_TIMEOUT |
| Card | 6 | CRD_ | CRD_FROZEN, CRD_STOLEN_REPORTED |
| Loan | 7 | LND_ | LND_SCORE_BELOW_MINIMUM, LND_DEFAULTED |
| Savings | 5 | SAV_ | SAV_INSUFFICIENT_BALANCE, SAV_GOAL_COMPLETED |
| Auth/Security | 12 | ATH_ | ATH_OTP_EXPIRED, ATH_PIN_LOCKED, ATH_DEVICE_NOT_BOUND |
| Compliance/KYC | 11 | CPL_ | CPL_KYC_REJECTED, CPL_SANCTIONS_MATCH |
| Settlement | 7 | STL_ | STL_RECONCILIATION_MISMATCH, STL_BATCH_FAILED |
| Fraud | 5 | FRD_ | FRD_BLOCKED, FRD_REVIEW_REQUIRED |
| System | 10 | SYS_ | SYS_INTERNAL_ERROR, SYS_SERVICE_UNAVAILABLE |

### Error Response Format

```json
{
  "success": false,
  "error": {
    "code": "WLT_INSUFFICIENT_BALANCE",
    "message": "Insufficient balance",
    "message_ar": "الرصيد غير كافٍ",
    "resolution": "Please deposit funds before retrying.",
    "resolution_ar": "يرجى إيداع الأموال قبل إعادة المحاولة.",
    "request_id": "01HXYZ..."
  }
}
```

### Retry Strategy

| HTTP Status | Retryable | Notes |
|-------------|-----------|-------|
| 400 Bad Request | No | Fix request |
| 401 Unauthorized | Yes | Refresh token |
| 402 Insufficient Balance | No | User action needed |
| 403 Forbidden | No | Permission issue |
| 404 Not Found | No | Resource missing |
| 408 Timeout | Yes | Exponential backoff |
| 409 Conflict | No | Idempotency/duplicate |
| 422 Validation Error | No | Fix payload |
| 429 Rate Limited | Yes | Retry-After header |
| 451 Unavailable (Legal) | No | Sanctions/legal block |
| 500 Server Error | Yes | Exponential backoff |
| 502 Bad Gateway | Yes | Service degraded |
| 503 Service Unavailable | Yes | Maintenance |

---

## 11. Domain Model

### 11 Bounded Contexts

| Context | Ubiquitous Language | Relationships |
|---------|-------------------|---------------|
| **Identity** | User, KYC, Device, PIN, Tier | Used by ALL contexts |
| **Wallet** | Wallet, Balance, Transaction, Hold, Limit | → Ledger |
| **Ledger** | Account, Entry, Journal, TrialBalance, GLCode | ← Wallet, ← Settlement, ← FX, ← Agent |
| **FX** | Rate, Quote, Conversion, Corridor, Spread | → Ledger, ← Wallet, ← Remittance |
| **Agent** | Agent, Float, CashIn, CashOut, Commission | → Wallet, → Ledger |
| **Merchant** | Merchant, Terminal, Payment, MDR, Settlement | → Wallet, → Settlement |
| **Settlement** | Batch, NetPosition, Reconciliation, CutOff | → Ledger, → Bank |
| **Remittance** | Order, Corridor, Beneficiary, Payout | → FX, → Ledger, → Wallet |
| **Compliance** | Case, Screening, Rule, SAR, Alert, PEP | ← ALL contexts |
| **Treasury** | Forecast, Position, Float, Reserve | → Ledger, ← ALL contexts |
| **Notification** | Template, Channel, Event, Outbox, Delivery | ← ALL contexts |

### Bounded Context Relationship Diagram

```
┌──────────┐     ┌──────────┐     ┌──────────┐
│ Identity │────▶│  Wallet  │────▶│  Ledger  │
└──────────┘     └────┬─────┘     └────┬─────┘
               ┌──────▼──────┐  ┌──────▼──────┐
               │     FX      │─▶│  Settlement │─▶ Bank
               └──────┬──────┘  └──────┬──────┘
          ┌───────────▼───┐  ┌────────▼────────┐
          │   Remittance  │  │    Merchant      │
          └───────────┬───┘  └────────▲────────┘
               ┌──────▼──────┐  ┌─────┴────────┐
               │   Agent     │──│  Treasury     │
               └─────────────┘  └──────────────┘

┌──────────────┐     ┌─────────────┐     ┌──────────────┐
│  Compliance  │◀────│ ALL CONTEXTS│────▶│ Notification │
│  (screening) │     │  (events)   │     │ (dispatcher) │
└──────────────┘     └─────────────┘     └──────────────┘
```

### Key Aggregate Roots

| Context | Aggregate | Key Attributes |
|---------|-----------|---------------|
| Identity | User | user_id (ULID), full_name_ar, full_name_en, national_id, phone, kyc_level, kyc_status, status, device_ids |
| Wallet | Wallet | wallet_id (ULID), user_id, currency (SYP/USD), balance (BigInt), status, tier_id |
| Wallet | Transaction | txn_id (ULID), from_wallet_id, to_wallet_id, amount, fee, status, hold_id |
| Ledger | Account | account_id (ULID), gl_code, name, type (asset/liability/equity/income/expense), balance |
| Ledger | JournalEntry | entry_id (ULID), entry_date, description, lines[], status |
| FX | Quote | quote_id (ULID), from_currency, to_currency, rate, amount, expires_at |
| Agent | Agent | agent_id (ULID), user_id, float_balance, status, governorate, commission_tier |
| Merchant | Merchant | merchant_id (ULID), store_name, mdr_rate, settlement_account, status |
| Settlement | Batch | batch_id (ULID), batch_date, net_positions[], status, settled_at |
| Remittance | Order | order_id (ULID), corridor_id, sender_id, beneficiary_id, amount, fx_rate, status |
| Compliance | Case | case_id (ULID), entity_type, entity_id, rule_id, score, status, decision |

---

## 12. Data Dictionary

### Entity Identifiers (ULID Format)

| Entity | Prefix | Example |
|--------|--------|---------|
| User | `usr_` | usr_01HXYZ... |
| Wallet | `wlt_` | wlt_01HXYZ... |
| Transaction | `txn_` | txn_01HXYZ... |
| Agent | `agt_` | agt_01HXYZ... |
| Merchant | `mch_` | mch_01HXYZ... |
| Remittance Order | `rem_` | rem_01HXYZ... |
| Settlement Batch | `stl_` | stl_01HXYZ... |
| Card | `crd_` | crd_01HXYZ... |
| Loan | `lnd_` | lnd_01HXYZ... |
| Savings Goal | `sav_` | sav_01HXYZ... |
| Fraud Case | `frd_` | frd_01HXYZ... |
| Compliance Case | `cpl_` | cpl_01HXYZ... |
| KYC Profile | `kyc_` | kyc_01HXYZ... |
| Device | `dev_` | dev_01HXYZ... |
| Session | `ses_` | ses_01HXYZ... |
| OTP | `otp_` | otp_01HXYZ... |
| Notification | `ntf_` | ntf_01HXYZ... |
| FX Quote | `fxq_` | fxq_01HXYZ... |
| Ledger Account | `gla_` | gla_01HXYZ... |
| Journal Entry | `jrn_` | jrn_01HXYZ... |
| Product | `prd_` | prd_01HXYZ... |
| Order | `ord_` | ord_01HXYZ... |
| Vendor | `vnd_` | vnd_01HXYZ... |
| Escrow | `esc_` | esc_01HXYZ... |

### Money Amount Fields

| Field Type | Precision | Storage | Example |
|-----------|-----------|---------|---------|
| SYP amount | BigInt (minor units) | SYP is smallest unit → no decimals | 100000 = 100,000 SYP |
| USD amount | BigInt (cents) | 2 decimal places × 100 | 10000 = $100.00 |
| Fee | BigInt | Same as currency | 500 = 5 SYP fee |
| FX Rate | BigInt (×100,000) | 5 decimal places | 1250000 = 12.50000 |
| Percentage | BigInt (×100) | 2 decimal places | 150 = 1.50% |
| Commission | BigInt | Same as currency | 2500 = 25 SYP |
| Balance | BigInt | Up to 9×10^18 | 50000000 = 50M SYP |
| Limit | BigInt | Per configuration | 5000000 = 5M daily limit |
| Hold | BigInt | Amount held | 100000 = 100K held |
| Discount | BigInt | Percentage × 100 | 500 = 5.00% discount |
| Points | Integer | Whole points | 1500 points |
| Count | Integer | Whole numbers | 42 transactions |

### Enums (30+ Sets)

| Domain | Enum | Values |
|--------|------|--------|
| Identity | kyc_level | NONE, BASIC, ENHANCED, FULL |
| Identity | kyc_status | PENDING, VERIFIED, REJECTED, EXPIRED |
| Identity | user_status | ACTIVE, SUSPENDED, CLOSED |
| Identity | gender | MALE, FEMALE |
| Identity | governorate | Damascus, Aleppo, Homs, Hama, Latakia, Tartous, DeirEzZor, Hasakeh, Raqqa, Idlib, Daraa, Suwayda, Quneitra, Rural Damascus |
| Wallet | wallet_status | PENDING, ACTIVE, FROZEN, LIMITED, SUSPENDED, CLOSED |
| Wallet | transaction_status | PENDING, PROCESSING, COMPLETED, FAILED, DISPUTED, RESOLVED, EXPIRED |
| Agent | agent_status | SUBMITTED, DOCUMENT_REVIEW, BACKGROUND_CHECK, APPROVED, ACTIVE, SUSPENDED, TERMINATED |
| Merchant | merchant_status | REGISTERED, VERIFIED, ACTIVE, SUSPENDED, TERMINATED, REJECTED |
| Remittance | remittance_status | DRAFT, RATE_LOCKED, AML_SCREENING, AML_HOLD, SANCTIONS_BLOCK, FX_CONVERSION, DISBURSING, COMPLETED, FAILED, EXPIRED, CANCELLED |
| FX | quote_status | ACTIVE, LOCKED, EXPIRED, CONVERTED |
| Settlement | batch_status | COLLECTING, NETTING, RECONCILING, SETTLING, COMPLETED, FAILED, EXCEPTION |
| Card | card_status | ISSUED, ACTIVE, SUSPENDED, FROZEN, CANCELLED, REPORTED_STOLEN |
| Savings | goal_status | ACTIVE, PAUSED, COMPLETED, CANCELLED |
| Loan | loan_status | DRAFT, SUBMITTED, CREDIT_CHECK, RISK_ASSESSMENT, UNDERWRITING, APPROVED, DISBURSING, ACTIVE, COMPLETED, DEFAULTED |
| Fraud | fraud_decision | ALLOW, BLOCK, REVIEW |
| Compliance | case_status | OPEN, INVESTIGATING, ESCALATED, RESOLVED, REJECTED |
| Compliance | screening_result | CLEAR, HIT, ERROR |

### Field Classification Matrix

| Level | Description | Examples |
|-------|-------------|---------|
| Critical | Financial integrity, PII, auth secrets | balance, pin_hash, kyc_docs, national_id |
| Restricted | Business-sensitive operational data | fee_config, risk_rules, float_balance |
| Confidential | Internal operations data | agent_location, device_fingerprint, ip_address |
| Internal | Non-sensitive operational data | timestamps, reference_numbers, status |
| Public | Non-sensitive display data | product_names, governorate_list, public_rates |

---

## 13. Financial Core (CFE)

### CFE V2 — 10 Sub-Engines

| Engine | Responsibility | Key Methods |
|--------|---------------|-------------|
| Account | Account CRUD, status management, hierarchy | create(), activate(), suspend(), close() |
| Balance | Balance queries, projections, history | getBalance(), project(), getHistory() |
| Hold | Earmark funds for pending transactions | hold(), release(), capture(), expire() |
| Posting | Double-entry journal posting | post(), batchPost(), reverse() |
| Fee | Fee calculation and assessment | calculateFee(), assessFee(), waiveFee() |
| FX | Multi-currency conversion at posting | convert(), getRate() |
| Settlement | Batch settlement processing | net(), settle(), reconcile() |
| Reserve | Reserve requirement management | calculateReserve(), setReserve() |
| Reversal | Full/partial reversal of posted entries | reverse(), reversePartial() |
| Liquidity | Liquidity position monitoring | getPosition(), projectNeeds() |

### Transaction Lifecycle State Machine

```
Init → Auth → Approved  → Posting → Settlement → Completed
              → Rejected                       → Reversed
                                                → Expired
```

### Hold Engine — 7 States, 5 Methods

**States:** Available → Held → Blocked → Posted → Released → Disputed
**Methods:** hold(), release(), capture(), expire(), getActiveHolds(), getHoldSummary()

### CFE Internal API

```
POST /internal/cfe/hold       → { account_id, amount, currency, reason }
POST /internal/cfe/release    → { hold_id, reason }
POST /internal/cfe/post       → { from_account, to_account, amount, currency, fee }
```

### Double-Entry Example (P2P Transfer 10,000 SYP)

```
Step 1: Hold
  Dr. Suspense (1500)          10,000
      Cr. Customer Wallet           10,000

Step 2: Post
  Dr. Customer Wallet          10,000
      Cr. Beneficiary Wallet        9,950
  Dr. Customer Wallet              50
      Cr. Fee Income                   50

Step 3: Settle
  Dr. Beneficiary Wallet        9,950
      Cr. Settlement Account         9,950
```

### Chart of Accounts (GL Codes)

| Range | Category | Examples |
|-------|----------|---------|
| 1xxxx | Assets | Customer Wallets, Settlement Accounts, Agent Float |
| 2xxxx | Liabilities | Customer Deposits, Suspense Accounts |
| 3xxxx | Equity | Retained Earnings, Paid-in Capital |
| 4xxxx | Income | Fee Income, FX Spread Income, Commission Income |
| 5xxxx | Expenses | Operating Expenses, Provision for Losses |
| 6xxxx | Contra | Contra Assets (for chargebacks, reversals) |
| 7xxxx | Off-Balance Sheet | Guarantees, Commitments |

### Fee Schedule

| Fee Type | Rate | Cap | Payor | Recipient |
|----------|------|-----|-------|-----------|
| P2P Transfer | 0.5% | 5,000 SYP | Sender | Beza |
| Agent Cash-in | 0.5% | 2,000 SYP | Customer | Agent |
| Agent Cash-out | 1.0% | 3,000 SYP | Customer | Agent |
| FX Conversion | 1.5% spread | None | Customer | Beza |
| Remittance | 2-3% | Per corridor | Sender | Beza + Corridor |
| Merchant MDR | 0.8% | Per merchant | Merchant | Beza |
| Bill Payment | 1.0% | Per biller | Customer | Beza + Biller |
| Loan Origination | 0% | None | Borrower | Beza |

### Product Catalog (8 Products)

| Code | Product | Min | Max (T3) | Fee | KYC Required |
|------|---------|-----|----------|-----|-------------|
| WLT-001 | P2P Transfer | 100 SYP | 10M SYP/day | 0.5% | Tier 1 |
| WLT-002 | Agent Cash-in | 1,000 SYP | 5M SYP/day | 0.5% | Tier 1 |
| WLT-003 | Agent Cash-out | 1,000 SYP | 3M SYP/day | 1.0% | Tier 1 |
| BLL-001 | Bill Payment | 100 SYP | 10M SYP | 1.0% | Tier 1 |
| MCH-001 | Merchant Payment | 100 SYP | 5M SYP/day | 0.8% MDR | Tier 1 |
| REM-001 | Remittance | €50 | €5,000 | 2-3% | Tier 2 |
| FX-001 | FX Conversion | 1,000 SYP | No limit | 1.5% spread | Tier 1 |
| LND-001 | Murabaha Loan | 50,000 SYP | 5M SYP | 15-25% markup | Tier 3 |

---

## 14. Design Language 2026

### Brand DNA

| Attribute | Expression |
|-----------|------------|
| **Trust** | Clean typography, clear hierarchy, transparent fees |
| **Speed** | Skeleton loading, instant transitions, optimistic UI |
| **Simplicity** | One primary action per screen, progressive disclosure |
| **Accessibility** | 44pt touch targets, WCAG AA+, RTL-native |
| **Modernity** | Glassmorphism, micro-interactions, fluid animations |
| **Syrian Identity** | Warm color palette, Arabic calligraphy accents |

### Color System

```
Primary:    #0D7C4A (Deep Green)    — Trust, Growth, Islamic Finance
Secondary:  #C8962E (Damascus Gold) — Value, Premium, Heritage
Accent:     #E8613A (Warm Orange)   — Action, Energy, Urgency
Success:    #22A67E (Emerald)       — Completed, Confirmed
Warning:    #F5A623 (Amber)         — Pending, Attention
Error:      #D32F2F (Crimson)       — Failed, Blocked
Info:       #2C6BED (Royal Blue)   — Information
Neutral:    #1A1A1A → #F7F7F7 (10 shades)
```

### Typography

| Style | Size/Line Height | Usage |
|-------|-----------------|-------|
| Display | 32/36 | Hero headlines |
| Title 1 | 24/28 | Screen titles |
| Title 2 | 20/24 | Section headers |
| Body | 16/22 | Primary content |
| Body Small | 14/20 | Secondary content |
| Caption | 12/16 | Labels, timestamps |
| Micro | 10/14 | Legal, footnotes |

**Fonts:** Noto Sans Arabic (system, 4 weights) + JetBrains Mono (amounts, codes)

### Component Architecture

**Buttons:** Primary, Secondary, Ghost, Danger, FAB — 5 states (default, pressed, loading, disabled)
**Inputs:** Text, Amount, Phone, PIN, OTP, Search — 5 states (default, focused, error, success, disabled)
**Cards:** Elevation, Border, Selection, Skeleton, Goal, Transaction
**Navigation:** Bottom Tab (5 max), Top Tab, Navigation Rail (tablet), Slide Drawer, USSD Menu

### Motion System

| Duration | Use |
|----------|-----|
| 200ms | Micro-interactions (button press) |
| 300ms | Standard transitions |
| 400ms | Emphasis (success animations) |
| 600ms | Page transitions |

**Easing:** easeOutCubic (entering), easeInOutCubic (pages), spring(60,6) (interactive)

### Screen Architecture Template

Every screen defines:
- **Screen Name** | **Business Goal** | **Psychological Goal** | **Trust Signal**
- **Layout Structure:** Header → Content → Footer → Bottom Sheet (optional)
- **States:** Loading (skeleton) → Empty (illustration + CTA) → Error (retry) → Offline (cached data) → Slow (spinner) → Success (confetti/checkmark)
- **Edge Cases:** Zero balance, network timeout, expired session, concurrent access, partial completion

---

## 15. Security Model

### Zero Trust — 5 Principles

1. **Never trust, always verify** — Every request authenticated and authorized
2. **Least privilege** — Minimal permissions per role, just-in-time elevation
3. **Assume breach** — Encrypt at rest and in transit, audit everything
4. **Micro-segmentation** — Module boundaries enforced at code level, not network
5. **Continuous verification** — Session validation on every request, device binding

### Multi-Factor Authentication Strategy

| Factor | Implementation | Used For |
|--------|---------------|----------|
| Something you know | PIN (6 digits, bcrypt hashed) | All transactions |
| Something you have | SMS OTP / TOTP app | Registration, login, large transfers |
| Something you are | Face ID / Fingerprint | High-value transactions > 500K SYP |
| Behavioral | Typing pattern, touch pressure | Passive fraud detection |

### JWT Token Architecture

| Token | TTL | Storage | Purpose |
|-------|-----|---------|---------|
| Access Token | 15 minutes | Memory (Flutter) | API authorization |
| Refresh Token | 7 days | Secure storage | Token rotation |
| Device Token | Permanent | Device keystore | Device binding |

### Data Encryption

| Data Type | At Rest | In Transit |
|-----------|---------|------------|
| PIN hash | bcrypt (cost 12) | TLS 1.3 |
| KYC documents | AES-256-GCM + envelope | TLS 1.3 |
| National ID | AES-256-GCM | TLS 1.3 |
| Balance data | MySQL TDE | TLS 1.3 |
| API keys | Hashicorp Vault | TLS 1.3 |
| Session tokens | Redis encrypted | TLS 1.3 |

---

## 16. Plans & Roadmaps

### Product Tiers

```
TIER A  (V1,  M1-6):   Wallet, Agent, FX, Fraud, Remittance, Bills, Merchant QR
TIER B  (V1.5, M7-10): Payroll, Savings, Settlement
TIER C  (V2,   M11-16): Cards, Loyalty, Government Collections
TIER D  (V3,   M17-24): Financing, Education, Humanitarian, Open Finance
TIER E  (V4,   M25-36): Marketplace, Escrow, Takaful, Investments
TIER F  (V5,   M37+):   Regional Expansion, Social Commerce
```

### Gantt Timeline (Months 1-24)

```
Month      1  2  3  4  5  6  7  8  9 10 11 12 13 14 15 16 17 18 19 20 21 22 23 24
TIER A ──────────────────────────────────────────────────────────────────────────────
Wallet     ██ ██ ██ ██ ██ ██
Agent Net              ██ ██ ██ ██
FX                         ██ ██ ██ ██
Fraud Mgmt                   ██ ██ ██ ██ ██ ██
Remittance                                     ██ ██ ██ ██
Bill Pay                                           ██ ██ ██ ██
Merchant QR                                                        ██ ██ ██ ██
TIER B ──────────────────────────────────────────────────────────────────────────────
Payroll                                                                   ██ ██ ██
Savings                                                                            ██ ██ ██
Settlement                                                                                      ██ ██ ██
TIER C ─────────────────────────────────────────────────────────────────────────────────────────────
Cards                                                                                                       ██ ██ ██ ██ ██ ██
Loyalty                                                                                                                    ██ ██ ██ ██
Gov Collections                                                                                                              ██ ██ ██ ██
TIER D ────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────
Financing                                                                                                                              ██ ██ ██ ██
Education                                                                                                                                      ██ ██ ██
Humanitarian                                                                                                                                      ██ ██ ██
Open Finance                                                                                                                                                     ██ ██ ██
```

### Build Plans (in `.opencode/plans/`)

| Plan | Version | Codename | Focus |
|------|---------|----------|-------|
| `خطة_بناء_beza_v0_الأسس.plan.md` | V0 | الأسس (Foundations) | Identity, IAM, Ledger, CFE |
| `خطة_بناء_beza_v1_152cd23a.plan.md` | V1 | الإطلاق (Launch) | Wallet, Agent, FX, Fraud, Remittance, Bills, Merchant |
| `خطة_بناء_beza_v2_69780e8a.plan.md` | V2 | التوسع (Expansion) | Cards, Loyalty, GovCollections |
| `خطة_بناء_beza_v3_7f921b42.plan.md` | V3 | التمويل (Financing) | Financing, Education, Humanitarian, OpenFinance |
| `خطة_بناء_beza_v4_7cc85ffd.plan.md` | V4 | السوق (Marketplace) | Marketplace, Escrow, Takaful, Investments |
| `خطة_بناء_beza_v5_e4ef8424.plan.md` | V5 | الإقليم (Regional) | Regional expansion, social commerce |

### Scope Documents

| Document | Products | Status |
|----------|----------|--------|
| `02-v1-scope.md` | Wallet, Agent, FX, Fraud, Remittance, Bills, Merchant | ✅ Complete |
| `03-v2-scope.md` | Cards, Loyalty, GovCollections | ✅ Complete |
| `04-v3-scope.md` | Financing, Education, Humanitarian, OpenFinance | ✅ Complete |
| `05-v4-scope.md` | Marketplace M1-M4, Escrow, Takaful, Investments | ✅ Complete |

---

## 17. Build Order & Dependencies

### 6-Phase Build Plan (24 Weeks)

| Phase | Weeks | Modules | Key Deliverable |
|-------|-------|---------|----------------|
| **Phase 1:** Foundation | 1-4 | Project scaffold, MySQL, Redis, Docker, CI/CD | Monorepo ready |
| **Phase 2:** Core Financial | 5-8 | Identity, IAM, Ledger, CFE | Financial engine operational |
| **Phase 3:** User Features | 9-12 | Wallet, Agent, Settlement | Users can store and move money |
| **Phase 4:** Revenue Features | 13-16 | FX, Remittance, Bills, Merchant | Platform generates revenue |
| **Phase 5:** Ops & Compliance | 17-20 | Fraud, Payroll, Compliance | Risk management operational |
| **Phase 6:** Launch Prep | 21-24 | Testing, audit, load testing, launch | V1 production go-live |

### Critical Dependency Insight

**Ledger must precede Wallet.** Without the accounting engine, wallet balances cannot be reliably tracked. CFE sits between all modules and the Ledger.

### Dependency Matrix

| Module | Depends On | Blocks | Shared Services |
|--------|-----------|--------|----------------|
| Identity | — | All modules | SMS, Storage |
| IAM | Identity | Admin, Permissions | Cache |
| Ledger | — | CFE, Settlement | Database |
| CFE | Ledger, Identity | Wallet, FX, Agent, Settlement, Remittance | DB, Queue |
| Wallet | Identity, CFE | Merchant, Agent, Remittance | Cache, Queue |
| Agent | Identity, Wallet, CFE | Settlement | SMS, Queue |
| Settlement | CFE, Wallet, Agent, Merchant | Treasury | Queue, Batch |
| FX | CFE | Remittance, Wallet | Rate Provider |
| Remittance | Identity, FX, Wallet, CFE, Compliance | Settlement | Queue, Screening |
| Fraud | All modules | Transaction processing | ML Engine |

---

## 18. User Journeys

### 15 Primary User Journeys

| # | Journey | Actor | Syria-Specific |
|---|---------|-------|----------------|
| 1 | **First-time user** | Unregistered citizen | Phone+OTP+PN+basic profile → Tier 1 active |
| 2 | **KYC upgrade** | Tier 1 user | National ID upload, selfie → Tier 2 (5M SYP/day) |
| 3 | **First transfer** | Active user | Send 500 SYP to contact, PIN confirmation, receipt |
| 4 | **Receive remittance** | Beneficiary | Diaspora sender → corridor rate → wallet credit/cash pickup |
| 5 | **Agent cash-out** | Cash user | Find agent, QR scan, PIN, agent gives cash, SMS receipts |
| 6 | **Payroll receipt** | Employee | Employer CSV upload → bulk disbursement → SMS notification |
| 7 | **Merchant payment** | Customer | Scan static QR → enter amount → PIN → confirmation |
| 8 | **Dispute resolution** | Customer | Report issue → support reviews (txn/device/logs) → decision |
| 9 | **Fraud review** | Fraud analyst | Alert triggered → case created → review → block/allow/flag |
| 10 | **Bill payment** | Household | Select biller → inquire → pay → receipt |
| 11 | **Savings goal** | Saver | Create goal → auto-save → track progress → complete |
| 12 | **Loan application** | Borrower | Apply → credit check → underwriter → accept → disburse |
| 13 | **School fee payment** | Parent | Search institution → student ID → fee inquiry → pay → QR receipt |
| 14 | **Humanitarian aid** | Beneficiary | Organization → program → voucher/cash → agent pickup |
| 15 | **Open Finance API** | Developer | Register app → consent → OAuth → API calls → webhooks |

### Syria-Specific UX Principles

1. **Offline-first:** Syria has unreliable internet; all critical flows work offline
2. **Dual SIM:** Handle multi-SIM (Syriatel + MTN) for OTP routing
3. **Low literacy:** Icon-driven UI, voice guidance for key flows
4. **Agent-assisted:** 40% of users will onboard via agents, not self-service
5. **USSD fallback:** *123# for feature phones (30% of target users)
6. **Cash culture:** All digital flows must have a cash off-ramp (agent cash-out)
7. **Trust signals:** Receipts, SMS confirmations, visible fee disclosure

### Syria-Specific Test Scenarios

1. Network timeout during P2P transfer (intermittent connectivity)
2. Dual SIM phone with different carriers for OTP delivery
3. Low balance (5,000 SYP) attempting 10,000 SYP transfer
4. Agent float shortage during cash-out peak
5. Power outage during batch settlement processing
6. CBS rate feed delayed beyond cut-off time
7. Sanctions list update during active remittance processing
8. Court order freeze on specific wallet

---

## 19. API Matrix

### Internal API (Module→Module)

150+ API relationships across 13 internal services, 8 external systems, and 5 client types.

### External API (Beza→3rd Party)

| Category | Integration | Protocol |
|----------|-------------|----------|
| SMS Gateway | Syriatel SMPP, MTN SMPP | SMPP 3.4 |
| Rate Feed | CBS (official fixing) | SOAP/XML |
| Remittance | Corridor partners (Western Union, etc.) | REST |
| Bill Payment | Syriatel, MTN, PEED, STE, Water, MoF | SFTP CSV / REST |
| Compliance | World-Check, OFAC, UNSCR | REST / HTTPS file |
| Banking | CBS RTGS, BSO, Bemo, SIIB | SWIFT MT / SFTP / REST |
| KYC | Syrian Civil Registry (optional) | REST |
| Merchant | Bemo POS terminals | ISO 8583 |

### API SLA Summary

| Tier | P99 Latency | Availability | Examples |
|------|-------------|-------------|---------|
| Critical | <200ms | 99.99% | Balance reads, holds, transfers |
| High | <500ms | 99.95% | Quotes, bill inquiry, remittance |
| Medium | <2s | 99.9% | Reports, history search |
| Low | <5s | 99.5% | Batch operations, exports |
| External | <5s | 99.5% | Biller APIs, SMS, screening |

---

## 20. Ledger Impact Matrix

### 42 Financial Operations × Double-Entry Impact

| Category | Operations | GL Codes | CFE Events |
|----------|-----------|----------|------------|
| P2P Transfers | 4 | 1100, 2100, 4100 | hold, post, fee |
| Agent Cash-In | 3 | 1100, 1200, 4100 | credit, commission |
| Agent Cash-Out | 3 | 1100, 1200, 5100 | debit, fee |
| FX Conversion | 4 | 1100, 2100, 4200 | convert, hold, post |
| Remittance Outbound | 6 | 1100, 2100, 2200, 4200 | hold, convert, disburse |
| Bill Payments | 6 | 1100, 2100, 4100 | hold, post, settle |
| Merchant Payments | 3 | 1100, 2100, 4100, 2200 | hold, capture, settle |
| Payroll | 3 | 1100, 2100, 5100 | disburse, fees |
| Agent Float | 2 | 1200, 1100 | topup, reconcile |
| Settlement | 4 | 2200, 1100, 2100 | net, settle, reconcile |
| System/Corrections | 4 | All | reverse, adjust, close |

### 40+ GL Account Types

| Code | Name | Type | Normal Balance |
|------|------|------|---------------|
| 1100 | Customer Wallets | Asset | Debit |
| 1200 | Agent Float | Asset | Debit |
| 1300 | Settlement Accounts | Asset | Debit |
| 1400 | Bank Accounts | Asset | Debit |
| 1500 | Suspense Accounts | Asset | Debit |
| 2100 | Customer Deposits | Liability | Credit |
| 2200 | Settlement Payable | Liability | Credit |
| 2300 | Agent Commission Payable | Liability | Credit |
| 3100 | Retained Earnings | Equity | Credit |
| 4100 | Fee Income | Income | Credit |
| 4200 | FX Spread Income | Income | Credit |
| 4300 | Commission Income | Income | Credit |
| 5100 | Operating Expenses | Expense | Debit |
| 5200 | Provision for Losses | Expense | Debit |

### Reconciliation Rules (REC-001 to REC-009)

1. Daily GL balance = sum of sub-ledger balances
2. All holds resolved within 24h (either posted or released)
3. Suspense account = 0 at EOD
4. Settlement batch totals = bank statement credits
5. Agent float physical = system float balance (±1% tolerance)
6. FX position net = 0 at EOD (all conversions settled)
7. Fee income = sum of all fee transactions
8. Trial balance = 0 (debits = credits)
9. No unreconciled items > 7 days

---

## 21. Production Readiness

### Launch Checklist — 200+ Items Across 8 Domains

#### 1. Infrastructure (32 items)

| Category | Key Items |
|----------|-----------|
| Compute & Hosting | Production VMs, auto-scaling, load balancers, CDN |
| Networking | TLS certs, DNS, firewall rules, WAF, DDoS protection |
| Monitoring | Uptime checks, synthetic monitoring, APM (Datadog) |
| Alerting | PagerDuty on-call, SMS/email escalation, SLA breach alerts |
| Backup & DR | Hourly DB snapshots, daily full backup, cross-region DR test |

#### 2. Security (29 items)

| Category | Key Items |
|----------|-----------|
| Application | OWASP Top 10 scan, JWT rotation, rate limiting, input validation |
| Data | Encryption at rest, KMS for secrets, PII classification, log masking |
| Infrastructure | OS hardening, container scanning, network isolation, WAF rules |

#### 3. Regulatory & Compliance (28 items)

| Category | Key Items |
|----------|-----------|
| Licensing | CBS e-money license, banking partner agreements |
| AML/CFT | Sanctions screening live, transaction monitoring, SAR workflow |
| CBS Reporting | Daily/Weekly/Monthly reports configured, audit trail ready |
| Data Protection | CMT compliance, data retention policy, breach notification plan |
| Sharia | Sharia board approval, annual audit, product certificates |

#### 4. Operations (29 items)

| Category | Key Items |
|----------|-----------|
| Support | Help desk system, call center, SLA matrix, escalation tree |
| Agent Network | Onboarding process, float management, commission structure |
| Banking Partners | Settlement accounts open, BSO/Bemo/SIIB connectivity tested |
| Telco Partners | Syriatel/MTN SMPP live, USSD short code approved |

#### 5. Testing (47 items)

| Category | Key Items |
|----------|-----------|
| Performance | Load test 500 TPS, stress test 2× peak, soak test 24h |
| Resilience | Chaos engineering: DB failover, Redis failover, network partition |
| Security | Pen test, vulnerability scan, dependency audit, third-party review |
| Functional | 24 end-to-end scenarios covering all 15 user journeys |

#### 6. Launch Checklist (48 items at T-7 days)

| Group | Items |
|-------|-------|
| L1-L8 | Feature readiness: Wallet, Agent, FX, Fraud, Remittance, Bills, Merchant, Compliance |
| L9-L15 | Operations: Support, monitoring, runbooks, escalation, communication channels |
| L16-L22 | Agent network: 500 agents, float funded, training complete, POS installed |
| L23-L30 | Financial: Bank accounts active, settlement tested, fee configuration verified |
| L31-L36 | Regulatory: CBS license displayed, Sharia approval posted, AML officer on-duty |
| L37-L42 | Communications: Press release, user SMS campaign, agent notification, social media |
| L43-L48 | Rollback: Decision criteria, revert process, database restore tested, contacts listed |

#### 7. Post-Launch (First 72 hours)

| Window | Focus |
|--------|-------|
| T+0 to T+4 | War room active, every incident logged, no code deploys |
| T+4 to T+24 | Bug fixes only (no features), monitor KPIs hourly |
| T+24 to T+48 | Review performance data, adjust rate limits if needed |
| T+48 to T+72 | First KPI review: DAU, success rates, agent activity |
| Week 1 | Stabilization sprints, 24/7 on-call |
| Week 2 | Performance tuning, agent feedback collection |
| Month 1 | Full KPI review, retrospective, V1.5 planning |

---

## 22. Testing

### Backend Test Suite

| Suite | Tests | Assertions | Notes |
|-------|-------|------------|-------|
| Auth & Identity | 75 | 150+ | Registration, OTP, PIN, login, device binding |
| Wallet | 8 | 20+ | Create, transfer, limits, holds |
| Ledger | 5 | 15+ | Journal entries, trial balance, accounts |
| CFE | 5 | 15+ | Posting, holds, fees, reversals |
| Agent | 6 | 18+ | Registration, cash-in/out, commissions |
| Settlement | 3 | 9+ | Batch creation, netting, reconciliation |
| FX | 3 | 9+ | Quotes, conversions, rate expiry |
| Remittance | 3 | 9+ | Orders, corridors, screenings |
| Merchant | 4 | 12+ | Registration, payments, refunds |
| Bills | 4 | 12+ | Inquiry, payment, provider adapters |
| Fraud | 3 | 9+ | Rules, scoring, cases |
| Payroll | 3 | 9+ | Batch, disbursement, CSV import |
| Savings | 3 | 9+ | Goals, contributions, auto-save |
| Cards | 3 | 9+ | Card lifecycle, disputes |
| Loyalty | 3 | 9+ | Points, tiers, cashback |
| GovCollections | 3 | 9+ | Inquiry, payment, admin |
| Financing | 4 | 12+ | Loans, credit scoring, BNPL, installments |
| Education | 6 | 18+ | Institutions, students, fees, CSV bulk |
| Humanitarian | 7 | 21+ | Programs, disbursements, OFAC screening |
| OpenFinance | 6 | 18+ | OAuth, payment initiation, webhooks |
| Escrow | 6 | 16+ | Agreements, holds, disputes |
| Marketplace M1 | 6 | 13+ | Catalog, orders, vendors |
| Marketplace M2 | 8 | 25+ | Gift cards, promos, loyalty |
| Marketplace M3-M4 | 8 | 30+ | Shipping, COD, API, rate limits |
| Takaful | 6 | 21+ | Products, policies, claims |
| Investments | 6 | 20+ | Funds, NAV, subscribe, redeem, Zakat |
| Admin | 8 | 24+ | Dashboard endpoints (8 modules) |
| USSD | 3 | 9+ | Menu engine, sessions |
| Feature tests | 30 | 42+ | Health, auth flows, IAM, API |
| **Total** | **361** | **842** | **All passing** |

### Mobile Test Suite (62 tests)

```bash
cd frontend/mobile
flutter test
```

### Admin Tests

```bash
cd frontend/admin
npm test
```

---

## 23. KPI Catalog

### 18 Operational KPIs

| # | KPI | Formula | Target | Frequency |
|---|-----|---------|--------|-----------|
| KPI-001 | Daily Active Users | Unique users with ≥1 txn | 5,000 (V1 M1) | Daily |
| KPI-002 | Monthly Active Users | Unique users with ≥1 txn/month | 20,000 (V1 M1) | Monthly |
| KPI-003 | Wallet Activation Rate | Wallets created / registrations | >60% | Weekly |
| KPI-004 | KYC Conversion Rate | Tier 2 users / registrations | >50% | Weekly |
| KPI-005 | Avg Transaction Value | Total volume / txn count | 25,000 SYP | Daily |
| KPI-006 | Transaction Success Rate | Successful / total txns | >98% | Daily |
| KPI-007 | Agent Cash-Out Success | Successful cash-outs / total | >95% | Daily |
| KPI-008 | Remittance Success Rate | Completed / initiated | >97% | Daily |
| KPI-009 | Settlement SLA | Batches settled on time | >99% | Daily |
| KPI-010 | Agent Liquidity Coverage | Agents with sufficient float | >85% | Daily |
| KPI-011 | Fraud Loss % | Fraud losses / total volume | <0.1% | Daily |
| KPI-012 | False Positive Rate | False fraud alerts / total | <3% | Weekly |
| KPI-013 | Chargeback Rate | Chargebacks / total txns | <0.5% | Monthly |
| KPI-014 | API P99 Latency | P99 response time | <200ms | Daily |
| KPI-015 | SMS Delivery Rate | Delivered / sent | >99% | Daily |
| KPI-016 | First Response Time (Support) | Avg time to first reply | <4 hours | Daily |
| KPI-017 | KYC Review SLA | Reviewed within 48h | >95% | Daily |
| KPI-018 | AML Queue Age | Avg time in AML queue | <2 hours | Daily |

---

## 24. Operations & Observability

### Observability Stack (3 Pillars)

| Pillar | Tool | Configuration |
|--------|------|--------------|
| **Logging** | Elasticsearch + Filebeat + Kibana | JSON structured, required fields, hot/warm/cold storage |
| **Metrics** | Prometheus + Grafana | 15s scrape, 30d retention |
| **Tracing** | OpenTelemetry | End-to-end transaction traces |

### Log Levels & Required Fields

| Level | Use Case |
|-------|----------|
| DEBUG | Detailed debugging (not in prod) |
| INFO | Successful operations, state transitions |
| WARNING | Degraded states, retry attempts, near-limits |
| ERROR | Failed operations, exceptions, timeouts |
| CRITICAL | Data integrity issues, security breaches, system down |

**Required fields:** timestamp, level, service_name, correlation_id, user_id, tenant_id, action, result, duration_ms

### Alerting Rules

| Rule | Condition | Channel |
|------|-----------|---------|
| ERROR rate >5% | Rolling 5min | PagerDuty + SMS |
| CRITICAL any | Instant | PagerDuty + SMS + phone |
| Laravel Exception | >10/min | Slack + email |
| Queue depth >1000 | Per queue | PagerDuty |
| API P99 >500ms | Rolling 5min | Slack |
| Settlement failed | Any | PagerDuty + phone |

### Command Center Dashboard

The 24/7 ops team in Damascus monitors:
- **Real-time metrics:** Transaction volume, success rate, active users
- **Transaction feed:** Live stream of all transactions with filters
- **System health:** All service status, queue depths, DB replication lag
- **SLA tracking:** Current vs. targets for all KPIs
- **Agent liquidity:** Map of agent float levels by governorate

### Incident Runbooks (in `.opencode/operations/runbooks/`)

| Runbook | Scenario |
|---------|----------|
| `01-agent-cash.md` | Agent cash shortage, system-wide cash-out failure |
| `02-fx-feed.md` | CBS rate feed delayed, provider failover procedure |
| `03-settlement-failure.md` | Batch settlement not processed, manual settlement steps |
| `04-ledger-incident.md` | Out-of-balance detection, investigative procedure |
| `05-aml-backlog.md` | AML queue exceeds SLA, manual review overflow |

---

## 25. i18n Translations

### Language Coverage

| Language | Code | Modules Covered | Status |
|----------|------|----------------|--------|
| English | en | All 31 modules | ✅ Complete |
| Arabic | ar | All 31 modules | ✅ Complete |
| Kurdish (Kurmanji) | ku | Financing, Education, Humanitarian, OpenFinance, Escrow, Marketplace, Takaful, Investments | ✅ Complete |
| Armenian | hy | Financing, Education, Humanitarian, OpenFinance, Escrow, Marketplace, Takaful, Investments | ✅ Complete |

Each module has its translations in `app/Modules/{Module}/Resources/lang/{code}/messages.php` with the same key structure as English.

---

## 26. Quick Start

### Prerequisites

| Tool | Version | Purpose |
|------|---------|---------|
| PHP | 8.5+ | Backend runtime |
| Composer | 2.x | PHP dependency manager |
| MySQL | 8.0+ | Production database |
| Redis | 7+ | Cache/queue/session |
| Flutter | 3.41+ | Mobile app framework |
| Node.js | 18+ | Admin panel |

### Backend Setup

```bash
cd backend
cp .env.example .env
# Edit .env with your database credentials
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve --port=8000
```

Health check: `curl http://127.0.0.1:8000/api/v1/health`

### Run All Backend Tests

```bash
cd backend
php vendor/bin/phpunit
```

Run specific module tests:

```bash
php vendor/bin/phpunit app/Modules/Wallet/Tests
php vendor/bin/phpunit app/Modules/Marketplace/Tests
php vendor/bin/phpunit app/Modules/Admin/Tests
```

### Mobile App Setup

```bash
cd frontend/mobile
flutter pub get
flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000
```

### Admin Panel Setup

```bash
cd frontend/admin
npm install
npm run dev
```

### Docker Deployment

```bash
cd backend
docker-compose up -d
```

### Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `APP_ENV` | Application environment | `local` |
| `APP_KEY` | Laravel app key | (auto-generated) |
| `APP_URL` | Application URL | `http://localhost` |
| `DB_CONNECTION` | Database driver | `mysql` |
| `DB_DATABASE` | Database name | `beza` |
| `JWT_SECRET` | JWT signing secret | (auto-generated) |
| `JWT_TTL` | Access token TTL (min) | `15` |
| `JWT_REFRESH_TTL` | Refresh token TTL (min) | `10080` |
| `OTP_EXPIRY` | OTP expiry (min) | `5` |
| `SMS_DRIVER` | SMS provider | `log` |

### OTP in Development

```bash
cd backend
php artisan get-otp 963xxxxxxxxx
```

OTP is stored in cache key `otp_plain_{otp_id}` for 5 minutes.

---

## 27. Documentation Index

### Architecture Docs (9 files)

| File | Content |
|------|---------|
| `architecture/00-system-context.md` | System context diagram — 12 components, external systems, actors, tech stack |
| `architecture/01-system-overview.md` | V1 architecture — 4 layers, 10 rules, core decisions, 8-step transaction flow |
| `architecture/02-events-catalog.md` | 36 events across 10 domains with schemas, producers, consumers |
| `architecture/02-event-platform.md` | Event platform — CloudEvents 1.0, RabbitMQ, dead letter strategy |
| `architecture/03-state-machines.md` | 10 entity state machines — transition tables, guards, timeouts, roles |
| `architecture/04-sequence-diagrams.md` | 12 sequence diagrams — happy path + failure + resilience for all flows |
| `architecture/05-event-versioning.md` | Versioning strategy, compatibility rules, migration windows |
| `architecture/06-service-boundaries.md` | Monolith boundaries, 3 extraction criteria, Syria rationale |
| `architecture/07-non-functional-requirements.md` | Performance, availability, security, scalability targets |

### ADR Docs (7 files)

| File | Decision |
|------|----------|
| `adr/ADR-001-mysql-8-over-postgresql.md` | MySQL 8.0 over PostgreSQL |
| `adr/ADR-002-modular-monolith-not-microservices.md` | Modular monolith, not microservices |
| `adr/ADR-003-rate-locking-15-second-window.md` | 15s FX rate locking window |
| `adr/ADR-004-wallet-ledger-separation.md` | Wallet-as-cached-view-of-Ledger |
| `adr/ADR-005-agent-risk-graduated-float-limits.md` | Graduated float limits by agent maturity |
| `adr/ADR-006-ledger-event-sourcing-strategy.md` | Transactional ledger (not event sourced) |
| `adr/ADR-007-marketplace-deferred.md` | Marketplace deferred to V4+ |

### Domain Docs (4 files)

| File | Content |
|------|---------|
| `domain/01-domain-model.md` | 11 bounded contexts, 20+ aggregates, 30+ invariants, entity ownership |
| `domain/02-data-dictionary.md` | Canonical data dictionary — 24 identifiers, 30+ enums, naming conventions |
| `domain/02-customer-lifecycle.md` | Customer lifecycle state machine, KYC tiers, suspension/closure |
| `domain/03-wallet-lifecycle.md` | Wallet lifecycle state machine, transition guards, timeout rules |

### Financial Core Docs (6 files)

| File | Content |
|------|---------|
| `financial-core/01-cfe-v2.md` | CFE V2 — 10 sub-engines, transaction lifecycle, hold engine |
| `financial-core/02-reconciliation-platform.md` | Reconciliation engine — capture, matching, exception queue |
| `financial-core/03-treasury-management.md` | Treasury operations — forecasting, liquidity, agent float |
| `financial-core/04-accounting-book.md` | IFRS-compliant accounting — all operations with double-entry |
| `financial-core/05-product-catalog.md` | 8 financial products with codes, fees, limits, KYC |
| `financial-core/06-pricing-engine.md` | Rule-based pricing — no hardcoded fees, Redis-cached rules |

### Execution Docs (8 files)

| File | Content |
|------|---------|
| `execution/01-build-order.md` | 6-phase build plan, 24 weeks, Ledger-before-Wallet |
| `execution/02-dependency-map.md` | 13-module dependency matrix |
| `execution/03-user-journeys-index.md` | 15 user journeys, 11 actor types, priority matrix |
| `execution/04-screen-inventory.md` | 100+ Flutter screens, 50+ admin, USSD, POS |
| `execution/05-api-matrix.md` | 150+ internal/external/client API relationships |
| `execution/06-ledger-impact-matrix.md` | 42 ledger operations, 40+ GL accounts, fee schedule |
| `execution/07-state-transition-matrix.md` | 136 transitions across 12 entities |
| `execution/08-production-readiness.md` | 200+ launch checklist items across 8 domains |

### User Journey Docs (9 files)

| File | Journey |
|------|---------|
| `journeys/01-first-time-user.md` | First-time user onboarding |
| `journeys/02-kyc-journey.md` | KYC upgrade |
| `journeys/03-first-transfer.md` | First P2P transfer |
| `journeys/04-remittance-receive.md` | Diaspora remittance |
| `journeys/05-agent-cashout.md` | Agent cash-out |
| `journeys/06-payroll-employee.md` | Employee payroll |
| `journeys/07-merchant-payment.md` | Merchant QR payment |
| `journeys/08-dispute-resolution.md` | Dispute resolution |
| `journeys/09-fraud-review.md` | Fraud case review |

### API Docs (2 files)

| File | Content |
|------|---------|
| `api/01-api-standards.md` | API standards — auth, pagination, errors, rate limiting, versioning, retry |
| `api/02-error-catalog.md` | 126 error codes across 14 domains — Arabic/English messages |

### Engineering Docs (13 files)

| File | Content |
|------|---------|
| `engineering/01-coding-standards.md` | General coding standards |
| `engineering/02-laravel-conventions.md` | Laravel module conventions |
| `engineering/03-react-conventions.md` | React/TypeScript conventions |
| `engineering/04-flutter-conventions.md` | Flutter/Dart conventions |
| `engineering/05-testing-standards.md` | Testing requirements |
| `engineering/06-git-strategy.md` | Git workflow |
| `engineering/07-branching-model.md` | Branching model |
| `engineering/08-code-review-checklist.md` | Code review checklist |
| `engineering/09-ai-coding-rules.md` | AI-assisted coding rules |
| `engineering/10-architecture-guardrails.md` | G-001 to G-010 non-negotiable rules |
| `engineering/10-definition-of-done.md` | Definition of Done |
| `engineering/12-financial-transaction-contract.md` | Financial transaction contract rules |
| `engineering/13-money-handling-standard.md` | Money handling — BigInt, no floats |

### Operations Docs (3 files)

| File | Content |
|------|---------|
| `operations/01-command-center.md` | Ops dashboard, alerting, escalation, runbooks |
| `operations/01-observability.md` | Logging (ELK), metrics (Prometheus), tracing (OpenTelemetry) |
| `operations/02-kpi-catalog.md` | 18 KPIs — DAU, success rates, latency, fraud loss, review SLAs |

### Design Doc (1 file)

| File | Content |
|------|---------|
| `design/01-design-language-2026.md` | Brand DNA, colors, typography, components, motion, screen template |

### Security Doc (1 file)

| File | Content |
|------|---------|
| `security/01-zero-trust.md` | Zero Trust — 5 principles, MFA strategy, JWT architecture, encryption |

### Product Doc (1 file)

| File | Content |
|------|---------|
| `product/01-vision-2026.md` | Platform vision, 8 pillars, success metrics |

### AI Platform Doc (1 file)

| File | Content |
|------|---------|
| `ai-platform/01-ai-architecture.md` | 5 AI services, 100+ fraud detection signals |

---

## License & Contact

- **Repository**: [https://github.com/es3dlll/beza-platform](https://github.com/es3dlll/beza-platform)
- **Issues**: [GitHub Issues](https://github.com/es3dlll/beza-platform/issues)
- **Architecture Docs**: `.opencode/docs/` (120+ Markdown files)
- **Build Plans**: `.opencode/plans/` (6 plans, V0-V5)

---

*Beza — المالية للجميع. Financial inclusion for everyone.*

*Built for Syria. Built for trust. Built for the unbanked.*
