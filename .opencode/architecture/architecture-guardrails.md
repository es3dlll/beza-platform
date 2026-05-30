# Architecture Guardrails — Non-Negotiable Rules

> Status: **ENFORCED** · Last Updated: 2026-05-29
> Violation = PR Rejected. No exceptions without ADR amendment.

---

## G-001: Zero Direct Ledger Access

**No module may read/write Ledger tables or call Ledger services directly.**

```
✅ Wallet → CFE → PostingEngine → Ledger
❌ Wallet → LedgerAccount::where(...)
❌ Wallet → DB::table('journal_entries')
❌ Wallet → AccountService (direct DI)
```

**Enforcement:** Any `use Modules\Ledger\*` outside `Modules\Ledger/` and `Modules/CoreFinancialEngine/` is rejected.

## G-002: Every Financial Operation Must Pass Through CFE

**Zero exceptions. No bypass.**

```
All money movement:
  1. CFE PostingEngine::execute()
  2. CFE FeeEngine::apply() if applicable
  3. CFE HoldEngine::place() if hold required
  4. CFE ReversalEngine::reverse() if correction
```

**Violation examples:**
- `WalletService` calling `JournalService::post()` directly
- A Controller doing `$account->balance += $amount`
- Any `DB::raw('UPDATE accounts SET balance = ...')`

## G-003: Controller-Service-Repository — Strict Layering

```
Controller  ← HTTP concern only (validation, response)
    ↓
Service     ← Business logic (orchestration)
    ↓
Repository  ← Data access (queries, persistence)
```

**Rules:**
- Controller NEVER calls Repository directly
- Controller NEVER contains `if` business logic
- Service NEVER returns JSON/HTTP response
- Repository NEVER contains business logic
- Service NEVER calls another module's Repository (use their Service)

## G-004: Money Must Use `App\Domain\ValueObjects\Money`

**Float is forbidden for monetary values.**

```php
// ✅ Correct
$price = Money::fromInt(50000, Currency::SYP());  // SYP 500.00

// ❌ Forbidden
$price = 50000;
$price = 500.00;
$price = (float) $amount;
```

**All amounts in database:** `bigint` (minor units, e.g., cents/syrian piasters).

**All amounts in APIs:** Integer minor units. Frontend renders formatting.

## G-005: Journal Entries Are Append-Only (WORM)

**No UPDATE or DELETE on `journal_entries` or `journal_lines`.**

- Errors → post a **reversal** entry (opposite direction)
- Corrections → post a **correcting** entry
- Deletion → **not allowed** under any circumstances

```sql
-- ❌ Forbidden
UPDATE journal_entries SET ... WHERE id = '...';
DELETE FROM journal_lines WHERE ...;

-- ✅ Allowed
INSERT INTO journal_entries (...) VALUES (...);  -- reversal
```

## G-006: Every Module Must Follow Standard Template

```
ModuleName/
├── Controllers/
├── Services/
├── Repositories/
├── Models/
├── DTOs/
├── Events/
├── Exceptions/
├── Http/Requests/
├── Providers/
├── Routes/
├── Jobs/
├── Contracts/
├── Database/
│   ├── Migrations/
│   └── Factories/
├── Resources/lang/{ar,en}/
└── Tests/
```

**Missing directories** (even if empty) → PR rejected.

## G-007: ULID for All Primary Keys

**No auto-increment. No UUIDv4. Only ULID.**

```php
$model->id = Str::ulid()->toBase32();
```

**Why:** Time-sortable, URL-safe, 26 chars, no collision risk.

## G-008: All External Communication via Events

**Modules communicate through Events, not through each other's classes.**

```php
// ✅ Correct
event(new WalletCredited($walletId, $amount));

// ❌ Forbidden — calling another module's service directly
$notificationService->sendSms(...);
$walletService->deduct(...);
```

Each module owns its Events. Listeners can be in other modules.

## G-009: Every API Error Must Map to Error Catalog

**No generic error messages. No `500` without code.**

Every response error includes:
```json
{
  "success": false,
  "error": {
    "code": "WALLET_INSUFFICIENT_BALANCE",
    "message": "Insufficient balance for this transfer",
    "message_ar": "الرصيد غير كافٍ لهذا التحويل",
    "details": { "available": 50000, "required": 100000 }
  }
}
```

Error codes must be registered in `docs/api/02-error-catalog.md`.

## G-010: Feature Tests Required for Every Public Endpoint

**No endpoint ships without a Feature test that covers:**

1. Happy path (200/201)
2. Validation failure (422)
3. Auth failure (401) if applicable
4. Business rule violation (422/409)

---

## Violation Protocol

| Severity | Action |
|----------|--------|
| **BLOCKER** (G-001, G-002, G-004, G-005) | PR rejected immediately. Fix required before merge. |
| **MAJOR** (G-003, G-006, G-008) | PR flagged. Requires explanation + fix within 24h. |
| **MINOR** (G-009, G-010) | Warning issued. Must be fixed before next PR. |

## Enforcement Automation

To be implemented:
- [ ] PHPStan rule: no `Modules\Ledger\*` import outside Ledger/CFE
- [ ] PHPStan rule: no `float` type hint for monetary values
- [ ] PHPStan rule: every Controller method returns `JsonResponse`
- [ ] GitHub Action: guardrail violation check on PR
