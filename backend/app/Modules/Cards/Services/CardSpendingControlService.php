<?php

declare(strict_types=1);

namespace Modules\Cards\Services;

use Modules\Cards\Models\Card;
use Modules\Cards\Exceptions\CardLimitExceededException;
use Modules\Cards\Repositories\CardTransactionRepository;

class CardSpendingControlService
{
    public function __construct(
        private readonly CardTransactionRepository $transactionRepository,
    ) {}

    public function checkLimits(Card $card, int $amount): void
    {
        if ($amount > $card->single_txn_limit) {
            throw new CardLimitExceededException('single transaction', $card->single_txn_limit, $amount);
        }

        $dailyUsed = $this->spentSince($card->id, now()->startOfDay()->toDateTimeString());
        if (($dailyUsed + $amount) > $card->daily_limit) {
            throw new CardLimitExceededException('daily', $card->daily_limit, $dailyUsed + $amount);
        }

        $weeklyUsed = $this->spentSince($card->id, now()->startOfWeek()->toDateTimeString());
        if (($weeklyUsed + $amount) > $card->weekly_limit) {
            throw new CardLimitExceededException('weekly', $card->weekly_limit, $weeklyUsed + $amount);
        }

        $monthlyUsed = $this->spentSince($card->id, now()->startOfMonth()->toDateTimeString());
        if (($monthlyUsed + $amount) > $card->monthly_limit) {
            throw new CardLimitExceededException('monthly', $card->monthly_limit, $monthlyUsed + $amount);
        }
    }

    public function spentSince(string $cardId, string|\DateTimeInterface $since): int
    {
        return $this->transactionRepository->findByCardAndStatusInPeriod(
            $cardId,
            ['approved', 'settled'],
            $since,
        );
    }
}
