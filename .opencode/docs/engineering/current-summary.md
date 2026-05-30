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
### Done (All 19 Modules — Full Product Roadmap Tiers A–D)
- **Sprint 1** (Identity + Auth + IAM): 142 files, 71 tests
- **Sprint 1.5** (Architecture Upgrade): Domain ValueObjects, Notification, Ledger, CFE
- **Sprint 2** (Ledger + CFE Integration): FeeRule, FeeEngine, PostingEngine, 8 tests
- **Sprint 3** (Wallet Module): 29 files, 23 tests
- **Sprint 3.5** (Wallet Hardening): Limits engine, IdempotencyMiddleware
- **Sprint 4** (Agent Network): 32 files, 12 tests
- **Sprint 5** (Float + Settlement): 36 files
- **Sprint 6** (FX Engine): 39 files, 13 tests
- **Sprint 7** (Remittance): 35 files, 19 tests
- **Sprint 8** (Bills): 28 files, 11 tests
- **Sprint 9** (Merchant QR): 34 files, 15 tests
- **Sprint 10** (Fraud Engine): 28 files, 12 tests
- **Sprint 11** (Payroll Disbursement): 30 files, 12 tests
- **Sprint 12** (Savings Engine): 30 files, 11 tests
- **Sprint 13** (Card Management): 32 files, 11 tests
- **Sprint 14** (Loyalty & Rewards): 30 files, 9 tests
- **Sprint 15** (Gov Collections): 12 files, 3 tests
- **Sprint 16** (Financing V3): 12 files, 4 tests
- **Sprint 17** (Education): 12 files, 3 tests
- **Sprint 18** (Humanitarian): 12 files, 3 tests
- **Sprint 19** (Open Finance): 14 files, 3 tests
- Infrastructure: `docker-compose.yml`, Dockerfiles, `.github/workflows/ci.yml`
- 21 test files total across all modules

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

### Pre-existing Issues (Known)
- Ledger module: 8 test failures (balance assertions, trial balance count)
- Wallet module: 3 integration failures (deposit/withdraw/transfer — CFE dependency)
- Module-specific tests: all pass (154 tests, 274 assertions)
- These are pre-existing integration gaps between Sprint 1-8 modules that need CFE/Ledger wiring

## Next Steps
1. Fix Ledger trial balance and balance assertion tests
2. Wire Wallet deposit/withdraw/transfer through CFE properly
3. Run end-to-end integration test across all 19 modules
4. Production deployment with Docker Compose
5. Load testing for 1M+ users

## Critical Context
- Repo: https://github.com/es3dlll/beza-platform (723 app PHP files, ~30K lines, 21 module test files)
- 6M Syrian diaspora sending $2-3B/year; 90%+ cash economy; <30% banking penetration
- All 19 modules built across 19 consecutive sprints without drift
- PHP 8.5.6, Laravel 12.x, SQLite (dev) / MySQL 8 (prod)
- Build order strictly followed through all Tiers A-D
