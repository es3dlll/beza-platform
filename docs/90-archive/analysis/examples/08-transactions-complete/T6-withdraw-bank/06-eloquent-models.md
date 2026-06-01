# 06 - الموديلز مع العلاقات والـ Casts (Eloquent Models)

## Model الخاص بالعملية

```php
<?php
// app/Models/BankWithdrawal.php

namespace App\Models;

use Illuminate\Database\EloquentModel;

class BankWithdrawal extends Model
{
    protected $fillable = [
        'user_id', 'amount', 'status', 'metadata',
    ];

    protected $casts = [
        'amount'   => 'decimal:2',
        'metadata' => 'json',
    ];

    public function user(): IlluminateDatabaseEloquentRelationsBelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transaction(): IlluminateDatabaseEloquentRelationsMorphOne
    {
        return $this->morphOne(Transaction::class, 'transactionable');
    }
}
```

## العلاقات مع الجداول الأساسية

```
User (1) ──── (M) BankWithdrawal (M) ──── (1) Transaction
User (1) ──── (M) Wallet
Wallet (1) ──── (M) Transaction (from_wallet_id / to_wallet_id)
```
