# Cards Flutter State Management

## Provider Definitions (Riverpod)

### Card List Provider
```dart
@riverpod
class CardList extends _$CardList {
  @override
  AsyncValue<List<CardState>> build() {
    _watchCardUpdates();
    return const AsyncValue.loading();
  }

  Future<void> refresh() async {
    state = const AsyncValue.loading();
    state = await AsyncValue.guard(() => _fetchCards());
  }

  Future<CardState> createCard(CreateCardRequest request) async {
    final repo = ref.read(cardRepositoryProvider);
    return repo.createCard(request);
  }

  Future<void> freezeCard(int cardId) async {
    final repo = ref.read(cardRepositoryProvider);
    await repo.freezeCard(cardId);
    refresh();
  }

  Future<void> unfreezeCard(int cardId) async {
    final repo = ref.read(cardRepositoryProvider);
    await repo.unfreezeCard(cardId);
    refresh();
  }

  void _watchCardUpdates() {
    ref.onDispose(
      ref.listen(eventBusProvider, (_, next) {
        next.whenData((event) {
          if (event is CardFrozen || event is CardUnfrozen ||
              event is CardCreated || event is CardClosed) {
            refresh();
          }
        });
      }).close,
    );
  }
}
```

### Card Detail Provider
```dart
@riverpod
class CardDetail extends _$CardDetail {
  @override
  AsyncValue<CardDetailState> build(int cardId) {
    _loadCard(cardId);
    return const AsyncValue.loading();
  }

  Future<void> _loadCard(int cardId) async {
    state = const AsyncValue.loading();
    state = await AsyncValue.guard(() => _fetchCardDetail(cardId));
  }

  Future<void> updateLimits(int cardId, Map<String, int> limits) async {
    final repo = ref.read(cardRepositoryProvider);
    await repo.updateLimits(cardId, limits);
    _loadCard(cardId);
  }

  Future<void> changePin(int cardId, String currentPin, String newPin) async {
    final repo = ref.read(cardRepositoryProvider);
    await repo.changePin(cardId, currentPin, newPin);
  }
}

class CardDetailState {
  final Card card;
  final List<CardTransaction> transactions;
  final bool hasMoreTransactions;
  final bool panVisible;
  final bool cvvVisible;
  // ...
}
```

### One-Time Card Provider
```dart
@riverpod
class OneTimeCard extends _$OneTimeCard {
  OneTimeCardState build() => OneTimeCardState.initial();

  Future<OneTimeCardResult> generate(int amount, String currency) async {
    state = state.copyWith(isGenerating: true);
    try {
      final repo = ref.read(cardRepositoryProvider);
      final result = await repo.createOneTimeCard(amount, currency);
      state = state.copyWith(
        isGenerating: false,
        currentCard: result,
        generatedAt: DateTime.now(),
      );
      return result;
    } catch (e) {
      state = state.copyWith(isGenerating: false, error: e.toString());
      rethrow;
    }
  }

  void destroy() {
    state = OneTimeCardState.initial();
  }

  Duration get timeRemaining {
    if (state.generatedAt == null) return Duration.zero;
    final expiry = state.generatedAt!.add(const Duration(hours: 24));
    return expiry.difference(DateTime.now());
  }
}
```

### Limit Editor Provider
```dart
@riverpod
class LimitEditor extends _$LimitEditor {
  LimitEditorState build(Card card) {
    return LimitEditorState(
      categories: {
        'online': card.limits.online,
        'pos': card.limits.pos,
        'atm': card.limits.atm,
        'international': card.limits.international,
      },
      maxLimits: {
        'online': card.kycLimits.onlineMax,
        'pos': card.kycLimits.posMax,
        'atm': card.kycLimits.atmMax,
        'international': card.kycLimits.internationalMax,
      },
    );
  }

  void setCategoryLimit(String category, int value) {
    state = state.copyWith(categories: {
      ...state.categories,
      category: value,
    });
  }

  Future<void> save() async {
    final repo = ref.read(cardRepositoryProvider);
    await repo.updateLimits(card.id, state.categories);
  }
}
```
