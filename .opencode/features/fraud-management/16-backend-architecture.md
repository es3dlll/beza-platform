# Backend Architecture — FraudEngine Module

## Architecture Overview

Beza uses a **Laravel Modular Monolith** architecture. FraudEngine is a self-contained module within the monolith, communicating with other features via events, commands, and a shared database.

```
┌─────────────────────────────────────────────────────────────────────┐
│                      BEZA MODULAR MONOLITH                          │
│                                                                     │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐           │
│  │  Wallet  │  │  Agent   │  │Remittance│  │ Merchant │  ...       │
│  │  Module  │  │  Module  │  │  Module  │  │  Module  │           │
│  └─────┬────┘  └─────┬────┘  └─────┬────┘  └─────┬────┘           │
│        │              │              │              │               │
│        └──────────────┴──────────────┴──────────────┘               │
│                           │                                         │
│                           ▼                                         │
│              ┌──────────────────────────┐                           │
│              │      FraudEngine         │  ◄── CROSS-CUTTING       │
│              │      Module              │                           │
│              └──────────────────────────┘                           │
│                           │                                         │
│                           ▼                                         │
│              ┌──────────────────────────┐                           │
│              │    Shared Kernel         │                           │
│              │  (Events, DB, Queue,     │                           │
│              │   Monolog, Cache)        │                           │
│              └──────────────────────────┘                           │
└─────────────────────────────────────────────────────────────────────┘
```

## FraudEngine Internal Architecture

```
Transaction ──▶ FraudEvent ──▶ Event Bus
                                    │
                                    ▼
                          ┌─────────────────┐
                          │ Rule Engine      │
                          │ - Rules (100+)   │
                          │ - ML Model       │
                          │ - Scoring Engine │
                          │ - Action Engine  │
                          └────────┬────────┘
                                    │
                                    ▼
                          ┌─────────────────┐
                          │ Decision         │
                          │ approve          │
                          │ review           │
                          │ block            │
                          └─────────────────┘
                                    │
                                    ▼
                          ┌─────────────────┐
                          │ Action Executor  │
                          │ - Approve txn    │
                          │ - Flag for review│
                          │ - Block txn      │
                          │ - Freeze account │
                          │ - Trigger alert  │
                          └─────────────────┘
```

## Component Details

### 1. FraudEvent — Event Ingestion

```php
// Every financial transaction generates a FraudEvent
class FraudEvent {
    public string $eventId;          // UUID
    public string $transactionId;    // Reference to original txn
    public string $featureSource;    // 'wallet', 'agent', 'remittance', etc.
    public float $amount;
    public string $currency;         // SYP, USD, EUR
    public string $senderId;
    public string $recipientId;
    public array $context;           // Feature-specific data (device, location, etc.)
    public Carbon $timestamp;
}
```

**Ingestion:**

- All features publish `TransactionInitiated` event
- FraudEngine subscribes via Laravel event system
- Events processed synchronously (must complete within 200ms)
- Async fallback for non-critical screening (batch review)

### 2. Rule Engine

The core decision-making component:

```
┌─────────────────────────────────────────────────────────────┐
│  RULE ENGINE                                                 │
│                                                             │
│  1. Feature Extraction Layer                                │
│     ┌────────────────────────────────────────────────────┐ │
│     │ Computes 200+ features from event context           │ │
│     │ - Amount-based: ratio to avg, z-score, growth rate │ │
│     │ - Time-based: hour, day, days since last txn       │ │
│     │ - Device-based: new device, device age, SIM change │ │
│     │ - Location-based: distance from home, new city     │ │
│     │ - Velocity: txns in 5min/30min/24h, total amount  │ │
│     │ - Network-based: agent pattern, corridor risk      │ │
│     └────────────────────────────────────────────────────┘ │
│                                                             │
│  2. Rule Evaluation Layer                                   │
│     ┌────────────────────────────────────────────────────┐ │
│     │ Evaluates 100+ rules in parallel (Laravel pipeline)│ │
│     │ Each rule returns: { triggered: bool, score: int } │ │
│     │ Rules are PHP classes implementing FraudRule        │ │
│     │ Rules loaded from database (configurable)           │ │
│     └────────────────────────────────────────────────────┘ │
│                                                             │
│  3. ML Scoring Layer                                        │
│     ┌────────────────────────────────────────────────────┐ │
│     │ ONNX Runtime for model inference                    │ │
│     │ Model: Gradient Boosted Trees + Deep Learning      │ │
│     │ Input: feature vector (200+ features)              │ │
│     │ Output: fraud probability (0.0 - 1.0)             │ │
│     │ Transforms probability to score (0-100)            │ │
│     └────────────────────────────────────────────────────┘ │
│                                                             │
│  4. Scoring & Decision Engine                               │
│     ┌────────────────────────────────────────────────────┐ │
│     │ Combined Score = RulesScore + MLScore              │ │
│     │ Weighted: 60% rules + 40% ML (configurable)       │ │
│     │ Decision Matrix:                                    │ │
│     │   0-39  → APPROVE (no action)                      │ │
│     │   40-59 → VERIFY (soft block, user verification)   │ │
│     │   60-79 → REVIEW (manual review by ops)            │ │
│     │   80-100→ BLOCK (transaction prevented)            │ │
│     └────────────────────────────────────────────────────┘ │
│                                                             │
│  5. Action Engine                                           │
│     ┌────────────────────────────────────────────────────┐ │
│     │ Executes the decision:                             │ │
│     │ - APPROVE: Publish TransactionApproved event       │ │
│     │ - VERIFY: Publish TransactionNeedsVerification     │ │
│     │ - REVIEW: Create FraudCase, Publish Alert          │ │
│     │ - BLOCK: Publish TransactionBlocked, Freeze        │ │
│     └────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

### 3. Directory Structure

```
Beza/
└── Modules/
    └── FraudEngine/
        ├── config/
        │   └── fraud.php                    # Module configuration
        ├── database/
        │   ├── migrations/                  # Fraud tables
        │   └── seeders/                     # Default rules
        ├── src/
        │   ├── FraudEngineServiceProvider.php
        │   ├── Events/
        │   │   ├── FraudAlertRaised.php
        │   │   ├── FraudInvestigationStarted.php
        │   │   ├── FraudConfirmed.php
        │   │   ├── FraudFalsePositive.php
        │   │   └── FraudModelRetrained.php
        │   ├── Listeners/
        │   │   ├── ScreenTransaction.php     # Main listener
        │   │   └── UpdateFraudModel.php
        │   ├── Rules/
        │   │   ├── Contracts/
        │   │   │   └── FraudRule.php        # Interface
        │   │   ├── Velocity/
        │   │   │   ├── HighVelocityRule.php
        │   │   │   └── RapidCashOutRule.php
        │   │   ├── Device/
        │   │   │   ├── NewDeviceRule.php
        │   │   │   └── DeviceCountryMismatchRule.php
        │   │   ├── Amount/
        │   │   │   ├── AmountSpikeRule.php
        │   │   │   └── RoundAmountRule.php
        │   │   ├── Location/
        │   │   │   └── NewLocationRule.php
        │   │   ├── Agent/
        │   │   │   ├── AgentFloatVarianceRule.php
        │   │   │   └── AgentCustomerCollusionRule.php
        │   │   ├── SIM/
        │   │   │   └── RecentSimSwapRule.php
        │   │   └── ML/
        │   │       └── MLPredictionRule.php
        │   ├── Scoring/
        │   │   ├── ScoreAggregator.php
        │   │   ├── RiskFactorCalculator.php
        │   │   └── DecisionEngine.php
        │   ├── ML/
        │   │   ├── ModelManager.php
        │   │   ├── ONNXScorer.php
        │   │   ├── FeatureExtractor.php
        │   │   └── TrainingPipeline.php
        │   ├── Actions/
        │   │   ├── ApproveTransactionAction.php
        │   │   ├── BlockTransactionAction.php
        │   │   ├── FreezeAccountAction.php
        │   │   ├── CreateFraudCaseAction.php
        │   │   └── SendAlertAction.php
        │   ├── Models/
        │   │   ├── FraudCase.php
        │   │   ├── FraudRule.php
        │   │   ├── FraudDecision.php
        │   │   └── FraudAlert.php
        │   ├── Repositories/
        │   │   ├── FraudCaseRepository.php
        │   │   └── FraudDecisionRepository.php
        │   ├── Services/
        │   │   ├── FraudScreeningService.php
        │   │   ├── CaseManagementService.php
        │   │   └── ReportingService.php
        │   ├── Http/
        │   │   ├── Controllers/
        │   │   │   ├── FraudDashboardController.php
        │   │   │   ├── FraudCaseController.php
        │   │   │   ├── RuleEngineController.php
        │   │   │   └── FraudReportController.php
        │   │   └── Requests/
        │   │       ├── ScreenTransactionRequest.php
        │   │       └── FraudCaseDecisionRequest.php
        │   └── Console/
        │       ├── RetrainFraudModel.php
        │       ├── CalculateFraudStats.php
        │       └── SyncFraudRules.php
        ├── resources/
        │   └── views/                      # Dashboard views
        ├── routes/
        │   └── api.php                     # FraudEngine API routes
        └── tests/
            ├── Unit/
            │   ├── Rules/
            │   └── Scoring/
            ├── Feature/
            │   ├── FraudScreeningTest.php
            │   └── CaseManagementTest.php
            └── Fixtures/
                └── FraudEvents.php
```

### 4. Database Schema

```sql
-- fraud_cases: Investigation cases
CREATE TABLE fraud_cases (
    id UUID PRIMARY KEY,
    case_number VARCHAR(20) UNIQUE,  -- FR-2025-00001
    status VARCHAR(30),              -- alert, investigation, confirmed, etc.
    priority VARCHAR(5),             -- P0, P1, P2, P3
    fraud_type VARCHAR(50),          -- account_takeover, sim_swap, etc.
    transaction_id UUID,
    amount DECIMAL(18,2),
    currency VARCHAR(3) DEFAULT 'SYP',
    victim_user_id UUID,
    suspect_user_id UUID,
    risk_score INTEGER,
    assigned_to UUID NULL,
    description TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    resolved_at TIMESTAMP NULL
);

-- fraud_decisions: Every screening decision
CREATE TABLE fraud_decisions (
    id UUID PRIMARY KEY,
    transaction_id UUID,
    risk_score INTEGER,
    decision VARCHAR(20),            -- approve, verify, review, block
    action_taken VARCHAR(50),        -- approved, blocked, frozen
    processing_time_ms INTEGER,
    rules_triggered JSONB,           -- [{rule_id, score, action}]
    ml_score DECIMAL(5,4),
    ml_model_version VARCHAR(20),
    decision_origin VARCHAR(20),     -- auto, manual
    decided_by UUID NULL,            -- ops user if manual
    created_at TIMESTAMP
);

-- fraud_rules: Configurable rules
CREATE TABLE fraud_rules (
    id UUID PRIMARY KEY,
    rule_key VARCHAR(20) UNIQUE,     -- VEL-003
    name VARCHAR(100),
    category VARCHAR(30),            -- velocity, device, amount, etc.
    description TEXT,
    conditions JSONB,               -- Rule conditions (AND/OR logic)
    action VARCHAR(20),              -- flag, slow, block, freeze
    score_weight INTEGER DEFAULT 0,
    is_active BOOLEAN DEFAULT true,
    is_shadow BOOLEAN DEFAULT false, -- log only, no action
    version INTEGER DEFAULT 1,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- fraud_alerts: Generated alerts
CREATE TABLE fraud_alerts (
    id UUID PRIMARY KEY,
    fraud_case_id UUID,
    priority VARCHAR(5),
    type VARCHAR(30),
    title VARCHAR(200),
    message TEXT,
    channel VARCHAR(30),             -- dashboard, push, sms, slack
    acknowledged_at TIMESTAMP NULL,
    acknowledged_by UUID NULL,
    created_at TIMESTAMP
);

-- fraud_ml_models: Model tracking
CREATE TABLE fraud_ml_models (
    id UUID PRIMARY KEY,
    version VARCHAR(20),
    status VARCHAR(20),              -- training, active, rollback, deprecated
    auc_roc DECIMAL(5,4),
    precision_score DECIMAL(5,4),
    recall_score DECIMAL(5,4),
    training_data_start DATE,
    training_data_end DATE,
    feature_count INTEGER,
    model_path VARCHAR(500),
    trained_by VARCHAR(100),
    deployed_at TIMESTAMP NULL,
    created_at TIMESTAMP
);
```

### 5. Event Flow (Sequence)

```
Wallet Module                 FraudEngine                     Database
    │                             │                             │
    │──TransactionInitiated──────▶│                             │
    │                             │                             │
    │                    ┌────────┴────────┐                    │
    │                    │ Feature Extract │                    │
    │                    └────────┬────────┘                    │
    │                             │                             │
    │                    ┌────────┴────────┐                    │
    │                    │ Rule Evaluation │                    │
    │                    │ (100 rules,     │                    │
    │                    │  parallel)      │                    │
    │                    └────────┬────────┘                    │
    │                             │                             │
    │                    ┌────────┴────────┐                    │
    │                    │ ML Scoring      │                    │
    │                    │ (ONNX)          │                    │
    │                    └────────┬────────┘                    │
    │                             │                             │
    │                    ┌────────┴────────┐                    │
    │                    │ Decision Engine │                    │
    │                    └────────┬────────┘                    │
    │                             │                             │
    │◀───Decision (approve)───────┘                             │
    │                             │                             │
    │                             │────FraudDecision──────────▶│
    │                             │                             │
    │ (Transaction continues)     │                             │
```

### 6. Integration with Other Features

```php
// In other feature modules (e.g., Wallet):
class WalletTransferService {
    public function transfer(TransferRequest $request): TransferResult
    {
        // ... validation, balance check ...

        // Fraud screening (synchronous, must complete < 200ms)
        $fraudResult = FraudScreeningService::screen(
            featureSource: 'wallet',
            transactionId: $transaction->id,
            amount: $request->amount,
            senderId: $request->sender_id,
            recipientId: $request->recipient_id,
            context: [
                'device_fingerprint' => $request->device_fingerprint,
                'location' => $request->location,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]
        );

        if ($fraudResult->decision === 'block') {
            throw new FraudBlockedException($fraudResult);
        }

        if ($fraudResult->decision === 'review') {
            $transaction->markAsPending();
            return TransferResult::pending($fraudResult);
        }

        // Continue with transfer
    }
}
```

### 7. Rule Interface

```php
namespace Modules\FraudEngine\Rules\Contracts;

interface FraudRule
{
    public function ruleKey(): string;
    public function category(): string;
    public function name(): string;
    public function evaluate(FraudEvent $event, FeatureVector $features): RuleResult;
}

class RuleResult {
    public bool $triggered;
    public int $score;           // 0-100 contribution
    public string $action;       // flag, slow, block, freeze
    public ?string $reason;      // Human-readable explanation
}
```

### 8. Performance Targets

| Component                  | Target    | P99 Max   |
| -------------------------- | --------- | --------- |
| Feature extraction         | 30ms      | 80ms      |
| Rule evaluation (parallel) | 20ms      | 50ms      |
| ML scoring (ONNX)          | 30ms      | 70ms      |
| Decision aggregation       | 10ms      | 30ms      |
| Action execution           | 10ms      | 30ms      |
| **Total**                  | **100ms** | **260ms** |
