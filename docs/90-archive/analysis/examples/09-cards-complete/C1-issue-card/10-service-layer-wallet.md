# 10 - طبقة الخدمة (Service Layer)

```php
<?php
namespace App\Services\Card;
use App\Models\Wallet;
use App\Models\Card;
use Illuminate\Support\Facades\DB;

class CardWalletService
{
    public function loadFromWallet(Wallet $wallet, Card $card, float $amount): void
    {
        DB::transaction(function () use ($wallet, $card, $amount) {
            $locked = Wallet::where('id', $wallet->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->available_balance < $amount) {
                throw new \RuntimeException('رصيد غير كافٍ في المحفظة');
            }

            DB::update(
                'UPDATE wallets SET balance = balance - ? WHERE id = ? AND balance >= ?',
                [$amount, $wallet->id, $amount]
            );

            DB::update(
                'UPDATE cards SET balance = balance + ?, card_load = card_load + ? WHERE id = ?',
                [$amount, $amount, $card->id]
            );
        }, attempts: 3);
    }

    public function refundToWallet(Wallet $wallet, float $amount): void
    {
        DB::update(
            'UPDATE wallets SET balance = balance + ? WHERE id = ?',
            [$amount, $wallet->id]
        );
    }

    public function freezeBalance(Card $card): void
    {
        DB::transaction(function () use ($card) {
            $locked = Card::where('id', $card->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== 'active') {
                throw new \RuntimeException('البطاقة غير نشطة');
            }

            DB::update(
                'UPDATE cards SET frozen_balance = balance, status = ? WHERE id = ? AND status = ?',
                ['frozen', $card->id, 'active']
            );
        }, attempts: 3);
    }

    public function unfreezeBalance(Card $card): void
    {
        DB::transaction(function () use ($card) {
            $locked = Card::where('id', $card->id)
                ->lockForUpdate()
                ->firstOrFail();

            DB::update(
                'UPDATE cards SET frozen_balance = 0, status = ? WHERE id = ? AND status = ?',
                ['active', $card->id, 'frozen']
            );
        }, attempts: 3);
    }

    public function holdAmount(Card $card, float $amount): void
    {
        $affected = DB::update(
            'UPDATE cards SET balance = balance - ?, hold_balance = hold_balance + ? WHERE id = ? AND balance >= ?',
            [$amount, $amount, $card->id, $amount]
        );
        if ($affected === 0) throw new \RuntimeException('فشل تعليق المبلغ');
    }

    public function releaseHold(Card $card, float $amount): void
    {
        DB::update(
            'UPDATE cards SET hold_balance = hold_balance - ?, balance = balance + ? WHERE id = ? AND hold_balance >= ?',
            [$amount, $amount, $card->id, $amount]
        );
    }
}
```

## العمليات المسموحة

| العملية | الوصف |
|---------|-------|
| loadFromWallet | خصم من المحفظة وإيداع في البطاقة |
| refundToWallet | إعادة رصيد من البطاقة إلى المحفظة |
| freezeBalance | تجميد رصيد البطاقة بالكامل |
| unfreezeBalance | إلغاء التجميد وإعادة الرصيد |
| holdAmount | تعليق مبلغ لعملية معلقة |
| releaseHold | تحرير المبلغ المعلق |
