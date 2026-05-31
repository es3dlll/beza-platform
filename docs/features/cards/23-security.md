# Cards Security

## PCI-DSS Compliance

### Card Data Protection
```php
// PAN Encryption (AES-256-GCM)
public function encryptPan(string $pan): EncryptedPan
{
    $key = config('beza.security.card_encryption_key');
    $iv = random_bytes(12); // GCM nonce
    $ciphertext = openssl_encrypt(
        $pan, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag
    );
    return new EncryptedPan(
        ciphertext: base64_encode($iv . $ciphertext . $tag),
        hash: hash('sha256', $pan), // For lookups
        suffix: substr($pan, -4),   // Last 4 for display
        bin: substr($pan, 0, 6),    // BIN for routing
    );
}

// CVV: Never stored
// PIN: Only in HSM, never in application memory or database

// Card data scope (what we store):
//   Stored: BIN (first 6), last 4, PAN hash (SHA-256), expiry, cardholder name
//   Encrypted at rest: Full PAN (AES-256-GCM)
//   Never stored: CVV, track data, PIN
//   Tokenized: Apple Pay / Google Pay DPAN (token, not real PAN)
```

### PCI-DSS Control Mapping
| Requirement | Implementation |
|-------------|---------------|
| 3.2 — Don't store sensitive auth data | CVV never stored, track data discarded |
| 3.4 — Render PAN unreadable | AES-256-GCM encryption at rest |
| 3.5 — Protect cryptographic keys | Key in HSM or vault, rotated quarterly |
| 4.1 — Encrypt transmission | TLS 1.3 for API, ISO 8583 over TLS/IPSec |
| 6.6 — Public-facing web app security | WAF, input validation, CSRF, rate limiting |
| 8.3 — Secure authentication | Biometric + PIN for sensitive card operations |
| 10.2 — Audit trails | All card operations logged (who, when, what) |
| 10.6 — Log review | Daily automated log analysis, SIEM integration |
| 11.3 — Penetration testing | Quarterly external + internal, annual ASV scan |
| 12.8 — Service provider oversight | BIN sponsor, TSP, card bureau PCI certified |

## 3DS for Online Transactions

### 3DS Flow
```
Step 1: Merchant initiates 3DS
  Merchant redirects to 3DS ACS (Access Control Server)
  ACS URL: https://3ds.beza.com/acs

Step 2: Cardholder Authentication
  Beza 3DS ACS:
    - Frictionless: User's device fingerprint + transaction risk < threshold
    - Challenge: SMS OTP or in-app biometric verification
  Challenge methods:
    - SMS OTP to registered phone
    - In-app biometric (Face ID / fingerprint) via deep link
    - App-based PIN verification

Step 3: Authentication Response
  ECI: 05 (fully authenticated), 06 (attempted), 07 (not authenticated)
  CAVV: Cardholder Authentication Verification Value
  XID: Extended Identifier

Step 4: Authorization with 3DS Data
  Auth request includes: ECI, CAVV, XID
  Beza CardProcessor: Verify CAVV signature → approve/decline

Step 5: Liability Shift
  If ECI=05 (fully authenticated): liability shifts to issuer
  If ECI=07 (not authenticated): liability remains with merchant
```

### 3DS Rules
```
Frictionless Rules (no challenge needed):
  - Transaction amount < 200,000 SYP
  - Merchant in whitelist (known low-risk merchants)
  - Device fingerprint matches card's registered device
  - Transaction risk score < 30 (AI fraud model)
  - Card registered for > 30 days with clean history

Challenge Required:
  - Amount > 200,000 SYP
  - New merchant (never used before by any Beza user)
  - Device mismatch (different phone/computer than normal)
  - Risk score 30-70
  - Cross-border transaction to high-risk country

Declined (no 3DS available):
  - Risk score > 70
  - Merchant on blacklist
  - Card status not active
```

## Card Fraud Monitoring

### Real-time Rules
```
Rule F1 — Velocity Check
  Threshold: > 5 auth attempts in 60 seconds on same card
  Action: Decline + freeze card temporarily (15 min)

Rule F2 — Amount Anomaly
  Threshold: Single txn > 5x average transaction value for user
  Action: 3DS challenge required

Rule F3 — Geographic Anomaly
  Threshold: Txn from country different from user location in < 1 hour
  Action: Decline unless online purchase with 3DS

Rule F4 — BIN Attack
  Threshold: > 10 auth attempts on different PANs with same BIN in 5 min
  Action: Rate-limit BIN for 15 min

Rule F5 — Card Testing
  Threshold: Multiple small auths ($0.01-$1.00) followed by larger attempt
  Action: Decline small auths, flag card

Rule F6 — ATM Pattern
  Threshold: Same card used at 3+ different ATMs in 1 hour
  Action: 3DS challenge via SMS OTP

Rule F7 — CNP (Card-Not-Present) Rules
  - AVS mismatch: Decline
  - CVV mismatch: Decline + flag
  - Billing address country ≠ IP country: Step-up auth
  - Anonymous IP/VPN: Step-up auth

Rule F8 — Card-Present Rules
  - Offline PIN verification: Required for all POS transactions
  - Chip read failure → fallback to swipe: Decline (force chip)
  - Contactless limit: 100,000 SYP without PIN (cumulative counter)
  - No match between card sequence number and issuer: Decline
```

## Chip / NFC Security

### EMV Chip Security
```
Transaction Types:
  Online Auth (Default): Chip generates ARQC → HSM verifies → online PIN
  Offline Auth (Fallback): Chip verifies offline PIN → SDA/DPA check

ARQC Verification:
  1. Chip generates ARQC using ICC key (unique per card)
  2. HSM verifies ARQC using issuer master key
  3. If ARQC invalid → decline with "Chipped card suspected counterfeit"

CVM (Cardholder Verification Method):
  POS: Online PIN (preferred) or offline PIN
  Contactless: Consumer device verification (phone unlock for Apple Pay)
  ATM: Online PIN mandatory
```

### Apple Pay / Google Pay Security
```
Tokenization:
  FPAN (Funding PAN) → TSP → DPAN (Device PAN)
  DPAN stored in Secure Element (SE) or Device Secure Enclave
  Merchant receives DPAN only, never FPAN

Transaction Security:
  Dynamic CVV: Generated per transaction, valid for that txn only
  Biometric Auth: User must authenticate (Face ID / fingerprint) per payment
  Device Binding: Token locked to specific device

Beza's Responsibilities:
  - Token Service Provider integration (MDES)
  - FPAN → DPAN mapping
  - Token transaction authorization
  - Token lifecycle (suspend/revoke when card frozen/closed)
```
