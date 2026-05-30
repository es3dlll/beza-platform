<?php

declare(strict_types=1);

namespace Modules\Fraud\Services;

use Modules\Fraud\DTOs\FraudCheckDto;
use Modules\Fraud\Enums\FraudDecision;
use Modules\Fraud\Enums\FraudSeverity;
use Modules\Fraud\Events\FraudTransactionBlocked;
use Modules\Fraud\Events\FraudCaseCreated;
use Modules\Fraud\Exceptions\FraudTransactionBlockedException;
use Modules\Fraud\Exceptions\FraudReviewRequiredException;
use Modules\Fraud\Exceptions\FraudDeviceBlockedException;
use Modules\Fraud\Exceptions\FraudIpBlockedException;
use Modules\Fraud\Exceptions\FraudRapidSuccessiveTxnsException;
use Modules\Fraud\Repositories\FraudEventRepository;
use Modules\Fraud\Repositories\FraudCaseRepository;
use Modules\Fraud\Repositories\FraudRuleRepository;
use Illuminate\Support\Str;
use Modules\Fraud\Models\FraudEvent;

class FraudEngine
{
    private const SCORE_BLOCK = 900;
    private const SCORE_REVIEW = 500;

    public function __construct(
        private readonly VelocityCheckService $velocityCheck,
        private readonly GeolocationAnomalyService $geoAnomaly,
        private readonly DeviceFingerprintService $deviceFingerprint,
        private readonly SanctionsScreeningService $sanctionsScreening,
        private readonly FraudEventRepository $eventRepository,
        private readonly FraudCaseRepository $caseRepository,
        private readonly FraudRuleRepository $ruleRepository,
    ) {}

    public function evaluate(FraudCheckDto $dto): FraudEvent
    {
        $totalScore = 0;
        $matchedRuleIds = [];
        $checkResults = [];

        // 1. Device & IP blacklist
        $deviceScore = $this->deviceFingerprint->check($dto->deviceId, $dto->ipAddress);
        if ($deviceScore > 0) {
            $totalScore += $deviceScore;
            $checkResults['device_blacklist'] = $deviceScore;
        }

        // 2. Velocity check
        $velocityScore = $this->velocityCheck->check($dto->actorId ?? '', $dto->eventType);
        if ($velocityScore > 0) {
            $totalScore += $velocityScore;
            $checkResults['velocity'] = $velocityScore;
        }

        // 3. Geolocation anomaly
        $lastEvent = $this->getLastEventWithLocation($dto->actorId ?? '');
        $geoScore = $this->geoAnomaly->check(
            $dto->latitude,
            $dto->longitude,
            $lastEvent?->latitude,
            $lastEvent?->longitude,
            $lastEvent?->created_at?->timestamp,
        );
        if ($geoScore > 0) {
            $totalScore += $geoScore;
            $checkResults['geolocation'] = $geoScore;
        }

        // 4. Sanctions screening
        $sanctionsScore = $this->sanctionsScreening->check($dto->fullName, $dto->iban, $dto->amount);
        if ($sanctionsScore > 0) {
            $totalScore += $sanctionsScore;
            $checkResults['sanctions'] = $sanctionsScore;
        }

        // Determine decision
        $decision = $this->determineDecision($totalScore);

        // Persist event
        $event = $this->eventRepository->create([
            'id' => (string) Str::ulid(),
            'event_type' => $dto->eventType,
            'actor_id' => $dto->actorId,
            'actor_type' => $dto->actorType,
            'ip_address' => $dto->ipAddress,
            'device_id' => $dto->deviceId,
            'user_agent' => $dto->userAgent,
            'latitude' => $dto->latitude,
            'longitude' => $dto->longitude,
            'metadata' => array_merge($dto->metadata, ['check_results' => $checkResults]),
            'risk_score' => $totalScore,
            'decision' => $decision->value,
        ]);

        // Take action based on decision
        if ($decision === FraudDecision::BLOCK) {
            $this->createFraudCase($event, FraudSeverity::CRITICAL, $totalScore, 'Automatically blocked: risk score ' . $totalScore);
            FraudTransactionBlocked::dispatch($event->id, $dto->actorId ?? '', $dto->eventType, $totalScore, 'Risk score ' . $totalScore . ' exceeded block threshold');
            throw new FraudTransactionBlockedException;
        }

        if ($decision === FraudDecision::REVIEW) {
            $this->createFraudCase($event, FraudSeverity::HIGH, $totalScore, 'Flagged for manual review: risk score ' . $totalScore);
            FraudCaseCreated::dispatch($event->id, $event->id, $dto->actorId ?? '', 'high', $totalScore);
            throw new FraudReviewRequiredException;
        }

        return $event;
    }

    public function checkOrFail(FraudCheckDto $dto): FraudEvent
    {
        $deviceScore = $this->deviceFingerprint->check($dto->deviceId, $dto->ipAddress);
        if ($deviceScore >= 500 && $dto->deviceId) {
            throw new FraudDeviceBlockedException($dto->deviceId ?? '');
        }
        if ($deviceScore >= 400 && $dto->ipAddress) {
            throw new FraudIpBlockedException($dto->ipAddress ?? '');
        }

        $velocityScore = $this->velocityCheck->check($dto->actorId ?? '', $dto->eventType);
        if ($velocityScore >= 400) {
            throw new FraudRapidSuccessiveTxnsException;
        }

        return $this->evaluate($dto);
    }

    private function determineDecision(int $score): FraudDecision
    {
        return match (true) {
            $score >= self::SCORE_BLOCK => FraudDecision::BLOCK,
            $score >= self::SCORE_REVIEW => FraudDecision::REVIEW,
            $score > 0 => FraudDecision::FLAG,
            default => FraudDecision::ALLOW,
        };
    }

    private function createFraudCase(FraudEvent $event, FraudSeverity $severity, int $riskScore, string $description): void
    {
        $case = $this->caseRepository->create([
            'id' => (string) Str::ulid(),
            'fraud_event_id' => $event->id,
            'actor_id' => $event->actor_id,
            'actor_type' => $event->actor_type,
            'status' => 'open',
            'severity' => $severity->value,
            'risk_score' => $riskScore,
            'description' => $description,
            'evidence' => $event->metadata,
        ]);

        FraudCaseCreated::dispatch($case->id, $event->id, $event->actor_id ?? '', $severity->value, $riskScore);
    }

    private function getLastEventWithLocation(string $actorId): ?FraudEvent
    {
        return FraudEvent::where('actor_id', $actorId)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderByDesc('created_at')
            ->first();
    }
}
