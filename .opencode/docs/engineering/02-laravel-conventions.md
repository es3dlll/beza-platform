# Laravel Conventions — Beza Platform

## Module Structure (Modular Monolith)

All backend code lives under `app/Modules/`. Each bounded domain is a self-contained module.

```
Modules/
├── Wallet/
│   ├── Controllers/
│   │   ├── WalletController.php
│   │   ├── TransferController.php
│   │   └── WalletStatementController.php
│   ├── Models/
│   │   ├── Wallet.php
│   │   └── WalletTransaction.php
│   ├── Services/
│   │   ├── WalletService.php
│   │   ├── TransferService.php
│   │   └── BalanceService.php
│   ├── Repositories/
│   │   ├── WalletRepository.php
│   │   └── WalletTransactionRepository.php
│   ├── DTOs/
│   │   ├── TransferRequestDto.php
│   │   ├── WalletCreateDto.php
│   │   └── BalanceInquiryDto.php
│   ├── Events/
│   │   ├── WalletCredited.php
│   │   └── WalletDebited.php
│   ├── Listeners/
│   │   ├── WalletCreditedHandler.php
│   │   └── WalletDebitedHandler.php
│   ├── Exceptions/
│   │   ├── InsufficientBalanceException.php
│   │   ├── WalletNotFoundException.php
│   │   └── DailyLimitExceededException.php
│   ├── Routes/
│   │   └── api.php
│   ├── Database/
│   │   ├── Migrations/
│   │   │   ├── 2026_01_01_000001_create_wallets_table.php
│   │   │   └── 2026_01_01_000002_create_wallet_transactions_table.php
│   │   └── Seeders/
│   │       └── WalletSeeder.php
│   ├── Requests/
│   │   ├── CreateWalletRequest.php
│   │   └── TransferRequest.php
│   └── Resources/
│       ├── WalletResource.php
│       └── TransactionResource.php
├── Agent/
│   ├── Controllers/
│   ├── Models/
│   ├── Services/
│   └── ...
├── FX/
│   ├── Controllers/
│   ├── Models/
│   ├── Services/
│   └── ...
├── Ledger/
│   ├── Controllers/
│   ├── Models/
│   ├── Services/
│   └── ...
└── Auth/
    ├── Controllers/
    ├── Models/
    ├── Services/
    └── ...
```

## Patterns (Strict Enforcement)

### Controllers — ONLY HTTP Plumbing
Controllers have exactly one responsibility: parse HTTP input, call a service, return an HTTP response.
- NO business logic (ifs, calculations, state checks).
- NO database queries.
- NO instantiation of models.
- Maximum 3 lines of logic before extracting to a service.

```php
// ✅ CORRECT
public function transfer(TransferRequest $request): JsonResponse
{
    $dto = TransferRequestDto::fromRequest($request);
    $result = $this->transferService->execute($dto);
    return new TransferResource($result);
}

// ❌ WRONG
public function transfer(Request $request): JsonResponse
{
    $wallet = Wallet::find($request->sender_wallet_id);
    if ($wallet->balance < $request->amount) {
        return response()->json(['error' => 'Insufficient balance'], 422);
    }
    $wallet->balance -= $request->amount;
    $wallet->save();
    // ...
}
```

### Services — ALL Business Logic
Service classes contain all domain logic. They are stateless singletons injected via the container.
- Each service focuses on one domain (e.g., `TransferService`, not `FinanceService`).
- Services call repositories for data access.
- Services dispatch events for cross-module communication.
- Services throw typed exceptions for business rule failures.

### Repositories — ALL Database Queries
Repository classes contain every database query for a given model. No query logic lives outside repositories.
- Extend a base `Repository` class with common methods: `findById`, `findAll`, `create`, `update`, `delete`.
- Repository methods return Eloquent models or collections, never raw arrays.
- Complex queries use query scopes on the model, called from the repository.
- Each repository receives its model via constructor injection.

```php
class WalletRepository extends Repository
{
    public function __construct(Wallet $model)
    {
        parent::__construct($model);
    }

    public function findByUserId(string $userId): ?Wallet
    {
        return $this->model->where('user_id', $userId)->first();
    }

    public function lockForUpdate(string $walletId): Wallet
    {
        return $this->model->where('id', $walletId)->lockForUpdate()->firstOrFail();
    }
}
```

### DTOs — Typed Data Transfer
All data passed between layers (controller → service, service → repository) uses typed DTOs.
- DTOs are immutable (`readonly` properties in PHP 8.1+).
- DTOs have named constructors: `fromRequest()`, `fromArray()`.
- DTOs validate data type at construction (not null checks — those are for validation layer).
- Never pass `$request->all()`, `$request->validated()`, or raw arrays between layers.

### Events — Cross-Module Communication
Modules NEVER call each other's methods directly or import each other's models.
- Module A dispatches an event. Module B listens for it.
- Events carry DTOs, not models (to avoid coupling).
- Listener handles the event by calling its own service layer.
- Events are dispatched after the transaction commits (using `afterCommit` or queued listeners).

```php
// In TransferService
public function execute(TransferRequestDto $dto): TransactionDto
{
    return DB::transaction(function () use ($dto) {
        // Debit sender, credit receiver
        $transaction = $this->processTransfer($dto);

        // Dispatch event for other modules
        TransferCompleted::dispatch(
            new TransferCompletedDto(
                transactionId: $transaction->id,
                senderId: $dto->senderUserId,
                receiverId: $dto->receiverUserId,
                amount: $dto->amount,
                currency: $dto->currency,
            )
        );

        return $transaction;
    });
}
```

### Jobs — Heavy Processing
Any operation that takes >500ms must be queued as a job.
- Jobs are dispatched from services, never from controllers.
- Jobs have a `tags()` method for monitoring and debugging.
- Failed jobs notify the team via the `failed` event.
- Syria-specific: jobs respect Friday (day of rest) scheduling for non-urgent processing.

## Naming Conventions

| Type | Convention | Example |
|------|-----------|---------|
| Controller | `{Resource}Controller` | `WalletController`, `AgentController` |
| Service | `{Domain}Service` | `TransferService`, `CommissionService` |
| Repository | `{Model}Repository` | `WalletRepository`, `AgentRepository` |
| DTO | `{Action}Dto` | `TransferRequestDto`, `BalanceInquiryDto` |
| Event | `{Entity}{Action}` (past tense) | `WalletCredited`, `UserRegistered` |
| Listener | `{Event}Handler` | `WalletCreditedHandler`, `UserRegisteredHandler` |
| Job | `{Task}Job` | `ProcessBulkPayrollJob`, `GenerateStatementJob` |
| Mail | `{Purpose}Mail` | `TransferReceiptMail`, `VerificationCodeMail` |
| Rule | `{Entity}{Validation}` | `WalletBalanceRule`, `DailyLimitRule` |
| Form Request | `{Action}Request` | `TransferRequest`, `CreateWalletRequest` |
| Resource | `{Model}Resource` | `WalletResource`, `TransactionResource` |
| Scope | `{Filter}` | `ActiveScope`, `VerifiedScope` |

## Validation

### Form Requests
- ALL controller validation uses Form Request classes. No inline `$request->validate()` in controllers.
- The `authorize()` method checks permissions/ownership before allowing the request.
- The `rules()` method returns validation rules.
- Custom validation messages returned in Arabic for user-facing errors.

```php
class TransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('transfer', Wallet::class)
            && $this->user()->id === $this->route('wallet')->user_id;
    }

    public function rules(): array
    {
        return [
            'receiver_phone' => ['required', 'string', 'regex:/^\+9639[0-9]{8}$/'],
            'amount' => ['required', 'integer', 'min:100', 'max:5000000'],
            'currency' => ['required', 'string', 'in:SYP,USD'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'receiver_phone.regex' => 'رقم الهاتف يجب أن يكون بصيغة سورية صحيحة',
            'amount.min' => 'الحد الأدنى للتحويل هو 100 ليرة سورية',
            'amount.max' => 'الحد الأقصى للتحويل هو 5,000,000 ليرة سورية',
        ];
    }
}
```

### Custom Rule Objects
Reusable validation logic extracted to Rule classes:
- `WalletBalanceRule` — validates sufficient balance
- `DailyLimitRule` — validates daily transaction limit
- `PhoneFormatRule` — validates Syrian phone numbers
- `NationalIdRule` — validates Syrian national ID format
- `SuspiciousAmountRule` — flags amounts matching fraud patterns

## Database

### Migration Rules
- ALL schema changes via migrations. Direct SQL modifications on the database are forbidden.
- Migrations must be reversible: `down()` method always drops/rolls back the change.
- Migration filenames include timestamp + description, e.g., `2026_01_01_000001_create_wallets_table.php`.
- Never edit a migration that has been deployed. Create a new migration for changes.

### Indexing Requirements
- Foreign keys: ALWAYS indexed (Laravel does this by default with `foreignId()`).
- Status fields: ALWAYS indexed (`status`, `is_verified`, `is_active`).
- Date range queries: ALWAYS indexed (`created_at`, `updated_at`, `completed_at`).
- Phone numbers: ALWAYS indexed (lookup by phone is the most common query).
- Composite indexes: Add for multi-column WHERE clauses used in queries.

### Soft Deletes
- ALL user-facing entities use soft deletes (`deleted_at` column).
- Include `Spatie\DeletedModels` or similar for cascading soft deletes.
- API queries filter out soft-deleted records by default (global scope).

### Audit Columns
Tables tracking financial or identity data include:
- `created_by` — ULID of the user who created the record
- `updated_by` — ULID of the user who last updated the record
- `created_at` / `updated_at` — timestamps (automatic)
- `deleted_at` — soft delete timestamp (if applicable)

### Syria-Specific Database Rules
- All monetary columns store amounts in smallest unit (SYP: lira, no sub-units; USD: cents).
- Currency stored as ISO 4217 code (SYP, USD, EUR, TRY).
- Syrian phone numbers stored with country code (+963) included.
- All timestamps stored in UTC with timezone conversion at application layer to Syria time (UTC+3 / AST).
