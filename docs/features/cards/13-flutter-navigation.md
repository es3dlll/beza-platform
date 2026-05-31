# Cards Flutter Navigation

## Route Configuration (GoRouter)

```dart
final router = GoRouter(
  initialLocation: '/cards',
  routes: [
    ShellRoute(
      builder: (context, state, child) => AppShell(child: child),
      routes: [
        GoRoute(
          path: '/cards',
          name: 'cardsHome',
          builder: (context, state) => const CardsHomeScreen(),
          routes: [
            GoRoute(
              path: 'create',
              name: 'createCard',
              builder: (context, state) => const CreateCardScreen(),
            ),
            GoRoute(
              path: ':cardId',
              name: 'cardDetail',
              builder: (context, state) => CardDetailScreen(
                cardId: int.parse(state.pathParameters['cardId']!),
              ),
              routes: [
                GoRoute(
                  path: 'limits',
                  name: 'cardLimits',
                  builder: (context, state) => CardLimitSettingsScreen(
                    cardId: int.parse(state.pathParameters['cardId']!),
                  ),
                ),
                GoRoute(
                  path: 'pin',
                  name: 'changePin',
                  builder: (context, state) => ChangePinScreen(
                    cardId: int.parse(state.pathParameters['cardId']!),
                  ),
                ),
                GoRoute(
                  path: 'transactions',
                  name: 'cardTransactions',
                  builder: (context, state) => CardTransactionListScreen(
                    cardId: int.parse(state.pathParameters['cardId']!),
                  ),
                  routes: [
                    GoRoute(
                      path: ':transactionId',
                      name: 'cardTransactionDetail',
                      builder: (context, state) => CardTransactionDetailScreen(
                        transactionId: state.pathParameters['transactionId']!,
                      ),
                    ),
                  ],
                ),
                GoRoute(
                  path: 'report-lost',
                  name: 'reportLostCard',
                  builder: (context, state) => ReportLostCardScreen(
                    cardId: int.parse(state.pathParameters['cardId']!),
                  ),
                ),
                GoRoute(
                  path: 'replace',
                  name: 'replaceCard',
                  builder: (context, state) => CardReplacementScreen(
                    cardId: int.parse(state.pathParameters['cardId']!),
                  ),
                ),
              ],
            ),
            GoRoute(
              path: 'one-time',
              name: 'oneTimeCard',
              builder: (context, state) => const OneTimeCardScreen(),
            ),
          ],
        ),
      ],
    ),
  ],
);
```

## Bottom Sheet Routes (Modal)
```dart
// Card details reveal (PAN + CVV) — biometric protected
GoRoute(
  path: 'card-details',
  pageBuilder: (context, state) => BottomSheetPage(
    child: CardDetailsRevealSheet(
      card: state.extra as Card,
    ),
  ),
);

// Confirmation dialogs
GoRoute(
  path: 'confirm-freeze',
  pageBuilder: (context, state) => BottomSheetPage(
    child: ConfirmFreezeSheet(
      card: state.extra as Card,
    ),
  ),
);

GoRoute(
  path: 'confirm-replace',
  pageBuilder: (context, state) => BottomSheetPage(
    child: ConfirmReplaceSheet(
      card: state.extra as Card,
    ),
  ),
);
```

## Deep Link Routes
```dart
// Direct card freeze from push notification
// beza://cards/freeze/{cardId}
GoRoute(
  path: '/cards/freeze/:cardId',
  builder: (context, state) => FreezeCardScreen(
    cardId: int.parse(state.pathParameters['cardId']!),
  ),
);

// Open transaction from notification
// beza://cards/transaction/{id}
GoRoute(
  path: '/cards/transaction/:id',
  builder: (context, state) => CardTransactionDetailScreen(
    transactionId: state.pathParameters['id']!,
  ),
);
```

## Navigation Triggers
| Action | Route | Auth Required |
|--------|-------|---------------|
| Create virtual card | /cards/create | KYC Level 2+ |
| View card detail | /cards/{id} | Biometric |
| View limits | /cards/{id}/limits | PIN |
| Change PIN | /cards/{id}/pin | Biometric + PIN |
| Report lost | /cards/{id}/report-lost | Biometric |
| Request replacement | /cards/{id}/replace | Biometric |
| One-time card | /cards/one-time | KYC Level 2+ |
