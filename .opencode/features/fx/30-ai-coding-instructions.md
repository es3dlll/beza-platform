# FX Engine AI Coding Instructions

## Instructions for AI Code Generation Agent

This file contains the exact instructions for an AI coding agent to implement the FX Engine feature. Follow these specifications precisely.

## Implementation Order
```
Phase 1 (Files 1-10): Database migrations + Models + Enums
Phase 2 (Files 11-20): Provider interfaces + Provider implementations
Phase 3 (Files 21-30): Services (RateProviderService, RateEngine, RateLockService)
Phase 4 (Files 31-40): Actions + Controllers + API routes + Policies
Phase 5 (Files 41-50): Events + Listeners + Jobs
Phase 6 (Files 51-60): Tests + Factories
Phase 7 (Files 61-70): Flutter screens + Providers + Widgets
```

## Migration Files to Create

### 1. Create FX Rates Table
```php
// database/migrations/2026_01_01_000501_create_fx_rates_table.php
// Schema definition in 16-database-schema.md
// Fields: id, tenant_id, pair (enum:SYP/USD,SYP/EUR,USD/EUR), bid, ask, mid, 
//         spread_pct, beza_rate, source, provider_id, response_time_ms,
//         is_stale, is_override, override_by, override_reason, recorded_at, expires_at
// Partitioned by month (RANGE on UNIX_TIMESTAMP(recorded_at))
// Partitions: p_2026_01 through p_2026_12 + p_future
// Indexes: pair, recorded_at, pair+time, provider, source
// Foreign keys: provider_id → fx_rate_providers.id, tenant_id → tenants.id
```

### 2. Create FX Rate Providers Table
```php
// database/migrations/2026_01_01_000502_create_fx_rate_providers_table.php
// Schema definition in 16-database-schema.md
// Fields: id, tenant_id, name, type (enum:api,scraper,manual), handler_class, priority,
//         status (enum:active,inactive,degraded), supported_pairs (json), base_url,
//         health_url, credentials_encrypted (text, encrypted), timeout_ms (default 2000),
//         retry_count (default 3), rate_limit_per_minute, consecutive_failures,
//         max_consecutive_failures (default 3), circuit_breaker_until, metadata (json),
//         last_success_at, last_failure_at, last_failure_reason, avg_response_time_ms,
//         uptime_24h, timestamps, soft_deletes
// Indexes: status, priority, type
```

### 3. Create FX Rate Locks Table
```php
// database/migrations/2026_01_01_000503_create_fx_rate_locks_table.php
// Fields: id, tenant_id, lock_id (unique), user_id, pair, rate, amount, source_currency,
//         target_currency, status (enum:active,used,expired,released), transaction_id,
//         idempotency_key, expires_at, used_at, timestamps
// Indexes: user, status, expires_at, transaction_id
// Unique: user_id + pair + status (only one active lock per user per pair)
```

### 4. Create FX Conversions Table
```php
// database/migrations/2026_01_01_000504_create_fx_conversions_table.php
// Schema definition in 16-database-schema.md
// Fields: id, tenant_id, uuid (unique), user_id, lock_id, rate_id, source_wallet_id,
//         target_wallet_id, source_currency, target_currency, source_amount,
//         target_amount, rate_used, mid_rate, spread_pct, spread_amount, fee,
//         fee_currency, total, status (enum:pending,completed,failed,reversed),
//         cfe_hold_id, cfe_posting_id, cfe_reference,
//         source_balance_before, source_balance_after,
//         target_balance_before, target_balance_after,
//         idempotency_key, reference, ip_address, user_agent, metadata (json),
//         reversed_at, reversal_reason, timestamps
// Indexes: user, status, created_at, pair, lock, idempotency, cfe_reference
```

### 5. Create FX Conversion Limits Table
```php
// database/migrations/2026_01_01_000505_create_fx_conversion_limits_table.php
// Fields: id, tenant_id, kyc_level, source_currency, target_currency,
//         daily_max, monthly_max, per_txn_max, per_txn_min
// Unique: kyc_level + source_currency + target_currency
// Seeded with initial values for KYC levels 0,1,2
```

### 6. Create FX Spread Config Table
```php
// database/migrations/2026_01_01_000506_create_fx_spread_config_table.php
// Fields: id, tenant_id, pair, user_tier (enum:basic,standard,premium,merchant),
//         spread_pct, min_spread_amount, max_spread_amount, is_active, created_by,
//         timestamps
// Unique: pair + user_tier + is_active
// Seeded with initial spreads: basic 4%, standard 3%, premium 1.5%, merchant 2%
```

### 7. Create FX CBS Reports Table
```php
// database/migrations/2026_01_01_000507_create_fx_cbs_reports_table.php
// Fields: id, tenant_id, report_date, report_type, pair, cbs_official_rate,
//         beza_avg_rate, beza_spread_avg, volume_converted, transaction_count,
//         generated_by, report_data (json), exported_at, timestamps
// Unique: report_date + pair + report_type
```

### 8. Create FX Rate Overrides Table (Audit Trail)
```php
// database/migrations/2026_01_01_000508_create_fx_rate_overrides_table.php
// Append-only audit table. No UPDATE/DELETE allowed.
// Fields: id, pair, old_rate, new_rate, reason, duration_minutes,
//         effective_from, effective_until, overridden_by, overridden_by_name,
//         overridden_by_role, twofa_token, ip_address, user_agent, session_id,
//         affected_providers, created_at
// Indexes: pair, overridden_by, created_at
```

## Model Files to Create

### RateProviderInterface
```php
// app/Modules/FX/Providers/Contracts/RateProviderInterface.php
interface RateProviderInterface {
    public function fetch(CurrencyPair $pair): RateResult;
    public function health(): HealthCheckResult;
    public function name(): string;
    public function supports(CurrencyPair $pair): bool;
}

// Each provider class must implement this interface:
// - CbsOfficialProvider: Fetches from CBS API/daily bulletin
// - ParallelMarketProvider: Fetches from exchange house API
// - BlackMarketProvider: Fetches from scraper/aggregator
// - CorridorProvider: Diaspora corridor rates
// - ManualOverrideProvider: Returns admin-set rate if override active

// Example implementation:
class CbsOfficialProvider implements RateProviderInterface
{
    public function __construct(
        private readonly HttpClient $http,
        private readonly ProviderConfig $config,
    ) {}

    public function fetch(CurrencyPair $pair): RateResult
    {
        $response = $this->http->get($this->config->endpoint, [
            'headers' => $this->config->headers,
            'timeout' => $this->config->timeout ?? 2000,
        ]);

        $data = $response->json();

        // Parse CBS response format
        return new RateResult(
            pair: $pair,
            bid: $data['buy_rate'],
            ask: $data['sell_rate'],
            mid: ($data['buy_rate'] + $data['sell_rate']) / 2,
            source: $this->name(),
            providerId: $this->config->providerId,
            responseTimeMs: $response->durationMs(),
            timestamp: now(),
        );
    }

    public function health(): HealthCheckResult
    {
        try {
            $start = microtime(true);
            $response = $this->http->head($this->config->healthUrl, ['timeout' => 2000]);
            $ms = (microtime(true) - $start) * 1000;
            return new HealthCheckResult(true, (int)$ms);
        } catch (Throwable $e) {
            return new HealthCheckResult(false, null, $e->getMessage());
        }
    }

    public function name(): string { return 'CBS Official'; }
    public function supports(CurrencyPair $pair): bool { return true; }
}
```

### FxRate Model
```php
// app/Modules/FX/Models/FxRate.php
// Relations: provider()
// Scopes: latest(), recent(int $minutes), byPair(CurrencyPair $pair), fresh()
// Methods: isFresh(int $maxAgeSeconds), isExpired(), withStaleIndicator()
// Casts: pair (CurrencyPair enum), bid/ask/mid/beza_rate (decimal),
//         is_stale/is_override (boolean), recorded_at/expires_at (datetime)
```

### FxRateProvider Model
```php
// app/Modules/FX/Models/FxRateProvider.php
// Relations: rates()
// Scopes: active(), byType(RateProviderType), byPriority()
// Methods: isAvailable(), recordSuccess(int $responseTimeMs), recordFailure(string $reason)
// Accessors: decrypted_config (decrypts credentials_encrypted on access)
// Casts: type (RateProviderType enum), status (RateProviderStatus enum),
//         supported_pairs (array), metadata (array)
```

### FxRateLock Model
```php
// app/Modules/FX/Models/FxRateLock.php
// Relations: user(), conversion()
// Scopes: active(), expired(), byUser(int $userId)
// Methods: isExpired(), remainingSeconds(), use(string $transactionId)
// Casts: pair (CurrencyPair enum), status (RateLockStatus enum),
//         rate (decimal), expires_at/used_at (datetime)
```

### FxConversion Model
```php
// app/Modules/FX/Models/FxConversion.php
// Relations: user(), sourceWallet(), targetWallet(), rate(), lock()
// Scopes: completed(), failed(), byPair(), byUser(int $userId)
// Methods: isCompleted(), canReverse()
// Casts: source_currency/target_currency (Currency enum),
//         status (ConversionStatus enum), rate_used/mid_rate (decimal)
```

## Service Implementation Notes

### RateProviderService
```php
// constructor injection: RateProviderRepository, RateRepository, RateCacheService, 
//   EventService, FailoverChain, Logger
// fetchRates(CurrencyPair $pair): RateResult
//   1. Get active providers sorted by priority + ML score
//   2. Filter out circuit-breaker providers
//   3. Iterate: try each provider's fetch()
//   4. On success: validate rate, cache, persist, emit RateUpdated
//   5. On failure: mark provider degraded, continue to next
//   6. If all fail: return stale cache or throw AllProvidersDownException
// resolveProvider(FxRateProvider $provider): RateProviderInterface
//   - Instantiate provider class with decrypted config
// validateRate(RateResult $rate, CurrencyPair $pair): bool
//   - Rate > 0, deviation < 20%, bid < ask, spread < max, freshness < 60s
```

### RateEngine
```php
// constructor injection: RateProviderService, RateCacheService, CbsReportingService
// getLiveRate(CurrencyPair $pair, ?User $user): BezaRate
//   1. Try cache first (fresh < 15s)
//   2. If stale/miss: fetch from RateProviderService
//   3. Calculate spread based on user tier + pair
//   4. Apply spread to mid rate → beza_rate
//   5. Return BezaRate DTO
// calculateSpread(CurrencyPair $pair, ?User $user): float
//   1. Get base spread from config per pair
//   2. Apply tier multiplier (premium gets discount)
//   3. Clamp to max spread (5% regulatory, 4% self-imposed)
// applySpread(float $midRate, float $spread): float
//   - mid × (1 + spread)
```

### RateLockService
```php
// constructor injection: Redis, RateLockRepository, EventService
// lockRate(LockRateRequest $request): RateLockResult
//   1. Generate lockId (UUID)
//   2. Lua script: SET fx:lock:{pair}:{userId} with NX and EXPIRE 30
//   3. If success: persist lock record in DB, emit RateLocked
//   4. If conflict: throw RateLockConflictException
// useLock(string $lockId, string $transactionId): bool
//   1. Find lock by lockId (DB + Redis)
//   2. Validate lock not expired
//   3. Mark as USED, attach transaction_id
//   4. Clean up Redis key
// releaseLock(string $lockId): bool
//   1. Lua script: only release if we own the lock
//   2. Mark as RELEASED in DB
// CRITICAL: Use Lua scripts for atomicity. Never use GET+SET (race condition).
```

### HedgeService
```php
// constructor injection: RateRepository, ConversionRepository, ProviderConfig
// calculateNetExposure(): NetExposure
//   - Sum all conversions today, group by currency
//   - Calculate net long/short per currency
// shouldHedge(): bool
//   - Check if net exposure exceeds thresholds:
//     USD: $10K, EUR: €5K
// executeHedge(): HedgeResult
//   - If net position exceeds threshold
//   - Execute reverse trade with provider
//   - Record hedge expense
```

### RateAnomalyService
```php
// detectAnomalies(): array
//   For each CurrencyPair:
//   1. Spread widening: current spread > 2x avg of last 10 samples
//   2. Price spike: |rate_change| > 5% in 1 minute
//   3. Provider divergence: max-min provider rates > 10%
//   Emit RateAnomalyDetected for each anomaly found
```

## Flutter Implementation Notes

### State Management
- Use Riverpod with code generation (@riverpod annotation)
- Four main providers: FXRateListProvider, ConversionFormProvider, RateLockProvider, ConversionExecutionProvider
- Auto-refresh rates every 15s via Timer (pause on background)
- Optimistic updates for conversions (debit instantly, confirm on API success)

### Screens
1. ExchangeHomeScreen: List of RateCardWidgets with pull-to-refresh
2. RateDetailScreen: Expanded pair detail with source breakdown + 24h chart
3. ConvertScreen: Wallet picker + amount input + rate preview + lock button
4. ConversionResultScreen: Success animation, receipt, share button
5. ConversionHistoryScreen: Paginated list with filters
6. AdminFXDashboard: Provider health, override panel, spread config, anomaly feed

### Key Widgets
- RateCardWidget: Rate display with bid/ask, source dots, sparkline
- RateLockTimer: Circular countdown animation (green → amber → red)
- ConvertPreviewCard: Source amount, rate, target amount, spread display
- SourceBreakdown: Expandable provider list with health dots
- ProviderHealthCard: Status, response time, uptime, last success/failure

### Offline Support
- SQLite cache for last known rates (TTL 5 min stale allowed)
- Last 50 conversions cached locally
- Offline: show stale rates with amber indicator, disable conversion (online required)
- Rate locking disabled when offline

## Testing Requirements
- Minimum 80% code coverage on services
- All API endpoints have 200, 400, 401, 403, 409, 410, 422, 503 response tests
- Test every provider implementation (mock HTTP)
- Rate lock atomicity test (concurrent lock attempts)
- E2E tests for: view rates, lock rate, convert, rate expiry
- Test failover chain (all providers failing, partial failure)
- Test anomaly detection (spread widening, price spike, provider divergence)
- Test spread calculation for every user tier + pair combination

## Key Architectural Decisions

1. **RateProviderInterface pattern**: Each provider is a standalone class implementing `fetch()`, `health()`, `name()`, `supports()`. New providers can be added without modifying existing code.

2. **Cascade failover**: RateProviderService iterates through providers sorted by priority + ML score. If one fails, it tries the next. Failure is never fatal unless ALL providers fail.

3. **Rate lock with Redis + Lua for atomicity**: Rate locks use Redis SET NX EX with Lua scripts to prevent race conditions. The Lua script atomically checks for existing locks and creates a new one.

4. **CFE integration for conversion posting**: Every completed conversion is posted to the Core Financial Engine (CFE) using double-entry accounting. The conversion service orchestrates: hold source → credit target → post FX income.

5. **Partitioned rate history**: fx_rates table is partitioned by month for query performance and easy archival of data older than 90 days.

6. **Encrypted provider credentials**: API keys and tokens are stored encrypted (AES-256-CBC) using a dedicated encryption key, separate from the application key.

7. **Circuit breaker pattern**: Providers with consecutive failures enter a circuit breaker state. They are skipped during rate fetch until the cooldown period expires.
```
