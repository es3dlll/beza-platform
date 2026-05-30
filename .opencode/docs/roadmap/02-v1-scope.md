# V1 Scope — Beza Platform Launch

**Target:** Months 1–6 | **Markets:** Damascus, Aleppo, Latakia, Homs | **Users:** 50,000 target by M6

---

## V1 CORE (Month 1–6)

### Wallet Module
- [x] Multi-currency wallet (SYP, USD)
- [x] P2P transfer by phone number (SYP and USD)
- [x] Balance check (app + USSD `*123#`)
- [x] Transaction history (30 days in-app, 90 days via support)
- [x] Top-up via agent cash-in (SYP only, USD at licensed agents)
- [x] Withdraw via agent cash-out
- [x] QR code receive (generate personal QR for P2P)
- [x] Profile: full name, phone, national ID number (رقم وطني), date of birth
- [x] Wallet limits: Tier 1 (basic, unverified): SYP 500,000 max balance, SYP 250,000 daily tx
- [x] Wallet limits: Tier 2 (verified): SYP 5,000,000 max balance, SYP 2,000,000 daily tx
- [x] Wallet limits: Tier 3 (premium): SYP 50,000,000 max balance, SYP 10,000,000 daily tx
- [x] Self-service language toggle (Arabic / English)
- [x] PIN set and change (6-digit)
- [x] Phone number change flow (SMS OTP verification)

### Agent Network Module
- [x] Agent registration form (name, shop name, location, phone, national ID)
- [x] Agent onboarding app (Android only, iOS TBD)
- [x] Agent cash-in: customer gives cash → agent confirms → wallet credited
- [x] Agent cash-out: customer requests → agent gives cash → wallet debited
- [x] Agent commission dashboard (per-transaction, daily summary)
- [x] Agent balance management (float top-up via bank transfer)
- [x] Agent geo-location (map view for users to find nearest agent)
- [x] Agent transaction limit: SYP 20,000,000 daily float per agent
- [x] Agent KYC: shop photo, owner national ID, utility bill (water or electricity), proof of business registration
- [x] Agent suspension/block capability (Compliance team)

### FX Module
- [x] SYP ↔ USD conversion inside wallet
- [x] Daily rate sync from CBS official feed (manual fallback if CBS API unavailable)
- [x] Dual rate display: "Official CBS rate" and "Reference market rate"
- [x] FX confirmation screen with rate lock (15-second hold)
- [x] FX daily limit: SYP 5,000,000 equivalent per user (Tier 2), SYP 20,000,000 (Tier 3)
- [x] FX transaction fee: 1.5% of converted amount
- [x] FX rate refresh every 30 minutes
- [x] USD balance can be received from remittance only (no direct USD cash-in at agents in V1)

### Fraud Prevention Module (V1)
- [x] Real-time transaction screening for ALL wallet transactions (transfer, cash-in, cash-out, bill pay, merchant QR)
- [x] Rule-based engine: 50+ rules (velocity, amount thresholds, device fingerprint, location anomaly)
- [x] ML model scoring: LightGBM trained on Syria-specific fraud patterns (SIM swap, mule accounts, agent fraud)
- [x] Risk scoring: 0–1000 scale, threshold configurable per product (wallet=700, remittance=600, merchant=750)
- [x] Automatic block: risk score > 900 → transaction declined + alert to compliance team
- [x] Manual review queue: risk score 700–900 → flagged for ops team review within 15 min
- [x] False positive feedback loop: confirmed FP → retrain model within 24h
- [x] Device fingerprinting: collect device ID, IP, GPS, SIM card, app version on every session
- [x] Velocity rules: >5 transactions/minute → block, >3 failed PIN attempts → 30min lock
- [x] Agent fraud detection: float mismatch > 10% → alert, same-device multi-agent login → block
- [x] Mule account detection: graph-based (same device + different users → investigation)
- [x] Fraud operations dashboard: real-time alerts, case management, decision (block/allow/review)
- [x] Fraud reporting: daily fraud summary, weekly trend analysis, monthly CBS fraud report
- [x] Integration with Compliance module: fraud case → AML screening → SAR if > 1M SYP

### Remittance Module
- [x] Inbound corridors: Lebanon, UAE, Jordan, Germany (via partner MTOs)
- [x] Payout in SYP (to SYP wallet balance) or USD (to USD wallet balance if Tier 2+)
- [x] Sender details: full name, phone, remittance purpose (from dropdown: family support, education, medical, other)
- [x] Reference number generation (12-digit alphanumeric)
- [x] SMS notification to recipient: "تحويل وارد من [name] بقيمة [amount]. الرقم المرجعي: [ref]"
- [x] Remittance fee: 3% for SYP payout, 4% for USD payout (includes FX conversion if needed)
- [x] Daily remittance receipt limit: SYP 10,000,000 equivalent
- [x] Monthly remittance receipt limit: SYP 50,000,000 equivalent
- [x] Sender verification: passport or national ID copy required for first transfer from each corridor
- [x] Compliance screening against OFAC/Syria sanctions list (automated name matching)
- [x] Beneficiary relationship declaration (family, friend, business)
- [x] Lebanon corridor priority: fastest route (Beirut-Damascus same-day settlement via partner bank)

### Bill Payment Module
- [x] Syriatel prepaid mobile top-up (SYP 500–50,000)
- [x] Syriatel postpaid bill payment (invoice number entry)
- [x] MTN prepaid mobile top-up (SYP 500–50,000)
- [x] MTN postpaid bill payment (invoice number entry)
- [x] Public Electricity bill (PEED — المؤسسة العامة للكهرباء) — meter number entry
- [x] Water bill (Damascus Water Authority / Aleppo Water Authority)
- [x] Landline phone bill (Syria Telecom)
- [x] Bill payment history (last 10 bills)
- [x] Auto-fill biller details from previous payments
- [x] Scheduled payment (up to 7 days ahead)
- [x] Receipt generation (PDF with QR verification)
- [x] SMS confirmation after successful payment
- [x] Late payment fee display before confirmation
- [x] Bill inquiry failure handling: "الخدمة غير متاحة حالياً. يرجى المحاولة لاحقاً" with retry option

### Merchant QR Module
- [x] Merchant registration form (business name, CR number, tax ID, location, owner national ID)
- [x] Static QR code generation per merchant (SYP only in V1)
- [x] Customer scans merchant QR → enters amount → confirms → payment sent to merchant wallet
- [x] Merchant receives payment notification with customer name and amount
- [x] Merchant daily transaction report
- [x] Merchant settlement: D+1 to merchant wallet balance
- [x] Merchant QR print-out generation (PDF, A5 size)
- [x] Merchant transaction limit: SYP 5,000,000 per transaction
- [x] Merchant QR linked to merchant wallet (not to personal wallet)
- [x] Refund capability (merchant-initiated, within 7 days)
- [x] Merchant dashboard: total sales, daily sales, average ticket size

### Authentication & Security (V1)
- [x] Phone number + SMS OTP login
- [x] PIN (6-digit) for transaction authorization
- [x] Biometric login (fingerprint / face) on supported devices
- [x] Session timeout after 10 minutes of inactivity
- [x] Device binding: max 2 devices per account
- [x] SIM card change detection → force re-login + re-verify
- [x] Rate limiting: 5 failed PIN attempts → 30-minute lock
- [x] Rate limiting: 10 failed OTP attempts → account temporary suspension (requires support unblock)
- [x] Transaction confirmation screen always shows: amount, fee, total, recipient, timestamp
- [x] Suspicious transaction detection: >3x usual transaction amount triggers additional verification

### Compliance Module (V1)
- [x] Tier 1 onboarding: name, phone, national ID number (self-declared)
- [x] Tier 2 verification: national ID card photo (front + back), selfie with ID, proof of address (utility bill)
- [x] Tier 3 verification: Tier 2 + income source declaration, bank statement or business registration
- [x] AML screening against CBS sanctions list (updated monthly)
- [x] OFAC/Sanctions screening for remittance senders
- [x] Transaction monitoring: automatic flag for >SYP 1,000,000 single transaction
- [x] Transaction monitoring: automatic flag for >3 transactions/day >SYP 500,000 each
- [x] Suspicious Activity Report (SAR) generation for Compliance team review
- [x] Daily compliance report to CBS (aggregate transaction volumes, flagged items)
- [x] KYC document storage (encrypted, separate from transactional database)

### USSD (`*123#`)
- [x] Balance inquiry
- [x] Mini-statement (last 5 transactions)
- [x] Agent locator (returns nearest 3 agents by SMS)
- [x] Bill payment inquiry (enter biller code + account number)
- [x] PIN change
- [x] Language toggle (Arabic/English via `*123*2#`)
- [x] All USSD menus in Arabic by default
- [x] USSD session timeout: 30 seconds
- [x] USSD menu depth: max 3 levels

### Notifications (V1)
- [x] SMS: credit notification, debit notification, OTP, bill payment confirmation
- [x] In-app push: same as SMS (when app is open)
- [x] Daily SMS summary: end-of-day balance if any transaction occurred
- [x] Weekly SMS marketing (opt-out supported): new agent locations, promotions
- [x] SMS sender ID: "Beza"
- [x] SMS delivery via Syriatel and MTN direct SMPP connection

---

## V1 ADMIN / OPERATIONS

### Operations Dashboard
- [x] User management: view, suspend, unsuspend, delete (soft)
- [x] Agent management: view, approve, suspend, commission override
- [x] Transaction search (by user phone, transaction ID, date range, amount range)
- [x] Transaction reversal (Ops team only, requires 2-person approval)
- [x] FX rate override (manual rate entry if CBS feed is down)
- [x] Biller management: add, remove, update biller API endpoints
- [x] Alert configuration: set thresholds for transaction volume, agent float, failed logins
- [x] Report generation (CSV): daily transactions, agent commissions, FX volume, new users
- [x] System health dashboard: API uptime, SMS delivery rate, USSD success rate
- [x] User wallet adjustment (Ops, requires compliance approval for >SYP 1,000,000)

### Support Module
- [x] Ticket creation (user complaint, dispute, inquiry)
- [x] Ticket categories: transaction dispute, account issue, agent issue, bill payment error, FX dispute, other
- [x] Ticket assignment to support agent
- [x] Transaction dispute workflow: user claims wrong debit → support reviews → refund or reject
- [x] User search by phone, national ID, wallet ID
- [x] Support agent notes (internal only)
- [x] Ticket status: Open → In Progress → Resolved → Closed
- [x] Support response SLA: 4 hours for payment disputes, 24 hours for general inquiries

---

## NOT IN V1

- [ ] Savings goals (e.g., "Save for Umrah", "Save for school fees") → V1.5 (Month 8+)
- [ ] Savings interest/profit distribution (mudaraba) → V1.5
- [ ] Automated payroll disbursement for employers → V1.5
- [ ] Payroll reports for employer (salary certificates) → V1.5
- [ ] Agent-to-agent float transfer → V1.5
- [ ] Automated settlement reconciliation → V1.5
- [ ] Merchant QR with dynamic amount (customer scans phone) → V1.5
- [ ] Credit scoring based on wallet history → V2
- [ ] SME financing (murabaha) → V2
- [ ] Buy Now Pay Later (BNPL) → V2
- [ ] Physical prepaid card → V2 (depends on CBS card approval)
- [ ] Virtual card for online payments → V2
- [ ] ATM cash withdrawal via card → V2
- [ ] Loyalty points / cashback program → V2
- [ ] Government tax collection → V2 (MoF MoU required)
- [ ] Government fee collection (passport, civil registry) → V2
- [ ] School fee payment → V3
- [ ] University registration fee payment → V3
- [ ] UNHCR/WFP humanitarian cash transfers → V3
- [ ] NGO cash-for-work disbursement → V3
- [ ] E-commerce marketplace → V3
- [ ] Third-party merchant storefront → V3
- [ ] Open API for third-party developers → V3
- [ ] P2P lending marketplace → V3
- [ ] Insurance products (takaful) → V3
- [ ] Investments / crowdfunding → V3
- [ ] iOS app (only Android in V1) → V1.5 (Month 9 target)
- [ ] Apple Pay / Google Pay integration → V2
- [ ] WhatsApp chatbot support → V1.5
- [ ] In-app customer support chat → V1.5
- [ ] Self-service account deletion → V2
- [ ] Subscription/standing order payments → V1.5
- [ ] QR code generation for bill payments → V2
- [ ] Wallet-to-bank transfer (SYP) → V1.5
- [ ] Wallet-to-bank transfer (USD) → V2
- [ ] Cash deposit via ATM → V2
- [ ] Multi-language support beyond Arabic/English (e.g., Kurdish, Armenian) → V3
- [ ] Accessibility mode for visually impaired → V2
- [ ] Offline transaction mode (store-and-forward) → V2
- [ ] NFC tap-to-pay → V2 (requires card module)
- [ ] Crypto/stablecoin wallet → V3

---

## V1 EXCLUSIONS — EXPLICIT REJECTIONS

These are not planned for any future version, due to regulatory or strategic reasons:
- [ ] Bitcoin/cryptocurrency trading (CBS prohibition on crypto)
- [ ] Gambling-related transactions (illegal in Syria)
- [ ] Alcohol or tobacco sales via merchant QR (regulated goods, not fintech scope)
- [ ] P2P lending between strangers (too high risk, regulatory unclear)
- [ ] Foreign currency cash-in at agents (USD cash handling requires special CBS license — reconsider in V2)
