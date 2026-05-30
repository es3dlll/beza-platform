# 12 — Cost Estimates

---

## Development Costs (One-Time)

| Item | Hours | Rate (USD) | Total (USD) |
|------|-------|------------|-------------|
| Backend (3 engineers × 4 months) | 2,000 | 25 | 50,000 |
| Frontend (2 engineers × 4 months) | 1,300 | 20 | 26,000 |
| Mobile (1 engineer × 3 months) | 500 | 20 | 10,000 |
| UI/UX design | 200 | 30 | 6,000 |
| QA (2 engineers × 3 months) | 1,000 | 15 | 15,000 |
| Project management | 400 | 25 | 10,000 |
| Infrastructure setup | 100 | 30 | 3,000 |
| Sharia compliance review | 40 | 100 | 4,000 |
| **Total development** | | | **124,000** |

## Recurring Costs (Monthly)

| Item | Cost (USD) | Notes |
|------|------------|-------|
| Server hosting (on-prem / colocation) | 2,000 | Damascus data centre |
| SMS gateway | 0.02 × volume | ~$500/month at 25,000 SMS |
| PDF storage (S3-compatible) | 200 | 50 GB/month |
| Team (ops + support) | 4,000 | 2 people |
| **Total monthly** | **6,700+** | Scales with volume |

## Revenue Model

| Component | Fee |
|-----------|-----|
| Per-batch processing fee | 0.5 % of total amount (capped at SYP 500,000) |
| Monthly platform fee (SME) | SYP 50,000 |
| Monthly platform fee (Enterprise) | SYP 200,000 |
| API access (optional) | SYP 100,000/month |
| T+1 settlement fee | Additional 0.25 % |

### Example: Mid-size Company (150 employees, avg salary SYP 800,000)

| Item | Amount |
|------|--------|
| Total monthly payroll | SYP 120,000,000 |
| Batch fee (0.5 %) | SYP 600,000 |
| Platform fee | SYP 50,000 |
| **Beza monthly revenue** | **SYP 650,000 (~$50)** |
| **Annual revenue per company** | **~$600** |
