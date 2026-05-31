<?php

declare(strict_types=1);

namespace App\Modules\Fraud\Services;

use App\Modules\Agent\Models\Agent;
use App\Modules\Core\ValueObjects\Money;
use App\Modules\Fraud\Models\ComplianceRule;
use App\Modules\Fraud\Models\RiskScore;

final class ComplianceRuleManager
{
    public function evaluate(ComplianceRule $rule, Agent $agent, Money $amount, array $context = []): array
    {
        $method = 'evaluate' . ucfirst($rule->rule_type);

        if (method_exists($this, $method)) {
            return $this->$method($rule, $agent, $amount, $context);
        }

        return ['triggered' => false, 'reason' => ''];
    }

    private function evaluateAmount(ComplianceRule $rule, Agent $agent, Money $amount, array $context): array
    {
        $minAmount = $rule->parameters['min_amount_fils'] ?? PHP_INT_MAX;

        if ($amount->fils() >= $minAmount) {
            return [
                'triggered' => true,
                'reason' => "المبلغ {$amount->fils()} فلس يتجاوز عتبة " . number_format($minAmount) . ' فلس',
            ];
        }

        return ['triggered' => false, 'reason' => ''];
    }

    private function evaluateFrequency(ComplianceRule $rule, Agent $agent, Money $amount, array $context): array
    {
        $maxCount = $rule->parameters['max_count'] ?? 3;
        $windowMinutes = $rule->parameters['window_minutes'] ?? 10;
        $since = now()->subMinutes($windowMinutes);

        $recentCount = RiskScore::where('user_id', $agent->user_id)
            ->where('created_at', '>=', $since)
            ->count();

        if ($recentCount >= $maxCount) {
            return [
                'triggered' => true,
                'reason' => "أكثر من {$maxCount} معاملة خلال {$windowMinutes} دقائق",
            ];
        }

        return ['triggered' => false, 'reason' => ''];
    }

    private function evaluateRegion(ComplianceRule $rule, Agent $agent, Money $amount, array $context): array
    {
        $highRiskRegions = $rule->parameters['regions'] ?? [];
        $agentRegion = $context['region'] ?? $agent->region;

        if (in_array($agentRegion, $highRiskRegions, true)) {
            return [
                'triggered' => true,
                'reason' => "المنطقة {$agentRegion} مصنفة عالية الخطورة",
            ];
        }

        return ['triggered' => false, 'reason' => ''];
    }

    private function evaluateDevice(ComplianceRule $rule, Agent $agent, Money $amount, array $context): array
    {
        $maxDevices = $rule->parameters['max_devices_per_day'] ?? 2;
        $deviceId = $context['device_id'] ?? null;

        if ($deviceId === null) {
            return ['triggered' => false, 'reason' => ''];
        }

        $todayDevices = RiskScore::where('user_id', $agent->user_id)
            ->where('created_at', '>=', now()->startOfDay())
            ->where('metadata->device_id', $deviceId)
            ->count();

        if ($todayDevices > $maxDevices) {
            return [
                'triggered' => true,
                'reason' => 'تجاوز عدد الأجهزة المستخدمة الحد المسموح يومياً',
            ];
        }

        return ['triggered' => false, 'reason' => ''];
    }

    public function getAllActive(): array
    {
        return ComplianceRule::where('is_active', true)
            ->orderBy('priority', 'desc')
            ->get()
            ->toArray();
    }

    public function toggleRule(string $key, bool $isActive): ComplianceRule
    {
        $rule = ComplianceRule::where('key', $key)->firstOrFail();
        $rule->update(['is_active' => $isActive]);
        return $rule->fresh();
    }

    public function updateRule(string $key, array $data): ComplianceRule
    {
        $rule = ComplianceRule::where('key', $key)->firstOrFail();
        $rule->update($data);
        return $rule->fresh();
    }

    public function createRule(array $data): ComplianceRule
    {
        return ComplianceRule::create($data);
    }

    public function previewImpact(string $key, array $newParameters, int $newScore): array
    {
        $recentScores = RiskScore::where('request_type', 'liquidity')
            ->latest()
            ->take(20)
            ->get();

        $beforeCount = $recentScores->filter(fn ($s) => $s->status !== 'approved')->count();
        $previewCount = 0;

        foreach ($recentScores as $score) {
            $previewCount += ($score->score + $newScore) >= 30 ? 1 : 0;
        }

        return [
            'total_recent' => $recentScores->count(),
            'flagged_before' => $beforeCount,
            'estimated_flagged_after' => $previewCount,
            'impact_percentage' => $recentScores->count() > 0
                ? round(($previewCount / $recentScores->count()) * 100, 1)
                : 0,
        ];
    }
}
