# Database Schema

## Entity Relationship Summary

```
aid_programs 1──N aid_beneficiaries
aid_programs 1──N aid_distributions
aid_distributions 1──N aid_distribution_items
aid_beneficiaries 1──N aid_vouchers
aid_vouchers 1──N aid_voucher_redemptions
aid_distributions 1──N aid_spending_tracking
aid_programs 1──N aid_donor_reports
aid_programs 1──N aid_sanctions_screening_logs
aid_beneficiaries 1──N aid_verification_records
```

## Table Definitions

### aid_programs
| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UUID | PK | Program identifier |
| ngo_id | VARCHAR(50) | NOT NULL, FK → ngo.id | Owning NGO |
| name_ar | TEXT | NOT NULL | Arabic name |
| name_en | TEXT | NOT NULL | English name |
| description_ar | TEXT | | Arabic description |
| description_en | TEXT | | English description |
| program_type | ENUM('mpc','cct','voucher','mixed') | NOT NULL | Type of assistance |
| currency | VARCHAR(3) | NOT NULL DEFAULT 'USD' | ISO 4217 |
| budget | DECIMAL(15,2) | NOT NULL | Total program budget |
| budget_used | DECIMAL(15,2) | NOT NULL DEFAULT 0 | Funds disbursed so far |
| budget_unspent | DECIMAL(15,2) | GENERATED ALWAYS AS (budget - budget_used) | Computed remaining |
| distribution_rules | JSONB | NOT NULL | Rules: amount, frequency, conditions |
| start_date | DATE | NOT NULL | Program start |
| end_date | DATE | NOT NULL | Program end |
| status | ENUM('draft','active','paused','completed','cancelled') | NOT NULL DEFAULT 'draft' | Current status |
| created_by | VARCHAR(50) | NOT NULL | User ID |
| created_at | TIMESTAMPTZ | NOT NULL DEFAULT NOW() | |
| updated_at | TIMESTAMPTZ | NOT NULL DEFAULT NOW() | |

**Indexes:** `(ngo_id, status)`, `(status, end_date)`, `(program_type)`

---

### aid_beneficiaries
| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UUID | PK | Beneficiary identifier |
| program_id | UUID | NOT NULL, FK → aid_programs.id | Enrolled program |
| program_beneficiary_id | VARCHAR(20) | UNIQUE, NOT NULL | Human-readable ID (e.g., BNF-8293-001) |
| full_name_encrypted | BYTEA | NOT NULL | AES-256-GCM encrypted full name |
| unhcr_id_encrypted | BYTEA | | AES-256-GCM encrypted UNHCR registration ID |
| phone_encrypted | BYTEA | | AES-256-GCM encrypted phone number |
| phone_hash | VARCHAR(64) | NOT NULL | SHA-256 hash for dedup lookups |
| governorate | VARCHAR(50) | NOT NULL | Governorate of residence |
| district | VARCHAR(50) | | District of residence |
| family_size | INT | NOT NULL | Number of household members |
| head_of_household | BOOLEAN | DEFAULT TRUE | Is this person the HH head? |
| special_needs | BOOLEAN | DEFAULT FALSE | Disability, elderly, etc. |
| biometric_template_finger | BYTEA | | Encrypted fingerprint template |
| biometric_template_face | BYTEA | | Encrypted face template |
| sanctions_status | ENUM('cleared','pending_review','blocked') | NOT NULL DEFAULT 'pending_review' | Sanctions screening result |
| sanctions_reviewed_by | VARCHAR(50) | | Compliance officer who reviewed |
| sanctions_reviewed_at | TIMESTAMPTZ | | Review timestamp |
| status | ENUM('active','inactive','suspended','blacklisted') | NOT NULL DEFAULT 'active' | Current status |
| enrolled_at | TIMESTAMPTZ | NOT NULL DEFAULT NOW() | |
| created_by | VARCHAR(50) | NOT NULL | |

**Indexes:** `(program_id, status)`, `(phone_hash)`, `(sanctions_status)`, `(governorate, district)`

**Encryption Strategy:** All PII columns use AES-256-GCM with per-field unique IV. Key rotation supported via key version column. Decryption only possible within the backend service; database-level access sees only binary data.

---

### aid_distributions
| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UUID | PK | Distribution identifier |
| batch_id | VARCHAR(30) | UNIQUE, NOT NULL | Human-readable batch ID (e.g., DIST-JUN2026-001) |
| program_id | UUID | NOT NULL, FK → aid_programs.id | Program being distributed |
| distribution_type | ENUM('mpc','cct','voucher') | NOT NULL | Distribution type |
| total_beneficiaries | INT | NOT NULL | Target count |
| successful_count | INT | DEFAULT 0 | Successful credits |
| failed_count | INT | DEFAULT 0 | Failed credits |
| total_amount | DECIMAL(15,2) | NOT NULL | Total value distributed |
| status | ENUM('pending','processing','completed','partial','failed') | NOT NULL DEFAULT 'pending' | |
| idempotency_key | VARCHAR(64) | UNIQUE | Prevents double-distribution |
| triggered_by | VARCHAR(50) | NOT NULL | User who triggered |
| started_at | TIMESTAMPTZ | | |
| completed_at | TIMESTAMPTZ | | |
| created_at | TIMESTAMPTZ | NOT NULL DEFAULT NOW() | |

**Indexes:** `(program_id, status)`, `(batch_id)`, `(created_at)`

---

### aid_distribution_items
| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UUID | PK | |
| distribution_id | UUID | NOT NULL, FK → aid_distributions.id | Parent distribution |
| beneficiary_id | UUID | NOT NULL, FK → aid_beneficiaries.id | Target beneficiary |
| amount | DECIMAL(15,2) | NOT NULL | Amount credited |
| wallet_transaction_id | VARCHAR(50) | FK → core wallet tx | Beza wallet transaction ref |
| status | ENUM('pending','success','failed') | NOT NULL DEFAULT 'pending' | |
| error_message | TEXT | | Failure reason |
| retry_count | INT | DEFAULT 0 | |
| processed_at | TIMESTAMPTZ | | |

**Indexes:** `(distribution_id, status)`, `(beneficiary_id)`, `(wallet_transaction_id)`

---

### aid_vouchers
| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UUID | PK | |
| voucher_code | VARCHAR(16) | UNIQUE, NOT NULL | 12-digit code (XXXX-XXXX-XXXX) |
| pin_hash | VARCHAR(64) | NOT NULL | Argon2 hash of 4-digit PIN |
| program_id | UUID | NOT NULL, FK → aid_programs.id | |
| beneficiary_id | UUID | NOT NULL, FK → aid_beneficiaries.id | |
| original_value | DECIMAL(10,2) | NOT NULL | Face value of voucher |
| remaining_balance | DECIMAL(10,2) | NOT NULL | Remaining spendable amount |
| item_list | JSONB | NOT NULL | Approved items with max quantities |
| expiry_date | DATE | NOT NULL | Expiry after which voucher is void |
| status | ENUM('active','partially_redeemed','fully_redeemed','expired','cancelled') | NOT NULL DEFAULT 'active' | |
| issued_by | VARCHAR(50) | NOT NULL | |
| issued_at | TIMESTAMPTZ | NOT NULL DEFAULT NOW() | |

**Indexes:** `(voucher_code)`, `(beneficiary_id, status)`, `(expiry_date, status)`

---

### aid_voucher_redemptions
| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UUID | PK | |
| voucher_id | UUID | NOT NULL, FK → aid_vouchers.id | |
| merchant_id | VARCHAR(50) | NOT NULL, FK → merchants.id | Redeeming merchant |
| items_redeemed | JSONB | NOT NULL | Array of {item_id, quantity, unit_price, total} |
| total_deducted | DECIMAL(10,2) | NOT NULL | Amount deducted from voucher |
| merchant_settlement_id | VARCHAR(50) | | Settlement reference |
| settlement_status | ENUM('pending','settled','failed') | NOT NULL DEFAULT 'pending' | |
| redeemed_at | TIMESTAMPTZ | NOT NULL DEFAULT NOW() | |

**Indexes:** `(voucher_id)`, `(merchant_id, redeemed_at)`, `(settlement_status)`

---

### aid_spending_tracking
| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UUID | PK | |
| beneficiary_id | UUID | NOT NULL, FK → aid_beneficiaries.id | |
| program_id | UUID | NOT NULL, FK → aid_programs.id | |
| transaction_id | VARCHAR(50) | UNIQUE, NOT NULL | Wallet transaction reference |
| merchant_id | VARCHAR(50) | | Merchant where spent |
| amount | DECIMAL(10,2) | NOT NULL | Transaction amount |
| mcc_code | VARCHAR(4) | | Merchant Category Code |
| category | ENUM('food','rent','health','education','transport','utilities','other') | NOT NULL | Spending category |
| governorate | VARCHAR(50) | | Location of spend |
| transaction_date | DATE | NOT NULL | |
| created_at | TIMESTAMPTZ | NOT NULL DEFAULT NOW() | |

**Indexes:** `(beneficiary_id, transaction_date)`, `(program_id, category)`, `(category, transaction_date)`

---

### aid_donor_reports
| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UUID | PK | |
| report_reference | VARCHAR(30) | UNIQUE, NOT NULL | e.g., RPT-ECHO-Q2-2026 |
| ngo_id | VARCHAR(50) | NOT NULL | |
| program_id | UUID | FK → aid_programs.id | |
| report_type | ENUM('disbursement','spending','reconciliation','comprehensive') | NOT NULL | |
| period_from | DATE | NOT NULL | |
| period_to | DATE | NOT NULL | |
| report_data | JSONB | NOT NULL | Full report payload |
| generated_by | VARCHAR(50) | NOT NULL | |
| generated_at | TIMESTAMPTZ | NOT NULL DEFAULT NOW() | |
| downloaded_at | TIMESTAMPTZ | | Last download |
| format | VARCHAR(10) | | json / pdf / csv |

**Indexes:** `(ngo_id, report_type, period_from)`, `(generated_at)`

---

### aid_verification_records
| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UUID | PK | |
| beneficiary_id | UUID | NOT NULL, FK → aid_beneficiaries.id | |
| agent_id | VARCHAR(50) | NOT NULL | Field agent user ID |
| verification_method | ENUM('biometric','unhcr_id','manual_fallback') | NOT NULL | |
| biometric_score | DECIMAL(5,2) | | Match percentage (0-100) |
| status | ENUM('verified','failed','pending_review') | NOT NULL | |
| offline_sync_id | VARCHAR(64) | | ID for offline queue dedup |
| verification_photo | BYTEA | | Encrypted verification photo |
| gps_coordinates | POINT | | Location of verification |
| verified_at | TIMESTAMPTZ | NOT NULL DEFAULT NOW() | |

**Indexes:** `(beneficiary_id, verified_at)`, `(agent_id)`, `(status)`

---

### aid_sanctions_screening_logs
| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | UUID | PK | |
| beneficiary_id | UUID | NOT NULL, FK → aid_beneficiaries.id | |
| screening_type | ENUM('initial','periodic','manual') | NOT NULL | |
| lists_checked | TEXT[] | NOT NULL | ['un','eu','ofac'] |
| match_score | DECIMAL(5,2) | NOT NULL | Highest match score |
| match_detail | JSONB | | Which list(s) matched, names, reasons |
| resolution | ENUM('cleared','false_positive','confirmed_match','escalated') | | |
| reviewed_by | VARCHAR(50) | | |
| reviewed_at | TIMESTAMPTZ | | |
| screened_at | TIMESTAMPTZ | NOT NULL DEFAULT NOW() | |

**Indexes:** `(beneficiary_id)`, `(resolution)`, `(screened_at)`
