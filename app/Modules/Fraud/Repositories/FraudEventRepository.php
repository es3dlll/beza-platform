<?php

declare(strict_types=1);

namespace Modules\Fraud\Repositories;

use Modules\Fraud\Models\FraudEvent;
use Modules\Fraud\Enums\FraudDecision;

class FraudEventRepository
{
    public function create(array $data): FraudEvent
    {
        return FraudEvent::create($data);
    }

    public function countByActorSince(string $actorId, string $eventType, int $sinceSeconds): int
    {
        $since = now()->subSeconds($sinceSeconds);
        return FraudEvent::where('actor_id', $actorId)
            ->where('event_type', $eventType)
            ->where('created_at', '>=', $since)
            ->count();
    }

    public function countByIpSince(string $ipAddress, string $eventType, int $sinceSeconds): int
    {
        $since = now()->subSeconds($sinceSeconds);
        return FraudEvent::where('ip_address', $ipAddress)
            ->where('event_type', $eventType)
            ->where('created_at', '>=', $since)
            ->count();
    }

    public function recentByActor(string $actorId, int $limit = 10): iterable
    {
        return FraudEvent::where('actor_id', $actorId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public function findById(string $id): ?FraudEvent
    {
        return FraudEvent::find($id);
    }
}
