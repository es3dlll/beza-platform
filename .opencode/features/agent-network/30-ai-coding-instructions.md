# Agent Network AI Coding Instructions

## Instructions for AI Code Generation Agent

This file contains the exact instructions for an AI coding agent to implement the Agent Network feature. Follow these specifications precisely.

## Implementation Order
```
Phase 1 (Files 1-8): Database migrations + Models + Enums
Phase 2 (Files 9-18): Repositories + Services + Actions
Phase 3 (Files 19-28): Controllers + API routes + Policies
Phase 4 (Files 29-36): Events + Listeners + Jobs
Phase 5 (Files 37-46): Tests + Factories
Phase 6 (Files 47-56): Flutter screens + Providers + Widgets
Phase 7 (Files 57-60): Offline sync + Printer + Biometric + USSD
```

## Migration Files to Create

### 1. Create Agents Table
```php
// database/migrations/2026_01_01_010001_create_agents_table.php
// Schema definition in 16-database-schema.md — agents table
// Fields: id, user_id (nullable), tenant_id, code (unique, VARCHAR 16),
//         full_name, phone (unique), shop_name, shop_type (enum),
//         address, city, district, location (POINT SRID 4326),
//         status (enum: pending/active/suspended/terminated, default: pending),
//         tier (enum: bronze/silver/gold/platinum, default: bronze),
//         float_balance (BIGINT default 0), commission_rate_cash_in (DECIMAL 5,4),
//         commission_rate_cash_out (DECIMAL 5,4),
//         max_cash_in_per_txn, max_cash_out_per_txn,
//         max_cash_in_daily, max_cash_out_daily, max_float_balance (all BIGINT),
//         pending_commission, total_commission_earned, total_transactions (all default 0),
//         operating_hours (JSON nullable), preferred_language (default 'ar'),
//         kyc_status (enum), kyc_approved_at, kyc_approved_by, kyc_expires_at,
//         last_login_at, last_activity_at, metadata (JSON nullable),
//         timestamps, soft_deletes
// Indexes: status, tier, city, district, phone, last_activity
// Spatial index: location
// Foreign keys: user_id → users.id, tenant_id → tenants.id

// NOTE: Add FULLTEXT index on (full_name, shop_name) for agent search
// NOTE: Add generated column for distance queries if needed
```

### 2. Create Agent Transactions Table
```php
// database/migrations/2026_01_01_010002_create_agent_transactions_table.php
// Schema in 16-database-schema.md — agent_transactions table
// Fields: id, tenant_id, agent_id, uuid (unique CHAR 36),
//         type (enum: cash_in/cash_out/float_funding/float_transfer_in/
//                   float_transfer_out/commission/adjustment),
//         status (enum: pending/completed/failed/reversed, default: pending),
//         amount, fee(0), commission(0), balance_before, balance_after (all BIGINT),
//         customer_phone (nullable), customer_wallet_id (nullable),
//         customer_balance_before, customer_balance_after (nullable),
//         counterparty_agent_id (nullable),
//         idempotency_key (nullable, VARCHAR 64),
//         verification_id (nullable), verification_method (enum nullable),
//         device_id (nullable VARCHAR 128), ip_address (nullable VARCHAR 45),
//         location (POINT nullable), offline_queued (BOOLEAN default false),
//         offline_queued_at (nullable), synced_at (nullable),
//         notes (nullable VARCHAR 500), metadata (JSON nullable),
//         reversed_by (nullable), reversal_reason (nullable), reversed_at (nullable),
//         timestamps
// Indexes: agent_id+created_at, type, status, customer_phone,
//          idempotency_key, device_id, offline_queued+synced_at
// Spatial index: location
// Foreign keys: agent_id → agents.id, customer_wallet_id → wallets.id,
//               counterparty_agent_id → agents.id
```

### 3. Create Agent Float Funding Table
```php
// database/migrations/2026_01_01_010003_create_agent_float_funding_table.php
// Schema in 16-database-schema.md — agent_float_funding table
```

### 4. Create Agent Commissions Table
```php
// database/migrations/2026_01_01_010004_create_agent_commissions_table.php
// Schema in 16-database-schema.md — agent_commissions table
```

### 5. Create Agent Commission Settlements Table
```php
// database/migrations/2026_01_01_010005_create_agent_commission_settlements_table.php
// Schema in 16-database-schema.md — agent_commission_settlements table
```

### 6. Create Agent Devices Table
```php
// database/migrations/2026_01_01_010006_create_agent_devices_table.php
// Schema in 16-database-schema.md — agent_devices table
```

### 7. Create Agent Tier Config Table (Seeded)
```php
// database/migrations/2026_01_01_010007_create_agent_tier_config_table.php
// Schema + seed data in 16-database-schema.md — agent_tier_config table
// Insert the tier config seed data as part of migration
```

## Model Files to Create

### Agent Model
```php
// app/Modules/Agent/Models/Agent.php
// Relations:
//   - user(): BelongsTo User (nullable — agent might not have wallet yet)
//   - transactions(): HasMany AgentTransaction
//   - floatFundings(): HasMany AgentFloatFunding
//   - commissions(): HasMany AgentCommission
//   - device(): HasOne AgentDevice
//   - sentTransfers(): HasMany AgentTransaction (where type = float_transfer_out)
//   - receivedTransfers(): HasMany AgentTransaction (where type = float_transfer_in)
// Scopes:
//   - scopeActive(): where status = 'active'
//   - scopeByTier(tier): where tier = X
//   - scopeByCity(city): where city = X
//   - scopeNearby(lat, lng, radius): spatial distance query
//   - scopeLowFloat(): where float_balance < 100000
//   - scopePendingKyc(): where kyc_expires_at < NOW()
// Methods:
//   - isActive(): bool
//   - isAvailable(): bool (active + device online)
//   - hasSufficientFloat(Money $amount): bool
//   - canCashIn(Money $amount): bool (uses LimitService)
//   - canCashOut(Money $amount): bool
//   - debitFloat(Money $amount): void
//   - creditFloat(Money $amount): void
//   - accrueCommission(Money $amount): void
//   - settleCommission(): Money (returns pending amount, resets to 0)
//   - incrementTransactionCount(): void
//   - distanceTo(float $lat, float $lng): float
// Casts:
//   - status: AgentStatus enum
//   - tier: AgentTier enum
//   - float_balance: integer
//   - operating_hours: array
//   - metadata: array
//   - location: custom Point cast (returns AgentLocation VO)
//   - kyc_status: KycStatus enum
//   - created_at: datetime
//   - updated_at: datetime
```

### AgentTransaction Model
```php
// app/Modules/Agent/Models/AgentTransaction.php
// Relations:
//   - agent(): BelongsTo Agent
//   - customerWallet(): BelongsTo Wallet (nullable)
//   - counterpartyAgent(): BelongsTo Agent (nullable)
// Scopes:
//   - scopeToday(), scopeThisMonth(), scopeByType(), scopeCompleted()
//   - scopePendingSync(): where offline_queued = true AND synced_at IS NULL
// Methods:
//   - isCashIn(): bool
//   - isCashOut(): bool
//   - canReverse(): bool (within 2 hours of creation)
//   - reverse(string $reason): void
// Casts: type (AgentTransactionType), status (AgentTransactionStatus),
//        amount (integer), fee (integer), commission (integer)
```

### AgentCommission Model
```php
// app/Modules/Agent/Models/AgentCommission.php
// Relations: agent(), transaction(), settlement()
// Scopes: accrued(), settled()
```

### AgentCommissionSettlement Model
```php
// app/Modules/Agent/Models/AgentCommissionSettlement.php
// Relations: commissions()
// Methods: markCompleted(), markFailed(string $error)
```

### AgentDevice Model
```php
// app/Modules/Agent/Models/AgentDevice.php
// Relations: agent()
// Methods: isOnline(), markSeen(), assignCertificate()
```

## Service Implementation Notes

### AgentService
```php
// constructor injection: AgentRepository, AgentDeviceRepository, FloatService,
//   CommissionService, LimitService, CustomerVerificationService, EventService

// register(array $data): Agent
//   1. Validate: phone uniqueness, national_id uniqueness, age > 18
//   2. Check: no existing agent within 500m
//   3. Create agent with status = 'pending', tier = 'bronze', float = 0
//   4. Upload documents to storage (S3/local)
//   5. Generate agent_code (format: BZ-{next_id})
//   6. Dispatch ProcessAgentKycJob
//   7. Emit AgentRegistered event
//   8. Return agent

// approve(int $agentId, int $approvedBy): Agent
//   1. Load agent with documents
//   2. Verify all required documents exist
//   3. Set status = 'active'
//   4. Create device record (generate X.509 certificate)
//   5. Send welcome SMS (includes initial PIN)
//   6. Emit AgentApproved event

// suspend(int $agentId, string $reason): Agent
//   1. Check FSM: active → suspended
//   2. Set status = 'suspended'
//   3. Invalidate all active sessions
//   4. Emit AgentSuspended event

// getByLocation(float $lat, float $lng, float $radiusKm): Collection
//   - Spatial query using ST_Distance_Sphere
//   - Return active agents within radius, ordered by distance

// getPerformanceStats(int $agentId): array
//   - Query today's volume, monthly volume, total txns, commission
//   - Calculate rank among agents in same city
```

### FloatService
```php
// constructor injection: AgentRepository, AgentFloatRepository,
//   WalletService, EventService

// getBalance(int $agentId): Money
//   - Return cached float (Redis) or DB
//   - Cache TTL: 60 seconds

// canDebit(int $agentId, Money $amount): bool
//   - Compare current float >= amount

// debit(int $agentId, Money $amount, string $reason): AgentTransaction
//   - Check canDebit (throw InsufficientFloatException if not)
//   - Create AgentTransaction (type depends on reason)
//   - Update agent.float_balance -= amount
//   - Check if now low/critical → emit event

// credit(int $agentId, Money $amount, string $reason): AgentTransaction
//   - Create AgentTransaction
//   - Update agent.float_balance += amount
//   - Check tier max float (throw MaxFloatExceededException if exceeded)

// topUp(int $agentId, Money $amount, FloatFundingSource $source): AgentFloatFunding
//   - Ensure amount > 0
//   - If wallet: call WalletService to debit agent's wallet, then credit float
//   - If cash: create pending funding record (hub verifies later)
//   - If agent-to-agent: debit source, credit target (both in transaction)
//   - Check tier max float before crediting
//   - Create AgentFloatFunding record

// reconcile(int $agentId): ReconciliationResult
//   - Calculate expected float from transaction history
//   - Compare with actual float_balance
//   - Return discrepancy
```

### CommissionService
```php
// constructor injection: AgentCommissionRepository, AgentRepository,
//   WalletService, EventService

// calculateCashInCommission(int $amount, AgentTier $tier): Money
//   - Rate map: Bronze 0.003, Silver 0.004, Gold 0.005, Platinum 0.006
//   - Minimum commission: 100 SYP
//   - Return Money

// calculateCashOutCommission(int $amount, AgentTier $tier): Money
//   - Rate map: Bronze 0.005, Silver 0.006, Gold 0.0075, Platinum 0.01
//   - Minimum commission: 200 SYP
//   - Return Money

// accrueCommission(int $agentId, Money $amount, string $txnRef): AgentCommission
//   - Insert agent_commission record (status = 'accrued')
//   - Update agent.pending_commission += amount
//   - Emit CommissionEarned event

// settleDaily(): AgentCommissionSettlement
//   1. BEGIN TRANSACTION
//   2. Query all agents with pending_commission > 0
//   3. For each agent:
//      a. settlementAmount = agent.pending_commission
//      b. Skip if agent.user_id is null
//      c. Credit user's wallet: settlementAmount (via WalletService)
//      d. Insert agent_commission_settlement record
//      e. Update all 'accrued' commissions to 'settled'
//      f. Reset agent.pending_commission = 0
//      g. Insert agent_transaction (type=commission, amount=settlementAmount)
//   4. COMMIT
//   5. Emit CommissionSettled events
//   6. Return settlement batch
```

### CashInService
```php
// execute(CashInRequest $request): TransactionResult
//   1. Validate agent is active
//   2. Verify customer SMS code (CustomerVerificationService)
//   3. Check agent float sufficient (FloatService.canDebit)
//   4. Check agent daily cash-in limit (LimitService)
//   5. Check customer wallet max balance
//   6. Check idempotency (duplicate prevention)
//   7. BEGIN DB TRANSACTION
//   8. Debit agent float (FloatService.debit)
//   9. Credit customer wallet (WalletService.credit)
//   10. Calculate commission (CommissionService.calculateCashInCommission)
//   11. Accrue commission (CommissionService.accrueCommission)
//   12. Create AgentTransaction record
//   13. COMMIT
//   14. Emit AgentCashInCompleted, CommissionEarned events
//   15. Return TransactionResult with receipt data
```

### CashOutService
```php
// execute(CashOutRequest $request): TransactionResult
//   1. Validate agent is active
//   2. Verify customer SMS code
//   3. Verify customer PIN
//   4. If amount > 500K: check biometric_verified (or is Platinum agent)
//   5. Check customer wallet sufficient balance
//   6. Check agent daily cash-out limit
//   7. Check customer daily cash-out limit
//   8. Check idempotency
//   9. BEGIN DB TRANSACTION
//   10. Debit customer wallet (amount + fee)
//   11. Credit agent float (amount only)
//   12. Recognize Beza fee income (fee portion)
//   13. Calculate commission (CommissionService.calculateCashOutCommission)
//   14. Accrue commission
//   15. Create AgentTransaction record
//   16. COMMIT
//   17. Emit AgentCashOutCompleted, CommissionEarned events
//   18. Return TransactionResult
```

## Flutter Implementation Notes

### State Management
- Use Riverpod with code generation (@riverpod annotation)
- Providers:
  - agentAuthProvider: login, logout, session management, PIN change
  - agentFloatProvider: balance, daily stats, top-up
  - agentCashInProvider: multi-step wizard state (phone, code, amount, confirm)
  - agentCashOutProvider: multi-step wizard state (same + PIN + biometric + handover)
  - agentTransactionsProvider: paginated list, filters, search, export
  - agentCommissionProvider: summary, settlement history
  - agentSyncProvider: offline queue management, sync triggers
- All providers handle AsyncValue states (loading, error, data)
- Implement optimistic updates for offline mode (queue then sync)

### Screens
1. AgentPosHomeScreen:
   - Float card (top), daily stats strip, two giant action buttons (cash-in green, cash-out red), recent transactions preview, alert banners, offline queue badge
   - Pull-to-refresh triggers sync
   - States: loading, empty (new agent), error, offline, float low/critical

2. CashInScreen (multi-step wizard):
   - StepIndicator (4 steps)
   - Step 0: Phone input (numeric keypad)
   - Step 1: SMS code verification (4-digit input)
   - Step 2: Amount input + commission estimate
   - Step 3: Confirmation + processing + result
   - All steps handle loading/slow/error states

3. CashOutScreen (multi-step wizard):
   - StepIndicator (5 steps)
   - Steps 0-2: Same as cash-in
   - Step 3: Amount + fee breakdown + customer PIN entry + biometric (if needed)
   - Step 4: Cash handover confirmation
   - All states: loading, error, block (PIN locked), biometric fail

4. FloatManagementScreen:
   - Float card, 7-day mini chart, top-up options (wallet, agent, cash), recent float movements

5. TransactionHistoryScreen:
   - Date filter (today/yesterday/custom), type chips, search, paginated list, export button
   - Pull-to-refresh

6. CommissionScreen:
   - Summary card (today/month/pending), 6-month chart, settlement history list

### Offline Support
- SQLite database with tables: agent_profile, agent_transactions (cache), offline_queue, float_history
- OfflineQueueItem: id, type (cash_in/cash_out), payload (JSON), idempotency_key, created_at, retry_count, status (pending/failed)
- Max queue size: 50 items (alert when >40)
- Sync triggers: app foreground, connectivity restored (ConnectivityPlus), pull-to-refresh, periodic (5 min timer)
- Sync strategy: FIFO, send batch POST /agent/sync, process results (mark success/fail)
- On sync failure: retry 3x with exponential backoff (2s, 5s, 15s), then move to failed queue

### Bluetooth Printer
- Package: esc_pos_printer or similar
- Auto-connect to last known MAC address (stored in secure storage)
- Generate receipt data as ESC/POS commands
- Arabic text encoding (CP-1256 or UTF-8)
- QR code with transaction reference
- Print on success; retry on failure

### Biometric
- Use local_auth package (Android BiometricPrompt)
- Check device capability before prompting
- On success: include biometric_verified: true in API request
- On fail: fallback to PIN only
- For high-value (>500K): biometric is required, if unavailable then block transaction

## Testing Requirements
- Minimum 90% code coverage on services (AgentService, FloatService, CommissionService, CashInService, CashOutService)
- All API endpoints test: 200, 400, 401, 403, 409, 422, 500 responses
- Test all Arabic error messages match exactly (assertJsonPath)
- Test offline queue: queue → sync → success/failure handling
- Test concurrent transactions (race conditions on float balance)
- Test agent status FSM transitions (every valid + invalid transition)
- Test commission calculation for every tier and edge case (minimum commission, zero amount)
- Test float debit/credit thread safety
- Test spatial queries (nearby agents)
- Flutter widget tests for all screens covering all states (loading, empty, error, offline, success)
