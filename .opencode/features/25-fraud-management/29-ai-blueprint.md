# AI Coding Blueprint — FraudEngine Module

## Overview

This document provides an AI coding blueprint for implementing the FraudEngine module in Laravel. It includes namespace conventions, file locations, service container bindings, and implementation patterns.

## Module Structure

```
Modules/FraudEngine/
├── config/
│   └── fraud.php
├── database/
│   ├── migrations/
│   │   ├── 2025_01_01_000001_create_fraud_cases_table.php
│   │   ├── 2025_01_01_000002_create_fraud_decisions_table.php
│   │   ├── 2025_01_01_000003_create_fraud_rules_table.php
│   │   ├── 2025_01_01_000004_create_fraud_alerts_table.php
│   │   ├── 2025_01_01_000005_create_fraud_ml_models_table.php
│   │   ├── 2025_01_01_000006_create_fraud_case_state_transitions_table.php
│   │   └── 2025_01_01_000007_create_fraud_event_store_table.php
│   └── seeders/
│       └── FraudRuleSeeder.php
├── src/
│   ├── FraudEngineServiceProvider.php
│   ├── FraudEngineFacade.php
│   ├── Contracts/
│   │   ├── FraudRule.php
│   │   ├── FraudScorer.php
│   │   ├── FraudDecision.php
│   │   └── FraudAction.php
│   ├── Events/
│   │   ├── FraudAlertRaised.php
│   │   ├── FraudInvestigationStarted.php
│   │   ├── FraudConfirmed.php
│   │   ├── FraudFalsePositive.php
│   │   └── FraudModelRetrained.php
│   ├── Listeners/
│   │   ├── ScreenTransactionListener.php
│   │   └── UpdateFraudModelListener.php
│   ├── Rules/
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
│   │   ├── RuleScorer.php
│   │   ├── MLScorer.php
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
│   │   └── FraudReportingService.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── FraudDashboardController.php
│   │   │   ├── FraudCaseController.php
│   │   │   ├── FraudScreenController.php
│   │   │   ├── RuleEngineController.php
│   │   │   └── FraudReportController.php
│   │   ├── Requests/
│   │   │   ├── ScreenTransactionRequest.php
│   │   │   └── FraudCaseDecisionRequest.php
│   │   └── Resources/
│   │       ├── FraudCaseResource.php
│   │       └── FraudDecisionResource.php
│   └── Console/
│       ├── Commands/
│       │   ├── RetrainFraudModel.php
│       │   ├── CalculateFraudStats.php
│       │   ├── SyncFraudRules.php
│       │   └── ArchiveFraudData.php
│       └── Kernel.php
├── resources/
│   └── views/
│       ├── dashboard.blade.php
│       ├── cases/
│       ├── rules/
│       └── reports/
├── routes/
│   └── api.php
└── tests/
    ├── Unit/
    │   ├── Rules/
    │   └── Scoring/
    ├── Feature/
    │   └── FraudScreeningTest.php
    └── Fixtures/
        └── FraudEventFactory.php
```

## Service Provider Bindings

```php
namespace Modules\FraudEngine;

use Illuminate\Support\ServiceProvider;
use Modules\FraudEngine\Contracts\FraudScorer;
use Modules\FraudEngine\Contracts\FraudDecision;
use Modules\FraudEngine\Scoring\ScoreAggregator;
use Modules\FraudEngine\Scoring\DecisionEngine;
use Modules\FraudEngine\ML\ONNXScorer;
use Modules\FraudEngine\Services\FraudScreeningService;
use Modules\FraudEngine\Services\CaseManagementService;

class FraudEngineServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FraudScorer::class, ScoreAggregator::class);
        $this->app->singleton(FraudDecision::class, DecisionEngine::class);

        $this->app->singleton(ONNXScorer::class, function ($app) {
            return new ONNXScorer(
                modelPath: config('fraud.ml.model_path'),
                featureCount: config('fraud.ml.feature_count'),
                timeoutMs: config('fraud.ml.inference_timeout_ms', 50),
            );
        });

        $this->app->singleton(FraudScreeningService::class);
        $this->app->singleton(CaseManagementService::class);

        $this->app->singleton('fraud.rule-registry', function ($app) {
            return new RuleRegistry(
                rules: config('fraud.rules.enabled', [])
            );
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'fraud');

        $this->publishes([
            __DIR__ . '/../config/fraud.php' => config_path('fraud.php'),
        ], 'fraud-config');

        $this->app['events']->listen(
            \Modules\FraudEngine\Events\TransactionInitiated::class,
            \Modules\FraudEngine\Listeners\ScreenTransactionListener::class,
        );
    }
}
```

## Facade

```php
namespace Modules\FraudEngine;

use Illuminate\Support\Facades\Facade;

class FraudEngineFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return FraudScreeningService::class;
    }
}
```

## Configuration

```php
return [
    'enabled' => env('FRAUD_ENGINE_ENABLED', true),

    'scoring' => [
        'rules_weight' => env('FRAUD_RULES_WEIGHT', 0.60),
        'ml_weight' => env('FRAUD_ML_WEIGHT', 0.40),
        'timeout_ms' => env('FRAUD_SCORING_TIMEOUT_MS', 150),
    ],

    'decisions' => [
        'safe_threshold' => env('FRAUD_SAFE_THRESHOLD', 39),
        'verify_threshold' => env('FRAUD_VERIFY_THRESHOLD', 59),
        'review_threshold' => env('FRAUD_REVIEW_THRESHOLD', 79),
    ],

    'ml' => [
        'model_path' => env('FRAUD_ML_MODEL_PATH', storage_path('fraud-models/current.onnx')),
        'feature_count' => env('FRAUD_ML_FEATURE_COUNT', 218),
        'inference_timeout_ms' => env('FRAUD_ML_TIMEOUT_MS', 50),
        'training' => [
            'schedule' => env('FRAUD_ML_TRAIN_SCHEDULE', 'daily'),
            'data_window_days' => env('FRAUD_ML_DATA_WINDOW', 90),
            'auto_deploy' => env('FRAUD_ML_AUTO_DEPLOY', true),
        ],
    ],

    'rules' => [
        'enabled' => [
            \Modules\FraudEngine\Rules\Velocity\HighVelocityRule::class,
            \Modules\FraudEngine\Rules\Device\NewDeviceRule::class,
            \Modules\FraudEngine\Rules\Amount\AmountSpikeRule::class,
            \Modules\FraudEngine\Rules\Location\NewLocationRule::class,
            \Modules\FraudEngine\Rules\Agent\AgentFloatVarianceRule::class,
            \Modules\FraudEngine\Rules\SIM\RecentSimSwapRule::class,
            \Modules\FraudEngine\Rules\ML\MLPredictionRule::class,
        ],
        'shadow_mode' => env('FRAUD_RULES_SHADOW_MODE', false),
    ],

    'alerts' => [
        'channels' => ['slack', 'push', 'sms', 'email'],
        'p0_channel' => env('FRAUD_P0_CHANNEL', 'slack'),
    ],

    'cbs' => [
        'sar_threshold_syp' => env('CBS_SAR_THRESHOLD_SYP', 1000000),
        'material_fraud_threshold_syp' => env('CBS_MATERIAL_FRAUD_SYP', 5000000),
        'reporting_email' => env('CBS_REPORTING_EMAIL', 'aml@cb.gov.sy'),
    ],
];
```

## Key Implementation Patterns

### Rule Interface

```php
namespace Modules\FraudEngine\Contracts;

use Modules\FraudEngine\Scoring\FraudEvent;
use Modules\FraudEngine\ML\FeatureVector;

interface FraudRule
{
    public function ruleKey(): string;
    public function category(): string;
    public function name(): string;
    public function evaluate(FraudEvent $event, FeatureVector $features): RuleResult;
}

readonly class RuleResult
{
    public function __construct(
        public bool $triggered,
        public int $score,
        public string $action,
        public string $reason,
    ) {}
}
```

### Scoring Pipeline

```php
class FraudScreeningService
{
    public function screen(ScreenTransactionRequest $request): FraudDecisionResult
    {
        $startTime = microtime(true);
        $features = FeatureExtractor::extract($request);

        $ruleResults = [];
        foreach ($this->ruleRegistry->getActiveRules() as $rule) {
            $ruleResults[] = $rule->evaluate($request->toEvent(), $features);
        }

        $mlScore = $this->mlScorer->score($features);
        $aggregateScore = $this->aggregator->aggregate($ruleResults, $mlScore);
        $decision = $this->decisionEngine->decide($aggregateScore);
        $action = $this->actionExecutor->execute($decision, $request);

        $this->logDecision($request, $aggregateScore, $decision, $action, $startTime);

        return new FraudDecisionResult(
            riskScore: $aggregateScore->total,
            decision: $decision->action,
            actionTaken: $action->name,
            rulesTriggered: $ruleResults,
            mlScore: $mlScore,
            processingTimeMs: (microtime(true) - $startTime) * 1000,
        );
    }
}
```

### ONNX Scorer

```php
class ONNXScorer
{
    private ?\ONNX\Runtime $runtime = null;
    private ?\ONNX\Model $model = null;

    public function __construct(
        private string $modelPath,
        private int $featureCount,
        private int $timeoutMs,
    ) {}

    public function score(FeatureVector $features): float
    {
        try {
            $this->ensureModelLoaded();
            $inputTensor = $this->featuresToTensor($features);
            $output = $this->model->predict(['input' => $inputTensor], $this->timeoutMs);
            return $output['fraud_probability'][0];
        } catch (\Throwable $e) {
            Log::warning('ML scoring failed', ['error' => $e->getMessage()]);
            return 0.5;
        }
    }
}
```

### Routes

```php
Route::prefix('fraud')->middleware(['api', 'auth:fraud-api'])->group(function () {
    Route::post('/screen', [FraudScreenController::class, 'screen']);
    Route::get('/cases', [FraudCaseController::class, 'index']);
    Route::get('/cases/{id}', [FraudCaseController::class, 'show']);
    Route::post('/cases/{id}/decision', [FraudCaseController::class, 'decision']);
    Route::post('/cases/{id}/notes', [FraudCaseController::class, 'addNote']);
    Route::get('/rules', [RuleEngineController::class, 'index']);
    Route::post('/rules/{id}/toggle', [RuleEngineController::class, 'toggle']);
    Route::get('/dashboard', [FraudDashboardController::class, 'index']);
    Route::get('/dashboard/kpis', [FraudDashboardController::class, 'kpis']);
    Route::get('/reports/cbs', [FraudReportController::class, 'cbsReport']);
    Route::get('/reports/provisioning', [FraudReportController::class, 'provisioning']);
});
```
