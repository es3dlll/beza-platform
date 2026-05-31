# FX Engine Flutter Screens

## Screen Tree
```
FXFeature
├── ExchangeHomeScreen
│   ├── RateCardWidget (for each pair)
│   │   ├── PairHeader (currency + favorite toggle)
│   │   ├── RateDisplay (Beza rate large)
│   │   ├── BidAskRow (bid | ask)
│   │   ├── LastUpdatedTag
│   │   ├── SparklineChart (24h trend)
│   │   ├── SourceBreakdown (expandable)
│   │   │   ├── SourceRow (name, rate, health dot)
│   │   │   └── SpreadInfo (mid vs Beza rate)
│   │   └── PairActions (Convert, View Chart)
│   ├── FXMarketStatusBanner
│   └── ConvertFAB
│
├── ConvertScreen
│   ├── WalletSourcePicker
│   │   └── WalletBalanceDisplay
│   ├── WalletTargetPicker
│   │   └── WalletBalanceDisplay
│   ├── AmountInput
│   │   └── QuickAmountChips (100K, 500K, 1M, 5M)
│   ├── RatePreviewCard
│   │   ├── RateDisplay (live/locked)
│   │   ├── ConversionBreakdown (sends, receives, spread)
│   │   └── RateLockTimer (when locked)
│   └── ConfirmConversionButton
│
├── RateLockOverlay (Bottom Sheet)
│   ├── LockAnimation (circular countdown)
│   ├── RateDetails
│   ├── PinInput
│   └── BiometricPrompt
│
├── ConversionResultScreen
│   ├── SuccessAnimation (Lottie)
│   ├── ConversionDetailCard
│   │   ├── SentAmount
│   │   ├── ReceivedAmount
│   │   ├── RateUsed
│   │   ├── Spread
│   │   ├── Reference
│   │   └── Timestamp
│   └── ShareReceiptButton
│
├── ConversionHistoryScreen
│   ├── FilterChips (All, Today, Week, Month)
│   ├── ConversionList (Paginated)
│   │   └── ConversionItemTile
│   │       ├── PairIcon (SYP→USD)
│   │       ├── SentAmount (SYP)
│   │       ├── ReceivedAmount (USD)
│   │       ├── RateSummary
│   │       ├── StatusBadge
│   │       └── Timestamp
│   └── EmptyStateWidget
│
├── ConversionDetailScreen
│   ├── StatusHeroSection
│   ├── ConversionAmounts
│   ├── RateDetailSection
│   ├── FeeBreakdownSection
│   └── ActionButtons (Share, Report, Repeat)
│
└── AdminFXDashboard (admin only)
    ├── ProviderHealthGrid
    │   └── ProviderHealthCard
    │       ├── ProviderName
    │       ├── Status (green/red/amber)
    │       ├── LastResponseTime
    │       ├── LastSuccessAt
    │       ├── CurrentRate
    │       └── Priority
    ├── RateOverridePanel
    │   ├── PairSelector
    │   ├── ManualRateInput
    │   ├── ReasonField
    │   └── OverrideButton (2FA protected)
    ├── SpreadConfigPanel
    │   ├── PerPairSpreadSliders
    │   └── PerTierOverride
    ├── AnomalyAlertFeed
    └── CBSReportExport
```

## Screen Specifications

### ExchangeHomeScreen
```
Widget Tree:
  Scaffold(
    body: RefreshIndicator(
      child: CustomScrollView(
        slivers: [
          SliverAppBar(title: "أسعار الصرف", actions: [NotificationBell]),
          SliverToBoxAdapter(child: MarketStatusBanner),
          SliverList(
            delegate: SliverChildBuilderDelegate(
              (ctx, i) => RateCardWidget(pair: pairs[i]),
              childCount: pairs.length,
            )
          ),
        ]
      )
    ),
    floatingActionButton: ConvertFAB("تحويل", onTap: navigateToConvert)
  )

Behavior:
  - Pull-to-refresh: force refresh all rate cards
  - RateCardWidget tap: navigate to expanded detail (or inline expansion)
  - Long-press rate: show "Convert with this pair" quick action
  - Auto-refresh rate cards every 15s while visible
  - Favorite toggle persists to user preferences
```

### ConvertScreen
```
Widget Tree:
  Scaffold(
    appBar: AppBar(title: "تحويل عملة"),
    body: SingleChildScrollView(
      child: Column(
        children: [
          WalletSourcePicker,
          SizedBox(height: 16),
          WalletTargetPicker,
          Divider(),
          AmountInput,
          QuickAmountChips,
          RatePreviewCard,
        ]
      )
    ),
    bottomNavigationBar: SafeArea(
      child: Padding(
        child: ConvertButton (fullWidth)
      )
    )
  )

Behavior:
  - Wallet picker: show balance, filter by currency
  - Amount input: auto-format with commas
  - Rate preview updates live as amount changes
  - Convert button states: disabled → "أدخل المبلغ" / "تحويل" / "تثبيت السعر"
  - Convert button disabled if: no amount, insufficient balance, all providers down
  - Tap convert: if rate not locked → lock first, then confirm
```
