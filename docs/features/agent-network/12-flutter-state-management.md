# Agent Network Flutter State Management

## Provider Definitions (Riverpod with code generation)

### AgentAuthProvider
```dart
@riverpod
class AgentAuth extends _$AgentAuth {
  @override
  AsyncValue<AgentAuthState> build() {
    _checkExistingSession();
    return const AsyncValue.loading();
  }

  Future<void> _checkExistingSession() async {
    final secureStorage = ref.read(secureStorageProvider);
    final token = await secureStorage.read(key: 'agent_token');
    if (token != null) {
      final agent = await ref.read(agentRepositoryProvider).getProfile();
      state = AsyncValue.data(AgentAuthState.authenticated(agent));
    } else {
      state = const AsyncValue.data(AgentAuthState.unauthenticated());
    }
  }

  Future<void> login(String phone, String pin) async {
    state = const AsyncValue.loading();
    state = await AsyncValue.guard(() async {
      final repo = ref.read(agentRepositoryProvider);
      final result = await repo.login(phone, pin);
      await ref.read(secureStorageProvider).write(key: 'agent_token', value: result.token);
      return AgentAuthState.authenticated(result.agent);
    });
  }

  Future<void> changePin(String oldPin, String newPin) async {
    final repo = ref.read(agentRepositoryProvider);
    await repo.changePin(oldPin, newPin);
  }

  Future<void> logout() async {
    await ref.read(secureStorageProvider).delete(key: 'agent_token');
    ref.invalidateSelf();
  }
}

class AgentAuthState {
  final Agent? agent;
  final bool isAuthenticated;
  // ...
}
```

### AgentFloatProvider
```dart
@riverpod
class AgentFloat extends _$AgentFloat {
  @override
  AsyncValue<FloatState> build() {
    _watchFloatUpdates();
    _fetchFloat();
    return const AsyncValue.loading();
  }

  Future<void> _fetchFloat() async {
    final repo = ref.read(agentRepositoryProvider);
    state = await AsyncValue.guard(() => repo.getFloat());
  }

  Future<FloatTopUpResult> topUpFromWallet(int amount, String pin) async {
    final repo = ref.read(agentRepositoryProvider);
    final result = await repo.topUpFloat(amount, pin);
    await _fetchFloat();
    return result;
  }

  Future<void> requestAgentTransfer(int targetAgentId, int amount) async {
    final repo = ref.read(agentRepositoryProvider);
    await repo.requestFloatTransfer(targetAgentId, amount);
  }

  void _watchFloatUpdates() {
    ref.onDispose(
      ref.listen(eventBusProvider, (_, next) {
        next.whenData((event) {
          if (event is FloatCredited || event is FloatDebited) {
            _fetchFloat();
          }
        });
      }).close,
    );
  }
}

class FloatState {
  final int currentBalance;        // SYP (smallest unit)
  final int dailyCashInTotal;
  final int dailyCashOutTotal;
  final int dailyCommissionEarned;
  final DateTime lastUpdated;
  final FloatStatus status;        // ok, low (<100K), critical (<50K)
  final List<FloatMovement> recentMovements;
  // ...
}
```

### AgentTransactionProvider
```dart
@riverpod
class AgentTransactions extends _$AgentTransactions {
  int _page = 1;
  bool _hasMore = true;
  String _filter = 'all';
  String? _dateFrom;
  String? _dateTo;
  String? _search;

  @override
  AsyncValue<List<AgentTransaction>> build() {
    _loadInitial();
    return const AsyncValue.loading();
  }

  Future<void> _loadInitial() async {
    state = const AsyncValue.loading();
    state = await AsyncValue.guard(() => _fetchPage(1));
  }

  Future<List<AgentTransaction>> _fetchPage(int page) async {
    final repo = ref.read(agentRepositoryProvider);
    return repo.getTransactions(
      page: page,
      filter: _filter,
      dateFrom: _dateFrom,
      dateTo: _dateTo,
      search: _search,
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
  void applyDateRange(String? from, String? to) { ... }
  void applySearch(String? query) { ... }

  Future<void> exportCsv() async {
    final repo = ref.read(agentRepositoryProvider);
    final path = await repo.exportTransactionsCsv(
      filter: _filter, dateFrom: _dateFrom, dateTo: _dateTo,
    );
    // Trigger share dialog
  }
}
```

### AgentCashInProvider
```dart
@riverpod
class AgentCashIn extends _$AgentCashIn {
  @override
  CashInState build() => CashInState.initial();

  void setPhoneNumber(String phone) {
    state = state.copyWith(phoneNumber: phone, step: state.phoneNumber.length >= 9 ? 1 : 0);
  }

  void setVerificationCode(String code) {
    state = state.copyWith(verificationCode: code);
    if (code.length == 4) {
      _verifyCode(code);
    }
  }

  Future<void> _verifyCode(String code) async {
    state = state.copyWith(isVerifying: true);
    try {
      final repo = ref.read(agentRepositoryProvider);
      final customer = await repo.verifyCustomer(state.phoneNumber, code);
      state = state.copyWith(customer: customer, isVerifying: false, step: 2);
    } catch (e) {
      state = state.copyWith(isVerifying: false, error: "رمز غير صحيح");
    }
  }

  void setAmount(int amount) {
    state = state.copyWith(
      amount: amount,
      estimatedCommission: (amount * 5 ~/ 1000), // 0.5%
      isValid: amount >= 5000 && amount <= state.maxAmount,
    );
  }

  Future<CashInResult> execute() async {
    state = state.copyWith(isProcessing: true);
    try {
      final repo = ref.read(agentRepositoryProvider);
      final result = await repo.cashIn(
        phoneNumber: state.phoneNumber,
        amount: state.amount,
        verificationCode: state.verificationCode,
      );
      state = state.copyWith(isProcessing: false, step: 3, result: result);
      return result;
    } catch (e) {
      state = state.copyWith(isProcessing: false, error: e.toString());
      rethrow;
    }
  }

  void reset() {
    state = CashInState.initial();
  }
}

class CashInState {
  final int step;                    // 0-3
  final String phoneNumber;
  final String verificationCode;
  final Customer? customer;
  final int amount;
  final int estimatedCommission;
  final int maxAmount;
  final bool isVerifying;
  final bool isProcessing;
  final bool isValid;
  final CashInResult? result;
  final String? error;
  // ...
}
```

### AgentCashOutProvider
```dart
@riverpod
class AgentCashOut extends _$AgentCashOut {
  @override
  CashOutState build() => CashOutState.initial();

  // Similar structure to CashInProvider but with:
  // - Fee calculation (1.5% of amount, cap 15,000 SYP)
  // - Customer PIN verification (step 3)
  // - Biometric handling (step 3b)
  // - Cash handover confirmation (step 4)

  Future<CashOutResult> execute() async {
    state = state.copyWith(isProcessing: true);
    try {
      final repo = ref.read(agentRepositoryProvider);
      final result = await repo.cashOut(
        phoneNumber: state.phoneNumber,
        amount: state.amount,
        customerPin: state.customerPin,
        biometricVerified: state.biometricVerified,
      );
      state = state.copyWith(isProcessing: false, step: 4, result: result);
      return result;
    } catch (e) {
      state = state.copyWith(isProcessing: false, error: e.toString());
      rethrow;
    }
  }
}
```

### AgentSyncProvider
```dart
@riverpod
class AgentSync extends _$AgentSync {
  Timer? _periodicTimer;

  @override
  SyncState build() {
    _setupConnectivityListener();
    _startPeriodicSync();
    return SyncState(
      isSyncing: false,
      pendingCount: 0,
      failedCount: 0,
      lastSyncAt: null,
    );
  }

  void _setupConnectivityListener() {
    ref.onDispose(
      Connectivity().onConnectivityChanged.listen((result) {
        if (result != ConnectivityResult.none && state.pendingCount > 0) {
          syncAll();
        }
      }).cancel,
    );
  }

  void _startPeriodicSync() {
    _periodicTimer = Timer.periodic(const Duration(minutes: 5), (_) {
      if (state.pendingCount > 0) syncAll();
    });
    ref.onDispose(() => _periodicTimer?.cancel());
  }

  Future<void> syncAll() async {
    state = state.copyWith(isSyncing: true);
    try {
      final repo = ref.read(agentRepositoryProvider);
      final result = await repo.syncPendingTransactions();
      state = state.copyWith(
        isSyncing: false,
        pendingCount: result.pendingCount,
        failedCount: result.failedCount,
        lastSyncAt: DateTime.now(),
      );
      // Refresh float + transactions after sync
      ref.read(agentFloatProvider.notifier).refresh();
      ref.read(agentTransactionsProvider.notifier).refresh();
    } catch (e) {
      state = state.copyWith(isSyncing: false, lastError: e.toString());
    }
  }
}

class SyncState {
  final bool isSyncing;
  final int pendingCount;
  final int failedCount;
  final DateTime? lastSyncAt;
  final String? lastError;
  // ...
}
```

## Event Bus Integration
```dart
// Events consumed by Agent Network feature
sealed class AgentEvent {
  factory AgentEvent.floatCredited(int amount, int newBalance) = FloatCredited;
  factory AgentEvent.floatDebited(int amount, int newBalance) = FloatDebited;
  factory AgentEvent.floatLow(int currentBalance) = FloatLow;
  factory AgentEvent.floatCritical(int currentBalance) = FloatCritical;
  factory AgentEvent.commissionEarned(int amount) = CommissionEarned;
  factory AgentEvent.transactionSynced(String reference) = TransactionSynced;
  factory AgentEvent.syncFailed(String reference, String reason) = SyncFailed;
}

// Event handling
ref.listen(eventBusProvider, (prev, next) {
  next.whenData((event) {
    switch (event) {
      case FloatLow(:final currentBalance):
        _showAlert("⚠️ رصيد الصندوق منخفض: ${formatAmount(currentBalance)} ل.س");
        _playAlertSound();
      case FloatCritical(:final currentBalance):
        _showAlert("🚨 رصيد الصندوق حرج! قم بالتعبئة فوراً");
        _vibrate();
      case CommissionEarned(:final amount):
        _showToast("💰 ربحت $amount ل.س عمولة");
      case SyncFailed(:final reference):
        _showError("فشل مزامنة المعاملة $reference");
    }
  });
});
```

## Optimistic Updates (Offline Mode)
```dart
// When network is unavailable, transactions are queued locally
// and UI reflects the change immediately

Future<void> executeCashInOffline() async {
  // 1. Save to offline queue
  final queueItem = OfflineQueueItem(
    id: uuid(),
    type: 'cash_in',
    payload: {'phone': phone, 'amount': amount, ...},
    createdAt: DateTime.now(),
    retryCount: 0,
  );
  await ref.read(offlineQueueProvider).add(queueItem);

  // 2. Optimistic UI update
  ref.read(agentFloatProvider.notifier).applyDebit(amount);
  ref.read(agentTransactionsProvider.notifier).addPending(queueItem);

  // 3. Show queued status
  _showToast("🔵 ستتم المعاملة تلقائياً عند الاتصال");

  // 4. Trigger sync (will process when online)
  ref.read(agentSyncProvider.notifier).syncAll();
}
```
