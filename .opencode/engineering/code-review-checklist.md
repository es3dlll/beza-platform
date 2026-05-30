# Code Review Checklist — PR Gate

> Every PR must pass ALL checks before merge.
> Reviewer must tick each box or explain why N/A.

---

## 1. Architecture Guardrails

- [ ] G-001: No direct Ledger access from outside Ledger/CFE
- [ ] G-002: Every financial operation passes through CFE
- [ ] G-003: Controller-Service-Repository layering respected
- [ ] G-004: `Money` ValueObject used (no float)
- [ ] G-005: No UPDATE/DELETE on journal entries
- [ ] G-006: Module follows standard template
- [ ] G-007: ULID for all new primary keys
- [ ] G-008: Cross-module communication via Events
- [ ] G-009: Error codes in catalog
- [ ] G-010: Feature tests for every endpoint

## 2. Security

- [ ] No SQL injection (raw queries use bindings or Eloquent)
- [ ] No secrets/keys in code (env vars or config only)
- [ ] Input validated via FormRequest (not in Controller)
- [ ] KYC level checked before financial operations
- [ ] Daily limits enforced for transfers/withdrawals
- [ ] JWT middleware on authenticated routes
- [ ] Device binding verified for sensitive operations

## 3. Code Quality

- [ ] No commented-out code
- [ ] No `dd()`, `dump()`, `var_dump()`, `logger()` without purpose
- [ ] No magic numbers (use constants or DB config)
- [ ] No empty catch blocks
- [ ] Services are `final` (not extendable by accident)
- [ ] DTOs are `final` and readonly
- [ ] Exceptions have meaningful messages
- [ ] Events use `Dispatchable` + `SerializesModels`

## 4. Domain & Financial

- [ ] Double-entry verified (debits = credits)
- [ ] Fee calculation verified
- [ ] Hold/release lifecycle verified
- [ ] Reversal creates opposite entry (not deletion)
- [ ] Currency conversion uses `Rate` ValueObject
- [ ] Transaction metadata captures `channel` + `initiated_by`
- [ ] Offline queue operations have idempotency key

## 5. Testing

- [ ] Happy path tested (200/201)
- [ ] Validation failure tested (422)
- [ ] Business rule violation tested (409/422)
- [ ] RefreshDatabase trait used for Feature tests
- [ ] No `@depends` (tests are independent)
- [ ] Assertions include response structure (not just status code)

## 6. i18n & Accessibility

- [ ] Arabic translations in `Resources/lang/ar/`
- [ ] English translations in `Resources/lang/en/`
- [ ] Error messages bilingual (`message` / `message_ar`)
- [ ] RTL-compatible UI (if frontend)

## 7. Migration Safety

- [ ] Migration is reversible (has `down()`)
- [ ] No `NOT NULL` on new column without default for existing rows
- [ ] Indexes on foreign keys
- [ ] ULID columns are `string(26)` not `char(36)`

---

## Review Decision

```
[ ] APPROVED — All checks pass
[ ] CHANGES_REQUESTED — Items listed below must be fixed
[ ] REJECTED — Blocker violation (G-001, G-002, G-004, G-005)

Comments:
_________________________________________________
_________________________________________________

Reviewer: ______________   Date: ______________
```
