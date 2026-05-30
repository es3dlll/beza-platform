# Agent Network Security

## POS Device Binding

### Device Identity
```
Each POS device has a unique identity established during manufacturing/activation:

1. Hardware-Bound Identity:
   - Device serial number (read-only from OS)
   - IMEI (for cellular-enabled devices)
   - MAC address (WiFi + Bluetooth)
   - Android Advertising ID

2. Software-Bound Identity:
   - X.509 client certificate (RSA 2048-bit)
   - Certificate issued by Beza internal CA
   - Certificate stored in Android Keystore (hardware-backed)
   - Certificate includes: device_serial, agent_id, issue_date, expiry_date

3. Device Binding Process:
   a. Agent activation → admin creates device record in agent_devices
   b. POS app generates keypair on first launch (Android Keystore)
   c. CSR (Certificate Signing Request) sent to Beza CA
   d. CA signs certificate → stored in Keystore
   e. All subsequent API calls include client certificate (mutual TLS)
```

### Device Attestation
```
On every login and periodically (every 24h):
  1. App collects device integrity evidence:
     - SafetyNet Attestation (Google Play Integrity)
     - Root detection (Magisk/SuperSU check)
     - Developer options status
     - USB debugging status
     - Package signature verification
  2. Evidence sent to Beza attestation service
  3. If evidence shows tampering (rooted, custom ROM, etc.):
     a. Block login/operation
     b. Send alert: "جهاز غير آمن — يرجى الاتصال بالدعم"
     c. Flag agent for review
```

## Agent Authentication

### Agent PIN
```
PIN Policy:
  - Length: 6 digits (no alphabetic)
  - Initial PIN: randomly generated, sent via SMS
    "رقمك السري المؤقت: 482193 — يرجى تغييره عند أول تسجيل دخول"
  - Forced change on first login
  - No repeating digits >3 (e.g., 111234 rejected)
  - No sequential ascending/descending >3 (e.g., 123456 rejected)

Storage:
  - Hashed with bcrypt (cost factor: 12)
  - Salt: random 16 bytes per PIN
  - Format: $2y$12$...

Rate Limiting:
  - 3 failed attempts → 30-minute lockout
  - 5 failed attempts in 24h → account suspended, manual reactivation
  - Lockout state stored in Redis with TTL

PIN Change:
  - Requires current PIN verification
  - Cannot reuse last 5 PINs (history stored in agent_pin_history)
  - SMS notification on change:
    "تم تغيير الرقم السري. إذا لم تقم بهذا التغيير، اتصل بالدعم فوراً"
```

### Session Management
```
Session Token:
  - JWT (JSON Web Token)
  - Issued at login, valid for 5 hours
  - Refresh: sliding expiration, max 12 hours
  - Claims: agent_id, agent_code, tier, device_serial, ip_address

Session Inactivity:
  - 5 minutes of inactivity → auto-lock POS app
  - Unlock with PIN (not full login)
  - 15 minutes → force logout (requires full login)

Concurrent Sessions:
  - Only 1 active session per agent
  - New login from different device → invalidate old session
  - Notification sent to old device: "تم تسجيل الدخول من جهاز آخر"
```

## Transaction Security

### Biometric Verification
```
Required for cash-out > 500,000 SYP (Bronze/Silver) or > 2,000,000 SYP (Gold/Platinum)

Implementation:
  1. POS checks device biometric capability (fingerprint sensor)
  2. If available: prompt customer to place finger on sensor
  3. Biometric match: device-level (Android BiometricPrompt)
  4. No biometric data stored on Beza servers — purely device-local
  5. On match: send biometric_verified: true in API request
  6. On fail (3 attempts): fallback to PIN + SMS OTP

For Platinum agents (> 5M SYP cash-out):
  - REQUIRED: Biometric + PIN (multi-factor)
```

### Transaction Limits
```
Per-Agent Limits (Tier-Based):
  See 04-product-strategy.md for exact limits

Per-Customer Limits:
  - Daily cash-out: KYC-dependent (wallet module enforces)
  - Per-transaction max: Tier-dependent
  - Cumulative daily check on BOTH agent and customer sides

Velocity Checks:
  - Max 3 cash-outs to same customer within 1 hour
  - Max 10 cash-ins from same customer within 1 hour
  - Max 30 transactions per agent within 1 hour
  - Flag for review if any threshold exceeded
```

### Cash Handover Security
```
Cash-out completion requires agent to confirm cash handover:
  1. Transaction processed → agent float credited
  2. POS shows: "الرجاء تسليم 50,000 ل.س للزبون"
  3. Agent taps "تم التسليم" to complete
  4. If not confirmed within 120 seconds:
     a. POS notification reminder
     b. After 5 min: auto-complete (system assumes delivered)
     c. After 24h: if customer disputes non-receipt, investigation triggered

Cash-in requires physical cash acceptance:
  1. Agent counts cash before entering amount on POS
  2. Training emphasizes counterfeit detection
  3. If counterfeit suspected: agent can cancel transaction
  4. After confirmation: agent bears risk of counterfeit cash
```

## Device-Level Security

### Kiosk Mode
```
POS devices run in kiosk mode (single app):

1. Android Management:
   - Device Policy Controller (DPC) via MDM
   - Lock task mode (android:lockTaskMode="if_whitelisted")
   - Home button disabled (app is the only interface)
   - Recent apps button disabled
   - Notification bar hidden
   - Volume keys disabled (except for accessibility)

2. Network Security:
   - WiFi: company-managed SSID only (or mobile data)
   - VPN: Always-on VPN to Beza network (WireGuard)
   - Firewall: all ports blocked except 443 to beza-api.com
   - DNS: encrypted DNS (DoH) via Beza resolver

3. Storage:
   - Full disk encryption (Android File-Based Encryption)
   - App data in credential-encrypted storage
   - Transaction queue encrypted at rest (AES-256-GCM)
   - Sensitive data (PIN, tokens) in EncryptedSharedPreferences
```

### Tamper Detection
```
Continuous monitoring:

1. Runtime Integrity:
   - Check for debugging (Debug.isDebuggerConnected())
   - Check for emulator (Build.FINGERPRINT, Build.MODEL)
   - Check for root (test-keys, su binary, rooted apps)
   - Check for app repackaging (signature mismatch)

2. On Tamper Detection:
   - Clear all local data (tokens, cache, queue)
   - Show message: "التطبيق في وضع غير آمن — يرجى إعادة التشغيل"
   - Report to backend: agent_id, device_serial, tamper_type, timestamp
   - Backend: flag device, notify security team
```

### Communication Security
```
All communications between POS app and Beza API are secured:

1. Transport:
   - TLS 1.3 minimum
   - Mutual TLS (mTLS) with client certificate
   - Certificate pinning (SHA-256 of public key)
   - HSTS enforced (max-age=31536000)

2. API Security:
   - JWT with short expiry (5h) + refresh
   - Idempotency key for all financial transactions
   - Request signing (HMAC-SHA256 of body + timestamp)
   - Anti-replay: timestamp + nonce in headers

3. Offline Queue Security:
   - Encrypted at rest (AES-256-GCM, key in Android Keystore)
   - Signed with device private key
   - Verified by backend on sync
```

## Audit Logging
```
All security-relevant events are logged:

| Event | Detail | Retention |
|-------|--------|-----------|
| Login attempt (success/fail) | agent_id, device, IP, timestamp | 1 year |
| PIN change | agent_id, timestamp | 1 year |
| Device binding | device_serial, agent_id, certificate | 5 years |
| Suspicious activity | agent_id, type, detail, timestamp | 5 years |
| Transaction reversal | agent_id, txn_id, reason | 5 years |
| Session timeout | agent_id, inactive_duration | 90 days |
| Tamper detection | device_serial, tamper_type | 1 year |
| KYC approval | agent_id, reviewer, decision | 5 years |

Logs stored in: Loki (hot, 30 days) + S3 (cold, 5 years)
```
