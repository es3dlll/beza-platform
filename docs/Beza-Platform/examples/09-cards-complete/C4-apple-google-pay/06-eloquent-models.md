# 06 - نماذج Eloquent (Eloquent Models)

## WalletToken Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WalletToken extends Model
{
    protected $fillable = [
        'card_id', 'device_id', 'device_type', 'token', 'status'
    ];

    protected $hidden = ['token'];

    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByDevice($query, $deviceId)
    {
        return $query->where('device_id', $deviceId);
    }
}
```

## Card Model (Token relation)

```php
public function walletTokens(): HasMany
{
    return $this->hasMany(WalletToken::class);
}
```
