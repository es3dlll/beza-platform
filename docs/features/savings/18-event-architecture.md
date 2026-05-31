# Savings Event Architecture

## Event Catalog

| Event | Emitted By | Payload | Subscribers |
|-------|-----------|---------|-------------|
| `GoalCreated` | GoalService | goal_id, user_id, name, target_amount, target_date | SendGoalCreatedNotification, UpdateSavingsAnalytics |
| `GoalDeposited` | DepositToGoalAction | goal_id, user_id, amount, balance_after, transaction_id | SendDepositNotification, UpdateSavingsAnalytics, CheckGoalCompletion |
| `GoalWithdrawn` | WithdrawFromGoalAction | goal_id, user_id, amount, penalty, balance_after, transaction_id | SendWithdrawalNotification, UpdateSavingsAnalytics |
| `GoalCompleted` | GoalService | goal_id, user_id, name, total_saved, total_profit, completed_at | SendGoalCompletedNotification, UpdateSavingsAnalytics |
| `GoalCancelled` | CancelGoalAction | goal_id, user_id, reason, refund_amount | SendCancellationNotification, UpdateSavingsAnalytics |
| `GoalMilestoneReached` | GoalService / AutoSaveService / RoundUpService | goal_id, user_id, name, percentage, current_amount | NotifyGoalMilestone, UpdateSavingsAnalytics |
| `AutoSaveExecuted` | AutoSaveService | goal_id, user_id, amount, balance_after, transaction_id | SendDepositNotification, CheckGoalCompletion, CheckMilestone |
| `AutoSaveSkipped` | AutoSaveService | goal_id, user_id, amount, reason | TrackSkipCount, NotifyIfConsecutiveSkips |
| `RoundUpExecuted` | RoundUpService | goal_id, user_id, amount, source_transaction_id, original_amount, round_up_amount | SendRoundUpNotification, UpdateSavingsAnalytics |
| `ProfitDistributed` | ProfitShareService | goal_id, user_id, amount, period, pool_total, return_rate | SendProfitNotification, UpdateSavingsAnalytics |
| `TeamGoalCreated` | TeamGoalService | team_id, goal_id, created_by, invite_code, name | SendTeamCreatedNotification |
| `TeamMemberJoined` | TeamGoalService | team_id, goal_id, user_id, team_name | SendTeamJoinedNotification, UpdateTeamAnalytics |
| `TeamMemberLeft` | TeamGoalService | team_id, goal_id, user_id, reason, refund_amount | SendTeamMemberLeftNotification, UpdateTeamAnalytics |
| `TeamMilestoneReached` | TeamGoalService | team_id, goal_id, name, percentage, member_count | SendTeamMilestoneNotification |
| `GoalLockExpired` | GoalLockService | goal_id, user_id, name, current_amount | SendLockExpiredNotification |

## Event Payload Schema

```php
// GoalCreated
[
    'event' => 'savings.goal.created',
    'version' => 1,
    'timestamp' => '2026-05-29T10:00:00Z',
    'data' => [
        'goal_id' => 'goal_abc123',
        'user_id' => 42,
        'name' => 'لابتوب جديد',
        'target_amount' => 2500000,
        'target_date' => '2026-12-01',
        'currency' => 'SYP',
        'type' => 'individual',
        'auto_save_enabled' => true,
        'round_up_enabled' => false,
        'goal_locked' => true,
        'cfe_sub_account_id' => 'cfe_sub_789',
    ]
]

// GoalMilestoneReached
[
    'event' => 'savings.goal.milestone',
    'version' => 1,
    'timestamp' => '2026-07-30T10:00:00Z',
    'data' => [
        'goal_id' => 'goal_abc123',
        'user_id' => 42,
        'name' => 'لابتوب جديد',
        'milestone' => 50,
        'current_amount' => 1250000,
        'target_amount' => 2500000,
        'days_to_goal' => 124,
        'is_on_track' => true,
    ]
]

// ProfitDistributed
[
    'event' => 'savings.profit.distributed',
    'version' => 1,
    'timestamp' => '2026-06-01T00:00:00Z',
    'data' => [
        'goal_id' => 'goal_abc123',
        'user_id' => 42,
        'amount' => 1200,
        'period' => 'monthly',
        'period_start' => '2026-05-01',
        'period_end' => '2026-05-31',
        'pool_total' => 50000000,
        'pool_return' => 150000,
        'return_rate_pct' => 0.3,
        'weight' => 0.025,
    ]
]

// AutoSaveExecuted
[
    'event' => 'savings.autosave.executed',
    'version' => 1,
    'timestamp' => '2026-05-30T10:00:00Z',
    'data' => [
        'goal_id' => 'goal_abc123',
        'user_id' => 42,
        'amount' => 5000,
        'balance_before' => 1245000,
        'balance_after' => 1250000,
        'frequency' => 'daily',
        'transaction_id' => 'txn_auto_456',
        'cfe_reference' => 'cfe_ref_xyz',
    ]
]

// RoundUpExecuted
[
    'event' => 'savings.roundup.executed',
    'version' => 1,
    'timestamp' => '2026-05-29T14:30:00Z',
    'data' => [
        'goal_id' => 'goal_abc123',
        'user_id' => 42,
        'amount' => 500,
        'original_transaction_amount' => 23500,
        'rounded_amount' => 24000,
        'source_transaction_id' => 'txn_wallet_789',
        'transaction_id' => 'txn_roundup_101',
    ]
]
```

## Listener Implementations

```php
// SendGoalCreatedNotification
class SendGoalCreatedNotification
{
    public function handle(GoalCreated $event): void
    {
        $goal = $event->goal;
        $user = User::find($goal->user_id);

        $notification = new PushNotification(
            title: 'تم إنشاء هدف التوفير 🎯',
            body: "هدف «{$goal->name}» جاهز! ابدأ التوفير الآن لتحقيق {$goal->target_amount} ل.س",
            data: ['route' => "/savings/{$goal->id}", 'goal_id' => $goal->id],
            user: $user,
        );

        NotificationService::send($notification);
    }
}

// NotifyGoalMilestone
class NotifyGoalMilestone
{
    public function handle(GoalMilestoneReached $event): void
    {
        $goal = $event->goal;
        $user = User::find($goal->user_id);

        $milestoneMessages = [
            25 => 'ربع الطريق! استمر في التوفير 💪',
            50 => 'نصف الهدف! أنت في منتصف الطريق 🎉',
            75 => 'ثلاثة أرباع! الهدف أصبح قريباً جداً 🔥',
            100 => 'تهانينا! لقد حققت هدفك بالكامل 🎉🎉🎉',
        ];

        $notification = new PushNotification(
            title: "{$goal->name} — {$event->percentage}%",
            body: $milestoneMessages[$event->percentage] ?? 'تقدم رائع! استمر 👍',
            data: ['route' => "/savings/{$goal->id}", 'goal_id' => $goal->id],
            user: $user,
        );

        NotificationService::send($notification);

        if ($event->percentage >= 100) {
            // Send SMS for critical milestone
            SmsService::send(
                phone: $user->phone,
                message: "مبروك! لقد حققت هدف التوفير «{$goal->name}» في تطبيق Beza. يمكنك الآن سحب أموالك.",
            );
        }
    }
}

// SendProfitNotification
class SendProfitNotification
{
    public function handle(ProfitDistributed $event): void
    {
        $goal = SavingsGoal::find($event->goalId);
        $user = User::find($goal->user_id);

        $notification = new PushNotification(
            title: 'تم توزيع الأرباح 💰',
            body: "تم إضافة {$event->amount} ل.س أرباحاً إلى هدف «{$goal->name}» بنسبة {$event->returnRate}%",
            data: ['route' => "/savings/{$goal->id}", 'goal_id' => $goal->id],
            user: $user,
        );

        NotificationService::send($notification);
    }
}
```

## Queue Configuration
```php
// Queue mapping for savings events
'connections' => [
    'savings_high' => [                          // Immediate: deposits, withdrawals
        'driver' => 'redis',
        'queue' => 'savings_high',
        'retry_after' => 90,
    ],
    'savings_low' => [                           // Deferred: notifications, analytics
        'driver' => 'redis',
        'queue' => 'savings_low',
        'retry_after' => 300,
    ],
    'savings_bulk' => [                          // Batch: profit distribution
        'driver' => 'redis',
        'queue' => 'savings_bulk',
        'retry_after' => 600,
    ],
];
```
