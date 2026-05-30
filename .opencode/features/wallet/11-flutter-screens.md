# Wallet Flutter Screens

## Screen Tree
```
WalletFeature
├── WalletHomeScreen
│   ├── BalanceCardWidget
│   │   └── EyeToggleWidget
│   ├── QuickActionsGrid
│   │   ├── SendActionButton
│   │   ├── RequestActionButton
│   │   ├── PayActionButton
│   │   ├── AgentActionButton
│   │   ├── TopUpActionButton
│   │   └── SavingsActionButton
│   ├── FXTickerWidget
│   ├── RecentTransactionsList
│   │   └── TransactionItemTile
│   └── SavingsGoalCard
│       └── CircularProgressIndicator
│
├── SendMoneyScreen
│   ├── ContactSearchBar
│   ├── RecentRecipientsHorizontalList
│   │   └── ContactAvatarChip
│   ├── PhoneNumberInput
│   ├── AmountInput
│   ├── CurrencyToggle
│   ├── FeeBreakdownCard
│   └── NoteInputField
│
├── ConfirmTransferScreen (Bottom Sheet)
│   ├── TransferSummaryCard
│   ├── ConfirmationPinInput
│   └── BiometricPrompt
│
├── TransferResultScreen
│   ├── SuccessAnimation (Lottie: confetti or checkmark)
│   ├── TransferDetailCard
│   └── ShareReceiptButton
│
├── TransactionHistoryScreen
│   ├── FilterTabBar (All, Sent, Received, Bills, Cash)
│   ├── SearchBar
│   ├── TransactionList (Paginated)
│   │   └── TransactionItemTile
│   └── EmptyStateWidget
│
├── TransactionDetailScreen
│   ├── StatusHeroSection
│   ├── AmountDisplay
│   ├── DetailRowsList
│   ├── FeeBreakdownSection
│   └── ActionButtons (Share, Report)
│
├── RequestMoneyScreen
│   ├── AmountInput
│   ├── ContactSelector
│   ├── NoteInput
│   └── SendRequestButton
│
├── AgentLocatorScreen (Map + List)
│   ├── MapWidget (Mapbox/Google Maps)
│   ├── AgentSearchBar
│   └── AgentResultList
│       └── AgentListItem (name, distance, status, queue)
│
└── BillPaymentFlow
    ├── BillCategoryGrid
    ├── CustomerIdInput
    ├── BillDetailCard
    ├── ConfirmPaymentSheet
    └── PaymentResultScreen
```

## Screen Specifications

### WalletHomeScreen
```
Widget Tree:
  Scaffold(
    body: RefreshIndicator(
      child: CustomScrollView(
        slivers: [
          SliverAppBar(pinned, title: "Beza", actions: [Notifications, Settings]),
          SliverToBoxAdapter(child: BalanceCard),
          SliverToBoxAdapter(child: QuickActionsGrid),
          SliverToBoxAdapter(child: FXTicker),
          SliverToBoxAdapter(child: SectionHeader("آخر المعاملات", onViewAll)),
          SliverList(delegate: TransactionItemDelegate),
          SliverToBoxAdapter(child: SavingsGoalCard),
        ]
      )
    ),
    bottomNavigationBar: BottomTabBar()
  )

Behavior:
  - Pull-to-refresh: resync balance + recent txns
  - BalanceCard tap: toggle currency (SYP ↔ USD)
  - Eye toggle: show/hide amounts with animation (crossfade 200ms)
  - Quick action: navigate to respective flow
  - Transaction tap: navigate to TransactionDetailScreen
  - Long press transaction: share receipt quick action
```

### SendMoneyScreen
```
Widget Tree:
  Scaffold(
    appBar: AppBar(title: "إرسال أموال"),
    body: SingleChildScrollView(
      child: Column(
        children: [
          ContactSearchBar,
          RecentRecipientsHorizontalList,
          Divider,
          PhoneNumberInput,
          SizedBox(height: 24),
          AmountInput,
          CurrencyToggle,
          FeeBreakdownCard,
          NoteInputField,
        ]
      )
    ),
    bottomNavigationBar: SafeArea(
      child: Padding(
        padding: EdgeInsets.all(16),
        child: TransferButton (fullWidth, Primary, 52h)
      )
    )
  )

Behavior:
  - Phone number input: mask +963 XX XXX XXXX
  - Amount input: auto-format with commas, max length 12
  - Currency toggle: immediate re-calculation of fee
  - Fee: real-time calculation as amount changes
  - Transfer button: disabled until valid (green) → enabled (primary)
  - Tap transfer: validate → open ConfirmTransferSheet
```

### TransactionHistoryScreen
```
Widget Tree:
  Scaffold(
    appBar: AppBar(title: "المعاملات"),
    body: Column(
      children: [
        SearchBar(hint: "ابحث عن معاملة..."),
        FilterTabBar(tabs: ["الكل", "مرسل", "مستلم", "فواتير", "نقدي"]),
        Expanded(
          child: TransactionList.builder(
            itemCount: transactions.length,
            itemBuilder: (context, index) => TransactionItemTile(
              icon: getIcon(transaction.type),
              label: transaction.label,
              amount: transaction.amount,
              timestamp: transaction.timestamp,
              status: transaction.status,
              onTap: () => navigateToDetail(transaction.id)
            ),
            onEndReached: () => loadMore(),
            emptyState: EmptyTransactionState()
          )
        )
      ]
    )
  )
```
