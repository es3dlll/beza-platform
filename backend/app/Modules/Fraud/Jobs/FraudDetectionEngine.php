<?php

declare(strict_types=1);

namespace App\Modules\Fraud\Jobs;

use App\Modules\Agent\Models\Agent;
use App\Modules\Core\Enums\Currency;
use App\Modules\Core\ValueObjects\Money;
use App\Modules\Fraud\Events\LiquidityApproved;
use App\Modules\Fraud\Models\ComplianceRule;
use App\Modules\Fraud\Models\RiskScore;
use App\Modules\Fraud\Services\ComplianceRuleManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class FraudDetectionEngine implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 30;
    public int $tries = 3;

    public function __construct(
        public Agent $agent,
        public int $amountFils,
        public string $currency,
        public string $requestId,
        public ?string $region = null,
        public ?string $deviceId = null,
    ) {}

    public function handle(ComplianceRuleManager $ruleManager): void
    {
        $amount = Money::fromFils($this->amountFils, Currency::from($this->currency));

        $totalScore = 0;
        $reasons = [];
        $appliedRules = [];

        $activeRules = ComplianceRule::where('is_active', true)
            ->orderBy('priority', 'desc')
            ->get();

        foreach ($activeRules as $rule) {
            $result = $ruleManager->evaluate($rule, $this->agent, $amount, [
                'region' => $this->region,
                'device_id' => $this->deviceId,
                'request_id' => $this->requestId,
            ]);

            if ($result['triggered']) {
                $totalScore += $rule->risk_score_impact;
                $reasons[] = $result['reason'];
                $appliedRules[] = [
                    'rule_key' => $rule->key,
                    'rule_name' => $rule->name,
                    'score_impact' => $rule->risk_score_impact,
                ];
            }
        }

        $totalScore = min($totalScore, 100);

        $decision = $this->determineDecision($totalScore);

        RiskScore::create([
            'score' => $totalScore,
            'status' => $decision,
            'reasons' => $reasons,
            'request_type' => 'liquidity',
            'request_id' => $this->requestId,
            'user_id' => $this->agent->user_id,
            'amount_fils' => $this->amountFils,
            'currency' => $this->currency,
            'region' => $this->region,
            'metadata' => [
                'applied_rules' => $appliedRules,
                'agent_id' => $this->agent->id,
            ],
        ]);

        if ($decision === 'approved') {
            event(new LiquidityApproved(
                agent: $this->agent,
                amount: $amount,
                riskScore: $totalScore,
                requestId: $this->requestId,
            ));
        }
    }

    private function determineDecision(int $score): string
    {
        if ($score < 30) {
            return 'approved';
        }
        if ($score < 70) {
            return 'suspended';
        }
        return 'rejected';
    }
}
