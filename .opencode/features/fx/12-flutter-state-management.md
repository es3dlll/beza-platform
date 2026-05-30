# FX Engine Flutter State Management

## Provider Definitions (Riverpod)

### FX Rate List Provider
```dart
@riverpod
class FXRateList extends _$FXRateList {
  Timer? _refreshTimer;

  @override
  AsyncValue<Map<String, RatePair>> build() {
    _startAutoRefresh();
    ref.onDispose(_stopAutoRefresh);
    refresh();
    return const AsyncValue.loading();
  }

  Future<void> refresh() async {
    state = await AsyncValue.guard(() => _fetchRates());
  }

  Future<Map<String, RatePair>> _fetchRates() async {
    final repo = ref.read(fxRateRepositoryProvider);
    return repo.getLiveRates();
  }

  void _startAutoRefresh() {
    _refreshTimer = Timer.periodic(const Duration(seconds: 15), (_) {
      // Silent refresh (no loading state)
      _fetchRates().then((rates) {
        state = AsyncValue.data(rates);
      }).catchError((_) {
        // Keep current state on error
      });
    });
  }

  void _stopAutoRefresh() {
    _refreshTimer?.cancel();
  }
}

class RatePair {
  final String pair; // "SYP/USD"
  final double bid;
  final double ask;
  final double mid;
  final double bezaRate;
  final double spread;
  final DateTime lastUpdated;
  final List<RateSource> sources;
  final List<RatePoint> history24h;
}
```

### Conversion Form Provider
```dart
@riverpod
class ConversionForm extends _$ConversionForm {
  ConversionFormState build() => ConversionFormState.initial();

  void setSourceWallet(Wallet wallet) {
    state = state.copyWith(sourceWallet: wallet, isValid: _validate());
  }

  void setTargetWallet(Wallet wallet) {
    state = state.copyWith(targetWallet: wallet, isValid: _validate());
  }

  void setAmount(int amount) {
    final rates = ref.read(fxRateListProvider).valueOrNull;
    final rate = rates?[_buildPairKey()];
    state = state.copyWith(
      amount: amount,
      bezaRate: rate?.bezaRate,
      outputAmount: _calculateOutput(amount, rate?.bezaRate),
      spreadAmount: _calculateSpread(amount, rate),
      isValid: _validate(),
    );
  }

  double _calculateOutput(int amount, double? rate) {
    if (rate == null) return 0;
    if (state.sourceWallet.currency == Currency.SYP) {
      return amount / rate;
    }
    return amount * rate;
  }

  int _calculateSpread(int amount, RatePair? rate) {
    if (rate == null) return 0;
    final midValue = amount / rate.mid;
    final bezaValue = amount / rate.bezaRate;
    return (midValue - bezaValue).abs().toInt();
  }

  bool _validate() {
    return state.sourceWallet != null &&
        state.targetWallet != null &&
        state.amount > 0 &&
        state.amount <= (state.sourceWallet?.balance ?? 0);
  }

  String _buildPairKey() {
    return '${state.sourceWallet?.currency}/${state.targetWallet?.currency}';
  }
}

class ConversionFormState {
  final Wallet? sourceWallet;
  final Wallet? targetWallet;
  final int amount;
  final double? bezaRate;
  final double outputAmount;
  final int spreadAmount;
  final bool isValid;
  // ...
}
```

### Rate Lock Provider
```dart
@riverpod
class RateLock extends _$RateLock {
  Timer? _countdownTimer;

  @override
  RateLockState build() => RateLockState.idle();

  Future<void> lockRate(String pair, int amount) async {
    state = RateLockState.locking();
    try {
      final repo = ref.read(fxRateRepositoryProvider);
      final lock = await repo.lockRate(
        pair: pair,
        amount: amount,
        durationSeconds: 30,
      );
      state = RateLockState.locked(
        lockId: lock.lockId,
        rate: lock.rate,
        expiresAt: lock.expiresAt,
      );
      _startCountdown(lock.expiresAt);
    } catch (e) {
      state = RateLockState.failed(e.toString());
    }
  }

  void _startCountdown(DateTime expiresAt) {
    _countdownTimer = Timer.periodic(const Duration(seconds: 1), (_) {
      final remaining = expiresAt.difference(DateTime.now());
      if (remaining.inSeconds <= 0) {
        state = RateLockState.expired();
        _countdownTimer?.cancel();
      } else {
        state = RateLockState.locked(
          remainingSeconds: remaining.inSeconds,
        );
      }
    });
  }

  @override
  void dispose() {
    _countdownTimer?.cancel();
    super.dispose();
  }
}

sealed class RateLockState {
  const RateLockState();
  const factory RateLockState.idle() = RateLockIdle;
  const factory RateLockState.locking() = RateLocking;
  const factory RateLockState.locked({String? lockId, double? rate, DateTime? expiresAt, int remainingSeconds}) = RateLocked;
  const factory RateLockState.failed(String reason) = RateLockFailed;
  const factory RateLockState.expired() = RateLockExpired;
}
```

### Conversion Execution Provider
```dart
@riverpod
class ConversionExecution extends _$ConversionExecution {
  @override
  AsyncValue<ConversionResult?> build() => const AsyncValue.data(null);

  Future<void> execute({
    required String sourceWalletId,
    required String targetWalletId,
    required int amount,
    required String pair,
    required String lockId,
    required String pin,
  }) async {
    state = const AsyncValue.loading();
    state = await AsyncValue.guard(() async {
      final repo = ref.read(fxRateRepositoryProvider);
      return repo.executeConversion(
        sourceWalletId: sourceWalletId,
        targetWalletId: targetWalletId,
        amount: amount,
        pair: pair,
        lockId: lockId,
        pinHash: _hashPin(pin),
        idempotencyKey: uuid(),
      );
    });
  }
}
```

## Event Bus Integration
```dart
// Events consumed by FX feature
sealed class FXEvent {
  const FXEvent();
  const factory FXEvent.rateUpdated(String pair, double rate, double bezaRate) = RateUpdated;
  const factory FXEvent.conversionCompleted(ConversionResult result) = ConversionCompleted;
  const factory FXEvent.conversionFailed(String reason, String pair) = ConversionFailed;
  const factory FXEvent.rateLockExpired(String lockId) = RateLockExpired;
  const factory FXEvent.anomalyDetected(String pair, String description) = AnomalyDetected;
}

// Event handling in providers
ref.listen(eventBusProvider, (prev, next) {
  next.whenData((event) {
    switch (event) {
      case RateUpdated(:final pair, :final bezaRate):
        _updateRateCard(pair, bezaRate);
      case ConversionCompleted(:final result):
        _refreshBalances();
        _addToHistory(result);
      case AnomalyDetected(:final pair, :final description):
        _showWarning("تقلب غير طبيعي في سعر $pair");
    }
  });
});
```
