# Testing Strategy

## Test Pyramid

```
         ╱─────╲
        ╱  E2E  ╲          5-10 critical journeys
       ╱─────────╲
      ╱  Integration ╲     20-30 service-level tests
     ╱─────────────────╲
    ╱    Unit Tests      ╲  200+ component/service tests
   ╱───────────────────────╲
```

## Unit Tests

| Module | Coverage Target | Key Test Scenarios |
|--------|-----------------|-------------------|
| `AidProgramService` | 95% | Create program (valid/invalid budget, missing fields), upload beneficiaries (CSV parse, validation), pause/resume program |
| `BeneficiaryVerificationService` | 95% | Biometric match (success, failure, low confidence), UNHCR ID validation, offline queue dedup, idempotency |
| `DistributionService` | 95% | Batch credit (all succeed, some fail, all fail), partial completion, retry logic |
| `MPCSpendingMonitor` | 90% | Category aggregation, burn rate calculation, anomaly detection (unusual patterns) |
| `ComplianceService` | 95% | Sanctions screening (match, no match, fuzzy match), duplicate detection, audit log immutability |
| `VoucherService` | 95% | Code generation (uniqueness, format), redemption (full, partial, expired, invalid code), settlement calculation |

## Integration Tests

| Test | Description |
|------|-------------|
| Enrollment → Sanctions → Distribution | Full flow: upload CSV → validate → screen → approve → distribute |
| Distribution → Wallet Credit | Verify wallet balance correctly updated after batch distribution |
| Voucher Issue → Redeem → Settle | Full voucher lifecycle including merchant settlement |
| Spending Tracking Pipeline | Verify merchant transactions properly categorised in spending dashboard |
| Offline Agent Sync | Agent verifies offline → queue syncs → duplicate prevented |
| Donor Report Generation | End-to-end report generation from raw data to formatted output |

## E2E Tests (Playwright)

| Test ID | Scenario | Critical? |
|---------|----------|-----------|
| E2E-01 | Program Manager creates MPC program, uploads beneficiaries, triggers distribution | Yes |
| E2E-02 | Field Agent verifies beneficiary biometrically and confirms disbursement | Yes |
| E2E-03 | Merchant redeems voucher, verifies settlement arrives T+2 | Yes |
| E2E-04 | Donor generates quarterly report and exports as PDF | Yes |
| E2E-05 | Compliance Officer reviews sanctions screening and resolves flagged case | Yes |
| E2E-06 | Offline agent verifies beneficiaries, syncs when online, no duplicates | Yes |
| E2E-07 | Beneficiary receives SMS notifications at each step | Yes |

## Sanctions Screening Test Cases

| Test | Input | Expected |
|------|-------|----------|
| Exact match OFAC | Name matches exactly | Blocked, manual review required |
| Fuzzy match UN list | Name differs by one character | Flagged for review (score > 80%) |
| False positive (common name) | Common Arabic name "محمد" | Score < 80%, cleared automatically |
| Arabic transliteration | "أسامة بن لادن" → match "Usama bin Laden" | Blocked (transliteration algorithm) |
| No match | Unique name, no sanctions | Cleared |

## Load Tests

| Scenario | Target | Tool |
|----------|--------|------|
| Concurrent beneficiary verification | 100 agents verifying simultaneously | k6 |
| Batch distribution throughput | 10k wallets credited in < 2 min | k6 |
| CSV upload with 50k records | Process within 5 min | k6 |
| Donor report generation | 500k records aggregated in < 5 min | k6 |
| API burst (1000 req/s) | p95 < 500ms | k6 |

## Test Data

- **Realistic Syrian data:** Names, phone numbers, governorates modelled from UNOCHA Syria data
- **Sanctions test lists:** Copy of UN Consolidated List subset for offline testing
- **Biometric test fixtures:** Synthesised fingerprint templates (10 unique, 5 duplicate, 5 low-quality)
- **Voucher test matrix:** Valid, expired, fully-redeemed, partially-redeemed, cancelled, invalid code
