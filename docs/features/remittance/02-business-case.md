# Remittance Business Case

## Revenue Model
| Revenue Stream | Calculation Model | Margin | Y3 Annual Revenue |
|---------------|-------------------|--------|-------------------|
| Local P2P Transfer Fee | 0.5% per txn (cap 5,000 SYP / $5) | 90% | $1.5M |
| Diaspora Remittance Fee | 1.5% of amount (sender pays) | 85% | $12M |
| FX Spread (USD→SYP) | 1.5% above mid-market rate | 100% | $8M |
| FX Spread (EUR→SYP) | 1.8% above mid-market rate | 100% | $4M |
| FX Spread (EUR→USD) | 1.0% above mid-market rate | 100% | $1M |
| Recurring Transfer Fee | 1.0% (discount for volume) | 90% | $3M |
| Premium Corridor Fee | 0.5% express corridor (EU fast-lane) | 95% | $500K |
| Bulk Transfer Fee | 0.3% per recipient | 85% | $500K |
| API Access (partner corridors) | Per-transaction or flat monthly | 90% | $2M |

## Cost Structure (Monthly)
| Cost Item | Monthly (USD) | Annual (USD) |
|-----------|--------------|--------------|
| Infrastructure (K8s, DB, Redis) | $40K | $480K |
| FX hedging costs | $25K | $300K |
| Compliance & AML screening | $50K | $600K |
| Sanctions screening licensing | $15K | $180K |
| Staff (25 people: eng, compliance, ops) | $60K | $720K |
| Customer support (Arabic + diaspora languages) | $25K | $300K |
| Bank settlement fees | $20K | $240K |
| Agent commissions (cash-out payouts) | $30K | $360K |
| SMS/notification costs | $10K | $120K |
| Regulatory licensing (per corridor) | $15K | $180K |
| **Total** | **$290K** | **$3.48M** |

## Unit Economics
| Metric | Local P2P | Diaspora Remittance |
|--------|-----------|---------------------|
| Average transaction value | 50,000 SYP ($4) | $250 |
| Average revenue per txn | 250 SYP ($0.02) | $3.75 (fee) + $3.75 (FX) = $7.50 |
| CAC (organic) | $1.50 | $3.00 (diaspora) |
| CAC (referral) | $1.00 | $2.00 |
| Average transactions/user/month | 8 | 2 |
| ARPU | $0.16/month | $15/month |
| Payback period | 1-3 months | 1-2 months |
| LTV (24 months) | $3.84 | $360 |

## Break-even Analysis
| Year | Remittance TP | Revenue | Cost | Profit/Loss |
|------|-------------|---------|------|-------------|
| 1 | $600M | $9M | $3.5M | $5.5M |
| 2 | $2.5B | $35M | $6M | $29M |
| 3 | $6B | $80M | $10M | $70M |

## Funding Required
| Round | Amount | Use |
|-------|--------|-----|
| Seed | $1M | MVP, licensing (CBL), team |
| Series A | $5M | Corridor expansion, compliance infra, AI fraud |
| Series B | $15M | Global licensing, correspondent banking, scale |
