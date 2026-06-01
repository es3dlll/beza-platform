# 06 - الموديلز مع العلاقات والـ Casts — المصادقة الثنائية (2FA)

## User Model (جزء 2FA)

```php
<?php
// app/Models/User.php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Crypt;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }

    protected $fillable = [
        'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed',
    ];

    protected $hidden = [
        'two_factor_secret', 'two_factor_recovery_codes',
    ];

    protected $casts = [
        'two_factor_confirmed' => 'boolean',
    ];

    // === دوال 2FA ===

    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_confirmed && !is_null($this->two_factor_secret);
    }

    public function isTwoFactorRequiredForAmount(float $amountUsd): bool
    {
        // إجباري للمبالغ > 1000 USD
        return $amountUsd > 1000;
    }

    public function getTwoFactorSecretAttribute(?string $value): ?string
    {
        if (is_null($value)) return null;
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function setTwoFactorSecretAttribute(?string $value): void
    {
        $this->attributes['two_factor_secret'] = $value
            ? Crypt::encryptString($value)
            : null;
    }

    public function enableTwoFactor(string $secret): void
    {
        $this->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_confirmed' => false,
        ])->save();
    }

    public function confirmTwoFactor(): void
    {
        $this->forceFill(['two_factor_confirmed' => true])->save();
    }

    public function disableTwoFactor(): void
    {
        $this->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed' => false,
        ])->save();
    }

    public function getRecoveryCodes(): array
    {
        return json_decode($this->two_factor_recovery_codes ?? '[]', true);
    }

    public function setRecoveryCodes(array $codes): void
    {
        $this->forceFill([
            'two_factor_recovery_codes' => json_encode($codes),
        ])->save();
    }

    public function useRecoveryCode(string $code): bool
    {
        $codes = $this->getRecoveryCodes();
        $key = array_search($code, $codes);

        if ($key === false) return false;

        unset($codes[$key]);
        $this->setRecoveryCodes(array_values($codes));

        return true;
    }
}
```

## دوال التشفير

```php
<?php
// في AppServiceProvider أو TwoFactorService

use Illuminate\Support\Facades\Crypt;

// التشفير يتم تلقائياً عبر Accessor/Mutator
// Crypt::encryptString($secret) → للتخزين
// Crypt::decryptString($stored) → للقراءة
```
