# Loyalty Flutter State Management

## Provider Definitions (Riverpod)

### LoyaltyPoints Provider
```dart
@riverpod
class LoyaltyPoints extends _$LoyaltyPoints {
  @override
  AsyncValue<PointsState> build() {
    _loadPoints();
    return const AsyncValue.loading();
  }

  Future<void> _loadPoints() async {
    state = const AsyncValue.loading();
    state = await AsyncValue.guard(() => _fetchPoints());
  }

  Future<PointsState> _fetchPoints() async {
    final repo = ref.read(loyaltyRepositoryProvider);
    final points = await repo.getPoints();
    final today = await repo.getTodayEarnings();
    final recent = await repo.getRecentActivity(10);
    return PointsState(
      balance: points.balance,
      sypValue: points.balance, // 1:1
      earnedToday: today,
      recentActivity: recent,
    );
  }

  void refresh() => _loadPoints();
}

class PointsState {
  final int balance;
  final int sypValue;
  final int earnedToday;
  final List<PointsTransaction> recentActivity;
  final bool isLoading;
  // ...
}
```

### Tier Provider
```dart
@riverpod
class Tier extends _$Tier {
  @override
  AsyncValue<TierState> build() {
    _loadTier();
    return const AsyncValue.loading();
  }

  Future<void> _loadTier() async {
    final repo = ref.read(loyaltyRepositoryProvider);
    state = await AsyncValue.guard(() async {
      final tierInfo = await repo.getTierInfo();
      final benefits = await repo.getTierBenefits(tierInfo.currentTier);
      final nextBenefits = tierInfo.nextTier != null
          ? await repo.getTierBenefits(tierInfo.nextTier)
          : null;

      return TierState(
        currentTier: tierInfo.currentTier,
        currentPoints: tierInfo.currentPoints,
        pointsRequired: tierInfo.pointsRequired,
        progress: tierInfo.progress,
        benefits: benefits,
        nextTier: tierInfo.nextTier,
        nextBenefits: nextBenefits,
      );
    });
  }
}

class TierState {
  final TierLevel currentTier;
  final int currentPoints;
  final int pointsRequired;
  final double progress; // 0.0 - 1.0
  final List<TierBenefit> benefits;
  final TierLevel? nextTier;
  final List<TierBenefit>? nextBenefits;
  // ...
}

enum TierLevel { bronze, silver, gold, platinum }
```

### RewardCatalog Provider
```dart
@riverpod
class RewardCatalog extends _$RewardCatalog {
  @override
  AsyncValue<CatalogState> build() {
    _loadCatalog();
    return const AsyncValue.loading();
  }

  Future<void> _loadCatalog() async {
    final repo = ref.read(loyaltyRepositoryProvider);
    state = await AsyncValue.guard(() async {
      final rewards = await repo.getAvailableRewards();
      final categories = rewards.map((r) => r.category).toSet().toList();
      return CatalogState(
        rewards: rewards,
        categories: categories,
        selectedCategory: categories.firstOrNull,
      );
    });
  }

  void selectCategory(String category) {
    state = AsyncValue.data(
      state.value!.copyWith(selectedCategory: category)
    );
  }
}

class CatalogState {
  final List<Reward> rewards;
  final List<String> categories;
  final String? selectedCategory;
  // ...
}
```

### Redemption Provider
```dart
@riverpod
class Redemption extends _$Redemption {
  @override
  RedemptionState build() => RedemptionState.initial();

  void selectReward(Reward reward) {
    state = state.copyWith(
      selectedReward: reward,
      step: RedemptionStep.confirm,
    );
  }

  Future<void> confirm(String pin) async {
    state = state.copyWith(isProcessing: true);
    final repo = ref.read(loyaltyRepositoryProvider);
    try {
      final result = await repo.redeem(
        rewardId: state.selectedReward!.id,
        pin: pin,
      );
      state = state.copyWith(
        isProcessing: false,
        step: RedemptionStep.success,
        result: result,
      );
      // Refresh points balance
      ref.read(loyaltyPointsProvider.notifier).refresh();
    } catch (e) {
      state = state.copyWith(
        isProcessing: false,
        error: e.toString(),
        step: RedemptionStep.error,
      );
    }
  }

  void reset() => state = RedemptionState.initial();
}

class RedemptionState {
  final Reward? selectedReward;
  final RedemptionStep step;
  final bool isProcessing;
  final String? error;
  final RedemptionResult? result;
  // ...
}

enum RedemptionStep { initial, confirm, processing, success, error }
```

## Event Bus Integration
```dart
// Event types consumed by Loyalty feature
sealed class LoyaltyEvent {
  factory LoyaltyEvent.pointsEarned(int amount, int newBalance) = PointsEarned;
  factory LoyaltyEvent.tierUpgraded(TierLevel newTier) = TierUpgraded;
  factory LoyaltyEvent.pointsRedeemed(int amount, String reward) = PointsRedeemed;
  factory LoyaltyEvent.pointsExpiring(int amount, DateTime expiryDate) = PointsExpiring;
}

ref.listen(eventBusProvider, (prev, next) {
  next.whenData((event) {
    switch (event) {
      case PointsEarned(:final amount):
        _showSnackBar("ربحت $amount نقطة!");
        ref.read(loyaltyPointsProvider.notifier).refresh();
      case TierUpgraded(:final newTier):
        _showTierCelebration(newTier);
        ref.read(tierProvider.notifier).refresh();
      case PointsExpiring(:final amount):
        _showExpiryWarning(amount);
    }
  });
});
```
