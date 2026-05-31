# Merchant Flutter Screens

## Screen Tree
```
MerchantFeature
├── MerchantRegistrationFlow
│   ├── PhoneEntryScreen
│   ├── OtpVerificationScreen
│   ├── PinCreationScreen
│   ├── BusinessInfoScreen
│   │   ├── BusinessNameField
│   │   ├── BusinessTypeDropdown
│   │   └── AddressInput
│   ├── DocumentUploadScreen
│   │   ├── LicenseCameraCapture
│   │   └── ShopPhotoCapture
│   └── VerificationPendingScreen
│
├── MerchantHomeScreen
│   ├── DailySalesCard
│   │   └── SalesAnimatedCounter
│   ├── QuickActionsGrid
│   │   ├── ShowQrButton
│   │   ├── CreateLinkButton
│   │   ├── ViewReportsButton
│   │   └── TransactionsButton
│   ├── RecentTransactionsList
│   │   └── MerchantTransactionTile
│   └── SettlementPreviewCard
│       └── SettlementProgressBar
│
├── QrDisplayScreen
│   ├── QrCodeImage (large, high-res)
│   ├── BusinessNameLogoRow
│   ├── AmountTypeToggle (static/dynamic)
│   ├── FixedAmountInput (if dynamic)
│   ├── BrightnessBoostButton
│   └── ShareDownloadActions
│
├── PaymentLinkScreen
│   ├── AmountInputSection
│   ├── DescriptionInput
│   ├── ExpirySelector
│   ├── PreviewCard
│   └── ResultActions (WhatsApp, SMS, Copy)
│
├── TransactionHistoryScreen
│   ├── FilterChips (All, QR, Link, POS)
│   ├── DateRangeSelector
│   ├── SearchBar
│   ├── PaginatedTransactionList
│   └── EmptyStateWidget
│
├── TransactionDetailScreen
│   ├── StatusHeroSection
│   ├── AmountDisplay
│   ├── DetailRowsList (customer, time, method, reference)
│   ├── MdrBreakdown
│   └── ActionButtons (Share, Refund)
│
├── SettlementHistoryScreen
│   ├── SettlementList (daily entries)
│   │   └── SettlementTile (date, gross, MDR, net)
│   └── SettlementDetailScreen
│       ├── SummarySection
│       ├── TransactionList (grouped under settlement)
│       └── DownloadPdfButton
│
└── MerchantSettingsScreen
    ├── BusinessProfileSection
    ├── WebhookConfiguration
    ├── NotificationPreferences
    ├── PinChange
    └── InviteMerchant
```

## Screen Specifications

### MerchantHomeScreen
```
Widget Tree:
  Scaffold(
    body: RefreshIndicator(
      child: CustomScrollView(
        slivers: [
          SliverAppBar(pinned, title: "متجر الشمّام", actions: [Notifications, Settings]),
          SliverToBoxAdapter(child: DailySalesCard),
          SliverToBoxAdapter(child: QuickActionsGrid),
          SliverToBoxAdapter(child: SectionHeader("آخر المعاملات", onViewAll)),
          SliverList(delegate: MerchantTransactionDelegate),
          SliverToBoxAdapter(child: SettlementPreviewCard),
        ]
      )
    )
  )

Behavior:
  - Pull-to-refresh: resync sales + recent txns + settlement preview
  - Daily sales card: animated counter when new payment arrives
  - Quick actions: navigate to respective screens
  - Transaction tap: navigate to TransactionDetailScreen
  - Settlement card tap: navigate to SettlementDetailScreen
  - Voice: new payment automatically spoken
  - Notification bell: unread count badge (settlement, verification updates)
```

### QrDisplayScreen
```
Widget Tree:
  Scaffold(
    appBar: AppBar(title: "QR Code"),
    body: Column(
      children: [
        Expanded(
          child: Center(
            child: Container(
              decoration: BoxDecoration(
                border: Border.all(color: Primary, width: 4),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  QrCodeImage(data: merchant.qrData, size: 280),
                  SizedBox(height: 12),
                  Text(businessName, style: heading),
                  if (logo != null) CircleAvatar(backgroundImage: logo, radius: 24),
                ]
              )
            )
          )
        ),
        BrightnessBoostButton(),
        Row(children: [DownloadButton(), PrintButton()]),
      ]
    ),
    bottomNavigationBar: PaymentAmountSection + ShareButton
  )

Behavior:
  - Brightness boost: set system brightness to 100%, revert after 60s
  - Download: save QR PNG to gallery
  - Amount toggle: static vs dynamic
  - Dynamic amount: numeric input with keyboard
  - Share: open system share sheet with QR image
  - Auto-rotate: QR display works in landscape for counter use
```

### PaymentLinkScreen
```
Widget Tree:
  Scaffold(
    appBar: AppBar(title: "إنشاء رابط دفع"),
    body: SingleChildScrollView(
      child: Column(
        children: [
          AmountInput(large, centered),
          DescriptionTextField(maxLines: 2),
          ExpirySelector(),
          SizedBox(height: 24),
          PreviewCard(amount, description, expiry),
          SizedBox(height: 24),
          CreateLinkButton(fullWidth, Primary),
        ]
      )
    )
  )

Behavior:
  - Amount: formatter with commas, max 12 digits
  - Preview: real-time update as user types
  - Create: POST to API → get short URL → show share sheet
  - Share sheet: WhatsApp, SMS, Copy, System share
```

### TransactionHistoryScreen
```
Widget Tree:
  Scaffold(
    appBar: AppBar(title: "المعاملات"),
    body: Column(
      children: [
        FilterChips(["الكل", "QR", "رابط", "نقطة بيع"]),
        DateRangeSelector(),
        SearchBar(hint: "ابحث عن معاملة..."),
        Expanded(
          child: TransactionList.builder(
            itemCount: transactions.length,
            itemBuilder: (context, index) => MerchantTransactionTile(
              amount: transaction.amount,
              customerPhone: transaction.customerPhone,
              timestamp: transaction.timestamp,
              method: transaction.method, // QR/Link/POS badge
              onTap: () => navigateToDetail(transaction.id)
            ),
            onEndReached: () => loadMore(),
            emptyState: EmptyMerchantTransactionState()
          )
        )
      ]
    )
  )
```
