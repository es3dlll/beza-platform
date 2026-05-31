# Wallet AI Coding Instructions

## Instructions for AI Code Generation Agent

This file contains the exact instructions for an AI coding agent to implement the Wallet feature. Follow these specifications precisely.

## Implementation Order
```
Phase 1 (Files 1-10): Database migrations + Models + Enums
Phase 2 (Files 11-20): Repositories + Services + Actions
Phase 3 (Files 21-30): Controllers + API routes + Policies
Phase 4 (Files 31-40): Events + Listeners + Jobs
Phase 5 (Files 41-50): Tests + Factories
Phase 6 (Files 51-60): Flutter screens + Providers + Widgets
```

## Migration Files to Create

### 1. Create Wallets Table
```php
// database/migrations/2026_01_01_000001_create_wallets_table.php
// Schema definition in 16-database-schema.md
// Fields: id, user_id, tenant_id, cfe_account_id, currency (enum:SYP,USD),
//         type (enum:main,savings,card,merchant), status (enum:active,frozen,closed,dormant),
//         kyc_level, daily_sent, daily_sent_at, monthly_sent, monthly_sent_at,
//         metadata (json), timestamps, soft_deletes
// Indexes: user_id, tenant, currency, status, unique(user_id, currency, type, deleted_at)
// Foreign key: user_id → users.id, tenant_id → tenants.id
```

### 2. Create Wallet Transactions Table
```php
// database/migrations/2026_01_01_000002_create_wallet_transactions_table.php
// Schema definition in 16-database-schema.md
// Fields: id, tenant_id, uuid (unique), sender_wallet_id, recipient_wallet_id,
//         type, status, amount, fee, fee_vat, currency, fx_rate,
//         fx_source_currency, fx_target_currency, cfe_reference, cfe_hold_id,
//         cfe_posting_id, idempotency_key, note, reference,
//         sender_balance_before, sender_balance_after,
//         recipient_balance_before, recipient_balance_after,
//         device_id, ip_address, location (POINT), agent_id, merchant_id,
//         biller_id, metadata (json), reversed_by, reversal_reason, reversed_at,
//         timestamps
// Indexes: sender, recipient, status, type, created_at, tenant+date,
//          reference, idempotency, cfe_reference
```

### 3. Create Wallet Balance History Table
```php
// Partitioned by month. Monthly partitions for 2026.
```

### 4. Create Wallet Daily Limits Table
```php
// Seeded with initial limits for KYC levels 0,1,2
```

### 5. Create Transfer Requests Table
```php
// Fields: id, tenant_id, requester_id, requestee_id, amount, currency,
//         note, status (pending,accepted,declined,expired,cancelled),
//         expires_at, timestamps
```

## Model Files to Create

### Wallet Model
```php
// app/Modules/Wallet/Models/Wallet.php
// Relations: user(), transactions(), sentTransactions(), receivedTransactions()
// Scopes: active(), byCurrency(), main()
// Methods: isActive(), canSend(Money $amount), recordDailySent(), hasSufficientBalance()
// Casts: currency (WalletCurrency enum), type (WalletType enum), status (WalletStatus enum),
//         metadata (array), daily_sent (integer)
```

### WalletTransaction Model
```php
// app/Modules/Wallet/Models/WalletTransaction.php
// Relations: senderWallet(), recipientWallet(), agent(), merchant()
// Scopes: completed(), pending(), failed(), today(), thisMonth(), byType()
// Methods: isDebit(), canReverse(), reverse(string $reason)
// Casts: type (TransactionType enum), status (TransactionStatus enum),
//         currency (Currency enum), amount (integer), fee (integer)
```

## Service Implementation Notes

### TransferService
```php
// constructor injection: WalletService, FeeService, LimitService, CfeService,
//   TransactionRepository, UserRepository, EventService
// send() method:
//   1. Validate sender wallet exists and is active
//   2. Check sufficient balance (amount + fee vs available)
//   3. Check daily limit (cached + DB)
//   4. Find/validate recipient by phone number
//   5. Create or get recipient wallet
//   6. Calculate fee via FeeService
//   7. Execute CFE transfer: hold → post → settle (in try-catch with rollback)
//   8. Persist Transaction record in DB
//   9. Emit TransferSent, WalletDebited, WalletCredited events
//   10. Return TransactionResult with receipt
```

### FeeService
```php
// calculateTransferFee(int $amount, Currency $currency, ?User $user): int
//   - Premium users: free (first 20/month)
//   - Standard: 0.5% capped at 5,000 SYP / $5 USD
//   - Min fee: 100 SYP
```

### LimitService
```php
// Methods:
//   getDailyLimit(User $user, Currency $currency): int
//   getPerTransactionLimit(User $user, Currency $currency): int
//   getRemainingDailyLimit(User $user, Currency $currency, int $todayUsed): int
//   validateTransfer(int $amount, Currency $currency, User $user): void
//     → throws DailyLimitExceededException or InvalidAmountException
// Limits come from wallet_daily_limits table keyed by kyc_level + currency + type
```

## Flutter Implementation Notes

### State Management
- Use Riverpod with code generation (@riverpod annotation)
- Three main providers: walletBalanceProvider, transferFormProvider, transactionListProvider
- Implement optimistic updates for transfers
- Event bus integration for real-time balance updates

### Screens
1. WalletHomeScreen: RefreshIndicator + CustomScrollView with slivers
2. SendMoneyScreen: Form with validation, real-time fee calculation
3. TransactionHistoryScreen: Paginated list with filter tabs + search
4. TransactionDetailScreen: Status hero, amount, breakdown, receipt sharing

### Offline Support
- SQLite cache for last known balance + last 50 transactions
- Write-ahead log for pending transfers
- Sync on foreground, connectivity restore, pull-to-refresh

## Testing Requirements
- Minimum 80% code coverage on services
- All API endpoints have 200, 400, 401, 403, 422 response tests
- E2E tests for: send money, cash-in, cash-out, bill payment, balance check
- Flutter widget tests for all screens (loading, empty, error states)
