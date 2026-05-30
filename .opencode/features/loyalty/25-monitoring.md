# Loyalty Monitoring

## Key Metrics (Prometheus)

### Business Metrics
```prometheus
# Total points earned (lifetime)
beza_loyalty_points_earned_total 150000000

# Total points redeemed
beza_loyalty_points_redeemed_total 60000000

# Points redemption rate (30d)
beza_loyalty_redemption_rate 0.40

# Active loyalty members
beza_loyalty_active_members_total 80000

# Tier distribution
beza_loyalty_tier_distribution{tier="bronze"} 50000
beza_loyalty_tier_distribution{tier="silver"} 20000
beza_loyalty_tier_distribution{tier="gold"} 8000
beza_loyalty_tier_distribution{tier="platinum"} 2000

# Points earned per minute
rate(beza_loyalty_points_earned[1m]) 50000

# Redemptions per minute
rate(beza_loyalty_redemptions_total[1m]) 12

# Active merchant campaigns
beza_loyalty_active_campaigns_total 25

# Campaign budget utilization
beza_loyalty_campaign_budget_utilization{campaign="cmp_abc123"} 0.65
```

### Technical Metrics
```prometheus
# API latency (ms)
beza_loyalty_api_duration_ms{endpoint="/points", quantile="0.5"} 15
beza_loyalty_api_duration_ms{endpoint="/points", quantile="0.99"} 80

beza_loyalty_api_duration_ms{endpoint="/redeem", quantile="0.5"} 120
beza_loyalty_api_duration_ms{endpoint="/redeem", quantile="0.99"} 500

# Batch job duration
beza_loyalty_batch_duration_minutes{job="tier_upgrade"} 12
beza_loyalty_batch_duration_minutes{job="points_expiry"} 3

# Points balance cache hit rate
beza_loyalty_cache_hit_rate 0.92

# Points liability (SYP)
beza_loyalty_liability_syp 150000000
```

## Grafana Dashboard: Loyalty Overview

### Row 1: Key Figures
```
┌────────────────┬────────────────┬────────────────┬────────────────┐
│ Points Balance │ Points Earned  │ Redemption     │ Active         │
│ (Total)        │ (24h)          │ Rate (30d)     │ Members        │
│ 150M           │ 1.2M           │ 40%            │ 80K            │
└────────────────┴────────────────┴────────────────┴────────────────┘
```

### Row 2: Tier Distribution & Activity
```
[Donut Chart: Tier distribution]
Bronze 62%, Silver 25%, Gold 10%, Platinum 3%

[Line Chart: Points earned vs redeemed (30 days)]
X: Date
Y: Points
Series: Earned, Redeemed, Expired
```

### Row 3: Campaign Performance
```
[Table: Top 10 active campaigns]
Columns: Campaign, Merchant, Budget, Used, Redemptions, Status

[Bar Chart: Redemptions by reward category]
X: Category (Fee, Airtime, Gift Card, Partner)
Y: Count (30d)
```

## Alert Rules (Prometheus)
```yaml
groups:
  - name: loyalty_alerts
    rules:
      - alert: LowRedemptionRate
        expr: beza_loyalty_redemption_rate < 0.20
        for: 7d
        annotations:
          summary: "Loyalty redemption rate below 20%"
          action: "Review reward catalog, consider promotions"

      - alert: PointsExpirySpike
        expr: rate(beza_loyalty_points_expired_total[1d]) > rate(beza_loyalty_points_expired_total[7d]) * 2
        for: 2d
        annotations:
          summary: "Points expiry rate doubled"
          action: "Check expiry job, send user notifications"

      - alert: CampaignBudgetExhausted
        expr: beza_loyalty_campaign_budget_utilization > 0.95
        annotations:
          summary: "Campaign budget nearly exhausted"
          action: "Notify merchant to top up"

      - alert: HighRedemptionErrorRate
        expr: rate(beza_loyalty_redemption_errors_total[5m]) > 0.05
        for: 2m
        annotations:
          summary: "Redemption error rate > 5%"
          action: "Check airtime/gift card provider APIs"
```
