# 09 - طبقة الخدمة (Service Layer)

## CardIssuanceService

```php
<?php

namespace App\Services\Card;

use App\Models\Card;
use App\Models\User;
use App\Models\Wallet;
use App\Events\CardIssued;
use App\Exceptions\Card\CardIssuanceFailedException;
use App\Exceptions\Card\CardLimitExceededException;
use App\Exceptions\Card\CardAlreadyExistsException;
use Illuminate\Support\Facades\DB;

class CardIssuanceService
{
    private const BIN = '639285';
    private const MAX_ACTIVE_CARDS = 5;

    public function issue(
        User $user,
        string $cardType,
        string $currency,
        float $dailyLimit,
        float $cardLoad = 0,
        ?int $walletId = null,
    ): array {
        $this->validateUserLimits($user, $cardType);

        return DB::transaction(function () use ($user, $cardType, $currency, $dailyLimit, $cardLoad, $walletId) {
            $wallet = Wallet::where('id', $walletId)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($wallet->balance < $cardLoad) {
                throw new CardIssuanceFailedException('رصيد غير كافٍ في المحفظة');
            }

            $pan = $this->generateCardNumber();
            $cvv = $this->generateCvv();
            $expiryDate = $this->calculateExpiryDate();

            $card = Card::create([
                'user_id' => $user->id,
                'card_type' => $cardType,
                'currency' => $currency,
                'pan_encrypted' => encrypt($pan),
                'pan_masked' => '**** **** **** ' . substr($pan, -4),
                'expiry_date' => $expiryDate,
                'cvv_hash' => bcrypt($cvv),
                'status' => $cardType === 'virtual' ? 'active' : 'pending',
                'daily_limit' => $dailyLimit,
                'daily_used' => 0,
                'monthly_limit' => $dailyLimit * 30,
                'monthly_used' => 0,
                'balance' => $cardLoad,
                'card_load' => $cardLoad,
                'issued_at' => now(),
            ]);

            if ($cardLoad > 0) {
                $wallet->decrement('balance', $cardLoad);
            }

            CardIssued::dispatch($card, $user);

            return ['card' => $card, 'cvv' => $cvv];
        }, attempts: 3);
    }

    private function generateCardNumber(): string
    {
        do {
            $number = self::BIN . str_pad((string) random_int(0, 999999999999), 12, '0', STR_PAD_LEFT);
            $number .= $this->luhnCheckDigit($number);
        } while (Card::where('pan_encrypted', encrypt($number))->exists());

        return $number;
    }

    private function luhnCheckDigit(string $digits): string
    {
        $sum = 0;
        $alt = true;
        for ($i = strlen($digits) - 1; $i >= 0; $i--) {
            $d = (int) $digits[$i];
            if ($alt) {
                $d *= 2;
                if ($d > 9) $d -= 9;
            }
            $sum += $d;
            $alt = !$alt;
        }
        return (string) ((10 - ($sum % 10)) % 10);
    }

    private function generateCvv(): string
    {
        return str_pad((string) random_int(0, 999), 3, '0', STR_PAD_LEFT);
    }

    private function calculateExpiryDate(): string
    {
        return now()->addYears(4)->format('Y-m-d');
    }

    private function validateUserLimits(User $user, string $cardType): void
    {
        $activeCount = Card::where('user_id', $user->id)
            ->whereIn('status', ['active', 'pending'])
            ->count();

        if ($activeCount >= self::MAX_ACTIVE_CARDS) {
            throw new CardLimitExceededException('تم تجاوز الحد الأقصى للبطاقات النشطة (5)');
        }

        if ($cardType === 'virtual') {
            $existing = Card::where('user_id', $user->id)
                ->where('card_type', 'virtual')
                ->where('status', 'active')
                ->exists();

            if ($existing) {
                throw new CardAlreadyExistsException('لديك بالفعل بطاقة افتراضية نشطة');
            }
        }
    }
}
```

### Flow
1. Validate user limits (max 5 cards, no duplicate virtual card)
2. DB::transaction with pessimistic lock on wallet
3. Generate Luhn-valid PAN with collision retry
4. Generate CVV and calculate expiry date
5. Create card record with encrypted PAN and hashed CVV
6. Decrement wallet balance if card_load > 0
7. Dispatch CardIssued event
8. Return card details with plain CVV
