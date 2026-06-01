# 06 - الموديلز مع العلاقات (Eloquent Models)

## Settlement Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Settlement extends Model
{
    protected $fillable = [
        'agent_id', 'amount', 'fee', 'status',
        'bank_account', 'notes', 'completed_at'
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
```

## Agent Model (Settlement scope)

```php
public function settlements(): HasMany
{
    return $this->hasMany(Settlement::class);
}

public function pendingSettlements(): HasMany
{
    return $this->hasMany(Settlement::class)->where('status', 'pending');
}
```
