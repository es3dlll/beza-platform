# Remittance Flutter State Management

## Provider Definitions (Riverpod)

### FX Rate Provider
```dart
@riverpod
class FXRate extends _$FXRate {
  Timer? _countdownTimer;
  int _secondsRemaining = 60;

  @override
  AsyncValue<FXRateState> build(String corridor) {
    _fetchRate(corridor);
    return const AsyncValue.loading();
  }

  Future<void> _fetchRate(String corridor) async {
    state = const AsyncValue.loading();
    state = await AsyncValue.guard(() => _getRate(corridor));
  }

  Future<FXRateState> _getRate(String corridor) async {
    final repo = ref.read(remittanceRepositoryProvider);
    return repo.getLiveRate(corridor);
  }

  Future<void> lockRate() async {
    final current = state.valueOrNull;
    if (current == null) return;

    state = AsyncValue.data(current.copyWith(locked: true, lockedAt: DateTime.now()));
    _secondsRemaining = 60;
    _startCountdown();
  }

  void _startCountdown() {
    _countdownTimer?.cancel();
    _countdownTimer = Timer.periodic(const Duration(seconds: 1), (timer) {
      _secondsRemaining--;
      if (_secondsRemaining <= 0) {
        timer.cancel();
        _unlockRate();
      } else {
        final current = state.valueOrNull;
        if (current != null) {
          state = AsyncValue.data(current.copyWith(countdown: _secondsRemaining));
        }
      }
    });
  }

  void _unlockRate() {
    final current = state.valueOrNull;
    if (current != null) {
      state = AsyncValue.data(current.copyWith(
        locked: false,
        lockedAt: null,
        countdown: 0,
      ));
    }
    _fetchRate(current!.corridor);
  }

  @override
  void dispose() {
    _countdownTimer?.cancel();
    super.dispose();
  }
}

class FXRateState {
  final String corridor;         // "EUR->SYP"
  final double rate;              // 13200.0
  final double midMarketRate;    // 13420.0
  final double spread;            // 1.8
  final bool locked;
  final DateTime? lockedAt;
  final int countdown;            // seconds remaining
  final DateTime lastUpdated;
}
```

### Transfer Form Provider
```dart
@riverpod
class RemittanceForm extends _$RemittanceForm {
  RemittanceFormState build() => RemittanceFormState.initial();

  void setBeneficiary(Beneficiary ben) {
    state = state.copyWith(
      beneficiary: ben,
      currency: ben.currencyPreference,
      isValid: _validate(),
    );
  }

  void setAmount(double amount, String currency) {
    final fx = ref.read(fxRateProvider(state.corridor).valueOrNull);
    final fee = _calculateFee(amount, state.corridor);
    final recipientGets = fx != null ? (amount * fx.rate * (1 - fee / 100)).round() : 0;

    state = state.copyWith(
      amount: amount,
      currency: currency,
      fee: fee,
      totalDebit: amount + (amount * fee / 100),
      recipientGets: recipientGets,
      isValid: _validate(),
    );
  }

  double _calculateFee(double amount, String corridor) {
    // Diaspora: 1.5% of amount
    // Local P2P: 0.5% capped at 5000 SYP
    if (corridor.startsWith('local')) {
      return (amount * 0.005).clamp(0, 5000);
    }
    return amount * 0.015;
  }

  bool _validate() {
    return state.beneficiary != null &&
        state.amount > 0 &&
        state.fundingSource != null;
  }

  Future<RemittanceResult> execute(String pinHash, bool biometricVerified) async {
    final repo = ref.read(remittanceRepositoryProvider);
    final fx = ref.read(fxRateProvider(state.corridor).valueOrNull);

    return repo.sendRemittance(
      beneficiaryId: state.beneficiary!.id,
      amount: state.amount,
      sourceCurrency: state.currency,
      targetCurrency: state.targetCurrency,
      fxRate: fx?.rate,
      fxLocked: fx?.locked ?? false,
      note: state.note,
      pinHash: pinHash,
      biometricVerified: biometricVerified,
    );
  }
}

class RemittanceFormState {
  final Beneficiary? beneficiary;
  final double amount;
  final String currency;           // EUR, USD, SYP
  final String targetCurrency;     // SYP, USD
  final String corridor;           // "EUR->SYP", "USD->SYP", "local"
  final double fee;
  final double totalDebit;
  final int recipientGets;         // in SYP (or target currency)
  final String? fundingSource;
  final String? deliveryMethod;
  final String note;
  final bool isValid;
}
```

### Beneficiary Provider
```dart
@riverpod
class BeneficiaryList extends _$BeneficiaryList {
  @override
  AsyncValue<List<Beneficiary>> build() {
    _loadBeneficiaries();
    return const AsyncValue.loading();
  }

  Future<void> _loadBeneficiaries() async {
    state = const AsyncValue.loading();
    state = await AsyncValue.guard(() =>
      ref.read(remittanceRepositoryProvider).getBeneficiaries());
  }

  Future<void> addBeneficiary(Beneficiary beneficiary) async {
    final repo = ref.read(remittanceRepositoryProvider);
    await repo.createBeneficiary(beneficiary);
    _loadBeneficiaries();
  }

  Future<void> deleteBeneficiary(int id) async {
    final repo = ref.read(remittanceRepositoryProvider);
    await repo.deleteBeneficiary(id);
    _loadBeneficiaries();
  }
}

class Beneficiary {
  final int id;
  final String name;
  final String relationship;     // أمي، أبي، أخي، إلخ
  final String phone;
  final String? city;
  final String currencyPreference; // SYP, USD
  final int? totalSent;
  final DateTime? lastSentAt;
  final bool isActive;
}
```

### Recurring Transfer Provider
```dart
@riverpod
class RecurringTransferList extends _$RecurringTransferList {
  @override
  AsyncValue<List<RecurringTransfer>> build() {
    _loadRecurring();
    return const AsyncValue.loading();
  }

  Future<void> createRecurring(CreateRecurringRequest request) async {
    final repo = ref.read(remittanceRepositoryProvider);
    await repo.createRecurringTransfer(request);
    _loadRecurring();
  }

  Future<void> pauseRecurring(int id) async {
    final repo = ref.read(remittanceRepositoryProvider);
    await repo.pauseRecurringTransfer(id);
    _loadRecurring();
  }

  Future<void> cancelRecurring(int id) async {
    final repo = ref.read(remittanceRepositoryProvider);
    await repo.cancelRecurringTransfer(id);
    _loadRecurring();
  }
}

class RecurringTransfer {
  final int id;
  final Beneficiary beneficiary;
  final double amount;
  final String currency;
  final String frequency;       // weekly, biweekly, monthly, quarterly
  final int dayOfMonth;
  final String duration;         // ongoing, fixed_count, end_date
  final int? maxExecutions;
  final DateTime? endDate;
  final String status;           // active, paused, cancelled, completed
  final DateTime nextExecution;
  final int totalExecutions;
  final double totalSent;
  final DateTime? lastExecutedAt;
}
```

## Event Bus Integration
```dart
sealed class RemittanceEvent {
  factory RemittanceEvent.sent(RemittanceResult result) = RemittanceSent;
  factory RemittanceEvent.received(RemittanceReceivedData data) = RemittanceReceived;
  factory RemittanceEvent.failed(String reason, String code) = RemittanceFailed;
  factory RemittanceEvent.fxLocked(String corridor, double rate) = FXLocked;
  factory RemittanceEvent.recurringExecuted(RecurringTransfer transfer) = RecurringExecuted;
}

// Event handling in providers
ref.listen(eventBusProvider, (prev, next) {
  next.whenData((event) {
    switch (event) {
      case RemittanceSent(:final result):
        _showNotification("تم إرسال ${result.recipientGets} ل.س");
        ref.read(remittanceHistoryProvider.notifier).refresh();
      case RemittanceReceived(:final data):
        _showNotification("تم استلام ${data.amount} من ${data.senderName}");
      case RemittanceFailed(:final reason):
        _showError(reason);
      case RecurringExecuted(:final transfer):
        _showNotification("تم تنفيذ التحويل الدوري إلى ${transfer.beneficiary.name}");
    }
  });
});
```
