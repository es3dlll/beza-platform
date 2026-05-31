# Merchant Acquiring Business Case

## Revenue Model
| Revenue Stream | Calculation | Margin | Y3 Annual Revenue |
|---------------|-------------|--------|-------------------|
| QR MDR (Static) | 1.5% per txn | 90% | $3.6M |
| QR MDR (Dynamic) | 1.5% per txn | 90% | $1.2M |
| POS MDR | 2.0% per txn | 85% | $2.5M |
| Web Checkout MDR | 2.5% per txn | 85% | $1.8M |
| Payment Link MDR | 2.0% per txn | 90% | $800K |
| POS Terminal Sale | $150/unit (cost $80) | 47% | $350K |
| POS Terminal Rental | $15/month | 80% | $180K |
| Receipt Paper Rolls | $5/roll (cost $2) | 60% | $60K |
| Settlement Fast-Track | 0.5% fee for T+0 | 100% | $300K |
| Premium Merchant | $20/month (analytics, priority support) | 90% | $240K |

## Cost Structure (Monthly)
| Cost Item | Monthly (USD) | Annual (USD) |
|-----------|--------------|--------------|
| POS terminal procurement (100 units/mo) | $8K | $96K |
| QR code printing/lamination (subsidized) | $2K | $24K |
| SMS/WhatsApp notification costs | $5K | $60K |
| Merchant support team (10 people) | $20K | $240K |
| Webhook delivery infrastructure | $3K | $36K |
| QR CDN + image hosting | $1K | $12K |
| Settlement batch processing infra | $2K | $24K |
| POS certificate management | $1K | $12K |
| Fraud monitoring operations | $5K | $60K |
| **Total** | **$47K** | **$564K** |

## Unit Economics
| Metric | Value |
|--------|-------|
| CAC (merchant app install + QR) | $2.00 |
| CAC (POS terminal + onboarding) | $25.00 |
| CAC (web checkout integration) | $50.00 |
| Average TP per merchant (monthly) | $2,000 |
| Average MDR per merchant (monthly) | $35 |
| Payback period (QR merchant) | 0.7 months |
| Payback period (POS merchant) | 8.5 months |
| LTV (QR merchant, 24 months) | $840 |
| LTV (POS merchant, 24 months) | $2,100 |
| Monthly churn (QR) | 3% |
| Monthly churn (POS) | 1% |

## Break-even Analysis
| Year | Merchants | Revenue | Cost | Profit/Loss |
|------|-----------|---------|------|-------------|
| 1 | 5K | $480K | $700K | ($220K) |
| 2 | 20K | $3.2M | $2.5M | $700K |
| 3 | 50K | $11M | $6.8M | $4.2M |

## Funding Required (Merchant-specific)
| Item | Amount | Use |
|------|--------|-----|
| POS terminal inventory | $200K | Bulk purchase of Android POS terminals |
| QR marketing + printing | $50K | Free QR kits for first 5K merchants |
| Merchant onboarding team | $150K | Field agents for in-person onboarding |
| Webhook infrastructure | $100K | Reliable delivery with retry + monitoring |
| Total | $500K | Part of Series A allocation |
