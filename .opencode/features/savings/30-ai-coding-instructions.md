# Savings AI Coding Instructions

## Module Overview
You are implementing the **Savings** feature for Beza Platform — a goal-based savings system with auto-save, round-up, profit sharing, and team goals. Arabic-first, Sharia-compliant.

## Key Architecture Decisions

1. **Feature-first Laravel module** under `app/Modules/Savings/`
2. **CFE (Core Financial Engine)** integration for all financial operations
3. **Queue-driven** for async operations (auto-save, round-up, notifications)
4. **Event-driven architecture** for extensibility
5. **Redis locks** for idempotent auto-save/round-up execution
6. **Insert-only transactions** — savings_transactions never updated, only created

## Naming Conventions

### Laravel (PHP)
- Models: `SavingsGoal`, `SavingsTransaction`, `SavingsTeam`, `SavingsTeamMember`
- Services: `GoalService`, `AutoSaveService`, `RoundUpService`, `ProfitShareService`, `TeamGoalService`
- Actions: `CreateGoalAction`, `DepositToGoalAction`, `ExecuteAutoSaveAction`
- Controllers: `GoalController`, `AutoSaveController`, `RoundUpController`, `TeamGoalController`
- Events: `GoalCreated`, `AutoSaveExecuted`, `ProfitDistributed`, etc.
- Jobs: `ProcessAutoSaveJob`, `CalculateMonthlyProfit`, `DistributeProfitJob`
- Requests: `CreateGoalRequest`, `DepositRequest`, `WithdrawRequest`
- Resources: `GoalResource`, `GoalTransactionResource`, `TeamResource`

### Flutter (Dart)
- Screens: `SavingsDashboardScreen`, `GoalDetailScreen`, `CreateGoalScreen`
- Providers: `GoalListProvider`, `GoalDetailProvider`, `CreateGoalProvider`
- Models: `GoalModel`, `GoalTransactionModel`, `TeamModel`
- Use Cases: `CreateGoalUseCase`, `DepositToGoalUseCase`, `GetGoalsUseCase`

## API Standards

### Response Format
```json
{
  "status": "success" | "error",
  "data": { ... },
  "error": {
    "code": "ERROR_CODE",
    "message": "Arabic message",
    "details": { ... }
  }
}
```

### Error Codes
```
VALIDATION_ERROR       — Input validation failed
INSUFFICIENT_BALANCE   — Main wallet balance insufficient
GOAL_NOT_FOUND         — Goal does not exist
GOAL_LOCKED            — Goal is locked (early withdrawal)
GOAL_COMPLETED         — Goal already completed
GOAL_CANCELLED         — Goal was cancelled
DUPLICATE_REQUEST      — Idempotency key already used
TEAM_FULL              — Team has max members
INVALID_INVITE_CODE    — Invite code invalid or expired
ALREADY_MEMBER         — User is already in team
```

### Idempotency
- All POST/PUT financial endpoints MUST support `Idempotency-Key` header
- Store idempotency_key in `savings_transactions.idempotency_key` (unique index)
- On duplicate: return 409 with existing transaction ID

### Pagination
- List endpoints use `page` and `per_page` query params
- Max `per_page`: 100, default: 20
- Response includes pagination metadata

## Database Rules

### Insert-Only Transactions
```php
// CORRECT: Create new record, never update
SavingsTransaction::create([...]);

// WRONG: Never update a transaction
SavingsTransaction::where('id', $id)->update([...]);

// CORRECT: If correction needed, create reversal transaction
SavingsTransaction::create([
    'type' => 'reversal',
    'reference' => "reversal:{$originalTxn->id}",
]);
```

### Goal Balance Updates
```php
// CORRECT: Use optimistic locking to prevent race conditions
SavingsGoal::where('id', $goal->id)
    ->where('current_amount', $goal->current_amount) // Optimistic lock
    ->update(['current_amount' => $goal->current_amount + $amount]);

if (DB::affectingStatement() === 0) {
    // Race condition detected, retry
    throw new OptimisticLockException();
}
```

## Sharia Compliance Checks

### Must-Have Checks
```php
trait ShariaCompliant
{
    // NEVER use the word "interest" or "فائدة"
    // ALWAYS use "profit"/"ربح" or "return"/"عائد"
    // NEVER guarantee a fixed return amount
    // ALWAYS disclose profit calculation methodology

    public function validateTerminology(array $content): void
    {
        $forbidden = ['interest', 'فائدة', 'riba', 'ربا', 'guaranteed_return'];
        foreach ($forbidden as $term) {
            if (str_contains(json_encode($content), $term)) {
                throw new ShariaComplianceException("Forbidden term: {$term}");
            }
        }
    }
}
```

### Profit Distribution Rules
```php
// Profit distribution MUST:
// 1. Be proportional to goal balance and time held
// 2. Never be guaranteed or fixed
// 3. Be calculated from actual pool returns ONLY
// 4. Deduct management fee (max 10%) transparently
// 5. Be recorded in profit_distributions table immutably

// CORRECT:
$profit = (int) ($profitPool * ($goal->current_amount / $poolTotal));

// WRONG (fixed return - implies riba):
$profit = (int) ($goal->current_amount * 0.005); // Never promise 0.5%
```

## Auto-Save Implementation Rules

```php
// Auto-save execution MUST:
// 1. Check sufficient balance in main wallet BEFORE debit
// 2. Use Redis lock to prevent double execution
// 3. Record in auto_save_logs after execution
// 4. Emit AutoSaveExecuted event
// 5. Handle insufficient balance gracefully (skip, not fail)
// 6. Track consecutive skips for nudge triggers

// Scheduling:
// - Query goals WHERE auto_save_enabled=true
//   AND status='active'
//   AND (last_execution IS NULL OR last_execution < today)
//   AND auto_save_time <= CURRENT_TIME
// - Process in batches of 500
// - Use withoutOverlapping(10) for cron
```

## Round-Up Implementation Rules

```php
// Round-up execution MUST:
// 1. Skip if source transaction is savings-related
// 2. Validate daily/monthly caps
// 3. Validate remaining main wallet balance > round-up amount
// 4. Execute as separate CFE transfer
// 5. Record source transaction reference

// ALWAYS round UP to nearest 1,000 (configurable)
// NEVER round DOWN
// Minimum round-up: 100 SYP (configurable)
```

## Team Goal Rules

```php
// Team goal implementation MUST:
// 1. Create goal as type='team' with first member as owner
// 2. Invite code: prefixed with "BEZA-SAVE-" + 6 alphanumeric chars
// 3. Max team size: 20 members
// 4. Owner can remove inactive members (7+ days no contribution)
// 5. When member leaves: refund their contribution (minus any fees)
// 6. Team milestones: based on TOTAL team progress, not individual
// 7. Profit: distributed to team goal, NOT individual members
//    (team-level profit tracking, distribution decided by team)
```

## Testing Requirements

```php
// Every financial operation MUST have:
// 1. Unit test: service method in isolation (mocked CFE)
// 2. Integration test: end-to-end with real DB + mocked CFE
// 3. API test: HTTP request → response validation
// 4. Edge case test: insufficient balance, duplicate, locked, etc.

// Minimum test coverage: 90% for services, 80% for actions, 100% for exceptions

// Performance test: 500 auto-saves < 60 seconds
// Security test: unauthorized access to other user's goal
```

## Common Mistakes to Avoid

```php
// ❌ WRONG: Direct user input without validation
$goal->current_amount += $request->amount;

// ✅ CORRECT: Use validated data through action/service
$goal = $this->goalService->deposit($goal, $validatedAmount, $user);

// ❌ WRONG: Missing idempotency check
Transaction::create([...]);

// ✅ CORRECT: Check idempotency first
if ($existing = Transaction::where('idempotency_key', $key)->first()) {
    return response()->json(['status' => 'duplicate'], 409);
}

// ❌ WRONG: Not using Redis lock for auto-save
$this->autoSaveService->execute($goal);

// ✅ CORRECT: Use lock
Cache::lock("autosave:{$goal->id}", 30)->block(5, function () use ($goal) {
    $this->autoSaveService->execute($goal);
});

// ❌ WRONG: Deleting transaction records
$transaction->delete();

// ✅ CORRECT: Transactions are insert-only
// For reversals: create reversal transaction with reference to original
```
