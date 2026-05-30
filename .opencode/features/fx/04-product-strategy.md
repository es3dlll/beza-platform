# FX Engine Product Strategy

## Product Phases

### Phase 1 (Months 1-2) — Core Rate Engine
- Rate provider framework (API, scraper, manual sources)
- CBS official rate feed
- Parallel market rate scraping (exchange house websites)
- Spread configuration engine
- Redis rate caching (TTL 15s)
- Provider failover chain (3 providers minimum)
- GET /fx/rates and GET /fx/rate/{pair} endpoints

### Phase 2 (Months 3-4) — Conversion & Locks
- Rate lock mechanism (30s hold, Redis + Lua atomicity)
- POST /fx/lock endpoint
- POST /fx/convert endpoint
- CFE integration for conversion posting
- Rate lock expiry enforcement (cron + async expiry)
- Conversion history and receipt generation
- Admin rate override with audit trail

### Phase 3 (Months 5-6) — Monitoring & Controls
- Rate provider health monitoring (30s cron)
- Rate anomaly detection (spread widening alerts)
- Maximum spread limits per pair per user tier
- Provider credential encryption
- Rate history (90-day retention)
- Admin dashboard for rate management
- CBS rate reporting module

### Phase 4 (Months 7-9) — Intelligence & Optimization
- ML rate prediction (short-term 5-min forecast)
- Optimal rate provider selection (ML-driven)
- Hedging exposure management
- Volatility prediction for hedging decisions
- Rate arbitrage protection
- Provider SLA tracking and scoring

## Feature Gating by User Tier
| Feature | Standard | Premium | Merchant |
|---------|----------|---------|----------|
| View live rates | ✓ | ✓ | ✓ |
| Rate history (7d) | ✓ | ✓ | ✓ |
| Rate history (90d) | ✗ | ✓ | ✓ |
| Rate lock duration | 15s | 30s | 60s |
| Max lock amount | $500 | $5,000 | $50,000 |
| Spread discount | 0% | 50% | 30% |
| API access | ✗ | ✗ | ✓ (paid) |
| CBS report download | ✗ | ✗ | ✓ |

## Pricing Strategy
| Operation | Standard | Premium | Merchant |
|-----------|----------|---------|----------|
| SYP→USD conversion | 3% spread | 1.5% spread | 2% spread |
| USD→SYP conversion | 2% spread | 1% spread | 1.5% spread |
| SYP→EUR conversion | 3.5% spread | 2% spread | 2.5% spread |
| USD→EUR conversion | 1.5% spread | 0.75% spread | 1% spread |
| Rate lock fee | Free | Free | $0.10/lock |
| FX API subscription | N/A | N/A | $500/month |
