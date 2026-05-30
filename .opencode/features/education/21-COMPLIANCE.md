# 21 — Compliance & Regulatory

## 21.1 Syrian Regulatory Landscape

| Regulator | Relevance | Requirements |
|---|---|---|
| **Central Bank of Syria (CBS)** | Payment processing, e-money | Licence required for payment aggregation; transaction reporting; AML/CFT compliance |
| **Syrian Ministry of Education** | School licensing | Only licensed schools can use platform; fee amounts must be ministry-approved |
| **Syrian Ministry of Higher Education** | University tuition | University fee structures regulated; annual approval required |
| **Syrian Tax Authority** | VAT, income tax | Transaction tax (5% TOT on services); school income reporting; Beza VAT on fees |
| **Syrian Telecommunications Regulatory Authority** | SMS, WhatsApp | Bulk SMS requires telecom licence; WhatsApp Business API regulated |
| **Anti-Money Laundering (AML) Commission** | Financial crimes | All education payments > 1M SYP require enhanced due diligence (EDD) |

## 21.2 Licensing

- Beza requires **Payment Aggregator Licence** from CBS to handle education fee collection
- Schools must provide valid **Ministry of Education Registration Certificate** during onboarding
- Tutoring centres must provide **Local Council Permit**
- All licences stored encrypted and re-verified annually

## 21.3 AML/CFT Requirements

| Threshold | Action |
|---|---|
| Single payment ≥ 1,000,000 SYP | EDD: verify source of funds, record purpose |
| Cumulative payments ≥ 5,000,000 SYP in 30 days | File STR (Suspicious Transaction Report) |
| School settlement > 10,000,000 SYP | Flag for manual review |
| Diaspora payment > 5,000,000 SYP equivalent | Verify remitter identity against sanctions lists |

### Sanctions Screening
- All schools screened against: UN Sanctions List, EU Syria Sanctions, OFAC SDN List
- All diaspora parents screened before allowing payment
- Daily automated screening of existing customers
- Positive match → freeze account, notify CBS AML unit

## 21.4 Data Protection

| Requirement | Implementation |
|---|---|
| **Data localisation** | All student and payment data stored on servers physically within Syria |
| **Parental consent** | For students under 18, data processing requires guardian consent (captured during onboarding) |
| **Data retention** | Payment records: 10 years per Syrian law; student records: 5 years after graduation |
| **Right to access** | Parents can download all data related to their children via app |
| **Breach notification** | Report to CBS within 24 hours of discovery |

## 21.5 Fee Regulation Compliance

### Private Schools
- Fee increases capped at 15% annually per Ministry decree
- Schools must publish fee structure 60 days before term start
- Beza platform enforces: new template cannot exceed previous by >15%

### Public Schools
- Fees set by Ministry of Education, no discretion
- Beza mirrors official fee schedule; discrepancies reported

### Private Universities
- Tuition set by university board, approved by Ministry of Higher Education
- Must display approval reference on invoice

## 21.6 Tax Compliance

- **Transaction Tax (TOT)**: 5% on value of services (school fees) — deducted at source and remitted to Tax Authority
- **Beza VAT**: 10% on platform fees — included in 2.5% transaction fee
- **School income tax**: Schools responsible for their own corporate income tax; Beza provides annual statement
- **Withholding tax**: 7.5% on payments to foreign entities (diaspora tooling providers)

## 21.7 Reporting to Regulators

| Report | Frequency | To |
|---|---|---|
| Payment volume report | Monthly | CBS |
| Suspicious transactions | As they occur | AML Commission |
| School onboarding report | Quarterly | Ministry of Education |
| Tax remittance report | Monthly | Tax Authority |
| Data breach report | Within 24h | CBS / Data Protection |
