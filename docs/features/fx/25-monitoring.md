# FX Engine Monitoring

## Key Metrics (Prometheus)

### Business Metrics
```prometheus
# Rates fetched per minute by provider
beza_fx_rate_fetches_total{provider="cbs_official", status="success"} 5760
beza_fx_rate_fetches_total{provider="parallel_market", status="success"} 5740
beza_fx_rate_fetches_total{provider="black_market", status="success"} 5600
beza_fx_rate_fetches_total{provider="black_market", status="failed"} 160

# Rate fetches per minute
rate(beza_fx_rate_fetches_total[1m]) 12

# Rate lock metrics
beza_fx_locks_total{status="acquired"} 1500
beza_fx_locks_total{status="used"} 1200
beza_fx_locks_total{status="expired"} 300
beza_fx_lock_usage_rate 0.80  # 80% of locks result in conversion

# Conversion metrics
beza_fx_conversions_total{pair="SYP/USD"} 45000
beza_fx_conversions_total{pair="SYP/EUR"} 5000
beza_fx_conversions_total{pair="USD/EUR"} 200

# Conversion volume (SYP equivalent)
beza_fx_conversion_volume_total{pair="SYP/USD"} 50000000000
beza_fx_conversion_volume_total{pair="SYP/EUR"} 5000000000
rate(beza_fx_conversion_volume_total[1h]) 350000000

# Spread revenue
beza_fx_spread_revenue_total{pair="SYP/USD"} 1500000000
rate(beza_fx_spread_revenue_total[24h]) 62500000

# Provider health
beza_fx_provider_health{provider="cbs_official"} 1
beza_fx_provider_health{provider="parallel_market"} 1
beza_fx_provider_health{provider="black_market"} 0
beza_fx_providers_online_total 2

# Anomaly metrics
beza_fx_anomalies_total{type="spread_widening"} 12
beza_fx_anomalies_total{type="price_spike"} 3
beza_fx_anomalies_total{type="provider_divergence"} 8
```

### Technical Metrics
```prometheus
# API latency (ms)
beza_fx_api_duration_ms{endpoint="/fx/rates", quantile="0.5"} 45
beza_fx_api_duration_ms{endpoint="/fx/rates", quantile="0.95"} 120
beza_fx_api_duration_ms{endpoint="/fx/rates", quantile="0.99"} 350

beza_fx_api_duration_ms{endpoint="/fx/convert", quantile="0.5"} 450
beza_fx_api_duration_ms{endpoint="/fx/convert", quantile="0.95"} 1200
beza_fx_api_duration_ms{endpoint="/fx/convert", quantile="0.99"} 3000

beza_fx_api_duration_ms{endpoint="/fx/lock", quantile="0.5"} 25
beza_fx_api_duration_ms{endpoint="/fx/lock", quantile="0.95"} 60
beza_fx_api_duration_ms{endpoint="/fx/lock", quantile="0.99"} 150

# Provider response time (ms)
beza_fx_provider_response_ms{provider="cbs_official", quantile="0.95"} 150
beza_fx_provider_response_ms{provider="parallel_market", quantile="0.95"} 90
beza_fx_provider_response_ms{provider="black_market", quantile="0.95"} 450

# Cache
beza_fx_cache_hit_rate{type="rate"} 0.88
beza_fx_cache_misses_total{type="rate"} 720

# Error rates
rate(beza_fx_api_errors_total{endpoint="/fx/convert"}[5m]) 0.015
rate(beza_fx_lock_failures_total[5m]) 0.02

# Provider failover count
beza_fx_provider_failovers_total 25

# Circuit breaker events
beza_fx_circuit_breaker_open_total{provider="black_market"} 3
```

## Grafana Dashboard: FX Engine Overview

### Row 1: Key Figures
```
┌─────────────┬──────────────┬──────────────┬──────────────┐
│ Providers   │ Rate Fetch   │ Lock Usage   │ Avg Spread   │
│ Online      │ Latency P99  │ Rate         │              │
│ 2 / 3       │ 350ms        │ 80%          │ 2.4%         │
├─────────────┼──────────────┼──────────────┼──────────────┤
│ Conversions │ Volume       │ Revenue      │ Cache Hit    │
│ Today       │ Today        │ Today        │ Rate         │
│ 1,200       │ 350M SYP     │ 8.5M SYP     │ 88%          │
└─────────────┴──────────────┴──────────────┴──────────────┘
```

### Row 2: Rate Trends
```
[Line Chart: Live rate feed — SYP/USD]
X: Time (real-time, rolling 5 min)
Y: Rate (SYP/USD)
Series: 
  - CBS Official (blue, flat)
  - Parallel Market (green)
  - Black Market (red)
  - Beza Rate (purple, thick)
  - Beza Bid (purple dashed)
  - Beza Ask (purple dashed)

[Gauge: Spread %]
Threshold: green < 3%, yellow 3-4%, red > 4%
Current: 2.6%
```

### Row 3: Provider Health
```
[Table: Provider Status]
Columns: Name | Type | Status | Priority | Response Time | Uptime 24h | Last Success | Last Failure
Rows:
  CBS Official     | API     | 🟢 Online  | 1  | 120ms | 99.8% | 10:00:00 | —
  Parallel Market  | API     | 🟢 Online  | 2  | 85ms  | 99.9% | 10:00:00 | —
  Black Market     | Scraper | 🔴 Degraded| 3  | 450ms | 92.1% | 09:45:00 | 09:59:45
  Manual Override  | Manual  | ⚪ Inactive| 99 | —     | —     | —        | —

[Status Timeline for Black Market scraper]
Bar chart showing up/down state for last 24h
```

### Row 4: Conversion Volume
```
[Bar Chart: Conversion volume by pair (24h)]
X: Hour
Y: Volume (SYP equivalent)
Series: SYP→USD (blue), SYP→EUR (green), USD→EUR (amber)

[Bar Chart: Revenue by pair (24h)]
X: Hour  
Y: Revenue (SYP)
Series: SYP→USD, SYP→EUR, USD→EUR
```

### Row 5: Anomalies & Alerts
```
[Timeline: Anomaly events]
Each event: type, severity, pair, brief description
Color-coded: info (blue), warning (amber), critical (red)

[Table: Recent anomalies (last 24h)]
Columns: Time | Type | Severity | Pair | Message | Auto-action
```

### Row 6: Cache & Performance
```
[Heatmap: Cache hit ratio by hour]
X: Hour (24h)
Y: Cache type (rate, stale)
Color: Green (high hit), yellow, red (low hit)

[Line Chart: API endpoint latency P50, P95, P99]
X: Time
Y: Latency (ms)
Series: GET /fx/rates, POST /fx/lock, POST /fx/convert
```

## Alert Rules (Prometheus)
```yaml
groups:
  - name: fx_alerts
    rules:
      - alert: AllProvidersDown
        expr: beza_fx_providers_online_total == 0
        for: 30s
        annotations:
          summary: "All FX rate providers are down"
          action: "P0: Check network, provider APIs, scraper fleet. Degrade to stale rates."

      - alert: ProviderDegraded
        expr: beza_fx_provider_health == 0
        for: 1m
        annotations:
          summary: "Provider {{ $labels.provider }} is degraded"
          action: "Check provider health endpoint, investigate root cause."

      - alert: HighConversionErrorRate
        expr: rate(beza_fx_api_errors_total{endpoint="/fx/convert"}[5m]) > 0.05
        for: 2m
        annotations:
          summary: "Conversion endpoint error rate > 5%"
          action: "Check conversion pipeline, CFE health, lock service."

      - alert: RateAnomalyCritical
        expr: beza_fx_anomalies_total{severity="critical"} > 0
        for: 10s
        annotations:
          summary: "Critical rate anomaly detected for {{ $labels.pair }}"
          action: "Immediate investigation. Check for market manipulation."

      - alert: HighLockExpiryRate
        expr: rate(beza_fx_locks_total{status="expired"}[5m]) / rate(beza_fx_locks_total[5m]) > 0.5
        for: 5m
        annotations:
          summary: "Rate lock expiry rate > 50% — users not converting"
          action: "Check conversion flow, rate competitiveness, UX friction."

      - alert: CacheHitRateLow
        expr: beza_fx_cache_hit_rate < 0.50
        for: 5m
        annotations:
          summary: "FX cache hit rate < 50%"
          action: "Check Redis health, cache key TTLs, fetch frequency."

      - alert: ProviderResponseTimeSpike
        expr: beza_fx_provider_response_ms{quantile="0.95"} > 1000
        for: 2m
        annotations:
          summary: "Provider {{ $labels.provider }} P95 response time > 1s"
          action: "Check provider API performance, consider deprioritizing."

      - alert: SpreadExceedsSelfLimit
        expr: beza_fx_spread_revenue_total / beza_fx_conversion_volume_total > 0.04
        for: 5m
        annotations:
          summary: "Effective spread > 4% (self-imposed limit)"
          action: "Check spread config, user tier assignments, anomaly conditions."
```
