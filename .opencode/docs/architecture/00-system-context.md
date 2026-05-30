# System Context — Beza Financial OS

## Legend

| Symbol | Meaning |
|--------|---------|
| [Person] | User / External Actor |
| [System] | Beza Internal Component |
| [Ext Sys] | External System |
| --> | Relationship / Data Flow |

## Diagram (Top Level)

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│                             BEZA FINANCIAL OS                                    │
│                                                                                  │
│  ┌────────────┐  ┌──────────────┐  ┌──────────────┐  ┌────────────────────┐    │
│  │  Wallet    │  │   FX Engine  │  │  Agent       │  │  CFE (Core         │    │
│  │  Module    │  │              │  │  Network     │  │  Financial Engine) │    │
│  └─────┬──────┘  └──────┬───────┘  └──────┬───────┘  └─────────┬──────────┘    │
│        │                │                  │                   │                │
│  ┌─────▼──────┐  ┌──────▼───────┐  ┌──────▼───────┐  ┌─────────▼──────────┐   │
│  │ Remittance │  │  Merchant    │  │  Bills       │  │  Settlement        │   │
│  │ Module     │  │  Payments    │  │  Payment     │  │  Engine            │   │
│  └────────────┘  └──────────────┘  └──────────────┘  └────────────────────┘   │
│                                                                                  │
│  ┌────────────┐  ┌──────────────┐  ┌──────────────┐  ┌────────────────────┐    │
│  │ Compliance │  │  Treasury    │  │  Notification│  │  Identity &        │    │
│  │ Engine     │  │  Management  │  │  Dispatcher  │  │  KYC Module        │    │
│  └────────────┘  └──────────────┘  └──────────────┘  └────────────────────┘    │
│                                                                                  │
└──────────────────────────────────────────────────────────────────────────────────┘
         │                    │                    │                    │
         ▼                    ▼                    ▼                    ▼
┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────────┐
│  SYRIAN USER    │  │   AGENT         │  │   MERCHANT      │  │  GOVERNMENT /       │
│  [Person]       │  │   [Person]      │  │   [Person]      │  │  REGULATOR [Ext Sys]│
│                 │  │                 │  │                 │  │                     │
│ • Individual    │  │ • Retail outlet │  │ • Shop/e-tail   │  │ • Central Bank of   │
│ • Diaspora      │  │ • Cash-in/      │  │ • Service       │  │   Syria (CBS)       │
│ • Wage earner   │  │   Cash-out point│  │   provider      │  │ • Syrian Capital    │
│ • SME owner     │  │ • Mobile money  │  │ • POS terminal  │  │   Market Authority  │
└─────────────────┘  └─────────────────┘  └─────────────────┘  └─────────────────────┘
         │                    │                    │                    │
         ▼                    ▼                    ▼                    ▼
┌──────────────────────────────────────────────────────────────────────────────────┐
│                              EXTERNAL SYSTEMS                                    │
│                                                                                  │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐  ┌────────────┐  │
│  │  CENTRAL BANK   │  │  COMMERCIAL     │  │  TELECOM        │  │  COMPLIANCE│  │
│  │  OF SYRIA (CBS) │  │  BANKS          │  │  PROVIDERS      │  │  DATABASES │  │
│  │                 │  │                 │  │                 │  │            │  │
│  │ • RTGS (Real-   │  │ • BSO (Bank of  │  │ • Syriatel SMS  │  │ • World-   │  │
│  │   Time Gross    │  │   Syria and     │  │   Gateway       │  │   Check    │  │
│  │   Settlement)   │  │   Overseas)     │  │ • MTN Syria SMS │  │ • OFAC SDN │  │
│  │ • SYP FX Fixing │  │ • Bemo Saudi    │  │   Gateway       │  │   List     │  │
│  │ • SWIFT MT/MX   │  │   Fransi Bank   │  │ • WhatsApp      │  │ • EU       │  │
│  │ • Regulatory    │  │ • SIIB (Syrian  │  │   Business API  │  │   Sanctions│  │
│  │   Reporting     │  │   International │  │                 │  │ • UNSCR    │  │
│  │                 │  │   Islamic Bank) │  │                 │  │   1267/    │  │
│  │                 │  │ • Arab Bank     │  │                 │  │   1373     │  │
│  │                 │  │   Syria         │  │                 │  │            │  │
│  └─────────────────┘  └─────────────────┘  └─────────────────┘  └────────────┘  │
│                                                                                  │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐  ┌────────────┐  │
│  │  DIASPORA       │  │  BILLER         │  │  CARD SCHEMES   │  │  FX         │  │
│  │  CORRIDORS      │  │  NETWORKS       │  │                 │  │  PROVIDERS │  │
│  │                 │  │                 │  │                 │  │            │  │
│  │ • Europe →      │  │ • Damascus      │  │ • UnionPay      │  │ • Reuters  │  │
│  │   Turkey → Syria│  │   Electricity   │  │   International │  │   FX Data  │  │
│  │ • UAE → Syria   │  │ • Damascus      │  │ • Mastercard    │  │ • Local    │  │
│  │ • US → Syria    │  │   Water Auth.   │  │   (limited via  │  │   FX       │  │
│  │ • Saudi Arabia  │  │ • Syrian Telecom│  │   third party)  │  │   Dealers  │  │
│  │   → Syria       │  │   (STE)         │  │                 │  │ • CBS      │  │
│  │ • Jordan → Syria│  │ • Ministry of   │  │                 │  │   Official │  │
│  │                 │  │   Finance (tax) │  │                 │  │   Rate     │  │
│  └─────────────────┘  └─────────────────┘  └─────────────────┘  └────────────┘  │
│                                                                                  │
│  ┌─────────────────┐  ┌─────────────────┐  ┌──────────────────────────────────┐ │
│  │  IDENTITY / KYC │  │  STORAGE /      │  │  MONITORING / OBSERVABILITY     │ │
│  │  PROVIDERS      │  │  INFRASTRUCTURE │  │                                 │ │
│  │                 │  │                 │  │ • Datadog / Grafana             │ │
│  │ • Syrian Civil  │  │ • AWS eu-       │  │ • PagerDuty Alerts              │ │
│  │   Registry API  │  │   central-1     │  │ • ELK Stack (centralized logs)  │ │
│  │   (ID scan)     │  │   (Frankfurt)   │  │ • Prometheus Metrics            │ │
│  │ • Biometric     │  │ • On-prem       │  │                                 │ │
│  │   match (opt.)  │  │   disaster      │  │                                 │ │
│  │                 │  │   recovery in   │  │                                 │ │
│  │                 │  │   Damascus      │  │                                 │ │
│  └─────────────────┘  └─────────────────┘  └─────────────────────────────────┘ │
└──────────────────────────────────────────────────────────────────────────────────┘
```

## Actor & System Definitions

---

### 1. BEZA WALLET MODULE [System]

| Attribute | Description |
|-----------|-------------|
| **System** | Beza Wallet Module |
| **Description** | Manages digital wallets in SYP and USD, holds, balance checks, transaction history. Supports Sharia-compliant (SIIB) and conventional (BSO, Bemo) wallet accounts. |
| **Responsibilities** | Wallet CRUD, balance management, hold operations (earmark funds), transaction journaling, wallet status (active/frozen/closed), daily/monthly limit enforcement. |
| **Interactions** | → **CFE (Ledger):** Post debit/credit entries (REST + async events) → **FX Engine:** Request conversion rates for cross-currency holds → **Notification Dispatcher:** Emit WalletCreated, WalletFrozen, LimitExceeded events → **Identity Module:** Validate wallet owner identity |
| **Incoming Flows** | ← **User App:** Initiate transfer, check balance ← **Agent Network:** Cash-in/Cash-out ← **Merchant Payments:** Payment authorization ← **Remittance Module:** Payout to beneficiary wallet |
| **Protocols** | gRPC (internal), Kafka events, REST (external-facing) |
| **Data Exchanged** | Wallet ID, owner ID, currency (SYP/USD), balance in sypa/usdc (smallest unit), hold amounts, transaction references, limit counters |
| **SLA** | Balance read: <100ms P99. Wallet creation: <500ms P99. Hold: <300ms P99. 99.99% uptime. |

### 2. BEZA FX ENGINE [System]

| Attribute | Description |
|-----------|-------------|
| **System** | Beza FX Engine |
| **Description** | Real-time foreign exchange engine handling SYP ↔ USD and cross-corridor conversions. Uses CBS official daily fixing rate + market-driven spread. Supports corridor-specific rates for diaspora remittances. |
| **Responsibilities** | Rate generation (mid-rate + spread), quote creation (firm quotes with TTL), conversion execution, corridor rate management (Europe→TRY→SYP, AED→SYP, USD→SYP), FX position tracking, rate caching. |
| **Interactions** | → **CBS:** Fetch official daily SYP fixing rate (SOAP/XML via RTGS gateway) → **Reuters FX Data:** Source USD/TRY, USD/AED, USD/EUR mid-rates → **Local FX Dealers:** Supplemental rate feed for large-value conversions → **Notification:** FXRateUpdated, QuoteExpired events |
| **Incoming Flows** | ← **Wallet Module:** Request rate for hold ← **Remittance Module:** Rate quote for corridor payout ← **Treasury Management:** Position rebalancing |
| **Protocols** | gRPC (rate quotes), Kafka (rate updates stream), REST (CBS integration) |
| **Data Exchanged** | Base currency, quote currency, bid/ask, mid-rate, spread %, rate timestamp, corridor ID, quote TTL, volume tier |
| **SLA** | Rate lookup: <50ms P99. Quote generation: <150ms P99. Rate feed latency from CBS: <60s. 99.99% uptime. |

### 3. BEZA AGENT NETWORK [System]

| Attribute | Description |
|-----------|-------------|
| **System** | Beza Agent Network |
| **Description** | Agent banking network across all 14 Syrian governorates. Agents provide cash-in/cash-out services to end users. Each agent has a float account (BSO settlement account sub-ledger). Commission structure by transaction type and volume. |
| **Responsibilities** | Agent onboarding/kyc, float management, cash-in/cash-out transaction processing, commission calculation and settlement, agent tier management, geo-distribution monitoring, inventory tracking of e-float. |
| **Interactions** | → **Wallet Module:** Credit/debit end-user wallet ←→ **BSO (Bank of Syria and Overseas):** Agent settlement account reconciliation → **CFE:** Post agent commission entries → **Notification:** CashInCompleted, FloatLow, CommissionPaid events → **Treasury:** Agent float position reporting |
| **Incoming Flows** | ← **Agent mobile app:** Cash-in request, cash-out request, float top-up ← **Agent web dashboard:** Transaction history, commission report |
| **Protocols** | REST (agent app), Kafka events, Batch file (BSO settlement reconciliation) |
| **Data Exchanged** | Agent ID, terminal ID, transaction amount, currency, float balance, commission rate tier, location (governorate/district), agent PIN, customer phone/MSISDN, biometric reference |
| **SLA** | Cash-in: <2s end-to-end. Cash-out: <2s end-to-end. Float reconciliation: T+1 batch. 99.95% uptime. |

### 4. CFE — CORE FINANCIAL ENGINE [System]

| Attribute | Description |
|-----------|-------------|
| **System** | CFE (Core Financial Engine / Ledger) |
| **Description** | Double-entry accounting engine — the single source of truth for all financial postings. Every transaction across all modules posts here. Chart of Accounts follows Syrian Accounting Standards (SAS) compatible with CBS regulatory reporting. |
| **Responsibilities** | Journal entry creation (double-entry), account balance maintenance, trial balance generation, GL posting, suspense account management, end-of-day (EOD) processing, regulatory report generation (CBS templates), audit trail. |
| **Interactions** | ← **Wallet Module:** Debit/credit entries ← **FX Engine:** FX conversion P&L entries ← **Agent Network:** Commission and settlement entries ← **Merchant Payments:** Merchant settlement entries ← **Remittance Module:** Remittance flow entries ← **Settlement Engine:** Net position entries |
| **Protocols** | Internal Kafka (commands + events), gRPC (account queries), Batch file (regulatory reports) |
| **Data Exchanged** | Account ID, journal ID, entry type (debit/credit), amount (sypa), currency, GL code (per SAS COA), posting date, value date, reference transaction ID, reconciliation status |
| **SLA** | Entry posting: <200ms P99 (async, guaranteed delivery). Balance query: <100ms P99. EOD run: <15min. 99.999% data integrity (zero tolerance for out-of-balance). |

### 5. BEZA REMITTANCE MODULE [System]

| Attribute | Description |
|-----------|-------------|
| **System** | Beza Remittance Module |
| **Description** | Cross-border remittance engine handling diaspora inflows through defined corridors. Payout options: wallet credit, cash pickup (agent network), bank account transfer (via RTGS). Each corridor has specific FX rate, fee structure, and compliance rules. |
| **Responsibilities** | Order creation (sender initiated), corridor routing, FX quote application, fee calculation (tiered by amount and corridor), compliance screening (sender/beneficiary), AML checks, payout execution, order lifecycle tracking, beneficiary management, corridor analytics. |
| **Interactions** | → **FX Engine:** Request corridor-specific rate → **Compliance Engine:** Screen sender & beneficiary against sanctions lists → **CFE:** Post remittance settlement entries → **Wallet Module:** Credit beneficiary wallet → **Notification:** RemittanceOrderCreated, RemittanceCompleted, RemittanceFailed events |
| **Incoming Flows** | ← **Diaspora sender (Europe/Turkey):** Initiate remittance via web/app ← **Diaspora sender (UAE):** Initiate remittance ← **Diaspora sender (US):** Initiate remittance ← **Corridor partner API:** Inbound order from money transfer operator (e.g., Western Union, MoneyGram corridor integrations) |
| **Corridors** | **Europe→Turkey→Syria:** EUR→TRY→SYP (via Turkish correspondent bank) **UAE→Syria:** AED→USD→SYP (via BSO/RTGS) **US→Syria:** USD→SYP (via correspondent banking) **Saudi Arabia→Syria:** SAR→USD→SYP **Jordan→Syria:** JOD→SYP (direct via Arab Bank Syria) |
| **Protocols** | REST (corridor partner APIs), Kafka (internal events), SWIFT MT103 (bank transfers), RTGS file (local bank payouts) |
| **Data Exchanged** | Order ID, corridor ID, sender details (name, country, ID proof), beneficiary details (name, phone, national ID), amount in source currency, amount in SYP, FX rate at booking, fee breakdown, payout method, remittance purpose code (per CBS classification), source of funds declaration |
| **SLA** | Order creation: <1s. Corridor FX quote: <200ms. Compliance screening: <3s (automated). Payout: <30min for wallet, <24hr for bank. 99.9% uptime. |

### 6. BEZA MERCHANT PAYMENTS [System]

| Attribute | Description |
|-----------|-------------|
| **System** | Beza Merchant Payments |
| **Description** | Merchant payment processing for Syrian retail and e-commerce. Supports POS (via Bemo terminal integration), QR code payments, e-commerce gateway. Merchant settlement in SYP with T+1 or T+2 cycles. MDR (Merchant Discount Rate) tiered by merchant category and volume. |
| **Responsibilities** | Merchant onboarding (KYC + site verification), terminal management, payment authorization, clearing and settlement, MDR fee calculation, dispute management, reconciliation reports, POS integration with Bemo Saudi Fransi terminals. |
| **Interactions** | → **Wallet Module:** Debit payer wallet → **CFE:** Post merchant settlement entries → **Settlement Engine:** Batch settlement to merchant Bemo account → **Bemo Saudi Fransi Bank:** Terminal integration, merchant account settlement → **Notification:** PaymentCompleted, SettlementPaid, DisputeOpened events |
| **Incoming Flows** | ← **Payer user app:** QR scan & pay ← **Merchant POS:** Transaction from Bemo terminal ← **E-commerce plugin:** Checkout via Beza gateway |
| **Protocols** | REST (e-commerce API), ISO 8583 (POS terminal bridge), Kafka (internal events) |
| **Data Exchanged** | Transaction ID, merchant ID, terminal ID, amount, currency (SYP), MDR rate, net settlement amount, card token (if card-on-file), QR payload, authorization code, settlement batch ID |
| **SLA** | Authorization: <800ms P99. Settlement: T+1 (D+1 10:00). 99.95% uptime. |

### 7. BEZA BILLS PAYMENT [System]

| Attribute | Description |
|-----------|-------------|
| **System** | Beza Bills Payment |
| **Description** | Electronic bill presentment and payment for Syrian utility and government bills. Integrates with Damascus Electricity, Damascus Water Authority, Syrian Telecom (STE), Ministry of Finance (tax payments), and other biller networks. |
| **Responsibilities** | Biller integration (file-based or API), bill presentment to user, payment execution, confirmation receipt generation, biller settlement, arrears calculation, late fee handling. |
| **Interactions** | → **Biller Networks:** Submit payment confirmation → **Wallet Module:** Debit user wallet → **CFE:** Post bill payment entries → **Notification:** BillPaid, BillReminder events |
| **Incoming Flows** | ← **User app:** View/pay bill ← **Biller API:** Submit bill file |
| **Protocols** | SFTP (biller file exchange), REST (biller APIs where available), Kafka (internal events) |
| **Data Exchanged** | Bill reference number, biller ID, subscriber ID, amount due, arrears, late fee, payment date, receipt number, bill period |
| **SLA** | Bill presentment: <2s. Payment confirmation: <5s. Biller settlement: T+1. 99.9% uptime. |

---

### 8. BEZA COMPLIANCE ENGINE [System]

| Attribute | Description |
|-----------|-------------|
| **System** | Beza Compliance Engine |
| **Description** | Sanctions screening, transaction monitoring, and AML/CFT case management. Mandatory compliance with CBS AML Law No. 31 of 2010 (as amended), FATF recommendations, and UNSCR sanctions regimes applicable to Syria. |
| **Responsibilities** | Real-time sanctions screening (sender, beneficiary, counterparty), PEP screening, transaction monitoring (velocity, volume, geographic), suspicious activity report (SAR) generation, case management, regulatory reporting to CBS AML/CFT department. |
| **Interactions** | → **World-Check:** Sanctions screening API (name, DOB, nationality, ID) → **OFAC SDN List:** Periodic download + match → **UNSCR 1267/1373:** Sanctions list screening → **Notification:** ScreeningAlert, SARGenerated events |
| **Incoming Flows** | ← **ALL contexts:** Screening requests (wallet creation, remittance, cash-out, merchant onboarding) ← **Transaction Monitoring:** Real-time transaction stream |
| **Protocols** | REST (World-Check API), HTTPS file download (OFAC/UN lists), Kafka (transaction stream), gRPC (internal screening requests) |
| **Data Exchanged** | Full name (Arabic + Latin), date of birth, nationality, ID document type & number, transaction details, screening result (hit/no-hit/match score), hit details, case ID, SAR reference |
| **SLA** | Screening: <3s P99 (automated). Manual review: <24hr for alerts. SAR filing: <48hr of detection. 99.99% uptime. |

---

### 9. BEZA TREASURY MANAGEMENT [System]

| Attribute | Description |
|-----------|-------------|
| **System** | Beza Treasury Management |
| **Description** | Manages liquidity positions across all bank accounts (BSO, Bemo, SIIB, CBS settlement account), agent float reserves, FX exposure, and reserve requirements per CBS regulations. |
| **Responsibilities** | Cash position monitoring (real-time), liquidity forecasting, agent float replenishment optimization, FX exposure management, reserve ratio compliance (CBS reserve requirement), interbank transfer initiation, funding gap analysis. |
| **Interactions** | → **BSO/Bemo/SIIB:** Balance inquiry, transfer initiation → **CFE:** Treasury account entries → **FX Engine:** Hedge position requests → **Agent Network:** Float replenishment triggers |
| **Incoming Flows** | ← **All contexts:** Position data for forecasting ← **CBS:** Reserve requirement updates |
| **Protocols** | REST (bank APIs), Kafka (internal position events), Batch file (bank statements), SWIFT MT940/950 (bank account statements) |
| **Data Exchanged** | Account balance, available balance, reserved amount, projected inflow/outflow, agent float aggregation, FX net position, CBS reserve balance |
| **SLA** | Position view: <30s from bank statement. Forecast: computed hourly. 99.9% uptime. |

---

### 10. BEZA IDENTITY & KYC MODULE [System]

| Attribute | Description |
|-----------|-------------|
| **System** | Beza Identity & KYC Module |
| **Description** | User identity management, KYC collection and verification, device binding, PIN management, biometric authentication (optional). Supports Syrian National ID, passport, and residency documents. |
| **Responsibilities** | User registration, KYC document collection (photo, ID scan), identity verification (automated checks + manual review), device binding (phone number via SMS OTP, device fingerprint), PIN creation & reset, multi-factor authentication, tiered KYC levels (basic, enhanced, full). |
| **Interactions** | → **Syrian Civil Registry:** National ID verification (optional API integration) → **SMS Provider:** OTP delivery (Syriatel/MTN) → **Compliance Engine:** Identity screening → **Notification:** UserRegistered, KYCUpgraded events |
| **Incoming Flows** | ← **User App:** Registration, KYC upload, PIN set/reset ← **Agent App:** Agent-assisted registration ← **All contexts:** Identity verification requests |
| **Protocols** | REST (user app), gRPC (internal verification), Kafka (events) |
| **Data Exchanged** | Full name (Arabic), national ID number, date of birth, phone number (09xx-xxx-xxx), email, device ID, biometric template hash, KYC level (1/2/3), verification status, document images (encrypted), PIN hash (bcrypt) |
| **SLA** | Registration: <3s. KYC upload: <5s. Identity verification: automated (<60s), manual (<24hr). 99.95% uptime. |

---

### 11. BEZA NOTIFICATION DISPATCHER [System]

| Attribute | Description |
|-----------|-------------|
| **System** | Beza Notification Dispatcher |
| **Description** | Multi-channel notification engine. Delivers transactional alerts, OTP codes, marketing communications, and compliance notifications via SMS, email, and WhatsApp Business API. |
| **Responsibilities** | Template management (Arabic + English), channel routing (SMS/Email/WhatsApp), delivery tracking, retry logic with exponential backoff, suppression/preference management, outbox pattern implementation. |
| **Interactions** | → **Syriatel SMPP:** SMS delivery → **MTN Syria SMPP:** SMS delivery ← **All contexts:** Event-driven notification requests |
| **Incoming Flows** | ← **ALL contexts:** Outbox events (async, transactional outbox pattern) |
| **Protocols** | SMPP 3.4 (SMS), SMTP (email), WhatsApp Business API (HTTP), Kafka (internal event consumption) |
| **Data Exchanged** | Recipient phone/email, template ID, rendered content (Arabic or English), channel, priority, correlation ID, delivery status |
| **SLA** | SMS: <10s P99. Email: <60s P99. WhatsApp: <30s P99. 99.9% delivery rate. 99.95% uptime. |

---

### 12. BEZA SETTLEMENT ENGINE [System]

| Attribute | Description |
|-----------|-------------|
| **System** | Beza Settlement Engine |
| **Description** | Batch settlement processing engine. Computes net positions for agents, merchants, and corridor partners. Generates settlement files for bank transfers via RTGS (CBS) and ACH. Handles reconciliation of settlement batches. |
| **Responsibilities** | Net position calculation (aggregate debits/credits), settlement batch creation, settlement file generation (CBS RTGS format, bank-specific formats), reconciliation of settled vs expected amounts, dispute/mismatch handling, settlement calendar (business days, Syrian holiday calendar). |
| **Interactions** | → **CBS RTGS:** Submit settlement instructions (MT103/MT202) → **BSO/Bemo/SIIB:** Submit agent/merchant settlements → **CFE:** Post settlement entries → **Notification:** SettlementBatchCompleted, SettlementFailed events |
| **Incoming Flows** | ← **Agent Network:** Daily float reconciliation ← **Merchant Payments:** Merchant settlement batch ← **Remittance Module:** Corridor payout batch |
| **Protocols** | SWIFT MT (CBS RTGS), Batch file (bank-specific CSV/XML), Kafka (internal events), SFTP (settlement file exchange) |
| **Data Exchanged** | Batch ID, net positions (per counterparty), gross debits/credits, settlement amount, settlement account IBAN, value date, reconciliation status, dispute reference |
| **SLA** | Batch computation: <10min for 50K transactions. Settlement file submission: per CBS cut-off (13:00 Damascus time). Reconciliation: T+1 by 08:00. 99.99% accuracy. |

---

## External System Definitions

### CBS — Central Bank of Syria [Ext Sys]

| Attribute | Description |
|-----------|-------------|
| **System** | Central Bank of Syria (CBS) |
| **Description** | Syria's central bank established in 1956. Regulates all banking activity, manages monetary policy, operates the Real-Time Gross Settlement (RTGS) system (called "Syria-RTGS"), publishes the official SYP exchange rate daily, and enforces AML/CFT regulations per Law No. 31 of 2010. |
| **Responsibilities** | Monetary policy (SYP stabilization), RTGS interbank settlement, commercial bank supervision, FX reserve management, official rate fixing, regulatory reporting collection, payment system oversight. |
| **Protocols** | SWIFT MT/MX (RTGS instructions), SOAP/XML (rate feed), SFTP (regulatory reporting file exchange), ISO 20022 (future adoption roadmap) |
| **Data Exchanged** | Official SYP/USD daily fixing rate, interbank transfer instructions (MT103/MT202), regulatory returns (liquidity, capital adequacy, loan portfolio), CBS circulars and directives |
| **SLA** | RTGS cut-off: 14:00 Damascus time (winter) / 15:00 (summer). Rate publication: daily 12:00 Damascus time. Settlement finality: irrevocable upon RTGS confirmation. |

### BSO — Bank of Syria and Overseas [Ext Sys]

| Attribute | Description |
|-----------|-------------|
| **System** | Bank of Syria and Overseas (BSO) |
| **Description** | Private Syrian bank established in 2004, joint venture with Lebanon's BankMed. BSO is Beza's primary settlement bank for agent float accounts. Maintains agent sub-accounts (one per agent) under Beza's master settlement account. |
| **Responsibilities** | Agent settlement account management, float account reconciliation, cash management services, Beza master account operation. |
| **Protocols** | SFTP (batch file exchange), REST API (balance inquiry), SWIFT MT940 (account statements) |
| **Data Exchanged** | Agent sub-account balances, daily statement files (MT940), settlement transfer confirmations, fee schedules |
| **SLA** | Account statement: available by 07:00 daily. Balance inquiry: real-time (REST). Transfer execution: within RTGS operating hours. |

### Bemo Saudi Fransi Bank [Ext Sys]

| Attribute | Description |
|-----------|-------------|
| **System** | Bemo Saudi Fransi Bank |
| **Description** | Syrian bank established in 2004, partnership between Banque Bemo (Lebanon/Syria) and Banque Saudi Fransi (Saudi Arabia). Provides merchant acquiring services, POS terminal deployment, and merchant account management for Beza's merchant payment network. |
| **Responsibilities** | Merchant account management, POS terminal infrastructure, transaction acquiring and settlement, merchant KYC under CBS regulations. |
| **Protocols** | ISO 8583 (POS terminal communication), SFTP (settlement files), REST API (merchant data) |
| **Data Exchanged** | Merchant settlement reports, terminal transaction logs, chargeback/dispute notifications, MDR fee schedules |
| **SLA** | Settlement file: T+1 06:00. POS terminal uptime: 99.5%. Chargeback processing: within 5 business days. |

### SIIB — Syrian International Islamic Bank [Ext Sys]

| Attribute | Description |
|-----------|-------------|
| **System** | Syrian International Islamic Bank (SIIB) |
| **Description** | Full-fledged Islamic bank operating under Sharia principles (no riba/interest). SIIB hosts Beza's Sharia-compliant wallet reserve accounts. All Sharia-compliant product flows (Mudarabah-based savings, Qard-hasan micro-lending) are settled through SIIB. |
| **Responsibilities** | Sharia-compliant account management, profit/loss distribution on pooled funds, Sharia audit coordination, Beza Sharia reserve account operation. |
| **Protocols** | SFTP (batch files), REST API, SWIFT MT |
| **Data Exchanged** | Sharia account balances, profit distribution statements, transaction files, Sharia compliance certificates |
| **SLA** | Account statements: daily by 07:00. Transfers: within RTGS hours. Sharia audit reporting: quarterly. |

### World-Check [Ext Sys]

| Attribute | Description |
|-----------|-------------|
| **System** | World-Check (Refinitiv / London Stock Exchange Group) |
| **Description** | Global sanctions, PEP, and adverse media screening database. Beza uses World-Check Risk Intelligence for real-time sanctions screening of all onboarding and transaction counterparties. Critical for CBS AML compliance and correspondent banking relationships. |
| **Responsibilities** | Sanctions list management (OFAC SDN, UNSCR, EU, UK HMT), PEP database, adverse media monitoring, risk scoring algorithm. |
| **Protocols** | REST API (JSON/XML), batch file download for local matching |
| **Data Exchanged** | Screening request (name, DOB, nationality, ID), screening result (match score, matched entity details, sanctions regime) |
| **SLA** | API response: <2s P95. Database update frequency: daily. Coverage: 100% of applicable sanctions regimes for Syria. |

### Diaspora Corridors [Ext System / Concept]

| Attribute | Description |
|-----------|-------------|
| **Corridor** | Europe → Turkey → Syria |
| **Description** | Primary corridor serving the Syrian diaspora in Germany, Sweden, Netherlands, France, and UK. Funds flow EUR → TRY (via Turkish correspondent) → SYP (via FX Engine). Payout via wallet credit or cash pickup through agent network. |
| **Volume Estimate** | ~45% of total inbound remittance volume. Average transaction: €200–500. |
| **Fees** | Sliding scale: 3% for <€500, 2.5% for €500–2000, 2% for >€2000. FX spread: 1.5% over CBS mid-rate. |

| **Corridor** | UAE → Syria |
| **Description** | Second-largest corridor serving Syrian workers in UAE. AED → USD → SYP via BSO/REST. Competitive due to worker remittance volumes. |
| **Volume Estimate** | ~25% of total inbound remittance volume. Average transaction: 500–2000 AED. |
| **Fees** | Flat 15 AED + 2% FX spread. |

| **Corridor** | US → Syria |
| **Description** | High-value but low-volume corridor due to OFAC sanctions complexity. USD → SYP via correspondent banking through BSO. Enhanced sanctions screening required. |
| **Volume Estimate** | ~10% of total inbound remittance volume. Average transaction: $500–5000. |
| **Fees** | $5 flat + 2.5% FX spread. Enhanced compliance surcharge: $2. |

| **Corridor** | Saudi Arabia → Syria |
| **Description** | Serving Syrian workers in KSA. SAR → USD → SYP. Competition from informal hawala networks. |
| **Volume Estimate** | ~15% of total inbound remittance volume. Average transaction: 500–3000 SAR. |
| **Fees** | 10 SAR + 2% FX spread. |

| **Corridor** | Jordan → Syria |
| **Description** | Land border corridor. Direct JOD → SYP via Arab Bank Syria. Fastest corridor (T+0 settlement via RTGS). |
| **Volume Estimate** | ~5% of total inbound remittance volume. Average transaction: 50–300 JOD. |
| **Fees** | 1 JOD + 1.5% FX spread. |

### Syriatel & MTN Syria [Ext Sys]

| Attribute | Description |
|-----------|-------------|
| **System** | Syriatel / MTN Syria |
| **Description** | Syria's two mobile network operators. Syriatel (market leader, ~55% share) and MTN Syria (~45% share). Both provide SMPP 3.4 gateways for SMS delivery (OTP, transaction alerts, marketing). Syriatel also offers mobile money overlay (Syriatel Cash). |
| **Protocols** | SMPP 3.4 (SMS), REST API (USSD push) |
| **Data Exchanged** | SMS text (Arabic/English), sender ID, recipient MSISDN, delivery status |
| **SLA** | SMS delivery: <5s P95 for OTP. 98% delivery rate. |

### Syrian Telecom Establishment (STE) [Ext Sys]

| Attribute | Description |
|-----------|-------------|
| **System** | Syrian Telecom Establishment (STE) |
| **Description** | State-owned landline and internet provider. STE bills are paid through Beza Bills Payment module. Integration via SFTP file exchange. |
| **Protocols** | SFTP (CSV file exchange) |
| **Data Exchanged** | Subscriber bills (CSV), payment confirmation files |
| **SLA** | Bill file delivery: monthly by 5th. Payment confirmation: T+1. |

---

## Technology Stack Context

| Layer | Technology | Rationale |
|-------|-----------|-----------|
| **Runtime** | Java 21 (Spring Boot 3.x) | Primary backend for CFE, Wallet, FX, Remittance |
| **Runtime** | Node.js 22 (TypeScript) | Agent network, merchant payments, notification dispatcher |
| **Database** | PostgreSQL 16 (with pg_partman) | All transactional data, partitioned by time |
| **Database** | Redis 7 (ElastiCache) | Rate cache, session store, rate limiter counters |
| **Streaming** | Apache Kafka 3.x (MSK) | Event backbone, transactional outbox |
| **Registry** | Confluent Schema Registry | Avro/JSON Schema for events |
| **Observability** | OpenTelemetry → Datadog | Distributed tracing, metrics, logs |
| **Deployment** | AWS EKS (Kubernetes) | Container orchestration, Frankfurt region |
| **DR** | On-prem disaster recovery in Damascus | Regulatory requirement for financial data residency |
