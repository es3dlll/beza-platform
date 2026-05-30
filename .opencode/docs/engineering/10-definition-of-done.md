# Definition of Done — Beza Platform

## Purpose

The Definition of Done (DoD) is the checklist every feature, bug fix, or change must pass before it is considered complete. No work item may transition from "In Progress" to "Done" without all applicable items checked.

---

## 1. Backend Feature DoD (Laravel)

### Database Layer
- [ ] Migration written with both `up()` and `down()` methods.
- [ ] Migration tested: run `php artisan migrate:rollback` and re-run to verify reversibility.
- [ ] Indexes created on foreign keys, status columns, and date range columns.
- [ ] Unique constraints on business keys (phone, national_id, reference).
- [ ] Soft deletes added for user-facing entities.
- [ ] Audit columns (`created_by`, `updated_by`) added for critical tables.

### Model Layer
- [ ] Model created with proper namespace in module directory.
- [ ] `$fillable` or `$guarded` defined (protect against mass assignment).
- [ ] `$casts` defined for all non-string attributes (integers, booleans, dates, enums).
- [ ] `$with` for eager-loaded relationships on list queries (prevent N+1).
- [ ] All relationships defined (`belongsTo`, `hasMany`, `morphMany`, etc.).
- [ ] Global scopes applied where appropriate (e.g., `ActiveScope`).

### Controller Layer
- [ ] Controller created with proper namespace.
- [ ] Controller has NO business logic (calls service only).
- [ ] Controller has NO database queries (calls repository only).
- [ ] Controller has NO model instantiation.
- [ ] Resource classes used for response formatting.

### Validation Layer
- [ ] Form Request created for each mutation endpoint.
- [ ] `authorize()` method checks permissions/ownership.
- [ ] `rules()` method defines all validation rules.
- [ ] Custom Rule classes used for reusable validation logic.
- [ ] Arabic error messages provided for all user-facing validation errors.

### Service Layer
- [ ] Service class created with business logic.
- [ ] Service receives dependencies via constructor injection (interfaces where applicable).
- [ ] Service methods have clear single responsibility.
- [ ] Service throws typed exceptions for business rule violations.
- [ ] Service dispatches events for cross-module communication.
- [ ] No raw SQL in service (uses repository only).

### Repository Layer
- [ ] Repository class created with data access logic.
- [ ] Repository extends base Repository or implements RepositoryInterface.
- [ ] Repository has methods matching query patterns: `findById`, `findByUserId`, `findAllPaginated`, `create`, `update`, `delete`.
- [ ] Repository uses Eloquent ORM (no raw SQL).
- [ ] Repository returns typed model objects (not arrays).

### DTO Layer
- [ ] DTO created for request data (input DTO).
- [ ] DTO created for response data (output DTO) if needed.
- [ ] DTO properties are typed and `readonly`.
- [ ] DTO has `fromRequest()` or `fromArray()` named constructor.
- [ ] DTO has `toArray()` method for serialization if needed.

### Events & Listeners
- [ ] Event class created if feature has cross-module impact.
- [ ] Event carries a DTO (not the Eloquent model).
- [ ] Listener created in the consuming module.
- [ ] Event dispatched after transaction commit.
- [ ] Event and listener registered in `EventServiceProvider`.

### API Layer
- [ ] Route defined with correct HTTP method, URI, and middleware.
- [ ] API version in URI (`/api/v1/...`).
- [ ] Proper status codes returned (200 success, 201 created, 422 validation, etc.).
- [ ] Response structure follows API contract (consistent `data`, `error`, `meta` keys).
- [ ] Pagination implemented for list endpoints (with `meta` containing total/per_page/page).
- [ ] Result limit enforced (default 25, max 100 per page).

### Error Handling
- [ ] Error code mapped in error catalog (`config/errors.php` or error enum).
- [ ] Typed exception class created (extends domain base exception).
- [ ] Exception handler returns structured error response.
- [ ] Error message provided in both Arabic and English.
- [ ] Exception context includes relevant data (user_id, amount, entity_id).

### Ledger & Financial Impact
- [ ] Ledger impact documented: which accounts are debited/credited.
- [ ] Double-entry validated (total debits = total credits).
- [ ] Ledger posting logic tested.
- [ ] FX impact documented if multi-currency involved.
- [ ] Reconciliation path defined (how this transaction is traceable in GL).

### Fraud & Compliance
- [ ] Fraud rules considered (velocity, amount thresholds, geographic anomalies).
- [ ] Compliance fields included (purpose of transaction, source of funds if required).
- [ ] AML screening triggers defined (if applicable).
- [ ] Suspicious activity reporting requirements documented.
- [ ] Daily/monthly limit enforcement verified.

### Testing
- [ ] Feature test covers success path (200/201 response, correct DB state).
- [ ] Feature test covers validation error (400/422 response).
- [ ] Feature test covers authentication error (401 response).
- [ ] Feature test covers authorization error (403 response).
- [ ] Feature test covers not-found error (404 response).
- [ ] Feature test covers business rule failure (422 with specific error code).
- [ ] Feature test covers server error path (500 if applicable).
- [ ] Edge cases tested: empty results, maximum values, duplicate submissions, concurrent requests.

---

## 2. Flutter Feature DoD (Mobile)

### Screen Development
- [ ] Screen created with all states: loading, empty, error, success.
- [ ] Loading state shows skeleton loader (not spinner) for list screens.
- [ ] Empty state shows helpful illustration and message in Arabic.
- [ ] Error state shows retry button and descriptive Arabic message.
- [ ] Success state shows confirmation with relevant next action.

### Offline Support
- [ ] API response cached in SQLite for offline access.
- [ ] Screen renders cached data when offline (with "offline" indicator).
- [ ] Write operations queued locally when offline.
- [ ] Queue persists across app restarts.
- [ ] Queue flushes successfully when connectivity restored.

### Navigation & Deep Linking
- [ ] Screen registered in GoRouter.
- [ ] Deep link enabled if screen is a shareable resource.
- [ ] Back navigation preserves form state.
- [ ] Bottom navigation active state correct.

### RTL & Arabic
- [ ] RTL layout verified on physical device.
- [ ] All text rendered with correct Arabic glyphs.
- [ ] Arabic-Indic numerals displayed where appropriate.
- [ ] Currency formatted with ل.س and thousands separator.
- [ ] Dates formatted in Arabic (ordinal not required, but month names Arabic).
- [ ] English text (system language) also renders correctly.

### Translations
- [ ] All user-facing strings extracted to ARB files.
- [ ] No hardcoded Arabic or English strings in widget code.
- [ ] ARB file entries have descriptions for context.
- [ ] Translation keys follow namespace convention: `{screen}.{element}.{state}`.
- [ ] Missing translations fall back to English gracefully.

### Accessibility
- [ ] Semantics labels added for all interactive elements.
- [ ] Screen reader navigation order verified.
- [ ] Touch targets minimum 48x48dp.
- [ ] Color contrast meets WCAG AA (4.5:1 for text).
- [ ] Font sizes scalable (no hardcoded px values).

### Testing
- [ ] Widget test renders screen with all states (loading, data, error, empty).
- [ ] Widget test verifies key UI elements and interactions.
- [ ] Unit test covers service/repository logic.
- [ ] Integration test covers critical user flow.

### Syria-Specific
- [ ] Phone input handles +963 correctly.
- [ ] National ID field validated with Syrian format.
- [ ] Amount input in SYP rejects decimals.
- [ ] Agent features: GPS, camera permissions requested properly.
- [ ] Low bandwidth mode considered (image compression, batch requests).

---

## 3. React Admin DoD (Admin Panel)

### Page Development
- [ ] Page created with loading, empty, error, and data states.
- [ ] Loading state shows skeleton (matching layout structure).
- [ ] Empty state shows "لا توجد بيانات" with icon.
- [ ] Error state shows error message and retry button.
- [ ] Pagination/filtering implemented for list pages.

### API Integration
- [ ] React Query hooks created for all API calls.
- [ ] Optimistic updates for mutation operations where safe.
- [ ] Cache invalidation configured on mutation success.
- [ ] Error handling in mutation `onError` callback.
- [ ] Loading indicators during mutations (disabled button, overlay).

### Arabic Support
- [ ] All labels from translation files (no hardcoded strings).
- [ ] RTL layout verified (sidebar, tables, modals, forms).
- [ ] Arabic number formatting (If needed) or Western numerals consistent.
- [ ] Date/time formatted for Syria timezone.
- [ ] CSV exports have Arabic headers.

### Testing
- [ ] Component renders with data.
- [ ] Component shows loading state.
- [ ] Component shows error state.
- [ ] Interactions tested (clicks, form submission).
- [ ] No console errors in tests.

---

## 4. General DoD (All Changes)

### Code Quality
- [ ] Code reviewed by at least 1 peer (2 for payment/financial changes).
- [ ] All review comments addressed or discussed.
- [ ] No P0/P1 bugs open against the feature.
- [ ] No debug code (`dd`, `var_dump`, `print_r`, `console.log`) present.
- [ ] No dead code (commented-out blocks, unused imports).
- [ ] Class ≤ 200 lines, method ≤ 20 lines.
- [ ] Type hints present on all PHP methods.

### CI/CD
- [ ] All CI pipelines pass (lint, type check, test, security scan).
- [ ] Coverage thresholds met (backend ≥ 80% feature, ≥ 70% unit).
- [ ] No new security vulnerabilities introduced.
- [ ] Static analysis (PHPStan/Psalm) passes at level max.
- [ ] No failing tests.

### Performance
- [ ] P99 API response < 200ms (measured).
- [ ] N+1 query checked (eager loading used).
- [ ] Database indexes present for new query patterns.
- [ ] Heavy processing dispatched as queue job.
- [ ] Caching considered for read-heavy endpoints.

### Feature Flags
- [ ] Feature wrapped in feature flag (if not ready for full rollout).
- [ ] Feature flag configurable per environment.
- [ ] Feature flag default is `false` (opt-in).
- [ ] Clean-up ticket created for flag removal.

### Documentation
- [ ] API endpoint documented (OpenAPI/Swagger).
- [ ] Configuration values documented (env vars, config files).
- [ ] Database schema changes noted.
- [ ] User-facing changes communicated to product team.
- [ ] Internal change (architecture, refactor) documented in `docs/`.

### Rollback Plan
- [ ] Migration reversible (tested `down()`).
- [ ] Feature can be disabled via feature flag.
- [ ] Queue jobs can be cancelled if misbehaving.
- [ ] Old API version still available (if API change is breaking).
- [ ] Rollback runbook exists for complex changes.

---

## DoD Verification

The developer checks all applicable items and marks them complete before requesting review. The reviewer validates DoD compliance during code review. The QA engineer validates DoD compliance during testing.

| Role | DoD Responsibility |
|------|-------------------|
| Developer | Self-check all items before submitting PR |
| Code Reviewer | Verify Backend / Flutter / General DoD during review |
| QA Engineer | Verify all items pass in staging before release sign-off |
| Engineering Lead | Final sign-off for production releases |

---

*A feature without passing DoD is not done. Do not deploy it.*
