# 23 — Testing Strategy

---

## Test Pyramid

```
         ╱  E2E (5 %)  ╲       — Playwright (web) + Detox (mobile)
        ╱ Integration     ╲    — Service-level + CFE mock
       ╱  (25 %)           ╲
      ╱━━━━━━━━━━━━━━━━━━━━━╲
     ╱   Unit Tests (70 %)    ╲  — Services, models, validators, CSV parser
```

## Unit Tests

| Module | Framework | Key Tests |
|--------|-----------|-----------|
| `PayrollService.process_batch` | pytest + pytest-asyncio | Happy path; insufficient balance; partial failure; CFE hold failure |
| `PayrollService.retry_failed` | pytest | Retry 3x then permanent; manual retry resets count |
| `PayslipGenerator.generate` | pytest | Correct Arabic template; encryption; S3 upload |
| `CSVParser` | pytest | Valid CSV; missing columns; wrong types; UTF-8 BOM handling |
| `CompanyService.onboard` | pytest | KYC validation; duplicate licence check; AML screening mock |
| Validators | pytest | Amount > 0; employee exists; currency match |

## Integration Tests

| Scenario | Approach |
|----------|----------|
| Full batch lifecycle | Wiremock for CFE; real PostgreSQL (testcontainers) |
| CFE hold → credit → release | Mock CFE server with stateful hold tracking |
| CSV → batch creation | Upload CSV via test API client → assert batch created |
| Wallet activation → salary receive | Mock employee user → activate → process batch → assert credit |
| Settlement T+1 lifecycle | Create batch with T+1 company → assert settlement due date |

## E2E Tests (Playwright)

| Test | User Flow |
|------|-----------|
| Company registers + approves | Admin approves company → company logs in → sees dashboard |
| Upload CSV + process batch | HR uploads valid CSV → confirms with PIN → batch completes |
| Failed employee + retry | HR uploads CSV with invalid employee → sees failure → fixes → retries |
| Download payslips | Batch completed → download ZIP → verify PDFs exist |
| Employee notification | Batch completed → verify employee gets push/SMS (mock SMS) |

## Load Tests (k6)

| Scenario | Target | Metrics |
|----------|--------|---------|
| 50 concurrent batches | Each 500 employees | p95 latency < 5 s |
| 100 concurrent CSV uploads | 2 MB each | p95 latency < 3 s |
| Payslip generation spike | 10,000 PDFs in 5 min | Success rate > 99 % |

## Test Data

- **Fixture CSV files:** valid.syp.csv, valid.usd.csv, missing-column.csv, wrong-type.csv, empty.csv, 5000-employees.csv
- **Mock CFE responses:** success, insufficient_balance, timeout, server_error
- **Test companies:** `company_al_sham` (active, T+0), `company_damascus_pharma` (active, T+1), `company_pending` (pending_review)
