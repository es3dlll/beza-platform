<?php

declare(strict_types=1);

namespace Modules\Fraud\Services;

use Modules\Fraud\Repositories\FraudEventRepository;

class VelocityCheckService
{
    private const DEFAULT_RULES = [
        ['event_type' => 'login', 'max_count' => 10, 'window' => 300],
        ['event_type' => 'payment', 'max_count' => 5, 'window' => 60],
        ['event_type' => 'registration', 'max_count' => 3, 'window' => 3600],
        ['event_type' => 'otp_request', 'max_count' => 5, 'window' => 300],
    ];

    public function __construct(
        private readonly FraudEventRepository $eventRepository,
    ) {}

    public function check(string $actorId, string $eventType): int
    {
        $riskScore = 0;

        foreach (self::DEFAULT_RULES as $rule) {
            if ($rule['event_type'] !== $eventType) {
                continue;
            }

            $count = $this->eventRepository->countByActorSince(
                $actorId,
                $eventType,
                $rule['window'],
            );

            if ($count >= $rule['max_count'] * 2) {
                $riskScore += 400;
            } elseif ($count >= $rule['max_count']) {
                $riskScore += 200;
            }
        }

        return $riskScore;
    }
}
