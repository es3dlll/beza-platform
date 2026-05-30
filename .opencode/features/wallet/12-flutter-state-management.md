# Wallet Flutter State Management

## Provider Definitions (Riverpod)

### Balance Provider
```dart
@riverpod
class WalletBalance extends _$WalletBalance {
  @override
  AsyncValue<BalanceState> build() {
    _watchBalanceUpdates();
    return const AsyncValue.loading();
  }

  Future<void> refresh() async {
    state = const AsyncValue.loading();
    state = await AsyncValue.guard(() => _fetchBalance());
  }

  Future<BalanceState> _fetchBalance() async {
    final repo = ref.read(walletRepositoryProvider);
    return repo.getBalance();
  }

  void _watchBalanceUpdates() {
    ref.onDispose(
      ref.listen(eventBusProvider, (_, next) {
        next.whenData((event) {
          if (event is WalletCredited || event is WalletDebited) {
            refresh();
          }
        });
      }).close,
    );
  }

  void toggleVisibility() {
    state = AsyncValue.data(state.value!.copyWith(hidden: !state.value.hidden));
  }
}

class BalanceState {
  final Map<String, int> balances; // SYP -> 125000, USD -> 250
  final DateTime lastUpdated;
  final bool hidden;
  // ...
}
```

### Transfer Provider
```dart
@riverpod
class TransferForm extends _$TransferForm {
  TransferFormState build() => TransferFormState.initial();

  void setPhoneNumber(String phone) {
    state = state.copyWith(phoneNumber: phone, isValid: _validate());
  }

  void setAmount(int amount) {
    state = state.copyWith(
      amount: amount,
      fee: _calculateFee(amount),
      total: amount + _calculateFee(amount),
      isValid: _validate(),
    );
  }

  void setNote(String note) {
    state = state.copyWith(note: note);
  }

  int _calculateFee(int amount) {
    // 0.5% capped at 5,000 SYP
    return min((amount * 5 ~/ 1000), 5000);
  }

  bool _validate() {
    return state.phoneNumber.length >= 10 &&
        state.amount >= 1000 &&
        state.amount <= state.balance;
  }

  Future<TransferResult> execute(String pin) async {
    final repo = ref.read(walletRepositoryProvider);
    return repo.sendMoney(
      phoneNumber: state.phoneNumber,
      amount: state.amount,
      note: state.note,
      pinHash: _hashPin(pin),
    );
  }
}

class TransferFormState {
  final String phoneNumber;
  final int amount;
  final int fee;
  final int total;
  final String note;
  final bool isValid;
  // ...
}
```

### Transaction List Provider
```dart
@riverpod
class TransactionList extends _$TransactionList {
  int _page = 1;
  bool _hasMore = true;
  String _filter = 'all';
  String? _search;

  @override
  AsyncValue<List<Transaction>> build() {
    _loadInitial();
    return const AsyncValue.loading();
  }

  Future<void> _loadInitial() async {
    state = const AsyncValue.loading();
    state = await AsyncValue.guard(() => _fetchPage(1));
  }

  Future<List<Transaction>> _fetchPage(int page) async {
    final repo = ref.read(walletRepositoryProvider);
    return repo.getTransactions(page: page, filter: _filter, search: _search);
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
}
```

## Event Bus Integration
```dart
// Event types consumed by Wallet feature
sealed class WalletEvent {
  factory WalletEvent.credited(int amount, String currency) = WalletCredited;
  factory WalletEvent.debited(int amount, String currency) = WalletDebited;
  factory WalletEvent.balanceRefreshed(BalanceState balance) = BalanceRefreshed;
  factory WalletEvent.transferFailed(String reason) = TransferFailed;
}

// Event handling in providers
ref.listen(eventBusProvider, (prev, next) {
  next.whenData((event) {
    switch (event) {
      case WalletCredited(:final amount, :final currency):
        _showNotification("تم استلام $amount $currency");
        _refreshBalance();
      case WalletDebited(:final amount, :final currency):
        _refreshBalance();
      case TransferFailed(:final reason):
        _showError(reason);
    }
  });
});
```

## Optimistic Updates
```dart
// When user confirms transfer:
// 1. Immediately debit balance in UI
// 2. Add pending transaction to history
// 3. Send API request in background
// 4. On success: update pending → completed
// 5. On failure: reverse optimistic update, show error

Future<void> executeTransfer() async {
  final optimisticTxn = Transaction.pending(
    id: uuid(),
    amount: -state.total,
    recipient: state.phoneNumber,
    timestamp: DateTime.now(),
  );

  // Optimistic UI update
  ref.read(walletBalanceProvider.notifier).applyDebit(state.total);
  ref.read(transactionListProvider.notifier).prepend(optimisticTxn);

  try {
    final result = await _transferUseCase.execute(...);
    ref.read(transactionListProvider.notifier)
        .updateStatus(optimisticTxn.id, TransactionStatus.completed);
  } catch (e) {
    // Rollback
    ref.read(walletBalanceProvider.notifier).reverseDebit(state.total);
    ref.read(transactionListProvider.notifier).remove(optimisticTxn.id);
    rethrow;
  }
}
```
