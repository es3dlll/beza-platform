<?php

declare(strict_types=1);

namespace Modules\Savings\Repositories;

use Modules\Savings\Models\SavingsTransaction;

final class SavingsTransactionRepository
{
    public function create(array $data): SavingsTransaction
    {
        return SavingsTransaction::create($data);
    }

    public function findByAccount(string $accountId, int $perPage = 15): iterable
    {
        return SavingsTransaction::where('savings_account_id', $accountId)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function sumByTypeSince(string $accountId, string $type, string $since): int
    {
        return (int) SavingsTransaction::where('savings_account_id', $accountId)
            ->where('type', $type)
            ->where('created_at', '>=', $since)
            ->sum('amount');
    }
}
