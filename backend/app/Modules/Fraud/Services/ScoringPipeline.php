<?php

declare(strict_types=1);

namespace App\Modules\Fraud\Services;

use App\Modules\Fraud\Models\FraudRule;

final class ScoringPipeline
{
    private const MAX_SCORE = 1000;
    private const MIN_SCORE = 0;

    private int $score = 500;
    private array $triggers = [];
    private string $action = 'allow';
    private string $reason = '';
    private string $reasonAr = '';

    public function evaluate(int $amount, int $deviceTrustScore, int $velocityCount, int $deviceCount, iterable $rules): self
    {
        $pipeline = clone $this;

        foreach ($rules as $rule) {
            if (!$rule->is_active) {
                continue;
            }

            $hit = $this->matchRule($rule, $amount, $deviceTrustScore, $velocityCount, $deviceCount);

            if ($hit) {
                $pipeline->score = max(self::MIN_SCORE, min(self::MAX_SCORE, $pipeline->score + $rule->score_impact));
                $pipeline->triggers[] = [
                    'rule_id' => $rule->id,
                    'name' => $rule->name,
                    'impact' => $rule->score_impact,
                    'action' => $rule->action,
                ];

                if ($this->isMoreSevere($rule->action, $pipeline->action)) {
                    $pipeline->action = $rule->action;
                    $pipeline->reason = $rule->name;
                    $pipeline->reasonAr = $rule->name_ar;
                }
            }
        }

        if ($pipeline->action === 'allow' && $pipeline->score >= 700) {
            $pipeline->action = 'allow';
        }

        return $pipeline;
    }

    private function matchRule(FraudRule $rule, int $amount, int $deviceTrustScore, int $velocityCount, int $deviceCount): bool
    {
        return match ($rule->metric) {
            'txn_amount' => $amount >= $rule->threshold,
            'device_trust' => $deviceTrustScore <= $rule->threshold,
            'txn_count_1h', 'txn_count_24h' => $velocityCount >= $rule->threshold,
            'device_count' => $deviceCount >= $rule->threshold,
            'score_threshold' => $this->score >= $rule->threshold,
            default => false,
        };
    }

    private function isMoreSevere(string $new, string $current): bool
    {
        $order = ['allow' => 0, 'flag' => 1, 'hold' => 2, 'block' => 3];
        return ($order[$new] ?? 0) > ($order[$current] ?? 0);
    }

    public function getScore(): int
    {
        return $this->score;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function getReasonAr(): string
    {
        return $this->reasonAr;
    }

    public function getTriggers(): array
    {
        return $this->triggers;
    }
}
