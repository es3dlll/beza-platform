# Build Order — Beza Platform V1

## Principle
Build by dependency, not by feature. Each phase produces a working, deployable system. No module ships without its dependents tested and deployed. Syria-specific constraints (SMPP via Syriatel, intermittent connectivity, dual-currency SYP/USD) are treated as first-class requirements from week 1.

---

## Phase 1: Foundation (Weeks 1–4)

### Week 1: Project Scaffold
- [ ] Laravel 11 project with module structure (`Modules/` directory, each module self-contained with migrations, models, routes, controllers, services, tests)
- [ ] MySQL 8.0 database with module-per-schema naming convention (`beza_identity`, `beza_wallet`, `beza_cfe`, `beza_agent`, `beza_fx`, `beza_remittance`, `beza_bills`, `beza_merchant`, `beza_settlement`, `beza_fraud`, `beza_compliance`, `beza_notification`)
- [ ] Redis 7 setup for cache (Laravel cache driver) + queue (horizon, 3 queues: high, default, low)
- [ ] Docker Compose for local development (PHP 8.2-fpm, MySQL 8.0, Redis 7, Nginx 1.25, Mailpit for email)
- [ ] CI/CD pipeline: GitHub Actions → PHPStan (level 6) → Pest tests → Deploy via rsync to Syrian VPS (Damascus DC)
- [ ] Monorepo structure:
  ```
  app/
  Modules/
    Identity/
    Wallet/
    CFE/
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

### Week 4: USSD (*123#)
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

## Phase 2: Wallet Core (Weeks 5–8)

### Week 5: Wallet Module — Database & Models
- [ ] Wallet Module: migrations (`wallets`, `transactions`, `holds`, `wallet_limits`, `wallet_tiers`)
- [ ] Wallet model with multi-currency support (SYP, USD) — separate balance columns or join table
- [ ] Transaction model with state machine:
  ```
  pending → held → completed
  pending → held → failed
  pending → cancelled
  ```
- [ ] Wallet limits per tier:
  - Tier 1: Daily 500K SYP, Monthly 5M SYP, Max balance 5M SYP
  - Tier 2: Daily 5M SYP, Monthly 50M SYP, Max balance 50M SYP
- [ ] Wallet status: active, frozen (compliance hold), closed
- [ ] Unique wallet address (UUID v4) per wallet for QR and transfers

### Week 6: Wallet — Transfer Flow
- [ ] P2P transfer by phone number (lookup user → resolve wallet → validate limits → hold → confirm)
- [ ] Hold engine: reserve balance on sender wallet (transaction state = held), 30-minute hold expiry
- [ ] CFE posting: double-entry on completion (`sender_wallet_id CREDIT`, `receiver_wallet_id DEBIT`)
- [ ] Balance cache: Redis hash with wallet_id → balance, invalidated on CFE event
- [ ] Idempotency middleware: `Idempotency-Key` header, 24-hour key expiry, stored in Redis
- [ ] Transfer receipt: UUID-based, sent via SMS and in-app notification
- [ ] Minimum transfer: 100 SYP, Maximum per transfer: tier-dependent

### Week 7: Wallet — History, Limits, Fraud Integration
- [ ] Transaction history: 30 days in-app (paginated, filterable by date/type), 90 days via support
- [ ] Daily/monthly limit enforcement: check against accumulated volume in Redis counters
- [ ] Fraud screening hook: risk score (0–100) calculated before posting, threshold > 70 blocks transaction
- [ ] Device fingerprint collection on every session (IP, User-Agent, device ID, screen resolution, timezone)
- [ ] Transaction notes: optional 140-char Arabic/English note with each transfer
- [ ] Scheduled job: release expired holds (every 5 minutes)

### Week 8: Wallet — CFE Integration
- [ ] CFE Module: migrations (`accounts`, `journal_entries`)
- [ ] Double-entry posting: sender debit (liability account), receiver credit (liability account)
- [ ] Balance reconciliation: `SUM(journal_entries WHERE account_id = wallet.cfe_account_id) = wallet.balance`
- [ ] Account types: asset (cash, bank), liability (customer wallets, agent floats), income (fees), expense (commissions, SMS costs)
- [ ] Chart of accounts: pre-seeded for Syrian context (CBS-compatible categories)
- [ ] Automated reconciliation check: daily cron compares CFE balance vs wallet balance, alerts on mismatch
- [ ] CFE audit log: immutable append-only table for all journal entries

---

## Phase 3: Agent Network (Weeks 9–12)

### Week 9: Agent Module — Registration
- [ ] Agent registration: name, shop name, governorate/city, location (lat/lng), phone, national ID
- [ ] Agent KYC: shop photo, national ID (front/back), utility bill, business registration (if applicable)
- [ ] Agent approval workflow: pending → document review → approved/ rejected
- [ ] Agent Android app scaffold: Kotlin + Jetpack Compose, API client with token auth
- [ ] Agent QR generation: unique QR per agent (encodes agent UUID + wallet address)
- [ ] Agent commission structure: configurable per transaction type (% of fee or flat amount)

### Week 10: Agent — Cash-in/Cash-out
- [ ] Agent cash-in: agent scans user QR → enters amount → user confirms with PIN → CFE posting (user credit, agent debit float)
- [ ] Agent cash-out: user requests amount → agent gives cash → user confirms with PIN → CFE posting (user debit, agent credit float)
- [ ] Agent float management: dedicated agent wallet with float balance, top-up via bank transfer
- [ ] Agent commission calculation: computed on completion, credited to agent commission wallet
- [ ] Cash-in limits: Tier 1 max 500K SYP/day, Tier 2 max 5M SYP/day
- [ ] Cash-out limits: same as cash-in
- [ ] SMS receipt to both user and agent after each transaction

### Week 11: Agent — Operations
- [ ] Agent commission dashboard: daily/weekly/monthly earnings, payouts, pending commissions
- [ ] Agent geo-location: map view for users to find nearest agent (Google Maps / OpenStreetMap)
- [ ] Agent float top-up via bank transfer: agent initiates → admin confirms → float credited
- [ ] Agent suspension/block: admin action (fraud, inactivity, KYC expiry)
- [ ] Agent performance reports: transaction volume, commission earned, customer satisfaction

### Week 12: Agent — Fraud & Limits
- [ ] Agent float mismatch detection: if `SUM(cash_in) - SUM(cash_out) ≠ float_change`, flag for review
- [ ] Agent daily limits enforcement: configurable per agent (max 10M SYP/day cash-in, 5M SYP/day cash-out)
- [ ] Same-device multi-agent detection: alert if multiple agents log in from same device fingerprint
- [ ] Agent fraud alerts: unusual patterns (multiple cash-outs same user, rapid transactions, location anomaly)
- [ ] Agent session timeout: 5 min idle, auto-logout
- [ ] Agent PIN change forced every 30 days

---

## Phase 4: FX Engine (Weeks 13–16)

### Week 13: FX — Module Setup
- [ ] FX Module: migrations (`exchange_rates`, `fx_quotes`, `fx_transactions`, `fx_limits`)
- [ ] Exchange rate provider integration: CBS daily rate (XML/CSV feed) + market rate spread
- [ ] Rate types: CBS official rate, Beza market rate (CBS + spread)
- [ ] Spread configuration: configurable per corridor, per tier (Tier 1: 2%, Tier 2: 1%)
- [ ] Rate cache in Redis with 15-minute TTL

### Week 14: FX — Quote & Lock
- [ ] Quote engine: user requests quote → system calculates rate + Beza spread + fees → quote valid for 60 seconds
- [ ] Rate lock: when quote accepted, rate is locked for 60 seconds
- [ ] Corridors: SYP→USD, USD→SYP (expand to EUR, TRY, SAR in V2)
- [ ] FX limits: Tier 1 max 1M SYP/month FX volume, Tier 2 max 10M SYP/month

### Week 15: FX — Execution
- [ ] FX execution: debit source currency wallet → credit destination currency wallet → CFE posting with FX journal entries
- [ ] FX fee calculation: Beza spread, fixed fee (configurable), CBS surcharge (configurable)
- [ ] FX receipt: SMS + in-app notification with amount, rate, fees, effective amount
- [ ] FX transaction history in wallet transaction list (tagged as "FX Conversion")

### Week 16: FX — Admin & Monitoring
- [ ] Admin rate override: CBS rate override + market rate manual adjustment
- [ ] FX P&L reporting: daily revenue from FX spreads
- [ ] Rate alert: if CBS rate changes by >5%, notify admin
- [ ] FX reconciliation: end-of-day check vs CFE

---

## Phase 5: Remittance (Weeks 17–20)

### Week 17: Remittance — Module & Corridors
- [ ] Remittance Module: migrations (`remittance_transactions`, `corridors`, `remittance_fees`, `remittance_partners`)
- [ ] Corridor setup: Diaspora → Syrian recipient (USD → SYP, EUR → SYP)
- [ ] Sender registration: diaspora user (email, passport, foreign address, source of funds)
- [ ] Recipient mapping: diaspora sender links to Syrian recipient phone number
- [ ] AML screening: sender name against sanction lists (OFAC, EU, UN)

### Week 18: Remittance — Send Flow
- [ ] Sender initiates: select corridor → enter amount (foreign currency) → confirm rate + fees → pay via card/wallet
- [ ] Conversion: FX engine converts foreign currency to SYP at locked rate
- [ ] Recipient notification: SMS + in-app push ("You received [amount] SYP from [sender name]")
- [ ] Payout options: wallet credit (instant), agent cash pickup (2 hours)
- [ ] Remittance limits: Max $5,000 per transaction, $20,000 per month per sender

### Week 19: Remittance — Payout & Compliance
- [ ] Wallet payout: immediate CFE posting to recipient wallet
- [ ] Agent cash pickup: recipient gets SMS with pickup code → presents at agent → agent validates → cash given
- [ ] Compliance hold: transactions > $1,000 held for manual review (24-hour SLA)
- [ ] Source of funds check for senders: triggered at $3,000+ per transaction
- [ ] CBS reporting: daily remittance report XML feed to Central Bank of Syria

### Week 20: Remittance — Operations
- [ ] Remittance dashboard: volume, corridors, fees, processing times
- [ ] Failed transaction handling: auto-retry up to 3 times, then manual review
- [ ] Refund flow: if recipient unavailable for 30 days, reverse transaction
- [ ] Partner reconciliation: daily report for payout partners (Western Union, MoneyGram-style)

---

## Phase 6: Bill Payment (Weeks 17–20, parallel with Phase 5)

### Week 17: Bills — Module Setup
- [ ] Bills Module: migrations (`bills`, `bill_payments`, `biller_contracts`)
- [ ] Biller types: electricity (Syriatel/Electricity Ministry), telecom (Syriatel, MTN), water, internet, government
- [ ] Biller API integration: SOAP/XML for government, REST for private
- [ ] Bill presentment: user enters account number → system fetches bill (amount, due date, biller name)

### Week 18: Bills — Payment Flow
- [ ] Payment flow: view bill → select wallet → enter PIN → confirm → CFE posting → biller confirmation
- [ ] Scheduled payments: set future date for recurring bills (weekly/monthly)
- [ ] Bill payment history: 90 days in-app, filterable by biller
- [ ] Bill payment receipt: SMS + in-app with bill reference number

### Week 19: Bills — Biller Reconciliation
- [ ] Biller settlement: end-of-day batch settlement to biller bank accounts
- [ ] Reconciliation: match payments sent to biller vs confirmed by biller
- [ ] Failed payment handling: biller API timeout → retry 3 times → refund wallet

### Week 20: Bills — Admin
- [ ] Biller management: add/edit/disable billers, configure API endpoints
- [ ] Bill payment reports: volume, success rate, fees, biller-wise breakdown
- [ ] Late payment reminders: SMS + in-app notification 3 days before due date

---

## Phase 7: Merchant QR (Weeks 21–24)

### Week 21: Merchant — Module & Registration
- [ ] Merchant Module: migrations (`merchants`, `merchant_qr_codes`, `merchant_settlements`)
- [ ] Merchant registration: business name, owner details, location, category (retail, food, services)
- [ ] Merchant KYC: business registration, tax ID, owner national ID, shop photos
- [ ] Merchant QR generation: static QR (same amount) and dynamic QR (amount set at POS)
- [ ] QR format: EMVCo merchant QR standard with Beza prefix

### Week 22: Merchant — Payment Flow
- [ ] Customer payment: scan merchant QR → enter amount (dynamic) or confirm (static) → enter PIN → CFE posting
- [ ] Merchant notification: real-time push notification on payment received
- [ ] Receipt generation: digital receipt sent to both customer and merchant
- [ ] Merchant POS integration: API for POS systems (REST + WebSocket)

### Week 23: Merchant — Settlement
- [ ] Merchant settlement: T+1 settlement from merchant wallet to merchant bank account
- [ ] Settlement batch: end-of-day calculation of all merchant transactions
- [ ] Settlement report: daily email with transaction list and net amount
- [ ] Settlement fee: configurable % per transaction (Merchant Discount Rate)

### Week 24: Merchant — Operations
- [ ] Merchant dashboard: transaction history, settlement status, daily/weekly volumes
- [ ] Merchant QR reprint: regenerate QR code if lost or damaged
- [ ] Merchant dispute handling: customer claims incorrect charge → merchant reviews → refund/decline
- [ ] Merchant fraud alerts: unusual transaction patterns (multiple small payments, rapid refunds)

---

## Phase 8: Operations (Weeks 21–24, parallel)

### Week 21: Admin Dashboard
- [ ] Admin dashboard: real-time KPIs (active users, transactions per minute, wallet balances, agent activity)
- [ ] User management: search, view, suspend, delete users
- [ ] KYC review panel: pending KYC list, approve/reject with notes
- [ ] Transaction search: search by transaction ID, user phone, amount, date range

### Week 22: Reports
- [ ] Daily settlement report: all transactions, fees, commissions, net positions
- [ ] Regulatory reports: CBS daily transaction report, AML suspicious activity report
- [ ] Revenue report: revenue by module (fees, spreads, commissions)
- [ ] Export: CSV/Excel download for all reports

### Week 23: Alerts & Monitoring
- [ ] System health dashboard: Redis hit rate, queue depth, API response times, error rates
- [ ] Transaction anomaly alerts: >20% failure rate, >10% fraud score, >50% queue backlog
- [ ] SMS credit monitoring: alert when SMS balance < 10,000 credits
- [ ] Server monitoring: CPU, memory, disk, network (Prometheus + Grafana)

### Week 24: Load Testing & Go-Live
- [ ] Load test: 100 concurrent users, 500 TPS target
- [ ] Failover test: MySQL primary → replica failover, Redis cluster failover
- [ ] Disaster recovery: full restore from backup (RPO: 15 min, RTO: 1 hour)
- [ ] Security audit: OWASP Top 10 scan, dependency vulnerability scan
- [ ] Go-live checklist sign-off: compliance, security, infrastructure, operations

---

## Phase 9: Fraud Engine (Weeks 5–24, parallel)

### Week 5–8: Fraud Core
- [ ] Fraud Module: migrations (`fraud_rules`, `fraud_cases`, `fraud_events`, `fraud_models`)
- [ ] Rule engine: configurable rules (IF `amount > X` AND `new_device = true` THEN `score = Y`)
- [ ] Event ingestion: consume wallet, auth, remittance events via Laravel events
- [ ] Rule types: velocity (X transactions per Y time), geo-anomaly, device-anomaly, amount-anomaly

### Week 9–12: Fraud Rules & Scoring
- [ ] Risk scoring pipeline: event → rule evaluation (parallel, Redis) → score aggregation → decision (allow/block/review)
- [ ] Thresholds: Green (0–30: allow), Yellow (31–70: review), Red (71–100: block)
- [ ] Real-time blocking: Redis pub/sub for fraud decisions → middleware blocks API request
- [ ] Case management: flagged transactions become fraud cases in admin panel

### Week 13–16: ML Models
- [ ] Feature engineering: transaction frequency, amount deviation, device velocity, geographic velocity
- [ ] Model training: historical data labeled by rule engine (supervised: gradient boosting)
- [ ] Model deployment: ONNX runtime in PHP (or Python microservice)
- [ ] Model monitoring: drift detection, accuracy vs rule engine

### Week 17–24: Fraud Ops
- [ ] Fraud investigation dashboard: case list with risk score, user history, device history
- [ ] False positive management: review → mark as false positive → feed back to model
- [ ] Fraud reporting: daily fraud summary, top rules triggered, false positive rate
- [ ] Syria-specific rules: new user sending >500K SYP in first 24 hours, agent-customer collusion pattern

---

## V1 Build Order Summary

| Phase | Duration | Modules | Dependencies |
|-------|----------|---------|-------------|
| 1: Foundation | W1–W4 | Identity, Auth, Profile, USSD | None |
| 2: Wallet Core | W5–W8 | Wallet, CFE | Phase 1 |
| 3: Agent Network | W9–W12 | Agent, Agent App | Phase 2 |
| 4: FX Engine | W13–W16 | FX, Rates API | Phase 2 |
| 5: Remittance | W17–W20 | Remittance, Corridors | Phase 4 |
| 6: Bill Payment | W17–W20 | Bills, Biller APIs | Phase 2 |
| 7: Merchant QR | W21–W24 | Merchant, Merchant QR | Phase 2 |
| 8: Operations | W21–W24 | Admin, Reports, Alerts, Infra | ALL |
| 9: Fraud Engine | W5–W24 (parallel) | Fraud Rules, ML, Cases | Phase 2+ |

## Critical Path
```
Identity ──▶ Wallet ──▶ Agent ───┐
                      ├──▶ FX ──▶ Remittance
                      ├──▶ Bills  │
                      └──▶ Merchant ──▶ Settlement
                                        │
Operations ────────────────────────────┘
Fraud Engine (parallel, W5 onward)
```

Total engineering calendar: **24 weeks** from scaffold to production go-live.
