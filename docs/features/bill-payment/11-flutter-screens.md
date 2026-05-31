# Bill Payment Flutter Screens

## Screen Tree
```
BillPaymentFeature
├── BillCategoryScreen (Root)
│   ├── BillCategoryGrid
│   │   ├── ElectricityCategoryCard
│   │   │   └── BillerListSheet (PEED, AleppoElectricity)
│   │   ├── WaterCategoryCard
│   │   │   └── BillerListSheet (DamascusWater, HomsWater)
│   │   ├── TelecomCategoryCard
│   │   │   └── BillerListSheet (Syriatel, MTN, SyriaTelecom)
│   │   ├── InternetCategoryCard
│   │   │   └── BillerListSheet (Aya, Saman)
│   │   ├── GovernmentCategoryCard
│   │   │   └── BillerListSheet (CivilAffairs, Passport, Justice)
│   │   └── EducationCategoryCard
│   │       └── BillerListSheet (DamascusUni, AlShamUni)
│   └── RecentBillsList
│       └── RecentBillItemTile
│
├── CustomerIdEntryScreen
│   ├── BillerHeaderWidget
│   ├── CustomerIdInput
│   │   └── PasteButton
│   │   └── ScanButton (Phase 2)
│   ├── IdFormatHint
│   └── PastBillsForIdPreview
│
├── BillDetailScreen
│   ├── BillDetailCard
│   │   ├── HeroAmountDisplay
│   │   ├── BillBreakdownTable
│   │   ├── LateFeeBanner
│   │   └── DueDateInfo
│   ├── ConfirmCheckbox
│   └── PrimaryPayButton
│
├── PaymentConfirmationSheet (Bottom Sheet)
│   ├── PaymentSummaryCard
│   ├── PinInputField
│   └── BiometricAuthOption
│
├── PaymentResultScreen
│   ├── PaymentSuccessAnimation (Lottie: checkmark)
│   ├── ReceiptCard
│   │   ├── BezaReferenceRow
│   │   ├── BillerReferenceRow
│   │   ├── AmountRow
│   │   ├── DateTimeRow
│   │   └── BillerNameRow
│   └── ActionButtons (Share, PDF, Pay Again, Home)
│
├── PaymentFailureScreen (variant)
│   ├── FailureAnimation
│   ├── ErrorReasonCard
│   └── ActionButtons (Retry, Fund Wallet, Support)
│
├── BillHistoryScreen
│   ├── BillFilterTabBar (All, Electricity, Water, Telecom, etc.)
│   ├── SearchBar
│   ├── BillHistoryList (Paginated)
│   │   └── BillHistoryItemTile (icon, biller, amount, date, status)
│   └── EmptyStateWidget
│
├── BillDetailScreen (History)
│   ├── ReceiptCard
│   ├── StatusTimeline
│   └── ActionButtons (Share, Report Issue)
│
└── ScheduledBillsScreen
    ├── UpcomingSection
    │   └── ScheduledBillTile (biller, customer ID, due date, auto-pay badge)
    ├── PastDueSection
    │   └── OverdueBillTile (red banner, pay now button)
    └── AddReminderButton
```

## Screen Specifications

### BillCategoryScreen
```
Widget Tree:
  Scaffold(
    appBar: AppBar(title: "دفع الفواتير"),
    body: RefreshIndicator(
      child: CustomScrollView(
        slivers: [
          SliverToBoxAdapter(child: SectionHeader("الفئات", subtitle: "اختر الفئة ثم المزوّد")),
          SliverGrid(
            gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
              crossAxisCount: 3,
              childAspectRatio: 1,
              crossAxisSpacing: 12,
              mainAxisSpacing: 12,
            ),
            delegate: SliverChildBuilderDelegate(
              // 6 category cards
            ),
          ),
          SliverToBoxAdapter(child: SizedBox(height: 24)),
          SliverToBoxAdapter(child: SectionHeader("آخر الفواتير المدفوعة")),
          SliverList(delegate: SliverChildListDelegate([
            RecentBillItemTile(biller: "PEED", amount: 44625, date: "10 يونيو"),
            RecentBillItemTile(biller: "Syriatel", amount: 33000, date: "5 يونيو"),
          ])),
        ]
      )
    ),
    bottomNavigationBar: BillBottomTabBar()
  )

Behavior:
  - Category tap: open biller list (modal bottom sheet)
  - Recent bill tap: pre-fill customer ID and navigate to detail
  - Biller list: shows biller logo, name (Ar/En), supported features
  - Search biller: text input at top of biller list
```

### CustomerIdEntryScreen
```
Widget Tree:
  Scaffold(
    appBar: AppBar(title: biller.name),
    body: Padding(
      child: Column(
        children: [
          BillerHeaderWidget(logo, name, category),
          SizedBox(height: 32),
          CustomerIdInput(
            controller: _idController,
            maxLength: biller.idLength,
            formatMask: biller.idFormat, // e.g. "XXXX-XXXX-XXXX-XXXX-XXXX"
            keyboardType: TextInputType.number,
            onChanged: _validateId,
          ),
          IdFormatHint(text: biller.idHint),
          if (biller.supportsOcr) ScanButton(),
          SizedBox(height: 24),
          PastBillsForIdPreview(biller: biller, customerId: _idController.text),
          Spacer(),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton(
              onPressed: _isValid ? _fetchBill : null,
              child: Text("استعلام عن الفاتورة"),
            ),
          ),
          SizedBox(height: 16),
        ]
      )
    )
  )

Behavior:
  - Auto-format: insert group separators as user types
  - Validate: check digit count, biller-specific check digit (Phase 2)
  - Scanner: open camera, OCR the 24-digit smart meter number
  - Existing bills: show past bills if this ID was used before
```

### BillHistoryScreen
```
Widget Tree:
  Scaffold(
    appBar: AppBar(title: "سجل الفواتير"),
    body: Column(
      children: [
        SearchBar(hint: "ابحث عن فاتورة..."),
        BillFilterTabBar(
          tabs: ["الكل", "كهرباء", "مياه", "اتصالات", "إنترنت", "حكومة"],
        ),
        Expanded(
          child: BillHistoryList.builder(
            itemCount: transactions.length,
            itemBuilder: (context, index) => BillHistoryItemTile(
              billerIcon: getBillerIcon(transaction.billerType),
              billerName: transaction.billerName,
              customerId: transaction.customerId,
              amount: transaction.amount,
              date: transaction.paidAt,
              status: transaction.status,
              onTap: () => navigateToDetail(transaction.id),
            ),
            onEndReached: () => loadMore(),
            emptyState: EmptyBillHistoryWidget(
              message: "لا توجد فواتير مدفوعة بعد",
              actionLabel: "ادفع فاتورة الآن",
            ),
          ),
        )
      ]
    )
  )
```

### ScheduledBillsScreen
```
Widget Tree:
  Scaffold(
    appBar: AppBar(title: "الفواتير المجدولة", actions: [AddButton]),
    body: ListView(
      children: [
        if (overdueBills.isNotEmpty)
          SectionHeader("متأخرة", trailing: "عدد: ${overdueBills.length}"),
        ...overdueBills.map((b) => OverdueBillTile(
          biller: b.biller,
          amount: b.amount,
          dueDate: b.dueDate,
          lateFee: b.lateFee,
          onPay: () => payBill(b),
        )),
        SectionHeader("قادمة"),
        ...upcomingBills.map((b) => ScheduledBillTile(
          biller: b.biller,
          customerId: b.customerId,
          nextDue: b.nextDue,
          type: b.scheduleType, // once / monthly / bi-monthly
          autoPay: b.autoPayEnabled,
          reminderDays: b.reminderDays,
          onEdit: () => editSchedule(b),
          onCancel: () => cancelSchedule(b),
          onToggleAutoPay: () => toggleAutoPay(b),
        )),
      ]
    )
  )
```
