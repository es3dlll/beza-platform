<?php

declare(strict_types=1);

namespace App\Modules\Compliance\Services;

use App\Modules\Compliance\Models\AuditTrail;
use App\Modules\Compliance\ValueObjects\RiskScore;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

final class RuleEngine
{
    const CACHE_KEY = 'compliance_rules';
    const CACHE_TTL = 300;

    public function loadRules(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return \App\Modules\Compliance\Models\ComplianceRuleConfig::where('active', true)->get()->map(
                fn ($cfg) => new \App\Modules\Compliance\ValueObjects\ComplianceRule(
                    id: $cfg->rule_id,
                    description: $cfg->description,
                    evaluationType: $cfg->evaluation_type,
                    threshold: $cfg->threshold,
                    action: $cfg->action,
                )
            )->toArray();
        });
    }

    public function evaluate(array $context): RiskScore
    {
        $rules = $this->loadRules();
        $max = 0;

        if (count($rules) === 0) {
            return new RiskScore(0);
        }

        foreach ($rules as $rule) {
            $score = $rule->evaluate($context);
            if ($score > $max) {
                $max = $score;
            }
        }

        return new RiskScore($max);
    }

    public function evaluateWithDetails(array $context): array
    {
        $rules = $this->loadRules();
        $details = [];
        $max = 0;

        foreach ($rules as $rule) {
            $score = $rule->evaluate($context);
            if ($score > $max) {
                $max = $score;
            }
            $details[] = [
                'rule_id' => $rule->id(),
                'score' => $score,
                'action' => $rule->action(),
            ];
        }

        $riskScore = new RiskScore($max);

        return [
            'risk_score' => $riskScore->toArray(),
            'rule_details' => $details,
            'requires_action' => $riskScore->requiresAction(),
            'requires_block' => $riskScore->requiresBlock(),
        ];
    }

    public function logAudit(string $ruleId, int $score, array $context, string $action): void
    {
        AuditTrail::create([
            'trace_id' => 'AUD-' . Str::ulid()->toBase32(),
            'rule_id' => $ruleId,
            'risk_score' => $score,
            'context' => $context,
            'action' => $action,
            'timestamp' => now()->getTimestamp(),
            'irreversible' => true,
        ]);
    }
}
