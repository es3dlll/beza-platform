# 06 - الموديلز مع العلاقات والـ Casts (Eloquent Models)

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

## Transaction Model (مع دوال الصرافة)

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

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
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

    /**
     * إنشاء معاملة صرافة
     */
    public static function createExchange(
        Wallet $fromWallet,
        Wallet $toWallet,
        float  $amount,
        float  $amountInUsd,
        float  $fee,
        array  $metadata = [],
    ): self {
        return self::create([
            'from_wallet_id'   => $fromWallet->id,
            'to_wallet_id'     => $toWallet->id,
            'amount'           => $amount,
            'amount_in_usd'    => $amountInUsd,
            'type'             => 'exchange',
            'status'           => 'completed',
            'reference_number' => self::generateReferenceNumber(),
            'description'      => "صرافة: {$fromWallet->currency} → {$toWallet->currency}",
            'fee'              => $fee,
            'metadata'         => $metadata,
            'completed_at'     => now(),
        ]);
    }
}
```

## ExchangeRate Model (اختياري)

```php
<?php
// app/Models/ExchangeRate.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    protected $fillable = [
        'from_currency', 'to_currency', 'rate',
        'buy_rate', 'sell_rate', 'fee_percentage',
        'effective_from', 'effective_to',
    ];

    protected $casts = [
        'rate'           => 'decimal:6',
        'buy_rate'       => 'decimal:6',
        'sell_rate'      => 'decimal:6',
        'fee_percentage' => 'decimal:2',
        'effective_from' => 'datetime',
        'effective_to'   => 'datetime',
    ];

    /**
     * الحصول على سعر الصرف الحالي
     */
    public static function getCurrentRate(string $from, string $to): ?self
    {
        return self::where('from_currency', $from)
            ->where('to_currency', $to)
            ->where('effective_from', '<=', now())
            ->where(function ($q) {
                $q->whereNull('effective_to')
                  ->orWhere('effective_to', '>=', now());
            })
            ->latest('effective_from')
            ->first();
    }
}
```

## مخطط العلاقات للصرافة

```
┌──────────────┐
│    User      │
│──────────────│
│ id: 1        │
│ name: أحمد   │
└──────┬───────┘
       │
       ├──────────────────────────┐
       │ hasMany                  │ hasMany
       ▼                          ▼
┌──────────────┐          ┌──────────────┐
│ Wallet (SYP) │          │ Wallet (USD) │
│──────────────│          │──────────────│
│ id: 1        │          │ id: 2        │
│ balance: 100K│          │ balance: 500 │
│ number: 62.. │          │ number: 63.. │
└──────┬───────┘          └──────┬───────┘
       │                         │
       │ from_wallet_id          │ to_wallet_id
       └──────────┬──────────────┘
                  ▼
       ┌──────────────────────┐
       │    Transaction       │
       │──────────────────────│
       │ amount: 100000 SYP   │
       │ fee: 1500 SYP        │
       │ converted: 7.58 USD  │
       │ rate: 13000          │
       │ type: exchange       │
       │ metadata: {          │
       │   "rate": 13000,     │
       │   "fee_pct": 1.5     │
       │ }                    │
       └──────────────────────┘
```
