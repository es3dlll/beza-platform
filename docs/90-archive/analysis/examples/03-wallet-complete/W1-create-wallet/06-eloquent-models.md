# 06 - الموديلز مع العلاقات والـ Casts (Eloquent Models)

## User Model

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

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }

    protected $fillable = [
        'uuid', 'name', 'email', 'phone', 'password', 'pin_code',
        'status', 'kyc_status', 'avatar', 'fcm_token',
        'two_factor_secret', 'two_factor_recovery_codes',
        'is_admin', 'is_merchant', 'is_agent', 'preferences',
        'device_id', 'last_login_ip', 'last_login_at',
    ];

    protected $hidden = [
        'password', 'pin_code', 'two_factor_secret', 'two_factor_recovery_codes',
    ];

    protected $casts = [
        'phone_verified_at'    => 'datetime',
        'email_verified_at'    => 'datetime',
        'is_admin'             => 'boolean',
        'is_merchant'          => 'boolean',
        'is_agent'             => 'boolean',
        'preferences'          => 'json',
        'last_login_at'        => 'datetime',
    ];

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

    public function sentTransactions(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(Transaction::class, Wallet::class, 'user_id', 'from_wallet_id');
    }

    public function receivedTransactions(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(Transaction::class, Wallet::class, 'user_id', 'to_wallet_id');
    }

    protected static function booted(): void
    {
        static::created(function (User $user) {
            // يتم إنشاء المحافظ عبر Listener
            // معرف في EventServiceProvider
        });
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
        'frozen_balance' => 'decimal:2',
        'is_active'      => 'boolean',
    ];

    protected $appends = ['available_balance'];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sentTransactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Transaction::class, 'from_wallet_id');
    }

    public function receivedTransactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Transaction::class, 'to_wallet_id');
    }

    public function getAvailableBalanceAttribute(): float
    {
        return $this->balance - $this->frozen_balance;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCurrency($query, string $currency)
    {
        return $query->where('currency', $currency);
    }
}
```

## Transaction Model

```php
<?php
// app/Models/Transaction.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'from_wallet_id', 'to_wallet_id', 'amount', 'amount_in_usd',
        'type', 'status', 'reference_number', 'description',
        'fee', 'metadata', 'completed_at',
    ];

    protected $casts = [
        'amount'        => 'decimal:2',
        'amount_in_usd' => 'decimal:2',
        'fee'           => 'decimal:2',
        'metadata'      => 'json',
        'completed_at'  => 'datetime',
    ];

    public function fromWallet(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'from_wallet_id');
    }

    public function toWallet(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'to_wallet_id');
    }

    public function sender(): \Illuminate\Database\Eloquent\Relations\HasOneThrough
    {
        return $this->hasOneThrough(
            User::class, Wallet::class,
            'id', 'id', 'from_wallet_id', 'user_id'
        );
    }

    public function receiver(): \Illuminate\Database\Eloquent\Relations\HasOneThrough
    {
        return $this->hasOneThrough(
            User::class, Wallet::class,
            'id', 'id', 'to_wallet_id', 'user_id'
        );
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public static function generateReferenceNumber(): string
    {
        $prefix = 'BZ';
        $timestamp = now()->format('ymdHis');
        $random = strtoupper(substr(uniqid(), -6));
        return "{$prefix}{$timestamp}{$random}";
    }

    public function markCompleted(): void
    {
        $this->update([
            'status'       => 'completed',
            'completed_at' => now(),
        ]);
    }
}
```

## مخطط العلاقات الكامل

```
┌─────────────┐
│    User     │
├─────────────┤
│ id          │
│ phone       │──┐
│ name        │  │
│ status      │  │
└─────────────┘  │
                 │ 1
                 ▼
        ┌──────────────────┐
        │     Wallet       │  ← 2 لكل User (SYP + USD)
        ├──────────────────┤
        │ id               │
        │ user_id          │──┐
        │ currency         │  │
        │ balance          │  │
        │ wallet_number    │  │
        └──────────────────┘  │
                              │ 1 (to_wallet)
         ┌────────────────────┘
         ▼
        ┌──────────────────────┐
        │    Transaction       │  ← type=deposit للهدية
        ├──────────────────────┤
        │ id                   │
        │ to_wallet_id         │── FK → wallets.id
        │ amount: 5.00         │
        │ type: deposit        │
        │ status: completed    │
        │ reference_number     │
        │ description: "هدية"  │
        └──────────────────────┘
```
