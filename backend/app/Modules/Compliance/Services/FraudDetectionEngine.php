<?php

declare(strict_types=1);

namespace App\Modules\Compliance\Services;

use App\Modules\Compliance\Enums\AlertSeverity;
use App\Modules\Compliance\Enums\CaseStatus;
use App\Modules\Compliance\Events\AlertGenerated;
use App\Modules\Compliance\Events\AutoBlockTriggered;
use App\Modules\Compliance\Events\CaseEscalated;
use App\Modules\Compliance\Events\ComplianceReviewRequired;
use App\Modules\Compliance\Events\TransactionCompleted;
use App\Modules\Compliance\Models\Alert;
use App\Modules\Compliance\Models\AuditTrail;
use App\Modules\Compliance\Models\ComplianceCase;
use App\Modules\Compliance\ValueObjects\RiskScore;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class FraudDetectionEngine
{
    public function __construct(private readonly RuleEngine $ruleEngine) {}

    public function evaluateTransaction(TransactionCompleted $event): void
    {
        $context = [
            'transaction_id' => $event->transactionId,
            'account_id' => $event->accountId,
            'recipient_id' => $event->recipientId,
            'amount' => $event->amount,
            'currency' => $event->currency,
            'device_fingerprint' => $event->deviceFingerprint,
            'country' => $event->country,
            'is_new_device' => $event->isNewDevice,
            'is_untrusted_device' => $event->isUntrustedDevice,
            'is_new_recipient' => $event->isNewRecipient,
            'recipient_repeat_amount' => $event->recipientRepeatAmount,
            'daily_transaction_count' => $event->dailyTransactionCount,
        ];

        $evaluation = $this->ruleEngine->evaluateWithDetails($context);
        $riskScore = new RiskScore($evaluation['risk_score']['score']);

        // تسجيل كل تقييم قاعدة في سجل التدقيق غير القابل للتعديل
        foreach ($evaluation['rule_details'] as $detail) {
            $this->ruleEngine->logAudit(
                ruleId: $detail['rule_id'],
                score: $detail['score'],
                context: $context,
                action: "evaluate_transaction_{$event->transactionId}",
            );
        }

        if ($riskScore->requiresBlock()) {
            $this->triggerBlock($event, $riskScore, $evaluation['rule_details']);
        } elseif ($riskScore->requiresAction()) {
            $this->triggerReview($event, $riskScore, $evaluation['rule_details']);
        }

        Log::info('FraudDetectionEngine: transaction evaluated', [
            'transaction_id' => $event->transactionId,
            'risk_score' => $riskScore->toArray(),
        ]);
    }

    private function triggerBlock(TransactionCompleted $event, RiskScore $riskScore, array $ruleDetails): void
    {
        $accountId = $event->accountId;
        $reason = 'Critical risk score: ' . $riskScore->level() . ' (' . $riskScore->score() . ')';

        Event::dispatch(new AutoBlockTriggered(
            accountId: $accountId,
            reason: $reason,
            riskScore: $riskScore->score(),
            timestamp: now()->getTimestamp(),
        ));

        $case = ComplianceCase::create([
            'case_id' => 'CASE-' . Str::ulid()->toBase32(),
            'transaction_id' => $event->transactionId,
            'account_id' => $accountId,
            'risk_score' => $riskScore->score(),
            'status' => CaseStatus::OPEN,
            'severity' => AlertSeverity::CRITICAL,
            'triggered_rules' => $ruleDetails,
            'context' => ['transaction' => [
                'amount' => $event->amount,
                'currency' => $event->currency,
                'device' => $event->deviceFingerprint,
                'country' => $event->country,
            ]],
        ]);

        Event::dispatch(new AlertGenerated(
            alertId: 'ALT-' . Str::ulid()->toBase32(),
            caseId: $case->case_id,
            severity: AlertSeverity::CRITICAL,
            message: $reason,
            timestamp: now()->getTimestamp(),
        ));
    }

    private function triggerReview(TransactionCompleted $event, RiskScore $riskScore, array $ruleDetails): void
    {
        Event::dispatch(new ComplianceReviewRequired(
            transactionId: $event->transactionId,
            accountId: $event->accountId,
            riskScore: $riskScore->score(),
            triggeredRules: $ruleDetails,
            timestamp: now()->getTimestamp(),
        ));

        $case = ComplianceCase::create([
            'case_id' => 'CASE-' . Str::ulid()->toBase32(),
            'transaction_id' => $event->transactionId,
            'account_id' => $event->accountId,
            'risk_score' => $riskScore->score(),
            'status' => CaseStatus::OPEN,
            'severity' => $riskScore->level(),
            'triggered_rules' => $ruleDetails,
            'context' => ['transaction' => [
                'amount' => $event->amount,
                'currency' => $event->currency,
                'device' => $event->deviceFingerprint,
                'country' => $event->country,
            ]],
        ]);

        Event::dispatch(new AlertGenerated(
            alertId: 'ALT-' . Str::ulid()->toBase32(),
            caseId: $case->case_id,
            severity: $riskScore->level(),
            message: "Compliance review required: risk score {$riskScore->score()}",
            timestamp: now()->getTimestamp(),
        ));
    }

    public function getCase(string $caseId): ?ComplianceCase
    {
        return ComplianceCase::where('case_id', $caseId)->first();
    }

    public function reviewCase(string $caseId, string $resolution, string $reason, ?string $reviewerId): array
    {
        $case = ComplianceCase::where('case_id', $caseId)->firstOrFail();

        CaseStatus::assertTransition($case->status, $resolution);

        $case->update([
            'status' => $resolution,
            'reviewer_id' => $reviewerId,
            'reviewed_at' => now(),
            'resolution' => $resolution,
            'resolution_reason' => $reason,
            'closed_at' => in_array($resolution, ['RESOLVED_FALSE_POSITIVE', 'RESOLVED_TRUE_POSITIVE', 'CLOSED'], true) ? now() : null,
        ]);

        Event::dispatch(new \App\Modules\Compliance\Events\CaseResolved(
            caseId: $caseId,
            resolution: $resolution,
            reviewerId: $reviewerId ?? 'system',
            timestamp: now()->getTimestamp(),
        ));

        $this->ruleEngine->logAudit(
            ruleId: 'case_review',
            score: $case->risk_score,
            context: ['case_id' => $caseId, 'resolution' => $resolution, 'reason' => $reason],
            action: "review_case_{$caseId}",
        );

        return $case->fresh()->toArray();
    }
}
