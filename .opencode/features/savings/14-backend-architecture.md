# Savings Backend Architecture

## Module Structure (Laravel)
```
app/Modules/Savings/
├── Controllers/
│   ├── GoalController.php           # CRUD + deposit/withdraw
│   ├── AutoSaveController.php       # Auto-save config
│   ├── RoundUpController.php        # Round-up toggle/config
│   ├── ProfitShareController.php    # Profit history
│   ├── TeamGoalController.php       # Team CRUD
│   └── TeamMemberController.php     # Join/leave/remove
│
├── Actions/
│   ├── CreateGoalAction.php         # Create goal + CFE sub-wallet
│   ├── DepositToGoalAction.php      # Deposit orchestration
│   ├── WithdrawFromGoalAction.php   # Withdrawal orchestration
│   ├── ExecuteAutoSaveAction.php    # Single auto-save execution
│   ├── ExecuteRoundUpAction.php     # Single round-up execution
│   ├── CalculateProfitAction.php    # Periodic profit calculation
│   ├── DistributeProfitAction.php   # Profit distribution
│   ├── CompleteGoalAction.php       # Goal completion workflow
│   ├── CreateTeamAction.php         # Team creation + invite
│   ├── JoinTeamAction.php           # Team join via invite code
│   └── CancelGoalAction.php         # Goal cancellation
│
├── Services/
│   ├── GoalService.php              # Core goal operations
│   ├── AutoSaveService.php          # Auto-save scheduling + execution
│   ├── RoundUpService.php           # Transaction monitoring + round-up
│   ├── ProfitShareService.php       # Profit calculation + distribution
│   ├── TeamGoalService.php          # Team goal orchestration
│   ├── GoalLockService.php          # Lock/unlock logic + penalties
│   └── GoalProgressService.php      # Progress calculation + milestones
│
├── Repositories/
│   ├── GoalRepository.php           # Goal CRUD + queries
│   ├── GoalTransactionRepository.php # Transaction queries
│   ├── AutoSaveLogRepository.php    # Auto-save execution log
│   ├── ProfitDistributionRepository.php
│   ├── TeamRepository.php
│   └── TeamMemberRepository.php
│
├── Models/
│   ├── SavingsGoal.php              # Goal model
│   ├── SavingsTransaction.php       # Transaction model
│   ├── AutoSaveConfig.php           # Auto-save config model
│   ├── AutoSaveLog.php              # Auto-save execution log
│   ├── RoundUpConfig.php            # Round-up config model
│   ├── ProfitDistribution.php       # Profit distribution model
│   ├── SavingsTeam.php              # Team model
│   └── SavingsTeamMember.php        # Team member model
│
├── Policies/
│   ├── SavingsGoalPolicy.php        # Goal authorization
│   ├── SavingsTeamPolicy.php        # Team authorization
│   └── WithdrawalPolicy.php         # Withdrawal rules
│
├── Events/
│   ├── GoalCreated.php
│   ├── GoalDeposited.php
│   ├── GoalWithdrawn.php
│   ├── GoalCompleted.php
│   ├── GoalMilestoneReached.php
│   ├── AutoSaveExecuted.php
│   ├── RoundUpExecuted.php
│   ├── ProfitDistributed.php
│   ├── TeamGoalCreated.php
│   ├── TeamMemberJoined.php
│   └── TeamMilestoneReached.php
│
├── Jobs/
│   ├── ProcessAutoSaveJob.php       # Scheduled auto-save
│   ├── ProcessRoundUpJob.php        # Async round-up execution
│   ├── CalculateMonthlyProfit.php   # Monthly profit calc
│   ├── DistributeProfitJob.php      # Async profit distribution
│   ├── NotifyGoalMilestone.php      # Milestone notification
│   ├── CheckGoalCompletion.php      # Daily goal completion check
│   └── ProcessTeamAutoSave.php      # Team auto-save split
│
├── Listeners/
│   ├── SendGoalCreatedNotification.php
│   ├── SendDepositNotification.php
│   ├── SendMilestoneNotification.php
│   ├── SendProfitNotification.php
│   └── UpdateSavingsAnalytics.php
│
├── Rules/
│   ├── ValidGoalTargetAmount.php
│   ├── SufficientMainBalance.php
│   ├── ValidWithdrawalAmount.php
│   ├── ValidAutoSaveTime.php
│   └── ValidTeamSize.php
│
├── Enums/
│   ├── GoalStatus.php               # active, completed, cancelled
│   ├── GoalType.php                 # individual, team
│   ├── TransactionType.php          # deposit, withdrawal, profit, roundup
│   ├── AutoSaveFrequency.php        # daily, weekly
│   ├── TeamStatus.php               # active, completed, disbanded
│   └── ProfitPeriod.php             # monthly, quarterly
│
├── Exceptions/
│   ├── GoalNotFoundException.php
│   ├── GoalAlreadyCompletedException.php
│   ├── InsufficientMainBalanceException.php
│   ├── GoalLockedException.php
│   ├── EarlyWithdrawalPenaltyException.php
│   ├── TeamFullException.php
│   ├── InvalidInviteCodeException.php
│   └── TeamMemberAlreadyExistsException.php
│
├── Providers/
│   └── SavingsServiceProvider.php   # Module registration
│
├── Console/
│   └── Commands/
│       ├── ProcessScheduledAutoSaves.php    # Cron: every hour
│       ├── ExecuteMonthlyProfitShare.php    # Cron: 1st of month
│       └── CheckGoalCompletionStatus.php    # Cron: daily
│
└── routes/
    └── api.php                      # Route definitions
```

## Service Layer Detail

### GoalService
```php
class GoalService
{
    public function __construct(
        private GoalRepository $goalRepo,
        private GoalTransactionRepository $txnRepo,
        private GoalProgressService $progressService,
        private GoalLockService $lockService,
        private CfeService $cfe,
        private EventService $eventService,
    ) {}

    public function create(CreateGoalRequest $request, User $user): SavingsGoal
    {
        // 1. Validate user has main wallet
        $mainWallet = $this->cfe->getMainWallet($user->id);

        // 2. Create CFE sub-account for savings goal
        $cfeSubAccount = $this->cfe->createSubAccount(
            parentAccountId: $mainWallet->cfe_account_id,
            currency: Currency::SYP,
            type: 'savings',
            metadata: ['goal_name' => $request->name],
        );

        // 3. Persist goal record
        $goal = $this->goalRepo->create([
            'user_id' => $user->id,
            'name' => $request->name,
            'target_amount' => $request->targetAmount,
            'current_amount' => 0,
            'currency' => Currency::SYP,
            'type' => $request->type ?? GoalType::INDIVIDUAL,
            'auto_save_enabled' => $request->autoSaveEnabled ?? false,
            'auto_save_frequency' => $request->autoSaveFrequency,
            'auto_save_amount' => $request->autoSaveAmount,
            'round_up_enabled' => $request->roundUpEnabled ?? false,
            'status' => GoalStatus::ACTIVE,
            'target_date' => $request->targetDate,
            'cfe_sub_account_id' => $cfeSubAccount->id,
        ]);

        // 4. Create auto-save config if enabled
        if ($request->autoSaveEnabled) {
            AutoSaveConfig::createForGoal($goal, $request->autoSaveConfig);
        }

        // 5. Create round-up config if enabled
        if ($request->roundUpEnabled) {
            RoundUpConfig::createForGoal($goal);
        }

        // 6. Emit event
        $this->eventService->emit(new GoalCreated($goal));

        return $goal;
    }

    public function track(User $user): array
    {
        $goals = $this->goalRepo->findAllByUser($user->id);
        return array_map(fn($goal) => [
            'goal' => $goal,
            'progress' => $this->progressService->calculate($goal),
            'milestone' => $this->progressService->getCurrentMilestone($goal),
        ], $goals);
    }

    public function complete(SavingsGoal $goal): void
    {
        // 1. Verify target reached
        if ($goal->current_amount < $goal->target_amount) {
            throw new GoalNotCompletedException('Target amount not reached');
        }

        // 2. Check lock period
        $lockPeriod = $this->lockService->getRemainingLockPeriod($goal);
        if ($lockPeriod > 0) {
            $goal->status = GoalStatus::AWAITING_RELEASE;
            $goal->save();
            return;
        }

        // 3. Mark completed
        $goal->status = GoalStatus::COMPLETED;
        $goal->completed_at = now();
        $goal->save();

        // 4. Emit event
        $this->eventService->emit(new GoalCompleted($goal));

        // 5. Send celebration notification
        $this->eventService->emit(new GoalMilestoneReached($goal, 100));
    }
}
```

### AutoSaveService
```php
class AutoSaveService
{
    public function __construct(
        private GoalRepository $goalRepo,
        private AutoSaveLogRepository $logRepo,
        private GoalLockService $lockService,
        private CfeService $cfe,
        private EventService $eventService,
    ) {}

    public function processScheduled(): int
    {
        $processed = 0;
        $dueGoals = $this->goalRepo->findDueForAutoSave();

        foreach ($dueGoals as $goal) {
            try {
                $this->execute($goal);
                $processed++;
            } catch (\Throwable $e) {
                Log::error("AutoSave failed for goal {$goal->id}: {$e->getMessage()}");
                $this->logRepo->recordFailure($goal, $e->getMessage());
            }
        }

        return $processed;
    }

    public function execute(SavingsGoal $goal): AutoSaveLog
    {
        // 1. Check main wallet has sufficient balance
        $mainWallet = $this->cfe->getMainWallet($goal->user_id);
        $amount = $goal->auto_save_amount;

        $this->cfe->checkSufficientBalance($mainWallet->cfe_account_id, $amount);

        // 2. Debit main wallet
        $debitResult = $this->cfe->debit(
            accountId: $mainWallet->cfe_account_id,
            amount: $amount,
            reference: "autosave-{$goal->id}-" . uniqid(),
        );

        // 3. Credit savings sub-wallet
        $creditResult = $this->cfe->credit(
            accountId: $goal->cfe_sub_account_id,
            amount: $amount,
            reference: $debitResult->reference,
        );

        // 4. Record transaction
        $txn = $this->goalRepo->recordTransaction(
            goalId: $goal->id,
            type: TransactionType::DEPOSIT,
            amount: $amount,
            balanceBefore: $goal->current_amount,
            balanceAfter: $goal->current_amount + $amount,
            reference: "autosave::{$debitResult->reference}",
        );

        // 5. Update goal current amount
        $goal->current_amount += $amount;
        $goal->save();

        // 6. Log execution
        $log = $this->logRepo->create([
            'goal_id' => $goal->id,
            'amount' => $amount,
            'status' => 'completed',
            'reference' => $txn->id,
            'executed_at' => now(),
        ]);

        // 7. Check milestone
        $this->eventService->emit(new AutoSaveExecuted($goal, $amount));
        $this->checkMilestone($goal);

        return $log;
    }

    public function updateSchedule(SavingsGoal $goal, AutoSaveConfig $config): void
    {
        $goal->auto_save_enabled = $config->enabled;
        $goal->auto_save_frequency = $config->frequency;
        $goal->auto_save_amount = $config->amount;
        $goal->save();

        if ($config->enabled) {
            AutoSaveConfig::updateForGoal($goal, $config);
        }
    }
}
```

### RoundUpService
```php
class RoundUpService
{
    public function __construct(
        private GoalRepository $goalRepo,
        private GoalTransactionRepository $txnRepo,
        private CfeService $cfe,
        private EventService $eventService,
    ) {}

    public function monitorTransaction(WalletTransaction $transaction): void
    {
        // 1. Skip if transaction is savings-related
        if (in_array($transaction->type, ['savings_deposit', 'savings_withdrawal'])) {
            return;
        }

        // 2. Find user's active round-up goal
        $goal = $this->goalRepo->findPrimaryRoundUpGoal($transaction->sender_id);
        if (!$goal) {
            return;
        }

        // 3. Calculate round-up amount
        $originalAmount = $transaction->amount;
        $roundedAmount = ceil($originalAmount / 1000) * 1000;
        $roundupAmount = $roundedAmount - $originalAmount;

        if ($roundupAmount <= 0) {
            return; // Already at thousand boundary
        }

        // 4. Execute round-up transfer
        try {
            $this->executeRoundUp($goal, $roundupAmount, $transaction);
        } catch (\Throwable $e) {
            Log::warning("RoundUp failed for transaction {$transaction->id}: {$e->getMessage()}");
        }
    }

    public function executeRoundUp(SavingsGoal $goal, int $amount, WalletTransaction $sourceTxn): void
    {
        $mainWallet = $this->cfe->getMainWallet($goal->user_id);

        // Round-up amount must be ≤ remaining main wallet balance
        $balance = $this->cfe->getBalance($mainWallet->cfe_account_id);
        $roundupAmount = min($amount, $balance->available);

        if ($roundupAmount < 100) {
            return; // Skip very small amounts
        }

        // Debit main wallet
        $debitResult = $this->cfe->debit(
            accountId: $mainWallet->cfe_account_id,
            amount: $roundupAmount,
            reference: "roundup-{$sourceTxn->id}",
        );

        // Credit savings sub-wallet
        $this->cfe->credit(
            accountId: $goal->cfe_sub_account_id,
            amount: $roundupAmount,
            reference: $debitResult->reference,
        );

        // Record transaction
        $txn = $this->goalRepo->recordTransaction(
            goalId: $goal->id,
            type: TransactionType::ROUNDUP,
            amount: $roundupAmount,
            balanceBefore: $goal->current_amount,
            balanceAfter: $goal->current_amount + $roundupAmount,
            reference: "roundup::{$debitResult->reference}",
        );

        // Update goal
        $goal->current_amount += $roundupAmount;
        $goal->save();

        // Emit event
        $this->eventService->emit(new RoundUpExecuted($goal, $roundupAmount, $sourceTxn));
        $this->checkMilestone($goal);
    }

    public function toggle(User $user, string $goalId, bool $enabled): void
    {
        $config = RoundUpConfig::firstOrCreate(['user_id' => $user->id]);
        $config->enabled = $enabled;
        $config->goal_id = $enabled ? $goalId : null;
        $config->save();

        // Update goal
        $goal = $this->goalRepo->findById($goalId);
        $goal->round_up_enabled = $enabled;
        $goal->save();
    }
}
```

### ProfitShareService
```php
class ProfitShareService
{
    public function __construct(
        private GoalRepository $goalRepo,
        private ProfitDistributionRepository $profitRepo,
        private CfeService $cfe,
        private EventService $eventService,
    ) {}

    public function calculateMonthly(): ProfitCalculationResult
    {
        // 1. Get total pooled savings balance
        $activeGoals = $this->goalRepo->findAllActive();
        $poolTotal = $activeGoals->sum('current_amount');

        if ($poolTotal <= 0) {
            return new ProfitCalculationResult(0, 0, []);
        }

        // 2. Get pool return from CFE investment engine
        $poolReturn = $this->cfe->getPoolReturn(
            poolAmount: $poolTotal,
            period: 'monthly',
        );

        // 3. Profit pool = return - management fee
        $managementFee = (int) ($poolReturn * 0.10); // 10% management fee
        $profitPool = $poolReturn - $managementFee;

        if ($profitPool <= 0) {
            return new ProfitCalculationResult(0, 0, []);
        }

        // 4. Calculate proportional distribution
        $distributions = [];
        foreach ($activeGoals as $goal) {
            $weight = $goal->current_amount / $poolTotal;
            $goalProfit = (int) ($profitPool * $weight);

            // Weight by time held (days since goal creation)
            $daysHeld = now()->diffInDays($goal->created_at);
            $timeWeight = min($daysHeld / 30, 1.0); // Cap at 1 month weight
            $adjustedProfit = (int) ($goalProfit * $timeWeight);

            if ($adjustedProfit > 0) {
                $distributions[] = [
                    'goal_id' => $goal->id,
                    'user_id' => $goal->user_id,
                    'amount' => $adjustedProfit,
                    'weight' => $weight,
                ];
            }
        }

        return new ProfitCalculationResult($poolTotal, $profitPool, $distributions);
    }

    public function distribute(): int
    {
        $result = $this->calculateMonthly();
        $distributed = 0;

        foreach ($result->distributions as $dist) {
            try {
                // 1. Credit savings sub-wallet
                $this->cfe->credit(
                    accountId: $this->goalRepo->findById($dist['goal_id'])->cfe_sub_account_id,
                    amount: $dist['amount'],
                    reference: "profit-{$dist['goal_id']}-" . now()->format('Ym'),
                );

                // 2. Record profit distribution
                $this->profitRepo->create([
                    'goal_id' => $dist['goal_id'],
                    'user_id' => $dist['user_id'],
                    'amount' => $dist['amount'],
                    'period' => ProfitPeriod::MONTHLY,
                    'distributed_at' => now(),
                ]);

                // 3. Update goal current amount
                $goal = $this->goalRepo->findById($dist['goal_id']);
                $goal->current_amount += $dist['amount'];
                $goal->save();

                // 4. Emit event
                $this->eventService->emit(
                    new ProfitDistributed($goal, $dist['amount'])
                );

                $distributed++;
            } catch (\Throwable $e) {
                Log::error("Profit distribution failed for goal {$dist['goal_id']}: {$e->getMessage()}");
            }
        }

        return $distributed;
    }
}
```

### TeamGoalService
```php
class TeamGoalService
{
    public function __construct(
        private TeamRepository $teamRepo,
        private TeamMemberRepository $memberRepo,
        private GoalRepository $goalRepo,
        private GoalService $goalService,
        private CfeService $cfe,
        private EventService $eventService,
    ) {}

    public function create(CreateTeamRequest $request, User $creator): SavingsTeam
    {
        // 1. Create the underlying savings goal
        $goal = $this->goalService->create(
            new CreateGoalRequest(
                name: $request->name,
                targetAmount: $request->targetAmount,
                targetDate: $request->targetDate,
                type: GoalType::TEAM,
                autoSaveEnabled: false,
                roundUpEnabled: false,
            ),
            $creator,
        );

        // 2. Create team record
        $team = $this->teamRepo->create([
            'name' => $request->name,
            'goal_id' => $goal->id,
            'created_by' => $creator->id,
            'invite_code' => $this->generateInviteCode(),
            'status' => TeamStatus::ACTIVE,
        ]);

        // 3. Add creator as first member
        $this->memberRepo->create([
            'team_id' => $team->id,
            'user_id' => $creator->id,
            'contribution' => 0,
            'joined_at' => now(),
        ]);

        // 4. Emit event
        $this->eventService->emit(new TeamGoalCreated($team, $creator));

        return $team;
    }

    public function join(string $inviteCode, User $user): SavingsTeamMember
    {
        // 1. Find team by invite code
        $team = $this->teamRepo->findByInviteCode($inviteCode);
        if (!$team) {
            throw new InvalidInviteCodeException();
        }

        // 2. Check team not full (max 20)
        $memberCount = $this->memberRepo->countByTeam($team->id);
        if ($memberCount >= 20) {
            throw new TeamFullException();
        }

        // 3. Check not already member
        if ($this->memberRepo->exists($team->id, $user->id)) {
            throw new TeamMemberAlreadyExistsException();
        }

        // 4. Add member
        $member = $this->memberRepo->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'contribution' => 0,
            'joined_at' => now(),
        ]);

        // 5. Emit event
        $this->eventService->emit(new TeamMemberJoined($team, $user));

        return $member;
    }

    public function getContributionSummary(int $teamId): array
    {
        $members = $this->memberRepo->findByTeam($teamId);
        $goal = $this->goalRepo->findById(
            $this->teamRepo->findById($teamId)->goal_id
        );

        return [
            'team' => $this->teamRepo->findById($teamId),
            'goal' => $goal,
            'members' => array_map(fn($m) => [
                'user' => $m->user,
                'contribution' => $m->contribution,
                'contribution_pct' => $goal->current_amount > 0
                    ? round(($m->contribution / $goal->current_amount) * 100, 1)
                    : 0,
                'joined_at' => $m->joined_at,
            ], $members),
        ];
    }

    private function generateInviteCode(): string
    {
        $prefix = 'BEZA-SAVE-';
        $suffix = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        return $prefix . $suffix;
    }
}
```
