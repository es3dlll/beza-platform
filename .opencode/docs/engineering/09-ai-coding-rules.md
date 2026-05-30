# AI Coding Rules (The AI Development Contract)

## Purpose

This document defines the binding contract for ALL AI-generated code in the Beza Platform. These rules are **not suggestions** — they are enforced in code review and CI. Any AI-generated code that violates these rules will be rejected, regardless of functional correctness.

Every developer using AI tools (GitHub Copilot, Cursor, opencode, or any LLM-based coding assistant) MUST ensure the output complies with these rules.

---

## Rule 1: No Raw SQL — Eloquent ORM Required 🚨

AI must NEVER generate raw SQL queries. All database access uses Eloquent ORM or the Query Builder with parameter binding.

### ❌ Prohibited
```php
// Raw SQL — ALWAYS REJECTED
DB::select("SELECT * FROM wallets WHERE user_id = ?", [$userId]);
DB::statement("UPDATE wallets SET balance = balance - ? WHERE id = ?", [$amount, $walletId]);
DB::insert("INSERT INTO wallet_transactions (...) VALUES (?, ?, ?)", [...]);
```

### ✅ Required
```php
// Eloquent ORM — ALWAYS PREFERRED
Wallet::where('user_id', $userId)->first();
$wallet->decrement('balance', $amount);
WalletTransaction::create([...]);

// Query Builder (when ORM is insufficient)
DB::table('wallets')->where('user_id', $userId)->first();
```

### Exception
Only legitimate use case for raw SQL is in high-performance batch operations proven via profiling to be a bottleneck. Must be explicitly approved by Engineering Lead in code review.

---

## Rule 2: Repository Pattern Mandatory 🚨

AI must NEVER place database queries in Controllers or Services. ALL data access goes through Repository classes.

### ❌ Prohibited
```php
// In Controller or Service — ALWAYS REJECTED
$wallet = Wallet::where('user_id', $userId)->first();
$transactions = WalletTransaction::where('wallet_id', $id)->get();
```

### ✅ Required
```php
// In Repository
class WalletRepository extends Repository
{
    public function findByUserId(string $userId): ?Wallet
    {
        return $this->model->where('user_id', $userId)->first();
    }
}

// In Service
$wallet = $this->walletRepository->findByUserId($userId);
```

---

## Rule 3: Service Layer Mandatory 🚨

AI must NEVER place business logic in Controllers. ALL business logic goes in Service classes.

### ❌ Prohibited
```php
// In Controller — ALWAYS REJECTED
public function transfer(Request $request): JsonResponse
{
    $wallet = $this->walletRepository->find($request->sender_wallet_id);
    if ($wallet->balance < $request->amount) {
        throw new InsufficientBalanceException();
    }
    // More business logic...
}
```

### ✅ Required
```php
// In Controller
public function transfer(TransferRequest $request): JsonResponse
{
    $dto = TransferRequestDto::fromRequest($request);
    $result = $this->transferService->execute($dto);
    return new TransferResource($result);
}

// In Service
public function execute(TransferRequestDto $dto): TransactionDto
{
    $senderWallet = $this->walletRepository->lockForUpdate($dto->senderWalletId);
    $this->validateBalance($senderWallet, $dto->amount);
    // Process transfer...
}
```

---

## Rule 4: DTOs Required for Data Transfer 🚨

AI must NEVER pass raw request data, arrays, or validated data between layers. ALL inter-layer communication uses typed DTOs.

### ❌ Prohibited
```php
// Passing raw data — ALWAYS REJECTED
$service->transfer($request->all());
$service->transfer($request->validated());
$service->transfer(['sender_id' => 1, 'amount' => 5000]);
```

### ✅ Required
```php
// Typed DTO
readonly class TransferRequestDto
{
    public function __construct(
        public string $senderWalletId,
        public string $receiverPhone,
        public int    $amount,
        public string $currency,
        public ?string $note,
    ) {}

    public static function fromRequest(TransferRequest $request): self
    {
        return new self(
            senderWalletId: $request->route('walletId'),
            receiverPhone:  $request->input('receiver_phone'),
            amount:         (int) $request->input('amount'),
            currency:       $request->input('currency'),
            note:           $request->input('note'),
        );
    }
}

// Usage
$dto = TransferRequestDto::fromRequest($request);
$this->transferService->execute($dto);
```

---

## Rule 5: Feature Tests Required for Every Endpoint 🚨

AI must NEVER mark a feature complete without a corresponding feature test. Every API endpoint requires a test covering success and failure paths.

### ❌ Prohibited
```php
// No test — considers feature "done" — ALWAYS REJECTED
```

### ✅ Required
```php
test('transfers successfully when balance sufficient', function () { /* ... */ });
test('fails when balance insufficient', function () { /* ... */ });
test('fails when receiver not found', function () { /* ... */ });
test('rejects unauthenticated requests', function () { /* ... */ });
test('rejects unauthorized access to other wallet', function () { /* ... */ });
test('validates phone number format', function () { /* ... */ });
```

---

## Rule 6: No Business Logic In Controller 🚨

If a controller method has more than 3 lines of logic beyond request parsing and response formatting, AI must extract it to a Service class.

### ❌ Prohibited
```php
// Controller with business logic — ALWAYS REJECTED
public function cashIn(CashInRequest $request): JsonResponse
{
    $agent = Agent::find($request->agent_id);
    if (!$agent->is_active) {
        return response()->json(['error' => 'Agent not active'], 403);
    }
    if ($agent->daily_limit_used + $request->amount > $agent->daily_limit) {
        return response()->json(['error' => 'Daily limit exceeded'], 422);
    }
    // More logic...
}
```

### ✅ Required
```php
// Controller — thin
public function cashIn(CashInRequest $request): JsonResponse
{
    $dto = CashInDto::fromRequest($request);
    $result = $this->cashInService->execute($dto);
    return new CashInResource($result);
}

// Service — all logic
public function execute(CashInDto $dto): TransactionDto
{
    $agent = $this->agentRepository->findOrFail($dto->agentId);
    $this->agentService->validateCanCashIn($agent, $dto->amount);
    // Process...
}
```

---

## Rule 7: Events For Cross-Module Communication 🚨

AI must NEVER make one module directly call another module's methods or import another module's models. All cross-module communication uses Events and Listeners.

### ❌ Prohibited
```php
// Direct cross-module call — ALWAYS REJECTED
use App\Modules\Ledger\Services\LedgerService;

class TransferService
{
    public function __construct(
        private LedgerService $ledgerService,  // Importing another module's service
    ) {}
}

// Or worse — using another module's model directly
use App\Modules\Ledger\Models\JournalEntry;
```

### ✅ Required
```php
// Event dispatched by Wallet module
class TransferCompleted
{
    public function __construct(
        public readonly TransferCompletedDto $dto,
    ) {}
}

// In TransferService
event(new TransferCompleted($dto));

// Listener in Ledger module
class TransferCompletedHandler
{
    public function handle(TransferCompleted $event): void
    {
        $this->ledgerService->recordTransfer($event->dto);
    }
}
```

---

## Rule 8: No Magic Numbers — Constants Required 🚨

AI must NEVER hardcode numeric values, string literals, or business rules inline. ALL constants go in config files or enum classes.

### ❌ Prohibited
```php
// Magic numbers — ALWAYS REJECTED
if ($amount > 5000000) { /* flag for review */ }
if ($attempts > 3) { /* lock account */ }
$commission = $amount * 0.02;
```

### ✅ Required
```php
// In config/wallet.php
return [
    'max_transfer_amount' => env('WALLET_MAX_TRANSFER', 5_000_000),
    'suspicious_threshold' => env('WALLET_SUSPICIOUS_THRESHOLD', 3_000_000),
    'commission_rate' => env('WALLET_COMMISSION_RATE', 0.02),
];

// In enums
enum TransferLimit: int
{
    case Daily = 10_000_000;
    case Monthly = 50_000_000;
    case Single = 5_000_000;
    case Minimum = 100;
}

// Usage
if ($amount > config('wallet.max_transfer_amount')) { ... }
```

---

## Rule 9: Type Hints Required 🚨

AI must NEVER omit type hints on PHP methods. Every method has parameter types and a return type.

### ❌ Prohibited
```php
// Missing types — ALWAYS REJECTED
public function getBalance($walletId)
{
    return $this->repo->find($walletId)?->balance;
}
```

### ✅ Required
```php
// Full types — REQUIRED
public function getBalance(string $walletId): ?int
{
    return $this->walletRepository->find($walletId)?->balance;
}
```

---

## Rule 10: Error Codes Required 🚨

AI must NEVER throw or return generic errors without an error code. ALL exceptions map to the error catalog.

### ❌ Prohibited
```php
// Generic error — ALWAYS REJECTED
throw new \Exception('Insufficient balance');
throw new \RuntimeException('Transfer failed');
return response()->json(['error' => 'Something went wrong'], 500);
```

### ✅ Required
```php
// Typed exception with error code
class InsufficientBalanceException extends WalletException
{
    public function __construct(int $available, int $required)
    {
        parent::__construct(
            code: 'WALLET_INSUFFICIENT_BALANCE',
            message: 'الرصيد غير كافي لإتمام هذه العملية',
            statusCode: 422,
            context: [
                'available' => $available,
                'required' => $required,
                'shortfall' => $required - $available,
            ],
        );
    }
}
```

---

## Violations & Enforcement

| Violation | Consequence |
|-----------|-------------|
| Rule 1–10 violation in code review | PR rejected. Comment with rule reference. |
| Repeated violations (3+ in a week) | Mandatory training session on these rules. |
| Rule 1, 6, or 7 in production | Immediate rollback. Incident post-mortem. |
| Rule 8 or 9 (low severity) | Warning. Fixed before merge. |

## AI Prompt Template

When using any AI coding tool, include this instruction in the prompt:

> You are writing code for the Beza Platform. Follow the AI Development Contract: use Eloquent ORM (no raw SQL), Repository pattern for data access, Service layer for business logic, typed DTOs for data transfer, Events for cross-module communication, no magic numbers, full type hints, error catalog codes, and feature tests for every endpoint. Never place business logic in controllers.

---

*Last updated: 2026-05-29*
*This document is reviewed and updated quarterly by the Engineering Lead.*
