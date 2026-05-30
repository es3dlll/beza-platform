# Beza Platform — Syria's Financial Operating System

[![CI](https://github.com/es3dlll/beza-platform/actions/workflows/ci.yml/badge.svg)](https://github.com/es3dlll/beza-platform/actions/workflows/ci.yml)
![PHP](https://img.shields.io/badge/PHP-8.5-777BB4?logo=php)
![Laravel](https://img.shields.io/badge/Laravel-11-FF2D20?logo=laravel)
![Flutter](https://img.shields.io/badge/Flutter-3.41-02569B?logo=flutter)
![Tests](https://img.shields.io/badge/Tests-361%20passing-brightgreen)

**Beza (بزة)** is a full-stack financial operating system purpose-built for Syria — a **Laravel modular monolith** backend with a **Flutter mobile app** and **React admin panel**, designed to serve 22M Syrians and a 6M diaspora sending $2-3B/year home, while bringing financial inclusion to a <25% banked population in a 90%+ cash economy.

---

## Table of Contents

- [Vision](#vision)
- [Project Structure](#project-structure)
- [Backend Architecture](#backend-architecture)
- [Module Catalog](#module-catalog)
- [Mobile App](#mobile-app)
- [Admin Panel](#admin-panel)
- [API Reference](#api-reference)
- [Documentation](#documentation)
- [Plans & Roadmap](#plans--roadmap)
- [Quick Start](#quick-start)
- [Testing](#testing)
- [Infrastructure](#infrastructure)
- [GitHub Workflow](#github-workflow)
- [License](#license)

---

## Vision

Beza is Syria's first Financial Operating System — a unified digital infrastructure replacing cash with programmable money, connecting citizens, diaspora, merchants, agents, government, NGOs, and enterprises through a single platform.

### Platform Pillars

| # | Pillar | Description |
|---|--------|-------------|
| 1 | **Wallet Infrastructure** | Multi-currency (SYP/USD) programmable wallets with tiered limits |
| 2 | **FX Infrastructure** | Automated rate engine with CBS rate feed, 15s quote locking |
| 3 | **Agent Banking Network** | National cash-in/cash-out network with float management |
| 4 | **Merchant Acquiring** | QR codes, payment links, POS for all business sizes |
| 5 | **Payroll Infrastructure** | Digital salary distribution for enterprises |
| 6 | **Government Collections** | Tax, fees, utilities, social payments |
| 7 | **Remittance Corridors** | Inbound diaspora money transfer (LB, AE, JO, DE) |
| 8 | **Savings & Financing** | Goal-based savings, Sharia-compliant lending (Murabaha, Qard Hasan, BNPL) |
| 9 | **Cards Infrastructure** | Virtual + physical prepaid cards |
| 10 | **Open Finance Layer** | API ecosystem for third-party innovation |
| 11 | **Marketplace** | Digital & physical goods marketplace with escrow |
| 12 | **Takaful** | Islamic insurance (health, device, travel) |
| 13 | **Investments** | Sharia-compliant fund investments |

### Market Context (Syria 2026)

| Metric | Value |
|--------|-------|
| Population | ~22M (incl. 6M diaspora) |
| Cash economy | 85%+ of transactions |
| Banked population | <25% |
| Smartphone penetration | ~60% (growing) |
| Diaspora remittances | $2-3B/year (mostly informal) |
| Unbanked SMEs | 95%+ no credit access |
| Youth (under 25) | 50%+ of population |

---

## Project Structure

```
beza-platform/
├── backend/                          ← Laravel 11 API
│   ├── app/
│   │   ├── Console/Commands/         ← Artisan commands
│   │   ├── Domain/ValueObjects/      ← Money, Currency, Rate, Percentage
│   │   ├── Exceptions/              ← Global exception handlers
│   │   ├── Http/Controllers/        ← API controllers
│   │   ├── Listeners/               ← Event listeners
│   │   └── Modules/                 ← **31 self-contained modules**
│   ├── bootstrap/                    ← Laravel bootstrap
│   ├── config/                       ← Configuration files
│   ├── database/
│   │   ├── migrations/              ← 100+ migration files
│   │   └── seeders/                 ← Demo data seeders
│   ├── docker/                       ← Docker PHP/Nginx config
│   ├── docs/                         ← OpenAPI spec
│   ├── postman/                      ← Postman collection + env
│   ├── public/                       ← Web server root
│   ├── resources/views/              ← Blade templates
│   ├── routes/                       ← API, web, console routes
│   ├── storage/                      ← Logs, cache, sessions
│   ├── tests/                        ← Backend tests (237+)
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
└── .opencode/                        ← AI-augmented documentation & plans
    ├── docs/                          ← Roadmaps, ADRs, architecture, engineering, security
    ├── plans/                         ← Build plans (V0 through V5)
    ├── features/                      ← Feature specs (Wallet, Settlement)
    ├── tasks/                         ← Task breakdowns (backend, Flutter, QA, DevOps, AI)
    └── operations/runbooks/           ← Incident runbooks
```

---

## Backend Architecture

### Technology Stack

| Component | Technology | Version |
|-----------|-----------|---------|
| Framework | Laravel | 11.54.0 |
| Language | PHP | 8.5.6 |
| Database | MySQL (prod) / SQLite (dev) | 8.0+ |
| Cache/Queue | Redis | 7+ |
| Event Bus | RabbitMQ | 3.x |
| Web Server | Nginx | latest |

### Modular Monolith — 31 Modules

The entire application is a single Laravel process with self-contained modules. Each module follows a strict structure:

```
Module/
├── Controllers/       ← HTTP endpoint handlers
├── Services/          ← Business logic (final classes)
├── Models/            ← Eloquent models (ULID PKs)
├── Enums/             ← PHP 8.1+ backed enums
├── Events/            ← Dispatchable events
├── Exceptions/        ← Domain-specific exceptions
├── Database/
│   ├── Migrations/    ← Table definitions
│   └── Factories/     ← Test factories
├── Routes/api.php     ← Module routes (prefix v1/{module})
├── Providers/         ← Service providers
├── Tests/             ← Feature tests
└── Resources/lang/    ← Translations (en, ar, ku, hy)
```

### Architecture Rules (Non-Negotiable)

1. **Modular Monolith ONLY** — no microservices, single Laravel process
2. **CFE owns ALL financial state** — no module writes balances directly
3. **Ledger = Single Source of Truth** — append-only journal (WORM)
4. **ULID for ALL primary keys** — no auto-increment IDs
5. **Money = bigint minor units** — no float, `App\Domain\ValueObjects\Money`
6. **Cross-module via Events only** — no direct service calls across modules
7. **Zero Trust** — RBAC + ABAC + JWT rotation + device binding
8. **Arabic-first, RTL-native** — Syria-specific (SYP, CBS, Syriatel, MTN)

### Financial Transaction Flow

```
User Action → Mobile App / USSD / Web
  → Nginx (TLS, rate limit)
    → Module Controller (validate, authorize)
      → Domain Service (business rules, compute)
        → CFE (hold → post → ledger → fee → FX)
          → Event Bus (emit TransactionEvent)
            → Listeners (notification, analytics, compliance)
              → Response (confirmation, receipt)
```

---

## Module Catalog

### Tier A — V1 (Core Financial Infrastructure)

| Module | Description | Files | Tests | Status |
|--------|-------------|-------|-------|--------|
| **Identity** | User registration, phone/OTP, KYC, device binding, PIN | 35+ | 71 | ✅ Complete |
| **IAM** | RBAC/ABAC: Super Admin, Compliance, Finance, Agent Manager, Support | 25+ | 4 | ✅ Complete |
| **Ledger** | Double-entry accounting, chart of accounts (13 seed accounts), journal, trial balance | 30+ | 5 | ✅ Complete |
| **CoreFinancialEngine** | Posting, Fee, Hold, Reversal engines — state machine (initiated→held→completed/failed/reversed) | 15+ | 5 | ✅ Complete |
| **Wallet** | Multi-currency (SYP/USD), limits T1/T2/T3, P2P transfer, balance cache | 25+ | 4 | ✅ Complete |
| **Agent** | Registration, KYC, cash-in/out, commission, geo-location, limits | 20+ | 6 | ✅ Complete |
| **Settlement** | Merchant D+1, agent settlement, batch processing, reconciliation | 15+ | 3 | ✅ Complete |
| **FX** | CBS rate feed, quote/lock 15s, SYP↔USD via suspense, 1.5% fee | 15+ | 3 | ✅ Complete |
| **Remittance** | Corridors LB/AE/JO/DE, inquire/receive, compliance hold >$1K, sanctions screening | 25+ | 3 | ✅ Complete |
| **Bills** | Syriatel/MTN/PEED/Water/Landline adapters, inquire→pay→receipt | 20+ | 4 | ✅ Complete |
| **Merchant** | Registration, static QR, D+1 settlement, refund | 20+ | 4 | ✅ Complete |
| **Fraud** | 20+ rules, scoring 0-1000, blacklist, device fingerprinting, case management | 25+ | 3 | ✅ Complete |

### Tier B — V1.5 (Growth)

| Module | Description | Files | Tests | Status |
|--------|-------------|-------|-------|--------|
| **Payroll** | Bulk disbursement, employer management, CSV import, salary certificates | 25+ | 3 | ✅ Complete |
| **Savings** | Goal-based savings, profit distribution, auto-sweep, withdrawal rules | 20+ | 3 | ✅ Complete |

### Tier C — V2 (Expansion)

| Module | Description | Files | Tests | Status |
|--------|-------------|-------|-------|--------|
| **Cards** | Virtual/physical card management, card schemes | 15+ | 3 | ✅ Complete |
| **Loyalty** | Points, tiers, cashback, rewards engine | 25+ | 3 | ✅ Complete |
| **GovCollections** | Inquire→pay pattern, CBS/BSO/tax/utility providers | 20+ | 3 | ✅ Complete |

### Tier D — V3 (Advanced Financial Services)

| Module | Description | Files | Tests | Status |
|--------|-------------|-------|-------|--------|
| **Financing** | Murabaha, Qard Hasan, Micro-Enterprise, BNPL — apply→approve→disburse→repay, credit scoring | 30+ | 4 | ✅ Complete |
| **Education** | Institution→student→fee hierarchy, partial payment, CSV bulk import, receipts | 20+ | 6 | ✅ Complete |
| **Humanitarian** | Organization→program→disbursement, batch processing, OFAC screening, donor reports | 25+ | 7 | ✅ Complete |
| **OpenFinance** | OAuth2 app/consent/token, Payment Initiation API, Account Info API, Webhooks (HMAC), sandbox | 25+ | 6 | ✅ Complete |

### Tier E — V4 (Super App Ecosystem)

| Module | Description | Files | Tests | Status |
|--------|-------------|-------|-------|--------|
| **Escrow** | Agreement, milestones, CFE hold/release/refund, dispute resolution, fee 1% capped 50K SYP | 15+ | 6 | ✅ Complete |
| **Marketplace M1** | Digital products catalog, order state machine, vendor invite-only onboarding, commission | 21+ | 6 | ✅ Complete |
| **Marketplace M2** | Gift cards (purchase, SMS/WhatsApp, QR redeem), promo codes, loyalty points | 11+ | 8 | ✅ Complete |
| **Marketplace M3** | Physical products, shipping zones (14 governorates), COD agent collection, tracking | 8+ | 4 | ✅ Complete |
| **Marketplace M4** | Open marketplace API (catalog, order, webhook), rate limiting 60/min | 1+ | 4 | ✅ Complete |
| **Takaful** | Islamic insurance — products, policies, claims, tabarru' pool, loss ratio dashboard | 15+ | 6 | ✅ Complete |
| **Investments** | Sharia-compliant funds, subscribe/redeem units, daily NAV, Zakat calculator, AUM dashboard | 16+ | 6 | ✅ Complete |
| **Admin V3+V4** | 8 admin controllers/services — Financing, Education, Humanitarian, OpenFinance, Marketplace, Escrow, Takaful, Investments | 16+ | 8 | ✅ Complete |

### Cross-Cutting / Infrastructure

| Module | Description | Status |
|--------|-------------|--------|
| **USSD** | *123# menu engine for feature phones | ✅ Complete |
| **Notification** | In-app, push (FCM), SMS, email — multi-channel | ✅ Complete |
| **Auth** | Firebase Cloud Messaging, token management | ✅ Complete |
| **IAM Middleware** | Permission middleware, role middleware | ✅ Complete |

### Test Coverage Summary

| Scope | Tests | Assertions |
|-------|-------|------------|
| Backend Modules | 331 | 800+ |
| Core (Auth, Health, etc.) | 30 | 42+ |
| **Total** | **361** | **842** |

All tests pass on SQLite in-memory database with 0 failures.

---

## Mobile App

A **Flutter 3.41** application with 68+ Dart files, 62 passing tests, 0 analyzer issues.

### Architecture

- **State Management**: Riverpod (`StateNotifierProvider`)
- **Routing**: GoRouter with deep links
- **Networking**: Dio with interceptors
- **Security**: `flutter_secure_storage` for JWT
- **Localization**: ARB files (Arabic + English)
- **Design**: Material 3, Syria-inspired palette (green `#1B5E20`, gold `#D4A843`)

### Screens (24+)

Splash → Welcome → Phone Entry → OTP → Create PIN → Confirm PIN → Home (Shell with bottom nav) → Wallet, Bills, Cards, Agent, Financing, Education, Humanitarian, Loyalty, Merchant, FX, Remittance, Payroll, Savings, Transactions, Notifications, Profile, Settings

---

## Admin Panel

A **React 18** single-page application for operations:

- **KYC Review**: Identity verification workflow
- **Agent Management**: Onboarding, float monitoring, commission config
- **Fraud Cases**: Review, decision, blacklist management
- **FX Rates**: Rate feed monitoring, manual override
- **Role & Permission Management**: RBAC configuration
- **User Management**: Search, detail, suspend
- **Transaction Search**: Full-text search across all transactions

---

## API Reference

All endpoints under `api/v1/{module}`. Full OpenAPI spec at `backend/docs/openapi.yaml`.

### Authentication

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/auth/register` | Submit phone number |
| POST | `/auth/verify-otp` | Verify OTP code |
| POST | `/auth/create-pin` | Create PIN |
| POST | `/auth/login` | Login (returns JWT) |
| POST | `/auth/logout` | Invalidate session |
| POST | `/auth/refresh` | Refresh token |

### Core

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/health` | Health check |
| GET | `/wallet/balance` | Get wallet balance |
| GET | `/transactions` | List transactions |
| GET | `/notifications` | List notifications |

### V1 Modules (200+ endpoints)

Each module exposes CRUD + action endpoints. Key modules:

| Module | Example Endpoints |
|--------|------------------|
| Wallet | `POST /transfer`, `POST /deposit`, `POST /withdraw` |
| Agent | `POST /cash-in`, `POST /cash-out`, `GET /nearby` |
| FX | `POST /quote`, `POST /convert` |
| Bills | `POST /inquire`, `POST /pay` |
| Merchant | `POST /pay`, `POST /refund` |
| Remittance | `POST /create`, `GET /track` |
| Financing | `POST /apply`, `POST /repay`, `POST /bnpl-checkout` |
| Education | `POST /inquire-fee`, `POST /pay-fee` |
| Humanitarian | `POST /disburse`, `POST /voucher` |
| OpenFinance | `POST /oauth/token`, `GET /accounts`, `POST /payments` |
| Marketplace | `GET /products`, `POST /orders`, `POST /gift-cards/redeem` |
| Escrow | `POST /`, `POST /{id}/release`, `POST /{id}/dispute` |
| Takaful | `POST /subscribe`, `POST /claims` |
| Investments | `POST /subscribe`, `POST /redeem`, `GET /nav-history` |

### Admin Endpoints (60+)

| Module | Prefix |
|--------|--------|
| Financing | `v1/admin/financing/*` |
| Education | `v1/admin/education/*` |
| Humanitarian | `v1/admin/humanitarian/*` |
| OpenFinance | `v1/admin/open-finance/*` |
| Marketplace | `v1/admin/marketplace/*` |
| Escrow | `v1/admin/escrow/*` |
| Takaful | `v1/admin/takaful/*` |
| Investments | `v1/admin/investments/*` |

---

## Documentation

The project includes **120+ Markdown files** in `.opencode/docs/` covering every aspect.

### Architecture

| File | Description |
|------|-------------|
| `00-system-context.md` | System context diagram |
| `01-system-overview.md` | High-level architecture, layers, decisions |
| `02-event-platform.md` | Event platform architecture |
| `03-state-machines.md` | All state machines |
| `04-sequence-diagrams.md` | Sequence diagrams for key flows |
| `05-event-versioning.md` | Event versioning strategy |
| `06-service-boundaries.md` | Module boundary contracts |
| `07-non-functional-requirements.md` | Performance, security, compliance |

### Financial Core

| File | Description |
|------|-------------|
| `01-cfe-v2.md` | Core Financial Engine v2 spec |
| `02-reconciliation-platform.md` | Reconciliation platform |
| `03-treasury-management.md` | Treasury management |
| `04-accounting-book.md` | Accounting book structure |
| `05-product-catalog.md` | Product catalog definitions |
| `06-pricing-engine.md` | Fee and pricing engine |

### Engineering Standards

| File | Description |
|------|-------------|
| `01-coding-standards.md` | PHP, Dart, TypeScript coding standards |
| `02-laravel-conventions.md` | Laravel module conventions |
| `04-flutter-conventions.md` | Flutter code conventions |
| `05-testing-standards.md` | Testing requirements and patterns |
| `07-branching-model.md` | Git branching model |
| `10-architecture-guardrails.md` | 10 non-negotiable architecture rules |

### Security

| File | Description |
|------|-------------|
| `01-zero-trust.md` | Zero Trust architecture |
| `01-authentication.md` | Auth patterns |
| `02-authorization.md` | RBAC/ABAC |
| `03-encryption.md` | Encryption standards |

### Domain

| File | Description |
|------|-------------|
| `01-domain-model.md` | Full domain model |
| `02-data-dictionary.md` | Data dictionary |
| `03-wallet-lifecycle.md` | Wallet state machine |
| `02-customer-lifecycle.md` | Customer journey states |

### API

| File | Description |
|------|-------------|
| `01-api-standards.md` | API design standards |
| `02-error-catalog.md` | 180+ error codes across 24+ domains |

### ADR (Architecture Decision Records)

| File | Decision |
|------|----------|
| `ADR-001-mysql-8-over-postgresql.md` | MySQL 8 over PostgreSQL |
| `ADR-002-modular-monolith-not-microservices.md` | Modular monolith, not microservices |
| `ADR-003-rate-locking-15-second-window.md` | FX rate locking 15s window |
| `ADR-004-wallet-ledger-separation.md` | Wallet-Ledger separation |
| `ADR-005-agent-risk-graduated-float-limits.md` | Agent risk-graduated float limits |
| `ADR-006-ledger-event-sourcing-strategy.md` | Ledger event sourcing |
| `ADR-007-marketplace-deferred.md` | Marketplace deferred to V4 |

### Roadmaps

| File | Scope |
|------|-------|
| `01-product-prioritization.md` | Full tier breakdown (A-E) with regulatory analysis |
| `02-v1-scope.md` | V1: Wallet, Agent, FX, Fraud, Remittance, Bills, Merchant |
| `03-v2-scope.md` | V2: Cards, Loyalty, GovCollections |
| `04-v3-scope.md` | V3: Financing, Education, Humanitarian, OpenFinance |
| `05-v4-scope.md` | V4: Marketplace, Escrow, Takaful, Investments |

### User Journeys

| File | Journey |
|------|---------|
| `01-first-time-user.md` | First-time user onboarding |
| `02-kyc-journey.md` | KYC verification |
| `03-first-transfer.md` | First P2P transfer |
| `04-remittance-receive.md` | Receive remittance |
| `05-agent-cashout.md` | Agent cash-out |
| `06-payroll-employee.md` | Payroll for employees |
| `07-merchant-payment.md` | Merchant QR payment |
| `08-dispute-resolution.md` | Dispute resolution |
| `09-fraud-review.md` | Fraud case review |

### i18n Translations

| Language | Modules Covered |
|----------|----------------|
| **en** (English) | All 31 modules |
| **ar** (Arabic) | All 31 modules |
| **ku** (Kurdish Kurmanji) | Financing, Education, Humanitarian, OpenFinance, Escrow, Marketplace, Takaful, Investments |
| **hy** (Armenian) | Financing, Education, Humanitarian, OpenFinance, Escrow, Marketplace, Takaful, Investments |

---

## Plans & Roadmap

### Build Plans (in `.opencode/plans/`)

| Plan | Version | Codename | Focus |
|------|---------|----------|-------|
| `خطة_بناء_beza_v0_الأسس.plan.md` | V0 | الأسس (Foundations) | Identity, IAM, Ledger, CFE |
| `خطة_بناء_beza_v1_152cd23a.plan.md` | V1 | الإطلاق (Launch) | Wallet, Agent, FX, Fraud, Remittance, Bills, Merchant |
| `خطة_بناء_beza_v2_69780e8a.plan.md` | V2 | التوسع (Expansion) | Cards, Loyalty, GovCollections |
| `خطة_بناء_beza_v3_7f921b42.plan.md` | V3 | التمويل (Financing) | Financing, Education, Humanitarian, OpenFinance |
| `خطة_بناء_beza_v4_7cc85ffd.plan.md` | V4 | السوق (Marketplace) | Marketplace, Escrow, Takaful, Investments |
| `خطة_بناء_beza_v5_e4ef8424.plan.md` | V5 | الإقليم (Regional) | Regional expansion, social commerce |

### Product Tiers

```
TIER A  (V1,  M1-6):   Wallet, Agent, FX, Fraud, Remittance, Bills, Merchant QR
TIER B  (V1.5, M7-10): Payroll, Savings, Settlement
TIER C  (V2,   M11-16): Cards, Loyalty, Government Collections
TIER D  (V3,   M17-24): Financing, Education, Humanitarian, Open Finance
TIER E  (V4,   M25-36): Marketplace, Escrow, Takaful, Investments
TIER F  (V5,   M37+):   Regional Expansion, Social Commerce
```

### Execution Docs

| File | Description |
|------|-------------|
| `01-build-order.md` | Module build order with dependencies |
| `02-dependency-map.md` | Full dependency graph |
| `03-user-journeys-index.md` | Index of all user journeys |
| `04-screen-inventory.md` | All mobile screens |
| `05-api-matrix.md` | API endpoint matrix |
| `06-ledger-impact-matrix.md` | Ledger account impact per module |
| `07-state-transition-matrix.md` | All state machines in one place |
| `08-production-readiness.md` | Production readiness checklist |

---

## Quick Start

### Prerequisites

| Tool | Version | Purpose |
|------|---------|---------|
| PHP | 8.5+ | Backend runtime |
| Composer | 2.x | PHP dependency manager |
| MySQL | 8.0+ | Production database |
| Redis | 7+ | Cache/queue/session |
| Flutter | 3.41+ | Mobile app |
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

### Mobile App Setup

```bash
cd frontend/mobile
flutter pub get
flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000
```

| Device | API URL |
|--------|---------|
| Android emulator | `http://10.0.2.2:8000` |
| iOS simulator | `http://localhost:8000` |
| Real device | `http://192.168.x.x:8000` |

### Admin Panel Setup

```bash
cd frontend/admin
npm install
npm run dev
```

### OTP in Development

```bash
cd backend
php artisan get-otp 963xxxxxxxxx
```

OTP is stored in cache key `otp_plain_{otp_id}` for 5 minutes.

### Docker Deployment

```bash
cd backend
docker-compose up -d
```

---

## Testing

### Backend (361 tests, 842 assertions)

```bash
cd backend
php artisan test
# or
php vendor/bin/phpunit
```

Run specific module tests:

```bash
php vendor/bin/phpunit app/Modules/Wallet/Tests
php vendor/bin/phpunit app/Modules/Marketplace/Tests
php vendor/bin/phpunit app/Modules/Admin/Tests
```

### Mobile (62 tests)

```bash
cd frontend/mobile
flutter test
```

### Admin (setup)

```bash
cd frontend/admin
npm test
```

---

## Infrastructure

### Docker Services

| Service | Image | Purpose |
|---------|-------|---------|
| `app` | `php:8.5-fpm` | PHP-FPM application server |
| `nginx` | `nginx:alpine` | Web server, reverse proxy |
| `mysql` | `mysql:8` | Primary database |
| `redis` | `redis:7-alpine` | Cache, queue, session store |
| `rabbitmq` | `rabbitmq:3-management` | Event bus |

### CI Pipeline (GitHub Actions)

- **Trigger**: Push to `main`, PR to `main`
- **PHP**: 8.5, extensions: mysql, redis, mbstring, xml, bcmath, json
- **Services**: MySQL 8, Redis 7
- **Steps**: Composer → PHP lint → PHPUnit (SQLite) → PHPUnit (MySQL)

---

## GitHub Workflow

### Branching Model

```
main          ← Production-ready
  └── develop ← Integration branch
       ├── feature/module-name  ← Feature branches
       └── fix/issue-description ← Bug fixes
```

### Commit Convention

```
feat(module): description     # New feature
fix(module): description      # Bug fix
docs(module): description     # Documentation
refactor(module): description # Code refactoring
test(module): description     # Tests
chore: description            # Maintenance
```

---

## License & Contact

- **Repository**: [https://github.com/es3dlll/beza-platform](https://github.com/es3dlll/beza-platform)
- **Issues**: [GitHub Issues](https://github.com/es3dlll/beza-platform/issues)
- **Architecture Docs**: `.opencode/docs/`
- **Build Plans**: `.opencode/plans/`

---

*Beza — المالية للجميع. Financial inclusion for everyone.*
