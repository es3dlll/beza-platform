# Loyalty Flutter Screens

## Screen Tree
```
LoyaltyFeature
├── LoyaltyHubScreen
│   ├── PointsCard
│   │   ├── PointsBalanceDisplay (animated counter)
│   │   ├── SypEquivalentLabel
│   │   ├── TodayEarningsLabel
│   │   └── RedeemButton
│   ├── TierCard
│   │   ├── TierIcon (emblem per tier)
│   │   ├── TierNameLabel
│   │   ├── ProgressBar (animated)
│   │   ├── ProgressLabel ("30,000 / 50,000")
│   │   └── BenefitsButton
│   ├── FeaturedRewardsSection
│   │   └── RewardItemCard (icon, title, points, CTA)
│   └── RecentPointsActivityList
│       └── PointsTransactionTile
│
├── TierProgressScreen
│   ├── CurrentTierHero
│   │   ├── LargeTierIcon
│   │   ├── TierName
│   │   └── ProgressRing (percentage)
│   ├── CurrentBenefitsList
│   │   └── BenefitRow (icon, label, value)
│   ├── NextTierPreview
│   │   ├── NextTierIcon
│   │   ├── NextTierName
│   │   ├── AdditionalBenefitsList
│   │   └── EstimatedSavingsLabel
│   └── EarningTipsSection
│       └── TipRow (icon, description, earn rate)
│
├── RewardsCatalogScreen
│   ├── CategoryTabs (Fee, Airtime, GiftCards, Partners)
│   └── RewardGrid / List
│       └── RewardItemCard
│           ├── RewardIcon
│           ├── Title
│           ├── PointCost
│           ├── SypValue
│           └── RedeemButton
│
├── RedemptionConfirmationSheet (Bottom Sheet)
│   ├── RewardSummaryCard
│   ├── PointCostDisplay
│   ├── SypValueDisplay
│   └── ConfirmWithPinInput
│
├── RedemptionResultScreen
│   ├── SuccessAnimation (Lottie)
│   ├── RedemptionDetail
│   └── ActionButton (use now / go to catalog)
│
├── PointsHistoryScreen
│   ├── SummaryCard (earned, used, expired totals)
│   ├── FilterTabs (all, earned, redeemed, expired)
│   └── PointsTransactionList (paginated)
│       └── PointsTransactionTile
│
├── MerchantCampaignListScreen
│   ├── ActiveCampaignsSection
│   └── CreateCampaignButton
│
└── MerchantCampaignDetailScreen
    ├── CampaignInfoCard
    ├── RedemptionStats
    └── CampaignActions (pause, extend, end)
```

## Screen Specifications

### LoyaltyHubScreen
```
Widget Tree:
  Scaffold(
    body: RefreshIndicator(
      child: CustomScrollView(
        slivers: [
          SliverAppBar(title: "برنامج المكافآت"),
          SliverToBoxAdapter(child: PointsCard),
          SliverToBoxAdapter(child: TierCard),
          SliverToBoxAdapter(child: SectionHeader("المكافآت المميزة", onViewAll)),
          SliverToBoxAdapter(child: FeaturedRewards),
          SliverToBoxAdapter(child: SectionHeader("آخر النشاط")),
          SliverList(delegate: PointsActivityDelegate),
        ]
      )
    )
  )

Behavior:
  - Pull-to-refresh: resync points, tier, rewards
  - PointsCard tap: open points history
  - TierCard tap: open tier progress screen
  - Reward tap: open redemption confirmation
  - Points animation: counter animates from old to new value
```

### RewardsCatalogScreen
```
Widget Tree:
  Scaffold(
    appBar: AppBar(title: "المكافآت"),
    body: Column(
      children: [
        CategoryTabBar,
        Expanded(
          child: RewardList(
            grid: crossAxisCount: 2,
            itemBuilder: RewardItemCard
          )
        )
      ]
    )
  )

Behavior:
  - Category tabs: filter rewards by type
  - Grid layout: 2 columns on mobile, 3 on tablet
  - RewardItemCard: tap to view detail + redeem
  - "Coming soon" badge on future rewards
  - "Popular" badge on top-redeemed rewards
```
