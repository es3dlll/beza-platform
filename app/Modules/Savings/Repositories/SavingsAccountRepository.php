<?php

declare(strict_types=1);

namespace Modules\Savings\Repositories;

use Modules\Savings\Models\SavingsAccount;

class SavingsAccountRepository
{
    public function create(array $data): SavingsAccount
    {
        return SavingsAccount::create($data);
    }

    public function findByUser(string $userId): ?SavingsAccount
    {
        return SavingsAccount::where('user_id', $userId)->first();
    }

    public function findByGoal(string $savingsGoalId): ?SavingsAccount
    {
        return SavingsAccount::where('savings_goal_id', $savingsGoalId)->first();
    }

    public function findById(string $id): ?SavingsAccount
    {
        return SavingsAccount::find($id);
    }

    public function update(string $id, array $data): SavingsAccount
    {
        $account = SavingsAccount::findOrFail($id);
        $account->update($data);
        return $account->fresh();
    }

    public function findOrCreateForGoal(string $userId, string $goalId): SavingsAccount
    {
        $account = $this->findByGoal($goalId);
        if ($account) {
            return $account;
        }
        return $this->create([
            'id' => (string) \Illuminate\Support\Str::ulid(),
            'user_id' => $userId,
            'savings_goal_id' => $goalId,
            'balance' => 0,
            'currency' => 'SYP',
        ]);
    }
}
