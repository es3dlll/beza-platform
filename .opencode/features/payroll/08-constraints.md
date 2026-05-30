# 08 — Constraints

---

## Regulatory Constraints

| ID | Constraint | Source |
|----|------------|--------|
| C-01 | All salary data must be stored on servers physically located in Syria | Central Bank of Syria (CBS) regulations |
| C-02 | Beza must obtain a Payment Service Provider license from CBS | CBS Law No. 18 |
| C-03 | Employer must provide itemized payslip per employee | Syrian Labour Law, Article 73 |
| C-04 | Maximum 3 business days for salary distribution after due date | Syrian Labour Law, Article 68 |
| C-05 | Foreign currency salaries (USD) require CBS approval per company | CBS Circular 2023/15 |
| C-06 | KYC/AML checks on all company beneficial owners (≥ 25 % share) | AML Law No. 31 |

## Business Constraints

| ID | Constraint | Rationale |
|----|------------|-----------|
| C-07 | Company must maintain minimum balance = 1 month payroll | Risk mitigation |
| C-08 | T+1 settlement only for companies > 12 months on platform | Trust threshold |
| C-09 | Maximum per-transaction limit: SYP 50,000,000 | CBS daily limit guidance |
| C-10 | Employee must have verified Beza wallet (KYC Level 1+) before receiving salary | AML requirement |

## Technical Constraints

| ID | Constraint | Rationale |
|----|------------|-----------|
| C-11 | Company account hold uses CFE (Core Financial Engine) — synchronous call | Funds verification before release |
| C-12 | Database: PostgreSQL 15+ with UUID primary keys | Consistency, distributed compatibility |
| C-13 | File storage: S3-compatible (MinIO on-prem) | No cloud dependency |
| C-14 | PDF generation: server-side (wkhtmltopdf or Puppeteer) | Client-side printing unreliable |
| C-15 | SMS gateway: local Syrian provider (e.g., Syriatel or MTN direct) | International SMS blocked/unreliable |

## Cultural Constraints

| ID | Constraint | Rationale |
|----|------------|-----------|
| C-16 | All user-facing content must be in Arabic (English optional) | User base primarily Arabic-speaking |
| C-17 | Salary payment day preference: typically 1st–5th of month | Syrian payroll norm |
| C-18 | Friday/Saturday weekend — no settlement runs | Banking holiday |
