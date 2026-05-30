# FX Engine Backend Architecture

## Module Structure (Laravel)
```
app/Modules/FX/
├── Controllers/
│   ├── RateController.php          # GET rates, GET rate detail
│   ├── ConversionController.php    # POST convert, GET history
│   ├── RateLockController.php      # POST lock, release
│   └── AdminFXController.php       # Provider management, overrides
│
├── Actions/
│   ├── FetchRatesAction.php        # Orchestrate rate fetch from providers
│   ├── LockRateAction.php          # Acquire rate lock (Redis + Lua)
│   ├── ExecuteConversionAction.php # Full conversion orchestration
│   ├── CalculateSpreadAction.php   # Apply spread per user tier + pair
│   └── GenerateCbsReportAction.php # CBS-compliant rate report
│
├── Services/
│   ├── RateProviderService.php     # Multi-source rate fetching + failover
│   ├── RateEngine.php              # Rate calculation with spread
│   ├── RateLockService.php         # Short-term rate holds (Redis)
│   ├── HedgeService.php            # Minimize FX exposure
│   ├── RateCacheService.php        # Redis caching layer
│   ├── RateAnomalyService.php      # Detect unusual rate movements
│   └── CbsReportingService.php     # CBS rate report generation
│
├── Providers/
│   ├── Contracts/
│   │   └── RateProviderInterface.php  # fetch(), health(), name()
│   ├── CbsOfficialProvider.php     # CBS daily bulletin (scraper)
│   ├── ParallelMarketProvider.php  # Exchange house API integration
│   ├── BlackMarketProvider.php     # Aggregator data feed
│   ├── CorridorProvider.php        # Diaspora corridor rates
│   └── ManualOverrideProvider.php  # Admin manual entry
│
├── Repositories/
│   ├── RateRepository.php          # fx_rates CRUD + history
│   ├── RateLockRepository.php      # Rate locks CRUD
│   ├── ConversionRepository.php    # Conversions CRUD + pagination
│   └── RateProviderRepository.php  # Provider config CRUD
│
├── Models/
│   ├── FxRate.php                  # Rate model
│   ├── FxRateProvider.php          # Provider config model
│   ├── FxRateLock.php              # Rate lock model
│   └── FxConversion.php            # Conversion model
│
├── Events/
│   ├── RateUpdated.php             # New rate fetched
│   ├── RateLocked.php              # Rate lock acquired
│   ├── RateExpired.php             # Rate lock expired
│   ├── ConversionCompleted.php     # Conversion executed
│   ├── RateProviderHealthChanged.php
│   └── RateAnomalyDetected.php
│
├── Jobs/
│   ├── FetchRatesJob.php           # Cron: fetch rates every 15s
│   ├── CheckRateProviderHealth.php # Cron: health check every 30s
│   ├── ExpireRateLocksJob.php      # Cron: expire stale locks
│   ├── DetectRateAnomaliesJob.php  # Cron: anomaly scanning
│   └── ProcessConversionJob.php    # Async conversion processing
│
├── Listeners/
│   ├── LogRateFetch.php
│   ├── NotifyRateAnomaly.php
│   ├── UpdateProviderHealth.php
│   └── PostConversionToCFE.php
│
├── Rules/
│   ├── ValidConversionAmount.php
│   ├── WithinConversionLimit.php
│   └── ValidRateLock.php
│
├── Enums/
│   ├── CurrencyPair.php            # SYP/USD, SYP/EUR, USD/EUR
│   ├── RateProviderType.php        # api, scraper, manual
│   ├── RateProviderStatus.php      # active, inactive, degraded
│   ├── RateLockStatus.php          # active, used, expired, released
│   └── ConversionStatus.php        # pending, completed, failed
│
├── Exceptions/
│   ├── RateFetchFailedException.php
│   ├── RateLockExpiredException.php
│   ├── AllProvidersDownException.php
│   ├── InsufficientBalanceForConversionException.php
│   └── RateAnomalyException.php
│
├── Providers/
│   └── FXServiceProvider.php       # Module registration
│
└── routes/
    └── api.php                     # Route definitions
```

## Service Layer Detail

### RateProviderService
```php
class RateProviderService
{
    public function __construct(
        private RateProviderRepository $providerRepo,
        private RateRepository $rateRepo,
        private RateCacheService $cache,
        private EventService $eventService,
        private Logger $logger,
    ) {}

    public function fetchRates(CurrencyPair $pair): RateResult
    {
        $providers = $this->providerRepo->getActiveProvidersSortedByPriority($pair);

        foreach ($providers as $provider) {
            try {
                $instance = $this->resolveProvider($provider);
                $rate = $instance->fetch($pair);

                if ($this->validateRate($rate, $pair)) {
                    // Cache and persist
                    $this->cache->setRate($pair, $rate);
                    $this->rateRepo->recordRate($rate);
                    $this->eventService->emit(new RateUpdated($pair, $rate));
                    return $rate;
                }
            } catch (Throwable $e) {
                $this->logger->warning("Provider {$provider->name} failed for {$pair->value}", [
                    'error' => $e->getMessage(),
                    'provider_id' => $provider->id,
                ]);
                $this->markProviderDegraded($provider);
                continue; // Cascade to next provider
            }
        }

        // All providers failed
        $cached = $this->cache->getStaleRate($pair);
        if ($cached) {
            return $cached->withStaleIndicator();
        }

        throw new AllProvidersDownException("All rate providers failed for {$pair->value}");
    }

    private function resolveProvider(FxRateProvider $provider): RateProviderInterface
    {
        return match ($provider->type) {
            RateProviderType::API => app($provider->handler_class, [
                'config' => $provider->decrypted_config,
            ]),
            RateProviderType::SCRAPER => app($provider->handler_class),
            RateProviderType::MANUAL => app(ManualOverrideProvider::class),
        };
    }

    private function validateRate(RateResult $rate, CurrencyPair $pair): bool
    {
        // Rate must be positive
        if ($rate->mid <= 0) return false;

        // Rate must not deviate more than 20% from last known rate
        $lastRate = $this->cache->getRate($pair);
        if ($lastRate) {
            $deviation = abs($rate->mid - $lastRate->mid) / $lastRate->mid;
            if ($deviation > 0.20) {
                $this->eventService->emit(new RateAnomalyDetected(
                    $pair, "Rate deviation {$deviation}% exceeds 20% threshold"
                ));
                return false;
            }
        }

        return true;
    }

    private function markProviderDegraded(FxRateProvider $provider): void
    {
        $provider->consecutive_failures++;
        if ($provider->consecutive_failures >= 3) {
            $provider->status = RateProviderStatus::DEGRADED;
            $provider->save();
            $this->eventService->emit(new RateProviderHealthChanged(
                $provider, RateProviderStatus::DEGRADED
            ));
        }
    }
}
```

### RateEngine
```php
class RateEngine
{
    public function __construct(
        private RateProviderService $providerService,
        private RateCacheService $cache,
        private CbsReportingService $cbs,
    ) {}

    public function getLiveRate(CurrencyPair $pair, ?User $user = null): BezaRate
    {
        // Try cache first
        $cached = $this->cache->getRate($pair);
        if ($cached && $cached->isFresh(15)) {
            $rate = $cached;
        } else {
            $rate = $this->providerService->fetchRates($pair);
        }

        // Apply spread based on user tier and pair
        $spread = $this->calculateSpread($pair, $user);
        $bezaRate = $this->applySpread($rate->mid, $spread);

        return new BezaRate(
            pair: $pair,
            mid: $rate->mid,
            bid: $rate->bid,
            ask: $rate->ask,
            bezaRate: $bezaRate,
            spread: $spread,
            sources: $rate->sources,
            lastUpdated: $rate->timestamp,
        );
    }

    public function calculateSpread(CurrencyPair $pair, ?User $user): float
    {
        $baseSpread = config("fx.spreads.{$pair->value}", 0.03); // 3% default

        if ($user) {
            $tier = $user->kyc_level >= 3 ? 'premium' : ($user->kyc_level >= 1 ? 'standard' : 'basic');
            $tierMultiplier = config("fx.tier_spreads.{$tier}", 1.0);
            $baseSpread *= $tierMultiplier;
        }

        return min($baseSpread, config('fx.max_spread', 0.05)); // Hard cap 5%
    }

    public function applySpread(float $midRate, float $spread): float
    {
        // For SYP/USD: user buys USD → we add spread to mid
        // For USD/SYP: user sells USD → we subtract spread from mid
        return $midRate * (1 + $spread);
    }
}
```

### RateLockService
```php
class RateLockService
{
    private const LOCK_TTL = 30; // seconds
    private const LUA_LOCK_SCRIPT = '
        local key = KEYS[1]
        local ttl = ARGV[1]
        local rate = ARGV[2]
        local lockId = ARGV[3]
        local userId = ARGV[4]
        local amount = ARGV[5]

        -- Check if existing lock
        local existing = redis.call("GET", key)
        if existing then
            return {0, "RATE_ALREADY_LOCKED"}
        end

        -- Set lock with TTL
        redis.call("SET", key, lockId, "EX", ttl)
        redis.call("HSET", "fx:lock:" .. lockId,
            "rate", rate,
            "user_id", userId,
            "amount", amount,
            "created_at", ARGV[6],
            "expires_at", ARGV[7]
        )
        return {1, lockId}
    ';

    public function __construct(
        private Redis $redis,
        private RateLockRepository $lockRepo,
        private EventService $eventService,
    ) {}

    public function lockRate(LockRateRequest $request): RateLockResult
    {
        $rateKey = "fx:rate:{$request->pair->value}";
        $lockKey = "fx:lock:{$request->pair->value}:{$request->userId}";

        // Evaluate Lua script atomically
        $result = $this->redis->eval(
            self::LUA_LOCK_SCRIPT,
            1,
            $lockKey,
            self::LOCK_TTL,
            $request->rate,
            Str::uuid(),
            $request->userId,
            $request->amount,
            now()->toIso8601String(),
            now()->addSeconds(self::LOCK_TTL)->toIso8601String(),
        );

        if ($result[0] === 0) {
            throw new RateLockConflictException($result[1]);
        }

        $lockId = $result[1];

        // Persist lock record
        $lock = $this->lockRepo->create([
            'lock_id' => $lockId,
            'user_id' => $request->userId,
            'pair' => $request->pair,
            'rate' => $request->rate,
            'amount' => $request->amount,
            'expires_at' => now()->addSeconds(self::LOCK_TTL),
            'status' => RateLockStatus::ACTIVE,
        ]);

        $this->eventService->emit(new RateLocked($lock));

        return new RateLockResult(
            lockId: $lockId,
            rate: $request->rate,
            expiresAt: $lock->expires_at,
            remainingSeconds: self::LOCK_TTL,
        );
    }

    public function useLock(string $lockId, string $transactionId): bool
    {
        $lock = $this->lockRepo->findByLockId($lockId);
        if (!$lock || $lock->status !== RateLockStatus::ACTIVE) {
            return false;
        }

        if ($lock->expires_at->isPast()) {
            $lock->status = RateLockStatus::EXPIRED;
            $lock->save();
            $this->eventService->emit(new RateExpired($lock));
            return false;
        }

        $lock->status = RateLockStatus::USED;
        $lock->transaction_id = $transactionId;
        $lock->save();

        // Clean up Redis
        $this->redis->del("fx:lock:{$lock->pair}:{$lock->user_id}");

        return true;
    }
}
```

### Rate Anomaly Detection
```php
class RateAnomalyService
{
    public function __construct(
        private RateRepository $rateRepo,
        private EventService $eventService,
    ) {}

    public function detectAnomalies(): array
    {
        $anomalies = [];

        foreach (CurrencyPair::cases() as $pair) {
            // 1. Check spread widening
            $recent = $this->rateRepo->getRecentRates($pair, 10);
            $avgSpread = $recent->avg('spread');
            $currentSpread = $recent->first()?->spread;

            if ($currentSpread && $avgSpread && $currentSpread > $avgSpread * 2) {
                $anomalies[] = new Anomaly(
                    type: 'SPREAD_WIDENING',
                    pair: $pair,
                    severity: 'warning',
                    message: "Spread widened from {$avgSpread}% to {$currentSpread}%",
                );
            }

            // 2. Check price spike (>5% in 1 minute)
            $minuteAgo = now()->subMinute();
            $oldest = $this->rateRepo->getRateAt($pair, $minuteAgo);
            $latest = $this->rateRepo->getLatestRate($pair);

            if ($oldest && $latest) {
                $change = abs($latest->mid - $oldest->mid) / $oldest->mid;
                if ($change > 0.05) {
                    $anomalies[] = new Anomaly(
                        type: 'PRICE_SPIKE',
                        pair: $pair,
                        severity: 'critical',
                        message: "Price changed {$change}% in 1 minute (threshold: 5%)",
                    );
                }
            }

            // 3. Check provider divergence
            $providers = $this->rateRepo->getLatestFromEachProvider($pair);
            if ($providers->count() >= 2) {
                $maxRate = $providers->max('mid');
                $minRate = $providers->min('mid');
                $divergence = ($maxRate - $minRate) / $minRate;
                if ($divergence > 0.10) {
                    $anomalies[] = new Anomaly(
                        type: 'PROVIDER_DIVERGENCE',
                        pair: $pair,
                        severity: 'warning',
                        message: "Provider rates diverge by {$divergence}%",
                    );
                }
            }
        }

        foreach ($anomalies as $anomaly) {
            $this->eventService->emit(new RateAnomalyDetected($anomaly));
        }

        return $anomalies;
    }
}
```

## API Endpoints

```php
// FX Module Routes (prefix: /api/v1/fx)

Route::middleware(['auth:sanctum'])->group(function () {
    // Public rates
    Route::get('/rates', [RateController::class, 'index']);         // All live rates
    Route::get('/rates/{pair}', [RateController::class, 'show']);   // Single pair detail
    Route::get('/history/{pair}', [RateController::class, 'history']); // Rate history

    // Rate lock
    Route::post('/lock', [RateLockController::class, 'lock']);      // Lock rate
    Route::delete('/lock/{lockId}', [RateLockController::class, 'release']); // Release lock

    // Conversion
    Route::post('/convert', [ConversionController::class, 'convert']); // Execute conversion
    Route::get('/conversions', [ConversionController::class, 'history']); // User conversion history
    Route::get('/conversions/{id}', [ConversionController::class, 'show']); // Conversion detail

    // Admin only
    Route::middleware(['role:admin'])->group(function () {
        Route::post('/providers/manage', [AdminFXController::class, 'manageProvider']);
        Route::post('/override', [AdminFXController::class, 'overrideRate']);
        Route::post('/spreads', [AdminFXController::class, 'updateSpreads']);
        Route::get('/health', [AdminFXController::class, 'providerHealth']);
        Route::get('/cbs-report', [AdminFXController::class, 'cbsReport']);
    });
});

// Internal service routes (service-to-service, no auth)
Route::prefix('/internal/fx')->group(function () {
    Route::post('/webhook/provider-callback', [WebhookController::class, 'providerRate']);
});
```
