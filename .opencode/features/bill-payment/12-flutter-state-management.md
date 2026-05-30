# Bill Payment Flutter State Management

## Provider Definitions (Riverpod)

### Bill Category Provider
```dart
@riverpod
class BillCategory extends _$BillCategory {
  @override
  AsyncValue<List<BillerCategory>> build() {
    _loadCategories();
    return const AsyncValue.loading();
  }

  Future<void> _loadCategories() async {
    state = const AsyncValue.loading();
    state = await AsyncValue.guard(() => _fetchCategories());
  }

  Future<List<BillerCategory>> _fetchCategories() async {
    final repo = ref.read(billRepositoryProvider);
    return repo.getCategories();
  }

  Future<void> refresh() async {
    await _loadCategories();
  }
}

class BillerCategory {
  final String id;             // "electricity", "water", "telecom"
  final String nameAr;         // "كهرباء"
  final String nameEn;         // "Electricity"
  final String iconUrl;        // category icon
  final List<Biller> billers;  // billers in this category
}
```

### Bill Fetch Provider
```dart
@riverpod
class BillFetch extends _$BillFetch {
  @override
  BillFetchState build() => BillFetchState.initial();

  Future<void> fetchBill({
    required String billerType,
    required String customerId,
  }) async {
    state = BillFetchState.loading(customerId: customerId);
    final result = await AsyncValue.guard(() {
      final repo = ref.read(billRepositoryProvider);
      return repo.fetchBill(billerType: billerType, customerId: customerId);
    });
    state = result.when(
      data: (bill) => BillFetchState.fetched(bill: bill),
      error: (e, _) => BillFetchState.failed(error: e.toString()),
    );
  }

  void reset() {
    state = BillFetchState.initial();
  }
}

class BillFetchState {
  final bool isLoading;
  final bool isFetched;
  final bool isFailed;
  final String? customerId;
  final Bill? bill;
  final String? error;

  const BillFetchState._({
    this.isLoading = false,
    this.isFetched = false,
    this.isFailed = false,
    this.customerId,
    this.bill,
    this.error,
  });

  factory BillFetchState.initial() => const BillFetchState._();
  factory BillFetchState.loading({String? customerId}) =>
      BillFetchState._(isLoading: true, customerId: customerId);
  factory BillFetchState.fetched({required Bill bill}) =>
      BillFetchState._(isFetched: true, bill: bill);
  factory BillFetchState.failed({required String error}) =>
      BillFetchState._(isFailed: true, error: error);
}
```

### Bill Payment Provider
```dart
@riverpod
class BillPayment extends _$BillPayment {
  @override
  BillPaymentState build() => BillPaymentState.initial();

  Future<void> payBill({
    required String billId,
    required String pin,
    required String billerReference,
  }) async {
    state = BillPaymentState.processing();
    final result = await AsyncValue.guard(() {
      final repo = ref.read(billRepositoryProvider);
      return repo.payBill(
        billId: billId,
        pinHash: _hashPin(pin),
        billerReference: billerReference,
      );
    });
    state = result.when(
      data: (receipt) => BillPaymentState.paid(receipt: receipt),
      error: (e, _) => BillPaymentState.failed(error: e.toString()),
    );
  }

  void reset() {
    state = BillPaymentState.initial();
  }

  String _hashPin(String pin) {
    // Client-side pin hashing before sending
    return sha256.convert(utf8.encode(pin)).toString();
  }
}

class BillPaymentState {
  final bool isProcessing;
  final bool isPaid;
  final bool isFailed;
  final BillReceipt? receipt;
  final String? error;

  const BillPaymentState._({
    this.isProcessing = false,
    this.isPaid = false,
    this.isFailed = false,
    this.receipt,
    this.error,
  });

  factory BillPaymentState.initial() => const BillPaymentState._();
  factory BillPaymentState.processing() =>
      const BillPaymentState._(isProcessing: true);
  factory BillPaymentState.paid({required BillReceipt receipt}) =>
      BillPaymentState._(isPaid: true, receipt: receipt);
  factory BillPaymentState.failed({required String error}) =>
      BillPaymentState._(isFailed: true, error: error);
}
```

### Bill History Provider
```dart
@riverpod
class BillHistory extends _$BillHistory {
  int _page = 1;
  bool _hasMore = true;
  String _filter = 'all';
  String? _search;

  @override
  AsyncValue<List<BillTransaction>> build() {
    _loadInitial();
    return const AsyncValue.loading();
  }

  Future<void> _loadInitial() async {
    state = const AsyncValue.loading();
    state = await AsyncValue.guard(() => _fetchPage(1));
  }

  Future<List<BillTransaction>> _fetchPage(int page) async {
    final repo = ref.read(billRepositoryProvider);
    return repo.getBillHistory(page: page, filter: _filter, search: _search);
  }

  Future<void> loadMore() async {
    if (!_hasMore) return;
    final current = state.value ?? [];
    final newPage = await _fetchPage(_page + 1);
    if (newPage.isEmpty) {
      _hasMore = false;
    } else {
      _page++;
      state = AsyncValue.data([...current, ...newPage]);
    }
  }

  void applyFilter(String filter) {
    _filter = filter;
    _page = 1;
    _hasMore = true;
    _loadInitial();
  }

  void applySearch(String? search) {
    _search = search;
    _page = 1;
    _hasMore = true;
    _loadInitial();
  }
}
```

### Bill Schedule Provider
```dart
@riverpod
class BillSchedule extends _$BillSchedule {
  @override
  AsyncValue<List<ScheduledBill>> build() {
    _loadScheduled();
    return const AsyncValue.loading();
  }

  Future<void> _loadScheduled() async {
    state = const AsyncValue.loading();
    state = await AsyncValue.guard(() {
      final repo = ref.read(billRepositoryProvider);
      return repo.getScheduledBills();
    });
  }

  Future<void> createSchedule({
    required String billerType,
    required String customerId,
    required ScheduleType type,
    required int reminderDays,
    required DateTime? nextDue,
    required bool autoPay,
  }) async {
    final repo = ref.read(billRepositoryProvider);
    await repo.createSchedule(
      billerType: billerType,
      customerId: customerId,
      type: type,
      reminderDays: reminderDays,
      nextDue: nextDue,
      autoPay: autoPay,
    );
    _loadScheduled();
  }

  Future<void> cancelSchedule(int scheduleId) async {
    final repo = ref.read(billRepositoryProvider);
    await repo.cancelSchedule(scheduleId);
    _loadScheduled();
  }

  Future<void> toggleAutoPay(int scheduleId, bool enabled) async {
    final repo = ref.read(billRepositoryProvider);
    await repo.toggleAutoPay(scheduleId, enabled);
    _loadScheduled();
  }
}

class ScheduledBill {
  final int id;
  final String billerType;
  final String billerName;
  final String customerId;
  final int? amount;      // null if variable
  final ScheduleType type;
  final int reminderDays;
  final DateTime nextDue;
  final bool autoPayEnabled;
  final String status;    // active, paused, cancelled
}
```

## Event Bus Integration
```dart
// Events consumed by Bill Payment feature
sealed class BillEvent {
  factory BillEvent.paid(BillReceipt receipt) = BillPaid;
  factory BillEvent.failed(String billerType, String error) = BillPaymentFailed;
  factory BillEvent.reminderDue(ScheduledBill bill) = BillReminderDue;
  factory BillEvent.autoPayCompleted(BillReceipt receipt) = AutoPayCompleted;
  factory BillEvent.autoPayFailed(String billerType, String reason) = AutoPayFailed;
}

// Event handling in providers
ref.listen(eventBusProvider, (prev, next) {
  next.whenData((event) {
    switch (event) {
      case BillPaid(:final receipt):
        _showNotification("تم دفع فاتورة ${receipt.billerName}");
        ref.read(billHistoryProvider.notifier).refresh();
      case BillReminderDue(:final bill):
        _showReminderNotification(bill);
      case AutoPayCompleted(:final receipt):
        _showAutoPayNotification(receipt);
      case AutoPayFailed(:final billerType, :final reason):
        _showAutoPayFailure(billerType, reason);
    }
  });
});
```

## Optimistic Updates
```dart
// When user pays a bill:
// 1. Show processing immediately
// 2. On success: add to history, show receipt
// 3. On failure: show error with retry option
// 4. No optimistic balance debit (wait for server confirmation)

Future<void> executePayment() async {
  final processingState = BillPaymentState.processing();
  state = processingState;

  try {
    final receipt = await _paymentUseCase.execute(
      billId: billId,
      pin: pin,
    );
    state = BillPaymentState.paid(receipt: receipt);
    ref.read(billHistoryProvider.notifier).addToTop(receipt);
  } catch (e) {
    state = BillPaymentState.failed(error: e.toString());
  }
}
```
