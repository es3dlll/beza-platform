# 06 - نماذج Eloquent (Eloquent Models)

## User Model

```php
<?php
// app/Models/User.php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Support\Str;

class User extends Authenticatable implements JWTSubject
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

    protected $fillable = [
        'uuid', 'name', 'phone', 'password', 'pin_code',
        'status', 'kyc_status', 'avatar', 'fcm_token',
        'two_factor_secret', 'two_factor_recovery_codes',
        'device_id', 'last_login_ip', 'last_login_at',
        'is_admin', 'is_merchant', 'is_agent', 'preferences',
    ];

    protected $hidden = [
        'password', 'pin_code', 'two_factor_secret', 'two_factor_recovery_codes',
    ];

    protected $casts = [
        'phone_verified_at'      => 'datetime',
        'last_login_at'          => 'datetime',
        'two_factor_confirmed'   => 'boolean',
        'is_admin'               => 'boolean',
        'is_merchant'            => 'boolean',
        'is_agent'               => 'boolean',
        'preferences'            => 'json',
    ];

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            $user->uuid = (string) Str::uuid();
        });
    }

    // === العلاقات ===

    public function wallets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Wallet::class);
    }

    public function sypWallet(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Wallet::class)->where('currency', 'SYP');
    }

    public function usdWallet(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Wallet::class)->where('currency', 'USD');
    }

    // === Scopes ===

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByPhone($query, string $phone)
    {
        return $query->where('phone', $phone);
    }
}
```

## Wallet Model

```php
<?php
// app/Models/Wallet.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    protected $fillable = [
        'user_id', 'currency', 'wallet_number',
        'balance', 'frozen_balance', 'is_active',
    ];

    protected $casts = [
        'balance'        => 'decimal:2',
        'frozen_balance'  => 'decimal:2',
        'is_active'       => 'boolean',
    ];

    protected $appends = ['available_balance'];

    // === العلاقات ===

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // === Accessors ===

    public function getAvailableBalanceAttribute(): float
    {
        return $this->balance - $this->frozen_balance;
    }
}
```

## مخطط العلاقات

```
┌─────────────┐
│    User     │
├─────────────┤
│ id          │
│ uuid        │
│ phone       │──┐
│ name        │  │
│ status      │  │
│ pin_code    │  │
└─────────────┘  │
                 │ 1
                 ▼
        ┌──────────────────┐
        │     Wallet       │
        ├──────────────────┤
        │ id               │
        │ user_id          │
        │ currency         │
        │ balance          │
        │ wallet_number    │
        └──────────────────┘
```
