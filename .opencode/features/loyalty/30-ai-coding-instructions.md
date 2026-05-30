# Loyalty AI Coding Instructions

## Instructions for AI Code Generation Agent

This file contains the exact instructions for an AI coding agent to implement the Loyalty feature. Follow these specifications precisely.

## Implementation Order
```
Phase 1 (Files 1-10): Database migrations + Models + Enums
Phase 2 (Files 11-20): Repositories + Services + Actions
Phase 3 (Files 21-30): Controllers + API routes + Policies
Phase 4 (Files 31-40): Events + Listeners + Jobs
Phase 5 (Files 41-50): Tests + Factories
Phase 6 (Files 51-60): Flutter screens + Providers + Widgets
```

## Migration Files to Create

### 1. Create Loyalty Points (Ledger) Table
```php
// database/migrations/2026_01_01_020001_create_loyalty_points_table.php
// Schema definition in 16-database-schema.md
// Fields: id, user_id, tenant_id, amount (positive/negative), type (earned/redeemed/expired/adjusted),
//         source (transfer_send, transfer_receive, bill_payment, etc.),
//         source_transaction_id, tier_multiplier (DECIMAL 3,1), running_balance,
//         expires_at, expired_at, metadata (json), timestamps
// Indexes: user_id, user_id+type, user_id+created_at, expires_at, source_transaction_id
```

### 2. Create Loyalty Points Balance Table
```php
// Fields: id, user_id (unique), balance, last_updated
// Simple cache table for fast balance lookup
```

### 3. Create Loyalty Tiers Table (Seeded)
```php
// Seeded with Bronze, Silver, Gold, Platinum definitions including:
//   min_points_12mo, points_multiplier, transfer_fee, cash_out_fee,
//   daily_send_limit, daily_cashout_limit, max_balance, fx_spread_discount,
//   support_priority, sort_order
```

### 4. Create Loyalty Rewards Table
```php
// Fields: id, name, name_en, category (fee_discount/airtime/gift_card/partner_offer),
//         description, description_en, point_cost, syp_value, image_url,
//         provider, featured, popular, stock (NULL=unlimited),
//         status (active/inactive/coming_soon), sort_order, timestamps
```

### 5. Create Loyalty Redemptions Table
```php
// Fields: id, user_id, reward_id, points_spent, syp_value,
//         status (completed/refunded/expired), coupon_code, coupon_expires_at,
//         metadata (json), timestamps
```

### 6. Create Loyalty Member Tier History Table
```php
// Fields: id, user_id, tier_level, rolling_total_points,
//         action (initial/upgrade/downgrade/manual_change), reason, created_at
```

### 7. Create Loyalty Merchant Campaigns Table
```php
// Fields: id, merchant_id, name, type (multiplier/fixed_points/cashback),
//         multiplier, fixed_points, min_transaction_amount,
//         budget_syp, budget_remaining, start_date, end_date,
//         status (draft/active/paused/ended), redemption_count,
//         total_points_awarded, timestamps
```

### 8. Create Loyalty Campaign Redemptions Table
```php
// Fields: id, campaign_id, user_id, transaction_id, points_awarded,
//         transaction_amount, created_at
```

## Model Files to Create

### LoyaltyPoints Model
```php
// app/Modules/Loyalty/Models/LoyaltyPoints.php
// Relations: user()
// Scopes: earned(), redeemed(), expired(), notExpired()
// Methods: isCredit(), isDebit(), isExpired(), getSypValue()
// Casts: type (string), amount (integer)
```

### LoyaltyTier Model
```php
// Relations: none (static lookup table)
// Methods: getBenefits(), getNextTier(), isMaxTier()
```

### LoyaltyReward Model
```php
// Relations: redemptions()
// Scopes: active(), byCategory(), featured()
// Methods: isAvailable(), canAfford($userPoints)
```

### LoyaltyRedemption Model
```php
// Relations: user(), reward()
// Scopes: completed(), byUser()
// Methods: isFeeDiscount(), isExpired()
```

### LoyaltyMerchantCampaign Model
```php
// Relations: merchant(), redemptions()
// Scopes: active(), byMerchant()
// Methods: isActive(), hasBudgetFor(), deductBudget()
```

## Service Implementation Notes

### PointsService
```php
// earn(): calc points (amount/1000 × tier multiplier) → insert ledger → update balance → check tier
// getBalance(): from cache or calculate from ledger
// getHistory(): paginated with type filters
// calculatePoints(): amount / 1000 * multiplier, floor()
// Events: emit PointsEarned on earn
```

### TierService
```php
// getCurrentTier(): from member_tier_history (latest)
// getMultiplier(): from tiers config
// getTierProgress(): current, next, points required, percentage
// checkAndUpgrade(): batch all users, compare rolling total vs thresholds
// checkAndDowngrade(): batch all users, apply 30-day grace period
// Events: emit TierUpgraded, TierDowngraded
```

### RedemptionService
```php
// redeem(): validate balance → check availability → deduct points → create coupon → return result
// getRedemptionHistory(): paginated by user
// refundRedemption(): reverse points, invalidate coupon, update status
// Events: emit PointsRedeemed
```

### MerchantLoyaltyService
```php
// createCampaign(): create record, fund wallet, activate
// processCampaignTransaction(): check budget, award bonus points, track
// endCampaign(): stop new redemptions, refund remaining budget
// Events: emit MerchantCampaignActivated, CampaignBudgetExhausted
```

### PointsExpiryService
```php
// calculateExpiry(): created_at + 12 months
// processExpiry(): batch find expired, mark expired, deduct balance
// getPointsExpiringSoon(): SUM of points expiring within N days
// Events: emit PointsExpired, PointsExpiringWarning
```

## Flutter Implementation Notes

### State Management
- Use Riverpod with code generation
- Providers: loyaltyPointsProvider, tierProvider, rewardCatalogProvider, redemptionProvider
- Event bus integration for real-time points updates

### Screens
1. LoyaltyHubScreen: Points card, tier card, featured rewards, recent activity
2. TierProgressScreen: Current tier, progress bar, benefits, next tier preview
3. RewardsCatalogScreen: Category tabs, reward grid, redeem action
4. RedemptionConfirmationSheet: Reward summary, PIN input
5. PointsHistoryScreen: Filterable list with summaries
6. MerchantCampaignListScreen: Active campaigns, create button

### Widgets
- PointsCard: Animated counter, SYP value, today's earnings
- TierCard: Emblem, name, progress bar, benefits link
- TierBadge: Small badge component (used in wallet header, transaction detail)
- RewardItemCard: Icon, name, points cost, SYP value, redeem CTA
- PointsTransactionTile: Icon, description, amount, timestamp, expiry indicator

## Testing Requirements
- Minimum 80% code coverage on services
- All API endpoints have 200, 400, 401, 403, 422 response tests
- E2E tests for: points earning, tier upgrade, redemption, merchant campaign
- Flutter widget tests for all screens (loading, empty, error states)
- Batch job tests: tier upgrade, points expiry, settlement
