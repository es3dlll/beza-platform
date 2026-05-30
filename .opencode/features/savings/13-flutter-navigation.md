# Savings Flutter Navigation

## Route Definitions (GoRouter)

```dart
class SavingsRoutes {
  static const dashboard = '/savings';
  static const createGoal = '/savings/create';
  static const goalDetail = '/savings/:goalId';
  static const goalTransactions = '/savings/:goalId/transactions';
  static const autoSaveConfig = '/savings/:goalId/autosave';
  static const roundUpConfig = '/savings/roundup';
  static const teamDetail = '/savings/teams/:teamId';
  static const createTeam = '/savings/teams/create';
  static const joinTeam = '/savings/teams/join';
  static const profitHistory = '/savings/profit';
  static const goalCelebration = '/savings/:goalId/celebrate';
  static const goalSettings = '/savings/:goalId/settings';
  static const goalTemplates = '/savings/templates';
}

List<RouteBase> savingsRoutes = [
  GoRoute(
    path: '/savings',
    name: 'savings.dashboard',
    builder: (context, state) => const SavingsDashboardScreen(),
    routes: [
      GoRoute(
        path: 'create',
        name: 'savings.createGoal',
        builder: (context, state) => const CreateGoalScreen(),
      ),
      GoRoute(
        path: ':goalId',
        name: 'savings.goalDetail',
        builder: (context, state) => GoalDetailScreen(
          goalId: state.pathParameters['goalId']!,
        ),
        routes: [
          GoRoute(
            path: 'transactions',
            name: 'savings.goalTransactions',
            builder: (context, state) => GoalTransactionsScreen(
              goalId: state.pathParameters['goalId']!,
            ),
          ),
          GoRoute(
            path: 'autosave',
            name: 'savings.autoSaveConfig',
            builder: (context, state) => AutoSaveConfigScreen(
              goalId: state.pathParameters['goalId']!,
            ),
          ),
          GoRoute(
            path: 'settings',
            name: 'savings.goalSettings',
            builder: (context, state) => GoalSettingsScreen(
              goalId: state.pathParameters['goalId']!,
            ),
          ),
          GoRoute(
            path: 'celebrate',
            name: 'savings.goalCelebration',
            builder: (context, state) => GoalCompletionCelebrationScreen(
              goalId: state.pathParameters['goalId']!,
            ),
          ),
        ],
      ),
      GoRoute(
        path: 'roundup',
        name: 'savings.roundUpConfig',
        builder: (context, state) => const RoundUpConfigScreen(),
      ),
      GoRoute(
        path: 'teams/create',
        name: 'savings.createTeam',
        builder: (context, state) => const CreateTeamScreen(),
      ),
      GoRoute(
        path: 'teams/join',
        name: 'savings.joinTeam',
        builder: (context, state) => const JoinTeamScreen(),
      ),
      GoRoute(
        path: 'teams/:teamId',
        name: 'savings.teamDetail',
        builder: (context, state) => TeamGoalDetailScreen(
          teamId: state.pathParameters['teamId']!,
        ),
      ),
      GoRoute(
        path: 'profit',
        name: 'savings.profitHistory',
        builder: (context, state) => const ProfitHistoryScreen(),
      ),
      GoRoute(
        path: 'templates',
        name: 'savings.goalTemplates',
        builder: (context, state) => const GoalTemplatesScreen(),
      ),
    ],
  ),
];
```

## Navigation Actions

```dart
// From home screen → savings dashboard
context.pushNamed('savings.dashboard');

// From dashboard → create goal
context.pushNamed('savings.createGoal');

// From goal card tap → goal detail
context.pushNamed('savings.goalDetail', pathParameters: {'goalId': goal.id});

// From goal detail → transactions
context.pushNamed('savings.goalTransactions', pathParameters: {'goalId': goal.id});

// From goal detail → auto-save config
context.pushNamed('savings.autoSaveConfig', pathParameters: {'goalId': goal.id});

// From goal detail → celebrate (on completion)
context.pushReplacementNamed('savings.goalCelebration', pathParameters: {'goalId': goal.id});

// From dashboard → create team
context.pushNamed('savings.createTeam');

// From dashboard → join team
context.pushNamed('savings.joinTeam');

// From dashboard → round-up config
context.pushNamed('savings.roundUpConfig');

// From dashboard → profit history
context.pushNamed('savings.profitHistory');

// Deep link: /savings/teams/join?code=SAVE-FAMILY-42
// → JoinTeamScreen with pre-filled invite code
```

## Bottom Tab Integration

```dart
// Savings is accessible from:
// 1. Bottom navigation tab "المزيد" → Savings card
// 2. Home screen savings summary card (quick glance)
// 3. Push notification deep links (goal milestone, round-up)
// 4. USSD: *123# → 5 (Savings) → list goals → select → check

// Tab bar item (phase 1: under "المزيد" tab)
// Phase 3+: Bottom tab "التوفير" (between "أرسل" and "المزيد")
```

## Deep Linking

```dart
// Deep link patterns:
beza://savings                                        → Dashboard
beza://savings/create                                 → Create Goal
beza://savings/goal/{goalId}                           → Goal Detail
beza://savings/teams/join?code={inviteCode}            → Join Team
beza://savings/teams/{teamId}                          → Team Detail

// Push notification deep links:
// "هدفك لابتوب جديد حقق 50%!" → tap → goal/{goalId}
// "تم توزيع الأرباح" → tap → /savings/profit
// "تمت دعوتك للانضمام إلى فريق" → tap → teams/join?code=XYZ
```
