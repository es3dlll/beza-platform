# Screen Inventory — Beza Platform

> **Last Updated:** 2026-05-29
> **Owner:** Product Team
> **Scope:** Flutter Mobile App, React Web Admin, USSD, Agent POS

---

## 1. Mobile App Screens (Flutter)

### 1.1 Core / Auth Module

| # | Screen | Route | Module | States | Auth Required |
|---|--------|-------|--------|--------|--------------|
| 1 | Splash | `/splash` | Core | loading → logged_in → logged_out | No |
| 2 | Onboarding | `/onboarding` | Core | carousel_1 → carousel_2 → carousel_3 → dismiss | No |
| 3 | Language Selection | `/onboarding/language` | Core | arabic → english → kurdish | No |
| 4 | Permission Granting | `/onboarding/permissions` | Core | sms → location → contacts → camera | No |
| 5 | Phone Entry | `/auth/phone` | Auth | empty → invalid → sending_otp → rate_limited | No |
| 6 | OTP Verification | `/auth/otp` | Auth | waiting → verifying → expired → verified → max_attempts | No |
| 7 | PIN Creation | `/auth/pin/create` | Auth | entering → confirming → mismatch → success | No |
| 8 | PIN Login | `/auth/pin/login` | Auth | entering → verifying → locked → reset | Yes |
| 9 | Biometric Prompt | `/auth/biometric/enable` | Auth | available → denied → enabled → skip | Yes |
| 10 | Forgot PIN | `/auth/pin/reset` | Auth | phone → otp → new_pin → success | No |
| 11 | Account Locked | `/auth/locked` | Auth | timer → support_contact → retry | No |

### 1.2 Wallet / Home Module

| # | Screen | Route | Module | States | Auth Required |
|---|--------|-------|--------|--------|--------------|
| 12 | Home (Dashboard) | `/home` | Wallet | loaded → empty → offline → error → maintenance | Yes |
| 13 | Balance Card | (widget on Home) | Wallet | visible → hidden → shimmer → error | Yes |
| 14 | Quick Actions | (widget on Home) | Wallet | 4 actions: Transfer, Cash-out, Pay, Bills | Yes |
| 15 | Recent Transactions | (widget on Home) | Wallet | loaded → empty → error → shimmer | Yes |
| 16 | Notification Bell | (widget on Home) | Wallet | has_unread → no_unread → loading | Yes |
| 17 | Full Transaction History | `/transactions` | Wallet | loaded → empty → error → paginating | Yes |
| 18 | Send Money | `/transfer/send` | Wallet | contact_pick → amount → review → pin_confirm → success | Yes |
| 19 | Send Money — Contact Picker | `/transfer/send/contacts` | Wallet | phone → contacts → recent → search → no_results | Yes |
| 20 | Send Money — Amount Entry | `/transfer/send/amount` | Wallet | entering → insufficient_balance → daily_limit_exceeded | Yes |
| 21 | Send Money — Confirmation | `/transfer/send/confirm` | Wallet | reviewing → pin_required → processing | Yes |
| 22 | Send Money — Success | `/transfer/send/success` | Wallet | success → share_receipt → new_transfer | Yes |
| 23 | Request Money | `/transfer/request` | Wallet | contact_pick → amount → review → success → share_link | Yes |
| 24 | Transaction Detail | `/transfer/{id}` | Wallet | loaded → not_found → error → pending_confirm | Yes |
| 25 | Transaction Receipt | `/transfer/{id}/receipt` | Wallet | loaded → not_found | Yes |

### 1.3 Agent Module

| # | Screen | Route | Module | States | Auth Required |
|---|--------|-------|--------|--------|--------------|
| 26 | Agent Map | `/agents` | Agent | loading → located → no_agents → error → location_denied | Yes |
| 27 | Agent List | `/agents/list` | Agent | loaded → empty → error → shimmer | Yes |
| 28 | Agent Detail | `/agents/{id}` | Agent | loaded → offline → error → closed | Yes |
| 29 | Agent Cash-in | `/agents/{id}/cashin` | Agent | amount → confirm → pin → success → failure | Yes |
| 30 | Agent Cash-out | `/agents/{id}/cashout` | Agent | amount → confirm → pin → success → failure | Yes |
| 31 | Agent QR Scan | `/agents/scan` | Agent | camera_open → scanning → recognized → error | Yes |
| 32 | Agent Feedback | `/agents/{id}/feedback` | Agent | rating → comment → submit → success | Yes |

### 1.4 Payments & Bills Module

| # | Screen | Route | Module | States | Auth Required |
|---|--------|-------|--------|--------|--------------|
| 33 | Bill Categories | `/bills` | Payments | loaded → empty → error | Yes |
| 34 | Bill Inquiry — Syriatel | `/bills/syriatel` | Payments | phone_input → inquiry → amount_display → pay | Yes |
| 35 | Bill Inquiry — MTN | `/bills/mtn` | Payments | phone_input → inquiry → amount_display → pay | Yes |
| 36 | Bill Inquiry — PEED (Electricity) | `/bills/electricity` | Payments | account_input → inquiry → amount_display → pay | Yes |
| 37 | Bill Inquiry — Water | `/bills/water` | Payments | account_input → inquiry → amount_display → pay | Yes |
| 38 | Bill Payment Confirmation | `/bills/confirm` | Payments | review → pin → processing → success → failure | Yes |
| 39 | Bill Payment History | `/bills/history` | Payments | loaded → empty → error | Yes |
| 40 | Merchant Payment — QR | `/merchant/pay` | Payments | camera → amount → confirm → success | Yes |
| 41 | Merchant Payment — Manual | `/merchant/pay/manual` | Payments | merchant_id → amount → confirm → success | Yes |

### 1.5 Remittance Module

| # | Screen | Route | Module | States | Auth Required |
|---|--------|-------|--------|--------|--------------|
| 42 | Remittance — Initiate | `/remittance/send` | Remittance | country_select → amount → fx_display → beneficiary | Yes |
| 43 | Remittance — Beneficiary | `/remittance/beneficiary` | Remittance | new → existing → search → no_results | Yes |
| 44 | Remittance — Beneficiary Add | `/remittance/beneficiary/new` | Remittance | name → bank_details → id_info → review → submit | Yes |
| 45 | Remittance — Review | `/remittance/confirm` | Remittance | fx_rate → fee → total → pin → submit | Yes |
| 46 | Remittance — Success | `/remittance/success` | Remittance | tracking_id → beneficiary → amount → eta → share | Yes |
| 47 | Remittance — Tracking | `/remittance/{id}/track` | Remittance | submitted → processing → completed → failed | Yes |
| 48 | Remittance — History | `/remittance/history` | Remittance | loaded → empty → error | Yes |

### 1.6 FX Module

| # | Screen | Route | Module | States | Auth Required |
|---|--------|-------|--------|--------|--------------|
| 49 | FX Rates | `/fx/rates` | FX | loaded → error → stale → updating | Yes |
| 50 | FX Converter | `/fx/convert` | FX | source → target → amount → rate → confirm → success | Yes |
| 51 | FX Conversion History | `/fx/history` | FX | loaded → empty → error | Yes |

### 1.7 KYC / Profile Module

| # | Screen | Route | Module | States | Auth Required |
|---|--------|-------|--------|--------|--------------|
| 52 | Profile | `/profile` | Profile | loaded → edit | Yes |
| 53 | Edit Profile | `/profile/edit` | Profile | editing → saving → success → error | Yes |
| 54 | KYC Status | `/profile/kyc` | KYC | not_started → pending → under_review → approved → rejected | Yes |
| 55 | KYC — Upload ID | `/profile/kyc/id` | KYC | capture → upload → analyzing → success → retry | Yes |
| 56 | KYC — Selfie | `/profile/kyc/selfie` | KYC | capture → liveness_check → success → retry | Yes |
| 57 | KYC — Tier 2 Upgrade | `/profile/kyc/tier2` | KYC | form → document_upload → submit → pending | Yes |
| 58 | KYC — Tier 3 Upgrade | `/profile/kyc/tier3` | KYC | in_person_required → schedule → documents → submit | Yes |
| 59 | Change PIN | `/profile/pin/change` | Profile | old_pin → new_pin → confirm → success | Yes |
| 60 | Change Language | `/profile/language` | Profile | arabic → english → kurdish → apply | Yes |
| 61 | Change Theme | `/profile/theme` | Profile | light → dark → system | Yes |
| 62 | Notifications Settings | `/profile/notifications` | Profile | push → sms → email → toggles | Yes |
| 63 | Transaction Limits | `/profile/limits` | Profile | tier_display → daily → weekly → monthly | Yes |
| 64 | About | `/profile/about` | Profile | version → licenses → terms → privacy | No |

### 1.8 Support Module

| # | Screen | Route | Module | States | Auth Required |
|---|--------|-------|--------|--------|--------------|
| 65 | Help Center | `/support` | Support | loaded → search → no_results | No |
| 66 | FAQ | `/support/faq` | Support | categories → questions → answers | No |
| 67 | Contact Support | `/support/contact` | Support | category → message → attachments → submit → ticket_id | Yes |
| 68 | Live Chat | `/support/chat` | Support | connecting → waiting → active → closed → offline | Yes |
| 69 | Dispute Transaction | `/support/dispute/{id}` | Support | reason → details → submit → case_number → tracking | Yes |
| 70 | Support Tickets | `/support/tickets` | Support | loaded → empty → error | Yes |
| 71 | Ticket Detail | `/support/tickets/{id}` | Support | open → agent_replied → waiting_customer → closed → resolved | Yes |

### 1.9 Offline / System Screens

| # | Screen | Trigger | Module | States |
|---|--------|---------|--------|--------|
| 72 | No Internet | System overlay | Core | disconnected → reconnecting → restored |
| 73 | Maintenance | API 503 response | Core | estimated_duration → retry → contact |
| 74 | Force Update | Version mismatch | Core | mandatory → optional → update → skip |
| 75 | Session Expired | Token expired | Auth | auto_logout → login_prompt |
| 76 | Rate Limited | 429 response | Core | retry_after → contact_support |
| 77 | Biometric Prompt | System overlay | Auth | available → denied → enabled → skip |

### 1.10 Widgets / Bottom Sheets / Modals

| # | Component | Type | Purpose |
|---|-----------|------|---------|
| 78 | Balance Card | Widget | Home dashboard header |
| 79 | Quick Actions | Widget | 4-grid action buttons |
| 80 | Recent Transactions | Widget | Last 5 transactions |
| 81 | Transaction Filter | Bottom Sheet | Filter by type, date, status |
| 82 | Contact Picker | Bottom Sheet | Phone & contact selection |
| 83 | PIN Entry | Modal | 4-6 digit PIN overlay |
| 84 | Biometric Auth | Modal | Fingerprint / Face ID |
| 85 | Success Animation | Full-screen | Lottie success animation |
| 86 | Receipt Share | Bottom Sheet | Share via WhatsApp, SMS |
| 87 | Agent Detail Popup | Bottom Sheet | Agent info, directions, call |
| 88 | Bill Category Grid | Widget | Icon-based category grid |
| 89 | FX Rate Ticker | Widget | Scrolling rate banner on Home |
| 90 | Notification Dropdown | Widget | In-app notifications |

---

## 2. Web Admin Screens (React)

### 2.1 Authentication

| # | Screen | Route | Access | States |
|---|--------|-------|--------|--------|
| 1 | Admin Login | `/admin/login` | Public | idle → loading → error → mfa_required → locked |
| 2 | MFA Challenge | `/admin/login/mfa` | Public | totp → sms_code → recovery_code |
| 3 | Forgot Password | `/admin/login/reset` | Public | email → token → new_password → success |
| 4 | Password Change | `/admin/change-password` | All Authenticated | current → new → confirm → success |

### 2.2 Dashboard & Analytics

| # | Screen | Route | Access | States |
|---|--------|-------|--------|--------|
| 5 | Operations Dashboard | `/admin` | Admin | loading → loaded → no_data → error |
| 6 | Real-time Monitor | `/admin/realtime` | Admin, Ops | live_updates → metrics → active_users → tps |
| 7 | Transaction Volume Chart | (widget on Dashboard) | Admin | daily → weekly → monthly → custom_range |
| 8 | System Health | `/admin/health` | Admin, Ops | all_green → warnings → critical → degraded |
| 9 | User Growth Chart | (widget on Dashboard) | Admin | loading → loaded → empty |
| 10 | Agent Performance | (widget on Dashboard) | Admin | top_agents → bottom_agents → avg_cash_in_out |

### 2.3 User Management

| # | Screen | Route | Access | States |
|---|--------|-------|--------|--------|
| 11 | User Search | `/admin/users` | Admin | search → results → no_results → error |
| 12 | User Detail | `/admin/users/{id}` | Admin | loaded → not_found → error |
| 13 | User Wallet | `/admin/users/{id}/wallet` | Admin | balances → transactions → limits → freeze |
| 14 | User KYC | `/admin/users/{id}/kyc` | Admin, Compliance | documents → status → history → notes |
| 15 | User Activity Log | `/admin/users/{id}/activity` | Admin, Compliance | timeline → filters → export |
| 16 | User Devices | `/admin/users/{id}/devices` | Admin | device_list → suspicious → revoke |
| 17 | User Transactions | `/admin/users/{id}/transactions` | Admin | list → filter → export |
| 18 | User Notes | `/admin/users/{id}/notes` | Admin, Compliance | add → edit — history |
| 19 | User Restrictions | `/admin/users/{id}/restrictions` | Admin, Compliance | freeze → limit → flag → release |

### 2.4 KYC Queue

| # | Screen | Route | Access | States |
|---|--------|-------|--------|--------|
| 20 | KYC Queue | `/admin/kyc` | Compliance | pending → in_review → approved → rejected |
| 21 | KYC Review | `/admin/kyc/{id}` | Compliance | documents → liveness → data → approve/reject → notes |
| 22 | KYC Dashboard | `/admin/kyc/dashboard` | Compliance | volume → avg_time → approval_rate → pending_by_tier |
| 23 | KYC Reports | `/admin/kyc/reports` | Compliance | daily → weekly → monthly → export |

### 2.5 Agent Management

| # | Screen | Route | Access | States |
|---|--------|-------|--------|--------|
| 24 | Agent Search | `/admin/agents` | Admin | search → filters → results → no_results → error |
| 25 | Agent Detail | `/admin/agents/{id}` | Admin | profile → performance → floats → transactions |
| 26 | Agent KYC | `/admin/agents/{id}/kyc` | Admin, Compliance | documents → review → approve → reject |
| 27 | Agent Float | `/admin/agents/{id}/float` | Admin, Finance | current → history → top_up → reconcile |
| 28 | Agent Commission | `/admin/agents/{id}/commission` | Admin, Finance | rates → earnings → settlements |
| 29 | Agent Transactions | `/admin/agents/{id}/transactions` | Admin | list → filter → export |
| 30 | Agent Settlement | `/admin/agents/{id}/settlement` | Admin, Finance | pending → processed → failed → retry |
| 31 | Agent Onboarding | `/admin/agents/onboarding` | Admin | new → documents → training → activation |
| 32 | Agent Map View | `/admin/agents/map` | Admin | all_agents → active → inactive → offline → density |
| 33 | Agent Network Dashboard | `/admin/agents/dashboard` | Admin | total → active → avg_txn → float_status |

### 2.6 Transaction Management

| # | Screen | Route | Access | States |
|---|--------|-------|--------|--------|
| 34 | Transaction Search | `/admin/transactions` | Admin | filters → results → no_results → error |
| 35 | Transaction Detail | `/admin/transactions/{id}` | Admin | overview → ledger → timeline → dispute |
| 36 | Transaction Reversal | `/admin/transactions/{id}/reverse` | Admin | reason → confirm → process → result |
| 37 | Pending Transactions | `/admin/transactions/pending` | Admin | holds → retry → expire → force |
| 38 | Failed Transactions | `/admin/transactions/failed` | Admin, Ops | list → reasons → retry → analyze |
| 39 | Transaction Reports | `/admin/transactions/reports` | Admin, Finance | volume → counts → fees → charts → export |

### 2.7 Fraud Operations

| # | Screen | Route | Access | States |
|---|--------|-------|--------|--------|
| 40 | Fraud Cases | `/admin/fraud` | Fraud Ops | open → investigating → confirmed → dismissed |
| 41 | Fraud Case Detail | `/admin/fraud/{id}` | Fraud Ops | transactions → user → rules → evidence → actions |
| 42 | Fraud Rules Engine | `/admin/fraud/rules` | Fraud Ops, Admin | rules → thresholds → actions → test |
| 43 | Fraud Dashboard | `/admin/fraud/dashboard` | Fraud Ops | flagged_count → block_rate → false_positive → trends |
| 44 | Fraud Alerts | `/admin/fraud/alerts` | Fraud Ops | realtime → silent → actioned → ignored |

### 2.8 FX & Rates

| # | Screen | Route | Access | States |
|---|--------|-------|--------|--------|
| 45 | FX Rate Management | `/admin/fx` | Admin | current_rates → source_rates → manual_overrides |
| 46 | FX Rate Override | `/admin/fx/override` | Admin | pair → rate → margin → effective_from → reason |
| 47 | FX Rate History | `/admin/fx/history` | Admin, Finance | changes → who → when → audit_trail |
| 48 | CBS Rate Feed Monitor | `/admin/fx/cbs-feed` | Admin, Ops | last_sync → next_sync → status → errors |

### 2.9 Remittance Operations

| # | Screen | Route | Access | States |
|---|--------|-------|--------|--------|
| 49 | Remittance Queue | `/admin/remittance` | Admin, Ops | pending → processing → completed → failed |
| 50 | Remittance Detail | `/admin/remittance/{id}` | Admin, Ops | sender → beneficiary → amounts → status → timeline |
| 51 | Remittance Partner MTOs | `/admin/remittance/partners` | Admin | list → status → limits → settlement |
| 52 | Remittance Dashboard | `/admin/remittance/dashboard` | Admin, Finance | volume → corridors → avg_time → failure_rate |

### 2.10 Settlement & Finance

| # | Screen | Route | Access | States |
|---|--------|-------|--------|--------|
| 53 | Settlement Queue | `/admin/settlement` | Admin, Finance | pending → processing → completed → failed |
| 54 | Settlement Detail | `/admin/settlement/{id}` | Admin, Finance | batch → transactions → amounts → bank_reference |
| 55 | Bank Account Management | `/admin/settlement/banks` | Admin, Finance | accounts → balances → reconciliation |
| 56 | GL / Ledger Viewer | `/admin/ledger` | Admin, Finance, Compliance | accounts → entries → balance → history |
| 57 | Commission Reports | `/admin/commission` | Admin, Finance | agents → partners → platform → export |

### 2.11 Compliance

| # | Screen | Route | Access | States |
|---|--------|-------|--------|--------|
| 58 | AML Screening Queue | `/admin/compliance/aml` | Compliance | pending → screening → alerted → cleared |
| 59 | PEP/Sanctions Check | `/admin/compliance/pep` | Compliance | results → matches → review → override |
| 60 | Suspicious Activity Reports | `/admin/compliance/sar` | Compliance | draft → submitted → filed → follow_up |
| 61 | SAR Creation | `/admin/compliance/sar/new` | Compliance | case → details → narrative → submit |
| 62 | Compliance Dashboard | `/admin/compliance/dashboard` | Compliance | alerts → sar_count → review_time → trends |
| 63 | Audit Log | `/admin/audit` | Compliance, Super Admin | timeline → filters → user → action → export |

### 2.12 Configuration

| # | Screen | Route | Access | States |
|---|--------|-------|--------|--------|
| 64 | System Config | `/admin/config` | Super Admin | modules → toggles → values → audit |
| 65 | Feature Flags | `/admin/config/features` | Super Admin | flags → rollout → percentage → override |
| 66 | Transaction Limits Config | `/admin/config/limits` | Super Admin | tiers → amounts → periods → currencies |
| 67 | Fee Config | `/admin/config/fees` | Super Admin | transaction_fees → agent_commissions → fx_margins |
| 68 | Notification Templates | `/admin/config/notifications` | Super Admin | sms → push → email → edit → preview |
| 69 | Role & Permissions | `/admin/config/roles` | Super Admin | roles → permissions → matrix → user_assign |
| 70 | Admin User Management | `/admin/config/admins` | Super Admin | list → add → edit → disable → audit |

---

## 3. USSD Screens

| # | Menu | Code | Response | Auth | States |
|---|------|------|----------|------|--------|
| 1 | Main Menu | `*123#` | 1. Balance\n2. Mini-statement\n3. Agents\n4. Bills\n5. PIN Change\n6. Language\n7. Support | PIN | main_menu |
| 2 | Balance | `*123*1#` | Your balance: 45,250 SYP | PIN | balance_display → session_end |
| 3 | Mini-statement | `*123*2#` | 1. +10,000 SYP from Ahmed\n2. -2,500 SYP to Sara\n3. +500 SYP cash-in\n4. -15,000 SYP bill pay\n5. +200 SYP from Omar | PIN | list → more → session_end |
| 4 | Agent Locator | `*123*3#` | Nearest agents:\n1. Damascus Mall (300m)\n2. Arnous Sq (800m)\n3. Bab Touma (1.2km) | No | location → list → session_end |
| 5 | Bill Inquiry | `*123*4#` | 1. Syriatel\n2. MTN\n3. Electricity | PIN | category → account → amount → confirm → session_end |
| 6 | PIN Change | `*123*5#` | Enter old PIN\nEnter new PIN\nConfirm new PIN | PIN | current → new → confirm → success → session_end |
| 7 | Language | `*123*6#` | 1. العربية\n2. English\n3. کوردی | No | select → apply → session_end |
| 8 | Support | `*123*7#` | Call center: 00963-11-XXX-XXXX | No | display → session_end |
| 9 | Cash-in (agent workflow) | `*123*8#` | Enter agent code\nEnter amount\nConfirm | PIN | agent_code → amount → confirm → success |
| 10 | Cash-out (agent workflow) | `*123*9#` | Enter agent code\nEnter amount\nConfirm | PIN | agent_code → amount → confirm → success |

---

## 4. Agent POS Screens (React Native / Web)

| # | Screen | Route | Auth | States |
|---|--------|-------|------|--------|
| 1 | Agent Login | `/pos/login` | Agent PIN | idle → loading → error → locked |
| 2 | POS Dashboard | `/pos` | Session | float_display → quick_actions → recent_txns |
| 3 | Cash-in (Customer) | `/pos/cash-in` | Session | phone → amount → confirm → receipt |
| 4 | Cash-out (Customer) | `/pos/cash-out` | Session | phone → amount → confirm → receipt |
| 5 | Float Top-up Request | `/pos/float/topup` | Session | amount → source → confirm → pending |
| 6 | Float Transfer | `/pos/float/transfer` | Session | to_agent → amount → confirm → success |
| 7 | Transaction History | `/pos/history` | Session | list → filter → detail → export |
| 8 | Transaction Detail | `/pos/history/{id}` | Session | overview → customer → status → receipt |
| 9 | Customer Lookup | `/pos/customer` | Session | phone → name → limited_profile → kyc_status |
| 10 | Agent Profile | `/pos/profile` | Session | info → limits → commission → performance |
| 11 | Agent KYC Status | `/pos/kyc` | Session | status → documents → re_upload |
| 12 | Daily Settlement | `/pos/settlement` | Session | expected → actual → difference → confirm |
| 13 | Reports | `/pos/reports` | Session | daily_summary → commission → transactions |
| 14 | PIN Change | `/pos/pin/change` | Session | current → new → confirm → success |
| 15 | Logout | `/pos/logout` | Session | confirm → session_end |

---

## 5. Screen State Platform Comparison

| State | Flutter | Web Admin | USSD | POS |
|-------|---------|-----------|------|-----|
| **Loading** | Shimmer / CircularProgressIndicator | Skeleton loader | "Please wait..." | Spinner |
| **Empty** | "No transactions yet" + CTA | "No results" | "No data found" | "No records" |
| **Error** | Snackbar / Error state widget | Toast + retry button | "Service unavailable. Try later." | Toast |
| **Offline** | Offline banner + cached data | N/A (online only) | N/A | N/A |
| **Success** | Lottie animation → navigate | Success toast → redirect | "Success" → end session | Receipt print |
| **Rate Limited** | "Too many requests. Try in X min" | Toast | "Try again later" | Toast |
| **Maintenance** | Full-screen overlay | Banner | "System under maintenance" | Banner |
| **Session Expired** | Redirect to login | Redirect to login | Session ended | Redirect to login |
| **Not Found** | "Not found" + navigate back | 404 page | N/A | "Not found" |

---

## 6. Screen-to-Module Mapping Summary

| Module | Flutter Screens | Web Admin Screens | USSD | POS | Total |
|--------|----------------|-------------------|------|-----|-------|
| Core | 6 | — | — | — | 6 |
| Auth | 8 | 4 | — | — | 12 |
| Wallet | 14 | — | 5 | — | 19 |
| Agent | 7 | 10 | 2 | 15 | 34 |
| Payments | 9 | — | 1 | — | 10 |
| Remittance | 7 | 4 | — | — | 11 |
| FX | 3 | 4 | — | — | 7 |
| KYC / Profile | 13 | 4 | — | 1 | 18 |
| Support | 7 | — | 1 | — | 8 |
| Admin | — | 11 | — | — | 11 |
| Config | — | 7 | — | — | 7 |
| Compliance | — | 5 | — | — | 5 |
| Fraud | — | 5 | — | — | 5 |
| Settlement / Finance | — | 5 | — | 1 | 6 |
| System / Offline | 6 | — | — | — | 6 |
| **Total** | **90** | **70** | **10** | **15** | **185** |

---

*End of Screen Inventory. 185+ screens documented across all platforms.*
