# Savings Flutter State Management

## Provider Architecture (Riverpod)

```
Provider Hierarchy:
  ┌────────────────────────────────────────────────────┐
  │              SavingsRepositoryProvider              │
  │  (Dio client + remote/local data sources)           │
  └────────────┬───────────────────────────────────────┘
               │
  ┌────────────▼───────────────────────────────────────┐
  │               GoalListProvider                      │
  │  State: AsyncValue<List<Goal>>                      │
  │  Methods:                                           │
  │    - Future<void> refreshGoals()                    │
  │    - Future<void> createGoal(CreateGoalRequest)     │
  │    - Future<void> cancelGoal(String goalId)         │
  │  Auto-refresh: on app foreground, pull-to-refresh   │
  └────────────┬───────────────────────────────────────┘
               │
  ┌────────────▼───────────────────────────────────────┐
  │             GoalDetailProvider (family)             │
  │  State: AsyncValue<GoalDetailState>                 │
  │  Families: goalId (String)                          │
  │  Fields: goal, transactions, profit, isLoading      │
  │  Methods:                                           │
  │    - Future<void> deposit(amount, pin)              │
  │    - Future<void> withdraw(amount, pin)             │
  │    - Future<void> refresh()                         │
  └────────────┬───────────────────────────────────────┘
               │
  ┌────────────▼───────────────────────────────────────┐
  │             CreateGoalProvider                      │
  │  State: CreateGoalFormState                         │
  │  Fields: name, targetAmount, targetDate,            │
  │          autoSaveEnabled, autoSaveAmount,            │
  │          autoSaveFrequency, roundUpEnabled,          │
  │          goalLockEnabled, isSubmitting, errors       │
  │  Methods:                                           │
  │    - void updateName(String)                        │
  │    - void updateTargetAmount(int)                   │
  │    - void updateTargetDate(DateTime)                │
  │    - void toggleAutoSave(bool)                      │
  │    - void updateAutoSaveAmount(int)                 │
  │    - void toggleRoundUp(bool)                       │
  │    - void toggleGoalLock(bool)                      │
  │    - Future<bool> submit(String pin)                │
  │    - int get suggestedDailyAmount (computed)        │
  └────────────┬───────────────────────────────────────┘
               │
  ┌────────────▼───────────────────────────────────────┐
  │              TeamGoalProvider (family)              │
  │  State: AsyncValue<TeamDetailState>                 │
  │  Families: teamId (String)                          │
  │  Fields: team, members, goal, isLoading             │
  │  Methods:                                           │
  │    - Future<void> createTeam(CreateTeamRequest)     │
  │    - Future<void> joinTeam(String inviteCode)       │
  │    - Future<void> leaveTeam()                       │
  │    - Future<void> removeMember(String userId)       │
  └─────────────────────────────────────────────────────┘

  ┌─────────────────────────────────────────────────────┐
  │            AutoSaveProvider (family)                │
  │  State: AsyncValue<AutoSaveConfig>                  │
  │  Families: goalId (String)                          │
  │  Methods:                                           │
  │    - Future<void> updateConfig(AutoSaveConfig)      │
  │    - Future<void> toggleAutoSave(bool)              │
  └─────────────────────────────────────────────────────┘

  ┌─────────────────────────────────────────────────────┐
  │            RoundUpProvider                          │
  │  State: AsyncValue<RoundUpConfig>                   │
  │  Fields: enabled, targetGoalId, minRoundAmount     │
  │  Methods:                                           │
  │    - Future<void> toggleRoundUp(bool)               │
  │    - Future<void> setTargetGoal(String goalId)      │
  └─────────────────────────────────────────────────────┘

  ┌─────────────────────────────────────────────────────┐
  │              ProfitProvider                         │
  │  State: AsyncValue<ProfitState>                     │
  │  Fields: totalProfit, distributions: List<Profit>   │
  │  Methods:                                           │
  │    - Future<void> refresh()                         │
  └─────────────────────────────────────────────────────┘
```

## State Classes

```dart
// GoalDetailState
class GoalDetailState {
  final Goal goal;
  final List<GoalTransaction> transactions;
  final List<ProfitDistribution> profits;
  final bool isLoading;
  final String? error;
  final bool isDepositing;
  final bool isWithdrawing;
}

// CreateGoalFormState
class CreateGoalFormState {
  final String name;
  final int targetAmount;
  final DateTime targetDate;
  final bool autoSaveEnabled;
  final int autoSaveAmount;
  final String autoSaveFrequency; // 'daily' | 'weekly'
  final bool roundUpEnabled;
  final bool goalLockEnabled;
  final bool isSubmitting;
  final Map<String, String?> fieldErrors;

  int get suggestedDailyAmount =>
      (targetAmount / targetDate.difference(DateTime.now()).inDays).ceil();

  bool get isValid =>
      name.isNotEmpty &&
      targetAmount >= 50000 &&
      targetDate.isAfter(DateTime.now().add(Duration(days: 7)));
}

// TeamDetailState
class TeamDetailState {
  final Team team;
  final List<TeamMember> members;
  final Goal? goal;
  final bool isLoading;
  final String? error;
}

// ProfitState
class ProfitState {
  final int totalProfit;
  final List<ProfitDistribution> distributions;
  final bool isLoading;
}
```

## Optimistic Updates

```dart
// Deposit optimistically updates UI before API responds
Future<void> deposit(int amount, String pin) async {
  state = state.copyWith(isDepositing: true);
  final oldGoal = state.goal;
  // Optimistic: add amount locally
  state = state.copyWith(
    goal: state.goal.copyWith(
      currentAmount: state.goal.currentAmount + amount,
    ),
  );
  try {
    await ref.read(savingsRepositoryProvider).deposit(state.goal.id, amount, pin);
    await refresh();
  } catch (e) {
    // Rollback on failure
    state = state.copyWith(goal: oldGoal, error: e.toString());
  }
}
```
