# 22 — Compliance

---

## Regulatory Compliance

### Central Bank of Syria (CBS)

| Requirement | Beza Approach | Status |
|-------------|---------------|--------|
| PSP license (Law 18) | Beza holds Class B PSP license — payroll is a regulated activity | ✅ Acquired |
| Data residency | All servers in Damascus; no cross-border data flow | ✅ Enforced |
| Transaction monitoring | Real-time monitoring for suspicious patterns | ✅ Built |
| Daily settlement limits | Company-level caps (SYP 500M/batch) | ✅ Configurable |
| CBS reporting | Monthly transaction volume report submitted via CBS portal | ✅ Automated |

### Anti-Money Laundering (AML Law 31)

| Requirement | Beza Approach |
|-------------|---------------|
| Beneficial owner identification | All owners ≥ 25 % must submit ID + proof |
| Sanctions screening | Every company screened against UN/CBS sanctions lists at onboarding |
| Transaction monitoring | Flag: batch amount > 2× historical average; > 50 % of employees failed |
| Record keeping | 7 years for all payroll records |
| Suspicious activity reporting | SAR submitted to CBS AML unit within 24 hours |

### Syrian Labour Law

| Article | Requirement | Beza Compliance |
|---------|-------------|-----------------|
| 68 | Salary paid within 3 days of due date | Batch scheduling ensures on-time processing |
| 73 | Itemized payslip required | Auto-generated PDF payslip per employee |
| 76 | Overtime must be documented | CSV supports overtime column (optional) |
| 121 | No salary deduction without consent | Payslip shows all deductions; company attestation |

## Sharia Compliance

| Aspect | Ruling | Beza Implementation |
|--------|--------|---------------------|
| Settlement fee (T+0) | Permissible — service fee for processing | Fixed % (0.5 %), disclosed upfront |
| Settlement fee (T+1) | Requires Sharia review — may be considered qard | Additional 0.25 % subject to Sharia board approval |
| Late payment penalty | Not permissible (riba) | No late fees; service suspension instead |
| Salary advance | Permissible if interest-free | Free advance (SYP 500K max); no fee |
| Currency exchange | Permissible at spot rate | USD batches locked at batch creation rate |

## Data Privacy

| Principle | Beza Implementation |
|-----------|---------------------|
| Consent | Company consent obtained at onboarding; employee consent at wallet activation |
| Data minimization | Only salary-related data collected; no health, religion, or political data |
| Access control | RBAC: company HR sees only their company; employee sees only their data |
| Deletion | Employee data deleted 90 days after termination (unless legal hold) |
| Breach notification | 24-hour notification to affected parties + CBS (if over 1,000 records) |
