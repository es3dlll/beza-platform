# Loyalty Event Architecture

## Events Produced

### PointsEarned
```json
{
  "specversion": "1.0",
  "id": "evt_pts_earn_abc123",
  "source": "/beza/loyalty/1.0",
  "type": "com.beza.loyalty.points_earned",
  "datacontenttype": "application/json",
  "subject": "user_42",
  "time": "2026-06-01T10:00:00Z",
  "tenant_id": "tenant_1",
  "data": {
    "points_id": 1,
    "user_id": 42,
    "amount": 25,
    "tier_multiplier": 1.0,
    "source": "transfer_send",
    "source_transaction_id": 12345,
    "running_balance": 15025,
    "expires_at": "2027-06-01T10:00:00Z",
    "created_at": "2026-06-01T10:00:00Z"
  }
}
```
**Consumers**: Notification (push: "+25 نقطة"), Analytics, Activity history

### PointsRedeemed
```json
{
  "specversion": "1.0",
  "id": "evt_pts_rdm_abc123",
  "source": "/beza/loyalty/1.0",
  "type": "com.beza.loyalty.points_redeemed",
  "datacontenttype": "application/json",
  "subject": "user_42",
  "time": "2026-06-01T10:00:00Z",
  "tenant_id": "tenant_1",
  "data": {
    "redemption_id": "rdm_abc123",
    "user_id": 42,
    "reward_id": 1,
    "reward_name": "Transfer Fee Discount 5,000",
    "points_spent": 5000,
    "syp_value": 5000,
    "coupon_code": "CPN_FD_ABC123",
    "balance_after": 10000,
    "created_at": "2026-06-01T10:00:00Z"
  }
}
```
**Consumers**: Notification, Analytics (redemption rate), Liability accounting

### TierUpgraded
```json
{
  "specversion": "1.0",
  "id": "evt_tier_up_abc123",
  "source": "/beza/loyalty/1.0",
  "type": "com.beza.loyalty.tier_upgraded",
  "datacontenttype": "application/json",
  "subject": "user_42",
  "time": "2026-06-02T02:00:00Z",
  "tenant_id": "tenant_1",
  "data": {
    "user_id": 42,
    "previous_tier": "silver",
    "new_tier": "gold",
    "rolling_total_points": 50200,
    "benefits_activated": {
      "transfer_fee": "0.3%",
      "points_multiplier": "1.5x",
      "daily_limit": 2000000
    },
    "upgraded_at": "2026-06-02T02:00:00Z"
  }
}
```
**Consumers**: Notification (push + in-app celebration), Fee/Limit service updates, Analytics

### TierDowngraded
```json
{
  "specversion": "1.0",
  "id": "evt_tier_down_abc123",
  "source": "/beza/loyalty/1.0",
  "type": "com.beza.loyalty.tier_downgraded",
  "datacontenttype": "application/json",
  "subject": "user_42",
  "time": "2026-06-02T02:00:00Z",
  "tenant_id": "tenant_1",
  "data": {
    "user_id": 42,
    "previous_tier": "gold",
    "new_tier": "silver",
    "rolling_total_points": 48000,
    "reason": "12_month_rolling_below_threshold",
    "downgraded_at": "2026-06-02T02:00:00Z"
  }
}
```
**Consumers**: Notification, Fee/Limit service updates

### PointsExpired
```json
{
  "specversion": "1.0",
  "id": "evt_pts_exp_abc123",
  "source": "/beza/loyalty/1.0",
  "type": "com.beza.loyalty.points_expired",
  "datacontenttype": "application/json",
  "subject": "user_42",
  "time": "2026-06-01T02:00:00Z",
  "data": {
    "user_id": 42,
    "points_expired": 2500,
    "balance_after": 12500,
    "expired_at": "2026-06-01T02:00:00Z"
  }
}
```
**Consumers**: Analytics, Points balance recalculation

### MerchantCampaignActivated
```json
{
  "specversion": "1.0",
  "id": "evt_camp_act_abc123",
  "source": "/beza/loyalty/1.0",
  "type": "com.beza.loyalty.campaign_activated",
  "datacontenttype": "application/json",
  "subject": "merchant_15",
  "time": "2026-06-01T08:00:00Z",
  "data": {
    "campaign_id": "cmp_abc123",
    "merchant_id": 15,
    "merchant_name": "اليكتترونكس",
    "campaign_name": "عروض الصيف",
    "type": "multiplier",
    "multiplier": 2.0,
    "budget": 500000,
    "start_date": "2026-06-01",
    "end_date": "2026-07-01"
  }
}
```
**Consumers**: Customer feed (show offer), Notification, Analytics

## Event Flow Diagram
```
WalletDebited/TransactionCompleted
    │
    ▼
EarnPointsAction::execute()
    │
    ├── Calculate points (amount / 1000 × tier multiplier)
    ├── Insert points ledger entry
    ├── Update points balance
    │
    ├── emit(PointsEarned) ───────────────────────────┐
    │    ├── Queue: PointsNotificationJob              │
    │    ├── Queue: LoyaltyAnalytics                   │
    │    └── Queue: CampaignCheckJob (merchant bonus?) │
    │                                                  │
    └── CheckTierUpgradeAction (if significant change) │
         │                                              │
         └── emit(TierUpgraded) ───────────────────────┤
              ├── ApplyTierBenefits (update fees/limits)│
              └── SendTierUpgradeNotification           │
```

## Batch Processing Flow
```
Daily Cron (02:00 AM):
  1. ProcessPointsExpiryJob
     → Find entries with expires_at <= today
     → Mark as expired, deduct from balance
     → emit(PointsExpired) per user

  2. CheckTierUpgradeJob (all users with recent activity)
     → Calculate 12-month rolling total
     → Check against tier thresholds
     → upgrade/downgrade as needed
     → emit(TierUpgraded / TierDowngraded)

  3. PointsExpiryWarningJob
     → Find users with points expiring in 30 days
     → emit(PointsExpiringWarning) for notification
```
