# V3 Scope — Beza Platform Expansion (Tier D)

**Target:** Months 17–24 | **Products:** Financing (Murabaha, Qard Hasan, Micro-Enterprise, BNPL), Education, Humanitarian, Open Finance | **Platform:** i18n كردي/أرمني

**Prerequisite:** V2 production-stable for 14 days, CreditScore API ready, ≥12 months transaction history

---

## V3 TIER D (Month 17–24)

### 1 — محاذاة V3 والامتثال

- [x] 04-v3-scope.md created
- [x] Financing backend enhanced: Murabaha, Qard Hasan, BNPL, credit scoring, admin dashboard
- [x] Financing routes extended with schedule, BNPL checkout, admin dashboard
- [x] Financing tests: 4 passing (apply→approve→disburse→repay)
- [ ] openapi.yaml V3 tags (financing, education, humanitarian, open-finance, developer)
- [ ] Ledger impact matrix — Financing (principal/markup), Education (fee collection), Humanitarian (budget debit)
- [ ] Missing journeys: 10–15 from user-journeys-index
- [ ] CI jobs: financing, education, humanitarian, openfinance
- [ ] Feature flags: `financing_enabled`, `education_enabled`, `humanitarian_enabled`, `open_api_enabled`
- [ ] CBS lending license / bank partnership agreement
- [ ] Sharia Board approval for financing products + BNPL
- [ ] Ministry of Education integration framework
- [ ] UN/NGO agreements (WFP/UNHCR) + OFAC general license

### 2 — التمويل والائتمان (Chapters 6–8 in product prioritization)

- [x] Credit scoring engine (transaction volume, regularity, KYC tier, account age, fraud history)
- [x] Murabaha: cost-plus pricing, installment schedule (principal + markup)
- [x] Qard Hasan: 0% interest, short term, low ceiling
- [x] Micro-Enterprise: wallet cashflow check via credit score
- [x] Loan lifecycle: pending → under_review → approved → disbursed → active → completed / defaulted
- [x] BNPL: 3/6 installments at merchant QR checkout endpoint
- [x] Late payment: Sharia-compliant penalty calculation
- [x] Admin: dashboard (NPL ratio, portfolio stats, loans by status)
- [x] Rejection when score below threshold (300)
- [ ] Auto-offer limits + rejection reasons (AR/EN)
- [ ] Auto-debit on due date + SMS reminder + grace period
- [ ] Exposure limits per user + per product
- [ ] Fraud ML integration for loan applications
- [ ] CBS monthly loan reporting
- [ ] Admin: approval queue, override, write-off, rescheduling
- [ ] Mobile: product catalog, application wizard, loan detail, repayment, BNPL checkout
- [ ] E2E: apply murabaha → approve → disburse → repay via CFE

### 3 — التعليم

- [ ] Institution → Student → Fee hierarchy
- [ ] Fee inquiry (student ID / registration number) + partial pay + receipt number
- [ ] ERP integration adapter (CSV bulk import Phase 1)
- [ ] Events: StudentRegistered, FeePaid — connected to Notification
- [ ] Institution portal: dashboard, batch upload, collection reports, overdue students
- [ ] Admin: education institutions CRUD
- [ ] Mobile: institution search → student → fee → pay PIN → PDF receipt
- [ ] Due date reminders (push/SMS)
- [ ] No sensitive grade storage — fees only
- [ ] Receipt with QR for verification at school
- [ ] E2E: pay school fee (mock institution in staging)

### 4 — المساعدات الإنسانية

- [ ] Organization → Program → Disbursement + budget tracking
- [ ] Batch disbursement for thousands of beneficiaries (queue chunked)
- [ ] Voucher vs cash-to-wallet — dual path
- [ ] Auto-decrement budget on DisbursementCompleted
- [ ] Agent pickup for beneficiaries without phone (SMS code)
- [ ] OFAC/UN screening per batch beneficiary list
- [ ] Beneficiary data encryption, NGO-only access
- [ ] Donor reports (UN format CSV)
- [ ] NGO portal: create program, upload beneficiary list, monitor disbursement
- [ ] Beza compliance approval before activation
- [ ] Mobile: aid notification, program details (no sensitive donor data)
- [ ] Agent pickup / immediate use
- [ ] Admin: active program monitoring, 90% budget alert
- [ ] E2E: batch 100 beneficiaries + UN template report

### 5 — Open Finance وبوابة المطورين

- [ ] OAuth2: Application → Consent → Token (scopes: read_balance, initiate_payment, …)
- [ ] TTL 2h + refresh token rotation + revocation
- [ ] User consent screen in app (Arabic scopes)
- [ ] Payment Initiation API (P2P, bulk, merchant)
- [ ] Account Information API (balance, transactions paginated)
- [ ] Wallet API (create for B2B partners — with consent)
- [ ] Webhooks: HMAC signed, retry, idempotency
- [ ] Rate limiting per developer tier
- [ ] Sandbox environment (sandbox.beza.app) — fake data, no real money
- [ ] Test/live API keys separated
- [ ] Developer portal: docs (OpenAPI render), playground, keys, webhooks log
- [ ] Developer onboarding: company KYC + API agreement
- [ ] CMT/data privacy — third-party data policy
- [ ] CBS sandbox quarterly reports
- [ ] E2E: sandbox app → OAuth → initiate mock payment → webhook received

### 6 — Flutter V3

- [ ] Features: financing, education, humanitarian (beneficiary), developer_consent
- [ ] Router + deep links (education receipt, OAuth consent)
- [ ] Financing: product catalog, application wizard, loan detail, repayment, BNPL checkout
- [ ] Education: institution search, fee inquiry, partial pay, receipt
- [ ] Humanitarian: benefit notification, program info (read-only)
- [ ] OAuth consent screen (Open Finance)
- [ ] Integration tests for financing + education flows
- [ ] RTL for all new text

### 7 — Admin V3 + بوابات B2B

- [ ] Financing ops: approval queue, NPL dashboard, product config
- [ ] Education: institution CRUD, fee monitoring
- [ ] Humanitarian: program oversight, budget alerts
- [ ] Open Finance: developers, API keys, revocation, usage metrics
- [ ] Reports: NPL ratio, school collection rate, NGO disbursement SLA
- [ ] Institution portal (education)
- [ ] NGO portal (humanitarian)
- [ ] Developer portal (static site or separate)

### 8 — تدويل موسّع

- [ ] app_ku.arb — Kurdish translations for all V3 texts
- [ ] app_hy.arb — Armenian translations for all V3 texts
- [ ] Native speaker review
- [ ] USSD/SMS templates in all 3 languages

### 9 — QA والأمان V3

- [ ] Feature + integration tests: Financing, Education, Humanitarian, OpenFinance
- [ ] E2E HTTP: full loan, school fee, humanitarian batch 50, OAuth sandbox payment
- [ ] Regression V1+V2 (daily smoke suite)
- [ ] Pen test: OAuth, institution upload, NGO bulk files
- [ ] OWASP API Security Top 10 on Open Finance
- [ ] Sharia + AML review for financing flows
- [ ] Humanitarian batch 10K beneficiaries < 30 min
- [ ] Open API 100 RPS sustained

### 10 — إطلاق V3

- [ ] Phased: Education → Humanitarian (pilot NGO) → Financing (limited geography) → Open Finance (sandbox public → production keys)
- [ ] Feature flags per governorate
- [ ] Runbooks: default loan, NGO batch failure, API key compromise
- [ ] 08-production-readiness.md updated for Tier D
- [ ] KPIs: loan book, fee collection GMV, API partners, beneficiaries served
- [ ] 14 days production per enabled product
- [ ] CBS quarterly Open Finance report
- [ ] Kickoff V4 planning: Marketplace (ADR-007), Takaful — no execution in V3

---

## NOT IN V3 (V4+)

| Item                            | Reason                               |
| ------------------------------- | ------------------------------------ |
| Marketplace / third-party store | ADR-007 → V4+                        |
| Crypto / stablecoin             | Regulatory rejection — v1 exclusions |
| P2P lending between strangers   | Strategic rejection — v1 exclusions  |
| Takaful / Investments           | Tier E (24+ months)                  |
| Core iOS/Android features       | Completed in V2                      |

---

## V3 KPIs

| Metric                              | Target               | Measurement                        |
| ----------------------------------- | -------------------- | ---------------------------------- |
| Loan book volume                    | SYP 500M by M24      | Total disbursed                    |
| NPL ratio                           | <5% at M24           | Overdue >90 days / total portfolio |
| Education fee GMV                   | SYP 50M/month by M24 | Monthly collection volume          |
| Beneficiaries served (Humanitarian) | 10,000 by M24        | Unique beneficiaries               |
| Open API partners                   | 5 by M24             | Active developer apps              |
| i18n coverage                       | 100% of V3 screens   | Kurdish + Armenian                 |
