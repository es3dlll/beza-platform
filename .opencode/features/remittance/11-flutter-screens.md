# Remittance Flutter Screens

## Screen Tree
```
RemittanceFeature
├── SendRemittanceScreen
│   ├── BeneficiarySelector
│   │   ├── SavedBeneficiaryList
│   │   └── AddBeneficiarySheet
│   ├── AmountInput
│   ├── CurrencySelector
│   ├── FXRateCard
│   │   ├── RateDisplay
│   │   ├── MidMarketComparison
│   │   └── RateLockTimer
│   ├── FundingSourceSelector (diaspora: card, bank, wallet)
│   ├── DeliveryMethodSelector (wallet, agent, bank)
│   ├── FeeBreakdownCard
│   └── NoteInputField
│
├── ConfirmRemittanceSheet (Bottom Sheet)
│   ├── TransferSummaryCard
│   ├── FXDetailsRow
│   ├── FeeBreakdown
│   ├── ConfirmationPinInput
│   └── BiometricPrompt
│
├── RemittanceResultScreen
│   ├── SuccessAnimation (Lottie: money flying to world map)
│   ├── TransferDetailCard
│   ├── TimelineWidget
│   ├── ShareReceiptButton
│   └── RepeatTransferButton
│
├── BeneficiaryManagementScreen
│   ├── BeneficiarySearchBar
│   ├── BeneficiaryList
│   │   └── BeneficiaryCard
│   └── EmptyBeneficiaryState
│
├── AddBeneficiaryScreen
│   ├── NameField
│   ├── RelationshipDropdown
│   ├── PhoneField
│   ├── CityField
│   ├── CurrencyPreferenceToggle
│   └── NotesField
│
├── RecurringTransferListScreen
│   ├── ActiveRecurringList
│   │   └── RecurringTransferCard
│   └── EmptyRecurringState
│
├── CreateRecurringTransferScreen
│   ├── BeneficiarySelector
│   ├── AmountInput
│   ├── FrequencySelector
│   ├── DayOfMonthPicker
│   ├── DurationSelector
│   ├── FXPreferenceToggle
│   └── RecurringSummaryCard
│
├── RemittanceHistoryScreen
│   ├── FilterTabBar (All, Sent, Received, Recurring)
│   ├── SearchBar
│   ├── TransferList (Paginated)
│   │   └── TransferItemTile
│   └── EmptyStateWidget
│
├── RemittanceDetailScreen
│   ├── StatusTimelineWidget
│   ├── AmountDisplay
│   ├── FXDetailSection
│   ├── FeeBreakdownSection
│   └── ActionButtons (Repeat, Share, Report)
│
├── RequestMoneyScreen
│   ├── AmountInput
│   ├── ContactSelector
│   ├── NoteInput
│   └── SendRequestButton
│
└── CorridorAdminScreen (Admin panel)
    ├── CorridorList
    ├── CorridorDetail (limits, rates, compliance)
    └── CorridorEditSheet
```

## Screen Specifications

### SendRemittanceScreen (Diaspora)
```
Widget Tree:
  Scaffold(
    appBar: AppBar(title: "إرسال أموال", actions: [RecurringToggle]),
    body: SingleChildScrollView(
      child: Column(
        children: [
          BeneficiarySelector(horizontal list of saved + add),
          Divider,
          FundingSourceSelector(card/bank/wallet),
          AmountInput(autofocus, large),
          CurrencySelector(chips: EUR, USD, SYP),
          SizedBox(height: 16),
          FXRateCard(
            rate: 13200,
            midMarketRate: 13420,
            spread: 1.8,
            locked: false,
            countdown: null,
          ),
          DeliveryMethodSelector(chips: wallet, agent, bank),
          FeeBreakdownCard(
            amount: 300, fee: 4.50, total: 304.50,
            fxRate: 13200, recipientGets: 3960000,
          ),
          NoteInputField(hint: "ملاحظة (اختياري)"),
        ]
      )
    ),
    bottomNavigationBar: SafeArea(
      child: Padding(
        padding: EdgeInsets.all(16),
        child: RateLockButton + SendButton(column)
      )
    )
  )

Behavior:
  - Beneficiary selection auto-fills phone + currency preference
  - Amount input: auto-format with commas, max length 10
  - Currency selector: changes FX rate display immediately
  - Rate lock: 60-second countdown, refresh on expiry
  - Fee recalculates on amount change AND rate change
  - Send button: disabled until all valid → enabled
  - Tap send: validate → open ConfirmRemittanceSheet
```

### RemittanceDetailScreen
```
Widget Tree:
  Scaffold(
    appBar: AppBar(title: "تفاصيل التحويل"),
    body: SingleChildScrollView(
      child: Column(
        children: [
          StatusHeroSection(
            status: "completed",
            amount: 3960000,
            currency: "SYP",
          ),
          StatusTimelineWidget(
            steps: [
              Timestamp("تم بدء التحويل", "10:00:00"),
              Timestamp("تم تثبيت سعر الصرف", "10:00:02", rate: "13,200"),
              Timestamp("تم خصم المبلغ", "10:00:05"),
              Timestamp("تم استلام المبلغ", "10:00:08"),
            ],
            activeIndex: 3,
          ),
          Divider,
          DetailSection(title: "معلومات المرسل",
            rows: [
              DetailRow("الاسم", "خالد الحسن"),
              DetailRow("من", "برلين، ألمانيا"),
            ]
          ),
          DetailSection(title: "معلومات المستلم",
            rows: [
              DetailRow("الاسم", "أم محمد"),
              DetailRow("البلد", "سوريا - دمشق"),
            ]
          ),
          DetailSection(title: "تفاصيل المبلغ",
            rows: [
              DetailRow("المبلغ المرسل", "300.00 EUR"),
              DetailRow("سعر الصرف", "13,200 SYP/EUR"),
              DetailRow("الرسوم (1.5%)", "4.50 EUR"),
              DetailRow("المستلم", "3,960,000 ل.س"),
            ]
          ),
          ActionButtons(
            repeat: () => _repeatTransfer(),
            shareReceipt: () => _shareReceipt(),
            reportProblem: () => _reportProblem(),
          ),
        ]
      )
    )
  )
```
