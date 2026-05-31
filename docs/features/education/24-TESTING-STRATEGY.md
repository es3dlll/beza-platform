# 24 — Testing Strategy

## 24.1 Test Pyramid

```
         ╱╲
        ╱ E2E ╲            ← 5% — Playwright (Web) + Detox (Mobile)
       ╱────────╲
      ╱Integration╲         ← 15% — API contract, DB, payment
     ╱──────────────╲
    ╱   Unit Tests    ╲    ← 80% — Components, services, utils
   ╱────────────────────╲
```

## 24.2 Unit Testing

| Layer | Framework | Coverage Target | Key Areas |
|---|---|---|---|
| Backend API | Jest (Node) / JUnit (Kotlin) | 90% | Fee calculation, late fee logic, validation |
| Frontend web | Vitest + React Testing Library | 85% | Components, hooks, form validation |
| Flutter | Flutter Test | 85% | Widgets, models, providers, formatters |
| Shared utils | Vitest / Jest | 95% | Currency formatting, date logic, fee math |

### Critical Test Cases (Fee Calculation)
```typescript
// Examples
calculateLateFee(balance: 995000, rate: 2, daysOverdue: 30) → 19900
calculateLateFee(balance: 995000, rate: 2, daysOverdue: 180) → 99500 (capped at 10%)
applySiblingDiscount(tuition: 750000, percent: 10, childNum: 2) → 75000
applyEarlyBird(total: 1045000, discount: 50000, beforeDate: true) → 995000
instalmentSchedule(total: 3000000, terms: 3) → [1000000, 1000000, 1000000]
fxConversion(amountSYP: 4800000, rate: 25890) → 185.36
```

## 24.3 Integration Testing

| Scope | Tool | Description |
|---|---|---|
| API contract | SuperTest (Node) / MockMVC (Kotlin) | All endpoints, all status codes |
| DB queries | TestContainers | Real PostgreSQL in container |
| Payment flow | WireMock (mocked Payment Core) | Debit, refund, settlement |
| Notification | WireMock (mocked WhatsApp API) | Send, delivery receipt, failure |
| FX conversion | WireMock (mocked FX Engine) | Rate lookup, conversion |

### Key Integration Scenarios
1. **Full payment flow**: Create invoice → pay → receipt → school webhook
2. **Bulk invoice generation**: 1000 students → 1000 invoices in < 30s
3. **Auto-pay execution**: Schedule → cron trigger → payment → notification
4. **Settlement batch**: Aggregate → calculate fees → transfer → confirm
5. **Overdue detection**: Cron → update statuses → trigger reminders

## 24.4 E2E Testing

| Platform | Tool | Key Flows |
|---|---|---|
| Web (School) | Playwright | Login, dashboard load, send bulk reminder, export CSV |
| Mobile (Parent) | Detox (iOS) / Maestro (Android) | Pay fee, view history, schedule auto-pay |
| Cross-platform | Playwright (emulated mobile) | Receipt download, deep links, RTL rendering |

## 24.5 Performance Testing

| Test | Tool | Target | Threshold |
|---|---|---|---|
| Load (typical term start) | k6 | 500 concurrent parents paying | p95 < 5s |
| Stress (enrolment day) | k6 | 2000 concurrent parents | No errors, p99 < 10s |
| Endurance | k6 | 100 concurrent for 24h | No memory leak |
| Spike | k6 | 0 → 1000 in 10s | Auto-scale within 30s |
| Dashboard load | k6 | 50 concurrent schools | p95 < 2s |

## 24.6 Security Testing

| Type | Frequency | Tool |
|---|---|---|
| SAST (Static) | Every PR | SonarQube, ESLint security plugin |
| DAST (Dynamic) | Weekly | OWASP ZAP |
| Dependency scan | Daily | Dependabot, Snyk |
| Penetration test | Quarterly | External firm (Year-1: Synapsis or similar) |
| API fuzzing | Pre-release | Custom fuzzer |
| Auth bypass | Every release | Manual OWASP Top 10 checklist |

## 24.7 Acceptance Criteria per User Story

Each story in the PRD must have:
- **Given** (precondition)
- **When** (action)
- **Then** (expected result)
- **And** (additional verifications)
- **Edge cases** (3-5 minimum)

## 24.8 Test Data

- **Synthetic school**: "Beza Test School" — 100 test students
- **Test parents**: 10 phone numbers (test-01@beza.sy ... test-10@beza.sy)
- **Test payments**: 1 SYP test payments for onboarding
- **Reset**: Test data resets daily at 03:00
- **Fixtures**: `tests/fixtures/education/` — CSVs for student rosters, fee templates
