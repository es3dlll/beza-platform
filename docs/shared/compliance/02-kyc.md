# Know Your Customer (KYC) Framework

> Single source of truth for KYC levels, requirements, and verification methods across ALL Beza Platform features.

## KYC Levels

| Level | Name | Daily Limit | Monthly Limit | Wallet Balance |
|-------|------|-------------|---------------|----------------|
| 0 | Unverified | 0 SYP | 0 SYP | Receive only, max 100,000 SYP |
| 1 | Basic | 50,000 SYP | 500,000 SYP | 200,000 SYP |
| 2 | Verified | 500,000 SYP | 5,000,000 SYP | 5,000,000 SYP |
| 3 | Full | 5,000,000 SYP | 50,000,000 SYP | No limit |

## Level 0 — Unverified

### Requirements
- Phone number (verified via SMS OTP)
- Full name (self-declared)
- Date of birth (self-declared)

### Capabilities
- Receive transfers (up to 100,000 SYP total wallet balance)
- View transaction history
- Cannot send transfers
- Cannot use agent services

### Upgrade To Level 1
- Prompt user to complete basic KYC
- Required before first send or cash-out

## Level 1 — Basic

### Requirements
| Requirement | Verification Method | Notes |
|-------------|-------------------|-------|
| Full name | Government ID OCR | Matched against ID document |
| Date of birth | Government ID OCR | Matched against ID document |
| National ID number | Government ID OCR | Syrian national ID or passport |
| Phone number | SMS OTP | Already verified at L0 |
| Selfie | Liveness check | Photo matching ID document |
| Address (city only) | Self-declared | Verified in L2 |

### Accepted Documents
| Document Type | Countries | Expiry Check |
|--------------|-----------|-------------|
| Syrian National ID (بطاقة شخصية) | Syria | Must be valid |
| Syrian Passport (جواز سفر) | Syria | Must be valid (>6 months) |
| Syrian Driver's License | Syria | Must be valid |
| Iraqi National ID | Iraq | Must be valid |
| Lebanese National ID | Lebanon | Must be valid |
| UNHCR Refugee ID | All | Must be valid |
| Residence Permit | Host country | Must be valid (>3 months) |

### Verification Flow
```
1. User selects document type
2. User captures photo of document front (and back if ID)
3. OCR extracts text fields
4. Facial image cropped from document
5. User captures selfie (liveness detection)
6. Face comparison: selfie vs document photo (threshold: 0.85 similarity)
7. All data reviewed by automated rules
8. If pass → Level 1 granted
9. If fail → Manual review queue or user retry
```

## Level 2 — Verified

### Requirements
| Requirement | Verification Method | Notes |
|-------------|-------------------|-------|
| All Level 1 requirements | — | Must already be L1 |
| Proof of address | Utility bill / bank statement | Must be <3 months old |
| Full address details | Verified document | Street, building, city |
| Source of income | Self-declared + supporting | Employment, business, remittances |
| Occupation | Self-declared | Matched against declared income |
| PEP status | Self-declared + sanctions check | Yes/No question |

### Accepted Address Documents
| Document | Requirements |
|----------|-------------|
| Utility bill (electricity, water) | Dated within 3 months, name matches ID |
| Bank statement | Dated within 3 months, name matches ID |
| Rental contract | Signed, notarized if possible |
| Government letter | Official letterhead, stamped |
| Employer letter | Official letterhead, signed |
| Phone bill | Dated within 3 months, name matches ID |

### Verification Flow
```
1. User uploads proof of address (PDF or photo)
2. Automated extraction: name, address, date
3. Name matched against Level 1 name (threshold: 0.9)
4. Date checked: must be within 3 months
5. If automated checks pass → Level 2 granted
6. If automated checks uncertain → Manual review queue
7. All Level 2 users → Sanctions screening re-run
```

## Level 3 — Full

### Requirements
| Requirement | Verification Method | Notes |
|-------------|-------------------|-------|
| All Level 2 requirements | — | Must already be L2 |
| In-person verification | Agent visit or branch visit | Physical ID check |
| Biometric registration | Fingerprint at agent location | Or iris scan where available |
| Source of funds declaration | Documented | Bank statements, business records |
| Enhanced Due Diligence (EDD) | Compliance interview | For high-risk profiles |
| Beneficial ownership | Declaration | For business accounts |

### When Level 3 Is Required
- User requests daily limit > 500,000 SYP
- User requests monthly limit > 5,000,000 SYP
- Cumulative transaction volume > 50,000,000 SYP
- User is a PEP or associated with PEP
- User is a legal entity / business
- User flagged by AML rules and subsequently cleared
- Agent or merchant registration

### EDD Requirements
```
EDD Checklist:
□ Source of wealth documentation (bank statements, property deeds, business records)
□ Source of funds for initial deposit
□ Business purpose and expected transaction profile
□ Organizational structure (for entities)
□ Ultimate beneficial owner(s) identified
□ Politically Exposed Person check
□ Enhanced ongoing monitoring (quarterly review)
□ Senior management approval for onboarding
```

## Document Handling

### Document Storage
```sql
CREATE TABLE kyc_documents (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id         UUID NOT NULL REFERENCES users(id),
    document_type   TEXT NOT NULL,        -- 'national_id', 'passport', 'utility_bill', ...
    document_side   TEXT,                 -- 'front', 'back', 'single'
    file_path       TEXT NOT NULL,        -- S3 key
    file_hash       TEXT NOT NULL,        -- SHA-256
    file_size       INTEGER NOT NULL,     -- bytes
    mime_type       TEXT NOT NULL,        -- 'image/jpeg', 'application/pdf'
    ocr_data        JSONB,               -- Extracted fields
    status          TEXT NOT NULL DEFAULT 'pending',  -- 'pending' | 'approved' | 'rejected'
    rejection_reason TEXT,
    reviewed_by     UUID,                 -- Compliance user ID
    reviewed_at     TIMESTAMPTZ,
    expires_at      TIMESTAMPTZ,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
```

### Storage Requirements
| Document Type | Max Size | Accepted Formats | Retention |
|--------------|----------|-----------------|-----------|
| ID/Passport | 10 MB | JPEG, PNG, PDF | 10 years |
| Selfie | 5 MB | JPEG, PNG | 10 years |
| Proof of address | 10 MB | JPEG, PNG, PDF | 10 years |
| Supporting docs | 20 MB | PDF | 10 years |

### Document Quality Requirements
```
Image requirements:
- Minimum resolution: 1280x720
- Maximum resolution: 4096x3072
- File format: JPEG (quality > 80%) or PNG
- Lighting: Even, no glare or shadows
- Document: Fully visible, all 4 corners in frame
- No obstructions: Fingers, watermarks, stickers
- No filters or editing
- Timestamp and GPS metadata stripped before storage
```

## Data Retention

### Retention Schedule
| Data Type | Retention Period | Action After Period |
|-----------|-----------------|-------------------|
| KYC documents (approved) | 10 years after account closure | Secure deletion |
| KYC documents (rejected) | 5 years after rejection | Secure deletion |
| Selfie images | 10 years after account closure | Secure deletion |
| Biometric data | 10 years after account closure | Secure deletion |
| KYC audit logs | 10 years | Archive |
| EDD records | 10 years after account closure | Secure deletion |

### Deletion Process
```php
class KycDataRetentionJob
{
    public function handle(): void
    {
        $cutoff = now()->subYears(10);

        // Get users whose accounts were closed 10+ years ago
        $expiredUsers = User::onlyTrashed()
            ->where('deleted_at', '<=', $cutoff)
            ->where('kyc_data_retained', true)
            ->get();

        foreach ($expiredUsers as $user) {
            DB::transaction(function () use ($user) {
                // 1. Delete files from S3
                $documents = KycDocument::where('user_id', $user->id)->get();
                foreach ($documents as $doc) {
                    Storage::disk('s3-kyc')->delete($doc->file_path);
                    $doc->delete();
                }

                // 2. Anonymize user record
                $user->update([
                    'full_name' => 'REDACTED',
                    'pin_hash' => 'REDACTED',
                    'kyc_level' => 0,
                    'kyc_data_retained' => false,
                ]);

                // 3. Log deletion
                Log::channel('compliance')->info('KYC data deleted', [
                    'user_id' => $user->id,
                    'deleted_at' => now(),
                ]);
            });
        }
    }
}
```

## KYC Statuses

| Status | Meaning | User Can Transact? |
|--------|---------|-------------------|
| `not_started` | User hasn't begun KYC | At Level 0 limits |
| `in_progress` | User has submitted documents, awaiting review | At current level |
| `pending_review` | Automated checks uncertain, manual review needed | At current level |
| `approved` | KYC verified | At new level limits |
| `rejected` | Documents failed verification | At current level |
| `expired` | Documents expired (e.g., passport) | Limited until re-verified |
| `suspended` | KYC suspended by compliance | No transactions |
| `appealed` | User appealed rejection | Under review |

## Automated Verification Rules

### ID OCR Validation
```php
class IdOcrValidator
{
    public function validate(array $ocrData, string $documentType): ValidationResult
    {
        // Rule 1: National ID number format
        if ($documentType === 'national_id') {
            if (!preg_match('/^\d{11}$/', $ocrData['national_id'])) {
                return ValidationResult::fail('Invalid national ID format (expected 11 digits)');
            }

            // Rule 2: Checksum validation (Syrian ID algorithm)
            if (!$this->validateSyrianIdChecksum($ocrData['national_id'])) {
                return ValidationResult::fail('National ID checksum failed');
            }
        }

        // Rule 3: Date of birth is reasonable (13-100 years old)
        $dob = Carbon::parse($ocrData['date_of_birth']);
        if ($dob->age < 13 || $dob->age > 100) {
            return ValidationResult::fail('Date of birth out of acceptable range');
        }

        // Rule 4: Document expiry check
        $expiry = Carbon::parse($ocrData['expiry_date']);
        if ($expiry->isPast()) {
            return ValidationResult::fail('Document is expired');
        }

        return ValidationResult::pass();
    }
}
```

### Liveness Detection
| Method | Technology | Threshold | Fallback |
|--------|-----------|-----------|----------|
| Active liveness | User blinks + turns head | Pass after 3/3 actions | 2 retries |
| Passive liveness | Single photo, AI analysis | Deepfake probability < 0.1 | Manual review |
| Video liveness | Short video selfie | 5 second video, mouth movement | 2 retries |

### Face Comparison
| Matcher | Threshold | False Accept Rate | False Reject Rate |
|---------|-----------|-------------------|-------------------|
| Face match (selfie vs ID) | 0.85 similarity | < 1% | < 5% |
| Face match (L3 in-person) | 0.90 similarity | < 0.5% | < 3% |
