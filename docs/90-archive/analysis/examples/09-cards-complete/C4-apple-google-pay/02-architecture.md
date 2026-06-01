# 02 - البنية المعمارية (Architecture) - Apple Pay و Google Pay

## Layer Stack

```
Flutter/React SPA --> API Gateway --> WalletEnrollController --> DigitalWalletService --> Database
                          |                      |                         |
                     Apple/Google Pay       Validation            Payment Network Gateway
                     SDK Integration
```

## Device Tokenization Flow

1. **User** initiates wallet enrollment from mobile app
2. **Device SDK** (Apple Pay/Google Pay) generates a device-specific key pair
3. **Device** sends public key + device info to Beza API
4. **DigitalWalletService** creates a DAN (Device Account Number) token:
   - Replaces the real PAN with a device-specific token
   - Encrypts the DAN with the device's public key
   - Stores the DAN mapping in `wallet_enrollments` table
5. **Payment Network** (Visa/Mastercard/Mada) is notified of the new DAN
6. **Response** contains the encrypted DAN and wallet metadata

## DAN Generation

```php
<?php

namespace App\Services;

use App\Models\Card;
use App\Models\WalletEnrollment;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class DigitalWalletService
{
    public function enroll(Card $card, string $walletType, string $deviceId): WalletEnrollment
    {
        $dan = $this->generateDan($card);
        $devicePublicKey = $this->getDevicePublicKey($deviceId);

        return DB::transaction(function () use ($card, $walletType, $deviceId, $dan, $devicePublicKey) {
            $enrollment = WalletEnrollment::create([
                'card_id' => $card->id,
                'user_id' => $card->user_id,
                'wallet_type' => $walletType,
                'device_id' => $deviceId,
                'dan_token' => Crypt::encryptString($dan),
                'dan_suffix' => substr($dan, -4),
                'status' => 'active',
                'enrolled_at' => now(),
                'expires_at' => now()->addYears(2),
            ]);

            event(new CardEnrolledInWallet($enrollment, $card->user));

            return $enrollment;
        }, attempts: 3);
    }

    private function generateDan(Card $card): string
    {
        // DAN format: 6-digit BIN + 9-digit random + 1 check digit = 16 digits
        $bin = substr($card->card_number, 0, 6);
        $random = str_pad(random_int(0, 999999999), 9, '0', STR_PAD_LEFT);
        $dan = $bin . $random;
        $checkDigit = $this->luhnCheckDigit($dan);
        return $dan . $checkDigit;
    }

    private function luhnCheckDigit(string $number): int
    {
        $sum = 0;
        for ($i = 0; $i < strlen($number); $i++) {
            $digit = (int)$number[$i];
            if ($i % 2 === 0) {
                $digit *= 2;
                if ($digit > 9) $digit -= 9;
            }
            $sum += $digit;
        }
        return (10 - ($sum % 10)) % 10;
    }
}
```

## Payment Network Interaction

| Network | Integration | Token Format |
|---------|-------------|--------------|
| Visa | VTS (Visa Token Service) | 16-digit, starts with 4 |
| Mastercard | MDES (Mastercard Digital Enablement Service) | 16-digit, starts with 5 |
| Mada | Mada Token Service | 16-digit, Saudi BIN range |

## Related Files

- Controller: `WalletEnrollController`
- Service: `DigitalWalletService`
- Request: `WalletEnrollRequest`
- Model: `WalletEnrollment`
- Event: `CardEnrolledInWallet`
- Listener: `SendWalletEnrollNotification`
