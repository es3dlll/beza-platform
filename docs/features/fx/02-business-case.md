# FX Engine Business Case

## Revenue Model
| Revenue Stream | Calculation Model | Margin | Y3 Annual Revenue |
|---------------|-------------------|--------|-------------------|
| Conversion spread (SYP/USD) | 1-3% of converted amount | 100% | $4M |
| Conversion spread (SYP/EUR) | 1.5-3.5% of converted amount | 100% | $2M |
| Conversion spread (USD/EUR) | 0.5-1.5% of converted amount | 100% | $500K |
| Remittance FX markup | 0.5% premium over interbank | 100% | $2.5M |
| Merchant settlement fee | 0.5% on USD settlement | 80% | $1M |
| Rate API licensing (B2B) | $500/month per client | 90% | $500K |
| CBS rate reporting service | $2,000/month | 90% | $1M |

## Cost Structure (Monthly)
| Cost Item | Monthly (USD) | Annual (USD) |
|-----------|--------------|--------------|
| Rate provider data feeds | $5K | $60K |
| Web scraping infrastructure | $2K | $24K |
| Redis cluster (cache + locks) | $3K | $36K |
| ML inference infrastructure | $4K | $48K |
| Staff (5 engineers, 1 quant) | $25K | $300K |
| Compliance & CBS reporting | $5K | $60K |
| **Total** | **$44K** | **$528K** |

## Unit Economics
| Metric | Value |
|--------|-------|
| Cost per rate fetch | $0.0002 |
| Cost per rate lock | $0.001 |
| Cost per conversion | $0.02 |
| Average revenue per conversion | $2.50 |
| Average conversion size | $250 |
| Gross margin per conversion | 92% |
| Break-even conversion volume | 17,600/month |

## Break-even Analysis
| Year | Conversion Volume | Revenue | Cost | Profit/Loss |
|------|------------------|---------|------|-------------|
| 1 | 200K conversions | $3M | $528K | $2.47M |
| 2 | 1M conversions | $8M | $1.2M | $6.8M |
| 3 | 3M conversions | $15M | $2.5M | $12.5M |

## Strategic Value
The FX Engine is not a standalone profit center — it is the **enabler** of the entire multi-currency platform. Without reliable FX, the Wallet (P2P, savings, cards), Remittance, and Merchant features cannot operate. The FX Engine directly enables:
- Wallet cross-currency transfers (projected 40% of all wallet transactions by Y3)
- Diaspora remittance corridor (targeting 15% of $3B annual Syria remittance market)
- Merchant USD settlement (unlocking e-commerce for Syrian businesses)
- CBS compliance reporting (regulatory requirement for payment license)
