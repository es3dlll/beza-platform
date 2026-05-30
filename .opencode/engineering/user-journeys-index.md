# User Journeys Index — Beza Platform V1

This index catalogs all primary user journeys for the Beza mobile money platform, written from the perspective of Syrian users, agents, diaspora senders, merchants, and operations staff. Each journey references a detailed walkthrough in `docs/journeys/`.

Journey files cover: actors, preconditions, happy path, error paths, Syria-specific UX notes, API call sequence, and test scenarios.

---

## Journey Index

| #   | Journey                        | Actor(s)                           | Description                                                                                                                                                                                                                                                                                                                                                     | Syria-Specific Notes                                                                                                                                                                                                                                                                                                                                                                                                                                         | File                                |
| --- | ------------------------------ | ---------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ | ----------------------------------- |
| 1   | **First Time User**            | Unregistered Syrian citizen        | Download app → Enter phone number → Receive OTP via SMS → Create 6-digit PIN → Basic profile → Tier 1 active → First transaction (send 500 SYP)                                                                                                                                                                                                                 | OTP must arrive within 10s on Syriatel/MTN networks; Arabic-first UI; phone number auto-detects +963 prefix; PIN cannot be sequential (123456) or repeated (111111); first transfer is always to a pre-selected demo contact                                                                                                                                                                                                                                 | `journeys/01-first-time-user.md`    |
| 2   | **KYC Upgrade**                | Registered user (Tier 1)           | Navigate to profile → "Upgrade Account" → Upload national ID (front/back) → Take selfie → Submit → Wait for admin review (avg 2 hours) → Approved → Tier 2 active (5M SYP daily limit)                                                                                                                                                                          | National ID must be 11-digit Syrian format; selfie must be live (not photo of photo); front/back must match same ID number; governorate must match ID; selfie illumination check for Syrian skin tones; rejected photos due to reflections on ID card are biggest pain point                                                                                                                                                                                 | `journeys/02-kyc-journey.md`        |
| 3   | **First Transfer**             | Tier 2 verified user               | Open app → "Send Money" → Select contact (phone book sync) → Enter amount (500 SYP) → Review fees → Enter PIN → Confirm → Success screen with receipt → SMS sent to recipient                                                                                                                                                                                   | Recipient name shown after phone lookup (privacy: only first name + first letter of last name); "Send to non-user?" → SMS invite with download link; min 100 SYP; no decimal points (SYP has no sub-unit); transfer note supports Arabic script (140 chars); Idempotency-Key prevents double-send if network drops                                                                                                                                           | `journeys/03-first-transfer.md`     |
| 4   | **Receive Remittance**         | Diaspora sender → Syrian recipient | Diaspora user logs in → "Send to Syria" → Select corridor (USD→SYP) → Enter amount ($200) → See locked rate + fees → Confirm → Pay via card/wallet → Recipient gets SMS ("You received 1,450,000 SYP from Ahmad") → Recipient has options: wallet (instant) or agent pickup (2h)                                                                                | Rate lock for 60 seconds; diaspora sender must pass AML screening (OFAC/EU/UN sanctions); recipient notified in Arabic; agent pickup requires 6-digit pickup code sent via SMS; recipient must present national ID at agent; max $5,000/txn, $20,000/month per sender; source of funds required above $3,000                                                                                                                                                 | `journeys/04-remittance-receive.md` |
| 5   | **Agent Cash-out**             | Verified user → Agent              | User opens app → "Find Agent" → Map shows nearest agents (sorted by distance, shows float available) → User walks to agent → "Cash Out" → Enter amount (50,000 SYP) → Agent scans user's QR code → Agent enters amount on their device → User confirms with PIN on their device → Agent gives cash → Both get SMS receipts                                      | Agent must have sufficient float (float < request amount → agent sees warning, can decline); agent location uses OpenStreetMap (no Google Maps dependency, works offline with cached tiles); cash-out commission: agent earns 0.5% of amount; user pays 0.25% fee; max cash-out: Tier 1 = 500K SYP/day, Tier 2 = 5M SYP/day                                                                                                                                  | `journeys/05-agent-cashout.md`      |
| 6   | **Employee Payroll**           | Employer → Employee                | Employer uploads CSV (phone, amount, notes) → Reviews total → Confirms with company PIN → System disburses to all employees → Each employee gets SMS ("You received 250,000 SYP from ABC Company") → Employees can withdraw via agent or transfer                                                                                                               | CSV format: `phone,amount_syp,note` (Arabic notes supported); min 10 employees per batch; max 500 employees per batch; employer wallet debited total amount + 0.1% processing fee; failed transfers (invalid phone, inactive user) are retried 3x then reported; employer gets end-of-day report CSV; Syria-specific: many employees have no bank account — Beza is their first formal financial service                                                     | `journeys/06-payroll-employee.md`   |
| 7   | **Merchant Payment**           | Customer → Merchant                | Customer at shop → Opens Beza → "Scan QR" → Scans merchant's static QR → Merchant enters amount on POS → Customer sees amount on phone → Confirms with PIN → Success → Merchant gets push notification + SMS → Customer gets digital receipt                                                                                                                    | Static QR: fixed amount (for fixed-price items, e.g. 1,500 SYP sandwich); Dynamic QR: amount set by merchant POS; EMVCo standard QR format; merchant MDR (Merchant Discount Rate) = 1.5% (capped by CBS); customer pays 0% fee; settlement T+1 to merchant bank; works offline: QR scan and PIN entry queued, sent when connection returns                                                                                                                   | `journeys/07-merchant-payment.md`   |
| 8   | **Dispute Resolution**         | Customer → Support Agent           | Customer sees incorrect transaction → "Report Issue" in app → Select transaction → Select reason (wrong amount, not me, fraud, duplicate) → Submit → Support ticket created → Support reviews transaction + device logs + IP → Decision: refund / reject / partial refund → Customer notified via SMS + in-app                                                  | Zero-knowledge evidence: support can see transaction data but not PIN or password; refund goes back to source wallet within 24 hours (admins can do instant refund for critical cases); disputed amounts >100K SYP require supervisor approval; all dispute actions logged to audit trail; Syria-specific: disputes may be caused by network double-sends (rolling back with idempotency key)                                                                | `journeys/08-dispute-resolution.md` |
| 9   | **Fraud Review**               | Operations / Fraud Analyst         | Fraud alert triggered (score > 70) → Case created in admin panel → Analyst opens case → Reviews user history, device fingerprint, IP geo, transaction pattern → Decision: block user / allow transaction (false positive) / flag for monitoring → Action logged → If block, user gets SMS ("Your account has been temporarily suspended. Contact support.")     | Syria-specific rules: (1) New user sending >500K SYP in first 24h → review, (2) Same phone registered on 3+ devices in 24h → block, (3) Agent doing 10+ cash-outs to same user in 1h → review, (4) Transaction from Damascus with device IP in Aleppo in <30min → impossible travel → block; false positive feedback loop: analyst marks "false positive" → model retrains weekly                                                                            | `journeys/09-fraud-review.md`       |
| 10  | **Agent Float Top-up**         | Agent → Admin → Bank               | Agent requests float top-up in app → Select amount (2,000,000 SYP) → Agent initiates bank transfer to Beza's Syrian bank account → Upload transfer receipt → Admin reviews → Confirms receipt → Float credited to agent wallet → Agent gets SMS                                                                                                                 | Bank transfer must be from agent's registered bank account; receipt must show: bank name, transfer date, amount, Beza account number (RBAN/IBAN); float credited within 2 hours during business hours (Sun–Thu, 9am–3pm Syria time); agent can also deposit cash at Beza office for instant float; Syria-specific: cash deposits preferred due to banking sanctions limiting transfers                                                                       | `journeys/10-agent-float-topup.md`  |
| 11  | **Bill Payment**               | Verified user → Biller             | User opens app → "Pay Bills" → Select biller (Electricity / Syriatel / MTN / Water) → Enter account number → System fetches bill → Shows amount due + due date + late fee → User confirms → Enter PIN → Payment sent → Biller confirmation received → Digital receipt                                                                                           | Electricity bill: account number is 10-digit "الرقم الآلي" (automatic number); Syriatel postpaid: 9-digit landline number; MTN prepaid: 10-digit mobile number; late fees: 1% per month for government bills; biller API can be slow (electricity ministry SOAP: 5–15 second response time) — timeout set to 30s with loading spinner; scheduled payments for recurring bills (monthly electricity)                                                          | `journeys/11-bill-payment.md`       |
| 12  | **FX Conversion**              | Tier 2 verified user               | User opens app → "Currency Exchange" → Select source (SYP) → Select target (USD) → Enter amount (1,000,000 SYP) → System shows: CBS rate + Beza spread (1.5%) + fees → Rate locked for 60 seconds → User confirms → Enter PIN → Conversion executed → Wallet updated (SYP debited, USD credited) → Receipt with rate breakdown                                  | CBS rate refreshed daily at 9am Syria time (XML feed from Central Bank's website); Beza spread: Tier 1 = 2%, Tier 2 = 1.5%; max FX volume: Tier 1 = 1M SYP/month, Tier 2 = 10M SYP/month; min conversion: 50,000 SYP; USD cannot be sent to non-Beza user (must convert back to SYP first); rate lock screenshot can be used as proof for CBS audit                                                                                                          | `journeys/12-fx-conversion.md`      |
| 13  | **Settlement Report**          | Finance / Ops Admin                | Admin logs in → "Settlement" → Select date → System generates daily report: (a) Total transactions, (b) Total fees, (c) Agent commissions, (d) Merchant MDR, (e) FX P&L, (f) Net position → Export as CSV → Submit to CBS via API → Email report to finance team                                                                                                | CBS requires: daily transaction volume report in XML format with specific schema (provided by CBS IT department); submission window: T+1 before 12pm Syria time; failure to submit → fine of 50,000 SYP per day (CBS regulation 2023); agent commissions report must list each agent's name, shop name, total transactions, and commission amount for tax purposes (Syrian tax authority requires monthly filing)                                            | `journeys/13-settlement-report.md`  |
| 14  | **Device Change / Lost Phone** | Registered user                    | User calls support → "Lost my phone" → Support verifies identity (name, national ID, last 3 transactions) → Deactivates old device → User downloads app on new phone → Logs in with phone + OTP → Registers new device → Old device auto-logout → User creates new PIN → Access restored                                                                        | Max 2 devices per user; if user already has 2 devices, old one must be deactivated first; OTP sent to SIM card (works even if WhatsApp/Telegram is on lost phone); PIN must be different from previous PIN (no reuse of last 5 PINs); device fingerprint captures: IMEI (Android), advertising ID, model, OS version, screen resolution, language; Syria-specific: phones with dual SIM (Syriatel + MTN) — default SIM detected automatically                | `journeys/14-device-change.md`      |
| 15  | **Account Freeze / Unfreeze**  | Compliance Officer → User          | Compliance detects suspicious activity → Freeze account (user blocked from all transactions) → User gets SMS ("Your account has been temporarily suspended. Call support.") → User calls or visits office → Compliance officer reviews → Decision: unfreeze (false alarm) or escalate (STR to AML commission) → User notified → Account status restored or held | Freeze types: (1) Soft freeze: no send, can receive, (2) Hard freeze: no send or receive, (3) Compliance hold: can't send >100K SYP; freeze reason codes: FRAUD-001 (suspected scam), AML-001 (sanctions match), KYC-001 (expired documents), COMP-001 (court order); unfreeze requires 2-factor approval (compliance officer + supervisor); Syria-specific: court orders can arrive by fax from Syrian magistrate — logged to audit trail with scanned copy | `journeys/15-account-freeze.md`     |

---

## Actor Role Definitions

| Actor                        | Role                              | Privileges                                      | Authentication                         |
| ---------------------------- | --------------------------------- | ----------------------------------------------- | -------------------------------------- |
| **Unregistered User**        | Syrian citizen with phone         | None (outside app)                              | None                                   |
| **Registered User (Tier 1)** | Basic KYC                         | Send/receive up to 500K SYP/day, bill pay       | Phone + OTP + PIN + Device binding     |
| **Registered User (Tier 2)** | Full KYC                          | All Tier 1 + 5M SYP/day, FX, remittance receive | Phone + OTP + PIN + Device binding     |
| **Diaspora Sender**          | Non-resident Syrian or foreigner  | Send remittance to Syria (max $5K/txn)          | Email + password + AML screening       |
| **Agent**                    | Registered cash-in/cash-out point | Cash-in, cash-out, commission dashboard         | Phone + OTP + PIN + Agent app          |
| **Merchant**                 | Registered business               | Receive QR payments, settlement reports         | Phone + OTP + PIN + Merchant dashboard |
| **Employer**                 | Registered business entity        | Payroll disbursement, bulk transfers            | Company credentials + PIN + 2FA        |
| **Admin (Super)**            | Platform operator                 | Full system access, user management, reports    | Email + password + 2FA + IP whitelist  |
| **Compliance Officer**       | AML/KYC team                      | KYC review, freeze/unfreeze, AML screening      | Email + password + 2FA + IP whitelist  |
| **Fraud Analyst**            | Fraud operations team             | Fraud case review, false positive feedback      | Email + password + 2FA + IP whitelist  |
| **Support Agent**            | Customer service                  | Dispute resolution, device change, account help | Email + password + 2FA                 |
| **Finance Ops**              | Settlement & reconciliation       | Settlement reports, CBS reporting, FX P&L       | Email + password + 2FA                 |

---

## Journey Priority Matrix

| Priority | Journey               | Impact              | Effort | Rationale                                     |
| -------- | --------------------- | ------------------- | ------ | --------------------------------------------- |
| P0       | 1: First Time User    | Critical            | Medium | No signup → no users                          |
| P0       | 3: First Transfer     | Critical            | Medium | Core MVP feature                              |
| P0       | 5: Agent Cash-out     | Critical            | High   | Physical cash is still king in Syria          |
| P0       | 2: KYC Upgrade        | Critical            | Medium | Required for Tier 2 limits                    |
| P1       | 4: Receive Remittance | High                | High   | Diaspora remittance is primary revenue driver |
| P1       | 11: Bill Payment      | High                | Medium | Daily utility for Syrian users                |
| P1       | 12: FX Conversion     | High                | Medium | Dual-currency economy (SYP/USD)               |
| P2       | 7: Merchant Payment   | Medium              | Medium | Growing but not MVP                           |
| P2       | 6: Employee Payroll   | Medium              | High   | B2B feature, secondary                        |
| P2       | 8: Dispute Resolution | Medium              | Medium | Trust-building, not volume                    |
| P3       | 9: Fraud Review       | Low (starts simple) | High   | Iterative, starts with rules                  |
| P3       | 14: Device Change     | Low                 | Low    | Edge case                                     |
| P3       | 15: Account Freeze    | Low                 | Low    | Edge case                                     |

---

## Journey Coverage by Module

| Module       | Journeys Covered                                                     |
| ------------ | -------------------------------------------------------------------- |
| Identity     | 1, 2, 14, 15                                                         |
| Wallet       | 3, 5 (funding), 6 (disbursement), 8 (refund)                         |
| Agent        | 5, 10                                                                |
| FX           | 12                                                                   |
| Remittance   | 4                                                                    |
| Bills        | 11                                                                   |
| Merchant     | 7                                                                    |
| Settlement   | 5, 7, 10, 13                                                         |
| Fraud        | 9                                                                    |
| Compliance   | 2, 8, 15                                                             |
| Notification | 1, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 14, 15                           |
| Admin        | 2 (KYC review), 8 (dispute), 9 (fraud), 13 (settlement), 15 (freeze) |
| USSD         | 1 (balance), 3 (mini-statement), 5 (agent locator)                   |

---

## Syria-Specific UX Principles Applied Across All Journeys

1. **Arabic-first**: All interfaces default to Arabic. English is secondary. Numbers are Eastern Arabic numerals (١٢٣) in SMS, Western Arabic (123) in app for financial figures.
2. **SYP formatting**: No decimal places. Space as thousands separator: "١٬٥٠٠٬٠٠٠ SYP" or "1,500,000 SYP".
3. **Network resilience**: All financial API calls are idempotent. Offline mode queues transactions for retry. OTP delivery has SMS + fallback channels.
4. **Low bandwidth**: App assets are <5MB initial download. Images are WebP compressed. API responses use compression.
5. **Device diversity**: Supports Android 8+, 2GB RAM minimum. iOS support is V2.
6. **Sanctions-aware**: No integration with US/EU banks. Hosting in Syria (Damascus DC). No Google Play Services dependency (works without Google Play).
7. **Legal compliance**: CBS regulatory reporting, AML Commission STR filing, tax authority monthly reports, data residency in Syria (all data stored in-country).

---

## Journey File Template

Each journey file in `docs/journeys/` follows this structure:

```
# {Journey Name}
**Actor:** {primary actor}
**Preconditions:** {what must be true before journey starts}
**Trigger:** {what initiates this journey}

## Happy Path
1. {step 1}
2. {step 2}
   - {sub-step detail}
3. {step 3}

## Error Paths
- **E1: {error name}** — {what happens when it goes wrong}
- **E2: {error name}** — {recovery mechanism}

## API Call Sequence
```

{request flow diagram}

```

## Test Scenarios
- TC-01: {scenario description}
- TC-02: {edge case}
```

## Syria-Specific Test Scenarios (Applied to Every Journey)

| Scenario                              | Journeys           | Description                                                                                                          |
| ------------------------------------- | ------------------ | -------------------------------------------------------------------------------------------------------------------- |
| Network timeout during PIN submission | 3, 4, 5, 7, 11, 12 | User enters PIN, network drops — idempotency key prevents double charge on retry                                     |
| Dual SIM phone (Syriatel + MTN)       | 1, 3, 5            | App detects default SIM for SMS; user can switch SIM in settings                                                     |
| Low balance during hold expiry        | 3, 5, 7            | Hold expires after 30 min, release triggers; insufficient balance on retry → user notified                           |
| Agent cash shortage                   | 5                  | Agent has insufficient physical cash; agent declines, user gets "Agent unavailable" with next nearest agent          |
| Power outage / no internet            | 1, 3, 5, 7         | Syrian grid outages (avg 2–4 hrs/day in some areas); offline queue stores last 10 transactions; sync on reconnection |
| CBS rate feed delayed                 | 12                 | XML feed from CBS arrives after 9am; FX quote engine uses previous day's rate with admin-override flag               |
| Sanctions list update                 | 4, 9               | OFAC/EU/UN sanctions list updated; existing sender re-screened; match → compliance hold + admin alert                |
| Court order freeze                    | 15                 | Fax from Syrian magistrate arrives; compliance officer creates freeze order; scanned copy attached to audit trail    |
