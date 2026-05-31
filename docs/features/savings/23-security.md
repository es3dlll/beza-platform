# Savings Security

## Authentication & Authorization

### Goal Access Control
```php
// Policy: Only goal owner (or team members for team goals) can access
class SavingsGoalPolicy
{
    public function view(User $user, SavingsGoal $goal): bool
    {
        if ($goal->user_id === $user->id) return true;
        if ($goal->type === 'team') {
            return SavingsTeamMember::where('team_id', $goal->team->id)
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->exists();
        }
        return false;
    }

    public function deposit(User $user, SavingsGoal $goal): bool
    {
        return $this->view($user, $goal);
    }

    public function withdraw(User $user, SavingsGoal $goal): bool
    {
        // Individual: only owner
        if ($goal->type === 'individual') {
            return $goal->user_id === $user->id;
        }
        // Team: only team members can withdraw their own contribution
        return SavingsTeamMember::where('team_id', $goal->team->id)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->exists();
    }

    public function delete(User $user, SavingsGoal $goal): bool
    {
        // Only creator can cancel
        return $goal->user_id === $user->id;
    }
}

class SavingsTeamPolicy
{
    public function view(User $user, SavingsTeam $team): bool
    {
        return $team->members()->where('user_id', $user->id)->exists();
    }

    public function update(User $user, SavingsTeam $team): bool
    {
        return $team->created_by === $user->id;
    }

    public function removeMember(User $user, SavingsTeam $team): bool
    {
        return $team->created_by === $user->id ||
               $team->members()->where('user_id', $user->id)
                    ->where('role', 'admin')->exists();
    }
}
```

### Sensitive Actions (Require PIN)
```php
// Every financial operation requires PIN verification
'transactions requiring pin' => [
    'savings:deposit',
    'savings:withdraw',
    'savings:autosave:enable',
    'savings:autosave:update',
    'savings:roundup:toggle',
    'savings:team:create',
    'savings:team:join',
    'savings:goal:cancel',
];
```

## Fraud Prevention Rules for Savings

```
Rule 1: Deposit Velocity — Max 10 deposits to the same goal in 1 hour
Rule 2: Withdrawal Velocity — Max 3 withdrawals from the same goal in 24 hours
Rule 3: Round-Up Manipulation — Cannot create artificial small transactions to trigger round-ups
    → Source transaction must be >= min_round_amount * 3
Rule 4: Team Hopping — Max join 3 teams in 7 days
Rule 5: Goal Flooding — Max create 10 goals in 24 hours
Rule 6: Auto-Save Abuse — Cannot change auto-save amount more than 3x in 7 days
Rule 7: Invite Code Brute Force — Max 10 failed join attempts in 1 hour → IP block 1h
Rule 8: Round-Up Daily Cap — Cannot exceed daily_max (50,000 SYP) in round-ups
```

## Data Privacy

```php
// Goal visibility: Private by default
// Team goal: Visible to team members only

// Personal data protection:
class SavingsGoal extends Model
{
    protected $hidden = [
        'cfe_sub_account_id',         // Internal CFE reference, never expose
        'metadata',                   // Flexible metadata, may contain PII
    ];

    // Encryption for sensitive goal metadata
    protected function metadata(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? decrypt($value) : null,
            set: fn ($value) => $value ? encrypt($value) : null,
        );
    }

    // Audit log: every financial operation recorded immutably
    // savings_transactions: never updated, only inserted
}
```

## Security Headers for Savings API
```
# All savings endpoints require:
Authorization: Bearer {token}
Idempotency-Key: {uuid}      # For deposit/withdraw/autosave
X-Device-Id: {device_id}     # Device binding
X-Device-Fingerprint: {hash} # Additional device verification
```

## Rate Limiting
```php
// API rate limits specific to savings endpoints
Route::middleware(['throttle:60,1'])->group(function () {          // 60 req/min
    Route::get('/savings/goals', ...);
    Route::get('/savings/goals/{id}', ...);
    Route::get('/savings/goals/{id}/progress', ...);
});

Route::middleware(['throttle:20,1'])->group(function () {          // 20 req/min
    Route::post('/savings/goals', ...);
    Route::post('/savings/goals/{id}/deposit', ...);
    Route::post('/savings/goals/{id}/withdraw', ...);
    Route::put('/savings/goals/{id}/autosave', ...);
    Route::post('/savings/roundup/toggle', ...);
});

Route::middleware(['throttle:10,1'])->group(function () {          // 10 req/min
    Route::post('/savings/teams', ...);
    Route::post('/savings/teams/{id}/join', ...);
});
```
