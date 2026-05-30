# Financial Product Catalog — Beza Platform

> **Document Code:** BZ-FIN-PRD-005
> **Version:** 2.0
> **Status:** Draft
> **Applicable Jurisdiction:** Syrian Arab Republic
> **Regulatory Body:** Central Bank of Syria (CBS) — Law No. 23/2005, Law No. 25/2021
> **Last Updated:** 2026-05-29

---

## Product: P2P Wallet Transfer

| Attribute | Value |
|-----------|-------|
| product_code | WLT-001 |
| product_type | WALLET_TRANSFER |
| currencys | SYP, USD |
| channels | Mobile App, USSD (*123*1#) |
| min_amount | 100 SYP |
| max_amount | Tier 1: 250K SYP/day, Tier 2: 2M SYP/day, Tier 3: 10M SYP/day |
| fee_model | PERCENTAGE + FLAT |
| fee_rules | 0.5% of amount, min 50 SYP, max 5,000 SYP |
| required_kyc | Tier 1 for send up to 250K/day, Tier 2+ for higher |
| ledger_accounts | Sender Wallet (Asset), Receiver Wallet (Asset), Fee Income (Income) |
| settlement_type | INSTANT (on-ledger) |
| reversal_possible | Yes (within 24h, admin only) |
| fraud_screening | Required (all transfers) |
| tax_implications | None (personal transfers, Syrian Income Tax Law Art. 42 exempt) |
| regulatory_ref | CBS Law 23/2005 Art. 72-75; CBS Payment Systems Directive 2022/12 |
| launch_version | V1.0 |
| status | LIVE |

---

## Product: Agent Cash-in

| Attribute | Value |
|-----------|-------|
| product_code | AGT-001 |
| product_type | CASH_IN |
| currencys | SYP only |
| channels | Mobile App (Agent-initiated), Agent POS Terminal |
| min_amount | 500 SYP |
| max_amount | Tier 1: 500K SYP/day, Tier 2: 3M SYP/day, Tier 3: 10M SYP/day |
| fee_model | PERCENTAGE |
| fee_rules | 0.5% of amount, min 100 SYP, max 3,000 SYP (paid by customer) |
| required_kyc | Customer: Tier 1; Agent: Certified Agent license (CBS Agent Banking Directive 2023) |
| ledger_accounts | Agent Float (Liability), Customer Wallet (Asset), Fee Income (Income), Commission Expense (Expense) |
| settlement_type | INSTANT (on-ledger float deduction) |
| reversal_possible | Yes (within 2h, agent + admin approval) |
| fraud_screening | Required (amount > 100K SYP triggers biometric verification) |
| tax_implications | Agent commissions subject to 5% WHT (Syrian Income Tax Law Art. 113) |
| regulatory_ref | CBS Agent Banking Framework 2023/45, AML/CFT Directive 2022/08 |
| launch_version | V1.0 |
| status | LIVE |

---

## Product: Agent Cash-out

| Attribute | Value |
|-----------|-------|
| product_code | AGT-002 |
| product_type | CASH_OUT |
| currencys | SYP only |
| channels | Mobile App (Agent-initiated), Agent POS Terminal |
| min_amount | 1,000 SYP |
| max_amount | Tier 1: 200K SYP/day, Tier 2: 1.5M SYP/day, Tier 3: 5M SYP/day |
| fee_model | PERCENTAGE |
| fee_rules | 1% of amount, min 200 SYP, max 5,000 SYP (paid by customer) |
| required_kyc | Customer: Tier 1; Agent: Certified Agent license |
| ledger_accounts | Customer Wallet (Asset), Agent Float (Liability), Fee Income (Income), Commission Expense (Expense) |
| settlement_type | INSTANT (on-ledger float top-up) |
| reversal_possible | Yes (within 2h, agent + admin approval) |
| fraud_screening | Required (all transactions; cash-out > 50K SYP requires OTP + biometric) |
| tax_implications | Cash handling fee exempt from VAT per Syrian Tax Law No. 24/2003 |
| regulatory_ref | CBS Agent Banking Framework 2023/45; CBS Cash Management Directive 2022/19 |
| launch_version | V1.0 |
| status | LIVE |

---

## Product: FX Conversion

| Attribute | Value |
|-----------|-------|
| product_code | FX-001 |
| product_type | FX_CONVERSION |
| currencys | SYP ↔ USD (additional corridors: EUR, TRY — planned V2.5) |
| channels | Mobile App, USSD, Web Portal |
| min_amount | 10 USD equivalent |
| max_amount | Tier 1: 500 USD/day, Tier 2: 5,000 USD/day, Tier 3: 25,000 USD/day |
| fee_model | SPREAD (buy/sell margin) |
| fee_rules | Buy (SYP→USD): 2% margin; Sell (USD→SYP): 1.5% margin; Tier 3: 50% discount on margin |
| required_kyc | Tier 2 minimum (CBS FX regulations); Tier 3 for amounts > 5,000 USD/day |
| ledger_accounts | SYP Wallet (Asset), USD Wallet (Asset), FX Income (Income), FX Settlement Account (Asset) |
| settlement_type | INSTANT (on-ledger) |
| reversal_possible | No (FX settled irrevocably per CBS FX Directive 2023/03 Art. 12) |
| fraud_screening | Required (enhanced due diligence for > 1,000 USD); suspicious transaction reporting to CBS |
| tax_implications | FX gains subject to 10% capital gains tax per Syrian Investment Law No. 18/2021 |
| regulatory_ref | CBS FX Directive 2023/03; CBS Law 23/2005 Art. 88-95; Syrian Exchange Control Law 2021 |
| launch_version | V1.5 |
| status | LIVE |

---

## Product: Inbound Remittance

| Attribute | Value |
|-----------|-------|
| product_code | REM-001 |
| product_type | INBOUND_REMITTANCE |
| currencys | SYP (disbursement), USD/EUR/TRY (funding corridors) |
| channels | Mobile App (claim), Agent Cash-out (disbursement), Bank Account (via Saraf network) |
| min_amount | 25 USD equivalent |
| max_amount | Corridor A (GCC → SYR): 10,000 USD/transaction; Corridor B (EU → SYR): 5,000 EUR/transaction |
| fee_model | PERCENTAGE + CORRIDOR FLAT |
| fee_rules | GCC corridor: 3% of amount (min 200 SYP, max 10,000 SYP); EU corridor: 4% (min 300 SYP, max 12,000 SYP); TRY corridor: 3.5% |
| required_kyc | Beneficiary: Tier 1 (up to 1,000 USD), Tier 2 (above); Sender: Vetted by originating partner (MOU required) |
| ledger_accounts | Nostro Settlement (Asset), Customer Wallet (Asset), Remittance Income (Income), Partner Commission (Expense) |
| settlement_type | T+0 (on-ledger), T+1 for bank account disbursements |
| reversal_possible | Yes (within 48h, only if unclaimed — SMS notification sent, auto-reverse after 7 days unclaimed) |
| fraud_screening | Required (sender/receiver screening against CBS sanctions list, AML screening per FATF standards) |
| tax_implications | Remittance receipts exempt from Syrian income tax per Legislative Decree 24/2020 Art. 14 |
| regulatory_ref | CBS Cross-Border Remittance Directive 2022/28; FATF Recommendation 16; Syrian AML Law 2021/31 |
| partners | Major GCC exchange houses (Al-Rajhi, UAE Exchange); EU corridor via EMI partners (Satispay, TransferGo — planned) |
| launch_version | V1.0 (GCC corridor), V2.0 (EU corridor) |
| status | LIVE (GCC), IN_DEVELOPMENT (EU, TRY) |

---

## Product: Bill Payment — Telecom

| Attribute | Value |
|-----------|-------|
| product_code | BIL-001 |
| product_type | BILL_PAYMENT |
| currencys | SYP only |
| channels | Mobile App, USSD (*123*2#), Agent |
| billers | Syriatel, MTN Syria |
| min_amount | 100 SYP |
| max_amount | 5,000,000 SYP (Syriatel postpaid); 500,000 SYP (MTN prepaid top-up) |
| fee_model | PERCENTAGE + FLAT |
| fee_rules | 0.5% of bill amount, min 100 SYP, max 2,000 SYP |
| required_kyc | Tier 1 minimum |
| ledger_accounts | Customer Wallet (Asset), Biller Settlement (Liability), Fee Income (Income), Biller Commission (Expense) |
| settlement_type | T+0 to biller (on-ledger, via CBS RTGS for amounts > 5M SYP) |
| reversal_possible | Yes (within 1h if transaction fails to post to biller — reconciliation required) |
| fraud_screening | Required (amount > 500K SYP flagged for manual review) |
| tax_implications | Service fee subject to 10% VAT per Syrian Tax Law No. 24/2003 (amended 2021) |
| regulatory_ref | CBS Bill Payment Framework 2023/32; Syriatel/MTN bilateral agreement |
| launch_version | V1.0 |
| status | LIVE |

---

## Product: Bill Payment — Electricity

| Attribute | Value |
|-----------|-------|
| product_code | BIL-002 |
| product_type | BILL_PAYMENT |
| currencys | SYP only |
| channels | Mobile App, USSD (*123*3#), Agent |
| billers | Public Establishment for Electricity (PEED — all 14 governorate directorates) |
| min_amount | 200 SYP |
| max_amount | 10,000,000 SYP (commercial accounts); 500,000 SYP (residential) |
| fee_model | PERCENTAGE |
| fee_rules | 0.5% of bill amount, min 100 SYP, max 2,000 SYP |
| required_kyc | Tier 1 |
| ledger_accounts | Customer Wallet (Asset), PEED Settlement Account (Liability), Fee Income (Income) |
| settlement_type | T+0 (on-ledger for amounts < 1M SYP); T+1 via CBS ACH for larger amounts |
| reversal_possible | Yes (within 24h, only if PEED confirms non-posting — SLAs defined in PEED MOU) |
| fraud_screening | Required (commercial accounts > 1M SYP require verified meter number) |
| tax_implications | Electricity bills exempt from additional taxation per PEED regulations |
| regulatory_ref | CBS Utility Payment Directive 2023/38; PEED-Bezz MOU 2024/01 |
| launch_version | V1.0 |
| status | LIVE |

---

## Product: Bill Payment — Water

| Attribute | Value |
|-----------|-------|
| product_code | BIL-003 |
| product_type | BILL_PAYMENT |
| currencys | SYP only |
| channels | Mobile App, USSD (*123*4#), Agent |
| billers | Damascus Water Establishment, Aleppo Water Company, other governorate water authorities (11 total) |
| min_amount | 100 SYP |
| max_amount | 200,000 SYP (residential); 2,000,000 SYP (commercial/industrial) |
| fee_model | PERCENTAGE |
| fee_rules | 0.5% of bill amount, min 100 SYP, max 1,000 SYP |
| required_kyc | Tier 1 |
| ledger_accounts | Customer Wallet (Asset), Water Authority Settlement (Liability), Fee Income (Income) |
| settlement_type | T+1 (batch settlement via CBS ACH) |
| reversal_possible | Yes (within 7 days — water authorities process reversals in monthly reconciliation cycles) |
| fraud_screening | Required (commercial accounts flagged for duplicate payment detection) |
| tax_implications | None per Water Authority regulations; municipal fees included in bill amount |
| regulatory_ref | CBS Utility Payment Directive 2023/38; bilateral MOUs with water authorities |
| launch_version | V1.0 |
| status | LIVE |

---

## Product: Merchant QR Payment

| Attribute | Value |
|-----------|-------|
| product_code | MER-001 |
| product_type | MERCHANT_PAYMENT |
| currencys | SYP only (USD QR — planned V2.5) |
| channels | Mobile App (QR scan), Merchant POS (QR display) |
| min_amount | 500 SYP |
| max_amount | Tier 1: 500K SYP/transaction, Tier 2: 2M SYP/transaction, Tier 3: 10M SYP/transaction |
| fee_model | MERCHANT DISCOUNT RATE (MDR) |
| fee_rules | 1% MDR on transaction amount, min 50 SYP, max 5,000 SYP. Micro-merchant (< 5M SYP monthly volume): 0.75% (subsidized) |
| required_kyc | Payer: Tier 1; Merchant: Beza Merchant Agreement + CBS Merchant Registration |
| ledger_accounts | Payer Wallet (Asset), Merchant Settlement Account (Liability), MDR Income (Income), Agent Commission (Expense) |
| settlement_type | INSTANT (on-ledger to merchant wallet); T+1 automatic sweep to merchant bank account |
| reversal_possible | Yes (dispute process: buyer initiates, merchant has 72h to respond; auto-refund if unresolved) |
| fraud_screening | Required (velocity checks > 10 transactions/hour; geolocation anomaly detection) |
| tax_implications | MDR fee subject to 10% VAT; merchants issue e-receipt per Syrian Tax Authority Directive 2024/09 |
| regulatory_ref | CBS Merchant Payment Framework 2024/01; Syrian Tax Authority E-Invoice Directive 2024 |
| launch_version | V1.0 |
| status | LIVE |

---

## Product: Payroll Disbursement

| Attribute | Value |
|-----------|-------|
| product_code | PAY-001 |
| product_type | B2B_PAYROLL |
| currencys | SYP |
| channels | Web Portal (employer upload), API (HR system integration), Mobile App (employee notification) |
| min_amount | 50,000 SYP (total payroll batch) |
| max_amount | Tier 1: 5M SYP/month, Tier 2: 50M SYP/month, Tier 3: 500M SYP/month (negotiable for enterprise) |
| fee_model | PERCENTAGE + BATCH_FLAT |
| fee_rules | 1% of total payroll amount, min 500 SYP, max 20,000 SYP per batch. Enterprise (> 500 employees): 0.75% (negotiated) |
| required_kyc | Employer: Tier 2 (business verification + CBS corporate registration); Employees: Tier 1 |
| ledger_accounts | Employer Wallet (Asset), Employee Wallets (Asset), Payroll Fee Income (Income) |
| settlement_type | T+0 (instant bulk disbursement — accounts debited atomically via ledger batch job) |
| reversal_possible | Partial reversal (admin only): incorrect employee payments reversed within 48h, provided employee wallet has sufficient balance |
| fraud_scheduling | Required (employer identity verification; employee list screening against ghost employee detection algorithm) |
| tax_implications | No withholding by Beza; employer responsible for income tax remittance per Syrian Income Tax Law Art. 45-48 |
| regulatory_ref | CBS B2B Payment Directive 2023/41; Syrian Labor Law No. 17/2010 Art. 56 (wage payment by electronic means) |
| launch_version | V1.0 |
| status | LIVE |

---

## Product: Savings Goal

| Attribute | Value |
|-----------|-------|
| product_code | SAV-001 |
| product_type | SAVINGS |
| currencys | SYP, USD |
| channels | Mobile App |
| min_amount | 1,000 SYP initial deposit; 100 SYP recurring |
| max_amount | 100,000,000 SYP (profit-sharing cap per CBS savings regulations) |
| fee_model | PROFIT_SHARING (Mudaraba) |
| fee_rules | No management fee. Profit split: 70% depositor, 30% Beza (as Mudarib). Profit distributed monthly based on actual returns from Beza's Sharia-compliant investment pool |
| required_kyc | Tier 2 (savings products require enhanced KYC per CBS Savings Directive) |
| ledger_accounts | Savings Pool (Asset — investment), Customer Savings Wallet (Liability), Mudarib Fee Income (Income), Profit Payable (Liability) |
| settlement_type | T+1 for deposit; T+3 for withdrawal (cooling period per Sharia compliance) |
| reversal_possible | No (savings deposits are irrevocable commitments; early withdrawal forfeits profit for the period) |
| fraud_screening | Required (savings mobility monitoring — unusual deposit/withdrawal patterns flagged to CBS) |
| tax_implications | Profit distributions: 7.5% WHT per Syrian Income Tax Law Art. 113 (for amounts > 500K SYP annual profit) |
| regulatory_ref | CBS Savings Directive 2023/56; Sharia Compliance Board Fatwa 2024/03 (Mudaraba Structure); Syrian Capital Market Authority regulations |
| sharia_compliant | Yes (Mudaraba structure, approved by Beza Sharia Board) |
| launch_version | V1.5 |
| status | LIVE |

---

## Product: Financing — Murabaha

| Attribute | Value |
|-----------|-------|
| product_code | FIN-001 |
| product_type | FINANCING_MURABAHA |
| currencys | SYP, USD |
| channels | Mobile App (application), Web Portal, In-branch (Beza hubs) |
| min_amount | 200,000 SYP |
| max_amount | 50,000,000 SYP (SYP); 100,000 USD (USD) |
| fee_model | COST_PLUS (Murabaha) |
| fee_rules | Profit rate: 8-12% per annum (SYP), 5-8% per annum (USD). Markup disclosed upfront as deferred sale price. No compounding — fixed installment structure. Late penalty: 2% of overdue amount (donated to charity per Sharia requirement) |
| required_kyc | Tier 3 (full credit assessment, collateral documentation, employment verification) |
| ledger_accounts | Financing Receivable (Asset), Customer Liability (Liability), Deferred Profit (Liability — amortized), Profit Income (Income), Penalty Charity Account (Off-balance sheet) |
| settlement_type | T+0 disbursement to supplier (direct payment — Murabaha requires asset purchase by bank); customer pays in deferred installments |
| reversal_possible | No (Murabaha contract binding per Sharia; early settlement possible with profit renegotiation) |
| fraud_screening | Enhanced due diligence; supplier verification; asset verification (field inspection for > 5M SYP) |
| tax_implications | Murabaha profit subject to corporate income tax at 28% (Syrian Income Tax Law Art. 25); asset registration fees apply |
| regulatory_ref | CBS Islamic Finance Directive 2023/61; Sharia Board Fatwa 2024/07; Syrian Civil Code Art. 465-480 (sale contract) |
| sharia_compliant | Yes (Murabaha to the purchase orderer — approved structure) |
| collateral | Required: asset being financed serves as collateral (rahn); additional guarantees for > 10M SYP |
| launch_version | V3.0 |
| status | IN_DEVELOPMENT (planned V3 launch Q3 2026) |

---

## Product: Financing — Qard Hasan

| Attribute | Value |
|-----------|-------|
| product_code | FIN-002 |
| product_type | FINANCING_QARD_HASAN |
| currencys | SYP |
| channels | Mobile App (application), Beza Hubs (in-person) |
| min_amount | 50,000 SYP |
| max_amount | 5,000,000 SYP |
| fee_model | ZERO_FEE (benevolent loan) |
| fee_rules | 0% interest — no profit margin. Service fee only: 1% of loan amount (max 50,000 SYP) for administrative costs. Late payment: no penalty (Qard Hasan prohibits any charge); defaulters are reported to CBS credit bureau only |
| required_kyc | Tier 2 minimum; must demonstrate genuine need (income verification, family status); max 1 loan per 12 months per borrower |
| ledger_accounts | Qard Hasan Fund (Asset — funded by Zakat/Sadaqat pool), Customer Loan Receivable (Asset), Service Fee Income (Income) |
| settlement_type | T+0 disbursement; repayment in equal installments (3-12 months) |
| reversal_possible | No (once disbursed, loan is binding; early repayment encouraged with no discount) |
| fraud_screening | Mandatory: social validation (borrower's reference check); community-based lending model |
| tax_implications | Qard Hasan fund contributions are tax-deductible per Syrian Zakat & Charity Law 2022/15 |
| regulatory_ref | CBS Microfinance Directive 2023/71; Sharia Board Fatwa 2024/12; Syrian Microfinance Law No. 15/2019 |
| sharia_compliant | Yes (Qard Hasan — Quran 2:245; zero-interest structure audited by Sharia Board quarterly) |
| launch_version | V3.0 |
| status | IN_DEVELOPMENT (planned V3 launch Q4 2026) |

---

## Product: Card Issuance

| Attribute | Value |
|-----------|-------|
| product_code | CRD-001 |
| product_type | CARD_ISSUANCE |
| currencys | SYP, USD (multi-currency card — V2 feature) |
| channels | Mobile App (digital issuance), Beza Hubs (physical card pickup) |
| card_scheme | Local: Syrian Payment Network (SPN); International: Mastercard (planned V3 — sanctions permitting) |
| card_type | Prepaid debit; Virtual (instant) + Physical (3-5 business days delivery) |
| issuance_fee | Virtual: Free; Physical: 10,000 SYP (one-time) |
| monthly_fee | 1,500 SYP (waived for Tier 2+ users) |
| min_balance | None |
| max_balance | 25,000,000 SYP (CBS prepaid card limit); USD card: 5,000 USD |
| fee_model | FIXED + PERCENTAGE |
| fee_rules | Issuance fee: as above; Monthly maintenance: 1,500 SYP; ATM withdrawal (domestic): 2,000 SYP flat; ATM withdrawal (international): 3 USD; POS transaction: Free; Card replacement: 15,000 SYP; PIN reissue: 5,000 SYP |
| required_kyc | Tier 1 for virtual card; Tier 2 for physical card + USD card |
| ledger_accounts | Card Liability (Liability — customer funds held in pooled account), Card Fee Income (Income), Settlement Suspense (Liability) |
| settlement_type | Prepaid (customer loads wallet, then transfers to card balance; instant on-ledger) |
| reversal_possible | Yes (chargeback processing per card scheme rules; 120-day window for disputes) |
| fraud_screening | Required: real-time transaction monitoring (SPN fraud scoring); card block via mobile app; geographic blocking |
| tax_implications | Card fees subject to 10% VAT per Syrian Tax Law No. 24/2003; international transaction fees exempt per CBS circular 2023/07 |
| regulatory_ref | CBS Prepaid Card Directive 2023/44; SPN Rulebook 2024; Mastercard Rules (when applicable) |
| launch_version | V2.0 |
| status | LIVE |

---

## Product: Card Transaction (POS / ATM)

| Attribute | Value |
|-----------|-------|
| product_code | CRD-002 |
| product_type | CARD_TRANSACTION_PROCESSING |
| currencys | SYP, USD |
| channels | POS Terminal (SPN network), ATM (SPN network), E-commerce (3DS — planned V2.5) |
| swipe_fee (interchange) | ATM: 2,000 SYP flat (domestic); POS: 0.5% MDR (interchange + processor) |
| cashback_limit | 100,000 SYP per POS cashback transaction |
| daily_atm_limit | Tier 1: 200K SYP, Tier 2: 500K SYP, Tier 3: 1.5M SYP |
| fee_model | INTERCHANGE + PROCESSOR |
| fee_rules | Issuer (Beza): 0.3% interchange on POS + 200 SYP flat; Acquirer (if Beza): 0.2% processor fee + 100 SYP flat; ATM: 2,000 SYP flat (interchange 1,200 SYP, processor 800 SYP) |
| required_kyc | Cardholder: active card (CRD-001); Merchant: SPN-registered POS merchant |
| ledger_accounts | Cardholder Wallet (Asset), SPN Settlement (Liability), Interchange Income (Income), Processor Fee (Expense) |
| settlement_type | T+1 (SPN batch settlement); T+0 for on-us transactions (Beza card on Beza POS) |
| reversal_possible | Yes (via SPN dispute management system; 30-day window for POS; 60-day for ATM) |
| fraud_screening | Required: SPN real-time fraud scoring, ATM PIN verification (3 attempts lockout), POS contactless limit: 100K SYP (no PIN) |
| tax_implications | Interchange income subject to corporate tax; exempt from VAT per CBS circular |
| regulatory_ref | SPN Operating Rules 2024; CBS Payment Card Directive 2023/44; Syrian AML Law 2021/31 |
| launch_version | V2.0 |
| status | LIVE |

---

## Product: Government Collection

| Attribute | Value |
|-----------|-------|
| product_code | GOV-001 |
| product_type | GOVERNMENT_COLLECTION |
| currencys | SYP only |
| channels | Mobile App, USSD (*123*5#), Agent, Web Portal |
| entities | Ministry of Finance (tax payments), Ministry of Interior (passport/ID fees), Damascus Governorate (municipal fees), General Organization for Civil Aviation (tickets), Ministry of Higher Education (university fees) |
| min_amount | 500 SYP |
| max_amount | 100,000,000 SYP (single transaction — CBS treasury settlement limit) |
| fee_model | PERCENTAGE |
| fee_rules | 0.5% of transaction amount, min 100 SYP, max 5,000 SYP. CBS mandates govt collection fees capped at 0.5% per CBS Directive 2024/17 |
| required_kyc | Tier 1 for fees < 1M SYP; Tier 2 for > 1M SYP (tax payments require tax ID verification) |
| ledger_accounts | Customer Wallet (Asset), Government Settlement Pool (Liability — CBS treasury account), Fee Income (Income) |
| settlement_type | T+1 (aggregated daily settlement to CBS Single Treasury Account via CBS RTGS) |
| reversal_possible | Limited (government payments are final per CBS Treasury Directive; incorrect amounts must go through formal refund process via the government entity) |
| fraud_screening | Required: automated reconciliation with government entity systems (API integration); duplicate payment detection; tax ID validation |
| tax_implications | Collections are pass-through; no tax implication for Beza. Government fees may be tax-deductible for payers |
| regulatory_ref | CBS Government Collection Framework 2024/17; CBS Single Treasury Account Directive 2023/28; Syrian Public Finance Law No. 28/2021 |
| launch_version | V2.0 |
| status | LIVE |

---

## Product: Escrow Service

| Attribute | Value |
|-----------|-------|
| product_code | ESC-001 |
| product_type | ESCROW |
| currencys | SYP, USD |
| channels | Mobile App (marketplace integration), API (B2B escrow), Web Portal |
| min_amount | 50,000 SYP (or 100 USD equivalent) |
| max_amount | 500,000,000 SYP (or 500,000 USD) — single escrow; higher amounts require CBS approval |
| fee_model | PERCENTAGE + FLAT |
| fee_rules | Marketplace: 1% of escrow amount (capped at 50,000 SYP); B2B/Real Estate: 0.5% (capped at 200,000 SYP); Flat setup fee: 5,000 SYP per escrow agreement |
| required_kyc | Buyer & Seller: Tier 2 minimum (both parties must be fully KYC'd); Real estate > 10M SYP: Tier 3 + property deed verification |
| ledger_accounts | Escrow Pool (Asset — segregate per escrow), Beneficiary Payable (Liability), Escrow Fee Income (Income), Refund Payable (Liability) |
| settlement_type | Escrow hold: T+0 (funds locked in segregated ledger); Release: upon fulfillment verification (manual or API proof); Auto-release: after 30 days if no dispute |
| reversal_possible | Yes (if both parties agree; or dispute resolution by Beza Escrow Committee; timeline: 5 business days for resolution) |
| fraud_screening | Enhanced: both parties screened against CBS sanctions; transaction monitoring for round-tripping; real estate (FIN-001 integration) requires title deed verification |
| tax_implications | Escrow fees subject to 10% VAT; escrow funds are not taxable (they are held in trust, not Beza income) |
| regulatory_ref | CBS Escrow Directive 2024/22; Syrian Civil Code Art. 720-730 (escrow contract); Beza Escrow Terms & Conditions v2.0 |
| sharia_compliant | Yes (Wakala bi Istithmar structure — agency-based escrow with Sharia Board Fatwa 2025/02) |
| launch_version | V3.0 |
| status | IN_DEVELOPMENT (planned V3 launch Q3 2026) |

---

## Product: Agent Float Top-up

| Attribute | Value |
|-----------|-------|
| product_code | AGT-003 |
| product_type | AGENT_FLOAT_MANAGEMENT |
| currencys | SYP only |
| channels | Web Portal (Agent Dashboard), Mobile App (Agent), API (agent network manager) |
| min_amount | 50,000 SYP (float top-up) |
| max_amount | 50,000,000 SYP (per top-up; subject to agent tier and bond amount) |
| fee_model | NO_FEE (operational product) |
| fee_rules | 0% fee for Beza-managed agents (float top-ups from agent's own bank account); Third-party agents: 0.2% processing fee (min 500 SYP, max 2,000 SYP) to cover CBS ACH transfer cost |
| required_kyc | Agent must be CBS-certified with active Agent License; minimum bond: 500K SYP (Tier 1 agent) to 10M SYP (Tier 3 agent) |
| ledger_accounts | Agent Float Wallet (Liability), CBS Settlement Account (Asset — funding source), Agent Float Fee Income (Income) |
| settlement_type | INSTANT (on-ledger credit to agent float wallet upon bank transfer confirmation or CBS ACH settlement) |
| reversal_possible | Yes (admin only: reversal of erroneous float top-up within 24h) |
| fraud_screening | Required: source of funds verification (bank account must match registered agent bank account); AML screening if float > 5M SYP |
| tax_implications | No direct tax (float is agent's own funds held in trust); agent commissions (AGT-001/002) are taxed separately |
| regulatory_ref | CBS Agent Float Management Directive 2024/18; CBS Agent Banking Framework 2023/45 |
| launch_version | V1.0 |
| status | LIVE |

---

## Product Index

| # | Product Code | Product Name | Version | Status | Category |
|---|--------------|--------------|---------|--------|----------|
| 1 | WLT-001 | P2P Wallet Transfer | V1.0 | LIVE | Transfer |
| 2 | AGT-001 | Agent Cash-in | V1.0 | LIVE | Agent |
| 3 | AGT-002 | Agent Cash-out | V1.0 | LIVE | Agent |
| 4 | FX-001 | FX Conversion | V1.5 | LIVE | FX |
| 5 | REM-001 | Inbound Remittance | V1.0 | LIVE (Partial) | Remittance |
| 6 | BIL-001 | Bill Payment — Telecom | V1.0 | LIVE | Bill Payment |
| 7 | BIL-002 | Bill Payment — Electricity | V1.0 | LIVE | Bill Payment |
| 8 | BIL-003 | Bill Payment — Water | V1.0 | LIVE | Bill Payment |
| 9 | MER-001 | Merchant QR Payment | V1.0 | LIVE | Merchant |
| 10 | PAY-001 | Payroll Disbursement | V1.0 | LIVE | B2B |
| 11 | SAV-001 | Savings Goal | V1.5 | LIVE | Savings |
| 12 | FIN-001 | Financing — Murabaha | V3.0 | IN_DEVELOPMENT | Financing |
| 13 | FIN-002 | Financing — Qard Hasan | V3.0 | IN_DEVELOPMENT | Financing |
| 14 | CRD-001 | Card Issuance | V2.0 | LIVE | Card |
| 15 | CRD-002 | Card Transaction | V2.0 | LIVE | Card |
| 16 | GOV-001 | Government Collection | V2.0 | LIVE | Government |
| 17 | ESC-001 | Escrow Service | V3.0 | IN_DEVELOPMENT | Escrow |
| 18 | AGT-003 | Agent Float Top-up | V1.0 | LIVE | Operations |

---

*This catalog is maintained by the Beza Financial Products team. All regulatory references are based on Syrian legislation in effect as of May 2026. Product specifications are subject to change upon CBS regulatory updates or Sharia Board rulings.*
