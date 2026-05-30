<?php

declare(strict_types=1);

namespace Modules\Fraud\Repositories;

use Modules\Fraud\Models\FraudCase;

class FraudCaseRepository
{
    public function create(array $data): FraudCase
    {
        return FraudCase::create($data);
    }

    public function findOpenByActor(string $actorId): iterable
    {
        return FraudCase::where('actor_id', $actorId)
            ->whereIn('status', ['open', 'under_review'])
            ->get();
    }

    public function update(string $id, array $data): FraudCase
    {
        $case = FraudCase::findOrFail($id);
        $case->update($data);
        return $case->fresh();
    }

    public function findById(string $id): ?FraudCase
    {
        return FraudCase::find($id);
    }

    public function paginate(int $perPage = 15, ?string $status = null): iterable
    {
        $query = FraudCase::query();
        if ($status) {
            $query->where('status', $status);
        }
        return $query->orderByDesc('created_at')->paginate($perPage);
    }
}
