# Domain Model — Beza Financial OS

## Bounded Contexts Overview

| Bounded Context | Ubiquitous Language | Description | Relationships |
|----------------|-------------------|-------------|---------------|
| **Identity** | User, Customer, KYC, Device, PIN, Tier | User registration, KYC collection, device binding, and authentication across all Beza products | Used by ALL contexts |
| **Wallet** | Wallet, Balance, Transaction, Hold, Limit | Digital wallet management in SYP and USD, holds, and transaction journaling | → Ledger (for postings) |
| **Ledger** | Account, Entry, Journal, TrialBalance, GLCode | Double-entry accounting engine following Syrian Accounting Standards (SAS) | ← Wallet, ← Settlement, ← FX, ← Agent, ← Merchant, ← Remittance |
| **FX** | Rate, Quote, Conversion, Corridor, Spread | Real-time foreign exchange rates and conversions for SYP/USD and corridor pairs | → Ledger, ← Wallet, ← Remittance |
| **Agent** | Agent, Float, CashIn, CashOut, Commission, Terminal | Agent banking network management across 14 Syrian governorates | → Wallet, → Ledger, → Treasury |
| **Merchant** | Merchant, Terminal, Payment, MDR, Settlement | Merchant payment acquiring (POS, QR, e-commerce) and settlement | → Wallet, → Settlement |
| **Settlement** | Batch, NetPosition, Reconciliation, CutOff | Batch netting and settlement across all counterparties | → Ledger, → Bank (CBS/BSO) |
| **Remittance** | Order, Corridor, Beneficiary, Payout, PurposeCode | Cross-border remittance from diaspora corridors | → FX, → Ledger, → Wallet, → Compliance |
| **Compliance** | Case, Screening, Rule, SAR, Alert, PEP | Sanctions screening, AML transaction monitoring, SAR management | ← ALL contexts (screening requests) |
| **Treasury** | Forecast, Position, Float, Reserve, Liquidity | Bank account liquidity management, reserve compliance, FX exposure | → Ledger, ← ALL contexts (position data) |
| **Notification** | Template, Channel, Event, Outbox, Delivery | Multi-channel notification dispatch (SMS/Email/WhatsApp) | ← ALL contexts (async) |

### Bounded Context Relationship Diagram

```
┌──────────┐     ┌──────────┐     ┌──────────┐
│ Identity │────▶│  Wallet  │────▶│  Ledger  │
└──────────┘     └────┬─────┘     └────┬─────┘
                      │                │
               ┌──────▼──────┐  ┌──────▼──────┐
               │     FX      │─▶│  Settlement │─▶ Bank (CBS/BSO)
               └──────┬──────┘  └──────┬──────┘
                      │                │
          ┌───────────▼───┐  ┌────────▼────────┐
          │   Remittance  │  │    Merchant      │
          └───────────┬───┘  └────────▲────────┘
                      │               │
               ┌──────▼──────┐  ┌─────┴────────┐
               │   Agent     │──│  Treasury     │
               └─────────────┘  └──────────────┘

┌──────────────┐     ┌─────────────┐     ┌──────────────┐
│  Compliance  │◀────│ ALL CONTEXTS│────▶│ Notification │
│  (screening) │     │  (events)   │     │ (dispatcher) │
└──────────────┘     └─────────────┘     └──────────────┘
```

---

## Aggregate Roots

### Identity Context

#### User Aggregate

| Attribute | Value |
|-----------|-------|
| **Root** | User (user_id) |
| **Description** | Core identity aggregate. Represents any natural person registered on Beza platform. Supports both Arabic and Latin name fields as required by Syrian Civil Registry. |

**Attributes:**
- `user_id`: UUID (v7)
- `full_name_ar`: String (Arabic), e.g. "أحمد محمد الخالد"
- `full_name_en`: String (Latin transliteration), e.g. "Ahmad Mohammad Al-Khaled"
- `national_id`: String (unique, Syrian National ID format: 10 digits)
- `national_id_type`: Enum (NATIONAL_ID \| PASSPORT \| RESIDENCY)
- `date_of_birth`: Date
- `gender`: Enum (MALE \| FEMALE)
- `phone`: String (MSISDN, format: 9639XXXXXXXX)
- `email`: String (optional)
- `address_governorate`: Enum (14 governorates: Damascus, Aleppo, Homs, Hama, Latakia, Tartous, DeirEzZor, Hasakeh, Raqqa, Idlib, Daraa, Suwayda, Quneitra, Rural Damascus)
- `address_city`: String
- `kyc_level`: Enum (NONE \| BASIC \| ENHANCED \| FULL)
- `kyc_status`: Enum (PENDING \| VERIFIED \| REJECTED \| EXPIRED)
- `status`: Enum (ACTIVE \| SUSPENDED \| CLOSED)
- `agent_assisted`: Boolean (true if registered via agent)
- `agent_id`: UUID (nullable, references Agent)
- `device_ids`: UUID[] (registered devices)
- `pin_hash`: String (bcrypt, PBKDF2)
- `failed_pin_attempts`: Integer
- `pin_locked_until`: Timestamp (nullable)
- `mfa_enabled`: Boolean
- `mfa_method`: Enum (SMS \| TOTP \| NONE)
- `created_at`: Timestamp
- `updated_at`: Timestamp
- `last_login_at`: Timestamp
- `kyc_expires_at`: Timestamp

**Invariants:**
- `full_name_ar` must contain at least 3 segments (first, father, family)
- `national_id` must pass Syrian National ID checksum validation (mod 11)
- Single `phone` cannot be registered more than once across active users
- `kyc_level = FULL` → all KYC documents must be verified
- `pin_hash` must be set before any financial transaction
- `failed_pin_attempts >= 5` → `status = SUSPENDED`, `pin_locked_until = now() + 24h`
- `kyc_level` can only increase (NONE → BASIC → ENHANCED → FULL), never decrease

**Domain Events:**
- `UserRegistered` — emitted on successful registration
- `KYCLevelUpgraded` — emitted when KYC level changes (contains oldLevel, newLevel)
- `UserSuspended` — emitted when user is suspended (exceeds PIN attempts / compliance hit)
- `UserReactivated` — emitted when suspension is lifted
- `DeviceBound` — emitted when new device is registered
- `DeviceUnbound` — emitted when device is removed
- `UserClosed` — emitted when user account is permanent closed

#### KYCDocument Aggregate

| Attribute | Value |
|-----------|-------|
| **Root** | KYCDocument (document_id) |

**Attributes:**
- `document_id`: UUID
- `user_id`: UUID (references User)
- `document_type`: Enum (NATIONAL_ID_FRONT \| NATIONAL_ID_BACK \| PASSPORT \| SELFIE \| PROOF_OF_ADDRESS \| RESIDENCY_PERMIT)
- `file_reference`: String (S3 key, encrypted at rest)
- `file_hash`: String (SHA-256 for integrity verification)
- `verification_status`: Enum (PENDING \| VERIFIED \| REJECTED \| EXPIRED)
- `verification_method`: Enum (MANUAL \| AUTOMATED_OCR \| THIRD_PARTY)
- `verified_by`: UUID (nullable, compliance officer user_id)
- `rejection_reason`: String (nullable)
- `submitted_at`: Timestamp
- `verified_at`: Timestamp (nullable)
- `expires_at`: Timestamp

**Invariants:**
- At least one government-issued ID document required for `kyc_level >= BASIC`
- Document images must be stored encrypted (AES-256) at rest
- `file_hash` must match S3 object on every retrieval
- Selfie required for `kyc_level = FULL`

**Domain Events:**
- `KYCDocumentSubmitted`
- `KYCDocumentVerified`
- `KYCDocumentRejected`
- `KYCDocumentExpired`

---

### Wallet Context

#### Wallet Aggregate

| Attribute | Value |
|-----------|-------|
| **Root** | Wallet (wallet_id) |
| **Description** | Digital wallet holding SYP or USD balance. Users may hold up to 2 wallets (one SYP, one USD). USD wallet is required for diaspora remittances and FX holds. |

**Attributes:**
- `wallet_id`: UUID (prefixed `wlt_`)
- `owner_id`: UUID (references User)
- `currency`: Enum (SYP \| USD)
- `balance`: BigInt (sypa — smallest SYP unit = 0.01 SYP; usdc — smallest USD unit = 0.01 USD)
- `available_balance`: BigInt (balance minus total active holds)
- `total_holds`: BigInt
- `status`: Enum (ACTIVE \| FROZEN \| CLOSED)
- `kyc_required_level`: Enum (BASIC \| ENHANCED \| FULL) — minimum KYC level to operate
- `daily_limit`: BigInt
- `daily_limit_used`: BigInt
- `monthly_limit`: BigInt
- `monthly_limit_used`: BigInt
- `daily_limit_reset_at`: Timestamp
- `monthly_limit_reset_at`: Timestamp
- `created_at`: Timestamp
- `opened_at`: Timestamp
- `closed_at`: Timestamp (nullable)

**Invariants:**
- `available_balance = balance - total_holds ≥ 0` (no overdraft)
- `total_holds = SUM(hold.amount WHERE hold.status = ACTIVE)`
- `daily_limit_used = SUM(ABS(tx.amount) WHERE tx.created_at >= daily_limit_reset_at)`
- `daily_limit_used ≤ daily_limit` — enforced before any debit
- `monthly_limit_used ≤ monthly_limit` — enforced before any debit
- `status = FROZEN` → no debits allowed, credits permitted
- `status = CLOSED` → no operations allowed, balance must be zero
- One wallet per currency per user (unique constraint on owner_id + currency)
- If wallet is for Sharia-compliant product, `currency = SYP` only and a tag `sharia: true` is set

**Domain Events:**
- `WalletCreated`
- `WalletFrozen` — includes reason and frozen_by
- `WalletActivated`
- `WalletClosed`
- `DailyLimitExceeded`
- `MonthlyLimitExceeded`
- `BalanceChanged` — includes previous_balance, new_balance, delta

#### Transaction Aggregate

| Attribute | Value |
|-----------|-------|
| **Root** | Transaction (transaction_id) |
| **Description** | Represents a single financial operation on a wallet. Types: transfer, hold-release, cash-in, cash-out, bill-pay, remittance-payout, merchant-payment. |

**Attributes:**
- `transaction_id`: UUID (prefixed `txn_`)
- `wallet_id`: UUID (references Wallet)
- `transaction_type`: Enum (TRANSFER \| CASH_IN \| CASH_OUT \| BILL_PAY \| REMITTANCE_PAYOUT \| MERCHANT_PAYMENT \| FX_CONVERSION \| HOLD \| RELEASE \| FEE \| COMMISSION \| ADJUSTMENT)
- `amount`: BigInt (always positive, direction determined by entry_type)
- `entry_type`: Enum (DEBIT \| CREDIT)
- `currency`: Enum (SYP \| USD)
- `balance_before`: BigInt
- `balance_after`: BigInt
- `status`: Enum (PENDING \| COMPLETED \| FAILED \| REVERSED)
- `reference_type`: String (e.g., "remittance_order", "bill_reference", "merchant_payment")
- `reference_id`: String (e.g., order ID, bill ID)
- `hold_id`: UUID (nullable, references Hold)
- `correlation_id`: String (distributed tracing)
- `description_ar`: String (Arabic, e.g. "تحويل إلى محفظة أخرى")
- `description_en`: String (English)
- `channel`: Enum (USER_APP \| AGENT_APP \| POS \| ECOMMERCE \| API \| SYSTEM)
- `device_id`: UUID (nullable)
- `ip_address`: String (nullable)
- `failed_reason`: String (nullable)
- `reversed_at`: Timestamp (nullable)
- `reversed_by`: UUID (nullable)
- `created_at`: Timestamp
- `completed_at`: Timestamp

**Invariants:**
- `balance_after = balance_before + amount` for CREDIT, `balance_after = balance_before - amount` for DEBIT
- `entry_type = DEBIT` → `balance_before - amount >= 0` (sufficient balance)
- A HOLD transaction must reference a valid, active Hold
- A RELEASE transaction resolves a Hold (sets hold.status = RELEASED)
- Only COMPLETED transactions can be REVERSED
- `reference_type + reference_id` must be unique for idempotency within same day

**Domain Events:**
- `TransactionInitiated`
- `TransactionCompleted`
- `TransactionFailed`
- `TransactionReversed`

#### Hold Aggregate

| Attribute | Value |
|-----------|-------|
| **Root** | Hold (hold_id) |
| **Description** | Earmarks funds in a wallet for a pending operation (e.g., pending FX conversion, pending merchant payment authorization). Hold expires after TTL if not released or captured. |

**Attributes:**
- `hold_id`: UUID (prefixed `hld_`)
- `wallet_id`: UUID (references Wallet)
- `amount`: BigInt
- `currency`: Enum (SYP \| USD)
- `status`: Enum (ACTIVE \| CAPTURED \| RELEASED \| EXPIRED)
- `reason`: String (e.g., "merchant_auth", "fx_quote", "remittance")
- `reference_type`: String
- `reference_id`: String
- `ttl_seconds`: Integer (default 300 for merchant auth, 60 for FX quote)
- `expires_at`: Timestamp
- `captured_at`: Timestamp (nullable)
- `released_at`: Timestamp (nullable)
- `created_at`: Timestamp

**Invariants:**
- `SUM(hold.amount WHERE status = ACTIVE) ≤ wallet.available_balance`
- Hold cannot exceed remaining daily/monthly limit
- `status = ACTIVE AND now() > expires_at` → auto-release (system job)
- `status = CAPTURED` → hold amount deducted from wallet balance
- `status = RELEASED` → hold amount returned to available_balance

**Domain Events:**
- `HoldCreated`
- `HoldCaptured`
- `HoldReleased`
- `HoldExpired`

---

### Ledger Context

#### Account Aggregate

| Attribute | Value |
|-----------|-------|
| **Root** | Account (account_id) |
| **Description** | Single GL account in the Chart of Accounts (COA) following Syrian Accounting Standards (SAS). Accounts are organized hierarchically: Asset, Liability, Equity, Income, Expense. |

**Attributes:**
- `account_id`: UUID (prefixed `acc_`)
- `gl_code`: String (SAS-compliant, hierarchical: 1xxx = Assets, 2xxx = Liabilities, etc.)
- `gl_code_full`: String (full path, e.g. "1.1.2.3 — Syrian Pound Current Accounts")
- `name_ar`: String ("الحسابات الجارية بالليرة السورية")
- `name_en`: String ("SYP Current Accounts")
- `account_type`: Enum (ASSET \| LIABILITY \| EQUITY \| INCOME \| EXPENSE \| CONTINGENT)
- `account_class`: Enum (BALANCE_SHEET \| PNL \| MEMO)
- `currency`: Enum (SYP \| USD \| MULTI)
- `is_control_account`: Boolean (parent account that aggregates children)
- `parent_account_id`: UUID (nullable, self-referential)
- `status`: Enum (ACTIVE \| FROZEN \| CLOSED)
- `normal_balance`: Enum (DEBIT \| CREDIT)
- `balance`: BigInt (current calculated balance)
- `reconciled_balance`: BigInt (balance at last reconciliation)
- `last_reconciled_at`: Timestamp (nullable)
- `created_at`: Timestamp

**Invariants:**
- `normal_balance = DEBIT` for ASSET and EXPENSE, `CREDIT` for LIABILITY, EQUITY, INCOME
- Control accounts balance = SUM(child account balances)
- `balance` must always equal SUM of all ledger entries for this account
- `status = CLOSED` → no new entries posted, balance must be zero

**Domain Events:**
- `AccountOpened`
- `AccountFrozen`
- `AccountClosed`
- `BalanceReconciled`

#### LedgerEntry Aggregate

| Attribute | Value |
|-----------|-------|
| **Root** | LedgerEntry (entry_id) |
| **Description** | Single double-entry posting. Every financial transaction produces exactly two entries (one debit, one credit). Entries are immutable once posted. |

**Attributes:**
- `entry_id`: UUID (monotonic, time-sorted)
- `journal_id`: UUID (references Journal)
- `account_id`: UUID (references Account)
- `entry_type`: Enum (DEBIT \| CREDIT)
- `amount`: BigInt
- `currency`: Enum (SYP \| USD)
- `exchange_rate_to_syp`: Decimal (18,6) (for USD entries, rate at time of posting)
- `balance_before`: BigInt
- `balance_after`: BigInt
- `description`: String (nullable)
- `reference_type`: String (e.g., "wallet_txn", "remittance", "settlement")
- `reference_id`: String
- `posted_at`: Timestamp
- `value_date`: Date (can differ from posted_at for backdated entries)
- `source_system`: String (which internal system posted this)

**Invariants:**
- Within a Journal, sum(DEBIT amounts) = sum(CREDIT amounts) (double-entry principle)
- `balance_after = balance_before + amount` for DEBIT, `balance_after = balance_before - amount` for CREDIT
- Entries are immutable — never modified or deleted
- `value_date` cannot be in the future
- Entries with `value_date < current_value_date` require back-value posting approval

**Domain Events:**
- `LedgerEntryPosted`
- `LedgerReversalPosted` (when correcting a prior entry, posts new reversing entries)

#### Journal Aggregate

| Attribute | Value |
|-----------|-------|
| **Root** | Journal (journal_id) |
| **Description** | Atomic batch of ledger entries representing one business transaction. Each journal is a balanced double-entry unit. |

**Attributes:**
- `journal_id`: UUID
- `journal_type`: Enum (WALLET \| FX \| SETTLEMENT \| COMMISSION \| FEE \| ADJUSTMENT \| REVERSAL \| EOD)
- `status`: Enum (DRAFT \| POSTED \| REVERSED)
- `entry_count`: Integer (must be ≥ 2)
- `total_debit`: BigInt
- `total_credit`: BigInt
- `source_transaction_id`: UUID (references originating transaction)
- `posted_by`: String (system or user who triggered the posting)
- `posted_at`: Timestamp
- `reversed_journal_id`: UUID (nullable, if this journal reverses another)
- `reversal_reason`: String (nullable)

**Invariants:**
- `total_debit = total_credit` (zero imbalance tolerance)
- `status = POSTED` → all entries immutable
- `status = REVERSED` → only allowed if original journal is POSTED
- Reversal posts new entries with opposite DEBIT/CREDIT

**Domain Events:**
- `JournalPosted`
- `JournalReversed`

---

### FX Context

#### FXRate Aggregate

| Attribute | Value |
|-----------|-------|
| **Root** | FXRate (rate_id) |
| **Description** | Exchange rate for a currency pair. CBS official mid-rate is the base reference for all SYP pairs. Market spread is added for customer-facing rates. |

**Attributes:**
- `rate_id`: UUID
- `base_currency`: String (ISO 4217, e.g. "USD", "EUR", "TRY", "AED")
- `quote_currency`: String (ISO 4217, always "SYP" for customer rates)
- `mid_rate`: Decimal (18,6) — CB mid-rate
- `bid_rate`: Decimal (18,6) — mid_rate × (1 - spread)
- `ask_rate`: Decimal (18,6) — mid_rate × (1 + spread)
- `spread_pct`: Decimal (5,2) — e.g. 1.50 for 1.5%
- `rate_type`: Enum (CBS_OFFICIAL \| MARKET \| CORRIDOR \| INTERNAL)
- `source`: String (e.g. "CBS", "Reuters", "Treasury")
- `valid_from`: Timestamp
- `valid_to`: Timestamp (nullable, rate expiry for firm quotes)
- `published_at`: Timestamp

**Invariants:**
- `bid_rate ≤ mid_rate ≤ ask_rate`
- `spread_pct = ((ask_rate - bid_rate) / mid_rate) × 100`
- CBS rates have no spread (bid = mid = ask)
- For a given base_currency + rate_type, `valid_to > now()` ensures current rate is active

**Domain Events:**
- `FXRateUpdated` — emitted when CBS publishes new daily rate
- `FXRateSpreadChanged` — emitted when Treasury adjusts spreads

#### FXQuote Aggregate

| Attribute | Value |
|-----------|-------|
| **Root** | FXQuote (quote_id) |
| **Description** | Firm quote for a specific conversion request. Locked rate valid for a short TTL (default 60s). Used by Remittance and Wallet modules to secure conversion rates before execution. |

**Attributes:**
- `quote_id`: UUID (prefixed `qte_`)
- `requestor_id`: UUID (references requesting context: Wallet, Remittance)
- `requestor_type`: Enum (WALLET \| REMITTANCE \| TREASURY)
- `base_currency`: String
- `quote_currency`: String
- `amount_in_base`: BigInt
- `amount_in_quote`: BigInt
- `rate_used`: Decimal (18,6)
- `rate_type`: Enum (CBS_OFFICIAL \| CORRIDOR)
- `corridor_id`: UUID (nullable, if quote is for a specific remittance corridor)
- `status`: Enum (ACTIVE \| ACCEPTED \| EXPIRED \| CANCELLED)
- `ttl_seconds`: Integer (default 60)
- `expires_at`: Timestamp
- `accepted_at`: Timestamp (nullable)
- `expired_at`: Timestamp (nullable)
- `created_at`: Timestamp

**Invariants:**
- `amount_in_quote = amount_in_base × rate_used` (rounded to smallest unit)
- `status = ACTIVE` → `now() < expires_at`
- Quote once ACCEPTED or EXPIRED cannot change status
- Maximum one active quote per requestor_id + requestor_type at a time

**Domain Events:**
- `FXQuoteCreated`
- `FXQuoteAccepted`
- `FXQuoteExpired`

#### FXConversion Aggregate

| Attribute | Value |
|-----------|-------|
| **Root** | FXConversion (conversion_id) |
| **Description** | Executed currency conversion. Consumes a quote to perform the actual conversion. Results in ledger entries for the FX position. |

**Attributes:**
- `conversion_id`: UUID (prefixed `fx_`)
- `quote_id`: UUID (references FXQuote)
- `from_wallet_id`: UUID (nullable, source wallet)
- `to_wallet_id`: UUID (nullable, destination wallet)
- `from_currency`: String
- `to_currency`: String
- `from_amount`: BigInt
- `to_amount`: BigInt
- `rate_applied`: Decimal (18,6)
- `fee_amount`: BigInt (FX fee)
- `fee_currency`: Enum (SYP \| USD)
- `status`: Enum (PENDING \| COMPLETED \| FAILED \| REVERSED)
- `completed_at`: Timestamp
- `failed_reason`: String (nullable)

**Invariants:**
- `to_amount = from_amount × rate_applied - fee_amount` (if fee in quote currency)
- Conversion always references exactly one accepted quote
- `status = COMPLETED` → ledger entries have been posted

**Domain Events:**
- `FXConversionCompleted`
- `FXConversionFailed`

---

### Agent Context

#### Agent Aggregate

| Attribute | Value |
|-----------|-------|
| **Root** | Agent (agent_id) |
| **Description** | An individual or retail outlet authorized to perform cash-in/cash-out services. Each agent has a float account (sub-ledger of Beza's BSO master account) and operates in a specific governorate. |

**Attributes:**
- `agent_id`: UUID (prefixed `agt_`)
- `owner_id`: UUID (references User — the agent's personal user account)
- `business_name_ar`: String ("مؤسسة الأقصى للخدمات المالية")
- `business_name_en`: String ("Al-Aqsa Financial Services")
- `business_type`: Enum (RETAIL \| PHARMACY \| GROCERY \| TELECOM_SHOP \| EXCHANGE_BUREAU \| OTHER)
- `governorate`: Enum (14 governorates)
- `district`: String
- `location_lat`: Decimal (10,7)
- `location_lng`: Decimal (10,7)
- `float_account_id`: String (BSO sub-account reference)
- `float_balance`: BigInt (SYP)
- `float_minimum`: BigInt (minimum required float)
- `float_maximum`: BigInt (maximum allowed float, based on tier)
- `commission_tier`: Integer (1-5, determines commission rates)
- `agent_tier`: Enum (BASIC \| STANDARD \| PREMIUM \| CORPORATE)
- `status`: Enum (PENDING \| ACTIVE \| SUSPENDED \| TERMINATED)
- `liquidity_provider`: Boolean (can perform float top-ups for other agents)
- `operating_hours_start`: Time (Damascus time)
- `operating_hours_end`: Time (Damascus time)
- `max_cash_in_per_txn`: BigInt
- `max_cash_out_per_txn`: BigInt
- `daily_txn_limit`: BigInt
- `is_trained`: Boolean (has completed agent training)
- `trained_at`: Timestamp (nullable)
- `created_at`: Timestamp
- `activated_at`: Timestamp (nullable)

**Invariants:**
- `float_balance ≥ float_minimum` — agent cannot operate below minimum float
- `float_balance ≤ float_maximum` — enforced on float top-up
- `status = ACTIVE` → KYC complete, training complete, float_minimum met
- `status = SUSPENDED` → no transactions allowed
- Agent can only serve customers in their registered governorate + adjacent (radius policy configurable)

**Domain Events:**
- `AgentRegistered`
- `AgentActivated`
- `AgentSuspended`
- `AgentTerminated`
- `AgentFloatLow` — emitted when float_balance < float_minimum + threshold
- `AgentFloatToppedUp`
- `AgentTierChanged`

#### CashTransaction Aggregate

| Attribute | Value |
|-----------|-------|
| **Root** | CashTransaction (cash_txn_id) |
| **Description** | Record of a cash-in or cash-out operation at an agent location. This is the point-of-presence transaction bridging physical cash and digital wallet. |

**Attributes:**
- `cash_txn_id`: UUID (prefixed `cash_`)
- `agent_id`: UUID (references Agent)
- `customer_user_id`: UUID (references User — the end customer)
- `transaction_type`: Enum (CASH_IN \| CASH_OUT)
- `amount`: BigInt
- `currency`: Enum (SYP \| USD)
- `fee_amount`: BigInt (agent service fee, if any)
- `agent_commission_amount`: BigInt
- `total_cash_received`: BigInt (cash_in: amount + fee; cash_out: amount - fee)
- `wallet_id`: UUID (references Wallet — customer's wallet credited/debited)
- `customer_balance_before`: BigInt
- `customer_balance_after`: BigInt
- `agent_float_before`: BigInt
- `agent_float_after`: BigInt
- `otp_verified`: Boolean
- `customer_phone`: String (MSISDN)
- `status`: Enum (PENDING \| COMPLETED \| FAILED \| REVERSED)
- `device_id`: UUID (agent terminal device)
- `location_lat`: Decimal (10,7)
- `location_lng`: Decimal (10,7)
- `completed_at`: Timestamp
- `created_at`: Timestamp

**Invariants:**
- CASH_IN: `wallet.balance` increases by amount; `agent.float_balance` decreases by amount
- CASH_OUT: `wallet.balance` decreases by amount; `agent.float_balance` increases by amount
- CASH_IN: `agent.float_balance_before - amount ≥ float_minimum`
- CASH_OUT: `wallet.balance_before - amount ≥ 0`
- `amount ≤ agent.max_cash_out_per_txn` for CASH_OUT, `≤ agent.max_cash_in_per_txn` for CASH_IN
- OTP must be verified for CASH_OUT above daily no-OTP threshold
- `agent.status = ACTIVE` required

**Domain Events:**
- `CashTransactionInitiated`
- `CashTransactionCompleted`
- `CashTransactionFailed`
- `CashTransactionReversed`

#### AgentCommission Aggregate

| Attribute | Value |
|-----------|-------|
| **Root** | AgentCommission (commission_id) |

**Attributes:**
- `commission_id`: UUID
- `agent_id`: UUID (references Agent)
- `period_start`: Date
- `period_end`: Date
- `total_cash_in_volume`: BigInt
- `total_cash_out_volume`: BigInt
- `total_commission_cash_in`: BigInt
- `total_commission_cash_out`: BigInt
- `bonus_amount`: BigInt
- `total_commission`: BigInt
- `status`: Enum (CALCULATED \| APPROVED \| PAID)
- `commission_tier_at_calc`: Integer
- `settled_at`: Timestamp (nullable)
- `created_at`: Timestamp

**Domain Events:**
- `CommissionCalculated`
- `CommissionPaid`

---

### Merchant Context

#### Merchant Aggregate

| Attribute | Value |
|-----------|-------|
| **Root** | Merchant (merchant_id) |
| **Description** | Business entity registered to accept payments through Beza. Settlement occurs to their Bemo Saudi Fransi merchant account. Supports multiple terminals and locations. |

**Attributes:**
- `merchant_id`: UUID (prefixed `mch_`)
- `owner_id`: UUID (references User — the merchant's personal account)
- `business_name_ar`: String
- `business_name_en`: String
- `business_registration_number`: String (Syrian Commercial Registry)
- `tax_id`: String (Syrian Tax ID)
- `merchant_category_code`: String (MCC, e.g. "5411" for grocery)
- `merchant_type`: Enum (PHYSICAL_STORE \| ECOMMERCE \| BOTH)
- `settlement_account_iban`: String (Bemo Saudi Fransi IBAN)
- `settlement_cycle`: Enum (T+1 \| T+2 \| WEEKLY)
- `mdr_rate`: Decimal (5,2) — Merchant Discount Rate as percentage (e.g. 2.50)
- `mdr_fixed_fee`: BigInt (fixed per-txn fee in SYP)
- `volume_tier`: Integer (1-5)
- `status`: Enum (PENDING \| ACTIVE \| SUSPENDED \| TERMINATED)
- `is_online`: Boolean (can accept e-commerce payments)
- `webhook_url`: String (nullable, for payment notifications)
- `api_key_hash`: String (nullable, for e-commerce API auth)
- `created_at`: Timestamp

**Invariants:**
- `settlement_account_iban` must be a valid Syrian IBAN (SA + 22 chars)
- Active merchant must have at least one registered Terminal
- `status = ACTIVE` → KYC complete, site verified for physical stores
- MDR cannot exceed CBS-regulated maximum (currently 3.5% for debit, 5% for credit-equivalent)

**Domain Events:**
- `MerchantRegistered`
- `MerchantActivated`
- `MerchantSuspended`
- `MDRRateChanged`

#### Terminal Aggregate

| Attribute | Value |
|-----------|-------|
| **Root** | Terminal (terminal_id) |
| **Description** | Physical or virtual point-of-sale endpoint. For physical stores, linked to a Bemo Saudi Fransi POS terminal. For e-commerce, a virtual terminal identified by API key. |

**Attributes:**
- `terminal_id`: UUID (prefixed `trm_`)
- `merchant_id`: UUID (references Merchant)
- `terminal_type`: Enum (POS \| QR_STATIC \| QR_DYNAMIC \| VIRTUAL \| SOFT_POS)
- `bemo_terminal_id`: String (nullable, Bemo's terminal ID for POS units)
- `serial_number`: String (nullable, for POS hardware)
- `location`: String (store address for physical terminals)
- `status`: Enum (ACTIVE \| INACTIVE \| DECOMMISSIONED)
- `qr_code`: String (nullable, base64 PNG for QR terminals)
- `last_heartbeat_at`: Timestamp (nullable)
- `created_at`: Timestamp

**Domain Events:**
- `TerminalRegistered`
- `TerminalActivated`
- `TerminalDecommissioned`

#### MerchantPayment Aggregate

| Attribute | Value |
|-----------|-------|
| **Root** | MerchantPayment (payment_id) |
| **Description** | A payment from a customer to a merchant. Includes authorization, clearing, and eventual settlement. |

**Attributes:**
- `payment_id`: UUID (prefixed `pay_`)
- `merchant_id`: UUID (references Merchant)
- `terminal_id`: UUID (references Terminal)
- `customer_user_id`: UUID (references User — payer)
- `customer_wallet_id`: UUID (references Wallet — payer's wallet)
- `amount`: BigInt (total transaction amount including fees)
- `currency`: Enum (SYP)
- `mdr_amount`: BigInt (MDR fee computed as rate × amount)
- `mdr_fixed_fee`: BigInt (fixed per-txn fee)
- `net_settlement_amount`: BigInt (amount - mdr_amount - mdr_fixed_fee)
- `payment_method`: Enum (WALLET \| CARD \| QR)
- `status`: Enum (AUTHORIZED \| CAPTURED \| SETTLED \| REFUNDED \| FAILED)
- `authorization_code`: String (nullable)
- `refund_reason`: String (nullable)
- `refunded_at`: Timestamp (nullable)
- `settlement_batch_id`: UUID (nullable, references Settlement batch)
- `created_at`: Timestamp
- `settled_at`: Timestamp (nullable)

**Invariants:**
- `net_settlement_amount = amount - mdr_amount - mdr_fixed_fee`
- `mdr_amount = amount × mdr_rate / 100`
- `status = SETTLED` → merchant's settlement account has been credited (within settlement cycle)
- Only CAPTURED payments can be REFUNDED
- Full refund only (partial refunds not supported in v1)

**Domain Events:**
- `PaymentAuthorized`
- `PaymentCaptured`
- `PaymentSettled`
- `PaymentRefunded`
- `PaymentFailed`

---

### Settlement Context

#### SettlementBatch Aggregate

| Attribute | Value |
|-----------|-------|
| **Root** | SettlementBatch (batch_id) |
| **Description** | Aggregation of net positions across all counterparties (agents, merchants, corridor partners) for a given settlement date. Used to generate RTGS instructions for CBS and bank-specific settlement files. |

**Attributes:**
- `batch_id`: UUID (prefixed `stl_`)
- `batch_type`: Enum (AGENT \| MERCHANT \| CORRIDOR \| INTERNAL)
- `settlement_date`: Date
- `cutoff_time`: Timestamp (batch cut-off time)
- `total_counterparties`: Integer
- `total_debit_amount`: BigInt (total owed by Beza)
- `total_credit_amount`: BigInt (total owed to Beza)
- `net_position`: BigInt (difference — positive means Beza owes)
- `currency`: Enum (SYP \| USD)
- `status`: Enum (CALCULATED \| APPROVED \| SUBMITTED \| SETTLED \| FAILED \| RECONCILED)
- `settlement_method`: Enum (RTGS \| ACH \| INTERNAL_TRANSFER)
- `settlement_reference`: String (nullable, CBS RTGS reference)
- `approver_id`: UUID (nullable, who approved the batch)
- `approved_at`: Timestamp (nullable)
- `submitted_at`: Timestamp (nullable)
- `settled_at`: Timestamp (nullable)
- `failure_reason`: String (nullable)
- `created_at`: Timestamp

**Invariants:**
- `net_position = total_debit_amount - total_credit_amount` (if positive: Beza pays counterparties)
- EACH counterparty's net position is calculated individually before aggregation
- `status = SUBMITTED` → batch file has been sent to CBS/bank
- `status = SETTLED` → CBS RTGS confirmation received
- T+1 settlement for agent and merchant batches
- T+2 for cross-border corridor batches

**Domain Events:**
- `SettlementBatchCalculated`
- `SettlementBatchApproved`
- `SettlementBatchSubmitted`
- `SettlementBatchSettled`
- `SettlementBatchFailed`
- `SettlementBatchReconciled`

#### NetPosition Aggregate

| Attribute | Value |
|-----------|-------|
| **Root** | NetPosition (position_id) |
| **Description** | Net position for a single counterparty within a settlement batch. |

**Attributes:**
- `position_id`: UUID
- `batch_id`: UUID (references SettlementBatch)
- `counterparty_type`: Enum (AGENT \| MERCHANT \| CORRIDOR_PARTNER \| BILLER)
- `counterparty_id`: UUID
- `gross_debits`: BigInt
- `gross_credits`: BigInt
- `net_amount`: BigInt
- `debit_or_credit`: Enum (DEBIT \| CREDIT) — direction from Beza's perspective
- `transactions_count`: Integer
- `settlement_account`: String (counterparty's bank account IBAN)
- `reconciled`: Boolean (matched against counterparty's records)
- `reconciled_at`: Timestamp (nullable)

**Domain Events:**
- `PositionCalculated`
- `PositionReconciled`
- `PositionDisputed`

---

### Remittance Context

#### RemittanceOrder Aggregate

| Attribute | Value |
|-----------|-------|
| **Root** | RemittanceOrder (order_id) |
| **Description** | Cross-border remittance request from a diaspora sender to a Syrian beneficiary. Each order follows a defined corridor with specific FX rates, fees, and compliance checks. |

**Attributes:**
- `order_id`: UUID (prefixed `rem_`)
- `corridor_id`: UUID (references Corridor)
- `sender_user_id`: UUID (references User — diaspora sender)
- `sender_country`: String (ISO country code, e.g. "DE", "AE", "US")
- `sender_full_name`: String
- `sender_phone`: String
- `sender_id_document`: String (reference to sender's ID)
- `beneficiary_full_name_ar`: String (Arabic)
- `beneficiary_phone`: String (963-format)
- `beneficiary_national_id`: String
- `beneficiary_relationship`: Enum (FAMILY \| FRIEND \| BUSINESS \| CHARITY)
- `source_amount`: BigInt (amount in source currency)
- `source_currency`: String (ISO 4217, e.g. "EUR", "AED", "USD")
- `target_amount`: BigInt (amount in SYP after conversion)
- `target_currency`: String (always "SYP")
- `fx_rate_applied`: Decimal (18,6)
- `fx_quote_id`: UUID (references FXQuote)
- `fee_amount_in_source`: BigInt
- `fee_amount_in_target`: BigInt
- `total_cost`: BigInt (source_amount + fee_in_source)
- `payout_method`: Enum (WALLET \| CASH_PICKUP \| BANK_TRANSFER)
- `payout_wallet_id`: UUID (nullable, if payout_method = WALLET)
- `payout_agent_id`: UUID (nullable, if payout_method = CASH_PICKUP)
- `payout_bank_account`: String (nullable, if payout_method = BANK_TRANSFER)
- `purpose_code`: String (CBS remittance purpose classification)
- `source_of_funds_declaration`: String
- `status`: Enum (PENDING \| SCREENING \| QUOTED \| PAID_IN \| PROCESSING \| COMPLETED \| FAILED \| REFUNDED)
- `compliance_screening_result`: Enum (PASS \| PENDING_REVIEW \| REJECTED)
- `compliance_case_id`: UUID (nullable, if screening alert raised)
- `refund_reason`: String (nullable)
- `completed_at`: Timestamp (nullable)
- `created_at`: Timestamp

**Invariants:**
- `status = COMPLETED` → funds credited to beneficiary (wallet/cash/bank)
- `status = REFUNDED` → source amount returned to sender (minus non-refundable fees if applicable)
- All gender fields are MALE, FEMALE. Note: For Syria context, Family/Personal Status Law requires certain fields
- `purpose_code` must be a valid CBS remittance purpose code (e.g., "01" = Family Support, "02" = Personal Gifts, "03" = Education, "04" = Medical, "05" = Business/Investment)
- Source of funds declaration required for amounts > $1000 USD equivalent
- Beneficiary national ID required for payout_method = WALLET or CASH_PICKUP

**Domain Events:**
- `RemittanceOrderCreated`
- `RemittanceOrderScreened` — compliance check result
- `RemittanceOrderPaidIn` — funds received from sender
- `RemittanceOrderProcessing` — corridor routing initiated
- `RemittanceOrderCompleted` — funds available to beneficiary
- `RemittanceOrderFailed`
- `RemittanceOrderRefunded`

#### Corridor Aggregate

| Attribute | Value |
|-----------|-------|
| **Root** | Corridor (corridor_id) |
| **Description** | Defines a remittance corridor with its specific FX rate source, fee structure, limits, and regulatory rules. Each corridor represents a source country/corridor pair. |

**Attributes:**
- `corridor_id`: UUID (prefixed `cor_`)
- `name`: String (e.g. "Europe→Turkey→Syria", "UAE→Syria")
- `source_country`: String (ISO code)
- `source_currency`: String (ISO 4217)
- `intermediate_currency`: String (nullable, e.g. "TRY" for Europe corridor)
- `target_currency`: String (always "SYP")
- `fx_rate_source`: Enum (CBS \| REUTERS \| CORRIDOR_FIXED)
- `fixed_spread_pct`: Decimal (5,2)
- `fee_type`: Enum (FLAT \| PERCENTAGE \| TIERED)
- `fee_structure`: JSON (tiered fee definition)
- `min_amount`: BigInt
- `max_amount`: BigInt
- `daily_limit_per_sender`: BigInt
- `monthly_limit_per_sender`: BigInt
- `is_active`: Boolean
- `supported_payout_methods`: Enum[] (WALLET, CASH_PICKUP, BANK_TRANSFER)
- `compliance_requirements`: JSON (additional KYC/docs needed for this corridor)
- `created_at`: Timestamp

**Domain Events:**
- `CorridorActivated`
- `CorridorDeactivated`
- `CorridorFeeStructureUpdated`

---

### Compliance Context

#### ComplianceCase Aggregate

| Attribute | Value |
|-----------|-------|
| **Root** | ComplianceCase (case_id) |
| **Description** | A compliance investigation case triggered by a screening alert, transaction monitoring rule hit, or suspicious activity report. Managed by Beza's compliance team. |

**Attributes:**
- `case_id`: UUID (prefixed `crc_`)
- `case_type`: Enum (SCREENING_ALERT \| TRANSACTION_MONITORING \| SAR \| PEP \| ADVERSE_MEDIA)
- `priority`: Enum (LOW \| MEDIUM \| HIGH \| CRITICAL)
- `status`: Enum (OPEN \| UNDER_REVIEW \| ESCALATED \| CLOSED \| DISMISSED)
- `subject_user_id`: UUID (nullable, references User)
- `subject_name`: String
- `subject_national_id`: String (nullable)
- `subject_phone`: String (nullable)
- `trigger_event`: String (e.g. "wallet_created", "remittance_order", "cash_out")
- `trigger_event_id`: UUID
- `trigger_amount`: BigInt (nullable)
- `trigger_currency`: String (nullable)
- `screening_match_reference`: String (World-Check match ID)
- `screening_match_score`: Decimal (5,2)
- `screening_matched_entity`: String (name on sanctions list)
- `screening_matched_list`: String (e.g. "OFAC_SDN", "UNSCR_1267", "EU_SANCTIONS")
- `is_pep`: Boolean
- `assignee_id`: UUID (nullable, compliance officer)
- `notes`: JSON[] (array of note objects with author, timestamp, content)
- `escalated_to`: String (nullable, e.g. "CBS_AML_DEPT")
- `escalated_at`: Timestamp (nullable)
- `closed_at`: Timestamp (nullable)
- `closure_reason`: String (nullable)
- `created_at`: Timestamp

**Invariants:**
- Case cannot be CLOSED without a documented closure_reason
- Case with `priority = CRITICAL` must be assigned within 1 hour
- `status = ESCALATED` → `escalated_to` and `escalated_at` must be set
- Active case for a user prevents new wallet creation

**Domain Events:**
- `ComplianceCaseOpened`
- `ComplianceCaseAssigned`
- `ComplianceCaseEscalated`
- `ComplianceCaseClosed`
- `ComplianceCaseDismissed`

#### ScreeningRequest Aggregate

| Attribute | Value |
|-----------|-------|
| **Root** | ScreeningRequest (screening_id) |
| **Description** | Record of a single sanctions/PEP screening request submitted to World-Check or internal screening engine. |

**Attributes:**
- `screening_id`: UUID
- `source_context`: String (context requesting screening)
- `source_reference_id`: UUID
- `screening_provider`: Enum (WORLD_CHECK \| OFAC_LOCAL \| UNSCR_LOCAL)
- `subject_name`: String
- `subject_name_ar`: String (nullable, Arabic script)
- `subject_dob`: Date (nullable)
- `subject_nationality`: String (ISO country code, nullable)
- `subject_id_type`: String (nullable)
- `subject_id_number`: String (nullable)
- `request_payload`: JSON (full request body sent to provider)
- `response_payload`: JSON (full response body from provider)
- `match_score`: Decimal (5,2)
- `is_hit`: Boolean
- `status`: Enum (PENDING \| COMPLETED \| FAILED \| TIMEOUT)
- `case_id`: UUID (nullable, generated if match_score > threshold)
- `screened_at`: Timestamp
- `created_at`: Timestamp

**Domain Events:**
- `ScreeningRequested`
- `ScreeningCompleted`
- `ScreeningFailed`

#### TransactionMonitoringRule Aggregate

| Attribute | Value |
|-----------|-------|
| **Root** | TransactionMonitoringRule (rule_id) |
| **Description** | Configurable AML rule for detecting suspicious transaction patterns. Rules are defined by the compliance team and evaluated against all transactions. |

**Attributes:**
- `rule_id`: UUID
- `rule_name`: String (e.g. "Rapid_CashOut_Sequential", "Velocity_SmallAmounts")
- `rule_type`: Enum (VELOCITY \| THRESHOLD \| GEOGRAPHIC \| BEHAVIORAL \| NETWORK)
- `parameters`: JSON (rule-specific thresholds, e.g. `{"max_txns_per_hour": 5, "max_amount": 500000}`)
- `severity`: Enum (LOW \| MEDIUM \| HIGH \| CRITICAL)
- `is_active`: Boolean
- `action_on_hit`: Enum (LOG_ONLY \| ALERT \| BLOCK)
- `created_by`: UUID (compliance officer)
- `created_at`: Timestamp
- `updated_at`: Timestamp

**Domain Events:**
- `RuleCreated`
- `RuleUpdated`
- `RuleDeactivated`
- `RuleHit` — emitted when a transaction matches this rule

---

### Treasury Context

#### TreasuryPosition Aggregate

| Attribute | Value |
|-----------|-------|
| **Root** | TreasuryPosition (position_id) |
| **Description** | Snapshot of Beza's liquidity position across all bank accounts at a point in time. Computed from bank statement data and internal ledger. |

**Attributes:**
- `position_id`: UUID
- `snapshot_time`: Timestamp
- `total_cash_at_banks`: JSON (map of bank_account_id to balance)
- `total_agent_float_liability`: BigInt (aggregate of all agent float balances)
- `total_customer_wallet_liability`: BigInt (aggregate customer wallet balances)
- `total_pending_settlement_payable`: BigInt
- `total_pending_settlement_receivable`: BigInt
- `total_fx_position`: JSON (net position per currency)
- `cbs_reserve_balance`: BigInt (required reserve held at CBS)
- `cbs_reserve_requirement`: BigInt (minimum reserve required)
- `liquidity_ratio`: Decimal (5,2) — `(cash_at_banks + cbs_reserve) / total_liabilities × 100`
- `status`: Enum (LIQUID \| WARNING \| CRITICAL)
- `created_at`: Timestamp

**Domain Events:**
- `PositionSnapshotCreated`
- `LiquidityWarning`
- `LiquidityCritical`
- `ReserveRequirementBreached`

#### FloatForecast Aggregate

| Attribute | Value |
|-----------|-------|
| **Root** | FloatForecast (forecast_id) |

**Attributes:**
- `forecast_id`: UUID
- `forecast_date`: Date (the date being forecasted)
- `predicted_cash_in_volume`: BigInt
- `predicted_cash_out_volume`: BigInt
- `predicted_net_float_change`: BigInt
- `predicted_agents_below_minimum`: Integer
- `recommended_replenishment`: BigInt
- `confidence_level`: Enum (HIGH \| MEDIUM \| LOW)
- `model_version`: String
- `created_at`: Timestamp

**Domain Events:**
- `FloatForecastGenerated`

---

### Notification Context

#### NotificationTemplate Aggregate

| Attribute | Value |
|-----------|-------|
| **Root** | NotificationTemplate (template_id) |
| **Description** | Reusable notification template with Arabic and English variants. Supports variable substitution (e.g. `{{amount}}`, `{{beneficiary_name}}`). |

**Attributes:**
- `template_id`: UUID
- `template_name`: String (e.g. "remittance_completed", "cash_in_receipt")
- `channel`: Enum (SMS \| EMAIL \| WHATSAPP \| PUSH)
- `content_ar`: String (Arabic template with variables, e.g. "تم استلام {{amount}} ل.س من {{sender_name}}")
- `content_en`: String (English template)
- `variables`: String[] (list of variable names used in template)
- `is_active`: Boolean
- `created_at`: Timestamp

**Domain Events:**
- `TemplateCreated`
- `TemplateUpdated`
- `TemplateDeactivated`

#### NotificationOutbox Aggregate

| Attribute | Value |
|-----------|-------|
| **Root** | NotificationOutbox (outbox_id) |
| **Description** | Transactional outbox record for outgoing notifications. Written atomically with the business transaction. Consumed by Notification Dispatcher for delivery. |

**Attributes:**
- `outbox_id`: UUID
- `aggregate_type`: String (e.g. "wallet", "remittance")
- `aggregate_id`: UUID
- `event_type`: String (e.g. "WalletCreated", "RemittanceCompleted")
- `event_payload`: JSON
- `channel`: Enum (SMS \| EMAIL \| WHATSAPP \| PUSH)
- `template_id`: UUID (references NotificationTemplate)
- `rendered_content_ar`: String (pre-rendered Arabic)
- `rendered_content_en`: String (pre-rendered English)
- `recipient_address`: String (phone or email)
- `status`: Enum (PENDING \| SENT \| DELIVERED \| FAILED)
- `delivery_attempts`: Integer
- `last_attempt_at`: Timestamp (nullable)
- `delivered_at`: Timestamp (nullable)
- `failure_reason`: String (nullable)
- `correlation_id`: String
- `created_at`: Timestamp

**Domain Events:**
- `OutboxRecordCreated`
- `OutboxSent`
- `OutboxDelivered`
- `OutboxDeliveryFailed`

---

## Entity Ownership Matrix

| Entity | Owning Context | Read By | Write By | Events Emitted |
|--------|---------------|---------|----------|---------------|
| User | Identity | ALL contexts | Identity (self), Agent (agent-assisted), Compliance | UserRegistered, KYCLevelUpgraded, UserSuspended, UserReactivated, UserClosed |
| KYCDocument | Identity | Identity, Compliance | Identity, Compliance (verification) | KYCDocumentSubmitted, KYCDocumentVerified, KYCDocumentRejected, KYCDocumentExpired |
| Wallet | Wallet | User, Ledger, Compliance, Remittance | Wallet, CFE | WalletCreated, WalletFrozen, WalletActivated, WalletClosed, DailyLimitExceeded, MonthlyLimitExceeded, BalanceChanged |
| Transaction | Wallet | User, Compliance, Settlement, Remittance, Merchant | Wallet, CFE | TransactionInitiated, TransactionCompleted, TransactionFailed, TransactionReversed |
| Hold | Wallet | Wallet, FX, Merchant | Wallet, CFE | HoldCreated, HoldCaptured, HoldReleased, HoldExpired |
| Account | Ledger | Finance, Compliance, Treasury | CFE ONLY | AccountOpened, AccountFrozen, AccountClosed, BalanceReconciled |
| LedgerEntry | Ledger | ALL contexts | CFE ONLY | LedgerEntryPosted, LedgerReversalPosted |
| Journal | Ledger | Finance, Compliance, Treasury | CFE ONLY | JournalPosted, JournalReversed |
| FXRate | FX | ALL contexts | FX, CBS (via import) | FXRateUpdated, FXRateSpreadChanged |
| FXQuote | FX | Wallet, Remittance, Treasury | FX | FXQuoteCreated, FXQuoteAccepted, FXQuoteExpired |
| FXConversion | FX | Wallet, Remittance, Treasury | FX, Ledger | FXConversionCompleted, FXConversionFailed |
| Agent | Agent | Agent (self), Treasury, Compliance, Settlement | Agent (onboarding), Compliance, Settlement | AgentRegistered, AgentActivated, AgentSuspended, AgentTerminated, AgentFloatLow, AgentFloatToppedUp, AgentTierChanged |
| CashTransaction | Agent | Agent, User (customer), Compliance, Settlement | Agent, Ledger | CashTransactionInitiated, CashTransactionCompleted, CashTransactionFailed, CashTransactionReversed |
| AgentCommission | Agent | Agent, Finance | Agent, Settlement | CommissionCalculated, CommissionPaid |
| Merchant | Merchant | Merchant (self), Settlement, Compliance, Finance | Merchant (onboarding), Compliance, Settlement | MerchantRegistered, MerchantActivated, MerchantSuspended, MDRRateChanged |
| Terminal | Merchant | Merchant, Bemo (via integration) | Merchant, Bemo | TerminalRegistered, TerminalActivated, TerminalDecommissioned |
| MerchantPayment | Merchant | Merchant, User (payer), Settlement, Compliance | Merchant, Wallet | PaymentAuthorized, PaymentCaptured, PaymentSettled, PaymentRefunded, PaymentFailed |
| SettlementBatch | Settlement | Finance, Treasury, Compliance, Bank (CBS/BSO) | Settlement, CFE | SettlementBatchCalculated, SettlementBatchApproved, SettlementBatchSubmitted, SettlementBatchSettled, SettlementBatchFailed, SettlementBatchReconciled |
| NetPosition | Settlement | Settlement, Finance, Treasury, Counterparty | Settlement | PositionCalculated, PositionReconciled, PositionDisputed |
| RemittanceOrder | Remittance | Sender, Beneficiary, Compliance, Settlement, Treasury | Remittance, FX, Compliance, Ledger | RemittanceOrderCreated, RemittanceOrderScreened, RemittanceOrderPaidIn, RemittanceOrderProcessing, RemittanceOrderCompleted, RemittanceOrderFailed, RemittanceOrderRefunded |
| Corridor | Remittance | ALL contexts | Remittance (admin), Treasury | CorridorActivated, CorridorDeactivated, CorridorFeeStructureUpdated |
| ComplianceCase | Compliance | Compliance, CBS (regulatory), Treasury | Compliance, ALL (trigger events) | ComplianceCaseOpened, ComplianceCaseAssigned, ComplianceCaseEscalated, ComplianceCaseClosed, ComplianceCaseDismissed |
| ScreeningRequest | Compliance | Compliance | Compliance engine (auto) | ScreeningRequested, ScreeningCompleted, ScreeningFailed |
| TransactionMonitoringRule | Compliance | Compliance | Compliance (admin) | RuleCreated, RuleUpdated, RuleDeactivated, RuleHit |
| TreasuryPosition | Treasury | Treasury, Finance, Compliance | Treasury engine (auto) | PositionSnapshotCreated, LiquidityWarning, LiquidityCritical, ReserveRequirementBreached |
| FloatForecast | Treasury | Treasury, Finance, Agent management | Treasury engine (auto) | FloatForecastGenerated |
| NotificationTemplate | Notification | Notification, ALL (read reference) | Notification (admin) | TemplateCreated, TemplateUpdated, TemplateDeactivated |
| NotificationOutbox | Notification | Notification Dispatcher, Monitoring | Notification (async write by ALL contexts) | OutboxRecordCreated, OutboxSent, OutboxDelivered, OutboxDeliveryFailed |

---

## Domain Event Catalog

| Event | Producer | Consumer(s) | Payload Summary |
|-------|----------|-------------|-----------------|
| UserRegistered | Identity | Notification (welcome), Compliance (screen), Wallet (create wallets) | user_id, name, phone, kyc_level |
| KYCLevelUpgraded | Identity | Compliance, Wallet (limit changes) | user_id, old_level, new_level |
| WalletCreated | Wallet | Ledger (open accounts), Notification, Compliance | wallet_id, owner_id, currency, status |
| BalanceChanged | Wallet | Notification, Treasury (aggregation) | wallet_id, previous_balance, new_balance, delta |
| DailyLimitExceeded | Wallet | Notification (alert user), Compliance (if repeated) | wallet_id, user_id, limit_type, current_usage, limit |
| TransactionCompleted | Wallet | Notification, Compliance (monitoring), Settlement | transaction_id, wallet_id, amount, type, status |
| HoldCreated | Wallet | FX (if FX hold) | hold_id, wallet_id, amount, currency |
| LedgerEntryPosted | Ledger | Finance (real-time GL), Treasury | entry_id, account_id, amount, entry_type, journal_id |
| FXRateUpdated | FX | ALL (rate cache invalidation) | base_currency, mid_rate, bid, ask, published_at |
| FXConversionCompleted | FX | Wallet (update balances), Ledger (post entries), Remittance | conversion_id, quote_id, from_amount, to_amount, rate |
| CashTransactionCompleted | Agent | Wallet (balance update), Notification, Treasury | cash_txn_id, agent_id, customer_id, amount, type (cash_in/cash_out) |
| AgentFloatLow | Agent | Treasury (replenishment), Notification (alert agent) | agent_id, current_float, minimum_float |
| PaymentCaptured | Merchant | Wallet (debit), Notification, Settlement | payment_id, merchant_id, amount, net_settlement_amount |
| RemittanceOrderCompleted | Remittance | Wallet (credit payout), Notification (sender + beneficiary), Treasury | order_id, corridor_id, sender_id, beneficiary_id, amount, payout_method |
| RemittanceOrderFailed | Remittance | Notification (sender), Treasury (refund process) | order_id, failure_reason, refund_status |
| ComplianceCaseOpened | Compliance | Notification (compliance team) | case_id, user_id, trigger_event, match_score, priority |
| RuleHit | Compliance | Compliance (case creation), Notification (alert) | rule_id, transaction_id, hit_details, severity |
| SettlementBatchSettled | Settlement | Ledger (post entries), Notification (agent/merchant), Treasury | batch_id, settlement_date, net_position, settlement_reference |
| LiquidityWarning | Treasury | Notification (treasury team) | position_id, liquidity_ratio, threshold |
| OutboxDelivered | Notification | Monitoring (delivery analytics) | outbox_id, channel, recipient, delivered_at |

---

## Value Objects

| Value Object | Attributes | Used By |
|-------------|-----------|---------|
| Money | amount (BigInt), currency (Enum) | Wallet, Transaction, LedgerEntry, all financial entities |
| ExchangeRate | base, quote, mid, bid, ask, spread_pct | FXRate, FXQuote |
| Address | governorate, city, street, landmark | User, Agent, Merchant |
| KYCLevel | level (Enum), verified_at, expires_at | User |
| CommissionTier | tier_level, cash_in_rate_pct, cash_out_rate_pct, bonus_threshold | Agent, AgentCommission |
| MDRRate | rate_pct, fixed_fee, volume_tier | Merchant, MerchantPayment |
| CorridorRoute | source_country, intermediate_country, target_country, currencies | Corridor |
| ComplianceResult | is_hit, match_score, matched_list, match_name | ScreeningRequest, ComplianceCase |
| AuditTrail | created_by, created_at, updated_by, updated_at | ALL aggregates |
| TimeRange | start_time, end_time | SettlementBatch, Transaction monitoring |
