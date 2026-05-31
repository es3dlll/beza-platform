# Savings Flutter Architecture

## Architecture Pattern
```
Feature-first modular architecture with Clean Architecture layers:

lib/
├── core/                              # Shared across features
│   ├── api/                           # Dio client, interceptors
│   ├── design/                        # Design tokens, shared widgets
│   ├── utils/                         # Formatters (currency, date)
│   └── services/                      # Biometrics, notifications
│
├── features/
│   └── savings/
│       ├── data/
│       │   ├── datasources/
│       │   │   ├── SavingsRemoteDataSource.dart   # API calls
│       │   │   └── SavingsLocalDataSource.dart    # SQLite cache
│       │   ├── models/
│       │   │   ├── GoalModel.dart                 # JSON serializable
│       │   │   ├── GoalTransactionModel.dart
│       │   │   ├── AutoSaveConfigModel.dart
│       │   │   ├── RoundUpConfigModel.dart
│       │   │   ├── ProfitDistributionModel.dart
│       │   │   ├── TeamModel.dart
│       │   │   └── TeamMemberModel.dart
│       │   └── repositories/
│       │       └── SavingsRepositoryImpl.dart
│       │
│       ├── domain/
│       │   ├── entities/
│       │   │   ├── Goal.dart
│       │   │   ├── GoalTransaction.dart
│       │   │   ├── AutoSaveConfig.dart
│       │   │   ├── RoundUpConfig.dart
│       │   │   ├── ProfitDistribution.dart
│       │   │   ├── Team.dart
│       │   │   └── TeamMember.dart
│       │   ├── repositories/
│       │   │   └── SavingsRepository.dart          # Abstract interface
│       │   └── usecases/
│       │       ├── CreateGoalUseCase.dart
│       │       ├── GetGoalsUseCase.dart
│       │       ├── DepositToGoalUseCase.dart
│       │       ├── WithdrawFromGoalUseCase.dart
│       │       ├── UpdateAutoSaveUseCase.dart
│       │       ├── ToggleRoundUpUseCase.dart
│       │       ├── GetGoalProgressUseCase.dart
│       │       ├── GetProfitHistoryUseCase.dart
│       │       ├── CreateTeamUseCase.dart
│       │       ├── JoinTeamUseCase.dart
│       │       └── GetTeamDetailsUseCase.dart
│       │
│       └── presentation/
│           ├── providers/
│           │   ├── GoalListProvider.dart
│           │   ├── GoalDetailProvider.dart
│           │   ├── CreateGoalProvider.dart
│           │   ├── AutoSaveProvider.dart
│           │   ├── RoundUpProvider.dart
│           │   ├── TeamGoalProvider.dart
│           │   └── ProfitProvider.dart
│           ├── screens/
│           │   ├── SavingsDashboardScreen.dart
│           │   ├── CreateGoalScreen.dart
│           │   ├── GoalDetailScreen.dart
│           │   ├── GoalTransactionsScreen.dart
│           │   ├── AutoSaveConfigScreen.dart
│           │   ├── RoundUpConfigScreen.dart
│           │   ├── TeamGoalDetailScreen.dart
│           │   ├── CreateTeamScreen.dart
│           │   ├── JoinTeamScreen.dart
│           │   ├── ProfitHistoryScreen.dart
│           │   └── GoalCompletionCelebrationScreen.dart
│           └── widgets/
│               ├── GoalCard.dart
│               ├── GoalProgressBar.dart
│               ├── GoalIconPicker.dart
│               ├── AutoSaveBadge.dart
│               ├── RoundUpBadge.dart
│               ├── TransactionListItem.dart
│               ├── TeamMemberRow.dart
│               ├── ProfitCard.dart
│               ├── MilestoneCelebration.dart
│               ├── GoalLockBadge.dart
│               └── SavingsSummaryCard.dart
│
├── app.dart
└── main.dart
```

## Widget Tree (Savings Dashboard)
```
SavingsDashboardScreen
├── AppBar (title: "التوفير", actions: [notifications])
├── RefreshIndicator
│   └── CustomScrollView
│       ├── SliverToBoxAdapter
│       │   └── SavingsSummaryCard (total saved, total profit)
│       ├── SliverList
│       │   └── GoalCard (per goal)
│       │       ├── GoalIconPicker.icon
│       │       ├── Text (goal name)
│       │       ├── GoalProgressBar
│       │       ├── Text (amount, percentage, time remaining)
│       │       ├── AutoSaveBadge (if enabled)
│       │       ├── RoundUpBadge (if enabled)
│       │       └── GoalLockBadge (if locked)
│       ├── SliverToBoxAdapter
│       │   └── AutoSaveSummaryCard
│       ├── SliverToBoxAdapter
│       │   └── ProfitCard (total profit, last distribution)
│       └── SliverToBoxAdapter
│           └── TeamGoalsSection (team goal cards)
└── FloatingActionButton (create goal)
```
