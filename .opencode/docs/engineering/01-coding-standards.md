# Coding Standards — Beza Platform

## Principles

### SOLID
- **Single Responsibility**: Every class has exactly one reason to change. Controllers handle HTTP, services handle business logic, repositories handle data access.
- **Open/Closed**: Classes open for extension, closed for modification. Use traits, interfaces, and service injection rather than modifying existing classes.
- **Liskov Substitution**: Subtypes must be substitutable for their base types. Never violate parent contracts in child classes.
- **Interface Segregation**: Many small, focused interfaces over one large interface. Split `WalletInterface` into `WalletReadInterface`, `WalletWriteInterface`, etc.
- **Dependency Inversion**: Depend on abstractions, not concretions. All services receive their dependencies via constructor injection using interfaces.

### DRY (Don't Repeat Yourself)
- Extract shared logic into service classes, traits, or helper functions.
- If the same SQL query or validation rule appears in two places, extract it to a Repository or Rule class.
- Duplicate string literals must be extracted to constants or config files.

### KISS (Keep It Simple)
- A function should do one thing and do it well. If a method requires a comment to explain what it does, it's too complex.
- Favor flat structures over deep nesting. Use early returns and guard clauses.
- Prefer framework conventions over custom solutions. Laravel's built-in features (events, queues, notifications) should be used before building custom alternatives.

### YAGNI (You Ain't Gonna Need It)
- Do not build for hypothetical future requirements. Build for what the current ticket requires.
- Do not add generic abstraction layers (interfaces, factories, strategies) unless there are at least two concrete implementations.
- Do not add caching until performance measurements demonstrate the need.

## Naming Conventions

| Element | Convention | Example |
|---------|-----------|---------|
| Classes | PascalCase | `WalletService`, `TransferController`, `AgentRepository` |
| Methods | camelCase | `getBalance()`, `processTransfer()`, `validateLimit()` |
| Variables | camelCase | `$senderWallet`, `$amountInSyp`, `$agentId` |
| Constants | UPPER_SNAKE | `MAX_TRANSACTION_AMOUNT`, `SYRIAN_POUND_CODE` |
| Config | snake_case | `wallet.daily_limit`, `fx.spread_percentage` |
| Enums | PascalCase (cases UPPER_SNAKE) | `TransactionStatus::PENDING`, `AgentRole::SUPER_AGENT` |
| DB Tables | snake_case (plural) | `wallet_transactions`, `user_wallets`, `agent_commissions` |
| DB Columns | snake_case | `sender_wallet_id`, `created_at`, `is_verified` |
| Routes | kebab-case | `/api/v1/wallet-transfers`, `/agent/cash-out` |

## Code Organization

### File Structure
- One class per file (PHP, Dart, TypeScript).
- Filename must match class name exactly (case-sensitive on Linux deployments).
- Namespace matches directory structure from app root.

### Size Limits
- Maximum 200 lines per class. If a class exceeds 200 lines, extract responsibilities into separate classes.
- Maximum 20 lines per method. If a method exceeds 20 lines, extract sub-methods.
- Maximum 3 levels of nesting. Beyond that, extract into a separate method.

### Formatting
- No trailing whitespace on any line.
- Files must end with exactly one newline.
- UTF-8 encoding for all source files (no BOM).
- Indentation: 4 spaces for PHP/Dart, 2 spaces for TypeScript/JavaScript.
- Line length: maximum 120 characters (80 preferred for inline comments).

## Error Handling

### Exception Rules
- NEVER swallow exceptions. Every `catch` block must either handle the exception or log it with context.
- ALWAYS use typed exceptions. Never throw or catch generic `\Exception`.
- Custom exception classes extend domain-specific base exceptions (e.g., `WalletException`, `TransferException`).
- All production exceptions must have a corresponding error code from the error catalog.

### Structured Error Responses
```json
{
  "success": false,
  "error": {
    "code": "WALLET_INSUFFICIENT_BALANCE",
    "message": "الرصيد غير كافي لإتمام هذه العملية",
    "details": {
      "available": 5000,
      "required": 15000,
      "currency": "SYP"
    },
    "request_id": "01JANYZ123...",
    "timestamp": "2026-05-29T10:30:00+03:00"
  }
}
```

### Logging Requirements
- ALL errors must include: `request_id`, `user_id` (if authenticated), `action`, `amount` (if financial), `entity_type`, `entity_id`.
- Use structured logging (JSON format) for all production logs.
- Never log full request bodies or sensitive PII (phone numbers masked, passwords never logged).

## Security Rules (Syria-Specific)

### SQL Injection Prevention
- NO raw SQL string concatenation. Always use Eloquent ORM, Query Builder, or prepared statements.
- NO `DB::select("...")` with string interpolation. Use parameter binding or the ORM.
- All user input must pass through Laravel's validation layer before reaching any query.

### Secrets Management
- NO secrets (API keys, DB passwords, encryption keys) in source code.
- All secrets loaded from environment variables via `config/` files.
- `.env.example` contains placeholder values only — no real secrets.
- Syria sanctions compliance: third-party API keys must be stored encrypted at rest.

### Debug Artifacts
- NO `dd()`, `var_dump()`, `print_r()`, `ray()`, `logger()->debug()` in committed code.
- Pre-commit hooks must scan for and block these patterns.
- Any debug output in a PR is grounds for immediate rejection.

### Input Validation (Syria Context)
- ALL input validated using a whitelist approach (reject unknown fields, allow known fields).
- Syrian phone numbers validated against country code +963 with 9-digit subscriber number format: `/^\+9639[0-9]{8}$/`.
- Syrian Pound amounts validated for positive integers only (no fractional SYP).
- National ID (رقم وطني) validated against Syrian format: 11-12 digit numeric.
- All monetary amounts must have an associated currency field.

### ID Strategy
- ALL primary keys exposed via API must be ULIDs or UUIDs. Never expose auto-increment IDs.
- Internal auto-increment IDs may exist for indexing but must never be returned in API responses.
- ULIDs preferred over UUIDs for sortability and readability.
