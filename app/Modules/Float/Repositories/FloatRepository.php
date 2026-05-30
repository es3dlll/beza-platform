<?php

declare(strict_types=1);

namespace Modules\Float\Repositories;

use Illuminate\Support\Collection;
use Modules\Float\Models\FloatAccount;
use Modules\Float\Models\FloatTransaction;
use Illuminate\Support\Str;

final class FloatRepository
{
    public function findById(string $id): ?FloatAccount
    {
        return FloatAccount::find($id);
    }

    public function findByOwner(string $ownerType, string $ownerId): Collection
    {
        return FloatAccount::where('owner_type', $ownerType)
            ->where('owner_id', $ownerId)
            ->get();
    }

    public function findByOwnerAndType(string $ownerType, string $ownerId, string $floatType): ?FloatAccount
    {
        return FloatAccount::where('owner_type', $ownerType)
            ->where('owner_id', $ownerId)
            ->where('float_type', $floatType)
            ->first();
    }

    public function save(FloatAccount $account): FloatAccount
    {
        $account->save();
        return $account;
    }

    public function saveTransaction(FloatTransaction $txn): FloatTransaction
    {
        $txn->save();
        return $txn;
    }

    public function findTransactions(string $accountId, int $limit = 20): Collection
    {
        return FloatTransaction::where('float_account_id', $accountId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
