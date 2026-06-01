# 06 - موديلات 2FA (Eloquent Models)

## User Model مع 2FA

```php
class User extends Authenticatable implements \Tymon\JWTAuth\Contracts\JWTSubject
{
    use Notifiable;

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }

    protected $hidden = [
        'password', 'pin_code',
        'two_factor_secret',        // ⚠️ مهم: إخفاء secret
        'two_factor_recovery_codes',
    ];

    protected $casts = [
        'two_factor_confirmed_at' => 'datetime',
    ];

    // === دوال 2FA ===

    public function hasTwoFactorEnabled(): bool
    {
        return !is_null($this->two_factor_confirmed_at);
    }

    public function twoFactorSecret(): ?string
    {
        return $this->two_factor_secret
            ? decrypt($this->two_factor_secret)
            : null;
    }

    public function setTwoFactorSecret(string $secret): void
    {
        $this->two_factor_secret = encrypt($secret);
    }

    public function recoveryCodes(): ?array
    {
        return $this->two_factor_recovery_codes
            ? json_decode(decrypt($this->two_factor_recovery_codes), true)
            : null;
    }

    public function setRecoveryCodes(array $codes): void
    {
        $this->two_factor_recovery_codes = encrypt(json_encode($codes));
    }

    public function confirmTwoFactor(): void
    {
        $this->two_factor_confirmed_at = now();
        $this->save();
    }

    public function disableTwoFactor(): void
    {
        $this->two_factor_secret = null;
        $this->two_factor_recovery_codes = null;
        $this->two_factor_confirmed_at = null;
        $this->save();
    }

    public function requiresTwoFactor(float $amount, string $currency): bool
    {
        // 2FA إجباري للمشرفين
        if ($this->is_admin) return true;

        // 2FA إجباري للمعاملات فوق 1000 USD
        if ($amount > 1000 && $currency === 'USD') return true;

        return false;
    }
}
```
