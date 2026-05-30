# Data Dictionary — Beza Platform

> Canonical single source of truth for every data field. All services, databases, APIs, and documentation MUST derive their field definitions from this document. Any discrepancy is a bug.

**Owner:** Data Governance Committee | **Version:** 2.0 | **Last updated:** Platform launch

---

## Entity Identifiers

All primary identifiers follow the patterns below. Every entity carries a 3-4 letter prefix in its string representation for human readability and debugging.

| Field | Type | Length/Format | Domain | Owned By | Description | Example |
|-------|------|--------------|--------|----------|-------------|---------|
| `user_id` | UUID | UUIDv4, hex `8-4-4-4-12` | Identity | Identity Module | Unique user identifier; used as foreign key across all domains | `usr_a1b2c3d4-e5f6-7890-abcd-ef1234567890` |
| `wallet_id` | ULID | 26 chars, Crockford base32 | Wallet | Wallet Module | Unique wallet identifier; sortable, timestamp-encoded | `wlt_01HKABCDEFG12345678901234` |
| `transaction_id` | ULID | 26 chars | Wallet | Wallet Module | Unique transaction identifier for all money movements | `txn_01HKABCDEFG12345678901234` |
| `account_id` | ULID | 26 chars | Ledger | CFE (Core Financial Engine) | Chart of accounts identifier; used in double-entry bookkeeping | `acc_01HKABCDEFG12345678901234` |
| `journal_entry_id` | UUID | UUIDv4 | Ledger | CFE | Single line in a double-entry journal; debits and credits are linked by `correlation_id` | `je_a1b2c3d4-e5f6-7890-abcd-ef1234567890` |
| `agent_id` | ULID | 26 chars | Agent | Agent Module | Agent entity identifier; agents are individuals operating cash-in/cash-out points | `agt_01HKABCDEFG12345678901234` |
| `merchant_id` | ULID | 26 chars | Merchant | Merchant Module | Merchant entity identifier; businesses accepting Beza payments | `mch_01HKABCDEFG12345678901234` |
| `remittance_order_id` | ULID | 26 chars | Remittance | Remittance Module | Cross-border remittance order; spans FX conversion + payout | `rem_01HKABCDEFG12345678901234` |
| `settlement_batch_id` | ULID | 26 chars | Settlement | Settlement Module | End-of-day settlement batch; aggregates transactions for CBS/agent/merchant settlement | `stl_01HKABCDEFG12345678901234` |
| `fx_quote_id` | ULID | 26 chars | FX | FX Module | Locked FX rate quote used for a conversion; TTL 15 seconds | `fxq_01HKABCDEFG12345678901234` |
| `fx_rate_id` | ULID | 26 chars | FX | FX Module | Point-in-time FX rate snapshot from feed (CBS or parallel market) | `fxr_01HKABCDEFG12345678901234` |
| `bill_id` | ULID | 26 chars | Bills | Bills Module | Bill payment transaction record; references the biller and account | `bil_01HKABCDEFG12345678901234` |
| `payroll_batch_id` | ULID | 26 chars | Payroll | Payroll Module | Batch of salary disbursements for corporate clients | `pay_01HKABCDEFG12345678901234` |
| `savings_goal_id` | ULID | 26 chars | Savings | Savings Module | User-defined savings target with auto-deposit schedule | `sav_01HKABCDEFG12345678901234` |
| `loan_id` | ULID | 26 chars | Financing | Financing Module | Loan product issued to a user; tracks principal, payments, overdue status | `loa_01HKABCDEFG12345678901234` |
| `card_id` | ULID | 26 chars | Cards | Cards Module | Prepaid or virtual card linked to a wallet | `crd_01HKABCDEFG12345678901234` |
| `fraud_case_id` | ULID | 26 chars | Fraud | Fraud Module | Fraud investigation case opened by automated rules or manual review | `frd_01HKABCDEFG12345678901234` |
| `compliance_case_id` | ULID | 26 chars | Compliance | Compliance Module | AML/CTF case; SAR filing, sanctions escalation, or enhanced due diligence | `cmp_01HKABCDEFG12345678901234` |
| `notification_id` | ULID | 26 chars | Notification | Notification Module | Individual notification delivery record (SMS, push, email) | `not_01HKABCDEFG12345678901234` |
| `session_id` | UUID | UUIDv4 | Identity | Identity Module | Authenticated user session; bound to device + access token | `ses_a1b2c3d4-e5f6-7890-abcd-ef1234567890` |
| `device_id` | UUID | UUIDv4 | Identity | Identity Module | Device fingerprint; derived from Android ID, IMEI, advertising ID, and IP | `dev_a1b2c3d4-e5f6-7890-abcd-ef1234567890` |
| `kyc_id` | ULID | 26 chars | Identity | Identity Module | KYC verification attempt record; one user may have multiple attempts | `kyc_01HKABCDEFG12345678901234` |
| `ticket_id` | ULID | 26 chars | Support | Support Module | Customer support or dispute ticket | `tkt_01HKABCDEFG12345678901234` |
| `audit_log_id` | ULID | 26 chars | System | Compliance/Ops | Immutable audit trail entry for all non-read operations | `aud_01HKABCDEFG12345678901234` |
| `idempotency_key` | UUID | UUIDv4 | System | API Gateway | Idempotency key for safe retries; TTL 24 hours, one-time use | `idm_a1b2c3d4-e5f6-7890-abcd-ef1234567890` |
| `biller_id` | ULID | 26 chars | Bills | Bills Module | Biller/integration partner identifier | `bil_01HKABCDEFG12345678901234` |
| `float_topup_id` | ULID | 26 chars | Agent | Agent Module | Agent float top-up/withdrawal record | `flt_01HKABCDEFG12345678901234` |
| `commission_id` | ULID | 26 chars | Agent | Agent Module | Agent or merchant commission payout record | `com_01HKABCDEFG12345678901234` |
| `fee_id` | ULID | 26 chars | Wallet | Wallet Module | Fee assessment record per transaction | `fee_01HKABCDEFG12345678901234` |
| `ledger_sync_id` | ULID | 26 chars | Ledger | CFE | Ledger synchronization marker between wallet service and CFE | `lsy_01HKABCDEFG12345678901234` |
| `cashback_rule_id` | ULID | 26 chars | Wallet | Wallet Module | Cashback promotion rule definition | `cbk_01HKABCDEFG12345678901234` |
| `promotion_id` | ULID | 26 chars | Marketing | Marketing Module | Marketing campaign or promotion identifier | `pro_01HKABCDEFG12345678901234` |
| `referral_id` | ULID | 26 chars | Marketing | Marketing Module | Referral tracking link identifier | `ref_01HKABCDEFG12345678901234` |
| `merchant_qr_id` | ULID | 26 chars | Merchant | Merchant Module | Static/virtual QR code assigned to a merchant | `qr_01HKABCDEFG12345678901234` |
| `agent_location_id` | ULID | 26 chars | Agent | Agent Module | Agent physical location (agent may have multiple locations) | `loc_01HKABCDEFG12345678901234` |

---

## Money Amounts

All monetary values use BigInt (integer) representing the amount in the smallest currency unit (e.g., SYP piaster, USD cent). Never use floating-point types for money.

| Field | Type | Precision | Description | Example |
|-------|------|-----------|-------------|---------|
| `amount` | BigInt | 0 (integer) | Transaction amount in smallest currency unit | `500000` = SYP 5,000.00 |
| `balance` | BigInt | 0 (integer) | Wallet or account balance in smallest unit | `15000000` = SYP 150,000.00 |
| `fee_amount` | BigInt | 0 (integer) | Fee assessed for a transaction | `25000` = SYP 250.00 |
| `commission_amount` | BigInt | 0 (integer) | Commission paid to agent or merchant | `10000` = SYP 100.00 |
| `cashback_amount` | BigInt | 0 (integer) | Cashback reward amount | `5000` = SYP 50.00 |
| `tax_amount` | BigInt | 0 (integer) | Tax component (if applicable) | `7500` = SYP 75.00 |
| `settlement_amount` | BigInt | 0 (integer) | Net settlement amount after fees | `475000` = SYP 4,750.00 |
| `limit_amount` | BigInt | 0 (integer) | Daily/monthly transaction limit value | `10000000` = SYP 100,000.00 |
| `minimum_amount` | BigInt | 0 (integer) | Minimum allowed transaction amount | `10000` = SYP 100.00 |
| `maximum_amount` | BigInt | 0 (integer) | Maximum allowed transaction amount | `1000000000` = SYP 10,000,000.00 |
| `agent_float_balance` | BigInt | 0 (integer) | Agent float balance in smallest unit | `500000000` = SYP 5,000,000.00 |
| `margin_amount` | BigInt | 0 (integer) | Markup/margin on FX spread | `25000` = SYP 250.00 |

### Currency Fields

| Field | Type | Format | Description | Example |
|-------|------|--------|-------------|---------|
| `currency` | String(3) | ISO 4217 alpha-3 | Currency code for the amount | `SYP`, `USD` |
| `source_currency` | String(3) | ISO 4217 | Source currency in FX conversion | `USD` |
| `target_currency` | String(3) | ISO 4217 | Target currency in FX conversion | `SYP` |
| `settlement_currency` | String(3) | ISO 4217 | Currency used for CBS/nostro settlement | `USD` |
| `fx_rate` | Decimal(10,6) | Up to 6 decimal places | Exchange rate between currencies | `13100.000000` (1 USD = 13,100 SYP) |
| `fx_margin_rate` | Decimal(5,4) | Up to 4 decimal places | Beza margin applied on top of reference rate | `0.0050` (0.5%) |
| `fx_spread` | Decimal(5,4) | Up to 4 decimal places | Bid-ask spread percentage | `0.0200` (2%) |
| `display_rate` | Decimal(10,6) | Up to 6 decimal places | Rate shown to user (reference + margin) | `13165.500000` |

---

## Timestamps

All timestamps MUST be stored in UTC. API responses return ISO 8601 UTC with timezone indicator. Mobile apps convert to device local timezone (Asia/Damascus).

| Field | Type | Format | Description |
|-------|------|--------|-------------|
| `created_at` | Timestamp | ISO 8601 UTC (`2025-05-29T10:30:00.123Z`) | Resource creation time; set once on insert |
| `updated_at` | Timestamp | ISO 8601 UTC | Last update time; updated on every mutation |
| `deleted_at` | Timestamp (nullable) | ISO 8601 UTC | Soft delete timestamp; NULL if active |
| `expires_at` | Timestamp (nullable) | ISO 8601 UTC | Expiration timestamp (holds, rate locks, sessions, OTPs) |
| `completed_at` | Timestamp (nullable) | ISO 8601 UTC | Transaction/job completion timestamp |
| `failed_at` | Timestamp (nullable) | ISO 8601 UTC | Transaction/job failure timestamp |
| `reversed_at` | Timestamp (nullable) | ISO 8601 UTC | Reversal timestamp for reversed transactions |
| `settled_at` | Timestamp (nullable) | ISO 8601 UTC | Settlement processing timestamp |
| `synced_at` | Timestamp (nullable) | ISO 8601 UTC | Last CFE/ledger sync timestamp |
| `last_login_at` | Timestamp (nullable) | ISO 8601 UTC | User's most recent authentication timestamp |
| `kyc_verified_at` | Timestamp (nullable) | ISO 8601 UTC | KYC verification completion timestamp |
| `otp_expires_at` | Timestamp | ISO 8601 UTC | OTP code expiration; TTL 5 minutes from generation |
| `rate_expires_at` | Timestamp | ISO 8601 UTC | FX rate quote expiration; TTL 15 seconds |
| `hold_expires_at` | Timestamp | ISO 8601 UTC | Fund hold/earmark expiration (e.g., 24h for bill holds) |
| `retry_at` | Timestamp (nullable) | ISO 8601 UTC | Scheduled retry time for failed operations |
| `archived_at` | Timestamp (nullable) | ISO 8601 UTC | Record moved to cold storage or archive |

---

## Enums

### Transaction Lifecycle

| Enum | Values | Description |
|------|--------|-------------|
| `transaction_status` | `pending`, `processing`, `held`, `completed`, `failed`, `reversed`, `disputed`, `expired` | Full lifecycle of any money movement |
| `transaction_type` | `p2p_transfer`, `cash_in`, `cash_out`, `merchant_payment`, `bill_payment`, `remittance_inbound`, `remittance_outbound`, `wallet_topup`, `wallet_withdrawal`, `fee_assessment`, `commission_payout`, `cashback_reward`, `refund`, `reversal`, `loan_disbursement`, `loan_repayment`, `savings_deposit`, `savings_withdrawal`, `interest_payment` | Categorization of every financial operation |
| `hold_reason` | `pending_bill`, `pending_fx`, `pending_remittance`, `dispute`, `compliance_review`, `fraud_review`, `scheduled` | Reason funds are on hold |

### Wallet

| Enum | Values | Description |
|------|--------|-------------|
| `wallet_status` | `active`, `frozen`, `suspended`, `closed`, `dormant` | Wallet operational state |
| `wallet_tier` | `basic`, `standard`, `premium`, `corporate` | Wallet feature/limit tier |
| `wallet_limit_type` | `daily_transaction_count`, `daily_transaction_volume`, `monthly_transaction_count`, `monthly_transaction_volume`, `daily_cash_in`, `daily_cash_out`, `max_balance`, `single_transaction` | Types of wallet limits |

### Ledger / CFE

| Enum | Values | Description |
|------|--------|-------------|
| `account_type` | `asset`, `liability`, `equity`, `income`, `expense`, `suspense`, `contra` | CFE chart of accounts types per IFRS |
| `entry_type` | `debit`, `credit` | Double-entry journal line direction |
| `ledger_status` | `pending_sync`, `synced`, `failed`, `reconciling`, `reconciled` | Ledger sync state between wallet service and CFE |
| `account_normal_balance` | `debit`, `credit` | Natural balance side of the account type |

### Agent

| Enum | Values | Description |
|------|--------|-------------|
| `agent_status` | `pending`, `active`, `suspended`, `terminated`, `under_review` | Agent lifecycle; agents must be `active` to perform transactions |
| `agent_type` | `retail_shop`, `kiosk`, `mobile_agent`, `bank_corner`, `post_office` | Agent category |
| `agent_commission_type` | `fixed_per_transaction`, `percentage_of_volume`, `tiered_rate`, `monthly_guarantee` | Commission structure type |
| `float_transaction_type` | `topup`, `withdrawal`, `settlement_adjustment`, `commission`, `reversal` | Agent float movement types |

### Merchant

| Enum | Values | Description |
|------|--------|-------------|
| `merchant_status` | `pending`, `active`, `suspended`, `terminated` | Merchant operational state |
| `merchant_category` | `retail`, `restaurant`, `pharmacy`, `supermarket`, `transport`, `utility`, `education`, `healthcare`, `government`, `other` | Merchant business category |
| `merchant_qr_type` | `static`, `dynamic` | QR code type: static (fixed amount) or dynamic (customer enters amount) |
| `merchant_settlement_frequency` | `daily`, `weekly`, `biweekly`, `monthly` | How often merchant receives settlement |

### Remittance

| Enum | Values | Description |
|------|--------|-------------|
| `remittance_status` | `initiated`, `rate_locked`, `aml_screening`, `processing`, `completed`, `failed`, `refunded` | Cross-border remittance lifecycle |
| `remittance_corridor` | `SYR_LBN`, `SYR_JOR`, `SYR_IRQ`, `SYR_EGY`, `SYR_TUR`, `SYR_ARE`, `SYR_SAU`, `SYR_KWT`, `SYR_QAT`, `SYR_OMN`, `SYR_BHR` | Active remittance corridors (Syria → destination) |
| `remittance_delivery_method` | `wallet_credit`, `bank_transfer`, `cash_pickup`, `mobile_money` | Payout method for recipient |
| `remittance_purpose` | `family_support`, `education`, `healthcare`, `emergency_aid`, `business_payment`, `savings`, `other` | Purpose of remittance (required for CBS reporting) |

### FX

| Enum | Values | Description |
|------|--------|-------------|
| `currency_code` | `SYP`, `USD` | Supported currencies (EUR planned V2) |
| `fx_rate_source` | `cbs_official`, `cbs_reference`, `parallel_market`, `interbank`, `manual_override` | Source of the FX rate |
| `fx_rate_status` | `active`, `stale`, `expired`, `invalid`, `manual_override` | Rate feed health status |
| `fx_lock_state` | `pending`, `locked`, `consumed`, `expired`, `cancelled` | Rate quote lifecycle |

### KYC / Identity

| Enum | Values | Description |
|------|--------|-------------|
| `kyc_tier` | `tier_1_basic`, `tier_2_verified`, `tier_3_premium` | KYC verification levels; each tier unlocks higher limits |
| `kyc_status` | `not_started`, `pending_documents`, `pending_verification`, `verified`, `rejected`, `expired` | KYC verification state |
| `kyc_document_type` | `national_id_front`, `national_id_back`, `passport`, `drivers_license`, `proof_of_address`, `selfie`, `birth_certificate`, `business_registration` | Accepted KYC document types |
| `kyc_verification_method` | `auto_ocr`, `auto_face_match`, `manual_review`, `civil_registry_api`, `third_party_api` | How KYC was verified |

### Compliance / AML

| Enum | Values | Description |
|------|--------|-------------|
| `fraud_risk` | `low`, `medium`, `high`, `critical` | Fraud risk classification score bands |
| `aml_screening_status` | `cleared`, `pending_review`, `flagged`, `escalated`, `sar_filed` | AML screening outcome |
| `sanctions_list_type` | `ofac_sdn`, `ofac_caption`, `eu_consolidated`, `un_security_council`, `cbs_syria`, `internal_blacklist` | Sanctions list sources |
| `compliance_action_type` | `account_freeze`, `transaction_block`, `sar_filing`, `kyc_rejection`, `kyc_approval`, `limit_reduction`, `account_closure`, `manual_review` | Actions taken by compliance team |
| `sar_status` | `draft`, `submitted_cbs`, `acknowledged`, `rejected`, `closed` | Suspicious Activity Report filing state |

### Notification

| Enum | Values | Description |
|------|--------|-------------|
| `notification_channel` | `sms`, `push`, `email`, `in_app`, `whatsapp` | Delivery channel for notifications |
| `notification_priority` | `low`, `normal`, `high`, `urgent` | Notification delivery priority |
| `notification_status` | `queued`, `sent`, `delivered`, `failed`, `read`, `clicked` | Delivery lifecycle |

### Bills

| Enum | Values | Description |
|------|--------|-------------|
| `bill_payment_status` | `inquiry_pending`, `inquiry_completed`, `payment_pending`, `payment_completed`, `payment_failed`, `refunded` | Bill payment lifecycle |
| `biller_category` | `electricity`, `water`, `telecom`, `internet`, `gas`, `municipality`, `education`, `insurance`, `government_fee`, `other` | Biller type |
| `biller_protocol` | `rest_api`, `soap`, `file_upload`, `ussd`, `custom_socket` | Integration protocol for biller |

### Cards

| Enum | Values | Description |
|------|--------|-------------|
| `card_type` | `virtual_prepaid`, `physical_prepaid`, `virtual_debit`, `physical_debit` | Card product type |
| `card_status` | `pending_activation`, `active`, `suspended`, `cancelled`, `expired`, `lost_stolen`, `blocked` | Card lifecycle status |
| `card_network` | `local_switch`, `mastercard`, `visa` | Card network (local switch for Syria domestic) |

### Financing

| Enum | Values | Description |
|------|--------|-------------|
| `loan_status` | `pending_approval`, `approved`, `active`, `overdue`, `defaulted`, `repaid`, `written_off`, `rejected` | Loan lifecycle |
| `loan_product` | `micro_loan_30d`, `micro_loan_60d`, `small_business_90d`, `emergency_loan`, `salary_advance` | Loan product types |
| `repayment_frequency` | `daily`, `weekly`, `biweekly`, `monthly`, `lump_sum` | Loan repayment schedule |

### Savings

| Enum | Values | Description |
|------|--------|-------------|
| `savings_goal_status` | `active`, `paused`, `completed`, `cancelled` | Savings goal state |
| `savings_frequency` | `daily`, `weekly`, `biweekly`, `monthly`, `one_time` | Auto-deposit frequency |
| `savings_deposit_source` | `wallet_balance`, `round_up`, `scheduled_auto_deposit`, `manual` | Source of savings deposit |

### Settlement

| Enum | Values | Description |
|------|--------|-------------|
| `settlement_status` | `pending`, `in_progress`, `completed`, `failed`, `reconciling`, `reconciled`, `disputed` | Settlement batch lifecycle |
| `settlement_party` | `agent`, `merchant`, `cbs`, `nostro`, `beza_fee_account`, `beza_commission_account`, `tax_authority` | Counterparty in settlement |
| `settlement_method` | `bank_transfer`, `wallet_credit`, `cash_pickup`, `cheque`, `netting` | Settlement transfer method |

### System

| Enum | Values | Description |
|------|--------|-------------|
| `audit_action` | `create`, `update`, `delete`, `soft_delete`, `restore`, `approve`, `reject`, `suspend`, `activate`, `freeze`, `unfreeze`, `login`, `logout`, `password_change`, `pin_change`, `kyc_approve`, `kyc_reject`, `compliance_freeze`, `compliance_unfreeze`, `rate_override`, `commission_change`, `limit_change`, `refund`, `reversal`, `settlement`, `sync`, `export_report` | All auditable system actions |
| `job_status` | `queued`, `running`, `completed`, `failed`, `cancelled`, `timed_out` | Background job/scheduler state |
| `feature_flag` | `enabled`, `disabled`, `beta`, `internal_only`, `maintenance` | Feature flag states |

---

## Naming Conventions

### Database Naming

| Artifact | Convention | Example |
|----------|-----------|---------|
| Table name | `snake_case`, plural, domain-prefixed | `wallet_transactions`, `agent_profiles`, `kyc_documents` |
| Column name | `snake_case`, descriptive | `current_balance`, `last_login_at` |
| Primary key | `{entity}_id` matching ULID/UUID column type | `wallet_id` |
| Foreign key | `{referenced_table_singular}_id` | `agent_id`, `merchant_id` |

### Index Conventions

| Type | Pattern | Example |
|------|---------|---------|
| Primary index | `pk_{table}` (implicit) | `pk_wallet_transactions` |
| Standard B-tree index | `idx_{table}_{column}` | `idx_wallet_transactions_status` |
| Composite index | `idx_{table}_{col1}_{col2}` | `idx_wallet_transactions_wallet_id_created_at` |
| Unique constraint | `uq_{table}_{column(s)}` | `uq_users_phone`, `uq_merchants_cr_number` |
| Full-text index | `idx_{table}_{column}_fts` | `idx_merchant_profiles_name_fts` |
| Partial/conditional index | `idx_{table}_{column}_where_{condition}` | `idx_wallet_transactions_status_where_pending` |
| Covering index | `idx_{table}_{col}_include_{extra_cols}` | `idx_wallet_transactions_id_include_amount_status` |
| GiST/GIN (Postgres) | `idx_{table}_{column}_gin` | `idx_agent_locations_coordinates_gist` |

### Foreign Key Conventions

| Type | Pattern | Example |
|------|---------|---------|
| Standard FK | `fk_{child_table}_{parent_table}` | `fk_wallet_transactions_wallets` |
| Self-referencing FK | `fk_{table}_{column}` | `fk_wallet_transactions_parent` |
| FK with composite | `fk_{child_table}_{parent_table}_composite` | `fk_ledger_entries_journals_composite` |

### Constraint Naming

| Type | Pattern | Example |
|------|---------|---------|
| Check constraint | `ck_{table}_{description}` | `ck_wallet_transactions_amount_positive` |
| NOT NULL | (implicit on column) | — |
| Default | `df_{table}_{column}` | `df_wallet_transactions_status` |
| Sequence | `seq_{table}_{column}` | `seq_reference_numbers_counter` |

### Trigger Naming

| Type | Pattern | Example |
|------|---------|---------|
| Audit trigger | `trg_{table}_audit` | `trg_wallet_transactions_audit` |
| Update timestamp | `trg_{table}_updated_at` | `trg_wallet_profiles_updated_at` |
| Business rule | `trg_{table}_{rule}` | `trg_wallet_transactions_prevent_self_transfer` |

---

## Data Type Mapping

| Logical Type | PostgreSQL | MySQL | SQLite (dev) | Dart/Flutter | TypeScript/Node |
|-------------|-----------|-------|-------------|-------------|-----------------|
| ULID | `UUID` or `TEXT(26)` | `CHAR(26)` | `TEXT` | `String` | `string` |
| UUID (v4) | `UUID` | `CHAR(36)` | `TEXT` | `String` | `string` |
| BigInt money | `BIGINT` | `BIGINT` | `INTEGER` | `int` | `number` (up to 2^53) |
| Decimal rate | `NUMERIC(10,6)` | `DECIMAL(10,6)` | `REAL` | `double` | `number` |
| String(3) currency | `CHAR(3)` | `CHAR(3)` | `TEXT` | `String` | `string` |
| Timestamp | `TIMESTAMPTZ` | `TIMESTAMP(3)` | `TEXT (ISO 8601)` | `DateTime` | `Date` |
| Enum | `TEXT` with `CHECK` or native `ENUM` | `ENUM` or `VARCHAR` | `TEXT` | `String` (const set) | `enum` (TypeScript) |
| Boolean | `BOOLEAN` | `TINYINT(1)` | `INTEGER` | `bool` | `boolean` |
| JSON | `JSONB` | `JSON` | `TEXT` | `Map<String, dynamic>` | `object` |
| Text | `TEXT` | `TEXT` | `TEXT` | `String` | `string` |
| Bytea/blob | `BYTEA` | `BLOB` | `BLOB` | `Uint8List` | `Buffer` |

---

## Field-Level Classification Matrix

Every field must be classified per the [Data Classification](../shared/data-governance/01-data-classification.md) policy.

| Classification | Label | Example Fields |
|---------------|-------|---------------|
| Critical | `CRIT` | `pin_hash`, `password_hash`, `otp_hash`, `token_signing_key` |
| Restricted | `REST` | `national_id`, `kyc_document_image`, `phone_number`, `full_name`, `email`, `date_of_birth`, `mother_name` |
| Confidential | `CONF` | `balance`, `transaction_amount`, `wallet_id`, `agent_commission_rate`, `bank_account_number` |
| Internal | `INT` | `device_id`, `ip_address`, `gps_coordinates`, `feature_flag` |
| Public | `PUB` | `agent_shop_name`, `merchant_business_name`, `fx_rate` (public API) |

---

## Change Management

| Change Type | Approval Required | Process |
|-------------|------------------|---------|
| New entity/table | Data Governance Committee + Lead Architect | RFC with schema review |
| New column (non-nullable) | Data Owner + Lead Architect | Migration plan with default value |
| New column (nullable) | Data Steward (notified) | PR with schema review |
| Column rename | Data Governance Committee | Deprecate old → migrate → drop (2-release cycle) |
| Column type change | Data Governance Committee | Impact analysis on all down-stream consumers |
| Enum value addition | Data Owner (notified) | PR with enum update |
| Enum value removal | Data Governance Committee | Deprecation notice 1 release before removal |
| Index addition | Data Steward (notified) | Performance justification in PR |
| FK addition/removal | Data Steward + Lead Architect | Referential integrity impact analysis |

---

## Syria-Specific Notes

- **SYP amounts** are stored in piasters (1 SYP = 100 piasters). A display value of SYP 5,000.00 is stored as `500000`.
- **USD amounts** are stored in cents (1 USD = 100 cents). A display value of USD 10.00 is stored as `1000`.
- **Phone numbers** are stored as E.164 format with +963 prefix for Syrian numbers (e.g., `+963955123456`).
- **National ID (رقم وطني)** is an 11-digit numeric string; stored encrypted as RESTRICTED data.
- **GPS coordinates** for agent locations must be rounded to 3 decimal places (~111m precision) for public display.
- **CBS reporting fields** (`cbs_reporting_group`, `cbs_transaction_code`, `cbs_branch_code`) are mandatory on all settlement and remittance records.
- **Ramadan/holiday calendars** affect settlement schedules; `settlement_due_date` fields may shift based on Central Bank of Syria holiday calendar.
