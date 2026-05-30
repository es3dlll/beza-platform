# Code Review Checklist — Beza Platform

## How to Use This Checklist

Every PR must be reviewed against this checklist. The reviewer checks each item and marks pass/fail/n/a. Any fail blocks the merge. All items must be pass or n/a before the PR is approved.

Items marked with 🚨 are **blocking** — must pass before merge.
Items marked with ⚠️ are **advisory** — should pass, reviewer may approve with justification.

---

## 1. Architecture

### 1.1 Module Structure 🚨
- [ ] New files follow the module structure (Controllers/Models/Services/Repositories/DTOs/Events)?
- [ ] No files placed outside module directories?
- [ ] Module boundaries respected (no cross-module model imports)?
- [ ] Namespaces match directory structure?

### 1.2 Controller Layer 🚨
- [ ] Controller has ONLY request parsing and response returning?
- [ ] No business logic (ifs, calculations, state checks) in controller?
- [ ] No database queries in controller?
- [ ] No model instantiation or `::create()` in controller?
- [ ] Controller calls service method and returns resource/response?
- [ ] Form Request class used for validation (not inline `$request->validate()`)?

### 1.3 Service Layer 🚨
- [ ] Business logic lives in service classes?
- [ ] Service receives all dependencies via constructor injection?
- [ ] Service methods have clear single responsibility?
- [ ] Service calls repository for data access?
- [ ] Service dispatches events for cross-module communication?
- [ ] Service throws typed exceptions (not generic `\Exception`)?

### 1.4 Repository Layer 🚨
- [ ] ALL database queries in repository classes?
- [ ] No `Model::where(...)` or `DB::table(...)` in controllers or services?
- [ ] Repository returns typed model objects or collections?
- [ ] Complex queries use scopes or query builder?
- [ ] Repository has proper `lockForUpdate()` or transaction support for critical operations?

### 1.5 DTO Layer 🚨
- [ ] DTOs used for all data transfer between layers?
- [ ] DTO has `fromRequest()` or `fromArray()` named constructor?
- [ ] DTO properties are typed and readonly?
- [ ] No raw `$request->all()` or `$request->validated()` passed to services?

### 1.6 Events & Cross-Module 🚨
- [ ] Cross-module communication uses events (not direct method calls)?
- [ ] Event carries a DTO (not the Eloquent model)?
- [ ] Listener calls its own module's service layer?
- [ ] Event dispatched after transaction commit (or queued)?

---

## 2. Security 🚨

### 2.1 Authentication & Authorization 🚨
- [ ] Endpoint protected with correct auth middleware?
- [ ] `authorize()` method in Form Request checks permissions?
- [ ] Ownership verified (user can only access own resources)?
- [ ] Role-based access enforced where applicable?
- [ ] Rate limiting applied to mutation endpoints?

### 2.2 Input Validation 🚨
- [ ] All user input validated through Form Request or Rule classes?
- [ ] Whitelist approach (allow known fields, reject unknown)?
- [ ] Syrian phone validated with correct regex?
- [ ] Amount fields validated for type, min, max, and currency?
- [ ] String fields have max length constraints?
- [ ] All validation messages provided in Arabic?

### 2.3 Injection Prevention 🚨
- [ ] No raw SQL string concatenation?
- [ ] No `DB::select()` or `DB::statement()` without parameter binding?
- [ ] All queries use Eloquent ORM or Query Builder with bindings?
- [ ] No `eval()`, `unserialize()`, or dynamic file inclusion?
- [ ] No shell command execution (`exec()`, `shell_exec()`, `system()`) without strict validation?

### 2.4 Data Exposure 🚨
- [ ] No sensitive data in API responses (passwords, PINs, full card numbers)?
- [ ] Auto-increment IDs not exposed in responses (use ULID/UUID)?
- [ ] Error messages don't leak internal details (stack traces, SQL queries)?
- [ ] Personal data masked or excluded from list endpoints?
- [ ] CORS configured correctly (production origins only)?

---

## 3. Testing

### 3.1 Test Coverage 🚨
- [ ] Feature test for new endpoint?
- [ ] Test covers: 200 (success), 400 (validation), 401 (unauth), 403 (forbidden), 404 (not found), 422 (business rule)?
- [ ] Business rule violations explicitly tested?
- [ ] Edge cases tested (empty list, max values, boundary values)?
- [ ] State transitions tested (if applicable)?

### 3.2 Test Quality ⚠️
- [ ] Tests use factories (not raw array create)?
- [ ] Tests assert on database state (not just response shape)?
- [ ] Tests are independent (not reliant on other tests)?
- [ ] No `sleep()` or hardcoded delays in tests?
- [ ] Test names follow convention and describe behavior?

### 3.3 Performance ⚠️
- [ ] N+1 query problem checked and eliminated?
- [ ] Eager loading used for relationships?
- [ ] Pagination implemented for list endpoints?
- [ ] Indexes exist for new query patterns?
- [ ] Heavy processing queued as job?

---

## 4. Code Quality

### 4.1 Clean Code 🚨
- [ ] No dead code (unused variables, methods, imports)?
- [ ] No commented-out code blocks?
- [ ] No `dd()`, `var_dump()`, `print_r()`, `ray()`, `logger()->debug()`?
- [ ] No TODO or FIXME without a ticket number?
- [ ] Class length ≤ 200 lines?
- [ ] Method length ≤ 20 lines?
- [ ] Nesting ≤ 3 levels deep?

### 4.2 Naming & Readability ⚠️
- [ ] Variable names clearly describe purpose?
- [ ] No single-letter variables (except loop counters)?
- [ ] Boolean variables use positive names (`isActive` not `isNotActive`)?
- [ ] Abbreviations avoided (use `transaction` not `txn`, `amount` not `amt`)?
- [ ] Method names are verbs describing action?

### 4.3 Error Handling 🚨
- [ ] All catch blocks have appropriate handling or logging?
- [ ] No empty catch blocks `catch (Exception $e) {}`?
- [ ] Exceptions have context data (user_id, amount, entity)?
- [ ] Error codes from error catalog used?
- [ ] User-facing error messages in Arabic?

### 4.4 Type Safety 🚨
- [ ] All PHP methods have return type declarations?
- [ ] All PHP method parameters have type declarations?
- [ ] No `mixed` type without strong justification?
- [ ] No loose comparison (`==`) where strict comparison (`===`) should be used?
- [ ] All DTO properties have typed properties (PHP 8.1+ `readonly`)?

---

## 5. Database

### 5.1 Migrations 🚨
- [ ] Migration is reversible (has `down()` method)?
- [ ] No editing of previously deployed migrations?
- [ ] Indexes created on foreign keys?
- [ ] Indexes created on status/date columns used in WHERE clauses?
- [ ] Default values appropriate for new columns?

### 5.2 Data Integrity 🚨
- [ ] Foreign key constraints defined?
- [ ] Unique constraints on business keys (phone, national_id)?
- [ ] Soft delete on user-facing entities?
- [ ] Audit columns (`created_by`, `updated_by`) on critical tables?
- [ ] Amount columns use smallest unit (integer, not float)?

---

## 6. Syria-Specific Checks

### 6.1 Localization 🚨
- [ ] All user-facing error messages in Arabic?
- [ ] All labels/titles in the UI from translation files?
- [ ] Arabic formatting for amounts and dates?
- [ ] RTL support considered (for admin and mobile)?

### 6.2 Regulatory ⚠️
- [ ] Transaction recording includes all required fields for compliance?
- [ ] Agent commission calculations match regulatory formula?
- [ ] Daily/monthly limits aligned with CBE (Central Bank of Syria) regulations?
- [ ] KYC requirements met for given transaction tier?
- [ ] Audit trail complete for regulatory review?

### 6.3 Regional Considerations ⚠️
- [ ] Phone number handling correct for Syria (+963)?
- [ ] Currency codes use ISO 4217 (SYP, USD)?
- [ ] Timezone handling correct (Syria UTC+3, DST considerations)?
- [ ] Friday schedule respected for non-urgent jobs?

---

## Reviewer Final Decision

| Decision | Criteria |
|----------|----------|
| ✅ Approve | All 🚨 items pass. Advisory items discussed. |
| 🔄 Changes Requested | Any 🚨 item fails. Comment with specific issue. |
| ❌ Reject | Multiple 🚨 failures, architecture violation, or security issue. |
