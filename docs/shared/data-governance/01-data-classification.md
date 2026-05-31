# Data Classification — Beza Platform

## Classification Matrix

| Category | Examples | Classification | Access | Encryption Required | Retention |
|----------|----------|---------------|--------|---------------------|-----------|
| **PII** | Full name, phone number, national ID (رقم وطني), date of birth, address, mother's name, email, signature image, national ID photo (front/back), passport copy, proof of address document | **Restricted** | Compliance team, User self-view only | AES-256 at rest (separate encrypted DB from transactional data); TLS 1.3 in transit; field-level encryption for national ID | 10 years after account closure (see data-retention) |
| **Financial** | Wallet balance (SYP, USD, EUR), transaction history, fee history, FX conversion records, remittance history, agent commission records, merchant settlement records, bank account numbers (if linked) | **Confidential** | Operations team (support, tx search), Compliance team (AML monitoring), User self-view; DevOps has encrypted access for DB maintenance only | AES-256 at rest; TLS 1.3 in transit; database-level TDE; access logging mandatory | 10 years (CBS requirement — see data-retention) |
| **KYC / Verification** | Tier 2 verification selfie, ID photo, utility bill images, proof of business registration, income source declaration, bank statement, CR number, tax ID | **Restricted** | Compliance team only; User self-view (limited to own documents); Operations cannot view — only see verification status (Verified/Unverified) | AES-256 at rest in segregated KYC storage (separate physical DB or S3 bucket with separate key); TLS 1.3; document access logged per-view | 10 years after account closure |
| **Authentication** | Password hash (bcrypt), PIN hash, OTP hash, biometric template (device-side only), session tokens | **Critical** | No human access; system-only (hash comparison); DevOps can rotate keys but never read plaintext | bcrypt for passwords/PINs (cost factor 12); HMAC-SHA256 for tokens; never store plaintext OTP; biometric data never stored server-side | PIN/password: retained until user change; Session tokens: until expiry; OTPs: purged after 5 minutes or successful use |
| **Device** | Device ID (Android ID/Advertising ID), device model, OS version, IP address, user-agent, SIM card serial (ICCID), phone IMEI, network operator (Syriatel/MTN), GPS location (when agent search used) | **Internal** | Security team, AI team (aggregate/anonymized only), Fraud detection (automated rules) | AES-256 at rest; IP addresses: retain only last 3 octets after 30 days; GPS: anonymized to 1km grid after 24 hours | Device fingerprint: 30 days after last login; IP logs: 90 days; Location data: 30 days |
| **Behavioral** | Screen tap coordinates, navigation paths, feature usage frequency, session duration, feature abandonment rate, scroll depth, form field dwell time, USSD menu navigation logs | **Internal** | AI/product team (aggregate/anonymized, no link to PII); cannot be used for individual profiling without explicit consent | Pseudonymized (user ID replaced with session hash); no PII linkage in analytics DB; AES-256 at rest | 24 months (product improvement purposes); aggregated reports retained indefinitely (no PII) |
| **Communications** | In-app chat messages, support ticket messages, SMS logs, email correspondence, call recordings (if support call) | **Confidential** | Support team (ticket context), Compliance team (dispute evidence), User self-view (own messages) | AES-256 at rest; TLS 1.3; chat messages encrypted end-to-end between user and support | Chat messages: 2 years; Support tickets: 5 years; Call recordings: 1 year |
| **Agent / Merchant** | Agent shop name, agent location (address + GPS), agent phone, agent national ID, shop photo, agent commission rate, merchant CR number, merchant tax ID, merchant settlement bank account | **Confidential** | Operations team, Compliance team, Agent network team; Public: agent name + location only (map view) | AES-256 at rest; TLS 1.3; agent GPS location: public map view shows approximate location (±50m) | Agent records: 5 years after deactivation; Merchant records: 7 years after deactivation |
| **Public** | Agent locations (approximate), FX rates (official CBS rate + Beza reference rate), agent working hours, merchant business name + category, Beza promotion content, app download link, help center articles | **Public** | Everyone (no authentication required for public API endpoints) | TLS 1.3 for API access; no encryption at rest needed (public by design) | Indefinite (public information) |
| **Audit Logs** | Admin actions (user suspend, transaction reversal, commission change, FX rate override, agent approval/rejection), compliance actions (SAR filing, account freeze), system configuration changes | **Confidential** | Compliance team, Security team, Internal Audit; read-only access; tamper-proof storage | AES-256 at rest; write-once-read-many (WORM) storage; SHA-256 hash chain for tamper detection | 7 years (see data-retention) |

---

## Encryption Standards

| Classification | Storage Encryption | Key Management | Transport Encryption |
|---------------|-------------------|----------------|---------------------|
| Restricted | AES-256-GCM with separate KMS key per environment (dev/staging/prod) | AWS KMS or HashiCorp Vault; key rotation every 90 days; separate CMK for PII and KYC | TLS 1.3 minimum; HSTS enabled; mutual TLS for inter-service communication |
| Confidential | AES-256-GCM; database-level TDE enabled; field-level encryption for high-value fields (e.g., bank account numbers) | AWS KMS; key rotation every 180 days | TLS 1.3 minimum; certificate pinning for mobile app API calls |
| Internal | AES-256-GCM; database-level TDE | AWS KMS; key rotation every 365 days | TLS 1.2 minimum |
| Critical | Argon2id for passwords; bcrypt (cost 12) for PINs; HSMs for token signing keys | AWS CloudHSM or equivalent; keys never in plaintext in memory | TLS 1.3; additional request signing with HMAC |
| Public | No encryption required | N/A | TLS 1.2 minimum for API |

---

## Access Control Rules

| Classification | Principle | Approval Required | Review Cadence | Audit Trail |
|---------------|-----------|------------------|----------------|-------------|
| Restricted | Least privilege; zero default access; need-to-know basis | Data owner + Compliance Officer approval required | Quarterly access review | Every read logged (who, what, when, why) |
| Confidential | Least privilege; role-based access | Data owner approval | Monthly access review | Every read logged |
| Internal | Role-based access | Team lead approval | Quarterly access review | Bulk access logging (API call count per user per day) |
| Critical | No human access (system-only hash comparison) | Security lead; key ceremony for key rotation | N/A (no human access) | Key access and rotation events logged |
| Public | Open access | N/A | N/A | Standard API logging only |

---

## Syria-Specific Regulatory Notes

- **CBS Law No. 23/2005** (Non-Banking Financial Institutions): All financial data must be stored on servers physically located within Syria. Beza will maintain primary DB in Damascus DC, with encrypted backup in Aleppo DR site.
- **CBS Circular on E-Money (2021)**: Transaction records must be retained for 10 years in a format that CBS can audit. Records must be recoverable within 5 business days of CBS request.
- **AML Law No. 31/2010**: KYC documents must be retained for 10 years after account closure. Beneficial ownership data must be maintained for all legal entity merchants.
- **CMT Data Privacy Law (draft, 2023)**: Expected to require: (a) explicit consent for data processing, (b) data minimization, (c) right to deletion (subject to CBS retention requirements), (d) cross-border data transfer restrictions.
- **Syria Electronic Transactions Law No. 17/2012**: Electronic records are admissible as evidence; digital signatures recognized.
- **Sanctions Compliance**: US OFAC sanctions and EU sanctions apply; data may not be stored on servers in sanctioned countries; cloud providers must not be US/EU sanctioned entities (prefer local hosting or providers without sanctions exposure).
