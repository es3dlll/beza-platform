<?php

declare(strict_types=1);

namespace App\Modules\Fraud\Services;

use App\Modules\Fraud\Events\FraudAlertTriggered;
use App\Modules\Fraud\Events\FraudDecisionLogged;
use App\Modules\Fraud\Exceptions\TransactionBlockedException;
use App\Modules\Fraud\Models\FraudDecision;
use App\Modules\Fraud\Models\FraudRule;

final class FraudGuard
{
    public function __construct(
        private readonly ScoringPipeline $scoringPipeline,
        private readonly VelocityService $velocityService,
        private readonly DeviceFingerprintService $deviceFingerprintService,
    ) {}

    public function preCheck(
        string $walletId,
        int $amount,
        array $deviceData = [],
        ?string $kycTier = 't0',
        ?string $contextId = null,
    ): FraudDecision {
        $rules = FraudRule::where('is_active', true)
            ->where('category', 'pre_check')
            ->where('kyc_tier_min', '<=', $kycTier ?? 't0')
            ->orderBy('priority', 'desc')
            ->get();

        $deviceFingerprint = null;
        $deviceTrustScore = 500;
        $deviceCount = 0;

        if (!empty($deviceData)) {
            $deviceFingerprint = $this->deviceFingerprintService->registerOrVerify($walletId, $deviceData);
            $deviceTrustScore = $deviceFingerprint->trust_score;
            $deviceCount = $this->deviceFingerprintService->getDeviceCount($walletId);
        }

        $velocityResults = [];
        $velocityRules = $rules->where('type', 'velocity');
        foreach ($velocityRules as $rule) {
            $allowed = $this->velocityService->checkAndIncrement($walletId, $rule);
            if (!$allowed) {
                $velocityResults[] = $rule;
            }
        }

        $pipeline = $this->scoringPipeline->evaluate(
            amount: $amount,
            deviceTrustScore: $deviceTrustScore,
            velocityCount: count($velocityResults),
            deviceCount: $deviceCount,
            rules: $rules,
        );

        $decision = FraudDecision::create([
            'wallet_id' => $walletId,
            'rule_id' => $pipeline->getTriggers()[0]['rule_id'] ?? null,
            'device_fingerprint_id' => $deviceFingerprint?->id,
            'action' => $pipeline->getAction(),
            'score_before' => 500,
            'score_after' => $pipeline->getScore(),
            'score_impact' => $pipeline->getScore() - 500,
            'reason' => $pipeline->getReason(),
            'reason_ar' => $pipeline->getReasonAr(),
            'context_type' => 'transaction',
            'context_id' => $contextId,
        ]);

        event(new FraudDecisionLogged(
            decisionId: $decision->id,
            walletId: $walletId,
            action: $decision->action,
            scoreBefore: 500,
            scoreAfter: $pipeline->getScore(),
            reason: $pipeline->getReason(),
        ));

        if ($pipeline->getAction() === 'block') {
            event(new FraudAlertTriggered(
                decisionId: $decision->id,
                walletId: $walletId,
                ruleId: $pipeline->getTriggers()[0]['rule_id'] ?? '',
                action: 'block',
                score: $pipeline->getScore(),
                reason: $pipeline->getReason(),
                contextId: $contextId,
            ));

            throw new TransactionBlockedException(
                score: $pipeline->getScore(),
                reason: $pipeline->getReason(),
            );
        }

        return $decision;
    }

    public function postMonitor(
        string $walletId,
        string $transactionId,
        int $amount,
        array $deviceData = [],
        ?string $kycTier = 't0',
    ): void {
        $rules = FraudRule::where('is_active', true)
            ->where('category', 'post_monitor')
            ->where('kyc_tier_min', '<=', $kycTier ?? 't0')
            ->get();

        $deviceFingerprint = null;
        if (!empty($deviceData)) {
            $deviceFingerprint = $this->deviceFingerprintService->registerOrVerify($walletId, $deviceData);
            $deviceFingerprint->updateTrustScore(5);  // successful txn = +5 trust
        }

        $pipeline = $this->scoringPipeline->evaluate(
            amount: $amount,
            deviceTrustScore: $deviceFingerprint?->trust_score ?? 500,
            velocityCount: 0,
            deviceCount: $this->deviceFingerprintService->getDeviceCount($walletId),
            rules: $rules,
        );

        if ($pipeline->getScore() >= 700) {
            FraudDecision::create([
                'wallet_id' => $walletId,
                'rule_id' => $pipeline->getTriggers()[0]['rule_id'] ?? null,
                'device_fingerprint_id' => $deviceFingerprint?->id,
                'action' => $pipeline->getAction(),
                'score_before' => 500,
                'score_after' => $pipeline->getScore(),
                'score_impact' => $pipeline->getScore() - 500,
                'reason' => $pipeline->getReason(),
                'reason_ar' => $pipeline->getReasonAr(),
                'context_type' => 'transaction',
                'context_id' => $transactionId,
            ]);
        }
    }
}
