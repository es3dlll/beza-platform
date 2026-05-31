# Build Order — Beza Platform V1 (Corrected Sequence)

## Principle

Build by dependency, not by feature. Each phase produces a working, deployable system. No module ships without its dependents tested and deployed. The critical architectural insight: **Ledger must precede Wallet** because Wallet balance is a cached projection of Ledger journal entries. Building Wallet before Ledger would require a costly rewrite. Syria-specific constraints (SMPP via Syriatel, intermittent connectivity, dual-currency SYP/USD) are treated as first-class requirements from week 1.

---

## Phase 1: Foundation (Weeks 1–4)

### Week 1: Project Scaffold

- [ ] Laravel 11 project with module structure (`Modules/` directory, each module self-contained with migrations, models, routes, controllers, services, tests)
- [ ] MySQL 8.0 database with module-per-schema naming convention (`beza_identity`, `beza_iam`, `beza_ledger`, `beza_cfe`, `beza_wallet`, `beza_agent`, `beza_fx`, `beza_remittance`, `beza_bills`, `beza_merchant`, `beza_settlement`, `beza_fraud`, `beza_compliance`, `beza_notification`)
- [ ] Redis 7 setup for cache (Laravel cache driver) + queue (horizon, 3 queues: high, default, low)
- [ ] Docker Compose for local development (PHP 8.2-fpm, MySQL 8.0, Redis 7, Nginx 1.25, Mailpit for email)
- [ ] CI/CD pipeline: GitHub Actions → PHPStan (level 6) → Pest tests → Deploy via rsync to Syrian VPS (Damascus DC)
- [ ] Monorepo structure:
  ```
  app/
  Modules/
    Identity/
    IAM/
    Ledger/
    CFE/
    Wallet/
    Agent/
    FX/
    Remittance/
    Bills/
    Merchant/
    Settlement/
    Fraud/
    Compliance/
    Notification/
  tests/
  config/
  docker/
  .github/workflows/
  ```
- [ ] Laravel Horizon config with 3 queues, supervisor per queue
- [ ] Error tracking: Sentry self-hosted (or flare) configured for Syrian network

### Week 2: Authentication & Identity

- [ ] Identity Module: migrations (`users`, `user_devices`, `user_sessions`, `user_pins`, `otp_codes`)
- [ ] Phone + OTP auth via Syriatel SMPP gateway (GSM modem fallback for redundancy)
- [ ] OTP rate limiting: max 3 attempts per phone per 10 minutes, max 5 resends per hour
- [ ] JWT access/refresh token (15 min access, 7 day refresh, stored in Redis blacklist)
- [ ] Device binding: max 2 devices per user, device fingerprint (IP + User-Agent + device ID hash)
- [ ] PIN creation: bcrypt hashed 6-digit PIN, required for all financial transactions
- [ ] Session management: Redis session store, 30 min idle timeout, force-logout on password/PIN change
- [ ] Phone number normalization: +963XXXXXXXX format, strip leading 0, validate Syria mobile prefixes (093, 094, 095, 099 → +96393...)

### Week 3: User Profile & Tiers

- [ ] User profile (name, phone, national ID, DOB, governorate, city, address)
- [ ] KYC tiers: Tier 1 (basic — phone + name, max 500K SYP daily), Tier 2 (verified — full KYC, max 5M SYP daily)
- [ ] National ID upload (front/back + selfie via Laravel Filesystem, S3-compatible or local disk with Syrian DC)
- [ ] Admin KYC review panel (Filament or custom Vue dashboard, list of pending KYC, approve/reject with reason)
- [ ] Rate limiting middleware: per-endpoint limits (auth: 5/min, transfers: 10/min, queries: 30/min)
- [ ] Syria-specific validation: national ID format (11 digits), phone prefix validation, governorate enum (14 governorates)

### Week 4: USSD (\*123#)

- [ ] USSD gateway integration (Syriatel USSD push API or GSM modem for SMPP USSD)
- [ ] USSD menu engine (max 3 levels deep, with timeouts and Arabic fallback)
- [ ] Menu structure:
  ```
  1. Balance inquiry → "Your balance is X SYP"
  2. Mini-statement → "Last 3 transactions: ..."
  3. Agent locator → "Nearest agent: [name], [distance]"
  4. PIN change → "Enter new PIN"
  5. Language → "English / العربية"
  ```
- [ ] Arabic USSD menus by default with English option
- [ ] USSD session timeout: 30 seconds, re-prompt 2 times, then terminate
- [ ] Rate limit: max 10 USSD sessions per phone per hour
- [ ] Error messages in Arabic: "عذراً، حدث خطأ. الرجاء المحاولة لاحقاً"

---

## Phase 2: IAM & Ledger (Weeks 5–8)

### Week 5: IAM Module — Roles & Permissions

- [ ] IAM Module: migrations (`roles`, `permissions`, `role_user`, `permission_role`, `model_has_roles`, `model_has_permissions`)
- [ ] Spatie Laravel Permission integration with module-based authorization
- [ ] Role hierarchy: Super Admin (full access), Compliance Officer (read + compliance actions), Finance (read + ledger), Agent Manager (agent CRUD + commission), Support (read + ticket actions)
- [ ] Module-based permissions: `wallet.transfer`, `ledger.read`, `agent.approve`, `fraud.review`, `settlement.execute`
- [ ] Permission middleware: `HasModulePermission` middleware for all financial routes
- [ ] User-role assignment via admin panel
- [ ] Audit log for all role/permission changes

### Week 6: Ledger Module — Chart of Accounts

- [ ] Ledger Module: migrations (`accounts`, `account_types`, `account_hierarchy`)
- [ ] Account types: Asset (1000–1999), Liability (2000–2999), Income (3000–3999), Expense (4000–4999), Suspense (2700–2799)
- [ ] Chart of accounts pre-seeded for Syrian context:
  - 1100 Bank Account (Settlement)
  - 1200 Agent Float Account
  - 2100 User Wallet (SYP)
  - 2101 User Wallet (USD)
  - 2300 Merchant Payable
  - 2400 Biller Payable
  - 2500 Remittance Settlement Account
  - 2600 FX Suspense Account
  - 2700 Suspense Account (Unresolved Transactions)
  - 2800 Provision for Fraud Losses
  - 2900 Corridor Partner Payable
  - 3100 Transaction Fee Income
  - 3200 FX Spread Income
  - 4100 Agent Commission Expense
- [ ] Account creation service (`LedgerService::createAccount()`) with validation against chart
- [ ] Account status: active, frozen, closed
- [ ] Balance calculation logic: `SUM(debits) — SUM(credits)` per account type (normal balance direction per type)

### Week 7: Ledger — Journal Entries & Posting Engine

- [ ] Ledger Module: migrations (`journal_entries`, `journal_entry_lines`)
- [ ] Double-entry posting engine: `LedgerService::postEntry($debitAccount, $creditAccount, $amount, $currency, $referenceId, $metadata)`
- [ ] Debits = Credits enforcement at database level (trigger or application check)
- [ ] Reference type + reference ID for traceability (links to originating transaction)
- [ ] Immutable journal entries: no UPDATE, no DELETE; only reversal entries
- [ ] Posting engine with transaction wrapping: all-or-nothing per batch
- [ ] Trial balance report: `SELECT account_id, SUM(debit), SUM(credit), SUM(debit) — SUM(credit) AS balance FROM journal_entries GROUP BY account_id` — must balance to zero

### Week 8: Ledger — Balance Calculation, Audit Trail, Reconciliation

- [ ] Balance calculation: materialized or cached per account (Redis with 60s TTL, fallback to SQL sum)
- [ ] Account history endpoint: all journal entries for a given account with pagination
- [ ] Audit trail: append-only log of all posting operations with who/what/when
- [ ] Reconciliation script: compare Ledger balance (journal sum) against external systems (wallet cache, bank statement)
- [ ] Daily reconciliation job: runs at midnight, reports mismatches to ops team
- [ ] Trial balance check: `SUM(all_account_balances) = 0` (fundamental accounting identity)

---

## Phase 3: Core Financial Engine (Weeks 9–12)

### Week 9: CFE Module — Transaction Model & State Machine

- [ ] CFE Module: migrations (`cfe_transactions`, `cfe_states`, `cfe_holds`)
- [ ] Transaction model with state machine:
  ```
  initiated → held → completed
  initiated → held → failed
  initiated → cancelled
  completed → reversed
  ```
- [ ] Hold engine: reserve balance on source account, 30-minute hold expiry with auto-release
- [ ] Hold states: pending, active, released, expired
- [ ] Idempotency: unique reference ID prevents duplicate posting (`UNIQUE KEY on reference_type + reference_id`)
- [ ] Retry mechanism: failed transactions auto-retry up to 3 times with exponential backoff

### Week 10: CFE — Posting Engine & Fee Engine

- [ ] Posting service: `CfeService::post($debits, $credits, $referenceType, $referenceId)` — validates debits = credits before posting
- [ ] Debit/credit validation: accounts exist, accounts are active, sufficient balance (for liability accounts)
- [ ] Fee engine: `FeeService::calculate($transactionType, $amount, $tier)` → returns fee amount, fee type, currency
- [ ] Fee posting: automatically creates CFE journal entry for fee income on same transaction reference
- [ ] Fee configuration: per transaction type, per tier, flat + percentage components
- [ ] Multi-currency fee support (SYP fees on USD transactions at conversion rate)

### Week 11: CFE — Reversal Engine & Suspense Handling

- [ ] Reversal engine: `CfeService::reverse($originalTransactionId, $reason)` — creates opposite entries for all original lines
- [ ] Reversal fee: calculated and posted as separate entry
- [ ] Original transaction marked as `reversed`; double-reversal prevented
- [ ] Suspense handling: `CfeService::moveToSuspense($transactionId, $reason)` — moves funds from source to suspense account
- [ ] Suspense resolution: `CfeService::releaseFromSuspense($suspenseId, $destinationAccountId)` — completes or refunds
- [ ] Suspense aging report: operations dashboard shows items older than 1h, 6h, 24h, 7d

### Week 12: CFE — Event Emission, Idempotency, Retry

- [ ] Event emission on every posting:
  - `MoneyHeld` — hold created on source account
  - `MoneyPosted` — transaction completed successfully
  - `MoneyReleased` — hold released (expired or cancelled)
  - `FeePosted` — fee entry posted
  - `ReversalPosted` — reversal completed
  - `SuspenseHeld` — funds moved to suspense
  - `SuspenseReleased` — funds released from suspense
- [ ] Event payload: reference_id, reference_type, amount, currency, account_id, timestamp, metadata
- [ ] Idempotency: Redis-based `Idempotency-Key` with 24-hour TTL, key = sha256(reference_type + reference_id)
- [ ] Retry with backoff: failed post → retry queue (high priority queue, 3 attempts, 5s/30s/120s backoff)
- [ ] Dead letter queue: after 3 failures, transaction moved to manual review queue

---

## Phase 4: Wallet Module (Weeks 13–16)

_Wallet is now built on top of a fully working Ledger + CFE. The Wallet module is a thin layer handling user-facing concerns: balances, limits, transfer UX, and notifications. All financial logic (holds, posting, fees) delegates to CFE._

### Week 13: Wallet — Database & Models

- [ ] Wallet Module: migrations (`wallets`, `wallet_limits`, `wallet_tiers`, `wallet_addresses`)
- [ ] Wallet model: multi-currency (SYP, USD), linked to CFE account via `cfe_account_id`
- [ ] Wallet creation: `WalletService::createWallet($userId, $currency)` → creates CFE account, links to wallet
- [ ] Wallet address: UUID v4 for QR and transfers
- [ ] Wallet status: active, frozen (compliance hold), closed
- [ ] Balance projection cache: Redis hash `wallet:{id}:balance` — read from Ledger on cache miss

### Week 14: Wallet — P2P Transfer Flow

- [ ] P2P transfer: lookup by phone number → resolve wallet → validate limits → call CFE hold → confirm → call CFE post
- [ ] Transfer flow delegates to CFE:
  - `CfeService::hold(sourceAccountId, amount)` — reserve balance
  - `CfeService::post([{account: source, credit: amount}, {account: dest, debit: amount}])` — finalize
- [ ] Sender debit, receiver credit, fee income — all handled by CFE posting engine
- [ ] Balance cache invalidation: invalidate both sender and receiver wallet caches on CFE `MoneyPosted` event
- [ ] Minimum transfer: 100 SYP, Maximum per transfer: tier-dependent

### Week 15: Wallet — Balance History, Limits, Queries

- [ ] Balance history: 30 days in-app (paginated, filterable by date/type), 90 days via support
- [ ] Daily/monthly limit enforcement: check against accumulated volume in Redis counters
- [ ] Limit configuration per tier: Tier 1 (500K SYP daily, 5M SYP monthly), Tier 2 (5M SYP daily, 50M SYP monthly)
- [ ] Transaction history: all wallet transactions with status, amount, fee, counterparty
- [ ] USSD balance check: `*123#` → reads from Redis cache (60s stale-while-revalidate)

### Week 16: Wallet — Notifications, SMS Receipts, USSD

- [ ] Transaction receipt: SMS + in-app notification on every completed transaction
- [ ] SMS template: "تم تحويل [amount] SYP إلى [name]. الرصيد: [balance] SYP. المرجع: [ref]"
- [ ] Language toggle receipt (Arabic by default, English on request)
- [ ] USSD mini-statement: last 3 transactions via `*123#`
- [ ] Scheduled job: release expired holds (every 5 minutes) via CFE

---

## Phase 5: Agent Network (Weeks 17–20)

### Week 17: Agent Module — Registration

- [ ] Agent registration: name, shop name, governorate/city, location (lat/lng), phone, national ID
- [ ] Agent KYC: shop photo, national ID (front/back), utility bill, business registration (if applicable)
- [ ] Agent approval workflow: pending → document review → approved/ rejected
- [ ] Agent Android app scaffold: Kotlin + Jetpack Compose, API client with token auth
- [ ] Agent QR generation: unique QR per agent (encodes agent UUID + wallet address)
- [ ] Agent commission structure: configurable per transaction type (% of fee or flat amount)

### Week 18: Agent — Cash-in/Cash-out

- [ ] Agent cash-in: agent scans user QR → enters amount → user confirms with PIN → CFE posting (user credit, agent debit float)
- [ ] Agent cash-out: user requests amount → agent gives cash → user confirms with PIN → CFE posting (user debit, agent credit float)
- [ ] Agent float management: linked to Ledger account 1200 (Agent Float Account)
- [ ] Agent commission calculation: computed on completion, credited to agent commission wallet
- [ ] Cash-in limits: Tier 1 max 500K SYP/day, Tier 2 max 5M SYP/day
- [ ] Cash-out limits: same as cash-in
- [ ] SMS receipt to both user and agent after each transaction

### Week 19: Agent — Operations

- [ ] Agent commission dashboard: daily/weekly/monthly earnings, payouts, pending commissions
- [ ] Agent geo-location: map view for users to find nearest agent (Google Maps / OpenStreetMap)
- [ ] Agent float top-up via bank transfer: agent initiates → admin confirms → CFE posting (bank to float)
- [ ] Agent suspension/block: admin action (fraud, inactivity, KYC expiry)
- [ ] Agent performance reports: transaction volume, commission earned, customer satisfaction

### Week 20: Agent — Fraud & Limits

- [ ] Agent float mismatch detection: if `SUM(cash_in) − SUM(cash_out) ≠ float_change`, flag for review
- [ ] Agent daily limits enforcement: configurable per agent (max 10M SYP/day cash-in, 5M SYP/day cash-out)
- [ ] Same-device multi-agent detection: alert if multiple agents log in from same device fingerprint
- [ ] Agent fraud alerts: unusual patterns (multiple cash-outs same user, rapid transactions, location anomaly)
- [ ] Agent session timeout: 5 min idle, auto-logout
- [ ] Agent PIN change forced every 30 days

---

## Phase 6: FX Engine (Weeks 21–24)

### Week 21: FX — Module Setup

- [ ] FX Module: migrations (`exchange_rates`, `fx_quotes`, `fx_transactions`, `fx_limits`)
- [ ] Exchange rate provider integration: CBS daily rate (XML/CSV feed) + market rate spread
- [ ] Rate types: CBS official rate, Beza market rate (CBS + spread)
- [ ] Spread configuration: configurable per corridor, per tier (Tier 1: 2%, Tier 2: 1%)
- [ ] Rate cache in Redis with 15-minute TTL

### Week 22: FX — Quote & Lock

- [ ] Quote engine: user requests quote → system calculates rate + Beza spread + fees → quote valid for 60 seconds
- [ ] Rate lock: when quote accepted, rate is locked for 60 seconds
- [ ] Corridors: SYP→USD, USD→SYP (expand to EUR, TRY, SAR in V2)
- [ ] FX limits: Tier 1 max 1M SYP/month FX volume, Tier 2 max 10M SYP/month

### Week 23: FX — Execution

- [ ] FX execution: CFE double-entry via suspense (debit SYP wallet → credit FX suspense → debit FX suspense → credit USD wallet)
- [ ] FX fee calculation: Beza spread, fixed fee (configurable), CBS surcharge (configurable)
- [ ] FX receipt: SMS + in-app notification with amount, rate, fees, effective amount
- [ ] FX transaction history in wallet transaction list (tagged as "FX Conversion")

### Week 24: FX — Admin & Monitoring

- [ ] Admin rate override: CBS rate override + market rate manual adjustment
- [ ] FX P&L reporting: daily revenue from FX spreads
- [ ] Rate alert: if CBS rate changes by >5%, notify admin
- [ ] FX reconciliation: end-of-day check vs CFE (suspense account must be zero)

---

## Phase 7: Remittance (Weeks 25–28)

### Week 25: Remittance — Module & Corridors

- [ ] Remittance Module: migrations (`remittance_transactions`, `corridors`, `remittance_fees`, `remittance_partners`)
- [ ] Corridor setup: Diaspora → Syrian recipient (USD → SYP, EUR → SYP)
- [ ] Sender registration: diaspora user (email, passport, foreign address, source of funds)
- [ ] Recipient mapping: diaspora sender links to Syrian recipient phone number
- [ ] AML screening: sender name against sanction lists (OFAC, EU, UN)

### Week 26: Remittance — Send Flow

- [ ] Sender initiates: select corridor → enter amount (foreign currency) → confirm rate + fees → pay via card/wallet
- [ ] Conversion: FX engine converts foreign currency to SYP at locked rate
- [ ] Recipient notification: SMS + in-app push ("You received [amount] SYP from [sender name]")
- [ ] Payout options: wallet credit (instant), agent cash pickup (2 hours)
- [ ] Remittance limits: Max $5,000 per transaction, $20,000 per month per sender

### Week 27: Remittance — Payout & Compliance

- [ ] Wallet payout: immediate CFE posting to recipient wallet
- [ ] Agent cash pickup: recipient gets SMS with pickup code → presents at agent → agent validates → cash given
- [ ] Compliance hold: transactions > $1,000 held for manual review (24-hour SLA)
- [ ] Source of funds check for senders: triggered at $3,000+ per transaction
- [ ] CBS reporting: daily remittance report XML feed to Central Bank of Syria

### Week 28: Remittance — Operations

- [ ] Remittance dashboard: volume, corridors, fees, processing times
- [ ] Failed transaction handling: auto-retry up to 3 times, then manual review
- [ ] Refund flow: if recipient unavailable for 30 days, reverse transaction
- [ ] Partner reconciliation: daily report for payout partners (Western Union, MoneyGram-style)

---

## Phase 8: Bill Payment (Weeks 25–28, parallel with Phase 7)

### Week 25: Bills — Module Setup

- [ ] Bills Module: migrations (`bills`, `bill_payments`, `biller_contracts`)
- [ ] Biller types: electricity (Syriatel/Electricity Ministry), telecom (Syriatel, MTN), water, internet, government
- [ ] Biller API integration: SOAP/XML for government, REST for private
- [ ] Bill presentment: user enters account number → system fetches bill (amount, due date, biller name)

### Week 26: Bills — Payment Flow

- [ ] Payment flow: view bill → select wallet → enter PIN → confirm → CFE posting → biller confirmation
- [ ] Scheduled payments: set future date for recurring bills (weekly/monthly)
- [ ] Bill payment history: 90 days in-app, filterable by biller
- [ ] Bill payment receipt: SMS + in-app with bill reference number

### Week 27: Bills — Biller Reconciliation

- [ ] Biller settlement: end-of-day batch settlement to biller bank accounts
- [ ] Reconciliation: match payments sent to biller vs confirmed by biller
- [ ] Failed payment handling: biller API timeout → retry 3 times → refund wallet

### Week 28: Bills — Admin

- [ ] Biller management: add/edit/disable billers, configure API endpoints
- [ ] Bill payment reports: volume, success rate, fees, biller-wise breakdown
- [ ] Late payment reminders: SMS + in-app notification 3 days before due date

---

## Phase 9: Merchant QR (Weeks 29–32)

### Week 29: Merchant — Module & Registration

- [ ] Merchant Module: migrations (`merchants`, `merchant_qr_codes`, `merchant_settlements`)
- [ ] Merchant registration: business name, owner details, location, category (retail, food, services)
- [ ] Merchant KYC: business registration, tax ID, owner national ID, shop photos
- [ ] Merchant QR generation: static QR (same amount) and dynamic QR (amount set at POS)
- [ ] QR format: EMVCo merchant QR standard with Beza prefix

### Week 30: Merchant — Payment Flow

- [ ] Customer payment: scan merchant QR → enter amount (dynamic) or confirm (static) → enter PIN → CFE posting
- [ ] Merchant notification: real-time push notification on payment received
- [ ] Receipt generation: digital receipt sent to both customer and merchant
- [ ] Merchant POS integration: API for POS systems (REST + WebSocket)

### Week 31: Merchant — Settlement

- [ ] Merchant settlement: T+1 settlement from merchant wallet to merchant bank account
- [ ] Settlement batch: end-of-day calculation of all merchant transactions
- [ ] Settlement report: daily email with transaction list and net amount
- [ ] Settlement fee: configurable % per transaction (Merchant Discount Rate)

### Week 32: Merchant — Operations

- [ ] Merchant dashboard: transaction history, settlement status, daily/weekly volumes
- [ ] Merchant QR reprint: regenerate QR code if lost or damaged
- [ ] Merchant dispute handling: customer claims incorrect charge → merchant reviews → refund/decline
- [ ] Merchant fraud alerts: unusual transaction patterns (multiple small payments, rapid refunds)

---

## Phase 10: Operations (Weeks 29–32, parallel)

### Week 29: Admin Dashboard

- [ ] Admin dashboard: real-time KPIs (active users, transactions per minute, wallet balances, agent activity)
- [ ] User management: search, view, suspend, delete users
- [ ] KYC review panel: pending KYC list, approve/reject with notes
- [ ] Transaction search: search by transaction ID, user phone, amount, date range

### Week 30: Reports

- [ ] Daily settlement report: all transactions, fees, commissions, net positions
- [ ] Regulatory reports: CBS daily transaction report, AML suspicious activity report
- [ ] Revenue report: revenue by module (fees, spreads, commissions)
- [ ] Export: CSV/Excel download for all reports

### Week 31: Alerts & Monitoring

- [ ] System health dashboard: Redis hit rate, queue depth, API response times, error rates
- [ ] Transaction anomaly alerts: >20% failure rate, >10% fraud score, >50% queue backlog
- [ ] SMS credit monitoring: alert when SMS balance < 10,000 credits
- [ ] Server monitoring: CPU, memory, disk, network (Prometheus + Grafana)

### Week 32: Load Testing & Go-Live

- [ ] Load test: 100 concurrent users, 500 TPS target
- [ ] Failover test: MySQL primary → replica failover, Redis cluster failover
- [ ] Disaster recovery: full restore from backup (RPO: 15 min, RTO: 1 hour)
- [ ] Security audit: OWASP Top 10 scan, dependency vulnerability scan
- [ ] Go-live checklist sign-off: compliance, security, infrastructure, operations

---

## Phase 11: Fraud Engine (Weeks 5–32, parallel)

### Week 5–8: IAM & Ledger Foundation

- [ ] Fraud Module: migrations (`fraud_rules`, `fraud_cases`, `fraud_events`, `fraud_models`)
- [ ] Rule engine: configurable rules (IF `amount > X` AND `new_device = true` THEN `score = Y`)
- [ ] Event ingestion: consume identity, IAM, Ledger events via Laravel events
- [ ] Rule types: velocity (X transactions per Y time), geo-anomaly, device-anomaly, amount-anomaly

### Week 9–12: CFE Integration

- [ ] Risk scoring pipeline: event → rule evaluation (parallel, Redis) → score aggregation → decision (allow/block/review)
- [ ] Thresholds: Green (0–30: allow), Yellow (31–70: review), Red (71–100: block)
- [ ] Real-time blocking: Redis pub/sub for fraud decisions → middleware blocks API request
- [ ] Case management: flagged transactions become fraud cases in admin panel

### Week 13–24: Wallet & Agent Integration

- [ ] Wallet-specific rules: new wallet sending >500K SYP in first 24 hours
- [ ] Agent-specific rules: agent-customer collusion detection, float mismatch alerts
- [ ] Device fingerprint analysis: SIM swap detection, multi-account device binding
- [ ] Fraud ops dashboard: case list, decision workflow, reporting

### Week 25–32: Advanced Fraud Ops

- [ ] Fraud investigation dashboard: case list with risk score, user history, device history
- [ ] False positive management: review → mark as false positive → feed back to model
- [ ] Fraud reporting: daily fraud summary, top rules triggered, false positive rate
- [ ] Syria-specific rules: new user sending >500K SYP in first 24 hours, agent-customer collusion pattern

---

## V1 Build Order Summary

| Phase            | Duration           | Modules                                                   | Dependencies           |
| ---------------- | ------------------ | --------------------------------------------------------- | ---------------------- |
| 1: Foundation    | W1–W4              | Identity, Auth, Profile, USSD                             | None                   |
| 2: IAM & Ledger  | W5–W8              | IAM, Ledger (Accounts, Journal, Posting)                  | Phase 1                |
| 3: CFE           | W9–W12             | CFE (State Machine, Post, Fee, Reverse, Suspense, Events) | Phase 2                |
| 4: Wallet        | W13–W16            | Wallet (Models, P2P, Limits, History, Notifications)      | Phase 1, 2, 3          |
| 5: Agent Network | W17–W20            | Agent, Agent App                                          | Phase 4                |
| 6: FX Engine     | W21–W24            | FX, Rates API                                             | Phase 4 (Wallet + CFE) |
| 7: Remittance    | W25–W28            | Remittance, Corridors                                     | Phase 6                |
| 8: Bill Payment  | W25–W28 (parallel) | Bills, Biller APIs                                        | Phase 4                |
| 9: Merchant QR   | W29–W32            | Merchant, Merchant QR                                     | Phase 4                |
| 10: Operations   | W29–W32 (parallel) | Admin, Reports, Alerts, Infra                             | ALL                    |
| 11: Fraud Engine | W5–W32 (parallel)  | Fraud Rules, Cases                                        | Phase 2+               |

## Critical Path

```
Identity ──▶ IAM ──▶ Ledger ──▶ CFE ──▶ Wallet ──▶ Agent ──▶ FX ──▶ Remittance
                                          ├──▶ Bills (parallel W25)
                                          └──▶ Merchant ──▶ Settlement
                                                    │
                                          Operations ┘
Fraud Engine (parallel, W5 onward)
```

Total engineering calendar: **32 weeks** from scaffold to production go-live. The additional 8 weeks versus the original 24-week plan come from inserting IAM + Ledger + CFE as a prerequisite phase before Wallet. This eliminates the architectural debt of building Wallet without a ledger.
