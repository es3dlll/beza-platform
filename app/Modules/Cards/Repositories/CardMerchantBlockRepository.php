<?php

declare(strict_types=1);

namespace Modules\Cards\Repositories;

use Modules\Cards\Models\CardMerchantBlock;

class CardMerchantBlockRepository
{
    public function isBlocked(string $cardId, string $merchantCategory): bool
    {
        return CardMerchantBlock::where('card_id', $cardId)
            ->where('merchant_category', $merchantCategory)
            ->exists();
    }

    public function add(array $data): CardMerchantBlock
    {
        return CardMerchantBlock::create($data);
    }

    public function remove(string $cardId, string $merchantCategory): void
    {
        CardMerchantBlock::where('card_id', $cardId)
            ->where('merchant_category', $merchantCategory)
            ->delete();
    }

    public function findByCard(string $cardId): iterable
    {
        return CardMerchantBlock::where('card_id', $cardId)->get();
    }
}
