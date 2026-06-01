# 10 - طبقة الخدمة - المحفظة (Service Layer - Wallet)

## CardWalletService

```php
<?php

namespace App\Services\Card;

use App\Models\Card;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

class CardWalletService
{
    public function reserveBalanceForCard(Wallet $wallet, Card $card, float $amount): void
    {
        DB::transaction(function () use ($wallet, $card, $amount) {
            $lockedWallet = Wallet::where('id', $wallet->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedCard = Card::where('id', $card->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedCard->status !== 'active' && $lockedCard->status !== 'frozen') {
                throw new \RuntimeException('Card is not eligible for balance reservation');
            }

            if ($lockedWallet->balance < $amount) {
                throw new \RuntimeException('Insufficient wallet balance');
            }

            $lockedWallet->decrement('balance', $amount);
            $lockedCard->increment('hold_balance', $amount);
        }, attempts: 3);
    }

    public function releaseReservedBalance(Wallet $wallet, Card $card, float $amount): void
    {
        DB::transaction(function () use ($wallet, $card, $amount) {
            $lockedWallet = Wallet::where('id', $wallet->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedCard = Card::where('id', $card->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedCard->hold_balance < $amount) {
                throw new \RuntimeException('Insufficient hold balance to release');
            }

            $lockedWallet->increment('balance', $amount);
            $lockedCard->decrement('hold_balance', $amount);
        }, attempts: 3);
    }
}
```

## العمليات

| العملية | الوصف |
|---------|-------|
| reserveBalanceForCard | حجز مبلغ من المحفظة لصالح البطاقة (يُستخدم عند تجميد بطاقة عليها معاملات معلقة) |
| releaseReservedBalance | إلغاء حجز المبلغ وإعادته للمحفظة (يُستخدم عند إلغاء التجميد أو إغلاق البطاقة) |
