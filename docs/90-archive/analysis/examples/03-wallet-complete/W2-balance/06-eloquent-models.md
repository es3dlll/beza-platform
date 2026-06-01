# 06 - الموديلز مع العلاقات والـ Casts (Eloquent Models)

## Wallet Model (كامل)

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

## User Model (جزء المحافظ)

```php
<?php
// app/Models/User.php — فقط أجزاء المحافظ

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

public function getBalanceAttribute(): array
{
    return [
        'syp' => $this->sypWallet ? [
            'balance'        => (float) $this->sypWallet->balance,
            'frozen'         => (float) $this->sypWallet->frozen_balance,
            'available'      => (float) $this->sypWallet->available_balance,
            'wallet_number'  => $this->sypWallet->wallet_number,
        ] : null,
        'usd' => $this->usdWallet ? [
            'balance'        => (float) $this->usdWallet->balance,
            'frozen'         => (float) $this->usdWallet->frozen_balance,
            'available'      => (float) $this->usdWallet->available_balance,
            'wallet_number'  => $this->usdWallet->wallet_number,
        ] : null,
    ];
}
```

## مخطط العلاقات

```
┌─────────────┐          ┌──────────────────┐
│    User     │    1────M│     Wallet       │
│─────────────│          │──────────────────│
│ id          │          │ id               │
│ name        │          │ user_id          │
│ phone       │          │ currency         │
│ status      │          │ balance          │
└─────────────┘          │ frozen_balance   │
                         │ wallet_number    │
                         │ is_active        │
                         └──────────────────┘
                                │
                          ┌─────┴─────┐
                          │           │
                      Currency:    Currency:
                        SYP         USD
                      balance:     balance:
                       X.XX         X.XX
```
