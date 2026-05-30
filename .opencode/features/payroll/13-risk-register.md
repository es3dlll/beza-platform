# 13 — Risk Register

---

| ID | Risk | Probability | Impact | Mitigation |
|----|------|-------------|--------|------------|
| R-01 | Company uploads incorrect amounts (overpay) | Medium | High | Require PIN confirmation before execution; employee can reject / return excess; full audit trail |
| R-02 | Employee wallet not activated | High | Low | Auto-retry + SMS to employee with activation link; HR notified |
| R-03 | Insufficient company balance | Medium | High | Pre-validation before batch starts; clear warning with exact shortfall amount |
| R-04 | CFE hold failure during batch | Low | Critical | Rollback entire batch; no partial holds; idempotency keys |
| R-05 | CSV parsing errors (malformed file) | Medium | Low | Row-level validation with Arabic error messages; template with examples |
| R-06 | SMS delivery failure | Medium | Medium | Fallback to push notification; in-app inbox with history |
| R-07 | Regulatory change (CBS new rules) | Low | High | Legal monitoring; modular compliance layer |
| R-08 | Server / data centre outage | Low | Critical | Multi-AZ PostgreSQL; DR site in Aleppo (Phase 2) |
| R-09 | Fraudulent company onboarding | Low | Critical | Beneficial owner KYC; physical document verification; manual review |
| R-10 | Employee disputes salary amount | Medium | Medium | Payslip contains full breakdown; dispute channel within app |
| R-11 | Double batch (same employees same month) | Medium | Medium | Duplicate detection: same (company_id, employee_id, month) within 30-day window |
| R-12 | USD exchange rate fluctuation | Low | Medium | Process USD batches at rate locked at batch creation timestamp |
