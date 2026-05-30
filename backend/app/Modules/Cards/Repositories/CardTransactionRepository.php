<?php

declare(strict_types=1);

namespace Modules\Cards\Repositories;

use Modules\Cards\Models\CardTransaction;

final class CardTransactionRepository
{
    public function create(array $data): CardTransaction
    {
        return CardTransaction::create($data);
    }

    public function update(string $id, array $data): CardTransaction
    {
        $txn = CardTransaction::findOrFail($id);
        $txn->update($data);
        return $txn->fresh();
    }

    public function findByCard(string $cardId, int $perPage = 15): iterable
    {
        return CardTransaction::where('card_id', $cardId)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function sumByCardSince(string $cardId, string $since): int
    {
        return (int) CardTransaction::where('card_id', $cardId)
            ->where('created_at', '>=', $since)
            ->whereIn('status', ['approved', 'settled'])
            ->sum('amount');
    }

    public function findByCardAndStatusInPeriod(string $cardId, array $statuses, string $since): int
    {
        return (int) CardTransaction::where('card_id', $cardId)
            ->whereIn('status', $statuses)
            ->where('created_at', '>=', $since)
            ->sum('amount');
    }
}
