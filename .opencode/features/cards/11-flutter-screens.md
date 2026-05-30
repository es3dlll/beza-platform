# Cards Flutter Screens

## Screen Tree
```
CardsFeature
├── CardsHomeScreen
│   ├── CardCarouselWidget
│   │   └── CardVisualWidget (front face)
│   │       ├── NetworkLogoWidget (Mastercard/Visa/ local)
│   │       ├── CardLastFourWidget
│   │       ├── CardNicknameWidget
│   │       ├── CardExpiryWidget
│   │       └── CardBalanceWidget
│   ├── CardCarouselPageIndicator
│   ├── CardQuickActionGrid
│   │   ├── FreezeToggleAction
│   │   ├── ShowDetailsAction
│   │   ├── LimitSettingsAction
│   │   ├── TransactionsAction
│   │   ├── AddToWalletAction (Apple Pay / Google Pay)
│   │   └── ReplaceCardAction
│   └── OneTimeCardFAB
│
├── CreateCardScreen
│   ├── CardTypeSelector (Virtual / Physical)
│   ├── CurrencySelector (SYP / USD)
│   ├── CardNicknameInput
│   ├── InitialLimitEditor
│   ├── FeeBreakdownCard
│   └── CreateCardButton
│
├── CardDetailScreen
│   ├── CardVisualWidget (full detail mode)
│   ├── PanDisplayWidget (masked → reveal with biometric)
│   ├── CvvDisplayWidget (masked → reveal)
│   ├── ExpiryDisplayWidget
│   ├── CardStatusBadge
│   ├── LimitSlidersSection
│   │   ├── OnlineLimitSlider
│   │   ├── PosLimitSlider
│   │   ├── AtmLimitSlider
│   │   └── InternationalLimitSlider
│   ├── PinManagementSection
│   │   └── ChangePinButton
│   └── CardTransactionsList (paginated)
│       └── CardTransactionItemTile
│
├── OneTimeCardScreen
│   ├── AmountInput (exact purchase amount)
│   ├── GenerateCardButton
│   ├── CardRevealWidget
│   ├── CountdownTimer (24h destroy)
│   └── AutoDestroyedBadge
│
├── ChangePinScreen
│   ├── CurrentPinInput
│   ├── NewPinInput
│   ├── ConfirmNewPinInput
│   └── BiometricScreenLock
│
├── CardTransactionDetailScreen
│   ├── StatusHeroSection
│   ├── AmountDisplay
│   ├── MerchantInfoCard
│   ├── TransactionDetailRows
│   └── ReportTransactionButton
│
├── ReportLostCardScreen
│   ├── CardSelector
│   ├── ReportReasonSelector
│   └── ConfirmAndBlockButton
│
└── CardReplacementScreen
    ├── CardPreviewWidget
    ├── ReplacementReasonSelector
    ├── DeliveryMethodSelector (Agent / Courier)
    ├── FeeDisplay
    └── RequestReplacementButton
```

## Key Widget Behaviors

### CardVisualWidget
- Front face: Network logo top-right, masked PAN, last 4, expiry, nickname, balance
- Frozen state: Entire card greyed out, "مجمدة" overlay with lock icon
- Lost state: Red overlay with "مفقودة" badge
- One-time state: Teal gradient background with "لمرة واحدة" badge
- Reveal animation: Card flip on detail screen

### LimitSlidersSection
- Range: 0 to KYC-based maximum
- Default values from card program configuration
- Categories: Online, POS, ATM, International
- Each slider shows current / max, with remaining calculated below
- ATM default: 0 (disabled) until physical card ordered

### PinManagementSection
- Set PIN: First-time setup, 6-digit numeric
- Change PIN: Requires current PIN + biometric
- PIN attempts remaining shown if recent failure
- "PIN محجوب" if blocked due to 3 failed attempts
