# 09 - طبقة الخدمة (Service Layer)

## CardManagementService

```php
<?php

namespace App\Services\Card;

use App\Events\CardStatusChanged;
use App\Models\Card;
use App\Models\Wallet;
use App\Exceptions\Card\CardFrozenException;
use App\Exceptions\Card\CardNotActiveException;
use App\Exceptions\Card\InvalidStatusTransitionException;
use App\Exceptions\Card\CardLimitUpdateException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CardManagementService
{
    public function toggleFreeze(Card $card): bool
    {
        return DB::transaction(function () use ($card) {
            $locked = Card::where('id', $card->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === 'closed' || $locked->status === 'lost' || $locked->status === 'stolen') {
                throw new CardNotActiveException('Cannot freeze/unfreeze a closed or lost card');
            }

            if ($locked->status === 'frozen') {
                $locked->update(['status' => 'active', 'frozen_at' => null]);
                CardStatusChanged::dispatch($locked, 'frozen', 'active');
                return false;
            }

            if ($locked->status !== 'active') {
                throw new InvalidStatusTransitionException('Card must be active to freeze');
            }

            $locked->update([
                'status' => 'frozen',
                'frozen_at' => now(),
                'frozen_balance' => $locked->balance,
            ]);

            CardStatusChanged::dispatch($locked, 'active', 'frozen');
            return true;
        }, attempts: 3);
    }

    public function updateLimits(Card $card, array $limits): Card
    {
        return DB::transaction(function () use ($card, $limits) {
            $locked = Card::where('id', $card->id)->lockForUpdate()->firstOrFail();

            if (in_array($locked->status, ['closed', 'lost', 'stolen'])) {
                throw new CardLimitUpdateException('Cannot update limits on a closed or lost card');
            }

            if (isset($limits['daily_limit'])) {
                if ($limits['daily_limit'] < $locked->daily_used) {
                    throw new CardLimitUpdateException('New daily limit is less than current daily usage');
                }
                $locked->daily_limit = $limits['daily_limit'];
            }

            if (isset($limits['monthly_limit'])) {
                if ($limits['monthly_limit'] < $locked->monthly_used) {
                    throw new CardLimitUpdateException('New monthly limit is less than current monthly usage');
                }
                $locked->monthly_limit = $limits['monthly_limit'];
            }

            $locked->limit_changed_at = now();
            $locked->save();

            return $locked->fresh();
        }, attempts: 3);
    }

    public function changePin(Card $card, string $newPin): void
    {
        DB::transaction(function () use ($card, $newPin) {
            $locked = Card::where('id', $card->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== 'active') {
                throw new CardNotActiveException('Only active cards can change PIN');
            }

            if (Hash::check($newPin, $locked->pin_hash)) {
                throw new InvalidStatusTransitionException('New PIN cannot be the same as the current PIN');
            }

            $locked->update([
                'pin_hash' => Hash::make($newPin),
                'pin_changed_at' => now(),
            ]);
        }, attempts: 3);
    }

    public function closeCard(Card $card, Wallet $wallet): void
    {
        DB::transaction(function () use ($card, $wallet) {
            $lockedCard = Card::where('id', $card->id)->lockForUpdate()->firstOrFail();
            $lockedWallet = Wallet::where('id', $wallet->id)->lockForUpdate()->firstOrFail();

            if ($lockedCard->status === 'closed') {
                throw new CardNotActiveException('Card is already closed');
            }

            $refundAmount = $lockedCard->balance + $lockedCard->hold_balance;

            if ($refundAmount > 0) {
                $lockedWallet->increment('balance', $refundAmount);
            }

            $lockedCard->update([
                'balance' => 0,
                'hold_balance' => 0,
                'status' => 'closed',
            ]);

            CardStatusChanged::dispatch($lockedCard, $lockedCard->getOriginal('status'), 'closed');
        }, attempts: 3);
    }
}
```
