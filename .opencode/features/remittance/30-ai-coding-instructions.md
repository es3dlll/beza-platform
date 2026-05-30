# Remittance AI Coding Instructions

## Instructions for AI Code Generation Agent

This file contains the exact instructions for an AI coding agent to implement the Remittance/Transfer feature. Follow these specifications precisely.

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

### 1. Create Remittances Table
```php
// database/migrations/2026_01_01_000001_create_remittances_table.php
// Schema definition in 16-database-schema.md
// Fields: id, tenant_id, uuid (unique), sender_id, beneficiary_id, recipient_id,
//         corridor_id, recurring_id,
//         source_amount (DECIMAL), source_currency (enum:SYP,USD,EUR),
//         fx_rate (DECIMAL), fx_lock_id, fx_locked_at, fx_mid_market_rate,
//         target_amount (BIGINT), target_currency (enum:SYP,USD,EUR),
//         fee (DECIMAL), fee_currency, fx_spread_income,
//         type (enum:local_p2p,diaspora,recurring,request),
//         status (enum:pending,fx_locked,processing,completed,failed,cancelled,disputed),
//         delivery_method (enum:wallet,agent_pickup,bank_deposit),
//         sender_wallet_debit_id, recipient_wallet_credit_id,
//         idempotency_key, note, reference, receipt_url,
//         compliance_status (enum:pending,passed,flagged,blocked),
//         compliance_notes (json), sanctions_screened_at, source_of_funds,
//         sender_ip, sender_country, device_id,
//         cancelled_at, cancel_reason, disputed_at, dispute_reason,
//         timestamps, soft_deletes
// Indexes: sender_id, recipient_id, beneficiary_id, corridor_id, status,
//          type, created_at, idempotency_key, reference, compliance_status
// Foreign keys: sender_id→users, beneficiary_id→beneficiaries,
//               recipient_id→users, corridor_id→remittance_corridors,
//               recurring_id→recurring_transfers
```

### 2. Create Remittance Corridors Table
```php
// database/migrations/2026_01_01_000002_create_remittance_corridors_table.php
// Fields: id, tenant_id, source_currency (USD,EUR,GBP,SEK,TRY,AED,SAR),
//         target_currency (SYP,USD,EUR), source_country (CHAR 2),
//         corridor_key (unique), name_ar, name_en,
//         daily_max_sender, monthly_max_sender, per_txn_max, per_txn_min,
//         fx_spread_percent, fee_percent, fee_fixed, fee_currency,
//         required_kyc_level, source_of_funds_threshold,
//         sanctions_list, travel_rule_threshold,
//         status (active,maintenance,inactive),
//         maintenance_message, estimated_restore_at,
//         settlement_method, correspondent_bank_id,
//         settlement_currency, settlement_account, settlement_fee,
//         metadata (json), timestamps, soft_deletes
// Seeded with initial corridors from 16-database-schema.md
```

### 3. Create Recurring Transfers Table
```php
// database/migrations/2026_01_01_000003_create_recurring_transfers_table.php
// Fields: id, tenant_id, sender_id, beneficiary_id, corridor_id,
//         amount, source_currency, target_currency,
//         frequency (weekly,biweekly,monthly,quarterly),
//         day_of_month, day_of_week, execution_time (TIME),
//         duration_type (ongoing,fixed_count,end_date),
//         max_executions, end_date, executions_count, failed_count,
//         fx_locking (at_execution,at_setup), locked_fx_rate,
//         status (active,paused,cancelled,completed),
//         next_execution_at, last_executed_at,
//         paused_at, pause_reason, cancelled_at, cancel_reason,
//         total_sent_amount, total_sent_currency,
//         metadata (json), timestamps, soft_deletes
// Indexes: sender_id, beneficiary_id, status, next_execution_at, frequency
```

### 4. Create Beneficiaries Table
```php
// database/migrations/2026_01_01_000004_create_beneficiaries_table.php
// Fields: id, tenant_id, user_id, recipient_user_id,
//         name, name_en, relationship (enum), relationship_custom,
//         phone, city, country (default SY),
//         currency_preference (SYP,USD), delivery_preference (wallet,agent_pickup),
//         total_transfers, total_sent_amount, total_sent_currency,
//         last_sent_at, is_favorite,
//         sanctions_status (pending,passed,flagged,blocked),
//         sanctions_screened_at, notes,
//         status (active,inactive), is_archived,
//         timestamps, soft_deletes
```

### 5. Create Transfer Requests Table
```php
// database/migrations/2026_01_01_000005_create_transfer_requests_table.php
// Fields: id, tenant_id, requester_id, requestee_id,
//         amount, currency, note,
//         status (pending,accepted,declined,expired,cancelled),
//         expires_at, accepted_at, declined_at, decline_reason,
//         cancelled_at, remittance_id (nullable),
//         timestamps
```

### 6. Create FX Rate Logs Table
```php
// database/migrations/2026_01_01_000006_create_fx_rate_logs_table.php
// Fields: id, corridor_id, lock_id (unique),
//         rate, mid_market_rate, spread_percent,
//         source_currency, target_currency, amount,
//         locked_by_user_id, locked_at, expires_at, consumed_at,
//         expired (boolean),
//         created_at
```

### 7. Create Corridor Daily Limits Table
```php
// database/migrations/2026_01_01_000007_create_corridor_daily_limits_table.php
// Fields: id, corridor_id, user_id, date,
//         total_sent, currency
// Unique index: (corridor_id, user_id, date)
```

## Model Files to Create

### Remittance Model
```php
// app/Modules/Remittance/Models/Remittance.php
// Relations: sender(), beneficiary(), recipient(), corridor(), recurring()
// Scopes: completed(), failed(), today(), byCorridor(), byType(), byStatus()
// Methods: canCancel(), isCrossBorder(), requiresTravelRule(), requiresSourceOfFunds()
// Casts: status (RemittanceStatus enum), type (RemittanceType enum),
//         currency enums, compliance_status (ComplianceStatus enum),
//         source_amount (decimal:2), fee (decimal:2), fx_rate (decimal:4)
```

### RemittanceCorridor Model
```php
// app/Modules/Remittance/Models/RemittanceCorridor.php
// Relations: remittances(), fxRateLogs()
// Scopes: active(), byCurrency(corridorKey)
// Methods: isActive(), getEffectiveLimits(User $user)
// Casts: status (CorridorStatus enum), currency enums,
//         limits (decimal:2), percentages (decimal:2)
```

### Beneficiary Model
```php
// app/Modules/Remittance/Models/Beneficiary.php
// Relations: user(), recipientUser(), remittances(), recurringTransfers()
// Scopes: active(), favorites(), bySanctionsStatus()
// Methods: requiresSanctionsScreening(), getRelationshipLabel()
// Casts: sanctions_status (ComplianceStatus enum), status (BeneficiaryStatus enum),
//         relationship (BeneficiaryRelationship enum), is_favorite (boolean)
```

### RecurringTransfer Model
```php
// app/Modules/Remittance/Models/RecurringTransfer.php
// Relations: sender(), beneficiary(), corridor(), remittances()
// Scopes: active(), due(), failedRecently(), byFrequency()
// Methods: isDue(), calculateNextExecution(), hasReachedEnd()
// Casts: frequency (RecurringFrequency enum), status (RecurringStatus enum),
//         duration_type (RecurringDuration enum), amount (decimal:2),
//         fx_locking (string), total_sent_amount (decimal:2)
```

## Service Implementation Notes

### RemittanceService
```php
// constructor injection: RemittanceRepository, FXService, CorridorService,
//   FeeService, BeneficiaryService, ComplianceService, WalletService, EventService
// send() method:
//   1. Validate corridor is active
//   2. Check sender's daily/monthly limits per corridor
//   3. Get or validate FX rate (lock check)
//   4. Calculate fee (corridor-based, user-tier aware)
//   5. Compliance screening (sanctions, travel rule, source of funds)
//   6. Debit sender wallet (source currency via CFE)
//   7. Convert via FX (rate × amount)
//   8. Credit recipient wallet (target currency via CFE)
//   9. Persist Remittance record in DB
//   10. Emit TransferSent, RemittanceCompleted, TransferReceived events
//   11. Return RemittanceResult with full details

// cancel() method:
//   1. Check within 30-minute window
//   2. Reverse CFE hold
//   3. Release funds back to sender
//   4. Update status to cancelled
//   5. Release FX lock if unused
```

### FXService
```php
// getLiveRate(string $corridorKey): FXRate
//   - Fetch from cache (Redis, TTL 30s)
//   - Cache miss: query primary provider (XE.com), cache result
//   - Fallback: secondary provider (OANDA)
//   - Calculate spread: rate × (1 + spread_percent)
//   - Return FXRate value object

// lockRate(string $corridorKey, float $rate, int $userId): string
//   - Store lock in Redis with 70s TTL
//   - Return lock UUID
//   - Log to fx_rate_logs table

// getLockedRate(string $lockId): FXRate
//   - Fetch from Redis
//   - If not found or expired: throw FXRateExpiredException
//   - Return FXRate value object

// consumeRateLock(string $lockId, int $remittanceId): void
//   - Remove from Redis
//   - Update fx_rate_logs with consumed_at

// convert(float $amount, FXRate $rate, Currency $from, Currency $to): int
//   - If from === to: return amount (no conversion)
//   - Else: return amount × rate.rate
//   - Result in smallest unit of target currency
```

### CorridorService
```php
// getActiveCorridor(Currency $source, Currency $target, string $country): RemittanceCorridor
// validateLimit(int $dailyTotal, float $amount, User $user): void
//   → throws DailyLimitExceededException
// getEffectiveLimits(User $user, RemittanceCorridor $corridor): array
//   → returns [daily_max, monthly_max, per_txn_max, per_txn_min]
//   → applies AI-based personalization if available
```

### ComplianceService
```php
// screenTransfer(User $sender, Beneficiary $beneficiary, User $recipient,
//                float $amount, RemittanceCorridor $corridor): void
//   → Sanctions check (sender + beneficiary + recipient)
//   → Travel rule check (>$1K: capture required data)
//   → Source of funds check (>$500: verify docs uploaded)
//   → Structuring detection (aggregate same-day transfers)
//   → If any fail: throw SanctionsBlockException or flag for review

// requiresSourceOfFunds(float $amount): bool
// requiresTravelRule(float $amount): bool
```

### RecurringService
```php
// getDueTransfers(): Collection<RecurringTransfer>
//   → Query: WHERE status = 'active' AND next_execution_at <= NOW()

// executeDueTransfer(RecurringTransfer $recurring): RemittanceResult
//   → Same flow as RemittanceService::send() but:
//     - Uses recurring's beneficiary + corridor
//     - Calculates fee at recurring rate (1.0% instead of 1.5%)
//     - On success: update executions_count, last_executed_at, next_execution_at
//     - On failure: increment failed_count, schedule retry
//     - On max failures (4): set status = paused, notify sender

// calculateNextExecution(RecurringTransfer $recurring): Carbon
//   → Based on frequency + day_of_month/week
```

### FeeService
```php
// calculateLocalP2PFee(int $amount, Currency $currency): int
//   - 0.5% capped at 5,000 SYP / $5 USD
//   - Min fee: 100 SYP / $0.10

// calculateDiasporaFee(float $amount, Currency $currency, ?User $user): float
//   - Standard: 1.5% of amount
//   - Premium user: 0.75%
//   - Recurring: 1.0% (if from recurring context)

// calculateFXSpread(Currency $from, Currency $to, RemittanceCorridor $corridor): float
//   - Uses corridor's fx_spread_percent
```

## Flutter Implementation Notes

### State Management
- Use Riverpod with code generation (@riverpod annotation)
- Providers: remittanceFormProvider, fxRateProvider, beneficiaryListProvider,
  recurringTransferListProvider, remittanceHistoryProvider
- Implement rate lock timer with disposable Timer
- Beneficiary list cached locally, synced on login

### Screens
1. SendRemittanceScreen: Beneficiary selector, amount with FX preview, fee breakdown
2. ConfirmRemittanceSheet: Bottom sheet with transfer summary + PIN + biometric
3. RemittanceResultScreen: Success animation, timeline, receipt sharing
4. BeneficiaryManagementScreen: Searchable list, add/edit, favorite toggle
5. CreateRecurringTransferScreen: Frequency picker, day selector, duration
6. RemittanceHistoryScreen: Paginated, filterable by type/corridor
7. RemittanceDetailScreen: Status timeline, FX details, fee breakdown

### Offline Support
- SQLite cache for beneficiary list (synced on login)
- FX rates not cached offline (always fetch latest)
- Transfer history: last 50 cached (full text search)
- Pending transfers: write-ahead log for offline queue

## Testing Requirements
- Minimum 80% code coverage on services
- All API endpoints have 200, 400, 401, 403, 422, 451 response tests
- E2E tests for:
  - Local P2P send
  - Diaspora remittance with FX
  - Recurring transfer setup
  - Beneficiary CRUD
  - Money request flow
  - Transfer cancellation
  - Compliance block scenario
- Flutter widget tests for all screens (loading, empty, error, success states)
- FX rate lock/unlock/expiry scenarios
- Sanctions screening mock tests
- Idempotency key integration tests
