# Merchant Flutter State Management

## Provider Definitions (Riverpod)

### Dashboard Provider
```dart
@riverpod
class MerchantDashboard extends _$MerchantDashboard {
  @override
  AsyncValue<DashboardState> build() {
    _watchPaymentEvents();
    return const AsyncValue.loading();
  }

  Future<void> refresh() async {
    state = const AsyncValue.loading();
    state = await AsyncValue.guard(() => _fetchDashboard());
  }

  Future<DashboardState> _fetchDashboard() async {
    final repo = ref.read(merchantRepositoryProvider);
    return repo.getDashboard();
  }

  void _watchPaymentEvents() {
    ref.onDispose(
      ref.listen(eventBusProvider, (_, next) {
        next.whenData((event) {
          if (event is PaymentReceived) {
            _addTransaction(event.transaction);
            _updateSalesTotal(event.amount);
            _playPaymentSound();
          }
        });
      }).close,
    );
  }

  void _addTransaction(MerchantTransaction txn) {
    final current = state.value!;
    state = AsyncValue.data(current.copyWith(
      recentTransactions: [txn, ...current.recentTransactions.take(19)],
      todaySales: current.todaySales + txn.amount,
      transactionCount: current.transactionCount + 1,
    ));
  }

  void _playPaymentSound() {
    ref.read(merchantVoiceServiceProvider).playPaymentSound();
  }
}

class DashboardState {
  final int todaySales;
  final int transactionCount;
  final double trendVsYesterday; // percentage
  final List<MerchantTransaction> recentTransactions;
  final SettlementPreview settlementPreview;
  // ...
}
```

### QR Provider
```dart
@riverpod
class MerchantQr extends _$MerchantQr {
  MerchantQrState build() => MerchantQrState.initial();

  void setAmountType(AmountType type) {
    state = state.copyWith(amountType: type);
  }

  void setFixedAmount(int amount) {
    state = state.copyWith(fixedAmount: amount);
  }

  Future<void> refreshQrCode() async {
    state = state.copyWith(isLoading: true);
    final repo = ref.read(merchantRepositoryProvider);
    final qrData = await repo.getQrCodeData(
      type: state.amountType,
      amount: state.amountType == AmountType.dynamic ? state.fixedAmount : null,
    );
    state = state.copyWith(qrData: qrData, isLoading: false);
  }

  Future<void> downloadQr() async {
    final imageRepo = ref.read(qrImageRepositoryProvider);
    await imageRepo.saveToGallery(state.qrData);
  }

  void activateBrightnessBoost() {
    state = state.copyWith(brightnessBoost: true);
    Timer(const Duration(seconds: 60), () {
      state = state.copyWith(brightnessBoost: false);
    });
  }
}

class MerchantQrState {
  final String qrData; // the encoded QR string
  final Uint8List? qrImage; // rendered PNG bytes
  final AmountType amountType; // static or dynamic
  final int? fixedAmount;
  final bool brightnessBoost;
  final bool isLoading;
  // ...
}

enum AmountType { static, dynamic }
```

### Payment Link Provider
```dart
@riverpod
class PaymentLinkForm extends _$PaymentLinkForm {
  PaymentLinkFormState build() => PaymentLinkFormState.initial();

  void setAmount(int amount) {
    state = state.copyWith(amount: amount, isValid: _validate());
  }

  void setDescription(String description) {
    state = state.copyWith(description: description);
  }

  void setExpiry(Duration expiry) {
    state = state.copyWith(expiry: expiry);
  }

  bool _validate() {
    return state.amount >= 1000 && state.description.isNotEmpty;
  }

  Future<PaymentLinkResult> create() async {
    state = state.copyWith(isCreating: true);
    final repo = ref.read(merchantRepositoryProvider);
    try {
      final result = await repo.createPaymentLink(
        amount: state.amount,
        description: state.description,
        expiry: state.expiry,
      );
      state = state.copyWith(isCreating: false, createdLink: result);
      return result;
    } catch (e) {
      state = state.copyWith(isCreating: false, error: e.toString());
      rethrow;
    }
  }
}

class PaymentLinkFormState {
  final int amount;
  final String description;
  final Duration expiry;
  final bool isCreating;
  final PaymentLinkResult? createdLink;
  final String? error;
  final bool isValid;
  // ...
}
```

### Transaction List Provider
```dart
@riverpod
class MerchantTransactionList extends _$MerchantTransactionList {
  int _page = 1;
  bool _hasMore = true;
  String _filter = 'all';
  String? _search;
  DateTimeRange? _dateRange;

  @override
  AsyncValue<List<MerchantTransaction>> build() {
    _loadInitial();
    return const AsyncValue.loading();
  }

  Future<void> _loadInitial() async {
    state = const AsyncValue.loading();
    state = await AsyncValue.guard(() => _fetchPage(1));
  }

  Future<List<MerchantTransaction>> _fetchPage(int page) async {
    final repo = ref.read(merchantRepositoryProvider);
    return repo.getTransactions(
      page: page,
      filter: _filter,
      search: _search,
      dateRange: _dateRange,
    );
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

  void applyFilter(String filter) { ... }
  void applySearch(String? search) { ... }
  void applyDateRange(DateTimeRange? range) { ... }
}
```

## Event Bus Integration
```dart
// Event types consumed by Merchant feature
sealed class MerchantEvent {
  factory MerchantEvent.paymentReceived(MerchantTransaction txn) = PaymentReceived;
  factory MerchantEvent.settlementCompleted(Settlement settlement) = SettlementCompleted;
  factory MerchantEvent.verificationUpdated(MerchantStatus status) = VerificationUpdated;
  factory MerchantEvent.qrScanned(String qrId, int amount) = QrScanned;
  factory MerchantEvent.linkPaid(String linkId, int amount) = LinkPaid;
}

// Event handling in providers
ref.listen(eventBusProvider, (prev, next) {
  next.whenData((event) {
    switch (event) {
      case PaymentReceived(:final txn):
        _showNotification("تم استلام ${txn.amount} ل.س");
        _refreshDashboard();
        _playSound();
      case SettlementCompleted(:final settlement):
        _showNotification("تمت التسوية: ${settlement.netAmount} ل.س");
        _refreshSettlements();
      case VerificationUpdated(:final status):
        _updateVerificationBadge(status);
    }
  });
});
```

## Optimistic Updates
```dart
// Payment Link creation is not optimistic (server generates URL)
// Transaction list: new payments arrive via WebSocket, not polling
// Dashboard: optimistic update on WebSocket event is the primary mechanism

// For refunds:
Future<void> processRefund(String transactionId) async {
  // Optimistic: mark as refunded in UI
  ref.read(merchantTransactionList.notifier).markRefunded(transactionId);

  try {
    await ref.read(merchantRepositoryProvider).refundTransaction(transactionId);
  } catch (e) {
    // Rollback
    ref.read(merchantTransactionList.notifier).unmarkRefunded(transactionId);
    rethrow;
  }
}
```
