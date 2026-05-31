# Bill Payment Business Case

## Revenue Model
| Revenue Stream | Calculation Model | Margin | Y3 Annual Revenue |
|---------------|-------------------|--------|-------------------|
| Biller Commission — Electricity (PEED) | 0.5% of bill amount | 100% | $400K |
| Biller Commission — Water (Damascus Water) | 0.75% of bill amount | 100% | $150K |
| Biller Commission — Telecom (Syriatel/MTN) | 1% of bill amount + 100 SYP fixed | 80% | $600K |
| Biller Commission — Syria Telecom | 0.5% of bill amount | 100% | $100K |
| Biller Commission — Government Fees | 1.5% of fee amount | 100% | $250K |
| Biller Commission — Internet (Aya/Saman) | 1% of bill amount | 100% | $100K |
| Biller Commission — Education | 1% of tuition | 100% | $100K |
| Late fee convenience | 500 SYP per late payment processed | 90% | $50K |
| SMS receipt confirmation | 50 SYP per SMS | 60% | $30K |
| Premium auto-pay subscription | 2,000 SYP/month (unlimited auto-pay) | 90% | $120K |

## Cost Structure (Monthly)
| Cost Item | Monthly (USD) | Annual (USD) |
|-----------|--------------|--------------|
| Biller API integration maintenance | $15K | $180K |
| CSV batch processing ops | $5K | $60K |
| SMS notifications for receipts | $10K | $120K |
| Customer support (bill disputes) | $8K | $96K |
| Infrastructure (API gateways, DB) | $10K | $120K |
| Compliance & reconciliation | $5K | $60K |
| **Total** | **$53K** | **$636K** |

## Unit Economics
| Metric | Value |
|--------|-------|
| CAC (organic) | $0.50 |
| CAC (paid) | $2.00 |
| Average bill payment value | 25,000 SYP |
| Average monthly bills per user | 3.2 |
| Average revenue per active user | $0.35/month |
| Payback period | 1.5–6 months |
| LTV (12 months) | $4.20 |
| LTV/CAC ratio | 2.1x (paid), 8.4x (organic) |

## Break-even Analysis
| Year | Users | Revenue | Cost | Profit/Loss |
|------|-------|---------|------|-------------|
| 1 | 150K | $600K | $636K | ($36K) |
| 2 | 500K | $2.1M | $1.1M | $1M |
| 3 | 1.5M | $4M | $1.9M | $2.1M |

## Funding Required (Bill Payment Share)
| Component | Amount | Use |
|-----------|--------|-----|
| Initial integration (10 billers) | $150K | API dev, testing, biller onboarding |
| CSV batch engine | $40K | Parser, validation, reconciliation UI |
| Reminder/scheduler engine | $60K | Cron system, notification pipeline |
| Ongoing (annual) | $200K | Maintenance, new billers, compliance |
