<?php

declare(strict_types=1);

namespace Modules\Cards\Repositories;

use Modules\Cards\Models\Card;

final class CardRepository
{
    public function create(array $data): Card
    {
        return Card::create($data);
    }

    public function findById(string $id): ?Card
    {
        return Card::find($id);
    }

    public function update(string $id, array $data): Card
    {
        $card = Card::findOrFail($id);
        $card->update($data);
        return $card->fresh();
    }

    public function findByUser(string $userId): iterable
    {
        return Card::where('user_id', $userId)->orderByDesc('created_at')->get();
    }

    public function findActiveByUser(string $userId): iterable
    {
        return Card::where('user_id', $userId)
            ->where('status', 'active')
            ->get();
    }
}
