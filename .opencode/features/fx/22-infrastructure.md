# FX Engine Infrastructure

## Deployment Architecture
```
┌──────────────────────────────────────────────────────────────┐
│                    Kubernetes Cluster                        │
│                                                              │
│  ┌────────────────┐  ┌────────────────┐  ┌────────────────┐ │
│  │  FX API Pods   │  │  Rate Fetcher  │  │  Anomaly       │ │
│  │  Replicas: 3   │  │  Replicas: 2   │  │  Detector: 1   │ │
│  │  CPU: 2, RAM:4 │  │  CPU: 1, RAM:2 │  │  CPU: 1, RAM:2 │ │
│  └────────────────┘  └────────────────┘  └────────────────┘ │
│                                                              │
│  ┌────────────────┐  ┌────────────────┐  ┌────────────────┐ │
│  │  Redis Cluster │  │  MySQL         │  │  RabbitMQ      │ │
│  │  (Cache+Locks) │  │  Primary+2 RO  │  │  Cluster 3     │ │
│  │  3 master+3 RO │  │  + FX Tables   │  │                │ │
│  └────────────────┘  └────────────────┘  └────────────────┘ │
│                                                              │
│  ┌────────────────┐  ┌────────────────┐  ┌────────────────┐ │
│  │  WebSocket     │  │  Scraper       │  │  ML Inference  │ │
│  │  Server: 2     │  │  Fleet: 3      │  │  Server: 1 GPU │ │
│  └────────────────┘  └────────────────┘  └────────────────┘ │
│                                                              │
└──────────────────────────────────────────────────────────────┘
```

## Scaling Strategy
```
FX API:
  - HPA: CPU > 70% OR req/s > 500 per replica → scale to max 8
  - Rate fetch requests: burst up to 2000 req/s during high traffic
  - Conversion requests: burst up to 100 req/s

Rate Fetcher:
  - Dedicated pods for periodic rate fetching (not on API pods)
  - 2 replicas for redundancy (active-passive)
  - Scaled independently from API (different load profile)

Redis:
  - Cluster mode: 3 masters, 3 replicas
  - Memory: 8GB per node
  - Key patterns:
    fx:rate:{pair} → TTL 15s (rate cache)
    fx:lock:{pair}:{userId} → TTL 30s (rate locks, Lua atomic)
    fx:lock:{lockId} → TTL 35s (lock metadata)
    fx:anomaly:{pair} → TTL 300s (anomaly cooldown)
    fx:provider:{id}:health → TTL 30s (health status)
  - Eviction: allkeys-lru (rate cache can be regenerated)

Database:
  - Read replicas: 2 (fx_rates is read-heavy)
  - fx_rates partitioned by month (auto-created ahead)
  - Historical partitions (older than 90 days) compressed
  - Connection pooling: 50 connections per API replica
```

## Rate Provider Health Checking
```php
// Cron job runs every 30 seconds
// One job per provider, or a single job checking all providers
// Timeout: 2 seconds per provider

class CheckRateProviderHealth extends Job
{
    public function handle(RateProviderRepository $providerRepo): void
    {
        $providers = $providerRepo->getActiveProviders();

        foreach ($providers as $provider) {
            try {
                $start = microtime(true);

                $response = Http::timeout(2)->get($provider->health_url);
                $responseTimeMs = (microtime(true) - $start) * 1000;

                if ($response->successful()) {
                    $provider->recordSuccess($responseTimeMs);
                } else {
                    $provider->recordFailure("HTTP {$response->status()}");
                }
            } catch (Throwable $e) {
                $provider->recordFailure($e->getMessage());
            }

            $provider->save();

            // Emit event on status change
            if ($provider->wasChanged('status')) {
                event(new RateProviderHealthChanged(
                    $provider,
                    $provider->getOriginal('status'),
                    $provider->status,
                ));
            }
        }
    }
}
```

## Cache Strategy
```php
class RateCacheService
{
    private const RATE_TTL = 15;        // seconds
    private const LOCK_TTL = 30;        // seconds
    private const STALE_TTL = 300;      // 5 minutes (served as stale)

    public function __construct(private Redis $redis) {}

    public function getRate(CurrencyPair $pair): ?BezaRate
    {
        $data = $this->redis->get("fx:rate:{$pair->value}");
        if (!$data) return null;

        return BezaRate::fromCache($data);
    }

    public function setRate(CurrencyPair $pair, BezaRate $rate): void
    {
        $this->redis->setex(
            "fx:rate:{$pair->value}",
            self::RATE_TTL,
            $rate->toCache()
        );
    }

    public function getStaleRate(CurrencyPair $pair): ?BezaRate
    {
        // Check if we have a stale rate (older than TTL but within STALE_TTL)
        $data = $this->redis->get("fx:rate:stale:{$pair->value}");
        if (!$data) return null;

        return BezaRate::fromCache($data)->withStaleIndicator();
    }

    public function setStaleRate(CurrencyPair $pair, BezaRate $rate): void
    {
        // Keep a separate stale key with longer TTL
        $this->redis->setex(
            "fx:rate:stale:{$pair->value}",
            self::STALE_TTL,
            $rate->toCache()
        );
    }

    public function acquireLock(string $lockKey, string $lockId, int $ttl = 30): bool
    {
        // Lua script for atomic lock acquisition
        $script = '
            local key = KEYS[1]
            local ttl = ARGV[1]
            local lockId = ARGV[2]
            return redis.call("SET", key, lockId, "NX", "EX", ttl)
        ';

        return (bool) $this->redis->eval($script, 1, $lockKey, $ttl, $lockId);
    }

    public function releaseLock(string $lockKey, string $lockId): bool
    {
        // Lua script: only release if we own the lock
        $script = '
            local key = KEYS[1]
            local expected = ARGV[1]
            if redis.call("GET", key) == expected then
                return redis.call("DEL", key)
            end
            return 0
        ';

        return (bool) $this->redis->eval($script, 1, $lockKey, $lockId);
    }

    public function invalidateRate(CurrencyPair $pair): void
    {
        $this->redis->del("fx:rate:{$pair->value}");
    }
}
```

## Failover Provider Chain
```php
class FailoverChain
{
    public function __construct(
        private RateProviderRepository $providerRepo,
        private RateCacheService $cache,
    ) {}

    public function execute(CurrencyPair $pair, callable $fetchFn): RateResult
    {
        $providers = $this->providerRepo
            ->getActiveProvidersSortedByPriority($pair)
            ->filter(fn($p) => $p->isAvailable());

        $lastError = null;

        foreach ($providers as $provider) {
            try {
                $start = microtime(true);
                $result = $fetchFn($provider);
                $responseTimeMs = (microtime(true) - $start) * 1000;

                // Validate rate
                if ($result->mid <= 0) {
                    throw new \Exception("Invalid rate: {$result->mid}");
                }

                // Check deviation from last known rate
                $lastRate = $this->cache->getRate($pair);
                if ($lastRate) {
                    $deviation = abs($result->mid - $lastRate->mid) / $lastRate->mid;
                    if ($deviation > 0.20) {
                        throw new \Exception("Rate deviation {$deviation}% exceeds 20%");
                    }
                }

                $provider->recordSuccess($responseTimeMs);
                $provider->save();

                return $result;

            } catch (Throwable $e) {
                $provider->recordFailure($e->getMessage());
                $provider->save();
                $lastError = $e;

                logger()->warning("Failover: Provider {$provider->name} failed", [
                    'error' => $e->getMessage(),
                    'pair' => $pair->value,
                ]);

                continue;
            }
        }

        // All providers failed — attempt stale cache
        $stale = $this->cache->getStaleRate($pair);
        if ($stale) {
            return $stale;
        }

        throw new AllProvidersDownException(
            "All providers failed for {$pair->value}. Last error: {$lastError?->getMessage()}"
        );
    }
}
```

## Rate Anomaly Detection Infrastructure
```php
// Runs as a cron job every 60 seconds
// Analyzes last N rate samples per pair
// Maintains a sliding window of rate history in Redis

class DetectRateAnomaliesJob
{
    private const WINDOW_SIZE = 30; // last 30 samples (~7.5 min)
    private const SPREAD_THRESHOLD_MULTIPLIER = 2.0;
    private const PRICE_SPIKE_THRESHOLD = 0.05; // 5% in 1 minute
    private const PROVIDER_DIVERGENCE_THRESHOLD = 0.10; // 10%

    public function handle(): void
    {
        $anomalies = [];

        foreach (CurrencyPair::cases() as $pair) {
            // Get recent samples from Redis sorted set
            $samples = $this->redis->zrevrange(
                "fx:history:{$pair->value}",
                0, self::WINDOW_SIZE - 1,
                'WITHSCORES'
            );

            if (count($samples) < 5) continue; // Not enough data

            $rates = array_map(fn($s, $ts) => [
                'rate' => (float) $s,
                'timestamp' => (int) $ts,
            ], array_keys($samples), array_values($samples));

            // Check 1: Spread widening
            $avgSpread = array_sum(array_column($rates, 'spread_pct')) / count($rates);
            $currentSpread = $rates[0]['spread_pct'] ?? 0;
            if ($currentSpread > $avgSpread * self::SPREAD_THRESHOLD_MULTIPLIER) {
                $anomalies[] = new Anomaly('SPREAD_WIDENING', $pair, 'warning',
                    "Spread {$currentSpread}% exceeds 2x avg {$avgSpread}%");
            }

            // Check 2: Price spike (1-min window)
            $oldest = $rates[array_key_last($rates)];
            $newest = $rates[0];
            $change = abs($newest['rate'] - $oldest['rate']) / $oldest['rate'];
            if ($change > self::PRICE_SPIKE_THRESHOLD) {
                $anomalies[] = new Anomaly('PRICE_SPIKE', $pair, 'critical',
                    "Price changed " . round($change * 100, 1) . "% in " .
                    round(($newest['timestamp'] - $oldest['timestamp']) / 60, 1) . " min");
            }

            // Check 3: Provider divergence
            $providerRates = $this->getLatestFromEachProvider($pair);
            if (count($providerRates) >= 2) {
                $max = max($providerRates);
                $min = min($providerRates);
                $divergence = ($max - $min) / $min;
                if ($divergence > self::PROVIDER_DIVERGENCE_THRESHOLD) {
                    $anomalies[] = new Anomaly('PROVIDER_DIVERGENCE', $pair, 'warning',
                        "Providers diverge by " . round($divergence * 100, 1) . "%");
                }
            }
        }

        foreach ($anomalies as $anomaly) {
            event(new RateAnomalyDetected($anomaly));
        }

        return $anomalies;
    }
}
```

## Rate Scraping Infrastructure
```
For non-API providers (exchange house websites, Telegram channels, etc.)

Architecture:
  ┌───────────────────────────────────────┐
  │         Scraper Fleet (3 pods)        │
  │                                       │
  │  ┌─────────────────────────────────┐  │
  │  │  Playwright/Headless Chrome    │  │
  │  │  - Exchange House A (damascus) │  │
  │  │  - Exchange House B (aleppo)   │  │
  │  │  - Telegram Channel X          │  │
  │  │  - Telegram Channel Y          │  │
  │  └─────────────────────────────────┘  │
  │                                       │
  │  Output: Parsed rates → POST to       │
  │  internal API /internal/fx/webhook    │
  └───────────────────────────────────────┘

Scraper Design:
  - Each scraper is a PHP class implementing RateProviderInterface
  - Uses Symfony Panther (PHP Headless Chrome) for JS-rendered pages
  - Falls back to simple DOM parsing for static pages
  - Rate extraction via CSS selectors (config per provider)
  - Telegram scraper uses MTProto API for channel message reading

Scraper Config Example:
  provider: "Exchange House Damascus"
  type: scraper
  url: "https://exchangehouse.example.com/rates"
  selectors:
    usd_buy: "#table-rates tr:first-child td:nth-child(2)"
    usd_sell: "#table-rates tr:first-child td:nth-child(3)"
    eur_buy: "#table-rates tr:nth-child(2) td:nth-child(2)"
    eur_sell: "#table-rates tr:nth-child(2) td:nth-child(3)"
  schedule: "*/15 * * * * *"  // Every 15 seconds
```
