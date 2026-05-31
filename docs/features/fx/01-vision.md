# FX Engine Feature Vision

## Elevator Pitch
The FX Engine is the infrastructural backbone of Beza Platform — managing Syria's multi-rate reality by sourcing live foreign exchange rates from multiple providers (CBS official, parallel market, black market, corridor-specific), applying configurable spreads, locking rates for transaction duration, and executing cross-currency conversions. It enables diaspora remittances, merchant settlement in any currency, and multi-currency wallet operations at scale.

## Problem Statement
- Syria operates multiple concurrent FX rates: CBS official (~13,000 SYP/USD), parallel market (~14,500 SYP/USD), black market (~15,200 SYP/USD), and corridor-specific rates (Damascus vs Aleppo differ by 200-500 SYP)
- No single source of truth for FX rates — apps, exchange houses, and banks each use different rates
- Diaspora remittances lose 5-10% to inefficient hawala channels with opaque FX markup
- Merchants cannot settle in foreign currency; forced to convert at unfavorable rates
- Multi-currency wallets are meaningless without a reliable, real-time FX conversion engine
- Rate manipulation by bad actors is a real risk (flash crashes, artificial spreads)

## Target Users
- **Primary**: Beza Wallet users performing cross-currency transactions (SYP ↔ USD, SYP ↔ EUR, USD ↔ EUR)
- **Secondary**: Diaspora Syrians sending remittances (EUR/USD → SYP)
- **Tertiary**: Merchants settling in USD while operating in SYP
- **Admin/Operator**: Beza treasury team managing rate sources, spreads, and overrides

## Core Capabilities
| Capability | Priority | Description |
|------------|----------|-------------|
| Live rate fetch | P0 | Fetch rates from multiple sources every 15s |
| Rate provider management | P0 | Add/remove/prioritize rate sources (API, scraper, manual) |
| Spread configuration | P0 | Configurable markup per currency pair per user tier |
| Rate lock | P0 | Lock a rate for 30-60s for transaction execution |
| Cross-currency conversion | P0 | Execute SYP↔USD, SYP↔EUR, USD↔EUR conversions |
| Rate cache | P0 | Redis-cached rates with 15s TTL |
| Provider failover | P0 | Cascade through providers on failure |
| Rate history | P0 | 90-day rate history for audit and reporting |
| Admin rate override | P1 | Manual rate entry with full audit trail |
| Rate anomaly detection | P1 | Detect unusual spread widening or flash crashes |
| Rate provider health monitoring | P1 | Health checks every 30s per provider |
| CBS rate reporting | P1 | Generate CBS-compliant rate reports |
| Rate prediction (ML) | P2 | Short-term 5-min rate forecast |
| Hedge exposure management | P2 | Track and minimize open FX exposure |
| Volatility prediction | P2 | Predict rate volatility for hedging decisions |

## Success Metrics
| Metric | Y1 Target | Y3 Target |
|--------|-----------|-----------|
| Rate source uptime | 99.5% | 99.95% |
| Rate fetch latency (P99) | 200ms | 100ms |
| Rate lock success rate | 99.8% | 99.95% |
| Conversion success rate | 99.5% | 99.9% |
| Spread revenue | $2.5M/year | $10M/year |
| Rate anomaly detection accuracy | 95% | 99% |
| Provider failover time | < 1s | < 200ms |
| Cache hit ratio | 85% | 95% |
| Cross-currency conversion volume | $10M/month | $200M/month |
| CBS report accuracy | 100% | 100% |
