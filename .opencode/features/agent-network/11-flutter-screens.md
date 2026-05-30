# Agent Network Flutter Screens

## Screen Tree
```
AgentNetworkFeature
├── AgentPosHomeScreen
│   ├── FloatDisplayCard
│   │   └── FloatStatusIndicator (color-coded)
│   ├── DailyStatsStrip (totals, commissions)
│   ├── CashInActionButton (green, 64h)
│   ├── CashOutActionButton (red, 64h)
│   ├── RecentTransactionsPreview (last 3)
│   │   └── AgentTransactionTile (3 items)
│   ├── AlertBanner (offline/float-low/notification)
│   └── OfflineQueueBadge (when >0 pending)
│
├── CashInScreen (multi-step wizard)
│   ├── StepIndicator (1-4)
│   ├── PhoneInputStep
│   │   ├── PhoneNumberInput (masked)
│   │   └── NumericKeypad
│   ├── VerificationStep
│   │   ├── VerificationCodeInput (4 digits)
│   │   └── ResendCodeTimer
│   ├── AmountStep
│   │   ├── AmountInput (monospace, large)
│   │   ├── NumericKeypad
│   │   ├── CommissionEstimateLabel
│   │   └── LimitInfoBanner
│   └── ConfirmationStep
│       ├── TransactionSummaryCard
│       ├── CustomerInfoCard
│       └── ConfirmButton (green)
│
├── CashOutScreen (multi-step wizard)
│   ├── StepIndicator (1-5)
│   ├── PhoneInputStep (same as Cash-in)
│   ├── VerificationStep (same as Cash-in)
│   ├── AmountStep
│   │   ├── AmountInput
│   │   ├── FeeBreakdownCard (amount + 1.5% fee + total)
│   │   ├── AgentCommissionLabel
│   │   └── LimitCheckBanner
│   ├── CustomerPinStep
│   │   ├── PinInput (customer enters PIN)
│   │   └── BiometricPrompt (if >500K SYP)
│   └── ConfirmationStep
│       ├── CashHandoverPrompt ("هل سلمت النقود؟")
│       └── CompleteButton
│
├── FloatManagementScreen
│   ├── FloatDisplayCard (detailed)
│   ├── FloatChart (last 7 days trend — simple bar chart)
│   ├── TopUpOptionsCard
│   │   ├── TopUpFromWalletOption
│   │   ├── TopUpFromAgentOption
│   │   └── TopUpAtHubOption
│   ├── FloatActivityList (recent float movements)
│   │   └── FloatActivityTile
│   └── LowFloatAlertSettings
│
├── TransactionHistoryScreen
│   ├── DateFilterSelector (today, yesterday, this week, custom)
│   ├── TypeFilterChips (الكل, إيداع, سحب, تعبئة, عمولة)
│   ├── SearchBar (phone number, amount, reference)
│   ├── TransactionList (paginated, pull-to-refresh)
│   │   └── AgentTransactionTile
│   └── ExportButton (print, PDF, CSV)
│
├── CommissionScreen
│   ├── CommissionSummaryCard (today, this month, pending settlement)
│   ├── CommissionChart (monthly bars, last 6 months)
│   ├── SettlementHistoryList
│   │   └── SettlementTile (date, amount, status)
│   └── CommissionRateInfo (tier rates explained)
│
├── AgentProfileScreen
│   ├── AgentInfoCard (name, code, tier badge, shop name)
│   ├── PerformanceStats (rating, volume rank, uptime %)
│   ├── DeviceInfo (device model, serial, printer connection)
│   ├── SecuritySettings (change PIN, biometric toggle)
│   └── SupportSection (hotline, WhatsApp, FAQs)
│
└── ReceiptPreviewScreen (bottom sheet)
    ├── ReceiptHeader (Beza logo + shop name)
    ├── TransactionDetails (reference, amount, date)
    ├── QRCode
    ├── PrintButton (bluetooth)
    ├── ShareButton (PDF)
    └── DoneButton
```

## Screen Specifications

### AgentPosHomeScreen
```
Widget Tree:
  Scaffold(
    appBar: AppBar(
      title: "وكيل Beza — $shopName",
      actions: [AlertBadgeIcon, SettingsIcon],
    ),
    body: RefreshIndicator(
      onRefresh: () => ref.read(agentSyncProvider.notifier).syncAll(),
      child: SingleChildScrollView(
        child: Column(
          children: [
            FloatDisplayCard(),
            SizedBox(height: 8),
            DailyStatsStrip(),
            SizedBox(height: 16),
            CashInActionButton(onTap: () => context.push('/agent/cash-in')),
            SizedBox(height: 12),
            CashOutActionButton(onTap: () => context.push('/agent/cash-out')),
            SizedBox(height: 20),
            SectionHeader("آخر المعاملات", onViewAll: () => context.push('/agent/history')),
            RecentTransactionsList(limit: 3),
            SizedBox(height: 16),
            if (showAlert) AlertBanner(message, type: alertType),
          ],
        ),
      ),
    ),
    bottomNavigationBar: AgentBottomNavBar(
      items: [الإيداع, السحب, الصندوق, العمليات],
      currentIndex: 0,
    ),
  )

Behavior:
  - Pull-to-refresh: sync offline queue → refresh float → refresh recent txns
  - Float card tap: navigate to FloatManagementScreen
  - Cash-in button: navigate to CashInScreen, reset state
  - Cash-out button: navigate to CashOutScreen, reset state
  - Transaction tap: show ReceiptPreviewScreen (bottom sheet)
  - Auto-refresh: UI refreshes when sync completes (via Riverpod listener)
```

### CashInScreen
```
Widget Tree:
  Scaffold(
    appBar: AppBar(title: "إيداع نقدي"),
    body: Column(
      children: [
        StepIndicator(currentStep: step, totalSteps: 4),
        Expanded(
          child: AnimatedSwitcher(
            child: [
              PhoneInputStep(),
              VerificationStep(),
              AmountStep(),
              ConfirmationStep(),
            ][step],
          ),
        ),
      ],
    ),
  )

Behavior:
  - Step 0 (Phone): auto-enable "التالي" when 9 digits entered
  - Step 1 (Code): auto-submit when 4 digits entered, resend timer 30s
  - Step 2 (Amount): real-time commission estimate, validate min/max
  - Step 3 (Confirm): API call → success/failure screen
  - Back button: confirm exit if step > 0 ("هل تريد إلغاء الإيداع؟")
```

### CashOutScreen
```
Widget Tree:
  Scaffold(
    appBar: AppBar(title: "سحب نقدي"),
    body: Column(
      children: [
        StepIndicator(currentStep: step, totalSteps: 5),
        Expanded(
          child: AnimatedSwitcher(
            child: [
              PhoneInputStep(),
              VerificationStep(),
              AmountStep(),
              CustomerPinStep(),
              CashHandoverStep(),
            ][step],
          ),
        ),
      ],
    ),
  )

Behavior:
  - Step 3 (PIN): 3 attempts → block 30 min → support contact option
  - Step 3 (Biometric): auto-capture fingerprint, fallback to PIN
  - Step 4 (Handover): timer 120 seconds, auto-complete after timeout
  - If customer leaves without cash: agent reports via "لم يستلم" button → dispute
```
