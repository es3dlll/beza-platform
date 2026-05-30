<?php

declare(strict_types=1);

namespace Modules\Savings\Repositories;

use Modules\Savings\Models\SavingsGoal;

final class SavingsGoalRepository
{
    public function create(array $data): SavingsGoal
    {
        return SavingsGoal::create($data);
    }

    public function findById(string $id): ?SavingsGoal
    {
        return SavingsGoal::find($id);
    }

    public function update(string $id, array $data): SavingsGoal
    {
        $goal = SavingsGoal::findOrFail($id);
        $goal->update($data);
        return $goal->fresh();
    }

    public function findByUser(string $userId): iterable
    {
        return SavingsGoal::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->get();
    }

    public function findActiveWithAutoSweep(): iterable
    {
        return SavingsGoal::where('auto_sweep_enabled', true)
            ->where('status', 'active')
            ->get();
    }
}
