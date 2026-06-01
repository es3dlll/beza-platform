# 06 - نماذج Eloquent (Eloquent Models)

## User Model (جزء تسجيل الدخول)

```php
<?php
// app/Models/User.php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;

    protected $fillable = [
        'name', 'phone', 'password', 'pin_code',
        'status', 'device_id', 'last_login_ip', 'last_login_at',
    ];

    protected $hidden = [
        'password', 'pin_code',
    ];

    protected $casts = [
        'last_login_at'   => 'datetime',
        'phone_verified_at' => 'datetime',
    ];

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }

    // === العلاقات ===

    public function wallets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Wallet::class);
    }

    // === دوال المصادقة ===

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function isBlocked(): bool
    {
        return $this->status === 'blocked';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function updateLoginMetadata(string $ip, ?string $deviceId): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ip,
            'device_id'     => $deviceId,
        ]);
    }

    // === Scopes ===

    public function scopeFindByPhone($query, string $phone)
    {
        return $query->where('phone', $phone);
    }
}
```

## ملاحظات

| الدالة | الاستخدام |
|--------|-----------|
| `isSuspended()` | التحقق من أن الحساب غير معلق |
| `cleanupOldTokens()` | حذف التوكنات القديمة عند تسجيل الدخول |
| `updateLoginMetadata()` | تحديث بيانات آخر دخول |
