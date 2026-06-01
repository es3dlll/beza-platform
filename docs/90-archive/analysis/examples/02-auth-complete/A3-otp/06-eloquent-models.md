# 06 - نماذج Eloquent (Eloquent Models)

## User Model (جزء OTP)

```php
<?php
// app/Models/User.php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $casts = [
        'phone_verified_at' => 'datetime',
    ];

    // === دوال OTP ===

    public function hasVerifiedPhone(): bool
    {
        return !is_null($this->phone_verified_at);
    }

    public function markPhoneAsVerified(): void
    {
        $this->update(['phone_verified_at' => now()]);
    }

    // === Scopes ===

    public function scopePhoneVerified($query)
    {
        return $query->whereNotNull('phone_verified_at');
    }

    public function scopePhoneUnverified($query)
    {
        return $query->whereNull('phone_verified_at');
    }
}
```

## ملاحظات

| الدالة | الاستخدام |
|--------|-----------|
| `hasVerifiedPhone()` | التحقق من أن رقم الهاتف موثق |
| `markPhoneAsVerified()` | تحديث timestamp بعد التحقق الناجح |

## نموذج OTP (مؤقت في Redis — ليس Eloquent)

```php
<?php
// app/Models/OtpCode.php — قيمة فقط، ليس Eloquent Model

namespace App\Models;

class OtpCode
{
    public function __construct(
        public readonly string $phone,
        public readonly string $code,
        public readonly int    $expiresAt,
        public int             $attempts = 0,
    ) {}

    public function isValid(): bool
    {
        return now()->timestamp < $this->expiresAt;
    }

    public function isExpired(): bool
    {
        return !$this->isValid();
    }

    public function incrementAttempts(): void
    {
        $this->attempts++;
    }

    public static function generate(string $phone): self
    {
        return new self(
            phone: $phone,
            code: (string) random_int(100000, 999999),
            expiresAt: now()->addMinutes(5)->timestamp,
        );
    }

    public function toArray(): array
    {
        return [
            'code'       => $this->code,
            'expires_at' => $this->expiresAt,
            'attempts'   => $this->attempts,
        ];
    }

    public static function fromArray(string $phone, array $data): self
    {
        return new self(
            phone: $phone,
            code: $data['code'],
            expiresAt: $data['expires_at'],
            attempts: $data['attempts'] ?? 0,
        );
    }
}
```
