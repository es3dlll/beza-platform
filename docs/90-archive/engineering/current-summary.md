## Goal

- Build Beza Financial OS for Syria — fully documented, AI-executable, production-ready codebase.

## Constraints & Preferences

- V1 = strict Laravel Modular Monolith — NO microservices, NO Kafka, NO K8s (Docker Compose)
- Build order: Identity → IAM → Ledger → CFE → Wallet → Agent → Settlement → FX → Remittance → Bills → Merchant QR → Fraud → Payroll → Savings → Cards → Loyalty → GovCollections → Financing → Education → Humanitarian → OpenFinance
- CFE owns ALL financial state; Ledger = single source of truth with append-only journal (WORM)
- Arabic-first, RTL-native, low-literacy friendly, Syria-specific (SYP, CBS, BSO, SIIB, Syriatel, MTN)
- Zero Trust: RBAC + ABAC + JWT rotation + device binding + biometrics
- Money: `App\Domain\ValueObjects\Money` (bigint minor units, no float)
- ULID for ALL primary keys
- Cross-module communication via Events only
- Documentation FROZEN (v1.7.0) — CODE FIRST throughout
- Architecture Guardrails G-001 to G-010 are NON-NEGOTIABLE

## Progress

### Done (All 24 Backend Modules — Tier A V1 Scope Complete)

- **Identity + Auth**: 142 files, 71 tests — phone/OTP/JWT/PIN/device binding
- **IAM**: Spatie roles/permissions — Super Admin, Compliance, Finance, Agent Manager, Support
- **Ledger**: Double-entry journal, chart of accounts (13 seed accounts), trial balance — 8 known test failures
- **CFE**: State machine (initiated→held→completed/failed/reversed), PostingEngine, FeeEngine, Reversal, Suspense, event emission
- **Wallet**: Multi-currency (SYP/USD), limits Tier 1/2/3, P2P transfer, balance cache — 3 integration gaps with CFE
- **Float**: Agent float Ledger account 1200, cash-in/out float management
- **Settlement**: Merchant D+1 settlement, biller batch settlement
- **Agent Network**: Registration, KYC, cash-in/out, commission, geo-location, limits
- **FX Engine**: CBS rate feed, quote/lock 15s, SYP↔USD via suspense, 1.5% fee
- **Remittance**: Corridors LB/AE/JO/DE, inquire/receive, compliance hold >$1K
- **Bills**: Syriatel/MTN/PEED/Water/Landline adapters, inquire→pay→receipt
- **Merchant QR**: Registration, static QR, D+1 settlement, refund
- **Fraud Engine**: 20+ rules, scoring 0–1000, blacklist, case management
- **Payroll**: Bulk disbursement, employer reports
- **Savings**: Goal-based savings engine
- **Cards**: Virtual/physical card management
- **Loyalty**: Points & rewards
- **GovCollections**: Inquire→pay pattern, CBS/BSO/tax/utility providers
- **Financing**: Apply→approve→disburse→repay lifecycle
- **Education**: Institution→student→fee hierarchy, partial payment
- **Humanitarian**: Org→program→disbursement with budget tracking
- **Open Finance**: OAuth2-style app/consent/token, scope validation
- **Flutter App**: 68 Dart files, 0 analyzer issues, 24 screens, 62 tests
- **Infrastructure**: `docker-compose.yml`, Dockerfiles, `.github/workflows/ci.yml`

### Key Decisions (Sprints 15-19)

- Gov Collections: inquire→pay pattern with 30-min inquiry expiry; CBS/BSO/tax/utility providers
- Financing: apply→approve→disburse→repay lifecycle; equal installment generation; interest calc (principal × rate × term/365)
- Education: institution→student→fee hierarchy; partial payment support with receipt numbers
- Humanitarian: org→program→disbursement with budget tracking; auto-decrement on disbursement
- Open Finance: OAuth2-style app/consent/token model; scope validation; 2h token TTL

### Infrastructure

- Docker Compose with PHP 8.5 FPM, Nginx, MySQL 8, Redis 7
- GitHub Actions CI: PHP 8.5, MySQL, Redis, full test suite
- `docker/php/Dockerfile`, `docker/nginx/default.conf`, `docker/php/php.ini`, `.dockerignore`

### Error Catalog

- 180+ error codes across 24 domains (6 new: GOV, EDU, HUM, OF, PAYROLL, LOYALTY)

### Pre-existing Issues (Known — Blocking V1 Release)

- **Ledger module**: 8 test failures (balance assertions, trial balance count) — blocks CFE
- **Wallet module**: 3 integration failures (deposit/withdraw/transfer — CFE dependency)
- All module-specific unit tests pass (154 tests, 274 assertions)
- **Admin React**: Not started (required for KYC review, agent management, ops dashboard)
- **USSD \*123#**: Not implemented (required for Syrian market launch)
- **CI pipeline**: No actual CI running (badge exists, workflow likely incomplete)
- **Load testing**: Not performed
- **Security audit**: Not performed

## Next Steps (from خطة_بناء_beza_v1)

1. **Task 1** — Docs alignment, CI, design tokens, OpenAPI sync
2. **Task 2** — Fix Ledger (8 tests) → Wire CFE → Fix Wallet (3 integrations)
3. **Task 3** — Auth hardening (SMPP OTP, device binding, PIN rate limit)
4. **Task 4** — Fraud engine integration middleware
5. **Task 5** — V1 backend products (Agent, FX, Remittance, Bills, Merchant)
6. **Task 6** — Flutter V1 screens complete
7. **Task 7** — Admin React V1
8. **Task 8** — USSD \*123#
9. **Task 9** — QA + Security + Load
10. **Task 10** — Production go-live

## Critical Context

- Repo: https://github.com/es3dlll/beza-platform (~723 app PHP files, ~30K lines, 24 modules)
- 6M Syrian diaspora sending $2-3B/year; 90%+ cash economy; <30% banking penetration
- PHP 8.5.6, Laravel 11.54, SQLite (dev) / MySQL 8 (prod)
- All 24 modules built, but integration gaps remain between Ledger→CFE→Wallet
- V1 strict scope per `02-v1-scope.md`: Wallet, Agent, FX, Remittance, Bills, Merchant, Fraud, Auth, Compliance
