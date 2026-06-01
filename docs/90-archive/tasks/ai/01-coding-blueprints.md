# AI Coding Blueprints — Agent-Executable Generation Prompts

## Blueprint 1: Generate Wallet Laravel Module

Prompt for AI code generation agent:

```markdown
You are a senior Laravel developer. Generate a complete Wallet module for Beza Platform following these exact specifications:

## Module Location
`app/Modules/Wallet/`

## Migrations
Create these migration files:
1. `2026_01_01_000001_create_wallets_table.php`
   - Fields: id (bigIncrements), user_id (foreignId→users), tenant_id (foreignId→tenants), cfe_account_id (string, unique), currency (enum: SYP, USD), type (enum: main, savings, card, merchant), status (enum: active, frozen, closed, dormant), kyc_level (tinyInteger, default 0), daily_sent (bigInteger, default 0), daily_sent_at (datetime, nullable), monthly_sent (bigInteger, default 0), monthly_sent_at (datetime, nullable), metadata (json, nullable), timestamps, softDeletes
   - Indexes: user_id, tenant_id, currency, status
   - Unique: user_id + currency + type + deleted_at

2. `2026_01_01_000002_create_wallet_transactions_table.php`
   - Fields from the Wallet Feature Bible 16-database-schema.md
   - All indexes as specified

3. `2026_01_01_000003_create_wallet_balance_history_table.php`
   - Partitioned by month

4. `2026_01_01_000004_create_wallet_daily_limits_table.php`
   - Seed with KYC level limits

5. `2026_01_01_000005_create_transfer_requests_table.php`

## Models
Generate with full relationships, scopes, casts:

### Wallet.php
- BelongsTo: user, tenant
- HasMany: transactions (as either sender or recipient)
- Scopes: active(), byCurrency($currency), main()
- Methods: isActive(), canSend(Money $amount), hasSufficientBalance(Money $amount)
- Casts: currency (WalletCurrency enum), type (WalletType enum), status (WalletStatus enum), metadata (array), daily_sent (integer)

### WalletTransaction.php
- BelongsTo: senderWallet (Wallet), recipientWallet (Wallet), agent (Agent), merchant (Merchant)
- Scopes: completed(), pending(), failed(), today(), thisMonth(), byType($type)
- Methods: isDebit(), canReverse(), reverse($reason)
- Casts: type (TransactionType enum), status (TransactionStatus enum), currency (Currency enum), amount (integer), fee (integer)

## Enums
Generate as PHP 8.1 backed enums:
- TransactionType: send, receive, cash_in, cash_out, bill_payment, airtime, card_payment, loan_disbursement, loan_repayment, savings_deposit, savings_withdrawal, fee, refund, reversal
- TransactionStatus: pending, completed, failed, reversed, disputed, expired
- WalletCurrency: SYP, USD
- WalletType: main, savings, card, merchant
- WalletStatus: active, frozen, closed, dormant

## Services

### FeeService
```php
class FeeService {
    public function calculateTransferFee(int $amount, Currency $currency, ?User $user = null): int
    // Standard: 0.5% capped at 5,000 SYP / $5 USD
    // Premium: free (first 20/month)
    // Min fee: 100 SYP
}
```

### LimitService
```php
class LimitService {
    public function getDailyLimit(User $user, Currency $currency): int
    public function getPerTransactionLimit(User $user, Currency $currency): int
    public function getRemainingDailyLimit(User $user, Currency $currency): int
    public function validateTransfer(int $amount, Currency $currency, User $user): void
    // Throws DailyLimitExceededException or InvalidAmountException
}
```

### TransferService
```php
class TransferService {
    public function send(SendMoneyRequest $request): TransactionResult
    // 1. Validate sender wallet
    // 2. Check sufficient balance
    // 3. Check daily limit
    // 4. Find/create recipient wallet
    // 5. Calculate fee
    // 6. Execute CFE hold → post → settle
    // 7. Persist transaction
    // 8. Emit events
    // 9. Return result with receipt
}
```

## Controllers
Generate RESTful controllers with proper validation, authorization, and error handling. Include:
- WalletController: balance(), balanceByCurrency(), transactions(), transactionDetail()
- TransferController: send(), requestMoney(), respondToRequest(), pendingRequests()

## Routes
```php
Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::get('/wallet/balance', [WalletController::class, 'balance']);
    Route::post('/wallet/transfer/send', [TransferController::class, 'send']);
    // ... all routes from 15-backend-api.md
});
```

Generate ALL files with complete implementations (not stubs). Use proper PHP type hints, docblocks, and Laravel best practices.
```

## Blueprint 2: Generate Flutter Wallet Screens

```markdown
Generate a complete Flutter Wallet feature implementation at `lib/features/wallet/`.

## Architecture
- Clean Architecture: data/domain/presentation layers
- State management: Riverpod with code generation (@riverpod annotation)
- Navigation: GoRouter integration at `lib/core/router/app_router.dart`

## Files to Generate

### Domain
1. `lib/features/wallet/domain/entities/wallet_balance.dart`
   - class WalletBalance with balances (Map<String, int>), lastUpdated (DateTime), hidden (bool)
   - copyWith(), formattedBalance(currency), toggleHidden()

2. `lib/features/wallet/domain/entities/transaction.dart`
   - id, type (TransactionType enum), status (TransactionStatus enum), amount (int), fee (int), currency (String), counterpartyName, counterpartyPhone, note, timestamp, reference
   - Factory fromJson(), toJson()

### Data
3. `lib/features/wallet/data/datasources/wallet_remote_datasource.dart`
   - getAllBalance(), getTransactions(page, filter, search), sendMoney(), requestMoney()

4. `lib/features/wallet/data/datasources/wallet_local_datasource.dart`
   - SQLite via drift: cache balance, cache recent transactions, pending actions queue

5. `lib/features/wallet/data/repositories/wallet_repository_impl.dart`
   - Implements WalletRepository interface

### Presentation
6. `lib/features/wallet/presentation/providers/wallet_balance_provider.dart`
   - AsyncNotifier: fetch, refresh, toggleVisibility
   - Auto-refresh on WalletCredited/WalletDebited events

7. `lib/features/wallet/presentation/providers/transfer_form_provider.dart`
   - StateNotifier: phone, amount, fee calculation, validation
   - executeTransfer() with optimistic update

8. `lib/features/wallet/presentation/providers/transaction_list_provider.dart`
   - AsyncNotifier: pagination, filter, search, loadMore()

9. `lib/features/wallet/presentation/screens/wallet_home_screen.dart`
   - RefreshIndicator → CustomScrollView → Slivers
   - BalanceCard + QuickActionsGrid + FXTicker + RecentTransactions + SavingsGoal

10. `lib/features/wallet/presentation/screens/send_money_screen.dart`
    - Form: contact search, phone input, amount input, fee breakdown, note
    - ConfirmTransferSheet (bottom sheet with PIN/biometric)

11. `lib/features/wallet/presentation/screens/transaction_history_screen.dart`
    - FilterTabBar + SearchBar + PaginatedList + EmptyState

12. `lib/features/wallet/presentation/widgets/balance_card_widget.dart`
    - Gradient background, large amount, eye toggle, quick action buttons

13. `lib/features/wallet/presentation/widgets/amount_input_widget.dart`
    - JetBrains Mono font, auto-format commas, currency prefix

14. `lib/features/wallet/presentation/widgets/transaction_item_tile.dart`
    - Icon (color-coded by type), label, amount, status badge, timestamp

## Theme Integration
Use BezaDesignSystem tokens:
- Colors: BezaColors.primary, BezaColors.success, BezaColors.error, etc.
- TextStyles: BezaTextStyles.display, title1, body, caption
- Spacing: BezaSpacing utilities

Generate ALL files with complete implementations. Use proper Dart conventions, null safety, and Flutter best practices. All Arabic text should use the app localization system (AppLocalizations.of(context).sendMoney etc.)
```

## Blueprint 3: Generate CFE Integration

```markdown
Generate the CFE (Core Financial Engine) integration service at `app/Services/CfeService.php`.

## Interface
```php
interface CfeServiceInterface {
    public function getBalance(string $accountId): BalanceDTO;
    public function hold(string $accountId, int $amount, string $reason, int $expiresInMinutes): HoldResult;
    public function release(string $holdId, string $reason): ReleaseResult;
    public function post(PostingRequest $request): PostingResult;
    public function executeTransfer(string $senderAccountId, string $recipientAccountId, int $amount, int $fee, string $reference): TransferResult;
    public function reverse(string $transactionReference, string $reason): ReversalResult;
    public function getTransactionStatus(string $reference): TransactionStatus;
}
```

## Implementation
Implement the interface with:
1. HTTP client to CFE internal API
2. Circuit breaker (3 failures → open circuit for 30s)
3. Retry with exponential backoff (100ms, 500ms, 2s, 5s)
4. Timeout: 5s for hold, 10s for post
5. Idempotency: pass idempotency key to CFE
6. Logging: all CFE requests and responses
7. Metrics: prometheus counters for CFE calls, latency, errors

## DTOs
Generate all DTO classes with proper typing.

Generate the complete implementation.
```
