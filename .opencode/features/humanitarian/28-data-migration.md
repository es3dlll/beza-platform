# Data Migration Plan

## Migration Scenarios

| Scenario | Description | Priority |
|----------|-------------|----------|
| **M-01** | Initial schema deployment (empty database) | P0 |
| **M-02** | NGO imports existing beneficiary lists (legacy CSV/Excel) | P0 |
| **M-03** | Migrate from paper-based distribution records to digital | P1 |
| **M-04** | Migrate from RedRose/ActivityInfo to Beza humanitarian module | P2 |
| **M-05** | Bulk re-screening of legacy beneficiaries against sanctions lists | P0 |

## Phase 1: Schema Deployment (M-01)

```sql
-- 001_create_aid_schema.sql
CREATE SCHEMA IF NOT EXISTS humanitarian;

-- Enable pgcrypto for AES encryption
CREATE EXTENSION IF NOT EXISTS pgcrypto;

-- aid_programs (see 16-database-schema.md for full DDL)
CREATE TABLE humanitarian.aid_programs (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    -- ... all columns as defined in schema
);

-- aid_beneficiaries with encrypted columns
CREATE TABLE humanitarian.aid_beneficiaries (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    full_name_encrypted BYTEA NOT NULL,
    unhcr_id_encrypted BYTEA,
    phone_encrypted BYTEA,
    phone_hash VARCHAR(64) NOT NULL,
    -- ... other columns
);

-- All other tables...
```

## Phase 2: Legacy Beneficiary Import (M-02)

### CSV Mapping Template

| Legacy Field | Beza Field | Transformation |
|-------------|------------|----------------|
| `Name` | `full_name` | Split into Arabic; transliteration for name_en |
| `UNHCR #` | `unhcr_id` | Strip spaces, uppercase |
| `Mobile` | `phone` | Normalise to +963 format |
| `Governorate (English)` | `governorate` | Map to standardised governorate list |
| `Camp/Site` | `district` | Free text |
| `HH Size` | `family_size` | Integer parse |
| `HoH (Y/N)` | `head_of_household` | Y→true, N→false |
| `Special (Y/N)` | `special_needs` | Y→true, N→false |

### Import Validation Rules

| Rule | Error Message |
|------|---------------|
| Phone must be valid Syrian number (09xx or +963xx) | `Invalid phone format` |
| UNHCR ID must match pattern `SYR-\d{4}-\d{3}` | `Invalid UNHCR ID format` |
| Family size must be 1-20 | `Family size out of range` |
| No duplicate UNHCR IDs in same program | `Duplicate UNHCR ID` |
| First name is required | `Name is required` |

### Import Steps
1. NGO uploads CSV via POST /programs/{id}/beneficiaries
2. System validates all rows (async job)
3. Error report returned with row-by-row issues
4. NGO corrects and re-uploads (incremental — only new/changed rows)
5. Validated rows undergo sanctions screening
6. Cleared beneficiaries enrolled; flagged ones held for manual review

## Phase 3: Paper-to-Digital Transition (M-03)

For NGOs migrating from paper distribution records:

### Data that Can Be Migrated
| Record Type | Example | Migration Method |
|-------------|---------|-----------------|
| Beneficiary name + location | Paper registration forms | Manual CSV entry + agent verification |
| Distribution history | Paper disbursement logs | Bulk import with disbursement date, amount, agent name |
| Verification records | Paper sign-off sheets | Bulk import (noted as "legacy verification") |

### Data that CANNOT Be Migrated
- Biometric templates (must be freshly captured via agent app)
- Spending data (only Beza-tracked transactions are authoritative)
- Sanctions screening results (must be re-run through Beza engine)

### Distribution Continuity
- For active programs, first Beza distribution starts fresh
- Beneficiaries must re-verify via biometric at agent point
- Legacy distribution amounts not counted in Beza reporting (marked as "pre-platform")

## Phase 4: Cross-Platform Migration (M-04)

### RedRose → Beza Mapping

| RedRose Field | Beza Field | Notes |
|---------------|------------|-------|
| `beneficiary_id` | `program_beneficiary_id` | Prefixed with RR- |
| `full_name` | `full_name` | Direct |
| `phone_number` | `phone` | Normalise |
| `location_admin2` | `governorate` | Map RedRose location codes |
| `registration_date` | `enrolled_at` | Preserved |

### ActivityInfo → Beza Mapping

| ActivityInfo Indicator | Beza Equivalent |
|-----------------------|-----------------|
| `Total beneficiaries reached` | COUNT(DISTINCT beneficiary_id) |
| `Cash transferred (USD)` | SUM(amount) FROM aid_distribution_items |
| `% spent on food` | SUM(spending) WHERE category='food' / total |
| `Average transfer value` | AVG(amount) |

## Phase 5: Bulk Sanctions Re-Screening (M-05)

| Step | Description | Timeline |
|------|-------------|----------|
| 1. Export existing beneficiaries | Extract all from legacy systems | Day 1 |
| 2. Normalise names | Arabic → Latin, remove diacritics | Day 1-2 |
| 3. Screen against UN/EU/OFAC | Batch process through sanctions engine | Day 2-3 |
| 4. Review matches | Compliance team reviews flagged names | Day 3-7 |
| 5. Enrol cleared beneficiaries | Insert into aid_beneficiaries | Day 7 |

## Rollback Plan

| Step | Action | Trigger |
|------|--------|---------|
| 1 | Disable humanitarian feature flag | Any critical production issue |
| 2 | Restore pre-migration database snapshot | Data integrity issue |
| 3 | Revert API routes to previous version | API incompatibility |
| 4 | Notify NGOs of rollback | After rollback completed |

## Migration Validation

| Check | Method | Pass Criteria |
|-------|--------|---------------|
| Beneficiary count matches | Compare source system vs Beza | 100% match |
| No duplicate entries | SQL query on phone_hash + program_id | 0 duplicates |
| Sanctions screening complete | Count beneficiaries by sanctions_status | 100% screened or pending_review |
| Distribution amount integrity | SUM(amount) matches expected | ±0.01% tolerance |
