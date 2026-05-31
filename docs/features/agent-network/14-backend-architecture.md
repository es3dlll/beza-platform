# Agent Network Backend Architecture

## Module Structure (Laravel)
```
app/Modules/Agent/
├── Controllers/
│   ├── AgentController.php           # Registration, profile, status
│   ├── CashInController.php          # Cash-in transactions
│   ├── CashOutController.php         # Cash-out transactions
│   ├── FloatController.php           # Float management (top-up, transfer)
│   ├── TransactionController.php     # Transaction history, export
│   ├── CommissionController.php       # Commission tracking, settlement
│   └── AgentAuthController.php       # Login, PIN management
│
├── Actions/
│   ├── RegisterAgentAction.php        # Full registration orchestration
│   ├── ApproveAgentAction.php         # KYC approval with background check
│   ├── ActivateAgentAction.php        # Device assignment, float initial
│   ├── ExecuteCashInAction.php        # Cash-in orchestration
│   ├── ExecuteCashOutAction.php       # Cash-out orchestration
│   ├── TopUpFloatAction.php           # Float top-up (wallet/cash)
│   ├── TransferFloatAction.php        # Agent-to-agent float transfer
│   ├── CalculateCommissionAction.php  # Per-transaction commission
│   ├── SettleCommissionAction.php     # T+1 batch settlement
│   ├── VerifyCustomerAction.php       # SMS code verification
│   └── ReconcileFloatAction.php       # EOD float reconciliation
│
├── Services/
│   ├── AgentService.php               # Core agent operations
│   ├── CommissionService.php          # Commission calculation engine
│   ├── FloatService.php               # Float balance management
│   ├── CashInService.php              # Cash-in business logic
│   ├── CashOutService.php             # Cash-out business logic
│   ├── CustomerVerificationService.php # SMS/USSD verification
│   ├── LimitService.php               # Agent tier-based limits
│   ├── AgentEventService.php          # Event emission
│   ├── OfflineSyncService.php         # Offline transaction processing
│   └── AgentDistanceService.php       # Location-based queries (spatial)
│
├── Repositories/
│   ├── AgentRepository.php            # Agent CRUD + spatial queries
│   ├── AgentTransactionRepository.php # Transaction CRUD + pagination
│   ├── AgentFloatRepository.php       # Float balance + history
│   ├── AgentCommissionRepository.php  # Commission accruals + settlements
│   └── AgentDeviceRepository.php      # POS device binding
│
├── Models/
│   ├── Agent.php                      # Agent model
│   ├── AgentTransaction.php           # Agent transaction model
│   ├── AgentFloat.php                 # Float balance model
│   ├── AgentFloatFunding.php          # Float funding record
│   ├── AgentCommission.php            # Commission record
│   ├── AgentCommissionSettlement.php  # Settlement batch
│   ├── AgentDevice.php                # POS device binding
│   └── AgentTier.php                  # Tier configuration
│
├── Policies/
│   ├── AgentPolicy.php                # Agent authorization
│   ├── CashInPolicy.php               # Cash-in authorization
│   └── CashOutPolicy.php              # Cash-out authorization
│
├── Events/
│   ├── AgentRegistered.php
│   ├── AgentApproved.php
│   ├── AgentSuspended.php
│   ├── AgentCashInCompleted.php
│   ├── AgentCashOutCompleted.php
│   ├── AgentFloatLow.php
│   ├── AgentFloatCritical.php
│   ├── CommissionEarned.php
│   ├── CommissionSettled.php
│   └── AgentOfflineTransactionSynced.php
│
├── Jobs/
│   ├── ProcessAgentKycJob.php         # Async KYC verification
│   ├── ProcessCashInJob.php           # Async cash-in (from offline queue)
│   ├── ProcessCashOutJob.php          # Async cash-out (from offline queue)
│   ├── SettleCommissionsJob.php       # T+1 commission settlement batch
│   ├── ReconcileAgentFloatsJob.php    # EOD float reconciliation
│   ├── SendFloatAlertJob.php          # Low float SMS notification
│   └── SyncAgentAnalyticsJob.php      # Analytics sync
│
├── Listeners/
│   ├── SendAgentApprovedNotification.php
│   ├── SendCashInNotification.php
│   ├── SendCashOutNotification.php
│   ├── UpdateAgentPerformanceStats.php
│   └── FlagSuspiciousAgentActivity.php
│
├── Rules/
│   ├── ValidAgentAmount.php
│   ├── SufficientAgentFloat.php
│   ├── AgentWithinDailyLimit.php
│   └── ValidAgentPhoneNumber.php
│
├── Enums/
│   ├── AgentStatus.php                # pending, active, suspended, terminated
│   ├── AgentTier.php                  # bronze, silver, gold, platinum
│   ├── AgentTransactionType.php       # cash_in, cash_out, float_funding, commission
│   ├── AgentTransactionStatus.php     # pending, completed, failed, reversed
│   └── FloatFundingSource.php         # wallet, cash, agent_to_agent
│
├── Exceptions/
│   ├── InsufficientFloatException.php
│   ├── DailyLimitExceededException.php
│   ├── AgentNotFoundException.php
│   ├── CustomerNotFoundException.php
│   ├── InvalidVerificationCodeException.php
│   └── AgentSuspendedException.php
│
├── Commands/
│   ├── SettleCommissions.php          # artisan agent:settle-commissions
│   ├── ReconcileFloats.php            # artisan agent:reconcile-floats
│   └── CheckLowFloats.php             # artisan agent:check-low-floats
│
├── Providers/
│   └── AgentServiceProvider.php       # Module registration
│
└── routes/
    └── api.php                        # Route definitions
```

## Service Layer Detail

### AgentService
```php
class AgentService
{
    public function __construct(
        private AgentRepository $agentRepo,
        private AgentDeviceRepository $deviceRepo,
        private FloatService $floatService,
        private CommissionService $commissionService,
        private LimitService $limitService,
        private CustomerVerificationService $verificationService,
        private AgentEventService $eventService,
    ) {}

    public function register(array $data): Agent
    {
        // Create agent record with status 'pending'
        // Upload documents to storage
        // Dispatch ProcessAgentKycJob
        // Return agent with temporary code
    }

    public function approve(int $agentId, int $approvedBy): Agent
    {
        // Verify documents complete
        // Check no existing agent within 500m
        // Set status to 'active'
        // Send welcome SMS with POS instructions
        // Emit AgentApproved event
    }

    public function getByLocation(float $lat, float $lng, float $radiusKm): Collection
    {
        // Spatial query: find active agents within radius, ordered by distance
    }

    public function getPerformanceStats(int $agentId): array
    {
        // Today's volume, this month's volume, rank, uptime %
    }

    public function updateTier(int $agentId): AgentTier
    {
        // Evaluate agent performance against tier criteria
        // Auto-upgrade if thresholds met
        // Notify agent of tier change
    }
}
```

### FloatService
```php
class FloatService
{
    public function getBalance(int $agentId): Money
    {
        return $this->floatRepo->getCurrentBalance($agentId);
    }

    public function canDebit(int $agentId, Money $amount): bool
    {
        $balance = $this->getBalance($agentId);
        return $balance->amount >= $amount->amount;
    }

    public function debit(int $agentId, Money $amount, string $reason): AgentFloat
    {
        if (!$this->canDebit($agentId, $amount)) {
            throw new InsufficientFloatException();
        }
        return $this->floatRepo->createMovement($agentId, -$amount->amount, $reason);
    }

    public function credit(int $agentId, Money $amount, string $reason): AgentFloat
    {
        return $this->floatRepo->createMovement($agentId, $amount->amount, $reason);
    }

    public function topUp(int $agentId, Money $amount, FloatFundingSource $source): AgentFloatFunding
    {
        // Create funding record
        // If from wallet: debit wallet, credit float
        // If cash: create pending funding (hub verifies cash later)
        // If agent-to-agent: debit source agent, credit target agent
        // Check tier max balance after top-up
    }
}
```

### CommissionService
```php
class CommissionService
{
    public function calculateCashInCommission(int $amount, AgentTier $tier): Money
    {
        $rates = [
            AgentTier::BRONZE => 0.003,  // 0.3%
            AgentTier::SILVER => 0.004,  // 0.4%
            AgentTier::GOLD => 0.005,     // 0.5%
            AgentTier::PLATINUM => 0.006, // 0.6%
        ];
        $commission = (int) ($amount * $rates[$tier]);
        return new Money(max($commission, 100), Currency::SYP); // Min 100 SYP
    }

    public function calculateCashOutCommission(int $amount, AgentTier $tier): Money
    {
        $rates = [
            AgentTier::BRONZE => 0.005,  // 0.5%
            AgentTier::SILVER => 0.006,  // 0.6%
            AgentTier::GOLD => 0.0075,   // 0.75%
            AgentTier::PLATINUM => 0.01, // 1.0%
        ];
        $commission = (int) ($amount * $rates[$tier]);
        return new Money(max($commission, 200), Currency::SYP); // Min 200 SYP
    }

    public function accrueCommission(int $agentId, Money $amount, string $transactionRef): AgentCommission
    {
        // Record commission accrual
        // Update agent's pending commission balance
        // Emit CommissionEarned event
    }

    public function settleDaily(): AgentCommissionSettlement
    {
        // Query all unsettled commissions from yesterday
        // Create settlement batch
        // Credit each agent's Beza wallet with commission amount
        // Mark commissions as settled
        // Emit CommissionSettled event for each
    }
}
```

## API Endpoints (Route Definitions)

```php
// Agent Module Routes (prefix: /api/v1/agent)

// Public routes (no auth)
Route::post('/register', [AgentController::class, 'register']);
Route::post('/login', [AgentAuthController::class, 'login']);

// Authenticated agent routes
Route::middleware(['auth:agent', 'agent.active'])->group(function () {
    // Profile & auth
    Route::get('/profile', [AgentController::class, 'profile']);
    Route::put('/profile', [AgentController::class, 'updateProfile']);
    Route::post('/change-pin', [AgentAuthController::class, 'changePin']);

    // Cash-in
    Route::post('/verify-customer', [CashInController::class, 'verifyCustomer']);
    Route::post('/cash-in', [CashInController::class, 'execute']);

    // Cash-out
    Route::post('/cash-out', [CashOutController::class, 'execute']);

    // Float
    Route::get('/float', [FloatController::class, 'balance']);
    Route::get('/float/history', [FloatController::class, 'history']);
    Route::post('/float/top-up', [FloatController::class, 'topUp']);
    Route::post('/float/transfer', [FloatController::class, 'transfer']);

    // Transactions
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::get('/transactions/{id}', [TransactionController::class, 'show']);
    Route::get('/transactions/export', [TransactionController::class, 'export']);

    // Commissions
    Route::get('/commissions', [CommissionController::class, 'index']);
    Route::get('/commissions/summary', [CommissionController::class, 'summary']);
    Route::get('/commissions/settlements', [CommissionController::class, 'settlements']);

    // Offline sync
    Route::post('/sync', [TransactionController::class, 'syncBatch']);
    Route::get('/sync/status', [TransactionController::class, 'syncStatus']);
});

// Admin routes
Route::middleware(['auth:admin'])->prefix('/admin')->group(function () {
    Route::get('/agents', [AgentController::class, 'list']);
    Route::post('/agents/{id}/approve', [AgentController::class, 'approve']);
    Route::post('/agents/{id}/suspend', [AgentController::class, 'suspend']);
    Route::post('/agents/{id}/terminate', [AgentController::class, 'terminate']);
    Route::get('/agents/pending', [AgentController::class, 'pendingKyc']);
});
```
