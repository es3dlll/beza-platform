# Savings Flutter Screens

## Screen Inventory

| # | Screen | Route | Widget |
|---|--------|-------|--------|
| 1 | Savings Dashboard | `/savings` | `SavingsDashboardScreen` |
| 2 | Create Goal | `/savings/create` | `CreateGoalScreen` |
| 3 | Goal Detail | `/savings/{goalId}` | `GoalDetailScreen` |
| 4 | Goal Transactions | `/savings/{goalId}/transactions` | `GoalTransactionsScreen` |
| 5 | Auto-Save Config | `/savings/{goalId}/autosave` | `AutoSaveConfigScreen` |
| 6 | Round-Up Config | `/savings/roundup` | `RoundUpConfigScreen` |
| 7 | Team Goal Detail | `/savings/teams/{teamId}` | `TeamGoalDetailScreen` |
| 8 | Create Team | `/savings/teams/create` | `CreateTeamScreen` |
| 9 | Join Team | `/savings/teams/join` | `JoinTeamScreen` |
| 10 | Profit History | `/savings/profit` | `ProfitHistoryScreen` |
| 11 | Goal Completion Celebration | `/savings/{goalId}/celebrate` | `GoalCompletionCelebrationScreen` |
| 12 | Goal Settings | `/savings/{goalId}/settings` | `GoalSettingsScreen` |
| 13 | Goal Templates | `/savings/templates` | `GoalTemplatesScreen` |

## Key Screen Specifications

### SavingsDashboardScreen
```dart
class SavingsDashboardScreen extends ConsumerStatefulWidget {
  // State dependencies:
  // - GoalListProvider (goals, isLoading, error)
  // - AutoSaveProvider (config summary)
  // - ProfitProvider (total profit, last distribution)

  @override
  ConsumerState<SavingsDashboardScreen> createState() => _SavingsDashboardScreenState();
}

// Key behaviors:
// - Pull-to-refresh reloads all providers
// - Empty state: illustration + "ابدأ التوفير الآن" CTA
// - GoalCard tap → navigate to GoalDetailScreen
// - FAB → navigate to CreateGoalScreen
// - AutoSaveSummaryCard tap → navigate to AutoSaveConfigScreen
// - ProfitCard tap → navigate to ProfitHistoryScreen
// - TeamGoalCard tap → navigate to TeamGoalDetailScreen
```

### CreateGoalScreen
```dart
class CreateGoalScreen extends ConsumerStatefulWidget {
  // State: CreateGoalProvider
  //   - goalName, targetAmount, targetDate
  //   - autoSaveEnabled, autoSaveAmount, autoSaveFrequency
  //   - roundUpEnabled, goalLockEnabled
  //   - isSubmitting, validationErrors

  // Form fields:
  // - IconPicker (horizontal scroll of 8 icons)
  // - TextFormField (name, max 100 chars, Arabic keyboard)
  // - AmountInputFormField (custom, large text, "ل.س" suffix)
  // - DatePickerFormField (min: today + 7 days)
  // - AutoSaveToggle → expands to amount + frequency + time
  // - RoundUpToggle
  // - GoalLockToggle → expands to lock condition

  // Smart suggestion: auto-calculates daily amount based on target / days
  //   displayed as "التوفير اليومي المقترح: 13,889 ل.س/يوم"
}
```

### GoalDetailScreen
```dart
class GoalDetailScreen extends ConsumerStatefulWidget {
  // Params: goalId (String)
  // State: GoalDetailProvider
  //   - goal, transactions, profit
  //   - depositAmount, withdrawalAmount (form state)

  // Sections:
  // 1. Amount display (big number, centered)
  // 2. Progress bar (animated gradient)
  // 3. Quick actions row (Deposit, Withdraw, Share)
  // 4. Details section (target, saved, remaining, date, status)
  // 5. Auto-save section (config summary + edit link)
  // 6. Profit section (total profit, last distribution)
  // 7. Transactions list (last 5 + "view all")

  // Deposit: bottom sheet with amount input + PIN confirm
  // Withdraw: bottom sheet with amount selector + penalty warning (if locked)
}
```

### GoalCompletionCelebrationScreen
```dart
class GoalCompletionCelebrationScreen extends StatelessWidget {
  // Params: goal (Goal entity)
  // Full-screen celebration:
  // 1. Confetti animation (confetti_widget package)
  // 2. Large checkmark or trophy icon
  // 3. "مبروك! 🎉 لقد حققت هدفك"
  // 4. Goal summary (name, amount, time taken)
  // 5. Total profit earned on this goal
  // 6. "سحب الآن" CTA
  // 7. "إنشاء هدف جديد" secondary CTA
  // 8. "مشاركة الإنجاز" share button
}
```

## Bottom Sheet Specifications

### DepositSheet
```dart
showDepositSheet(BuildContext context, String goalId) {
  return showModalBottomSheet(
    context: context,
    builder: (ctx) => Padding(
      padding: EdgeInsets.all(24),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text("إيداع في الهدف", style: SavingsTypography.goalName),
          SizedBox(height: 16),
          AmountInputField(hint: "المبلغ"),
          SizedBox(height: 8),
          Text("الرصيد المتاح: 125,000 ل.س", style: SavingsTypography.bodyAr),
          SizedBox(height: 24),
          PinInputField(hint: "رمز الحماية"),
          SizedBox(height: 24),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton(
              onPressed: () => context.read<GoalDetailProvider>().deposit(),
              child: Text("تأكيد الإيداع"),
            ),
          ),
        ],
      ),
    ),
  );
}
```

### WithdrawSheet
```dart
showWithdrawSheet(BuildContext context, String goalId) {
  // Same pattern but with:
  // - Partial amount selector (25%, 50%, 75%, 100% quick picks)
  // - Early withdrawal penalty warning if goal is locked:
  //   "سيتم خصم 2% رسوم سحب مبكر (5,000 ل.س)"
  // - Profit settlement notice:
  //   "سيتم تسوية الأرباح المستحقة حتى تاريخه"
}
```
