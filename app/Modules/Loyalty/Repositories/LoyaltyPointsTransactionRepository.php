<?php

declare(strict_types=1);

namespace Modules\Loyalty\Repositories;

use Modules\Loyalty\Models\LoyaltyPointsTransaction;

class LoyaltyPointsTransactionRepository
{
    public function create(array $data): LoyaltyPointsTransaction
    {
        return LoyaltyPointsTransaction::create($data);
    }

    public function findByUser(string $userId, int $perPage = 15): iterable
    {
        return LoyaltyPointsTransaction::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }
}
